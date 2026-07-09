package com.deyeducation.app;

import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.VideoView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

import com.bumptech.glide.Glide;
import com.bumptech.glide.load.DataSource;
import com.bumptech.glide.load.engine.GlideException;
import com.bumptech.glide.request.RequestListener;
import com.bumptech.glide.request.target.Target;

public class NoticeDetailActivity extends AppCompatActivity {
    public static final String EXTRA_ID = "notice_id";
    public static final String EXTRA_TYPE = "notice_type";
    public static final String EXTRA_CONTENT = "notice_content";
    public static final String EXTRA_MEDIA_URL = "media_url";
    public static final String EXTRA_DATE = "notice_date";
    public static final String EXTRA_SEEN = "seen";

    public static final String RESULT_MARKED_READ = "marked_read";

    private ApiClient api;
    private int noticeId;
    private boolean seen;
    private VideoView videoView;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_notice_detail);

        noticeId = getIntent().getIntExtra(EXTRA_ID, 0);
        String type = getIntent().getStringExtra(EXTRA_TYPE);
        String content = getIntent().getStringExtra(EXTRA_CONTENT);
        String mediaUrl = getIntent().getStringExtra(EXTRA_MEDIA_URL);
        String date = getIntent().getStringExtra(EXTRA_DATE);
        seen = getIntent().getBooleanExtra(EXTRA_SEEN, false);

        MaterialToolbar toolbar = findViewById(R.id.noticeToolbar);
        UiUtils.setupViewerWindow(this, toolbar);
        toolbar.setTitle(R.string.notice);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        TextView dateView = findViewById(R.id.noticeDate);
        TextView typeLabel = findViewById(R.id.noticeTypeLabel);
        TextView textView = findViewById(R.id.noticeText);
        ImageView imageView = findViewById(R.id.noticeImage);
        videoView = findViewById(R.id.noticeVideo);
        View progressBar = findViewById(R.id.noticeProgress);

        dateView.setText(date == null || date.isEmpty() ? "" : date);
        api = new ApiClient(new SessionManager(this));

        String noticeType = type == null ? "text" : type.toLowerCase();
        switch (noticeType) {
            case "image":
                typeLabel.setText(R.string.notice_type_image);
                imageView.setVisibility(View.VISIBLE);
                UiUtils.setLoaderVisible(progressBar, true);
                Glide.with(this)
                        .load(mediaUrl)
                        .placeholder(R.drawable.bg_image_placeholder)
                        .error(R.drawable.bg_image_placeholder)
                        .listener(new RequestListener<android.graphics.drawable.Drawable>() {
                            @Override
                            public boolean onLoadFailed(@Nullable GlideException e, Object model,
                                                        Target<android.graphics.drawable.Drawable> target,
                                                        boolean isFirstResource) {
                                UiUtils.setLoaderVisible(progressBar, false);
                                return false;
                            }

                            @Override
                            public boolean onResourceReady(android.graphics.drawable.Drawable resource,
                                                           Object model,
                                                           Target<android.graphics.drawable.Drawable> target,
                                                           DataSource dataSource,
                                                           boolean isFirstResource) {
                                UiUtils.setLoaderVisible(progressBar, false);
                                return false;
                            }
                        })
                        .into(imageView);
                break;
            case "video":
                typeLabel.setText(R.string.notice_type_video);
                if (mediaUrl != null && !mediaUrl.isEmpty()) {
                    if (progressBar instanceof FunLoaderView funLoader) {
                        funLoader.setKind(FunLoaderView.Kind.VIDEO);
                    }
                    videoView.setVisibility(View.VISIBLE);
                    UiUtils.setLoaderVisible(progressBar, true);
                    videoView.setVideoURI(Uri.parse(mediaUrl));
                    videoView.setOnPreparedListener(mp -> {
                        UiUtils.setLoaderVisible(progressBar, false);
                        mp.setLooping(false);
                    });
                    videoView.setOnErrorListener((mp, what, extra) -> {
                        UiUtils.setLoaderVisible(progressBar, false);
                        UiUtils.toast(this, getString(R.string.video_load_failed));
                        return true;
                    });
                    videoView.start();
                } else {
                    textView.setVisibility(View.VISIBLE);
                    textView.setText(R.string.video_load_failed);
                }
                break;
            default:
                typeLabel.setText(R.string.notice_type_text);
                textView.setVisibility(View.VISIBLE);
                textView.setText(content == null ? "" : content);
                break;
        }

        if (!seen && noticeId > 0) {
            markAsRead();
        }
    }

    private void markAsRead() {
        api.patch("/notices/" + noticeId + "/seen", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(org.json.JSONObject json) {
                if (json.optBoolean("updated", false)) {
                    seen = true;
                    setResult(RESULT_OK);
                }
            }

            @Override
            public void onError(String message) {
            }
        });
    }

    @Override
    protected void onPause() {
        if (videoView != null && videoView.isPlaying()) {
            videoView.pause();
        }
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        if (videoView != null) {
            videoView.stopPlayback();
        }
        super.onDestroy();
    }
}
