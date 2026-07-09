package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

import org.json.JSONObject;

public class CourseDetailActivity extends AppCompatActivity {
    public static final String EXTRA_ID = "course_id";
    public static final String EXTRA_TITLE = "course_title";

    public static void open(Context context, int id, String title) {
        Intent intent = new Intent(context, CourseDetailActivity.class);
        intent.putExtra(EXTRA_ID, id);
        intent.putExtra(EXTRA_TITLE, title);
        context.startActivity(intent);
    }

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_course_detail);

        int id = getIntent().getIntExtra(EXTRA_ID, 0);
        String fallbackTitle = getIntent().getStringExtra(EXTRA_TITLE);

        MaterialToolbar toolbar = findViewById(R.id.courseToolbar);
        String initialTitle = fallbackTitle == null ? getString(R.string.course_details) : fallbackTitle;
        toolbar.setTitle(initialTitle);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        ImageView image = findViewById(R.id.courseImage);
        TextView title = findViewById(R.id.courseTitle);
        TextView meta = findViewById(R.id.courseMeta);
        TextView description = findViewById(R.id.courseDescription);
        View progress = findViewById(R.id.courseProgress);

        title.setText(initialTitle);

        if (id <= 0) {
            UiUtils.toast(this, getString(R.string.no_records));
            finish();
            return;
        }

        UiUtils.setLoaderVisible(progress, true);
        new ApiClient(new SessionManager(this)).get("/events/" + id, false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    if (!UiUtils.isContextValid(CourseDetailActivity.this)) {
                        return;
                    }
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        UiUtils.toast(CourseDetailActivity.this, getString(R.string.no_records));
                        return;
                    }
                    String name = data.optString("name", initialTitle);
                    title.setText(name);
                    toolbar.setTitle(name);

                    String price = data.optString("price", "0");
                    String date = data.optString("date", "");
                    StringBuilder metaText = new StringBuilder("Price: ₹").append(price);
                    if (!date.isEmpty() && !"null".equals(date)) {
                        metaText.append("\nStarts: ").append(date);
                    }
                    meta.setText(metaText.toString());

                    UiUtils.bindHtml(description, data.optString("description", ""));
                    UiUtils.loadImage(CourseDetailActivity.this,
                            UrlHelper.imageFromJson(
                                    new SessionManager(CourseDetailActivity.this).getBaseUrl(), data),
                            image, 0);
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    if (UiUtils.isContextValid(CourseDetailActivity.this)) {
                        UiUtils.toast(CourseDetailActivity.this, message);
                    }
                });
            }
        });
    }
}
