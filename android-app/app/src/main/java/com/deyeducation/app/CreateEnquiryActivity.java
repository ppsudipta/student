package com.deyeducation.app;

import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class CreateEnquiryActivity extends AppCompatActivity {
    private final List<String> categoryIds = new ArrayList<>();
    private ApiClient api;
    private ProgressBar progressBar;
    private MaterialButton submitButton;
    private TextView selectedAttachmentView;
    private ImageButton removeAttachmentButton;
    private AttachmentHelper.SelectedFile selectedFile;
    private ActivityResultLauncher<String[]> pickFileLauncher;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_enquiry);

        pickFileLauncher = registerForActivityResult(
                new ActivityResultContracts.OpenDocument(),
                this::onFileSelected);

        MaterialToolbar toolbar = findViewById(R.id.createToolbar);
        UiUtils.setupViewerWindow(this, toolbar);
        toolbar.setTitle(R.string.new_enquiry);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        Spinner spinner = findViewById(R.id.categorySpinner);
        TextInputEditText subject = findViewById(R.id.inputSubject);
        TextInputEditText message = findViewById(R.id.inputMessage);
        submitButton = findViewById(R.id.btnSubmitEnquiry);
        progressBar = findViewById(R.id.createProgress);
        selectedAttachmentView = findViewById(R.id.tvSelectedAttachment);
        removeAttachmentButton = findViewById(R.id.btnRemoveAttachment);
        MaterialButton attachButton = findViewById(R.id.btnAttachFile);
        api = new ApiClient(new SessionManager(this));

        attachButton.setOnClickListener(v -> pickFileLauncher.launch(AttachmentHelper.openDocumentMimeTypes()));
        removeAttachmentButton.setOnClickListener(v -> clearAttachment());

        loadCategories(spinner);
        submitButton.setOnClickListener(v -> submit(subject, message, spinner));
    }

    private void onFileSelected(Uri uri) {
        if (uri == null) {
            return;
        }
        try {
            getContentResolver().takePersistableUriPermission(uri,
                    android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION);
        } catch (Exception ignored) {
        }
        try {
            selectedFile = AttachmentHelper.readSelectedFile(this, uri);
            selectedAttachmentView.setText(selectedFile.fileName);
            selectedAttachmentView.setVisibility(View.VISIBLE);
            removeAttachmentButton.setVisibility(View.VISIBLE);
        } catch (Exception e) {
            clearAttachment();
            UiUtils.toast(this, e.getMessage());
        }
    }

    private void clearAttachment() {
        selectedFile = null;
        selectedAttachmentView.setText("");
        selectedAttachmentView.setVisibility(View.GONE);
        removeAttachmentButton.setVisibility(View.GONE);
    }

    private void loadCategories(Spinner spinner) {
        api.get("/enquiry-categories", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    List<String> labels = new ArrayList<>();
                    categoryIds.clear();
                    JSONArray data = json.optJSONArray("data");
                    if (data != null) {
                        for (int i = 0; i < data.length(); i++) {
                            JSONObject row = data.optJSONObject(i);
                            if (row == null) {
                                continue;
                            }
                            categoryIds.add(row.optString("id"));
                            labels.add(row.optString("label"));
                        }
                    }
                    if (labels.isEmpty()) {
                        categoryIds.add("academic");
                        categoryIds.add("financial");
                        categoryIds.add("technical");
                        categoryIds.add("facilities");
                        categoryIds.add("other");
                        labels.add("Academic");
                        labels.add("Financial");
                        labels.add("Technical Support");
                        labels.add("Facilities");
                        labels.add("Others");
                    }
                    spinner.setAdapter(new ArrayAdapter<>(CreateEnquiryActivity.this,
                            android.R.layout.simple_spinner_dropdown_item, labels));
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> UiUtils.toast(CreateEnquiryActivity.this, message));
            }
        });
    }

    private void submit(TextInputEditText subjectInput, TextInputEditText messageInput, Spinner spinner) {
        String subject = subjectInput.getText() != null ? subjectInput.getText().toString().trim() : "";
        String message = messageInput.getText() != null ? messageInput.getText().toString().trim() : "";
        if (subject.isEmpty() || message.isEmpty() || categoryIds.isEmpty()) {
            UiUtils.toast(this, getString(R.string.fill_all_fields));
            return;
        }
        progressBar.setVisibility(View.VISIBLE);
        submitButton.setEnabled(false);

        Map<String, String> fields = new HashMap<>();
        fields.put("enquiry_type", categoryIds.get(spinner.getSelectedItemPosition()));
        fields.put("subject", subject);
        fields.put("message", message);

        ApiClient.Callback callback = new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    submitButton.setEnabled(true);
                    UiUtils.toast(CreateEnquiryActivity.this, getString(R.string.enquiry_sent));
                    setResult(RESULT_OK);
                    finish();
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    submitButton.setEnabled(true);
                    UiUtils.toast(CreateEnquiryActivity.this, message);
                });
            }
        };

        if (selectedFile != null) {
            api.postMultipart("/enquiries", fields, "attachment",
                    selectedFile.bytes, selectedFile.fileName, selectedFile.mimeType, true, callback);
        } else {
            try {
                JSONObject body = new JSONObject();
                body.put("enquiry_type", fields.get("enquiry_type"));
                body.put("subject", subject);
                body.put("message", message);
                api.post("/enquiries", body, true, callback);
            } catch (Exception e) {
                progressBar.setVisibility(View.GONE);
                submitButton.setEnabled(true);
                UiUtils.toast(this, e.getMessage());
            }
        }
    }
}
