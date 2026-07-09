package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;

import org.json.JSONArray;
import org.json.JSONObject;

public class LegalPoliciesActivity extends AppCompatActivity {
    public static void open(Context context) {
        context.startActivity(new Intent(context, LegalPoliciesActivity.class));
    }

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_legal_policies);

        MaterialToolbar toolbar = findViewById(R.id.legalToolbar);
        toolbar.setTitle(R.string.legal_terms_title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        LinearLayout container = findViewById(R.id.legalContainer);
        View progress = findViewById(R.id.legalProgress);
        UiUtils.setLoaderVisible(progress, true);

        new ApiClient(new SessionManager(this)).get("/legal-policies", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    bindSections(container, json.optJSONObject("data"));
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    UiUtils.toast(LegalPoliciesActivity.this, message);
                });
            }
        });
    }

    private void bindSections(LinearLayout container, JSONObject data) {
        container.removeAllViews();
        if (data == null) {
            return;
        }
        JSONArray sections = data.optJSONArray("sections");
        if (sections == null) {
            return;
        }
        LayoutInflater inflater = LayoutInflater.from(this);
        for (int i = 0; i < sections.length(); i++) {
            JSONObject section = sections.optJSONObject(i);
            if (section == null) {
                continue;
            }
            TextView title = new TextView(this);
            title.setText(section.optString("title"));
            title.setTextColor(getColor(R.color.primary_text));
            title.setTextSize(17f);
            title.setTypeface(title.getTypeface(), android.graphics.Typeface.BOLD);
            LinearLayout.LayoutParams titleLp = new LinearLayout.LayoutParams(
                    ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
            titleLp.bottomMargin = UiUtils.dp(this, 8);
            titleLp.topMargin = UiUtils.dp(this, i == 0 ? 0 : 16);
            title.setLayoutParams(titleLp);
            container.addView(title);

            JSONArray items = section.optJSONArray("items");
            if (items == null) {
                continue;
            }
            for (int j = 0; j < items.length(); j++) {
                View row = inflater.inflate(android.R.layout.simple_list_item_1, container, false);
                TextView text = row.findViewById(android.R.id.text1);
                text.setText("• " + items.optString(j));
                text.setTextColor(getColor(R.color.primary_text));
                text.setTextSize(14f);
                text.setPadding(UiUtils.dp(this, 8), UiUtils.dp(this, 6), 0, UiUtils.dp(this, 6));
                container.addView(row);
            }
        }
    }
}
