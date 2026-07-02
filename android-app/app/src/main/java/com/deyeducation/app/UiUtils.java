package com.deyeducation.app;

import android.app.Activity;
import android.content.Context;
import android.text.Html;
import android.text.TextUtils;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.resource.bitmap.RoundedCorners;

public final class UiUtils {
    private UiUtils() {
    }

    public static void toast(Context context, String message) {
        if (!isContextValid(context)) {
            return;
        }
        Toast.makeText(context, message, Toast.LENGTH_LONG).show();
    }

    public static void loadImage(Context context, String url, ImageView view, int cornerRadiusDp) {
        if (!isContextValid(context) || view == null) {
            return;
        }
        if (isBlankUrl(url)) {
            url = null;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        int radius = (int) (cornerRadiusDp * context.getResources().getDisplayMetrics().density);
        var request = Glide.with(view)
                .load(url)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder);
        if (radius > 0) {
            request.transform(new RoundedCorners(radius)).centerCrop();
        } else {
            request.centerCrop();
        }
        request.into(view);
    }

    public static void loadZoomImage(Context context, String url, ImageView view) {
        if (!isContextValid(context) || view == null) {
            return;
        }
        if (isBlankUrl(url)) {
            url = null;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        Glide.with(view)
                .load(url)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder)
                .fitCenter()
                .into(view);
    }

    public static void bindHtml(TextView view, String html) {
        if (view == null) {
            return;
        }
        if (html == null || html.isEmpty() || "null".equals(html)) {
            view.setText("");
            return;
        }
        try {
            view.setText(Html.fromHtml(html, Html.FROM_HTML_MODE_COMPACT));
        } catch (Exception e) {
            view.setText(html.replaceAll("<[^>]+>", " ").trim());
        }
    }

    public static boolean isContextValid(Context context) {
        if (context == null) {
            return false;
        }
        if (context instanceof Activity activity) {
            return !activity.isFinishing() && !activity.isDestroyed();
        }
        return true;
    }

    private static boolean isBlankUrl(String url) {
        return TextUtils.isEmpty(url) || "null".equalsIgnoreCase(url);
    }

    public static int dp(Context context, int value) {
        return (int) (value * context.getResources().getDisplayMetrics().density + 0.5f);
    }
}
