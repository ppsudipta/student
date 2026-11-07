package com.deyeducation.app;

import android.app.Activity;
import android.content.Context;
import android.graphics.Matrix;
import android.graphics.drawable.Drawable;
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
import com.bumptech.glide.load.DataSource;
import com.bumptech.glide.load.engine.GlideException;
import com.bumptech.glide.load.resource.bitmap.FitCenter;
import com.bumptech.glide.load.resource.bitmap.RoundedCorners;
import com.bumptech.glide.request.RequestListener;
import com.bumptech.glide.request.target.Target;

public final class UiUtils {
    private UiUtils() {
    }

    public static void toast(Context context, String message) {
        if (!isContextValid(context)) {
            return;
        }
        Toast.makeText(context, message, Toast.LENGTH_LONG).show();
    }

    /**
     * Loads remote images with fitCenter so landscape and portrait both show fully
     * (letterboxed) inside a fixed-size container — no cropping.
     */
    public static void loadImage(Context context, String url, ImageView view, int cornerRadiusDp) {
        if (!isContextValid(context) || view == null) {
            return;
        }
        if (isBlankUrl(url)) {
            url = null;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        int radius = (int) (cornerRadiusDp * context.getResources().getDisplayMetrics().density);
        view.setScaleType(ImageView.ScaleType.FIT_CENTER);
        var request = Glide.with(view)
                .load(url)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder);
        if (radius > 0) {
            request = request.transform(new FitCenter(), new RoundedCorners(radius));
        } else {
            request = request.fitCenter();
        }
        request.into(view);
    }

    /**
     * Promo carousel: never crop. Fit inside the frame, pin to the top,
     * center horizontally when leftover space is on the sides; blank band stays at bottom.
     */
    public static void loadPromoImage(Context context, String url, ImageView view) {
        if (!isContextValid(context) || view == null) {
            return;
        }
        if (isBlankUrl(url)) {
            url = null;
        }
        int placeholder = R.drawable.bg_image_placeholder;
        view.setScaleType(ImageView.ScaleType.MATRIX);
        ensurePromoMatrixOnLayout(view);
        Glide.with(view)
                .load(url)
                .placeholder(placeholder)
                .error(placeholder)
                .fallback(placeholder)
                .listener(new RequestListener<Drawable>() {
                    @Override
                    public boolean onLoadFailed(@Nullable GlideException e, Object model,
                                                Target<Drawable> target, boolean isFirstResource) {
                        view.post(() -> applyPromoTopCenterMatrix(view));
                        return false;
                    }

                    @Override
                    public boolean onResourceReady(Drawable resource, Object model,
                                                   Target<Drawable> target, DataSource dataSource,
                                                   boolean isFirstResource) {
                        view.post(() -> applyPromoTopCenterMatrix(view));
                        return false;
                    }
                })
                .into(view);
        view.post(() -> applyPromoTopCenterMatrix(view));
    }

    private static void ensurePromoMatrixOnLayout(ImageView view) {
        Object tag = view.getTag(R.id.sliderImage);
        if (Boolean.TRUE.equals(tag)) {
            return;
        }
        view.setTag(R.id.sliderImage, Boolean.TRUE);
        view.addOnLayoutChangeListener((v, left, top, right, bottom, oldLeft, oldTop, oldRight, oldBottom) -> {
            if (right - left != oldRight - oldLeft || bottom - top != oldBottom - oldTop) {
                applyPromoTopCenterMatrix(view);
            }
        });
    }

    /** Scale to fit, align top, center horizontally. */
    public static void applyPromoTopCenterMatrix(ImageView view) {
        if (view == null) {
            return;
        }
        Drawable drawable = view.getDrawable();
        int viewW = view.getWidth() - view.getPaddingLeft() - view.getPaddingRight();
        int viewH = view.getHeight() - view.getPaddingTop() - view.getPaddingBottom();
        if (drawable == null || viewW <= 0 || viewH <= 0) {
            return;
        }
        int drawableW = drawable.getIntrinsicWidth();
        int drawableH = drawable.getIntrinsicHeight();
        if (drawableW <= 0 || drawableH <= 0) {
            return;
        }

        float scale = Math.min((float) viewW / drawableW, (float) viewH / drawableH);
        float dx = (viewW - drawableW * scale) * 0.5f;
        float dy = 0f;

        Matrix matrix = new Matrix();
        matrix.setScale(scale, scale);
        matrix.postTranslate(dx, dy);
        view.setScaleType(ImageView.ScaleType.MATRIX);
        view.setImageMatrix(matrix);
    }

    public static void loadCircleImage(Context context, String url, ImageView view) {
        loadCircleImage(context, url, view, R.drawable.ic_menu_profile);
    }

    public static void loadCircleImage(Context context, String url, ImageView view, int placeholderRes) {
        if (!isContextValid(context) || view == null) {
            return;
        }
        if (isBlankUrl(url)) {
            url = null;
        }
        var request = Glide.with(view).load(url);
        if (placeholderRes != 0) {
            request = request.placeholder(placeholderRes)
                    .error(placeholderRes)
                    .fallback(placeholderRes);
        }
        request.circleCrop().into(view);
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
