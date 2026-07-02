package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.text.Html;
import android.view.View;
import android.widget.ImageView;
import android.widget.ProgressBar;
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

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_about);

        MaterialToolbar toolbar = findViewById(R.id.aboutToolbar);
        toolbar.setTitle(R.string.about_us_title);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        ImageView hero = findViewById(R.id.aboutHero);
        TextView title = findViewById(R.id.aboutTitle);
        TextView description = findViewById(R.id.aboutDescription);
        TextView phone = findViewById(R.id.aboutPhone);
        TextView email = findViewById(R.id.aboutEmail);
        TextView address = findViewById(R.id.aboutAddress);
        MaterialButton maps = findViewById(R.id.btnOpenMaps);
        ProgressBar progress = findViewById(R.id.aboutProgress);

        progress.setVisibility(View.VISIBLE);
        SessionManager session = new SessionManager(this);
        new ApiClient(session).get("/about", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        return;
                    }
                    JSONObject company = data.optJSONObject("company");
                    JSONObject about = data.optJSONObject("about");

                    if (about != null) {
                        title.setText(about.optString("title", getString(R.string.about_us_title)));
                        String details = about.optString("details", "");
                        description.setText(Html.fromHtml(details, Html.FROM_HTML_MODE_COMPACT));
                        UiUtils.loadImage(AboutActivity.this, about.optString("image_url"), hero, 0);
                    } else if (company != null) {
                        title.setText(company.optString("name", getString(R.string.about_us_title)));
                    }

                    if (company != null) {
                        phone.setText(getString(R.string.phone_label) + ": " + company.optString("ph1", "-"));
                        email.setText(getString(R.string.email_label) + ": " + company.optString("email", "-"));
                        address.setText(getString(R.string.address_label) + ": " + company.optString("address", "-"));
                        mapUrl = company.optString("map_url");
                        if (mapUrl == null || mapUrl.isEmpty()) {
                            String addr = company.optString("address");
                            if (!addr.isEmpty()) {
                                mapUrl = "https://www.google.com/maps/search/?api=1&query=" + Uri.encode(addr);
                            }
                        }
                        if (company.optString("image_url").isEmpty() && about == null) {
                            UiUtils.loadImage(AboutActivity.this, company.optString("image_url"), hero, 0);
                        }
                    }
                    maps.setVisibility(mapUrl == null || mapUrl.isEmpty() ? View.GONE : View.VISIBLE);
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
                    UiUtils.toast(AboutActivity.this, message);
                });
            }
        });

        maps.setOnClickListener(v -> {
            if (mapUrl != null && !mapUrl.isEmpty()) {
                startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(mapUrl)));
            }
        });
    }
}
