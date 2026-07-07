package com.deyeducation.app;

import android.net.Uri;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
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
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class EnquiryDetailFragment extends Fragment {
    public static final String ARG_ID = "enquiry_id";
    public static final String ARG_SUBJECT = "enquiry_subject";

    private ApiClient api;
    private SessionManager session;
    private int enquiryId;
    private EnquiryMessagesAdapter adapter;
    private ProgressBar progressBar;
    private TextInputEditText inputReply;
    private MaterialButton sendButton;
    private TextView metaView;
    private TextView replyAttachmentView;
    private AttachmentHelper.SelectedFile selectedFile;
    private ActivityResultLauncher<String[]> pickFileLauncher;

    public static EnquiryDetailFragment newInstance(int id, String subject) {
        Bundle args = new Bundle();
        args.putInt(ARG_ID, id);
        args.putString(ARG_SUBJECT, subject);
        EnquiryDetailFragment fragment = new EnquiryDetailFragment();
        fragment.setArguments(args);
        return fragment;
    }

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        pickFileLauncher = registerForActivityResult(
                new ActivityResultContracts.OpenDocument(),
                this::onFileSelected);
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
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        metaView = view.findViewById(R.id.enquiryMeta);
        RecyclerView list = view.findViewById(R.id.messageList);
        inputReply = view.findViewById(R.id.inputReply);
        sendButton = view.findViewById(R.id.btnSendReply);
        progressBar = view.findViewById(R.id.enquiryProgress);
        replyAttachmentView = view.findViewById(R.id.tvReplyAttachment);
        ImageButton attachButton = view.findViewById(R.id.btnAttachReply);

        adapter = new EnquiryMessagesAdapter(session.getBaseUrl());
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        attachButton.setOnClickListener(v ->
                pickFileLauncher.launch(AttachmentHelper.openDocumentMimeTypes()));
        sendButton.setOnClickListener(v -> sendReply());
        loadThread();

        if (subject != null && !subject.isEmpty()) {
            activity.setScreenTitle(subject);
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

    private void onFileSelected(Uri uri) {
        if (uri == null || !isAdded()) {
            return;
        }
        try {
            requireContext().getContentResolver().takePersistableUriPermission(uri,
                    android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION);
        } catch (Exception ignored) {
        }
        try {
            selectedFile = AttachmentHelper.readSelectedFile(requireContext(), uri);
            replyAttachmentView.setText(selectedFile.fileName);
            replyAttachmentView.setVisibility(View.VISIBLE);
        } catch (Exception e) {
            clearAttachment();
            UiUtils.toast(requireContext(), e.getMessage());
        }
    }

    private void clearAttachment() {
        selectedFile = null;
        replyAttachmentView.setText("");
        replyAttachmentView.setVisibility(View.GONE);
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

        Map<String, String> fields = new HashMap<>();
        fields.put("message", text);

        ApiClient.Callback callback = new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    sendButton.setEnabled(true);
                    inputReply.setText("");
                    clearAttachment();
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
                progressBar.setVisibility(View.GONE);
                sendButton.setEnabled(true);
                UiUtils.toast(requireContext(), e.getMessage());
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
