package com.deyeducation.app;

public final class AppConfig {
    /** Local XAMPP on same WiFi (phone). Emulator: use http://10.0.2.2/admin/laravel-api/public/api */
    public static final String DEFAULT_BASE_URL = "http://187.127.187.70/api/api";
  //  public static final String DEFAULT_BASE_URL = "http://192.168.1.103/admin/laravel-api/public/api";
    public static final String PREFS_NAME = "dey_app";
    public static final String KEY_BASE_URL = "base_url";
    public static final String KEY_ACCESS_TOKEN = "access_token";
    public static final String KEY_STUDENT_NAME = "student_name";

    private AppConfig() {
    }
}
