package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;
import org.json.JSONObject;

public class ProfileAccountActivity extends AppCompatActivity {
  public static void open(Context context) {
    context.startActivity(new Intent(context, ProfileAccountActivity.class));
  }

  @Override
  protected void onCreate(@Nullable Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    setContentView(R.layout.activity_profile_account);

    MaterialToolbar toolbar = findViewById(R.id.accountToolbar);
    toolbar.setNavigationOnClickListener(v -> finish());
    toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

    LinearLayout container = findViewById(R.id.accountDetailsContainer);
    View progressBar = findViewById(R.id.accountProgress);
    SessionManager session = new SessionManager(this);
    ApiClient api = new ApiClient(session);

    UiUtils.setLoaderVisible(progressBar, true);
    api.get("/me", true, new ApiClient.Callback() {
      @Override
      public void onSuccess(JSONObject json) {
        runOnUiThread(() -> {
          UiUtils.setLoaderVisible(progressBar, false);
          JSONObject student = json.optJSONObject("student");
          if (student == null) {
            return;
          }
          container.removeAllViews();
          addRow(container, "Name", student.optString("name"));
          addRow(container, "Mobile", student.optString("mobile_number"));
          addRow(container, "Email", student.optString("email"));
          addRow(container, "Address", student.optString("address"));
          addRow(container, "Class", student.optString("class"));
          addRow(container, "Session", student.optString("session"));
          addRow(container, "Course", student.optString("course"));
          addRow(container, "Registration", student.optString("registration_code"));
          addRow(container, "Status", student.optString("status"));
        });
      }

      @Override
      public void onError(String message) {
        runOnUiThread(() -> {
          UiUtils.setLoaderVisible(progressBar, false);
          UiUtils.toast(ProfileAccountActivity.this, message);
        });
      }
    });
  }

  private void addRow(LinearLayout container, String label, String value) {
    View row = LayoutInflater.from(this).inflate(R.layout.item_profile_detail_row, container, false);
    ((TextView) row.findViewById(R.id.detailLabel)).setText(label);
    String text = value == null || value.isEmpty() || "null".equals(value)
        ? getString(R.string.not_provided) : value;
    ((TextView) row.findViewById(R.id.detailValue)).setText(text);
    container.addView(row);
  }
}
