package com.deyeducation.app;

import android.content.Intent;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import org.json.JSONObject;

import java.util.Iterator;

public class ProfileFragment extends Fragment {
    private ApiClient api;
    private SessionManager session;
    private ProgressBar progressBar;
    private LinearLayout detailsContainer;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_profile, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        progressBar = view.findViewById(R.id.profileProgress);
        detailsContainer = view.findViewById(R.id.profileDetails);
        MaterialButton logout = view.findViewById(R.id.btnLogout);

        logout.setOnClickListener(v -> {
            session.clear();
            startActivity(new Intent(requireContext(), LoginActivity.class));
            requireActivity().finish();
        });

        loadProfile(view);
    }

    private void loadProfile(View root) {
        progressBar.setVisibility(View.VISIBLE);
        api.get("/me", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    JSONObject student = json.optJSONObject("student");
                    if (student == null) return;

                    TextView name = root.findViewById(R.id.profileName);
                    TextView classInfo = root.findViewById(R.id.profileClass);
                    ImageView image = root.findViewById(R.id.profileImage);

                    name.setText(student.optString("name"));
                    classInfo.setText(student.optString("class") + " · " + student.optString("session"));
                    UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(session.getBaseUrl(), student), image, 36);

                    detailsContainer.removeAllViews();
                    addDetailRow("Mobile", student.optString("mobile_number"));
                    addDetailRow("Email", student.optString("email"));
                    addDetailRow("Address", student.optString("address"));
                    addDetailRow("Course", student.optString("course"));
                    addDetailRow("Registration", student.optString("registration_code"));
                    addDetailRow("Total Fees", student.optString("total_fees"));
                    addDetailRow("Paid Fees", student.optString("paid_fees"));
                    addDetailRow("Due Fees", student.optString("due_fees"));
                    addDetailRow("Status", student.optString("status"));
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void addDetailRow(String label, String value) {
        MaterialCardView card = new MaterialCardView(requireContext());
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT);
        lp.bottomMargin = UiUtils.dp(requireContext(), 10);
        card.setLayoutParams(lp);
        card.setCardBackgroundColor(getResources().getColor(R.color.surface, null));
        card.setRadius(UiUtils.dp(requireContext(), 12));
        card.setStrokeColor(getResources().getColor(R.color.border, null));
        card.setStrokeWidth(1);
        card.setCardElevation(0f);

        LinearLayout inner = new LinearLayout(requireContext());
        inner.setOrientation(LinearLayout.VERTICAL);
        int pad = UiUtils.dp(requireContext(), 14);
        inner.setPadding(pad, pad, pad, pad);

        TextView labelView = new TextView(requireContext());
        labelView.setText(label);
        labelView.setTextColor(getResources().getColor(R.color.secondary_text, null));
        labelView.setTextSize(12f);

        TextView valueView = new TextView(requireContext());
        valueView.setText(value == null || value.isEmpty() ? "Not provided" : value);
        valueView.setTextColor(getResources().getColor(R.color.primary_text, null));
        valueView.setTextSize(14f);

        inner.addView(labelView);
        inner.addView(valueView);
        card.addView(inner);
        detailsContainer.addView(card);
    }
}
