package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class GalleryFragment extends Fragment {
    private ApiClient api;
    private SessionManager session;
    private SwipeRefreshLayout swipeRefresh;
    private ProgressBar progressBar;
    private TextView emptyView;
    private GalleryAdapter adapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_gallery, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        progressBar = view.findViewById(R.id.galleryProgress);
        emptyView = view.findViewById(R.id.galleryEmpty);
        RecyclerView grid = view.findViewById(R.id.galleryGrid);
        adapter = new GalleryAdapter();
        grid.setLayoutManager(new GridLayoutManager(requireContext(), 2));
        grid.setHasFixedSize(false);
        grid.setAdapter(adapter);

        swipeRefresh.setColorSchemeResources(R.color.primary);
        swipeRefresh.setOnRefreshListener(this::loadData);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) -> grid.canScrollVertically(-1));
        loadData();
    }

    private void loadData() {
        if (!swipeRefresh.isRefreshing()) {
            progressBar.setVisibility(View.VISIBLE);
        }
        emptyView.setVisibility(View.GONE);

        api.get("/gallery?per_page=60", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    swipeRefresh.setRefreshing(false);
                    progressBar.setVisibility(View.GONE);
                    List<GalleryItem> items = parseGallery(json);
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

    private List<GalleryItem> parseGallery(JSONObject json) {
        List<GalleryItem> items = new ArrayList<>();
        String baseUrl = session.getBaseUrl();
        JSONArray rows = extractRows(json);
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            GalleryItem item = new GalleryItem();
            item.title = row.optString("name", row.optString("title", "Gallery"));
            item.imageUrl = UrlHelper.imageFromJson(baseUrl, row);
            items.add(item);
        }
        return items;
    }

    private JSONArray extractRows(JSONObject json) {
        Object data = json.opt("data");
        if (data instanceof JSONArray) {
            return (JSONArray) data;
        }
        if (data instanceof JSONObject) {
            Object inner = ((JSONObject) data).opt("data");
            if (inner instanceof JSONArray) {
                return (JSONArray) inner;
            }
        }
        return new JSONArray();
    }

    private class GalleryAdapter extends RecyclerView.Adapter<GalleryAdapter.Holder> {
        private final List<GalleryItem> items = new ArrayList<>();

        void setItems(List<GalleryItem> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_gallery_card, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            GalleryItem item = items.get(position);
            holder.title.setText(item.title.isEmpty() ? getString(R.string.gallery) : item.title);
            UiUtils.loadImage(holder.itemView.getContext(), item.imageUrl, holder.image, 12);
            holder.itemView.setOnClickListener(v ->
                    ImageViewerActivity.open(requireContext(), item.title, item.imageUrl));
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        class Holder extends RecyclerView.ViewHolder {
            final ImageView image;
            final TextView title;

            Holder(@NonNull View itemView) {
                super(itemView);
                image = itemView.findViewById(R.id.galleryImage);
                title = itemView.findViewById(R.id.galleryTitle);
            }
        }
    }

    private static class GalleryItem {
        String title = "";
        String imageUrl;
    }
}
