package com.deyeducation.app;

import android.text.TextUtils;

import org.json.JSONObject;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

public final class UrlHelper {
    private UrlHelper() {
    }

    /** Prefer server-provided {@code image_url}, else build from legacy {@code image} path. */
    public static String imageFromJson(String baseUrl, JSONObject row) {
        if (row == null) {
            return null;
        }
        String direct = row.optString("image_url", "");
        if (!TextUtils.isEmpty(direct) && !"null".equals(direct)) {
            return direct;
        }
        return resolveImageUrl(baseUrl, row.optString("image"));
    }

    /**
     * Mirrors PHP {@code ../admin/{path}} from the pages folder:
     * - admin uploads (event/, promotional/, …) → {projectRoot}/admin/{path}
     * - student photos (../img/…) → {projectRoot}/img/{file}
     */
    public static String resolveImageUrl(String baseUrl, String path) {
        if (TextUtils.isEmpty(path)) {
            return null;
        }
        if (path.startsWith("http://") || path.startsWith("https://")) {
            return path;
        }
        String cleaned = normalizeRelativePath(path);
        String projectRoot = projectRootFromApi(baseUrl);

        if (cleaned.startsWith("img/")) {
            return encodeUrl(projectRoot + cleaned);
        }
        return encodeUrl(projectRoot + "admin/" + cleaned);
    }

    public static String projectRootFromApi(String apiBaseUrl) {
        String base = apiBaseUrl == null ? "" : apiBaseUrl.trim();
        if (base.endsWith("/api/api")) {
            base = base.substring(0, base.length() - "/api/api".length());
        } else if (base.endsWith("/api")) {
            base = base.substring(0, base.length() - "/api".length());
        }
        if (base.contains("/laravel-api/public")) {
            base = base.substring(0, base.indexOf("/laravel-api/public"));
        }
        if (!base.endsWith("/")) {
            base = base + "/";
        }
        return base;
    }

    /** @deprecated use {@link #projectRootFromApi(String)} */
    public static String siteBaseFromApi(String apiBaseUrl) {
        return projectRootFromApi(apiBaseUrl) + "admin/";
    }

    private static String normalizeRelativePath(String path) {
        String cleaned = path.replace("\\/", "/");
        while (cleaned.startsWith("../")) {
            cleaned = cleaned.substring(3);
        }
        while (cleaned.startsWith("./")) {
            cleaned = cleaned.substring(2);
        }
        if (cleaned.startsWith("/")) {
            cleaned = cleaned.substring(1);
        }
        return cleaned;
    }

    private static String encodeUrl(String url) {
        int schemeEnd = url.indexOf("://");
        if (schemeEnd < 0) {
            return url;
        }
        String prefix = url.substring(0, schemeEnd + 3);
        String rest = url.substring(schemeEnd + 3);
        int slash = rest.indexOf('/');
        if (slash < 0) {
            return url;
        }
        String host = rest.substring(0, slash);
        String path = rest.substring(slash + 1);
        String[] segments = path.split("/");
        StringBuilder encoded = new StringBuilder(prefix).append(host).append('/');
        for (int i = 0; i < segments.length; i++) {
            if (i > 0) {
                encoded.append('/');
            }
            try {
                encoded.append(URLEncoder.encode(segments[i], "UTF-8").replace("+", "%20"));
            } catch (UnsupportedEncodingException e) {
                encoded.append(segments[i]);
            }
        }
        return encoded.toString();
    }

    public static String cleanHtml(String value) {
        if (value == null) {
            return "";
        }
        return value.replaceAll("<[^>]*>", "")
                .replace("&nbsp;", " ")
                .replace("\\/", "/")
                .trim();
    }
}
