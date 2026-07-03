package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ProgressBar;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONObject;

public class ChangePasswordFragment extends Fragment {
    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_change_password, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        TextInputEditText currentInput = view.findViewById(R.id.inputCurrentPassword);
        TextInputEditText newInput = view.findViewById(R.id.inputNewPassword);
        TextInputEditText confirmInput = view.findViewById(R.id.inputConfirmPassword);
        MaterialButton saveButton = view.findViewById(R.id.btnSavePassword);
        ProgressBar progressBar = view.findViewById(R.id.passwordProgress);
        ApiClient api = ((MainActivity) requireActivity()).getApi();

        saveButton.setOnClickListener(v -> {
            String current = textOf(currentInput);
            String next = textOf(newInput);
            String confirm = textOf(confirmInput);
            if (current.isEmpty() || next.isEmpty() || confirm.isEmpty()) {
                UiUtils.toast(requireContext(), getString(R.string.fill_all_fields));
                return;
            }
            if (!next.equals(confirm)) {
                UiUtils.toast(requireContext(), getString(R.string.password_mismatch));
                return;
            }
            progressBar.setVisibility(View.VISIBLE);
            saveButton.setEnabled(false);
            try {
                JSONObject body = new JSONObject();
                body.put("current_password", current);
                body.put("new_password", next);
                api.post("/change-password", body, true, new ApiClient.Callback() {
                    @Override
                    public void onSuccess(JSONObject json) {
                        if (!isAdded()) {
                            return;
                        }
                        requireActivity().runOnUiThread(() -> {
                            progressBar.setVisibility(View.GONE);
                            saveButton.setEnabled(true);
                            UiUtils.toast(requireContext(), getString(R.string.password_changed));
                            currentInput.setText("");
                            newInput.setText("");
                            confirmInput.setText("");
                            if (getActivity() instanceof MainActivity) {
                                ((MainActivity) getActivity()).popBackStackIfPossible();
                            }
                        });
                    }

                    @Override
                    public void onError(String message) {
                        if (!isAdded()) {
                            return;
                        }
                        requireActivity().runOnUiThread(() -> {
                            progressBar.setVisibility(View.GONE);
                            saveButton.setEnabled(true);
                            UiUtils.toast(requireContext(), message);
                        });
                    }
                });
            } catch (Exception e) {
                progressBar.setVisibility(View.GONE);
                saveButton.setEnabled(true);
                UiUtils.toast(requireContext(), e.getMessage());
            }
        });
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.change_password));
        }
    }

    private String textOf(TextInputEditText input) {
        return input.getText() != null ? input.getText().toString().trim() : "";
    }
}
