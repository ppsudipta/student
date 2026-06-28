package com.deyeducation.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.button.MaterialButton;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;

public class ListFragment extends Fragment {
    public static final String ARG_TYPE = "type";
    public static final String TYPE_MATERIALS = "materials";
    public static final String TYPE_NOTICES = "notices";
    public static final String TYPE_GALLERY = "gallery";
    public static final String TYPE_FEES = "fees";
    public static final String TYPE_HOMEWORK = "homework";
    public static final String TYPE_ENQUIRIES = "enquiries";
    public static final String TYPE_COURSES = "courses";

    private ApiClient api;
    private SessionManager session;
    private String type;
    private SwipeRefreshLayout swipeRefresh;
    private ProgressBar progressBar;
    private TextView emptyView;
    private ListAdapter adapter;

    public static ListFragment newInstance(String type) {
        ListFragment fragment = new ListFragment();
        Bundle args = new Bundle();
        args.putString(ARG_TYPE, type);
        fragment.setArguments(args);
        return fragment;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_list, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();
        type = getArguments() != null ? getArguments().getString(ARG_TYPE, TYPE_MATERIALS) : TYPE_MATERIALS;

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        progressBar = view.findViewById(R.id.listProgress);
        emptyView = view.findViewById(R.id.emptyView);
        RecyclerView recyclerView = view.findViewById(R.id.recyclerView);
        adapter = new ListAdapter();
        recyclerView.setLayoutManager(new LinearLayoutManager(requireContext()));
        recyclerView.setAdapter(adapter);

        swipeRefresh.setColorSchemeResources(R.color.primary);
        swipeRefresh.setOnRefreshListener(this::loadData);
        loadData();
    }

    private void loadData() {
        if (!swipeRefresh.isRefreshing()) {
            progressBar.setVisibility(View.VISIBLE);
        }
        emptyView.setVisibility(View.GONE);

        String path = pathForType();
        boolean auth = !TYPE_COURSES.equals(type) && !TYPE_GALLERY.equals(type) || true;
        api.get(path, auth, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    progressBar.setVisibility(View.GONE);
                    List<ListItem> items = parseItems(json);
                    adapter.setItems(items);
                    emptyView.setVisibility(items.isEmpty() ? View.VISIBLE : View.GONE);
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    progressBar.setVisibility(View.GONE);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private String pathForType() {
        switch (type) {
            case TYPE_NOTICES:
                return "/notices?per_page=30";
            case TYPE_GALLERY:
                return "/gallery?per_page=30";
            case TYPE_FEES:
                return "/fees";
            case TYPE_HOMEWORK:
                return "/homework?per_page=30";
            case TYPE_ENQUIRIES:
                return "/enquiries?per_page=30";
            case TYPE_COURSES:
                return "/courses?per_page=30";
            default:
                return "/materials?per_page=30";
        }
    }

    private List<ListItem> parseItems(JSONObject json) {
        List<ListItem> items = new ArrayList<>();
        if (TYPE_FEES.equals(type)) {
            JSONObject summary = json.optJSONObject("summary");
            if (summary != null) {
                items.add(buildFieldsItem("Fee Summary", summary));
            }
            JSONObject payments = json.optJSONObject("payments");
            JSONArray rows = payments != null ? extractArray(payments) : new JSONArray();
            appendRows(items, rows, "payment_reason", "amount");
            return items;
        }
        JSONArray rows = extractRootArray(json);
        if (TYPE_MATERIALS.equals(type)) {
            appendMaterialRows(items, rows);
        } else {
            appendRows(items, rows, titleKeyForType(), subtitleKeyForType());
        }
        return items;
    }

    private JSONArray extractRootArray(JSONObject json) {
        Object data = json.opt("data");
        if (data instanceof JSONArray) {
            return (JSONArray) data;
        }
        if (data instanceof JSONObject) {
            return extractArray((JSONObject) data);
        }
        return new JSONArray();
    }

    private JSONArray extractArray(JSONObject container) {
        Object data = container.opt("data");
        if (data instanceof JSONArray) {
            return (JSONArray) data;
        }
        return new JSONArray();
    }

    private void appendRows(List<ListItem> items, JSONArray rows, String titleKey, String subtitleKey) {
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            ListItem item = new ListItem();
            item.title = first(row, titleKey, "name", "title", "subject", "notice_content");
            item.subtitle = first(row, subtitleKey, "material_description", "message", "description", "content");
            item.raw = row;
            items.add(item);
        }
    }

    private void appendMaterialRows(List<ListItem> items, JSONArray rows) {
        String baseUrl = session.getBaseUrl();
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            ListItem item = new ListItem();
            item.title = first(row, "material_title", "name", "title");
            item.subtitle = UrlHelper.cleanHtml(first(row, "material_description", "description", "subject", "material_category"));
            item.raw = row;
            item.fileUrl = first(row, "file_url");
            if (item.fileUrl.isEmpty()) {
                String filePath = row.optString("file_path");
                if (!filePath.isEmpty() && !"null".equals(filePath)) {
                    item.fileUrl = UrlHelper.resolveImageUrl(baseUrl, filePath);
                }
            }
            JSONObject playback = row.optJSONObject("playback");
            if (playback != null) {
                item.videoUrl = playback.optString("embed_url");
            }
            items.add(item);
        }
    }

