package com.deyeducation.app;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.View;
import android.view.WindowManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.LinearLayout;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

import org.json.JSONObject;

public class VideoActivity extends AppCompatActivity {
    public static final String EXTRA_MATERIAL_ID = "material_id";
    public static final String EXTRA_TITLE = "title";

    private static final String USER_AGENT =
            "Mozilla/5.0 (Linux; Android 10; Mobile) AppleWebKit/537.36 "
                    + "(KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";
    private static final long SLOW_LOAD_HINT_MS = 12000L;

    private WebView webView;
    private FunLoaderView progressBar;
    private LinearLayout contentContainer;
    private FrameLayout fullscreenContainer;
    private SessionManager session;
    private ApiClient api;

    private View customView;
    private WebChromeClient.CustomViewCallback customViewCallback;
    private boolean pageReady;
    private final Handler slowLoadHandler = new Handler(Looper.getMainLooper());
    private final Runnable slowLoadHint = () -> {
        if (!pageReady && progressBar != null && progressBar.getVisibility() == View.VISIBLE) {
            UiUtils.toast(this, getString(R.string.loader_video_slow));
        }
    };

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().setFlags(
                WindowManager.LayoutParams.FLAG_SECURE,
                WindowManager.LayoutParams.FLAG_SECURE);
        setContentView(R.layout.activity_video);

        int materialId = getIntent().getIntExtra(EXTRA_MATERIAL_ID, 0);
        String title = getIntent().getStringExtra(EXTRA_TITLE);

        MaterialToolbar toolbar = findViewById(R.id.videoToolbar);
        UiUtils.setupViewerWindow(this, toolbar);
        toolbar.setTitle(title == null ? getString(R.string.play_video) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        progressBar = findViewById(R.id.videoProgress);
        webView = findViewById(R.id.webView);
        contentContainer = findViewById(R.id.videoContent);
        fullscreenContainer = findViewById(R.id.videoFullscreenContainer);
        session = new SessionManager(this);
        api = new ApiClient(session);

        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);
        settings.setUserAgentString(USER_AGENT);

        hardenWebView(webView);
        webView.setWebViewClient(new SecureWebViewClient());
        webView.setWebChromeClient(new FullscreenChromeClient());

        if (materialId <= 0) {
            UiUtils.toast(this, getString(R.string.video_load_failed));
            finish();
            return;
        }
        fetchAndPlay(materialId);
    }

    private void showVideoLoader() {
        pageReady = false;
        slowLoadHandler.removeCallbacks(slowLoadHint);
        UiUtils.setLoaderVisible(progressBar, true);
        slowLoadHandler.postDelayed(slowLoadHint, SLOW_LOAD_HINT_MS);
    }

    private void hideVideoLoader() {
        pageReady = true;
        slowLoadHandler.removeCallbacks(slowLoadHint);
        UiUtils.setLoaderVisible(progressBar, false);
    }

    private void fetchAndPlay(int materialId) {
        showVideoLoader();
        api.get("/materials/" + materialId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    JSONObject data = json.optJSONObject("data");
                    JSONObject playback = data != null ? data.optJSONObject("playback") : null;
                    String embedUrl = playback != null ? playback.optString("embed_url") : "";
                    if (embedUrl.isEmpty()) {
                        hideVideoLoader();
                        UiUtils.toast(VideoActivity.this, getString(R.string.video_load_failed));
                        finish();
                        return;
                    }
                    loadSecureEmbed(embedUrl);
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    hideVideoLoader();
                    UiUtils.toast(VideoActivity.this, getString(R.string.video_load_failed));
                    finish();
                });
            }
        });
    }

    private void loadSecureEmbed(String embedUrl) {
        String embed = appendQueryParams(embedUrl, "title=0&byline=0&portrait=0&sidedock=0&dnt=1&transparent=0");
        String html = "<!DOCTYPE html><html><head>"
                + "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1\">"
                + "<style>html,body{margin:0;padding:0;height:100%;background:#000;overflow:hidden;"
                + "-webkit-user-select:none;user-select:none;-webkit-touch-callout:none;}"
                + "iframe{position:fixed;top:0;left:0;width:100%;height:100%;border:0}</style></head><body>"
                + "<iframe src=\"" + escapeHtml(embed) + "\" allow=\"autoplay; fullscreen; picture-in-picture\" "
                + "allowfullscreen webkitallowfullscreen mozallowfullscreen referrerpolicy=\"no-referrer-when-downgrade\" "
                + "sandbox=\"allow-scripts allow-same-origin allow-presentation allow-fullscreen\"></iframe>"
                + "</body></html>";
        webView.loadDataWithBaseURL("https://player.vimeo.com", html, "text/html", "UTF-8", null);
    }

    private class FullscreenChromeClient extends WebChromeClient {
        @Override
        public void onShowCustomView(View view, CustomViewCallback callback) {
            if (customView != null) {
                callback.onCustomViewHidden();
                return;
            }
            customView = view;
            customViewCallback = callback;
            fullscreenContainer.addView(view, new FrameLayout.LayoutParams(
                    FrameLayout.LayoutParams.MATCH_PARENT,
                    FrameLayout.LayoutParams.MATCH_PARENT));
            fullscreenContainer.setVisibility(View.VISIBLE);
            contentContainer.setVisibility(View.GONE);
            getWindow().addFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN);
            getWindow().getDecorView().setSystemUiVisibility(
                    View.SYSTEM_UI_FLAG_FULLSCREEN
                            | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                            | View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                            | View.SYSTEM_UI_FLAG_LAYOUT_STABLE
                            | View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                            | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN);
        }

        @Override
        public void onHideCustomView() {
            if (customView == null) {
                return;
            }
            fullscreenContainer.removeView(customView);
            fullscreenContainer.setVisibility(View.GONE);
            customView = null;
            if (customViewCallback != null) {
                customViewCallback.onCustomViewHidden();
                customViewCallback = null;
            }
            contentContainer.setVisibility(View.VISIBLE);
            getWindow().clearFlags(WindowManager.LayoutParams.FLAG_FULLSCREEN);
            getWindow().getDecorView().setSystemUiVisibility(View.SYSTEM_UI_FLAG_VISIBLE);
        }
    }

    @SuppressLint("ClickableViewAccessibility")
    private void hardenWebView(WebView view) {
        view.setLongClickable(false);
        view.setHapticFeedbackEnabled(false);
        view.setOnLongClickListener(v -> true);
        view.setOnCreateContextMenuListener((menu, v, info) -> menu.clear());
    }

    private class SecureWebViewClient extends WebViewClient {
        @Override
        public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
            showVideoLoader();
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            hideVideoLoader();
        }

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
            String url = request.getUrl().toString().toLowerCase();
            return !url.contains("player.vimeo.com");
        }

        @Override
        @SuppressWarnings("deprecation")
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            return !url.toLowerCase().contains("player.vimeo.com");
        }
    }

    private static String appendQueryParams(String url, String params) {
        return url + (url.contains("?") ? "&" : "?") + params;
    }

    private static String escapeHtml(String value) {
        return value.replace("&", "&amp;")
                .replace("\"", "&quot;")
                .replace("<", "&lt;")
                .replace(">", "&gt;");
    }

    @Override
    protected void onDestroy() {
        slowLoadHandler.removeCallbacks(slowLoadHint);
        super.onDestroy();
    }

    @Override
    public void onBackPressed() {
        if (customView != null) {
            WebChromeClient client = webView.getWebChromeClient();
            if (client != null) {
                client.onHideCustomView();
            }
            return;
        }
        finish();
    }
}
