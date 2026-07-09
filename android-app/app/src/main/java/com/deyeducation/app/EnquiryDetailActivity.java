package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageButton;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class EnquiryDetailActivity extends AppCompatActivity {
    public static final String EXTRA_ID = "enquiry_id";
    public static final String EXTRA_SUBJECT = "enquiry_subject";

    public static void open(Context context, int id, String subject) {
        Intent intent = new Intent(context, EnquiryDetailActivity.class);
        intent.putExtra(EXTRA_ID, id);
        intent.putExtra(EXTRA_SUBJECT, subject);
        context.startActivity(intent);
    }

    private ApiClient api;
    private SessionManager session;
    private int enquiryId;
    private EnquiryMessagesAdapter adapter;
    private View progressBar;
    private TextInputEditText inputReply;
    private MaterialButton sendButton;
    private TextView replyAttachmentView;
    private AttachmentHelper.SelectedFile selectedFile;
    private ActivityResultLauncher<String[]> pickFileLauncher;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_enquiry_detail);

        pickFileLauncher = registerForActivityResult(
                new ActivityResultContracts.OpenDocument(),
                this::onFileSelected);

        enquiryId = getIntent().getIntExtra(EXTRA_ID, 0);
        String subject = getIntent().getStringExtra(EXTRA_SUBJECT);
        session = new SessionManager(this);
        api = new ApiClient(session);

        MaterialToolbar toolbar = findViewById(R.id.enquiryToolbar);
        toolbar.setTitle(subject == null ? getString(R.string.enquiry_thread) : subject);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        TextView meta = findViewById(R.id.enquiryMeta);
        RecyclerView list = findViewById(R.id.messageList);
        inputReply = findViewById(R.id.inputReply);
        sendButton = findViewById(R.id.btnSendReply);
        progressBar = findViewById(R.id.enquiryProgress);
        replyAttachmentView = findViewById(R.id.tvReplyAttachment);
        ImageButton attachButton = findViewById(R.id.btnAttachReply);

        adapter = new EnquiryMessagesAdapter(session.getBaseUrl());
        list.setLayoutManager(new LinearLayoutManager(this));
        list.setAdapter(adapter);

        attachButton.setOnClickListener(v ->
                pickFileLauncher.launch(AttachmentHelper.openDocumentMimeTypes()));
        sendButton.setOnClickListener(v -> sendReply());
        loadThread(meta);
    }

    private void onFileSelected(Uri uri) {
        if (uri == null) {
            return;
        }
        try {
            getContentResolver().takePersistableUriPermission(uri,
                    Intent.FLAG_GRANT_READ_URI_PERMISSION);
        } catch (Exception ignored) {
        }
        try {
            selectedFile = AttachmentHelper.readSelectedFile(this, uri);
            replyAttachmentView.setText(selectedFile.fileName);
            replyAttachmentView.setVisibility(View.VISIBLE);
        } catch (Exception e) {
            clearAttachment();
            UiUtils.toast(this, e.getMessage());
        }
    }

    private void clearAttachment() {
        selectedFile = null;
        replyAttachmentView.setText("");
        replyAttachmentView.setVisibility(View.GONE);
    }

    private void loadThread(TextView meta) {
        UiUtils.setLoaderVisible(progressBar, true);
        api.get("/enquiries/" + enquiryId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        return;
                    }
                    meta.setText(ucFirst(data.optString("enquiry_type"))
                            + " · " + data.optString("status", "pending"));
                    adapter.setMessages(parseMessages(data.optJSONArray("messages")));
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    UiUtils.toast(EnquiryDetailActivity.this, message);
                });
            }
        });
    }

    private void sendReply() {
        String text = inputReply.getText() != null ? inputReply.getText().toString().trim() : "";
        if (text.isEmpty()) {
            UiUtils.toast(this, getString(R.string.fill_all_fields));
            return;
        }
        UiUtils.setLoaderVisible(progressBar, true);
        sendButton.setEnabled(false);

        Map<String, String> fields = new HashMap<>();
        fields.put("message", text);

        ApiClient.Callback callback = new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    sendButton.setEnabled(true);
                    inputReply.setText("");
                    clearAttachment();
                    UiUtils.toast(EnquiryDetailActivity.this, getString(R.string.message_sent));
                    loadThread(findViewById(R.id.enquiryMeta));
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    sendButton.setEnabled(true);
                    UiUtils.toast(EnquiryDetailActivity.this, message);
                });
            }
        };

        if (selectedFile != null) {
            api.postMultipart("/enquiries/" + enquiryId + "/messages", fields, "attachment",
                    selectedFile.bytes, selectedFile.fileName, selectedFile.mimeType, true, callback);
        } else {
            try {
                JSONObject body = new JSONObject();
                body.put("message", text);
                api.post("/enquiries/" + enquiryId + "/messages", body, true, callback);
            } catch (Exception e) {
                UiUtils.setLoaderVisible(progressBar, false);
                sendButton.setEnabled(true);
                UiUtils.toast(this, e.getMessage());
            }
        }
    }

    private List<JSONObject> parseMessages(JSONArray array) {
        List<JSONObject> items = new ArrayList<>();
        if (array == null) {
            return items;
        }
        for (int i = 0; i < array.length(); i++) {
            JSONObject row = array.optJSONObject(i);
            if (row != null) {
                items.add(row);
            }
        }
        return items;
    }

    private String ucFirst(String value) {
        if (value == null || value.isEmpty()) {
            return "";
        }
        return value.substring(0, 1).toUpperCase() + value.substring(1);
    }
}
