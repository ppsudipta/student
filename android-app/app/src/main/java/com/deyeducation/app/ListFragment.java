package com.deyeducation.app;

import android.content.Intent;
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
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.tabs.TabLayout;

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

    private static final int MATERIAL_FILTER_ALL = 0;
    private static final int MATERIAL_FILTER_DOCUMENT = 1;
    private static final int MATERIAL_FILTER_VIDEO = 2;

    private ApiClient api;
    private SessionManager session;
    private String type;
    private SwipeRefreshLayout swipeRefresh;
    private ProgressBar progressBar;
    private TextView emptyView;
    private TabLayout materialTabs;
    private ListAdapter adapter;
    private final List<ListItem> allMaterialItems = new ArrayList<>();
    private int materialFilter = MATERIAL_FILTER_ALL;

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
        materialTabs = view.findViewById(R.id.materialTabs);
        RecyclerView recyclerView = view.findViewById(R.id.recyclerView);
        adapter = new ListAdapter();
        recyclerView.setLayoutManager(new LinearLayoutManager(requireContext()));
        recyclerView.setHasFixedSize(false);
        recyclerView.setAdapter(adapter);

        if (TYPE_MATERIALS.equals(type)) {
            setupMaterialTabs();
        }

        swipeRefresh.setColorSchemeResources(R.color.primary);
        swipeRefresh.setOnRefreshListener(this::loadData);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) ->
                recyclerView.canScrollVertically(-1));
        loadData();
    }

    private void setupMaterialTabs() {
        materialTabs.setVisibility(View.VISIBLE);
        materialTabs.removeAllTabs();
        materialTabs.addTab(materialTabs.newTab().setText(R.string.materials_tab_all));
        materialTabs.addTab(materialTabs.newTab().setText(R.string.materials_tab_documents));
        materialTabs.addTab(materialTabs.newTab().setText(R.string.materials_tab_videos));
        materialTabs.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                materialFilter = tab.getPosition();
                applyMaterialFilter();
            }

            @Override
            public void onTabUnselected(TabLayout.Tab tab) {
            }

            @Override
            public void onTabReselected(TabLayout.Tab tab) {
            }
        });
    }

    private void applyMaterialFilter() {
        List<ListItem> filtered = new ArrayList<>();
        for (ListItem item : allMaterialItems) {
            if (matchesMaterialFilter(item)) {
                filtered.add(item);
            }
        }
        adapter.setItems(filtered);
        emptyView.setVisibility(filtered.isEmpty() ? View.VISIBLE : View.GONE);
    }

    private boolean matchesMaterialFilter(ListItem item) {
        switch (materialFilter) {
            case MATERIAL_FILTER_VIDEO:
                return item.hasVideo;
            case MATERIAL_FILTER_DOCUMENT:
                return !item.hasVideo;
            default:
                return true;
        }
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
                    if (TYPE_MATERIALS.equals(type)) {
                        allMaterialItems.clear();
                        allMaterialItems.addAll(items);
                        applyMaterialFilter();
                    } else {
                        adapter.setItems(items);
                        emptyView.setVisibility(items.isEmpty() ? View.VISIBLE : View.GONE);
                    }
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

            String materialType = row.optString("material_type", "").toLowerCase();
            item.materialId = row.optInt("id", 0);
            item.materialType = materialType;
            item.hasVideo = row.optBoolean("is_video") || "video".equals(materialType);
            item.canDownload = "yes".equalsIgnoreCase(row.optString("permission", "no"));

            String sourceUrl = first(row, "file_path");
            if (!item.hasVideo) {
                item.fileUrl = first(row, "file_url");
                if (item.fileUrl.isEmpty() && !sourceUrl.isEmpty() && !"null".equals(sourceUrl)) {
                    item.fileUrl = UrlHelper.resolveImageUrl(baseUrl, sourceUrl);
                }
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

            if (item.hasVideo && item.materialId > 0) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setText(R.string.play_video);
                View.OnClickListener openVideo = v -> {
                    Intent intent = new Intent(requireContext(), VideoActivity.class);
                    intent.putExtra(VideoActivity.EXTRA_MATERIAL_ID, item.materialId);
                    intent.putExtra(VideoActivity.EXTRA_TITLE, item.title);
                    startActivity(intent);
                };
                holder.action.setOnClickListener(openVideo);
                holder.itemView.setOnClickListener(openVideo);
            } else if (item.fileUrl != null && !item.fileUrl.isEmpty()) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setText(R.string.open_material);
                View.OnClickListener openFile = v -> openMaterial(item);
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

    private void openMaterial(ListItem item) {
        if (isImageMaterial(item)) {
            ImageViewerActivity.open(
                    requireContext(),
                    item.title,
                    item.fileUrl,
                    item.canDownload);
            return;
        }
        Intent intent = new Intent(requireContext(), PdfActivity.class);
        intent.putExtra(PdfActivity.EXTRA_URL, item.fileUrl);
        intent.putExtra(PdfActivity.EXTRA_TITLE, item.title);
        intent.putExtra(PdfActivity.EXTRA_CAN_DOWNLOAD, item.canDownload);
        startActivity(intent);
    }

    private boolean isImageMaterial(ListItem item) {
        if (item.materialType.equals("image")
                || item.materialType.equals("jpg")
                || item.materialType.equals("jpeg")
                || item.materialType.equals("png")
                || item.materialType.equals("gif")) {
            return true;
        }
        String lower = item.fileUrl == null ? "" : item.fileUrl.toLowerCase();
        return lower.endsWith(".jpg") || lower.endsWith(".jpeg")
                || lower.endsWith(".png") || lower.endsWith(".gif")
                || lower.endsWith(".webp");
    }

    private static class ListItem {
        String title = "";
        String subtitle = "";
        int materialId = 0;
        boolean hasVideo = false;
        boolean canDownload = false;
        String materialType = "";
        String fileUrl = "";
        JSONObject raw;
    }
}
