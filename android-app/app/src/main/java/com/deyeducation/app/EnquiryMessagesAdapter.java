package com.deyeducation.app;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class EnquiryMessagesAdapter extends RecyclerView.Adapter<EnquiryMessagesAdapter.Holder> {
    private final List<JSONObject> items = new ArrayList<>();
    private final String baseUrl;

    public EnquiryMessagesAdapter(String baseUrl) {
        this.baseUrl = baseUrl;
    }

    public void setMessages(List<JSONObject> next) {
        items.clear();
        if (next != null) {
            items.addAll(next);
        }
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

        holder.attachmentsContainer.removeAllViews();
        List<String> names = AttachmentHelper.attachmentNamesFromJson(row);
        List<String> urls = AttachmentHelper.attachmentUrlsFromJson(baseUrl, row);
        if (names.isEmpty() || urls.isEmpty()) {
            holder.attachmentsContainer.setVisibility(View.GONE);
            return;
        }

        holder.attachmentsContainer.setVisibility(View.VISIBLE);
        LayoutInflater inflater = LayoutInflater.from(holder.itemView.getContext());
        int count = Math.min(names.size(), urls.size());
        for (int i = 0; i < count; i++) {
            String name = names.get(i);
            String url = urls.get(i);
            if (url == null || url.isEmpty()) {
                continue;
            }
            View chip = inflater.inflate(R.layout.item_selected_attachment, holder.attachmentsContainer, false);
            TextView nameView = chip.findViewById(R.id.tvAttachmentName);
            View remove = chip.findViewById(R.id.btnRemoveAttachmentItem);
            if (remove != null) {
                remove.setVisibility(View.GONE);
            }
            nameView.setText(AttachmentHelper.displayFileName(name));
            chip.setOnClickListener(v ->
                    AttachmentHelper.openAttachmentUrl(holder.itemView.getContext(), name, url));
            holder.attachmentsContainer.addView(chip);
        }
        if (holder.attachmentsContainer.getChildCount() == 0) {
            holder.attachmentsContainer.setVisibility(View.GONE);
        }
    }

    @Override
    public int getItemCount() {
        return items.size();
    }

    static class Holder extends RecyclerView.ViewHolder {
        final TextView sender;
        final TextView time;
        final TextView body;
        final LinearLayout attachmentsContainer;

        Holder(@NonNull View itemView) {
            super(itemView);
            sender = itemView.findViewById(R.id.msgSender);
            time = itemView.findViewById(R.id.msgTime);
            body = itemView.findViewById(R.id.msgBody);
            attachmentsContainer = itemView.findViewById(R.id.msgAttachmentsContainer);
        }
    }
}
