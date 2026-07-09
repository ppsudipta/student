package com.deyeducation.app;

import android.app.Activity;
import android.content.Context;
import android.text.Html;
import android.text.TextUtils;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

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

    public static void setLoaderVisible(@Nullable View loader, boolean visible) {
        if (loader == null) {
            return;
        }
        if (loader instanceof FunLoaderView funLoader) {
            if (visible) {
                funLoader.show();
            } else {
                funLoader.hide();
            }
        } else {
            loader.setVisibility(visible ? View.VISIBLE : View.GONE);
        }
    }

    public static void setupColorfulSwipeRefresh(SwipeRefreshLayout refresh) {
        if (refresh == null) {
            return;
        }
        refresh.setColorSchemeResources(
                R.color.primary,
                R.color.accent_teal,
                R.color.accent,
                R.color.accent_pink,
                R.color.accent_yellow);
        refresh.setProgressBackgroundColorSchemeResource(R.color.white);
    }

    /** Keep toolbar and actions below the system status bar (fixes overlap on edge-to-edge devices). */
    public static void setupViewerWindow(AppCompatActivity activity, View toolbar) {
        if (activity == null || toolbar == null) {
            return;
        }
        WindowCompat.setDecorFitsSystemWindows(activity.getWindow(), false);
        activity.getWindow().setStatusBarColor(ContextCompat.getColor(activity, R.color.primary));
        final int toolbarBase = activity.getResources().getDimensionPixelSize(R.dimen.toolbar_height);
        ViewCompat.setOnApplyWindowInsetsListener(toolbar, (v, windowInsets) -> {
            Insets statusBars = windowInsets.getInsets(WindowInsetsCompat.Type.statusBars());
            ViewGroup.LayoutParams lp = v.getLayoutParams();
            lp.height = toolbarBase + statusBars.top;
            v.setPadding(v.getPaddingLeft(), statusBars.top, v.getPaddingRight(), v.getPaddingBottom());
            v.setLayoutParams(lp);
            return windowInsets;
        });
        ViewCompat.requestApplyInsets(toolbar);
    }
}
