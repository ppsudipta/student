package com.deyeducation.app;

import android.Manifest;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Context;
import android.content.pm.PackageManager;
import android.os.Build;
import android.util.Log;

import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.FragmentActivity;

import com.google.firebase.messaging.FirebaseMessaging;

import org.json.JSONObject;

public final class PushNotificationHelper {
    public static final String CHANNEL_ID = "notices";
    public static final int PERMISSION_REQUEST_CODE = 2401;
    private static final String TAG = "PushNotify";

    private PushNotificationHelper() {
    }

    public static void ensureChannel(Context context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            return;
        }
        NotificationManager manager = context.getSystemService(NotificationManager.class);
        if (manager == null) {
            return;
        }
        NotificationChannel channel = new NotificationChannel(
                CHANNEL_ID,
                context.getString(R.string.push_channel_notices),
                NotificationManager.IMPORTANCE_HIGH
        );
        channel.setDescription(context.getString(R.string.push_channel_notices_desc));
        manager.createNotificationChannel(channel);
    }

    public static void requestPermissionIfNeeded(FragmentActivity activity) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) {
            return;
        }
        if (ContextCompat.checkSelfPermission(activity, Manifest.permission.POST_NOTIFICATIONS)
                == PackageManager.PERMISSION_GRANTED) {
            return;
        }
        ActivityCompat.requestPermissions(
                activity,
                new String[]{Manifest.permission.POST_NOTIFICATIONS},
                PERMISSION_REQUEST_CODE
        );
    }

    public static void registerCurrentToken(Context context) {
        SessionManager session = new SessionManager(context.getApplicationContext());
        if (!session.isLoggedIn()) {
            Log.w(TAG, "skip register: not logged in");
            return;
        }
        ensureChannel(context);
        FirebaseMessaging.getInstance().getToken()
                .addOnSuccessListener(token -> {
                    if (token == null || token.isEmpty()) {
                        Log.w(TAG, "FCM token empty");
                        return;
                    }
                    Log.i(TAG, "FCM token acquired, length=" + token.length());
                    session.setFcmToken(token);
                    sendTokenToServer(context, token);
                })
                .addOnFailureListener(e -> Log.e(TAG, "FCM getToken failed", e));
    }

    public static void sendTokenToServer(Context context, String token) {
        SessionManager session = new SessionManager(context.getApplicationContext());
        if (!session.isLoggedIn() || token == null || token.isEmpty()) {
            return;
        }
        ApiClient api = new ApiClient(session);
        JSONObject body = new JSONObject();
        try {
            body.put("token", token);
            body.put("platform", "android");
        } catch (Exception ignored) {
        }
        api.post("/device-token", body, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                Log.i(TAG, "device token registered: " + json.optString("message"));
            }

            @Override
            public void onError(String message) {
                Log.e(TAG, "device token register failed: " + message);
            }
        });
    }

    public static void unregisterCurrentToken(Context context, Runnable after) {
        SessionManager session = new SessionManager(context.getApplicationContext());
        String token = session.getFcmToken();
        if (!session.isLoggedIn() || token.isEmpty()) {
            if (after != null) {
                after.run();
            }
            return;
        }
        ApiClient api = new ApiClient(session);
        JSONObject body = new JSONObject();
        try {
            body.put("token", token);
        } catch (Exception ignored) {
        }
        api.post("/device-token/unregister", body, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                session.setFcmToken("");
                if (after != null) {
                    after.run();
                }
            }

            @Override
            public void onError(String message) {
                session.setFcmToken("");
                if (after != null) {
                    after.run();
                }
            }
        });
    }
}
