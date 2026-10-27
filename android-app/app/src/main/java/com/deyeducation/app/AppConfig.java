package com.deyeducation.app;

public final class AppConfig {
    /** Local XAMPP on same WiFi (phone). Emulator: use http://10.0.2.2/admin/laravel-api/public/api */
    public static final String DEFAULT_BASE_URL = "https://deyeducation.cloud/api/api";
    // If SSL is not ready yet, use: "http://deyeducation.cloud/api/api"
    // Local XAMPP: "http://192.168.1.103/admin/laravel-api/public/api"
    public static final String PREFS_NAME = "dey_app";
    public static final String KEY_BASE_URL = "base_url";
    public static final String KEY_ACCESS_TOKEN = "access_token";
    public static final String KEY_STUDENT_NAME = "student_name";
    public static final String KEY_FCM_TOKEN = "fcm_token";

    /** Official YouTube channel — opens channel + subscribe in YouTube app when installed. */
    public static final String DEFAULT_YOUTUBE_URL = "https://www.youtube.com/@TanoyKumarDey";
    public static final String DEFAULT_YOUTUBE_HANDLE = "@TanoyKumarDey";

    private AppConfig() {
    }
}
