package com.deyeducation.app;

import android.content.Context;
import android.widget.ImageView;
import android.widget.Toast;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.bitmap.RoundedCorners;

public final class UiUtils {
    private UiUtils() {
    }

    public static void toast(Context context, String message) {
        Toast.makeText(context, message, Toast.LENGTH_LONG).show();
    }

    public static void loadImage(Context context, String url, ImageView view, int cornerRadiusDp) {
        if (url == null || url.isEmpty()) {
            return;
        }
        int radius = (int) (cornerRadiusDp * context.getResources().getDisplayMetrics().density);
        Glide.with(context)
                .load(url)
                .transform(new RoundedCorners(radius))
                .centerCrop()
                .into(view);
    }

    public static int dp(Context context, int value) {
        return (int) (value * context.getResources().getDisplayMetrics().density + 0.5f);
    }
}
