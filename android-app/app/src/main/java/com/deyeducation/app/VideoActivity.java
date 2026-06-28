package com.deyeducation.app;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.view.WindowManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.ProgressBar;

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

    private WebView webView;
    private ProgressBar progressBar;
    private SessionManager session;
    private ApiClient api;

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
        toolbar.setTitle(title == null ? getString(R.string.play_video) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        progressBar = findViewById(R.id.videoProgress);
        webView = findViewById(R.id.webView);
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
        webView.setWebChromeClient(new WebChromeClient());

        if (materialId <= 0) {
            UiUtils.toast(this, getString(R.string.video_load_failed));
            finish();
            return;
        }
        fetchAndPlay(materialId);
    }

    private void fetchAndPlay(int materialId) {
        progressBar.setVisibility(ProgressBar.VISIBLE);
        api.get("/materials/" + materialId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    progressBar.setVisibility(ProgressBar.GONE);
                    JSONObject data = json.optJSONObject("data");
                    JSONObject playback = data != null ? data.optJSONObject("playback") : null;
                    String embedUrl = playback != null ? playback.optString("embed_url") : "";
                    if (embedUrl.isEmpty()) {
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
                    progressBar.setVisibility(ProgressBar.GONE);
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
                + "allowfullscreen referrerpolicy=\"no-referrer-when-downgrade\" "
                + "sandbox=\"allow-scripts allow-same-origin allow-presentation\"></iframe>"
                + "</body></html>";
        webView.loadDataWithBaseURL("https://player.vimeo.com", html, "text/html", "UTF-8", null);
    }

    @SuppressLint("ClickableViewAccessibility")
    private void hardenWebView(WebView view) {
        view.setLongClickable(false);
        view.setHapticFeedbackEnabled(false);
        view.setOnLongClickListener(v -> true);
        view.setOnCreateContextMenuListener((menu, v, info) -> menu.clear());
    }

    private static class SecureWebViewClient extends WebViewClient {
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
    public void onBackPressed() {
        finish();
    }
}
