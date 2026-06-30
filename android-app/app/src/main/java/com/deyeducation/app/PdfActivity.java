package com.deyeducation.app;

import android.graphics.Bitmap;
import android.graphics.pdf.PdfRenderer;
import android.os.Bundle;
import android.os.ParcelFileDescriptor;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.view.WindowManager;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.widget.NestedScrollView;

import com.github.chrisbanes.photoview.PhotoView;
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

    private ProgressBar progressBar;
    private TextView errorView;
    private NestedScrollView scrollView;
    private LinearLayout pagesContainer;
    private MaterialToolbar toolbar;
    private ParcelFileDescriptor fileDescriptor;
    private PdfRenderer pdfRenderer;
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
        toolbar.setTitle(title == null || title.isEmpty() ? getString(R.string.read_material) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);
        if (!canDownload) {
            toolbar.setSubtitle(getString(R.string.material_view_only));
        }
        toolbar.inflateMenu(R.menu.menu_material_viewer);
        toolbar.setOnMenuItemClickListener(this::onToolbarMenuClick);
        updateDownloadMenu(toolbar.getMenu());

        progressBar = findViewById(R.id.pdfProgress);
        errorView = findViewById(R.id.pdfError);
        scrollView = findViewById(R.id.pdfScroll);
        pagesContainer = findViewById(R.id.pdfPagesContainer);

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
        progressBar.setVisibility(View.VISIBLE);
        errorView.setVisibility(View.GONE);
        scrollView.setVisibility(View.GONE);
        pagesContainer.removeAllViews();

        SessionManager session = new SessionManager(this);
        IO.execute(() -> {
            try {
                File file = downloadToCache(session, url);
                cachedFile = file;
                if (destroyed.get()) {
                    return;
                }
                runOnUiThread(() -> openRenderer(file));
            } catch (Exception e) {
                if (!destroyed.get()) {
                    runOnUiThread(() -> showError(getString(R.string.pdf_open_failed)));
                }
            }
        });
    }

    private File downloadToCache(SessionManager session, String urlString) throws Exception {
        File dir = new File(getCacheDir(), "secure_pdfs");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IllegalStateException("Cache unavailable");
        }
        File target = new File(dir, hash(urlString) + ".pdf");
        if (target.exists() && target.length() > 0) {
            if (looksLikePdf(target)) {
                return target;
            }
            target.delete();
        }

        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(urlString).openConnection();
            conn.setConnectTimeout(20000);
            conn.setReadTimeout(60000);
            conn.setRequestProperty("Accept", "application/pdf");
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
                byte[] buffer = new byte[8192];
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

    private void openRenderer(File file) {
        try {
            fileDescriptor = ParcelFileDescriptor.open(file, ParcelFileDescriptor.MODE_READ_ONLY);
            pdfRenderer = new PdfRenderer(fileDescriptor);
            int pageCount = pdfRenderer.getPageCount();
            if (pageCount == 0) {
                showError(getString(R.string.pdf_open_failed));
                return;
            }
            int pageWidth = getResources().getDisplayMetrics().widthPixels - UiUtils.dp(this, 16);
            scrollView.setVisibility(View.VISIBLE);
            progressBar.setVisibility(View.GONE);
            toolbar.setSubtitle(canDownload
                    ? getString(R.string.pdf_scroll_hint, pageCount)
                    : getString(R.string.material_view_only));

            IO.execute(() -> renderPages(pageWidth, pageCount));
        } catch (Exception e) {
            showError(getString(R.string.pdf_open_failed));
        }
    }

    private void renderPages(int pageWidth, int pageCount) {
        for (int i = 0; i < pageCount; i++) {
            if (destroyed.get()) {
                return;
            }
            Bitmap bitmap = renderPageBitmap(i, pageWidth);
            if (bitmap == null || destroyed.get()) {
                if (bitmap != null) {
                    bitmap.recycle();
                }
                return;
            }
            final int pageIndex = i;
            runOnUiThread(() -> addPageView(bitmap, pageIndex + 1, pageCount));
        }
    }

    private Bitmap renderPageBitmap(int index, int pageWidth) {
        synchronized (this) {
            if (pdfRenderer == null) {
                return null;
            }
            PdfRenderer.Page page = pdfRenderer.openPage(index);
            int height = (int) (page.getHeight() * ((float) pageWidth / page.getWidth()));
            Bitmap bitmap = Bitmap.createBitmap(pageWidth, height, Bitmap.Config.ARGB_8888);
            page.render(bitmap, null, null, PdfRenderer.Page.RENDER_MODE_FOR_DISPLAY);
            page.close();
            return bitmap;
        }
    }

    private void addPageView(Bitmap bitmap, int pageNumber, int pageCount) {
        if (destroyed.get()) {
            bitmap.recycle();
            return;
        }
        PhotoView photoView = new PhotoView(this);
        photoView.setAdjustViewBounds(true);
        photoView.setScaleType(android.widget.ImageView.ScaleType.FIT_CENTER);
        photoView.setLongClickable(false);
        photoView.setImageBitmap(bitmap);
        photoView.setContentDescription(getString(R.string.pdf_page_label, pageNumber, pageCount));
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT);
        lp.bottomMargin = UiUtils.dp(this, 8);
        pagesContainer.addView(photoView, lp);
    }

    private void showError(String message) {
        progressBar.setVisibility(View.GONE);
        scrollView.setVisibility(View.GONE);
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
        closeRenderer();
        pagesContainer.removeAllViews();
        super.onDestroy();
    }

    private void closeRenderer() {
        synchronized (this) {
            if (pdfRenderer != null) {
                try {
                    pdfRenderer.close();
                } catch (Exception ignored) {
                }
                pdfRenderer = null;
            }
            if (fileDescriptor != null) {
                try {
                    fileDescriptor.close();
                } catch (Exception ignored) {
                }
                fileDescriptor = null;
            }
        }
    }
}
