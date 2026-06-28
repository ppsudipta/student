package com.deyeducation.app;

import android.content.Context;
import android.content.SharedPreferences;

public class SessionManager {
    private final SharedPreferences prefs;

    public SessionManager(Context context) {
        prefs = context.getSharedPreferences(AppConfig.PREFS_NAME, Context.MODE_PRIVATE);
    }

    public String getBaseUrl() {
        return prefs.getString(AppConfig.KEY_BASE_URL, AppConfig.DEFAULT_BASE_URL);
    }

    public void setBaseUrl(String baseUrl) {
        prefs.edit().putString(AppConfig.KEY_BASE_URL, baseUrl).apply();
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

    public boolean isLoggedIn() {
        return !getToken().isEmpty();
    }

    public void clear() {
        prefs.edit()
                .remove(AppConfig.KEY_ACCESS_TOKEN)
                .remove(AppConfig.KEY_STUDENT_NAME)
                .apply();
    }
}
