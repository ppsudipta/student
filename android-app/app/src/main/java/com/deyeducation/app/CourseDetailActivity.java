package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.text.Html;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
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
        toolbar.setTitle(fallbackTitle == null ? getString(R.string.course_details) : fallbackTitle);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        ImageView image = findViewById(R.id.courseImage);
        TextView title = findViewById(R.id.courseTitle);
        TextView meta = findViewById(R.id.courseMeta);
        TextView description = findViewById(R.id.courseDescription);
        ProgressBar progress = findViewById(R.id.courseProgress);

        progress.setVisibility(View.VISIBLE);
        new ApiClient(new SessionManager(this)).get("/events/" + id, false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        return;
                    }
                    String name = data.optString("name");
                    title.setText(name);
                    toolbar.setTitle(name);
                    String price = data.optString("price", "0");
                    String date = data.optString("date", "");
                    meta.setText(getString(R.string.course_price, price)
                            + (date.isEmpty() ? "" : "\n" + getString(R.string.course_date, date)));
                    description.setText(Html.fromHtml(
                            data.optString("description", ""), Html.FROM_HTML_MODE_COMPACT));
                    UiUtils.loadImage(CourseDetailActivity.this, data.optString("image_url"), image, 0);
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
                    UiUtils.toast(CourseDetailActivity.this, message);
                });
            }
        });
    }
}
