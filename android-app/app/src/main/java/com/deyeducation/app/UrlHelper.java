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
            return resolveImageUrl(baseUrl, direct);
        }
        return resolveImageUrl(baseUrl, row.optString("image"));
    }

    /**
     * Mirrors PHP {@code ../admin/{path}} from the pages folder:
     * - admin uploads (event/, promotional/, …) → {projectRoot}/admin/{path}
     * - student photos (../img/…) → {projectRoot}/img/{file}
     * - API image_url may be "/admin/event/file.jpg" → prepend host from API base
     */
    public static String resolveImageUrl(String baseUrl, String path) {
        if (TextUtils.isEmpty(path)) {
            return null;
        }
        if (path.startsWith("http://") || path.startsWith("https://")) {
            return path;
        }
        String cleaned = path.replace("\\/", "/");
        if (cleaned.startsWith("/")) {
            String root = projectRootFromApi(baseUrl);
            if (root.endsWith("/")) {
                root = root.substring(0, root.length() - 1);
            }
            return encodeUrl(root + cleaned);
        }
        cleaned = normalizeRelativePath(cleaned);
        String projectRoot = projectRootFromApi(baseUrl);

        if (cleaned.startsWith("admin/")) {
            return encodeUrl(projectRoot + cleaned);
        }
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

    public static boolean isHostedVideoUrl(String url) {
        if (TextUtils.isEmpty(url)) {
            return false;
        }
        String lower = url.toLowerCase();
        return lower.contains("vimeo.com") || lower.contains("youtube.com") || lower.contains("youtu.be");
    }

    /** Build embed URL for Vimeo/YouTube links (mirrors Laravel {@code videoPlayback}). */
    public static String videoEmbedUrl(String source) {
        if (TextUtils.isEmpty(source) || "null".equals(source)) {
            return "";
        }
        String url = source.trim().replace("\\/", "/");
        if (!url.startsWith("http://") && !url.startsWith("https://")) {
            return "";
        }

        java.util.regex.Matcher vimeo = java.util.regex.Pattern
                .compile("vimeo\\.com/(?:video/)?(\\d+)(?:/([A-Za-z0-9]+))?", java.util.regex.Pattern.CASE_INSENSITIVE)
                .matcher(url);
        if (vimeo.find()) {
            String embed = "https://player.vimeo.com/video/" + vimeo.group(1);
            String hash = extractQueryParam(url, "h");
            if (TextUtils.isEmpty(hash) && vimeo.group(2) != null) {
                hash = vimeo.group(2);
            }
            if (!TextUtils.isEmpty(hash)) {
                embed += "?h=" + hash;
            }
            return embed;
        }

        java.util.regex.Matcher youtube = java.util.regex.Pattern
                .compile("(?:youtube\\.com/(?:watch\\?v=|embed/)|youtu\\.be/)([A-Za-z0-9_-]+)", java.util.regex.Pattern.CASE_INSENSITIVE)
                .matcher(url);
        if (youtube.find()) {
            return "https://www.youtube-nocookie.com/embed/" + youtube.group(1);
        }

        if (url.contains("player.vimeo.com") || url.contains("youtube.com/embed")) {
            return url;
        }
        return "";
    }

    private static String extractQueryParam(String url, String key) {
        int q = url.indexOf('?');
        if (q < 0) {
            return "";
        }
        for (String part : url.substring(q + 1).split("&")) {
            int eq = part.indexOf('=');
            if (eq > 0 && key.equals(part.substring(0, eq))) {
                return part.substring(eq + 1);
            }
        }
        return "";
    }
}
