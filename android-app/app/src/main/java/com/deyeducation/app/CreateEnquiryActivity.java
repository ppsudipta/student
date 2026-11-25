package com.deyeducation.app;

import android.net.Uri;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ImageButton;
import android.widget.LinearLayout;
import android.widget.Spinner;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.Nullable;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class CreateEnquiryActivity extends PortraitActivity {
    public static final String TYPE_RPS_CHAT = "rps_chat";
    public static final int MAX_ATTACHMENTS = 5;

    private final List<String> categoryIds = new ArrayList<>();
    private final List<AttachmentHelper.SelectedFile> selectedFiles = new ArrayList<>();
    private ApiClient api;
    private View progressBar;
    private MaterialButton submitButton;
    private MaterialButton attachButton;
    private TextView attachmentLabelView;
    private TextView attachmentHintView;
    private LinearLayout attachmentsList;
    private Spinner categorySpinner;
    private ActivityResultLauncher<String[]> pickFileLauncher;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_enquiry);

        pickFileLauncher = registerForActivityResult(
                new ActivityResultContracts.OpenMultipleDocuments(),
                this::onFilesSelected);

        MaterialToolbar toolbar = findViewById(R.id.createToolbar);
        UiUtils.setupViewerWindow(this, toolbar);
        toolbar.setTitle(R.string.new_enquiry);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        categorySpinner = findViewById(R.id.categorySpinner);
        TextInputEditText subject = findViewById(R.id.inputSubject);
        TextInputEditText message = findViewById(R.id.inputMessage);
        submitButton = findViewById(R.id.btnSubmitEnquiry);
        progressBar = findViewById(R.id.createProgress);
        attachmentLabelView = findViewById(R.id.tvAttachmentLabel);
        attachmentHintView = findViewById(R.id.tvAttachmentHint);
        attachmentsList = findViewById(R.id.attachmentsList);
        attachButton = findViewById(R.id.btnAttachFile);
        api = new ApiClient(new SessionManager(this));

        attachButton.setOnClickListener(v -> {
            if (selectedFiles.size() >= MAX_ATTACHMENTS) {
                UiUtils.toast(this, getString(R.string.attachment_max_reached));
                return;
            }
            pickFileLauncher.launch(AttachmentHelper.openDocumentMimeTypes());
        });

        categorySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                updateAttachmentUiForCategory();
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {
            }
        });

        loadCategories();
        submitButton.setOnClickListener(v -> submit(subject, message));
    }

    private void onFilesSelected(List<Uri> uris) {
        if (uris == null || uris.isEmpty()) {
            return;
        }
        int remaining = MAX_ATTACHMENTS - selectedFiles.size();
        if (remaining <= 0) {
            UiUtils.toast(this, getString(R.string.attachment_max_reached));
            return;
        }
        int accepted = 0;
        for (Uri uri : uris) {
            if (accepted >= remaining) {
                UiUtils.toast(this, getString(R.string.attachment_max_reached));
                break;
            }
            if (uri == null) {
                continue;
            }
            try {
                getContentResolver().takePersistableUriPermission(uri,
                        android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION);
            } catch (Exception ignored) {
            }
            try {
                AttachmentHelper.SelectedFile file = AttachmentHelper.readSelectedFile(this, uri);
                selectedFiles.add(file);
                accepted++;
            } catch (Exception e) {
                UiUtils.toast(this, e.getMessage());
            }
        }
        renderSelectedFiles();
        updateAttachmentUiForCategory();
    }

    private void renderSelectedFiles() {
        attachmentsList.removeAllViews();
        LayoutInflater inflater = LayoutInflater.from(this);
        for (int i = 0; i < selectedFiles.size(); i++) {
            AttachmentHelper.SelectedFile file = selectedFiles.get(i);
            View row = inflater.inflate(R.layout.item_selected_attachment, attachmentsList, false);
            TextView name = row.findViewById(R.id.tvAttachmentName);
            ImageButton remove = row.findViewById(R.id.btnRemoveAttachmentItem);
            name.setText(file.fileName);
            final int index = i;
            remove.setOnClickListener(v -> {
                if (index >= 0 && index < selectedFiles.size()) {
                    selectedFiles.remove(index);
                    renderSelectedFiles();
                    updateAttachmentUiForCategory();
                }
            });
            attachmentsList.addView(row);
        }
        attachButton.setEnabled(selectedFiles.size() < MAX_ATTACHMENTS);
    }

    private boolean isRpsChatSelected() {
        int position = categorySpinner.getSelectedItemPosition();
        return position >= 0 && position < categoryIds.size()
                && TYPE_RPS_CHAT.equals(categoryIds.get(position));
    }

    private void updateAttachmentUiForCategory() {
        if (isRpsChatSelected()) {
            attachmentLabelView.setText(R.string.attachment_required_rps);
        } else {
            attachmentLabelView.setText(R.string.attachment_optional);
        }
        attachmentHintView.setText(R.string.attachment_limit_hint_multi);
    }

    private void loadCategories() {
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
                        applyFallbackCategories(labels);
                    }
                    categorySpinner.setAdapter(new ArrayAdapter<>(CreateEnquiryActivity.this,
                            android.R.layout.simple_spinner_dropdown_item, labels));
                    updateAttachmentUiForCategory();
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    List<String> labels = new ArrayList<>();
                    categoryIds.clear();
                    applyFallbackCategories(labels);
                    categorySpinner.setAdapter(new ArrayAdapter<>(CreateEnquiryActivity.this,
                            android.R.layout.simple_spinner_dropdown_item, labels));
                    updateAttachmentUiForCategory();
                    UiUtils.toast(CreateEnquiryActivity.this, message);
                });
            }
        });
    }

    private void applyFallbackCategories(List<String> labels) {
        categoryIds.add("academic");
        categoryIds.add("financial");
        categoryIds.add("technical");
        categoryIds.add("facilities");
        categoryIds.add(TYPE_RPS_CHAT);
        categoryIds.add("other");
        labels.add("Academic");
        labels.add("Financial");
        labels.add("Technical Support");
        labels.add("Facilities");
        labels.add(getString(R.string.enquiry_type_rps_chat));
        labels.add("Others");
    }

    private void submit(TextInputEditText subjectInput, TextInputEditText messageInput) {
        String subject = subjectInput.getText() != null ? subjectInput.getText().toString().trim() : "";
        String message = messageInput.getText() != null ? messageInput.getText().toString().trim() : "";
        if (subject.isEmpty() || message.isEmpty() || categoryIds.isEmpty()) {
            UiUtils.toast(this, getString(R.string.fill_all_fields));
            return;
        }
        String enquiryType = categoryIds.get(categorySpinner.getSelectedItemPosition());
        if (TYPE_RPS_CHAT.equals(enquiryType) && selectedFiles.isEmpty()) {
            UiUtils.toast(this, getString(R.string.attachment_required));
            return;
        }
        if (selectedFiles.size() > MAX_ATTACHMENTS) {
            UiUtils.toast(this, getString(R.string.attachment_max_reached));
            return;
        }

        UiUtils.setLoaderVisible(progressBar, true);
        submitButton.setEnabled(false);

        Map<String, String> fields = new HashMap<>();
        fields.put("enquiry_type", enquiryType);
        fields.put("subject", subject);
        fields.put("message", message);

        ApiClient.Callback callback = new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    submitButton.setEnabled(true);
                    UiUtils.toast(CreateEnquiryActivity.this, getString(R.string.enquiry_sent));
                    setResult(RESULT_OK);
                    finish();
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    submitButton.setEnabled(true);
                    UiUtils.toast(CreateEnquiryActivity.this, message);
                });
            }
        };

        if (!selectedFiles.isEmpty()) {
            String fileField = selectedFiles.size() > 1 ? "attachments" : "attachment";
            api.postMultipart("/enquiries", fields, fileField, selectedFiles, true, callback);
        } else {
            try {
                JSONObject body = new JSONObject();
                body.put("enquiry_type", enquiryType);
                body.put("subject", subject);
                body.put("message", message);
                api.post("/enquiries", body, true, callback);
            } catch (Exception e) {
                UiUtils.setLoaderVisible(progressBar, false);
                submitButton.setEnabled(true);
                UiUtils.toast(this, e.getMessage());
            }
        }
    }
}
