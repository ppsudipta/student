package com.deyeducation.app;

import android.os.Bundle;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.WindowManager;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.github.barteksc.pdfviewer.PDFView;
import com.github.barteksc.pdfviewer.util.FitPolicy;
import com.google.android.material.appbar.MaterialToolbar;

import java.io.BufferedInputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.atomic.AtomicBoolean;

public class PdfActivity extends AppCompatActivity {
    public static final String EXTRA_URL = "url";
    public static final String EXTRA_TITLE = "title";
    public static final String EXTRA_CAN_DOWNLOAD = "can_download";

    private static final ExecutorService IO = Executors.newSingleThreadExecutor();

    private MaterialToolbar toolbar;
    private PDFView pdfView;
    private View progressBar;
    private TextView errorView;

    private final AtomicBoolean destroyed = new AtomicBoolean(false);
    private File cachedFile;
    private boolean canDownload;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        String title = getIntent().getStringExtra(EXTRA_TITLE);
        String url = getIntent().getStringExtra(EXTRA_URL);
        canDownload = getIntent().getBooleanExtra(EXTRA_CAN_DOWNLOAD, false);

        if (!canDownload) {
            getWindow().setFlags(
                    WindowManager.LayoutParams.FLAG_SECURE,
                    WindowManager.LayoutParams.FLAG_SECURE);
        }

        setContentView(R.layout.activity_pdf);

        toolbar = findViewById(R.id.pdfToolbar);
        UiUtils.setupViewerWindow(this, toolbar);
        toolbar.setTitle(title == null || title.isEmpty() ? getString(R.string.read_material) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);
        toolbar.inflateMenu(R.menu.menu_material_viewer);
        toolbar.setOnMenuItemClickListener(this::onToolbarMenuClick);
        updateDownloadMenu(toolbar.getMenu());

        pdfView = findViewById(R.id.pdfView);
        progressBar = findViewById(R.id.pdfProgress);
        errorView = findViewById(R.id.pdfError);

        if (url == null || url.isEmpty()) {
            showError(getString(R.string.pdf_open_failed));
            return;
        }
        loadPdf(url);
    }

    private boolean onToolbarMenuClick(MenuItem item) {
        if (item.getItemId() == R.id.action_download) {
            downloadMaterial();
            return true;
        }
        return false;
    }

    private void updateDownloadMenu(Menu menu) {
        MenuItem download = menu.findItem(R.id.action_download);
        if (download != null) {
            download.setVisible(canDownload);
        }
    }

    private void downloadMaterial() {
        if (cachedFile == null || !cachedFile.exists()) {
            UiUtils.toast(this, getString(R.string.download_failed));
            return;
        }
        String title = getIntent().getStringExtra(EXTRA_TITLE);
        IO.execute(() -> {
            boolean ok = MaterialDownloadHelper.saveToDownloads(
                    this, cachedFile, title == null ? "material" : title, "application/pdf");
            runOnUiThread(() -> UiUtils.toast(
                    PdfActivity.this,
                    ok ? getString(R.string.download_started) : getString(R.string.download_failed)));
        });
    }

    private void loadPdf(String url) {
        UiUtils.setLoaderVisible(progressBar, true);
        errorView.setVisibility(View.GONE);
        pdfView.setVisibility(View.GONE);

        SessionManager session = new SessionManager(this);
        IO.execute(() -> {
            try {
                File file = downloadToCache(session, url);
                cachedFile = file;
                if (destroyed.get()) {
                    return;
                }
                runOnUiThread(() -> displayPdf(file));
            } catch (Exception e) {
                if (!destroyed.get()) {
                    runOnUiThread(() -> showError(getString(R.string.pdf_open_failed)));
                }
            }
        });
    }

    private void displayPdf(File file) {
        UiUtils.setLoaderVisible(progressBar, false);
        pdfView.setVisibility(View.VISIBLE);
        pdfView.setBackgroundColor(0xFFFFFFFF);
        pdfView.setLayerType(View.LAYER_TYPE_HARDWARE, null);
        pdfView.useBestQuality(true);
        pdfView.post(() -> {
            if (destroyed.get()) {
                return;
            }
            try {
                pdfView.fromFile(file)
                        .defaultPage(0)
                        .enableSwipe(true)
                        .swipeHorizontal(false)
                        .enableDoubletap(true)
                        .enableAntialiasing(false)
                        .autoSpacing(false)
                        .fitEachPage(true)
                        .pageFitPolicy(FitPolicy.WIDTH)
                        .spacing(8)
                        .onLoad(pageCount -> {
                            String subtitle = canDownload
                                    ? getString(R.string.pdf_scroll_hint, pageCount)
                                    : getString(R.string.material_view_only);
                            toolbar.setSubtitle(subtitle);
                            pdfView.fitToWidth(0);
                        })
                        .onError(t -> showError(getString(R.string.pdf_open_failed)))
                        .load();
            } catch (Throwable t) {
                showError(getString(R.string.pdf_open_failed));
            }
        });
    }

    private File downloadToCache(SessionManager session, String urlString) throws Exception {
        File dir = new File(getCacheDir(), "secure_pdfs");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IllegalStateException("Cache unavailable");
        }
        File target = new File(dir, hash(urlString) + ".pdf");
        if (target.exists() && target.length() > 0 && looksLikePdf(target)) {
            return target;
        }
        if (target.exists()) {
            target.delete();
        }

        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(urlString).openConnection();
            conn.setConnectTimeout(20000);
            conn.setReadTimeout(120000);
            conn.setRequestProperty("Accept", "application/pdf,*/*");
            String token = session.getToken();
            if (token != null && !token.isEmpty()) {
                conn.setRequestProperty("Authorization", "Bearer " + token);
            }
            int code = conn.getResponseCode();
            if (code >= 400) {
                throw new IllegalStateException("HTTP " + code);
            }
            try (InputStream in = new BufferedInputStream(conn.getInputStream());
                 FileOutputStream out = new FileOutputStream(target)) {
                byte[] buffer = new byte[16384];
                int read;
                while ((read = in.read(buffer)) != -1) {
                    out.write(buffer, 0, read);
                }
            }
            if (!looksLikePdf(target)) {
                target.delete();
                throw new IllegalStateException("Not a PDF file");
            }
        } finally {
            if (conn != null) {
                conn.disconnect();
            }
        }
        return target;
    }

    private void showError(String message) {
        UiUtils.setLoaderVisible(progressBar, false);
        pdfView.setVisibility(View.GONE);
        errorView.setText(message);
        errorView.setVisibility(View.VISIBLE);
    }

    private static boolean looksLikePdf(File file) throws Exception {
        if (file.length() < 5) {
            return false;
        }
        byte[] header = new byte[5];
        try (java.io.FileInputStream in = new java.io.FileInputStream(file)) {
            if (in.read(header) != 5) {
                return false;
            }
        }
        return header[0] == '%' && header[1] == 'P' && header[2] == 'D' && header[3] == 'F';
    }

    private static String hash(String value) throws Exception {
        MessageDigest digest = MessageDigest.getInstance("SHA-256");
        byte[] bytes = digest.digest(value.getBytes(StandardCharsets.UTF_8));
        StringBuilder sb = new StringBuilder();
        for (byte b : bytes) {
            sb.append(String.format("%02x", b));
        }
        return sb.toString();
    }

    @Override
    protected void onDestroy() {
        destroyed.set(true);
        if (pdfView != null) {
            pdfView.recycle();
        }
        super.onDestroy();
    }
}
