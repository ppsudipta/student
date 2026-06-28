package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

import org.json.JSONObject;

public class DetailActivity extends AppCompatActivity {
    public static final String EXTRA_TITLE = "title";
    public static final String EXTRA_PATH = "path";

    public static void open(Context context, String title, String path) {
        Intent intent = new Intent(context, DetailActivity.class);
        intent.putExtra(EXTRA_TITLE, title);
        intent.putExtra(EXTRA_PATH, path);
        context.startActivity(intent);
    }

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_detail);

        String title = getIntent().getStringExtra(EXTRA_TITLE);
        String path = getIntent().getStringExtra(EXTRA_PATH);

        MaterialToolbar toolbar = findViewById(R.id.detailToolbar);
        toolbar.setTitle(title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        LinearLayout container = findViewById(R.id.detailContainer);
        SessionManager session = new SessionManager(this);
        ApiClient api = new ApiClient(session);

        api.get(path, path.contains("company") ? false : true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> bindJson(container, json));
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> UiUtils.toast(DetailActivity.this, message));
            }
        });
    }

    private void bindJson(LinearLayout container, JSONObject json) {
        container.removeAllViews();
        JSONObject data = json.optJSONObject("data");
        if (data == null && json.has("name")) {
            data = json;
        }
        if (data == null) {
            addRow(container, "Info", json.toString());
            return;
        }
        addRow(container, "Name", data.optString("name"));
        addRow(container, "Phone", first(data, "ph1", "phone", "mobile"));
        addRow(container, "Email", data.optString("email"));
        addRow(container, "Address", data.optString("address"));
        addRow(container, "About", UrlHelper.cleanHtml(data.optString("about", data.optString("description"))));
    }

    private String first(JSONObject obj, String... keys) {
        for (String key : keys) {
            String value = obj.optString(key);
            if (!value.isEmpty() && !"null".equals(value)) {
                return value;
            }
        }
        return "";
    }

    private void addRow(LinearLayout container, String label, String value) {
        if (value == null || value.isEmpty() || "null".equals(value)) {
            return;
        }
        TextView labelView = new TextView(this);
        labelView.setText(label);
        labelView.setTextColor(getColor(R.color.secondary_text));
        labelView.setTextSize(12f);
        labelView.setPadding(0, UiUtils.dp(this, 10), 0, UiUtils.dp(this, 2));

        TextView valueView = new TextView(this);
        valueView.setText(value);
        valueView.setTextColor(getColor(R.color.primary_text));
        valueView.setTextSize(15f);
        valueView.setLayoutParams(new LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT));

        container.addView(labelView);
        container.addView(valueView);
    }
}
