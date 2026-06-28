package com.deyeducation.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONObject;

public class LoginActivity extends AppCompatActivity {
    private SessionManager session;
    private ApiClient api;
    private ProgressBar progressBar;

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

        baseUrl.setText(session.getBaseUrl());
        mobile.setText("9038495748");
        password.setText("12345677");

        login.setOnClickListener(v -> {
            String base = baseUrl.getText() == null ? "" : baseUrl.getText().toString().trim();
            String phone = mobile.getText() == null ? "" : mobile.getText().toString().trim();
            String pass = password.getText() == null ? "" : password.getText().toString();
            if (base.isEmpty() || phone.isEmpty() || pass.isEmpty()) {
                UiUtils.toast(this, "Please fill all fields");
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
                        openMain();
                    });
                }

                @Override
                public void onError(String message) {
                    runOnUiThread(() -> {
                        setLoading(false);
                        UiUtils.toast(LoginActivity.this, message);
                    });
                }
            });
        });
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
    }

    private void openMain() {
        startActivity(new Intent(this, MainActivity.class));
        finish();
    }
}
