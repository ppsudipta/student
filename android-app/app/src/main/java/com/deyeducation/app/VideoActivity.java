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
        String lower = url.toLowerCase();
        if (lower.contains("player.vimeo.com") || lower.contains("youtube.com/embed")) {
            String embed = url;
            if (lower.contains("vimeo")) {
                embed = appendQueryParams(url, "title=0&byline=0&portrait=0&sidedock=0&dnt=1");
            } else if (lower.contains("youtube.com/embed")) {
                embed = appendQueryParams(url, "rel=0&modestbranding=1");
            }
            String html = "<!DOCTYPE html><html><head>"
                    + "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1\">"
                    + "<style>html,body{margin:0;padding:0;height:100%;background:#000;overflow:hidden}"
                    + "iframe{position:fixed;top:0;left:0;width:100%;height:100%;border:0}</style></head><body>"
                    + "<iframe src=\"" + escapeHtml(embed) + "\" allow=\"autoplay; fullscreen; picture-in-picture\" "
                    + "allowfullscreen referrerpolicy=\"no-referrer-when-downgrade\"></iframe>"
                    + "</body></html>";
            webView.loadDataWithBaseURL(
                    lower.contains("vimeo") ? "https://player.vimeo.com" : "https://www.youtube.com",
                    html,
                    "text/html",
                    "UTF-8",
                    null);
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
