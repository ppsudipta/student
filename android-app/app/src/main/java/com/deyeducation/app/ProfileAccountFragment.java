package com.deyeducation.app;

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

import org.json.JSONObject;

public class ProfileAccountFragment extends Fragment {
    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_profile_account, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        LinearLayout container = view.findViewById(R.id.accountDetailsContainer);
        ProgressBar progressBar = view.findViewById(R.id.accountProgress);
        MainActivity activity = (MainActivity) requireActivity();
        ApiClient api = activity.getApi();

        progressBar.setVisibility(View.VISIBLE);
        api.get("/me", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    JSONObject student = json.optJSONObject("student");
                    if (student == null) {
                        return;
                    }
                    container.removeAllViews();
                    addRow(container, getString(R.string.label_name), student.optString("name"));
                    addRow(container, getString(R.string.label_mobile), student.optString("mobile_number"));
                    addRow(container, getString(R.string.label_email), student.optString("email"));
                    addRow(container, getString(R.string.label_address), student.optString("address"));
                    addRow(container, getString(R.string.label_class), student.optString("class"));
                    addRow(container, getString(R.string.label_session), student.optString("session"));
                    addRow(container, getString(R.string.label_course), student.optString("course"));
                    addRow(container, getString(R.string.label_registration), student.optString("registration_code"));
                    addRow(container, getString(R.string.label_total_fees), student.optString("total_fees"));
                    addRow(container, getString(R.string.label_paid_fees), student.optString("paid_fees"));
                    addRow(container, getString(R.string.label_due_fees), student.optString("due_fees"));
                    addRow(container, getString(R.string.label_status), student.optString("status"));
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

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.my_account_details));
        }
    }

    private void addRow(LinearLayout container, String label, String value) {
        View row = LayoutInflater.from(requireContext())
                .inflate(R.layout.item_profile_detail_row, container, false);
        ((TextView) row.findViewById(R.id.detailLabel)).setText(label);
        String text = value == null || value.isEmpty() || "null".equals(value)
                ? getString(R.string.not_provided) : value;
        ((TextView) row.findViewById(R.id.detailValue)).setText(text);
        container.addView(row);
    }
}
