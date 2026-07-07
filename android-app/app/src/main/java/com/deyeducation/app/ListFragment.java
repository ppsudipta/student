package com.deyeducation.app;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;
import com.google.android.material.tabs.TabLayout;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Locale;

public class ListFragment extends Fragment {
    public static final String ARG_TYPE = "type";
    public static final String TYPE_MATERIALS = "materials";
    public static final String TYPE_NOTICES = "notices";
    public static final String TYPE_GALLERY = "gallery";
    public static final String TYPE_FEES = "fees";
    public static final String TYPE_HOMEWORK = "homework";
    public static final String TYPE_ENQUIRIES = "enquiries";
    public static final String TYPE_COURSES = "courses";
    public static final String TYPE_ATTENDANCE = "attendance";

    private static final String MATERIAL_CAT_ALL = "all";
    private static final String MATERIAL_CAT_VIDEO = "video";
    private static final String MATERIAL_CAT_WORKSHEET = "worksheet";
    private static final String MATERIAL_CAT_QUESTION = "question_paper";
    private static final String MATERIAL_CAT_RPS = "rps";
    private static final String MATERIAL_CAT_OTHERS = "others";

    private static final int NOTICE_FILTER_ALL = 0;
    private static final int NOTICE_FILTER_UNREAD = 1;

    private static final int ITEM_DEFAULT = 0;
    private static final int ITEM_NOTICE = 1;

