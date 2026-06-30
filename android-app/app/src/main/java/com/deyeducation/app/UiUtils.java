package com.deyeducation.app;

import android.content.Context;
import android.text.TextUtils;
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
        if (context == null || view == null) {
            return;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        int radius = (int) (cornerRadiusDp * context.getResources().getDisplayMetrics().density);
        Object source = TextUtils.isEmpty(url) ? null : url;
        Glide.with(context)
                .load(source)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder)
                .transform(new RoundedCorners(radius))
                .centerCrop()
                .into(view);
    }

    public static void loadZoomImage(Context context, String url, ImageView view) {
        if (context == null || view == null) {
            return;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        Object source = TextUtils.isEmpty(url) ? null : url;
        Glide.with(context)
                .load(source)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder)
                .fitCenter()
                .into(view);
    }

    public static int dp(Context context, int value) {
        return (int) (value * context.getResources().getDisplayMetrics().density + 0.5f);
    }
}
