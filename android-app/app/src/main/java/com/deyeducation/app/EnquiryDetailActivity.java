package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
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
import java.util.List;

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
    private int enquiryId;
    private MessageAdapter adapter;
    private ProgressBar progressBar;
    private TextInputEditText inputReply;
    private MaterialButton sendButton;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_enquiry_detail);

        enquiryId = getIntent().getIntExtra(EXTRA_ID, 0);
        String subject = getIntent().getStringExtra(EXTRA_SUBJECT);
        api = new ApiClient(new SessionManager(this));

        MaterialToolbar toolbar = findViewById(R.id.enquiryToolbar);
        toolbar.setTitle(subject == null ? getString(R.string.enquiry_thread) : subject);
        toolbar.setNavigationOnClickListener(v -> finish());
        toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);

        TextView meta = findViewById(R.id.enquiryMeta);
        RecyclerView list = findViewById(R.id.messageList);
        inputReply = findViewById(R.id.inputReply);
        sendButton = findViewById(R.id.btnSendReply);
        progressBar = findViewById(R.id.enquiryProgress);

        adapter = new MessageAdapter();
        list.setLayoutManager(new LinearLayoutManager(this));
        list.setAdapter(adapter);

        sendButton.setOnClickListener(v -> sendReply());
        loadThread(meta);
    }

    private void loadThread(TextView meta) {
        progressBar.setVisibility(View.VISIBLE);
        api.get("/enquiries/" + enquiryId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
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
                    progressBar.setVisibility(View.GONE);
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
        progressBar.setVisibility(View.VISIBLE);
        sendButton.setEnabled(false);
        try {
            JSONObject body = new JSONObject();
            body.put("message", text);
            api.post("/enquiries/" + enquiryId + "/messages", body, true, new ApiClient.Callback() {
                @Override
                public void onSuccess(JSONObject json) {
                    runOnUiThread(() -> {
                        progressBar.setVisibility(View.GONE);
                        sendButton.setEnabled(true);
                        inputReply.setText("");
                        UiUtils.toast(EnquiryDetailActivity.this, getString(R.string.message_sent));
                        loadThread(findViewById(R.id.enquiryMeta));
                    });
                }

                @Override
                public void onError(String message) {
                    runOnUiThread(() -> {
                        progressBar.setVisibility(View.GONE);
                        sendButton.setEnabled(true);
                        UiUtils.toast(EnquiryDetailActivity.this, message);
                    });
                }
            });
        } catch (Exception e) {
            progressBar.setVisibility(View.GONE);
            sendButton.setEnabled(true);
            UiUtils.toast(this, e.getMessage());
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

    private static class MessageAdapter extends RecyclerView.Adapter<MessageAdapter.Holder> {
        private final List<JSONObject> items = new ArrayList<>();

        void setMessages(List<JSONObject> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext())
                    .inflate(R.layout.item_enquiry_message, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            JSONObject row = items.get(position);
            boolean admin = "admin".equals(row.optString("sender_type"));
            holder.sender.setText(admin
                    ? holder.itemView.getContext().getString(R.string.admin_reply)
                    : holder.itemView.getContext().getString(R.string.you));
            holder.time.setText(row.optString("created_at", ""));
            holder.body.setText(row.optString("message", ""));
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        static class Holder extends RecyclerView.ViewHolder {
            final TextView sender;
            final TextView time;
            final TextView body;

            Holder(@NonNull View itemView) {
                super(itemView);
                sender = itemView.findViewById(R.id.msgSender);
                time = itemView.findViewById(R.id.msgTime);
                body = itemView.findViewById(R.id.msgBody);
            }
        }
    }
}
