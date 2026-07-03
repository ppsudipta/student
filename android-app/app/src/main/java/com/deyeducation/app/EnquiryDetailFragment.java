package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class EnquiryDetailFragment extends Fragment {
    public static final String ARG_ID = "enquiry_id";
    public static final String ARG_SUBJECT = "enquiry_subject";

    private ApiClient api;
    private int enquiryId;
    private MessageAdapter adapter;
    private ProgressBar progressBar;
    private TextInputEditText inputReply;
    private MaterialButton sendButton;
    private TextView metaView;

    public static EnquiryDetailFragment newInstance(int id, String subject) {
        Bundle args = new Bundle();
        args.putInt(ARG_ID, id);
        args.putString(ARG_SUBJECT, subject);
        EnquiryDetailFragment fragment = new EnquiryDetailFragment();
        fragment.setArguments(args);
        return fragment;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_enquiry_detail, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        Bundle args = getArguments();
        enquiryId = args == null ? 0 : args.getInt(ARG_ID, 0);
        String subject = args == null ? null : args.getString(ARG_SUBJECT);
        api = ((MainActivity) requireActivity()).getApi();

        metaView = view.findViewById(R.id.enquiryMeta);
        RecyclerView list = view.findViewById(R.id.messageList);
        inputReply = view.findViewById(R.id.inputReply);
        sendButton = view.findViewById(R.id.btnSendReply);
        progressBar = view.findViewById(R.id.enquiryProgress);

        adapter = new MessageAdapter();
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        sendButton.setOnClickListener(v -> sendReply());
        loadThread();

        if (getActivity() instanceof MainActivity && subject != null && !subject.isEmpty()) {
            ((MainActivity) getActivity()).setScreenTitle(subject);
        }
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            Bundle args = getArguments();
            String subject = args == null ? null : args.getString(ARG_SUBJECT);
            if (subject == null || subject.isEmpty()) {
                ((MainActivity) getActivity()).setScreenTitle(getString(R.string.enquiry_thread));
            }
        }
    }

    private void loadThread() {
        progressBar.setVisibility(View.VISIBLE);
        api.get("/enquiries/" + enquiryId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        return;
                    }
                    metaView.setText(ucFirst(data.optString("enquiry_type"))
                            + " · " + data.optString("status", "pending"));
                    adapter.setMessages(parseMessages(data.optJSONArray("messages")));
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void sendReply() {
        String text = inputReply.getText() != null ? inputReply.getText().toString().trim() : "";
        if (text.isEmpty()) {
            UiUtils.toast(requireContext(), getString(R.string.fill_all_fields));
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
                    if (!isAdded()) {
                        return;
                    }
                    requireActivity().runOnUiThread(() -> {
                        progressBar.setVisibility(View.GONE);
                        sendButton.setEnabled(true);
                        inputReply.setText("");
                        UiUtils.toast(requireContext(), getString(R.string.message_sent));
                        loadThread();
                    });
                }

                @Override
                public void onError(String message) {
                    if (!isAdded()) {
                        return;
                    }
                    requireActivity().runOnUiThread(() -> {
                        progressBar.setVisibility(View.GONE);
                        sendButton.setEnabled(true);
                        UiUtils.toast(requireContext(), message);
                    });
                }
            });
        } catch (Exception e) {
            progressBar.setVisibility(View.GONE);
            sendButton.setEnabled(true);
            UiUtils.toast(requireContext(), e.getMessage());
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

    private class MessageAdapter extends RecyclerView.Adapter<MessageAdapter.Holder> {
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

        class Holder extends RecyclerView.ViewHolder {
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
