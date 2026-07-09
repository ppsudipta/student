package com.deyeducation.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class AboutContentFragment extends Fragment {
    public static final String ARG_MODE = "mode";
    public static final String MODE_ABOUT = "about";
    public static final String MODE_ACADEMY = "academy";

    private ApiClient api;
    private String mapUrl;
    private View progress;
    private TextView titleView;
    private TextView descriptionView;
    private TextView phoneView;
    private TextView emailView;
    private TextView addressView;
    private ImageView heroView;
    private MaterialCardView contactCard;
    private MaterialButton mapsButton;
    private TextView teachersSectionTitle;
    private RecyclerView teachersGrid;
    private TeacherAdapter teacherAdapter;

    public static AboutContentFragment newInstance(String mode) {
        Bundle args = new Bundle();
        args.putString(ARG_MODE, mode);
        AboutContentFragment fragment = new AboutContentFragment();
        fragment.setArguments(args);
        return fragment;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_about_content, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        api = ((MainActivity) requireActivity()).getApi();

        progress = view.findViewById(R.id.aboutProgress);
        heroView = view.findViewById(R.id.aboutHero);
        titleView = view.findViewById(R.id.aboutTitle);
        descriptionView = view.findViewById(R.id.aboutDescription);
        phoneView = view.findViewById(R.id.aboutPhone);
        emailView = view.findViewById(R.id.aboutEmail);
        addressView = view.findViewById(R.id.aboutAddress);
        contactCard = view.findViewById(R.id.contactCard);
        mapsButton = view.findViewById(R.id.btnOpenMaps);
        teachersSectionTitle = view.findViewById(R.id.teachersSectionTitle);
        teachersGrid = view.findViewById(R.id.teachersGrid);
        teacherAdapter = new TeacherAdapter();
        teachersGrid.setLayoutManager(new GridLayoutManager(requireContext(), 2));
        teachersGrid.setNestedScrollingEnabled(false);
        teachersGrid.setAdapter(teacherAdapter);

        mapsButton.setOnClickListener(v -> {
            if (mapUrl != null && !mapUrl.isEmpty()) {
                startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse(mapUrl)));
            }
        });

        loadAbout();
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            String mode = getMode();
            int titleRes = MODE_ACADEMY.equals(mode)
                    ? R.string.academy_details
                    : R.string.about_us_title;
            ((MainActivity) getActivity()).setScreenTitle(getString(titleRes));
        }
    }

    private String getMode() {
        Bundle args = getArguments();
        if (args == null) {
            return MODE_ABOUT;
        }
        return args.getString(ARG_MODE, MODE_ABOUT);
    }

    private void loadAbout() {
        UiUtils.setLoaderVisible(progress, true);
        api.get("/about", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data != null) {
                        bindAbout(data);
                    } else {
                        loadCompanyFallback();
                    }
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> loadCompanyFallback());
            }
        });
    }

    private void loadCompanyFallback() {
        api.get("/company", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject company = json.optJSONObject("data");
                    if (company != null) {
                        bindCompany(company, null);
                    }
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void bindAbout(JSONObject data) {
        bindCompany(data.optJSONObject("company"), data.optJSONObject("about"));
    }

    private void bindCompany(JSONObject company, JSONObject about) {
        String mode = getMode();
        SessionManager session = ((MainActivity) requireActivity()).getSession();
        String baseUrl = session.getBaseUrl();

        if (MODE_ACADEMY.equals(mode)) {
            if (company != null) {
                titleView.setText(company.optString("name", getString(R.string.academy_details)));
                String details = about != null ? about.optString("details", "") : "";
                if (details.isEmpty() || "null".equals(details)) {
                    details = firstNonEmpty(
                            company.optString("about"),
                            company.optString("description"),
                            company.optString("details"));
                }
                if (details.isEmpty() || "null".equals(details)) {
                    descriptionView.setText(R.string.academy_details_hint);
                } else {
                    UiUtils.bindHtml(descriptionView, details);
                }
                UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(baseUrl, company), heroView, 0);
            } else if (about != null) {
                titleView.setText(about.optString("title", getString(R.string.academy_details)));
                UiUtils.bindHtml(descriptionView, about.optString("details", ""));
                UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(baseUrl, about), heroView, 0);
            }
            contactCard.setVisibility(View.GONE);
            loadTeachers();
            return;
        }

        if (about != null) {
            titleView.setText(about.optString("title", getString(R.string.about_us_title)));
            UiUtils.bindHtml(descriptionView, about.optString("details", ""));
            UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(baseUrl, about), heroView, 0);
        } else if (company != null) {
            titleView.setText(company.optString("name", getString(R.string.about_us_title)));
            UiUtils.bindHtml(descriptionView, firstNonEmpty(
                    company.optString("about"),
                    company.optString("description"),
                    company.optString("details")));
            UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(baseUrl, company), heroView, 0);
        }

        if (company == null) {
            contactCard.setVisibility(View.GONE);
            return;
        }

        contactCard.setVisibility(View.VISIBLE);
        phoneView.setText(getString(R.string.phone_label) + ": " + safe(company.optString("ph1")));
        emailView.setText(getString(R.string.email_label) + ": " + safe(company.optString("email")));
        addressView.setText(getString(R.string.address_label) + ": " + safe(company.optString("address")));

        mapUrl = company.optString("map_url");
        if (mapUrl == null || mapUrl.isEmpty()) {
            String addr = company.optString("address");
            if (!addr.isEmpty() && !"null".equals(addr)) {
                mapUrl = "https://www.google.com/maps/search/?api=1&query=" + Uri.encode(addr);
            }
        }
        mapsButton.setVisibility(mapUrl == null || mapUrl.isEmpty() ? View.GONE : View.VISIBLE);
    }

    private void loadTeachers() {
        SessionManager session = ((MainActivity) requireActivity()).getSession();
        api.get("/teachers?per_page=30", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    List<TeacherItem> items = parseTeachers(json, session.getBaseUrl());
                    teacherAdapter.setItems(items);
                    boolean hasTeachers = !items.isEmpty();
                    teachersSectionTitle.setVisibility(hasTeachers ? View.VISIBLE : View.GONE);
                    teachersGrid.setVisibility(hasTeachers ? View.VISIBLE : View.GONE);
                });
            }

            @Override
            public void onError(String message) {
            }
        });
    }

    private List<TeacherItem> parseTeachers(JSONObject json, String baseUrl) {
        List<TeacherItem> items = new ArrayList<>();
        Object data = json.opt("data");
        JSONArray rows = new JSONArray();
        if (data instanceof JSONArray) {
            rows = (JSONArray) data;
        } else if (data instanceof JSONObject) {
            Object inner = ((JSONObject) data).opt("data");
            if (inner instanceof JSONArray) {
                rows = (JSONArray) inner;
            }
        }
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) {
                continue;
            }
            TeacherItem item = new TeacherItem();
            item.name = row.optString("name", "Teacher");
            item.subject = row.optString("subject", "");
            item.imageUrl = UrlHelper.imageFromJson(baseUrl, row);
            items.add(item);
        }
        return items;
    }

    private class TeacherAdapter extends RecyclerView.Adapter<TeacherAdapter.Holder> {
        private final List<TeacherItem> items = new ArrayList<>();

        void setItems(List<TeacherItem> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext())
                    .inflate(R.layout.item_teacher_card, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            TeacherItem item = items.get(position);
            holder.name.setText(item.name);
            holder.subject.setText(item.subject.isEmpty() ? getString(R.string.not_provided) : item.subject);
            UiUtils.loadImage(holder.itemView.getContext(), item.imageUrl, holder.image, 12);
            holder.itemView.setOnClickListener(v -> {
                if (item.imageUrl != null) {
                    ImageViewerActivity.open(requireContext(), item.name, item.imageUrl);
                }
            });
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        class Holder extends RecyclerView.ViewHolder {
            final ImageView image;
            final TextView name;
            final TextView subject;

            Holder(@NonNull View itemView) {
                super(itemView);
                image = itemView.findViewById(R.id.teacherImage);
                name = itemView.findViewById(R.id.teacherName);
                subject = itemView.findViewById(R.id.teacherSubject);
            }
        }
    }

    private static class TeacherItem {
        String name = "";
        String subject = "";
        String imageUrl;
    }

    private String firstNonEmpty(String... values) {
        for (String value : values) {
            if (value != null && !value.isEmpty() && !"null".equals(value)) {
                return value;
            }
        }
        return "";
    }

    private String safe(String value) {
        if (value == null || value.isEmpty() || "null".equals(value)) {
            return "-";
        }
        return value;
    }
}
