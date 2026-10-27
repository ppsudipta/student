package com.deyeducation.app;

import android.content.Context;
import android.content.SharedPreferences;

import java.util.Locale;

public class SessionManager {
    private final SharedPreferences prefs;

    public SessionManager(Context context) {
        prefs = context.getSharedPreferences(AppConfig.PREFS_NAME, Context.MODE_PRIVATE);
        migrateServerUrlIfNeeded();
    }

    /**
     * Old installs keep the previous IP in prefs. When SERVER_CONFIG_VERSION bumps
     * (or a known legacy host is found), force DEFAULT_BASE_URL.
     */
    private void migrateServerUrlIfNeeded() {
        int savedVersion = prefs.getInt(AppConfig.KEY_SERVER_CONFIG_VERSION, 0);
        String savedUrl = prefs.getString(AppConfig.KEY_BASE_URL, "");
        boolean staleHost = isLegacyServerUrl(savedUrl);
        if (savedVersion < AppConfig.SERVER_CONFIG_VERSION || staleHost || savedUrl == null || savedUrl.isEmpty()) {
            prefs.edit()
                    .putString(AppConfig.KEY_BASE_URL, AppConfig.DEFAULT_BASE_URL)
                    .putInt(AppConfig.KEY_SERVER_CONFIG_VERSION, AppConfig.SERVER_CONFIG_VERSION)
                    .apply();
        }
    }

    private static boolean isLegacyServerUrl(String url) {
        if (url == null || url.isEmpty()) {
            return true;
        }
        String lower = url.toLowerCase(Locale.US);
        return lower.contains("187.127.187.70")
                || lower.contains("179.197.249.7")
                || lower.contains("187.127")
                || (lower.contains("/api") && !lower.contains("deyeducation.cloud")
                && lower.matches("(?i).*https?://\\d{1,3}(\\.\\d{1,3}){3}.*"));
    }

    public String getBaseUrl() {
        migrateServerUrlIfNeeded();
        String url = prefs.getString(AppConfig.KEY_BASE_URL, AppConfig.DEFAULT_BASE_URL);
        if (url == null || url.isEmpty()) {
            return AppConfig.DEFAULT_BASE_URL;
        }
        return url;
    }

    public void setBaseUrl(String baseUrl) {
        String value = baseUrl == null || baseUrl.trim().isEmpty()
                ? AppConfig.DEFAULT_BASE_URL
                : baseUrl.trim();
        // Never persist known-legacy hosts again.
        if (isLegacyServerUrl(value)) {
            value = AppConfig.DEFAULT_BASE_URL;
        }
        prefs.edit()
                .putString(AppConfig.KEY_BASE_URL, value)
                .putInt(AppConfig.KEY_SERVER_CONFIG_VERSION, AppConfig.SERVER_CONFIG_VERSION)
                .apply();
    }

    public String getToken() {
        return prefs.getString(AppConfig.KEY_ACCESS_TOKEN, "");
    }

    public void setToken(String token) {
        prefs.edit().putString(AppConfig.KEY_ACCESS_TOKEN, token).apply();
    }

    public String getStudentName() {
        return prefs.getString(AppConfig.KEY_STUDENT_NAME, "");
    }

    public void setStudentName(String name) {
        prefs.edit().putString(AppConfig.KEY_STUDENT_NAME, name).apply();
    }

    public String getFcmToken() {
        return prefs.getString(AppConfig.KEY_FCM_TOKEN, "");
    }

    public void setFcmToken(String token) {
        prefs.edit().putString(AppConfig.KEY_FCM_TOKEN, token == null ? "" : token).apply();
    }

    public boolean isLoggedIn() {
        return !getToken().isEmpty();
    }

    public void clear() {
        prefs.edit()
                .remove(AppConfig.KEY_ACCESS_TOKEN)
                .remove(AppConfig.KEY_STUDENT_NAME)
                .remove(AppConfig.KEY_FCM_TOKEN)
                .putString(AppConfig.KEY_BASE_URL, AppConfig.DEFAULT_BASE_URL)
                .putInt(AppConfig.KEY_SERVER_CONFIG_VERSION, AppConfig.SERVER_CONFIG_VERSION)
                .apply();
    }
}
