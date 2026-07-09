package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;

import org.json.JSONObject;

public class AboutActivity extends AppCompatActivity {
    public static void open(Context context) {
        context.startActivity(new Intent(context, AboutActivity.class));
    }

    private String mapUrl;
    private ApiClient api;
    private View progress;
    private TextView titleView;
    private TextView descriptionView;
    private TextView phoneView;
    private TextView emailView;
    private TextView addressView;
    private ImageView heroView;
    private MaterialButton mapsButton;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_about);

        MaterialToolbar toolbar = findViewById(R.id.aboutToolbar);
        toolbar.setTitle(R.string.about_us_title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        heroView = findViewById(R.id.aboutHero);
        titleView = findViewById(R.id.aboutTitle);
        descriptionView = findViewById(R.id.aboutDescription);
        phoneView = findViewById(R.id.aboutPhone);
        emailView = findViewById(R.id.aboutEmail);
        addressView = findViewById(R.id.aboutAddress);
        mapsButton = findViewById(R.id.btnOpenMaps);
        progress = findViewById(R.id.aboutProgress);

        api = new ApiClient(new SessionManager(this));
        mapsButton.setOnClickListener(v -> {
            if (mapUrl != null && !mapUrl.isEmpty()) {
                startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(mapUrl)));
            }
        });

        loadAbout();
    }

    private void loadAbout() {
        UiUtils.setLoaderVisible(progress, true);
        api.get("/about", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    if (!UiUtils.isContextValid(AboutActivity.this)) {
                        return;
                    }
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data != null) {
                        bindAbout(data);
                    } else {
                        loadCompanyFallback();
                    }
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    if (!UiUtils.isContextValid(AboutActivity.this)) {
                        return;
                    }
                    loadCompanyFallback();
                });
            }
        });
    }

    private void loadCompanyFallback() {
        api.get("/company", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    if (!UiUtils.isContextValid(AboutActivity.this)) {
                        return;
                    }
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject company = json.optJSONObject("data");
                    if (company != null) {
                        bindCompany(company, null);
                    }
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    if (UiUtils.isContextValid(AboutActivity.this)) {
                        UiUtils.toast(AboutActivity.this, message);
                    }
                });
            }
        });
    }

    private void bindAbout(JSONObject data) {
        JSONObject company = data.optJSONObject("company");
        JSONObject about = data.optJSONObject("about");
        bindCompany(company, about);
    }

    private void bindCompany(JSONObject company, JSONObject about) {
        if (about != null) {
            titleView.setText(about.optString("title", getString(R.string.about_us_title)));
            UiUtils.bindHtml(descriptionView, about.optString("details", ""));
            SessionManager session = new SessionManager(this);
            UiUtils.loadImage(AboutActivity.this,
                    UrlHelper.imageFromJson(session.getBaseUrl(), about), heroView, 0);
        } else if (company != null) {
            titleView.setText(company.optString("name", getString(R.string.about_us_title)));
        }

        if (company == null) {
            mapsButton.setVisibility(View.GONE);
            return;
        }

        phoneView.setText(getString(R.string.phone_label) + ": " + safe(company.optString("ph1")));
        emailView.setText(getString(R.string.email_label) + ": " + safe(company.optString("email")));
        addressView.setText(getString(R.string.address_label) + ": " + safe(company.optString("address")));

        mapUrl = company.optString("map_url");
        if (mapUrl == null || mapUrl.isEmpty()) {
            String addr = company.optString("address");
            if (!addr.isEmpty() && !"null".equals(addr)) {
                mapUrl = "https://www.google.com/maps/search/?api=1&query=" + Uri.encode(addr);
            }
        }

        if (about == null) {
            SessionManager session = new SessionManager(this);
            UiUtils.loadImage(AboutActivity.this,
                    UrlHelper.imageFromJson(session.getBaseUrl(), company), heroView, 0);
        }
        mapsButton.setVisibility(mapUrl == null || mapUrl.isEmpty() ? View.GONE : View.VISIBLE);
    }

    private String safe(String value) {
        if (value == null || value.isEmpty() || "null".equals(value)) {
            return "-";
        }
        return value;
    }
}
