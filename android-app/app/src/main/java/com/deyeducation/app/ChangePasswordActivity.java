package com.deyeducation.app;

import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONObject;

public class ChangePasswordActivity extends AppCompatActivity {
  @Override
  protected void onCreate(@Nullable Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    setContentView(R.layout.activity_change_password);

    MaterialToolbar toolbar = findViewById(R.id.passwordToolbar);
    toolbar.setNavigationOnClickListener(v -> finish());
    toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

    TextInputEditText currentInput = findViewById(R.id.inputCurrentPassword);
    TextInputEditText newInput = findViewById(R.id.inputNewPassword);
    TextInputEditText confirmInput = findViewById(R.id.inputConfirmPassword);
    MaterialButton saveButton = findViewById(R.id.btnSavePassword);
    ProgressBar progressBar = findViewById(R.id.passwordProgress);
    ApiClient api = new ApiClient(new SessionManager(this));

    saveButton.setOnClickListener(v -> {
      String current = textOf(currentInput);
      String next = textOf(newInput);
      String confirm = textOf(confirmInput);
      if (current.isEmpty() || next.isEmpty() || confirm.isEmpty()) {
        UiUtils.toast(this, getString(R.string.fill_all_fields));
        return;
      }
      if (!next.equals(confirm)) {
        UiUtils.toast(this, getString(R.string.password_mismatch));
        return;
      }
      progressBar.setVisibility(View.VISIBLE);
      saveButton.setEnabled(false);
      try {
        JSONObject body = new JSONObject();
        body.put("current_password", current);
        body.put("new_password", next);
        api.post("/change-password", body, true, new ApiClient.Callback() {
          @Override
          public void onSuccess(JSONObject json) {
            runOnUiThread(() -> {
              progressBar.setVisibility(View.GONE);
              saveButton.setEnabled(true);
              UiUtils.toast(ChangePasswordActivity.this, getString(R.string.password_changed));
              finish();
            });
          }

          @Override
          public void onError(String message) {
            runOnUiThread(() -> {
              progressBar.setVisibility(View.GONE);
              saveButton.setEnabled(true);
              UiUtils.toast(ChangePasswordActivity.this, message);
            });
          }
        });
      } catch (Exception e) {
        progressBar.setVisibility(View.GONE);
        saveButton.setEnabled(true);
        UiUtils.toast(this, e.getMessage());
      }
    });
  }

  private String textOf(TextInputEditText input) {
    return input.getText() != null ? input.getText().toString().trim() : "";
  }
}