    private ApiClient api;
    private SessionManager session;
    private String type;
    private SwipeRefreshLayout swipeRefresh;
    private ProgressBar progressBar;
    private LinearLayout emptyView;
    private TextView emptyText;
    private TabLayout materialTabs;
    private ListAdapter adapter;
    private final List<ListItem> allMaterialItems = new ArrayList<>();
    private final List<ListItem> allNoticeItems = new ArrayList<>();
    private String materialCategoryFilter = MATERIAL_CAT_ALL;
    private int noticeFilter = NOTICE_FILTER_ALL;
    private ActivityResultLauncher<Intent> noticeLauncher;
    private ActivityResultLauncher<Intent> enquiryLauncher;

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        noticeLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == Activity.RESULT_OK && isAdded()) {
                        loadData();
                        if (requireActivity() instanceof MainActivity) {
                            ((MainActivity) requireActivity()).refreshUnreadNoticesBadge();
                        }
                    }
                });
        enquiryLauncher = registerForActivityResult(
                new ActivityResultContracts.StartActivityForResult(),
                result -> {
                    if (result.getResultCode() == Activity.RESULT_OK && isAdded()) {
                        loadData();
                    }
                });
    }

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
        emptyText = view.findViewById(R.id.emptyText);
        materialTabs = view.findViewById(R.id.materialTabs);
        RecyclerView recyclerView = view.findViewById(R.id.recyclerView);
        adapter = new ListAdapter();
        recyclerView.setLayoutManager(new LinearLayoutManager(requireContext()));
        recyclerView.setHasFixedSize(false);
        recyclerView.setAdapter(adapter);

        if (TYPE_MATERIALS.equals(type)) {
            setupMaterialTabs();
        } else if (TYPE_NOTICES.equals(type)) {
            setupNoticeTabs();
        }

        View fab = view.findViewById(R.id.fabAdd);
        if (TYPE_ENQUIRIES.equals(type) && fab != null) {
            fab.setVisibility(View.VISIBLE);
            fab.setOnClickListener(v -> enquiryLauncher.launch(
                    new Intent(requireContext(), CreateEnquiryActivity.class)));
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
        materialTabs.setTabMode(TabLayout.MODE_SCROLLABLE);
        materialTabs.setTabGravity(TabLayout.GRAVITY_START);
        addMaterialTab(R.string.materials_tab_all, MATERIAL_CAT_ALL, R.drawable.ic_tab_all);
        addMaterialTab(R.string.materials_tab_videos, MATERIAL_CAT_VIDEO, R.drawable.ic_tab_video);
        addMaterialTab(R.string.materials_tab_worksheet, MATERIAL_CAT_WORKSHEET, R.drawable.ic_tab_document);
        addMaterialTab(R.string.materials_tab_question_papers, MATERIAL_CAT_QUESTION, R.drawable.ic_tab_document);
        addMaterialTab(R.string.materials_tab_rps, MATERIAL_CAT_RPS, R.drawable.ic_tab_document);
        addMaterialTab(R.string.materials_tab_others, MATERIAL_CAT_OTHERS, R.drawable.ic_tab_document);
        materialTabs.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                Object tag = tab.getTag();
                materialCategoryFilter = tag == null ? MATERIAL_CAT_ALL : tag.toString();
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

    private void addMaterialTab(int labelRes, String tag, int iconRes) {
        TabLayout.Tab tab = materialTabs.newTab()
                .setText(labelRes)
                .setIcon(iconRes);
        tab.setTag(tag);
        materialTabs.addTab(tab);
    }

    private void setupNoticeTabs() {
        materialTabs.setVisibility(View.VISIBLE);
        materialTabs.removeAllTabs();
        materialTabs.addTab(materialTabs.newTab()
                .setText(R.string.notices_tab_all)
                .setIcon(R.drawable.ic_nav_notices));
        materialTabs.addTab(materialTabs.newTab()
                .setText(R.string.notices_tab_unread)
                .setIcon(R.drawable.ic_bell));
        materialTabs.addOnTabSelectedListener(new TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(TabLayout.Tab tab) {
                noticeFilter = tab.getPosition();
                applyNoticeFilter();
            }

            @Override
            public void onTabUnselected(TabLayout.Tab tab) {
            }

            @Override
            public void onTabReselected(TabLayout.Tab tab) {
            }
        });
    }

    private void applyNoticeFilter() {
        List<ListItem> filtered = new ArrayList<>();
        for (ListItem item : allNoticeItems) {
            if (noticeFilter == NOTICE_FILTER_UNREAD && item.seen) {
                continue;
            }
            filtered.add(item);
        }
        adapter.setItems(filtered);
        emptyView.setVisibility(filtered.isEmpty() ? View.VISIBLE : View.GONE);
        emptyText.setText(noticeFilter == NOTICE_FILTER_UNREAD
                ? getString(R.string.notice_no_unread)
                : getString(R.string.no_records));
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
        String category = normalizeCategory(item.materialCategory);
        switch (materialCategoryFilter) {
            case MATERIAL_CAT_VIDEO:
                return item.hasVideo;
            case MATERIAL_CAT_WORKSHEET:
                return "worksheet".equals(category);
            case MATERIAL_CAT_QUESTION:
                return "question paper".equals(category) || category.contains("question");
            case MATERIAL_CAT_RPS:
                return "rps".equals(category);
            case MATERIAL_CAT_OTHERS:
                if (item.hasVideo) {
                    return false;
                }
                return !"worksheet".equals(category)
                        && !"question paper".equals(category)
                        && !category.contains("question")
                        && !"rps".equals(category);
            default:
                return true;
        }
    }

    private String normalizeCategory(String value) {
        if (value == null) {
            return "";
        }
        return value.trim().toLowerCase(Locale.ROOT);
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
                    } else if (TYPE_NOTICES.equals(type)) {
                        allNoticeItems.clear();
                        allNoticeItems.addAll(items);
                        applyNoticeFilter();
                        if (requireActivity() instanceof MainActivity) {
                            ((MainActivity) requireActivity()).updateNoticesBadge(
                                    json.optInt("unread_count", countUnread(items)));
                        }
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
                return "/events?per_page=30";
            case TYPE_ATTENDANCE:
                return "/attendance?per_page=31";
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
        } else if (TYPE_NOTICES.equals(type)) {
            appendNoticeRows(items, rows);
        } else if (TYPE_ENQUIRIES.equals(type)) {
            appendEnquiryRows(items, rows);
        } else if (TYPE_ATTENDANCE.equals(type)) {
            appendAttendanceRows(items, rows);
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
            item.eventId = row.optInt("id", 0);
            item.raw = row;
            items.add(item);
        }
    }

    private void appendEnquiryRows(List<ListItem> items, JSONArray rows) {
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) {
                continue;
            }
            ListItem item = new ListItem();
            item.enquiryId = row.optInt("id", 0);
            item.title = row.optString("subject", "Enquiry");
            String typeLabel = row.optString("enquiry_type", "");
            if (!typeLabel.isEmpty()) {
                typeLabel = typeLabel.substring(0, 1).toUpperCase() + typeLabel.substring(1);
            }
            boolean replied = row.optBoolean("has_admin_reply", false) || !row.optString("reply_message").isEmpty();
            item.subtitle = typeLabel + " · " + (replied ? getString(R.string.notice_read) : getString(R.string.pending_reply));
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
            item.materialCategory = row.optString("material_category", "");
            item.subtitle = UrlHelper.cleanHtml(first(row, "material_description", "description", "subject"));
            if (item.subtitle.isEmpty() && !item.materialCategory.isEmpty()) {
                item.subtitle = item.materialCategory;
            }
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

    private void appendAttendanceRows(List<ListItem> items, JSONArray rows) {
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) {
                continue;
            }
            ListItem item = new ListItem();
            String status = row.optString("status", "");
            String date = row.optString("attendance_date", "");
            String title = row.optString("attendance_title", "");
            if (title.isEmpty() || "null".equals(title)) {
                title = row.optString("class_name", getString(R.string.attendance));
            }
            item.title = title + " · " + status;
            StringBuilder subtitle = new StringBuilder();
            if (!date.isEmpty() && !"null".equals(date)) {
                subtitle.append(date);
            }
            String day = row.optString("day_name", "");
            if (!day.isEmpty() && !"null".equals(day)) {
                if (subtitle.length() > 0) {
                    subtitle.append(" · ");
                }
                subtitle.append(day);
            }
            String className = row.optString("class_name", "");
            if (!className.isEmpty() && !"null".equals(className)) {
                if (subtitle.length() > 0) {
                    subtitle.append(" · ");
                }
                subtitle.append(className);
            }
            item.subtitle = subtitle.toString();
            item.raw = row;
            items.add(item);
        }
    }

    private void appendNoticeRows(List<ListItem> items, JSONArray rows) {
        String baseUrl = session.getBaseUrl();
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) {
                continue;
            }
            ListItem item = new ListItem();
            item.noticeId = row.optInt("id", 0);
            item.seen = row.optBoolean("seen", false) || row.optInt("seen", 0) == 1;
            item.noticeType = row.optString("notice_type", "text").toLowerCase();
            item.noticeContent = row.optString("notice_content", "");
            item.mediaUrl = first(row, "media_url");
            if (item.mediaUrl.isEmpty() && !item.noticeContent.isEmpty()
                    && ("image".equals(item.noticeType) || "video".equals(item.noticeType))) {
                item.mediaUrl = UrlHelper.resolveImageUrl(baseUrl, item.noticeContent);
            }
            item.title = noticePreviewTitle(item);
            item.subtitle = formatNoticeDate(row.optString("created_at", ""));
            item.raw = row;
            items.add(item);
        }
    }

    private String noticePreviewTitle(ListItem item) {
        if ("image".equals(item.noticeType)) {
            return getString(R.string.notice_preview_image);
        }
        if ("video".equals(item.noticeType)) {
            return getString(R.string.notice_preview_video);
        }
        String text = UrlHelper.cleanHtml(item.noticeContent);
        if (text.length() > 120) {
            return text.substring(0, 117) + "...";
        }
        return text.isEmpty() ? getString(R.string.notice) : text;
    }

    private String formatNoticeDate(String value) {
        if (value == null || value.isEmpty() || "null".equals(value)) {
            return "";
        }
        return value.replace('T', ' ').replaceAll(":00$", "");
    }

    private int countUnread(List<ListItem> items) {
        int count = 0;
        for (ListItem item : items) {
            if (!item.seen) {
                count++;
            }
        }
        return count;
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

    private class ListAdapter extends RecyclerView.Adapter<RecyclerView.ViewHolder> {
        private final List<ListItem> items = new ArrayList<>();

        void setItems(List<ListItem> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @Override
        public int getItemViewType(int position) {
            return TYPE_NOTICES.equals(type) ? ITEM_NOTICE : ITEM_DEFAULT;
        }

        @NonNull
        @Override
        public RecyclerView.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            LayoutInflater inflater = LayoutInflater.from(parent.getContext());
            if (viewType == ITEM_NOTICE) {
                return new NoticeHolder(inflater.inflate(R.layout.item_notice_card, parent, false));
            }
            return new DefaultHolder(inflater.inflate(R.layout.item_list_card, parent, false));
        }

        @Override
        public void onBindViewHolder(@NonNull RecyclerView.ViewHolder holder, int position) {
            ListItem item = items.get(position);
            if (holder instanceof NoticeHolder) {
                bindNotice((NoticeHolder) holder, item);
                return;
            }
            bindDefault((DefaultHolder) holder, item);
        }

        private void bindNotice(NoticeHolder holder, ListItem item) {
            holder.title.setText(item.title);
            holder.subtitle.setText(item.subtitle);
            holder.unreadDot.setVisibility(item.seen ? View.GONE : View.VISIBLE);
            holder.status.setText(item.seen ? R.string.notice_read : R.string.notice_unread);
            holder.status.setTextColor(ContextCompat.getColor(
                    requireContext(),
                    item.seen ? R.color.secondary_text : R.color.primary));
            holder.card.setCardBackgroundColor(ContextCompat.getColor(
                    requireContext(),
                    item.seen ? R.color.surface : R.color.primary_light));

            int iconRes = R.drawable.ic_bell;
            if ("image".equals(item.noticeType)) {
                iconRes = R.drawable.ic_material_image;
            } else if ("video".equals(item.noticeType)) {
                iconRes = R.drawable.ic_material_video;
            }
            holder.icon.setImageResource(iconRes);

            View.OnClickListener open = v -> openNotice(item);
            holder.itemView.setOnClickListener(open);
        }

        private void bindDefault(DefaultHolder holder, ListItem item) {
            holder.title.setText(item.title.isEmpty() ? "Item" : item.title);
            holder.subtitle.setText(item.subtitle);
            holder.action.setOnClickListener(null);
            holder.itemView.setOnClickListener(null);

            if (TYPE_MATERIALS.equals(type)) {
                holder.iconFrame.setVisibility(View.VISIBLE);
                holder.itemIcon.setImageResource(iconForMaterial(item));
            } else {
                holder.iconFrame.setVisibility(View.GONE);
            }

            if (item.hasVideo && item.materialId > 0) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setIconResource(R.drawable.ic_play_small);
                holder.action.setContentDescription(getString(R.string.play_video));
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
                holder.action.setIconResource(isImageMaterial(item)
                        ? R.drawable.ic_material_image
                        : R.drawable.ic_read_small);
                holder.action.setContentDescription(getString(R.string.open_material));
                View.OnClickListener openFile = v -> openMaterial(item);
                holder.action.setOnClickListener(openFile);
                holder.itemView.setOnClickListener(openFile);
            } else if (TYPE_ENQUIRIES.equals(type) && item.enquiryId > 0) {
                holder.action.setVisibility(View.GONE);
                View.OnClickListener openEnquiry = v -> openEnquiry(item);
                holder.itemView.setOnClickListener(openEnquiry);
            } else if (TYPE_COURSES.equals(type) && item.eventId > 0) {
                holder.action.setVisibility(View.VISIBLE);
                holder.action.setIconResource(R.drawable.ic_chevron_right);
                holder.action.setContentDescription(getString(R.string.view_course));
                View.OnClickListener openCourse = v -> {
                    if (getActivity() instanceof MainActivity) {
                        ((MainActivity) getActivity()).showFragment(
                                CourseDetailFragment.newInstance(item.eventId, item.title),
                                item.title,
                                true);
                    }
                };
                holder.action.setOnClickListener(openCourse);
                holder.itemView.setOnClickListener(openCourse);
            } else {
                holder.action.setVisibility(View.GONE);
            }
        }

        private int iconForMaterial(ListItem item) {
            if (item.hasVideo) {
                return R.drawable.ic_material_video;
            }
            if (isImageMaterial(item)) {
                return R.drawable.ic_material_image;
            }
            return R.drawable.ic_material_pdf;
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        class DefaultHolder extends RecyclerView.ViewHolder {
            final View iconFrame;
            final ImageView itemIcon;
            final TextView title;
            final TextView subtitle;
            final MaterialButton action;

            DefaultHolder(@NonNull View itemView) {
                super(itemView);
                iconFrame = itemView.findViewById(R.id.itemIconFrame);
                itemIcon = itemView.findViewById(R.id.itemIcon);
                title = itemView.findViewById(R.id.itemTitle);
                subtitle = itemView.findViewById(R.id.itemSubtitle);
                action = itemView.findViewById(R.id.btnAction);
            }
        }

        class NoticeHolder extends RecyclerView.ViewHolder {
            final MaterialCardView card;
            final ImageView icon;
            final TextView title;
            final TextView subtitle;
            final TextView status;
            final View unreadDot;

            NoticeHolder(@NonNull View itemView) {
                super(itemView);
                card = itemView.findViewById(R.id.noticeCard);
                icon = itemView.findViewById(R.id.noticeIcon);
                title = itemView.findViewById(R.id.noticeTitle);
                subtitle = itemView.findViewById(R.id.noticeSubtitle);
                status = itemView.findViewById(R.id.noticeStatus);
                unreadDot = itemView.findViewById(R.id.noticeUnreadDot);
            }
        }
    }

    private void openNotice(ListItem item) {
        Intent intent = new Intent(requireContext(), NoticeDetailActivity.class);
        intent.putExtra(NoticeDetailActivity.EXTRA_ID, item.noticeId);
        intent.putExtra(NoticeDetailActivity.EXTRA_TYPE, item.noticeType);
        intent.putExtra(NoticeDetailActivity.EXTRA_CONTENT, item.noticeContent);
        intent.putExtra(NoticeDetailActivity.EXTRA_MEDIA_URL, item.mediaUrl);
        intent.putExtra(NoticeDetailActivity.EXTRA_DATE, item.subtitle);
        intent.putExtra(NoticeDetailActivity.EXTRA_SEEN, item.seen);
        noticeLauncher.launch(intent);
    }

    private void openEnquiry(ListItem item) {
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).showFragment(
                    EnquiryDetailFragment.newInstance(item.enquiryId, item.title),
                    item.title,
                    true);
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
        int noticeId = 0;
        int enquiryId = 0;
        int eventId = 0;
        boolean hasVideo = false;
        boolean canDownload = false;
        boolean seen = true;
        String materialType = "";
        String materialCategory = "";
        String noticeType = "";
        String noticeContent = "";
        String mediaUrl = "";
        String fileUrl = "";
        JSONObject raw;
    }
}