    private ListItem buildFieldsItem(String title, JSONObject obj) {
        ListItem item = new ListItem();
        item.title = title;
        StringBuilder body = new StringBuilder();
        Iterator<String> keys = obj.keys();
        while (keys.hasNext()) {
            String key = keys.next();
            body.append(formatKey(key)).append(": ").append(obj.optString(key)).append("\n");
        }
        item.subtitle = body.toString().trim();
        item.raw = obj;
        return item;
    }

    private String titleKeyForType() {
        switch (type) {
            case TYPE_NOTICES:
                return "notice_content";
            case TYPE_GALLERY:
                return "name";
            case TYPE_HOMEWORK:
                return "title";
            case TYPE_ENQUIRIES:
                return "subject";
            case TYPE_COURSES:
                return "name";
            default:
                return "material_title";
        }
    }

    private String subtitleKeyForType() {
        switch (type) {
            case TYPE_NOTICES:
                return "created_at";
            case TYPE_GALLERY:
                return "type";
            case TYPE_HOMEWORK:
                return "description";
            case TYPE_ENQUIRIES:
                return "message";
            default:
                return "description";
        }
    }

    private String first(JSONObject obj, String... keys) {
        for (String key : keys) {
            String value = obj.optString(key);
            if (value != null && !value.isEmpty() && !"null".equals(value)) {
                return UrlHelper.cleanHtml(value);
            }
        }
        return "";
    }

    private String formatKey(String key) {
        return key.replace("_", " ");
    }

    private class ListAdapter extends RecyclerView.Adapter<ListAdapter.Holder> {
        private final List<ListItem> items = new ArrayList<>();

        void setItems(List<ListItem> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_list_card, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            ListItem item = items.get(position);
            holder.title.setText(item.title.isEmpty() ? "Item" : item.title);
            holder.subtitle.setText(item.subtitle);
            holder.action.setOnClickListener(null);
            holder.itemView.setOnClickListener(null);

            if (item.videoUrl != null && !item.videoUrl.isEmpty()) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setText(R.string.play_video);
                View.OnClickListener openVideo = v -> {
                    Intent intent = new Intent(requireContext(), VideoActivity.class);
                    intent.putExtra(VideoActivity.EXTRA_URL, item.videoUrl);
                    intent.putExtra(VideoActivity.EXTRA_TITLE, item.title);
                    startActivity(intent);
                };
                holder.action.setOnClickListener(openVideo);
                holder.itemView.setOnClickListener(openVideo);
            } else if (item.fileUrl != null && !item.fileUrl.isEmpty()) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setText(R.string.open_material);
                View.OnClickListener openFile = v -> openMaterial(item.fileUrl);
                holder.action.setOnClickListener(openFile);
                holder.itemView.setOnClickListener(openFile);
            } else {
                holder.action.setVisibility(View.GONE);
            }
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        class Holder extends RecyclerView.ViewHolder {
            final TextView title;
            final TextView subtitle;
            final MaterialButton action;

            Holder(@NonNull View itemView) {
                super(itemView);
                title = itemView.findViewById(R.id.itemTitle);
                subtitle = itemView.findViewById(R.id.itemSubtitle);
                action = itemView.findViewById(R.id.btnAction);
            }
        }
    }

    private void openMaterial(String url) {
        try {
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            startActivity(Intent.createChooser(intent, getString(R.string.open_material)));
        } catch (Exception e) {
            UiUtils.toast(requireContext(), "Unable to open file");
        }
    }

    private static class ListItem {
        String title = "";
        String subtitle = "";
        String videoUrl = "";
        String fileUrl = "";
        JSONObject raw;
    }
}
