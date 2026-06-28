package com.deyeducation.app;

import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
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

    private void request(String method, String path, JSONObject body, boolean auth, Callback callback) {
        IO.execute(() -> {
            HttpURLConnection conn = null;
            try {
                String base = session.getBaseUrl();
                if (base.endsWith("/")) {
                    base = base.substring(0, base.length() - 1);
                }
                String requestPath = path;
                if (!requestPath.startsWith("/")) {
                    requestPath = "/" + requestPath;
                }
                conn = (HttpURLConnection) new URL(base + requestPath).openConnection();
                conn.setRequestMethod(method);
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                conn.setRequestProperty("Accept", "application/json");
                if (auth) {
                    conn.setRequestProperty("Authorization", "Bearer " + session.getToken());
                }
                if (body != null) {
                    conn.setDoOutput(true);
                    conn.setRequestProperty("Content-Type", "application/json");
                    try (OutputStream os = conn.getOutputStream()) {
                        os.write(body.toString().getBytes(StandardCharsets.UTF_8));
                    }
                }
                int code = conn.getResponseCode();
                BufferedReader reader = new BufferedReader(new InputStreamReader(
                        code >= 400 ? conn.getErrorStream() : conn.getInputStream(),
                        StandardCharsets.UTF_8));
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
            } catch (Exception e) {
                callback.onError(e.getMessage() == null ? "Network error" : e.getMessage());
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        });
    }
}
