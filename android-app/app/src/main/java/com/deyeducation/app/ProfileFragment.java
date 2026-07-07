package com.deyeducation.app;

import android.content.Intent;
import android.content.ContentResolver;
import android.net.Uri;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.PickVisualMediaRequest;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;

import org.json.JSONObject;

import java.io.InputStream;

public class ProfileFragment extends Fragment {
    private ApiClient api;
    private SessionManager session;
    private ProgressBar progressBar;
    private ImageView profileImage;
    private ActivityResultLauncher<PickVisualMediaRequest> pickImageLauncher;

    @Override
    public void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        pickImageLauncher = registerForActivityResult(
                new ActivityResultContracts.PickVisualMedia(),
                this::uploadProfilePhoto);
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_profile, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        progressBar = view.findViewById(R.id.profileProgress);
        profileImage = view.findViewById(R.id.profileImage);
        MaterialButton logout = view.findViewById(R.id.btnLogout);

        profileImage.setOnClickListener(v -> pickImageLauncher.launch(
                new PickVisualMediaRequest.Builder()
                        .setMediaType(ActivityResultContracts.PickVisualMedia.ImageOnly.INSTANCE)
                        .build()));

        setupMenuRow(view.findViewById(R.id.menuAccountDetails), R.drawable.ic_edit_profile,
                getString(R.string.my_account_details),
                v -> activity.showFragment(new ProfileAccountFragment(),
                        getString(R.string.my_account_details), true));

        view.findViewById(R.id.btnPaymentMethod).setOnClickListener(v ->
                activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_FEES), getString(R.string.fees)));

        setupMenuRow(view.findViewById(R.id.menuChangePassword), R.drawable.ic_lock,
                getString(R.string.change_password),
                v -> activity.showFragment(new ChangePasswordFragment(),
                        getString(R.string.change_password), true));

        setupMenuRow(view.findViewById(R.id.menuNotifications), R.drawable.ic_bell,
                getString(R.string.notifications_menu),
                v -> activity.selectBottomNav(R.id.nav_notices));

        setupMenuRow(view.findViewById(R.id.menuProgressReport), R.drawable.ic_attendance,
                getString(R.string.progress_report),
                v -> activity.showFragment(new AttendanceFragment(),
                        getString(R.string.progress_report), true));

        setupMenuRow(view.findViewById(R.id.menuLegal), R.drawable.ic_shield,
                getString(R.string.legal_policies),
                v -> activity.showFragment(new LegalPoliciesFragment(),
                        getString(R.string.legal_terms_title), true));

        logout.setOnClickListener(v -> {
            session.clear();
            startActivity(new Intent(requireContext(), LoginActivity.class));
            requireActivity().finish();
        });

        loadProfile(view);
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.profile));
        }
    }

    private void setupMenuRow(View row, int iconRes, String label, View.OnClickListener click) {
        ((ImageView) row.findViewById(R.id.menuIcon)).setImageResource(iconRes);
        ((TextView) row.findViewById(R.id.menuLabel)).setText(label);
        row.setOnClickListener(click);
    }

    private void uploadProfilePhoto(Uri uri) {
        if (uri == null || !isAdded()) {
            return;
        }
        progressBar.setVisibility(View.VISIBLE);
        try (InputStream input = requireContext().getContentResolver().openInputStream(uri)) {
            if (input == null) {
                progressBar.setVisibility(View.GONE);
                UiUtils.toast(requireContext(), getString(R.string.network_error));
                return;
            }
            ContentResolver resolver = requireContext().getContentResolver();
            String mimeType = resolver.getType(uri);
            if (mimeType == null || !mimeType.startsWith("image/")) {
                progressBar.setVisibility(View.GONE);
                UiUtils.toast(requireContext(), getString(R.string.network_error));
                return;
            }
            String extension = "jpg";
            if ("image/png".equals(mimeType)) {
                extension = "png";
            } else if ("image/webp".equals(mimeType)) {
                extension = "webp";
            } else if ("image/gif".equals(mimeType)) {
                extension = "gif";
            }
            byte[] bytes = ApiClient.readAllBytes(input);
            api.postMultipart("/me/photo", null, "image", bytes, "profile." + extension, mimeType, true,
                    new ApiClient.Callback() {
                        @Override
                        public void onSuccess(JSONObject json) {
                            if (!isAdded()) {
                                return;
                            }
                            requireActivity().runOnUiThread(() -> {
                                progressBar.setVisibility(View.GONE);
                                UiUtils.toast(requireContext(), getString(R.string.photo_updated));
                                JSONObject student = json.optJSONObject("student");
                                if (student != null) {
                                    UiUtils.loadImage(requireContext(),
                                            UrlHelper.imageFromJson(session.getBaseUrl(), student),
                                            profileImage, 36);
                                    if (requireActivity() instanceof MainActivity) {
                                        ((MainActivity) requireActivity()).refreshNavProfileImage(student);
                                    }
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
                                UiUtils.toast(requireContext(), message);
                            });
                        }
                    });
        } catch (Exception e) {
            progressBar.setVisibility(View.GONE);
            UiUtils.toast(requireContext(), e.getMessage());
        }
    }

    private void loadProfile(View root) {
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

                    TextView name = root.findViewById(R.id.profileName);
                    TextView location = root.findViewById(R.id.profileLocation);

                    name.setText(student.optString("name"));
                    String address = student.optString("address");
                    location.setText(address.isEmpty() || "null".equals(address)
                            ? student.optString("class") : address);
                    UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(session.getBaseUrl(), student), profileImage, 36);
                    session.setStudentName(student.optString("name"));
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
}
