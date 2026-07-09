package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import org.json.JSONArray;
import org.json.JSONObject;

public class LegalPoliciesFragment extends Fragment {
    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_legal_policies, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        LinearLayout container = view.findViewById(R.id.legalContainer);
        View progress = view.findViewById(R.id.legalProgress);
        ApiClient api = ((MainActivity) requireActivity()).getApi();

        UiUtils.setLoaderVisible(progress, true);
        api.get("/legal-policies", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    bindSections(container, json.optJSONObject("data"));
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

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.legal_terms_title));
        }
    }

    private void bindSections(LinearLayout container, JSONObject data) {
        container.removeAllViews();
        if (data == null) {
            return;
        }
        JSONArray sections = data.optJSONArray("sections");
        if (sections == null) {
            return;
        }
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < sections.length(); i++) {
            JSONObject section = sections.optJSONObject(i);
            if (section == null) {
                continue;
            }
            View card = inflater.inflate(R.layout.item_legal_section, container, false);
            TextView title = card.findViewById(R.id.legalSectionTitle);
            TextView body = card.findViewById(R.id.legalSectionBody);
            title.setText(section.optString("title"));

            StringBuilder text = new StringBuilder();
            JSONArray items = section.optJSONArray("items");
            if (items != null) {
                for (int j = 0; j < items.length(); j++) {
                    if (text.length() > 0) {
                        text.append("\n\n");
                    }
                    text.append("• ").append(items.optString(j));
                }
            }
            body.setText(text.toString());
            container.addView(card);
        }
    }
}
