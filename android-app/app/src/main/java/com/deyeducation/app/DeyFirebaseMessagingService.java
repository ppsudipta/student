package com.deyeducation.app;

import android.app.PendingIntent;
import android.content.Intent;
import android.os.Build;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

import java.util.Map;

public class DeyFirebaseMessagingService extends FirebaseMessagingService {
    @Override
    public void onNewToken(@NonNull String token) {
        SessionManager session = new SessionManager(this);
        session.setFcmToken(token);
        PushNotificationHelper.sendTokenToServer(this, token);
    }

    @Override
    public void onMessageReceived(@NonNull RemoteMessage message) {
        PushNotificationHelper.ensureChannel(this);

        String title = getString(R.string.app_name);
        String body = getString(R.string.push_default_body);
        if (message.getNotification() != null) {
            if (message.getNotification().getTitle() != null) {
                title = message.getNotification().getTitle();
            }
            if (message.getNotification().getBody() != null) {
                body = message.getNotification().getBody();
            }
        }

        Map<String, String> data = message.getData();
        if (data != null) {
            if (data.containsKey("title") && data.get("title") != null && !data.get("title").isEmpty()) {
                title = data.get("title");
            }
            if (data.containsKey("body") && data.get("body") != null && !data.get("body").isEmpty()) {
                body = data.get("body");
            }
        }

        Intent intent = new Intent(this, MainActivity.class);
        intent.putExtra(MainActivity.EXTRA_SCREEN, "notices");
        int enquiryId = 0;
        if (data != null) {
            if (data.containsKey("screen") && data.get("screen") != null && !data.get("screen").isEmpty()) {
                intent.putExtra(MainActivity.EXTRA_SCREEN, data.get("screen"));
            }
            if (data.containsKey("enquiry_id")) {
                try {
                    enquiryId = Integer.parseInt(String.valueOf(data.get("enquiry_id")));
                } catch (Exception ignored) {
                    enquiryId = 0;
                }
                if (enquiryId > 0) {
                    intent.putExtra(MainActivity.EXTRA_ENQUIRY_ID, enquiryId);
                    intent.putExtra(MainActivity.EXTRA_SCREEN, "enquiry");
                }
            }
            if (data.containsKey("enquiry_subject") && data.get("enquiry_subject") != null) {
                intent.putExtra(MainActivity.EXTRA_ENQUIRY_SUBJECT, data.get("enquiry_subject"));
            }
        }
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);

        int flags = PendingIntent.FLAG_UPDATE_CURRENT;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            flags |= PendingIntent.FLAG_IMMUTABLE;
        }
        int requestCode = enquiryId > 0 ? enquiryId : 1001;
        PendingIntent pendingIntent = PendingIntent.getActivity(this, requestCode, intent, flags);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, PushNotificationHelper.CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_stat_notice)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(new NotificationCompat.BigTextStyle().bigText(body))
                .setAutoCancel(true)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setContentIntent(pendingIntent);

        try {
            NotificationManagerCompat.from(this).notify(
                    enquiryId > 0 ? enquiryId : (int) System.currentTimeMillis(),
                    builder.build());
        } catch (SecurityException ignored) {
            // POST_NOTIFICATIONS denied on Android 13+
        }
    }
}
