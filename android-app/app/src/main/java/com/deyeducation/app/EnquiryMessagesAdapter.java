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

        String attachment = row.optString("attachment", "");
        String attachmentUrl = UrlHelper.enquiryAttachmentFromJson(baseUrl, row);
        if (attachmentUrl != null && !attachment.isEmpty() && !"null".equalsIgnoreCase(attachment)) {
            holder.attachmentRow.setVisibility(View.VISIBLE);
            holder.attachmentName.setText(AttachmentHelper.displayFileName(attachment));
            holder.attachmentRow.setOnClickListener(v ->
                    AttachmentHelper.openAttachment(holder.itemView.getContext(), baseUrl, row));
        } else {
            holder.attachmentRow.setVisibility(View.GONE);
            holder.attachmentRow.setOnClickListener(null);
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
        final LinearLayout attachmentRow;
        final TextView attachmentName;

        Holder(@NonNull View itemView) {
            super(itemView);
            sender = itemView.findViewById(R.id.msgSender);
            time = itemView.findViewById(R.id.msgTime);
            body = itemView.findViewById(R.id.msgBody);
            attachmentRow = itemView.findViewById(R.id.msgAttachmentRow);
            attachmentName = itemView.findViewById(R.id.msgAttachmentName);
        }
    }
}
