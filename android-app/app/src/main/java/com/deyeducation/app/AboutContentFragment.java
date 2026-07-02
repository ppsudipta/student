package com.deyeducation.app;

import android.content.Intent;
import android.net.Uri;
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

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import org.json.JSONObject;

public class AboutContentFragment extends Fragment {
    public static final String ARG_MODE = "mode";
    public static final String MODE_ABOUT = "about";
    public static final String MODE_ACADEMY = "academy";

    private ApiClient api;
    private String mapUrl;
    private ProgressBar progress;
    private TextView titleView;
    private TextView descriptionView;
    private TextView phoneView;
    private TextView emailView;
    private TextView addressView;
    private ImageView heroView;
    private MaterialCardView contactCard;
    private MaterialButton mapsButton;

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
        progress.setVisibility(View.VISIBLE);
        api.get("/about", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progress.setVisibility(View.GONE);
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
                    progress.setVisibility(View.GONE);
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
                    progress.setVisibility(View.GONE);
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
                UiUtils.bindHtml(descriptionView, details);
                String heroUrl = UrlHelper.imageFromJson(baseUrl, company);
                UiUtils.loadImage(requireContext(), heroUrl, heroView, 0);
            } else if (about != null) {
                titleView.setText(about.optString("title", getString(R.string.academy_details)));
                UiUtils.bindHtml(descriptionView, about.optString("details", ""));
                UiUtils.loadImage(requireContext(), about.optString("image_url"), heroView, 0);
            }
            contactCard.setVisibility(View.GONE);
            return;
        }

        if (about != null) {
            titleView.setText(about.optString("title", getString(R.string.about_us_title)));
            UiUtils.bindHtml(descriptionView, about.optString("details", ""));
            UiUtils.loadImage(requireContext(), about.optString("image_url"), heroView, 0);
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
