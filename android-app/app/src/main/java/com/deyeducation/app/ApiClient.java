package com.deyeducation.app;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.Map;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ApiClient {
    public interface Callback {
        void onSuccess(JSONObject json);

        void onError(String message);
    }

    private static final ExecutorService IO = Executors.newCachedThreadPool();

    private final SessionManager session;

    public ApiClient(SessionManager session) {
        this.session = session;
    }

    public void get(String path, boolean auth, Callback callback) {
        request("GET", path, null, auth, callback);
    }

    public void post(String path, JSONObject body, boolean auth, Callback callback) {
        request("POST", path, body, auth, callback);
    }

    public void patch(String path, boolean auth, Callback callback) {
        request("PATCH", path, null, auth, callback);
    }

    public void patch(String path, JSONObject body, boolean auth, Callback callback) {
        request("PATCH", path, body, auth, callback);
    }

    public void postMultipart(String path, Map<String, String> fields, String fileField,
                              byte[] fileBytes, String fileName, String mimeType,
                              boolean auth, Callback callback) {
        IO.execute(() -> {
            HttpURLConnection conn = null;
            try {
                conn = openConnection(path, "POST", auth);
                String boundary = "----" + UUID.randomUUID();
                conn.setDoOutput(true);
                conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=" + boundary);

                try (OutputStream os = conn.getOutputStream()) {
                    if (fields != null) {
                        for (Map.Entry<String, String> entry : fields.entrySet()) {
                            writeField(os, boundary, entry.getKey(), entry.getValue());
                        }
                    }
                    if (fileBytes != null && fileField != null && fileName != null) {
                        writeFile(os, boundary, fileField, fileName,
                                mimeType == null ? "application/octet-stream" : mimeType, fileBytes);
                    }
                    os.write(("--" + boundary + "--\r\n").getBytes(StandardCharsets.UTF_8));
                }
                deliver(conn, callback);
            } catch (Exception e) {
                callback.onError(e.getMessage() == null ? "Network error" : e.getMessage());
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        });
    }

    private void request(String method, String path, JSONObject body, boolean auth, Callback callback) {
        IO.execute(() -> {
            HttpURLConnection conn = null;
            try {
                conn = openConnection(path, method, auth);
                if (body != null) {
                    conn.setDoOutput(true);
                    conn.setRequestProperty("Content-Type", "application/json");
                    try (OutputStream os = conn.getOutputStream()) {
                        os.write(body.toString().getBytes(StandardCharsets.UTF_8));
                    }
                }
                deliver(conn, callback);
            } catch (Exception e) {
                callback.onError(e.getMessage() == null ? "Network error" : e.getMessage());
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        });
    }

    private HttpURLConnection openConnection(String path, String method, boolean auth) throws Exception {
        String base = session.getBaseUrl();
        if (base.endsWith("/")) {
            base = base.substring(0, base.length() - 1);
        }
        String requestPath = path.startsWith("/") ? path : "/" + path;
        HttpURLConnection conn = (HttpURLConnection) new URL(base + requestPath).openConnection();
        conn.setRequestMethod(method);
        conn.setConnectTimeout(20000);
        conn.setReadTimeout(20000);
        conn.setRequestProperty("Accept", "application/json");
        if (auth) {
            conn.setRequestProperty("Authorization", "Bearer " + session.getToken());
        }
        return conn;
    }

    private void deliver(HttpURLConnection conn, Callback callback) throws Exception {
        int code = conn.getResponseCode();
        InputStream stream = code >= 400 ? conn.getErrorStream() : conn.getInputStream();
        if (stream == null) {
            callback.onError("Request failed (" + code + ")");
            return;
        }
        BufferedReader reader = new BufferedReader(new InputStreamReader(stream, StandardCharsets.UTF_8));
        StringBuilder out = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            out.append(line);
        }
        JSONObject json = new JSONObject(out.toString());
        if (code >= 400) {
            callback.onError(json.optString("message", "Request failed (" + code + ")"));
        } else {
            callback.onSuccess(json);
        }
    }

    private void writeField(OutputStream os, String boundary, String name, String value) throws Exception {
        os.write(("--" + boundary + "\r\n").getBytes(StandardCharsets.UTF_8));
        os.write(("Content-Disposition: form-data; name=\"" + name + "\"\r\n\r\n").getBytes(StandardCharsets.UTF_8));
        os.write((value + "\r\n").getBytes(StandardCharsets.UTF_8));
    }

    private void writeFile(OutputStream os, String boundary, String field, String fileName,
                           String mimeType, byte[] bytes) throws Exception {
        os.write(("--" + boundary + "\r\n").getBytes(StandardCharsets.UTF_8));
        os.write(("Content-Disposition: form-data; name=\"" + field + "\"; filename=\"" + fileName + "\"\r\n")
                .getBytes(StandardCharsets.UTF_8));
        os.write(("Content-Type: " + mimeType + "\r\n\r\n").getBytes(StandardCharsets.UTF_8));
        os.write(bytes);
        os.write("\r\n".getBytes(StandardCharsets.UTF_8));
    }

    public static byte[] readAllBytes(InputStream input) throws Exception {
        ByteArrayOutputStream buffer = new ByteArrayOutputStream();
        byte[] data = new byte[8192];
        int n;
        while ((n = input.read(data)) != -1) {
            buffer.write(data, 0, n);
        }
        return buffer.toByteArray();
    }
}
