package com.deyeducation.app;

import android.annotation.SuppressLint;
import android.os.Bundle;
import android.view.WindowManager;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

public class VideoActivity extends AppCompatActivity {
    public static final String EXTRA_URL = "url";
    public static final String EXTRA_TITLE = "title";

    private static final String USER_AGENT =
            "Mozilla/5.0 (Linux; Android 10; Mobile) AppleWebKit/537.36 "
                    + "(KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36";

    private WebView webView;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getWindow().setFlags(
                WindowManager.LayoutParams.FLAG_SECURE,
                WindowManager.LayoutParams.FLAG_SECURE);
        setContentView(R.layout.activity_video);

        String url = getIntent().getStringExtra(EXTRA_URL);
        String title = getIntent().getStringExtra(EXTRA_TITLE);

        MaterialToolbar toolbar = findViewById(R.id.videoToolbar);
        toolbar.setTitle(title == null ? getString(R.string.play_video) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        webView = findViewById(R.id.webView);
        WebSettings settings = webView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setUserAgentString(USER_AGENT);
        webView.setLongClickable(false);
        webView.setHapticFeedbackEnabled(false);
        webView.setWebViewClient(new WebViewClient());
        webView.setWebChromeClient(new WebChromeClient());
        loadSecureVideo(url);
    }

    private void loadSecureVideo(String url) {
        if (url == null || url.isEmpty()) {
            finish();
            return;
        }

        String normalized = UrlHelper.videoEmbedUrl(url);
        if (!normalized.isEmpty()) {
            url = normalized;
        }

        String lower = url.toLowerCase();
        if (lower.contains("youtube.com") || lower.contains("youtu.be")) {
            url = toNoCookieYouTube(url);
            webView.loadUrl(appendQueryParams(url, "playsinline=1&rel=0&modestbranding=1&fs=1"));
            return;
        }
        if (lower.contains("player.vimeo.com") || lower.contains("vimeo.com")) {
            webView.loadUrl(appendQueryParams(url, "title=0&byline=0&portrait=0&sidedock=0&dnt=1"));
            return;
        }
        if (lower.contains(".mp4")) {
            String html = "<!DOCTYPE html><html><head>"
                    + "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
                    + "<style>html,body{margin:0;background:#000;height:100%}"
                    + "video{width:100%;height:100%;object-fit:contain;background:#000}</style></head><body>"
                    + "<video controls controlsList=\"nodownload noplaybackrate\" playsinline "
                    + "src=\"" + escapeHtml(url) + "\"></video></body></html>";
            webView.loadDataWithBaseURL(null, html, "text/html", "UTF-8", null);
            return;
        }
        webView.loadUrl(url);
    }

    private static String toNoCookieYouTube(String url) {
        return url
                .replace("https://www.youtube.com/embed/", "https://www.youtube-nocookie.com/embed/")
                .replace("http://www.youtube.com/embed/", "https://www.youtube-nocookie.com/embed/")
                .replace("https://youtube.com/embed/", "https://www.youtube-nocookie.com/embed/");
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
        if (webView != null && webView.canGoBack()) {
            webView.goBack();
            return;
        }
        super.onBackPressed();
    }
}
