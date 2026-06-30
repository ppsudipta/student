package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.MenuItem;
import android.view.WindowManager;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.DataSource;
import com.bumptech.glide.load.engine.GlideException;
import com.bumptech.glide.request.RequestListener;
import com.bumptech.glide.request.target.Target;
import com.github.chrisbanes.photoview.PhotoView;
import com.google.android.material.appbar.MaterialToolbar;

import java.io.File;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ImageViewerActivity extends AppCompatActivity {
    public static final String EXTRA_URL = "url";
    public static final String EXTRA_TITLE = "title";
    public static final String EXTRA_CAN_DOWNLOAD = "can_download";

    private static final ExecutorService IO = Executors.newSingleThreadExecutor();

    private File cachedFile;
    private boolean canDownload;
    private String imageUrl;

    public static void open(Context context, String title, String imageUrl) {
        open(context, title, imageUrl, false);
    }

    public static void open(Context context, String title, String imageUrl, boolean canDownload) {
        Intent intent = new Intent(context, ImageViewerActivity.class);
        intent.putExtra(EXTRA_TITLE, title);
        intent.putExtra(EXTRA_URL, imageUrl);
        intent.putExtra(EXTRA_CAN_DOWNLOAD, canDownload);
        context.startActivity(intent);
    }

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        String title = getIntent().getStringExtra(EXTRA_TITLE);
        imageUrl = getIntent().getStringExtra(EXTRA_URL);
        canDownload = getIntent().getBooleanExtra(EXTRA_CAN_DOWNLOAD, false);

        if (!canDownload) {
            getWindow().setFlags(
                    WindowManager.LayoutParams.FLAG_SECURE,
                    WindowManager.LayoutParams.FLAG_SECURE);
        }

        setContentView(R.layout.activity_image_viewer);

        MaterialToolbar toolbar = findViewById(R.id.imageToolbar);
        toolbar.setTitle(title == null || title.isEmpty() ? getString(R.string.gallery) : title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);
        if (!canDownload) {
            toolbar.setSubtitle(getString(R.string.material_view_only));
        }
        toolbar.inflateMenu(R.menu.menu_material_viewer);
        toolbar.setOnMenuItemClickListener(this::onToolbarMenuClick);
        MenuItem download = toolbar.getMenu().findItem(R.id.action_download);
        if (download != null) {
            download.setVisible(canDownload);
        }

        PhotoView photoView = findViewById(R.id.photoView);
        UiUtils.loadZoomImage(this, imageUrl, photoView);
        cacheImageForDownload(imageUrl);
    }

    private boolean onToolbarMenuClick(MenuItem item) {
        if (item.getItemId() == R.id.action_download) {
            downloadImage();
            return true;
        }
        return false;
    }

    private void cacheImageForDownload(String url) {
        if (!canDownload || url == null || url.isEmpty()) {
            return;
        }
        Glide.with(this)
                .downloadOnly()
                .load(url)
                .listener(new RequestListener<File>() {
                    @Override
                    public boolean onLoadFailed(@Nullable GlideException e, Object model,
                                                Target<File> target, boolean isFirstResource) {
                        return false;
                    }

                    @Override
                    public boolean onResourceReady(File resource, Object model, Target<File> target,
                                                   DataSource dataSource, boolean isFirstResource) {
                        cachedFile = resource;
                        return false;
                    }
                })
                .submit();
    }

    private void downloadImage() {
        if (cachedFile == null || !cachedFile.exists()) {
            UiUtils.toast(this, getString(R.string.download_failed));
            return;
        }
        String title = getIntent().getStringExtra(EXTRA_TITLE);
        String mime = mimeFromUrl(imageUrl);
        IO.execute(() -> {
            boolean ok = MaterialDownloadHelper.saveToDownloads(
                    this, cachedFile, title == null ? "image" : title, mime);
            runOnUiThread(() -> UiUtils.toast(
                    ImageViewerActivity.this,
                    ok ? getString(R.string.download_started) : getString(R.string.download_failed)));
        });
    }

    private static String mimeFromUrl(String url) {
        if (url == null) {
            return "image/jpeg";
        }
        String lower = url.toLowerCase();
        if (lower.endsWith(".png")) {
            return "image/png";
        }
        if (lower.endsWith(".gif")) {
            return "image/gif";
        }
        if (lower.endsWith(".webp")) {
            return "image/webp";
        }
        return "image/jpeg";
    }
}
