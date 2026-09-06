package com.deyeducation.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.dialog.MaterialAlertDialogBuilder;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONObject;

import java.util.Locale;

public class LoginActivity extends AppCompatActivity {
    private SessionManager session;
    private ApiClient api;
    private View progressBar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        session = new SessionManager(this);
        api = new ApiClient(session);

        if (session.isLoggedIn()) {
            openMain();
            return;
        }

        setContentView(R.layout.activity_login);
        progressBar = findViewById(R.id.loginProgress);
        TextInputEditText baseUrl = findViewById(R.id.inputBaseUrl);
        TextInputEditText mobile = findViewById(R.id.inputMobile);
        TextInputEditText password = findViewById(R.id.inputPassword);
        MaterialButton login = findViewById(R.id.btnLogin);

        baseUrl.setText(AppConfig.DEFAULT_BASE_URL);

        findViewById(R.id.linkRegister).setOnClickListener(v -> {
            String base = baseUrl.getText() == null ? "" : baseUrl.getText().toString().trim();
            Intent intent = new Intent(this, RegisterActivity.class);
            if (!base.isEmpty()) {
                intent.putExtra(RegisterActivity.EXTRA_BASE_URL, base);
            }
            startActivity(intent);
        });

        login.setOnClickListener(v -> {
            String base = baseUrl.getText() == null ? "" : baseUrl.getText().toString().trim();
            String phone = mobile.getText() == null ? "" : mobile.getText().toString().trim();
            String pass = password.getText() == null ? "" : password.getText().toString();
            if (base.isEmpty() || phone.isEmpty() || pass.isEmpty()) {
                UiUtils.toast(this, getString(R.string.fill_all_fields));
                return;
            }
            session.setBaseUrl(base);
            setLoading(true);
            JSONObject body = new JSONObject();
            try {
                body.put("mobile_number", phone);
                body.put("password", pass);
            } catch (Exception ignored) {
            }
            api.post("/login", body, false, new ApiClient.Callback() {
                @Override
                public void onSuccess(JSONObject json) {
                    runOnUiThread(() -> {
                        setLoading(false);
                        String token = json.optString("access_token");
                        if (token.isEmpty()) {
                            UiUtils.toast(LoginActivity.this, getString(R.string.login_failed));
                            return;
                        }
                        session.setToken(token);
                        JSONObject student = json.optJSONObject("student");
                        if (student != null) {
                            session.setStudentName(student.optString("name"));
                        }
                        PushNotificationHelper.registerCurrentToken(LoginActivity.this);
                        openMain();
                    });
                }

                @Override
                public void onError(String message) {
                    runOnUiThread(() -> {
                        setLoading(false);
                        showLoginError(message);
                    });
                }
            });
        });
    }

    private void showLoginError(String message) {
        if (message == null || message.isEmpty()) {
            message = getString(R.string.login_failed);
        }
        String lower = message.toLowerCase(Locale.US);
        boolean needsDialog = lower.contains("approval")
                || lower.contains("not active")
                || lower.contains("completed")
                || lower.contains("pending");
        if (needsDialog) {
            new MaterialAlertDialogBuilder(this)
                    .setTitle(R.string.account_pending_title)
                    .setMessage(message)
                    .setPositiveButton(R.string.ok, null)
                    .show();
        } else {
            UiUtils.toast(this, message);
        }
    }

    private void setLoading(boolean loading) {
        UiUtils.setLoaderVisible(progressBar, loading);
    }

    private void openMain() {
        startActivity(new Intent(this, MainActivity.class));
        finish();
    }
}
