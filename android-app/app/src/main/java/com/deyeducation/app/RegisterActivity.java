package com.deyeducation.app;

import android.content.ContentResolver;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.CheckBox;
import android.widget.LinearLayout;
import android.widget.Spinner;
import android.widget.TextView;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.PickVisualMediaRequest;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.datepicker.MaterialDatePicker;
import com.google.android.material.dialog.MaterialAlertDialogBuilder;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.InputStream;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.TimeZone;

public class RegisterActivity extends AppCompatActivity {
    public static final String EXTRA_BASE_URL = "base_url";

    private SessionManager session;
    private ApiClient api;
    private View registerProgress;
    private View classesProgress;
    private MaterialButton registerButton;
    private LinearLayout classCheckboxContainer;
    private Spinner sessionSpinner;
    private TextView photoNameView;
    private TextInputEditText dobInput;

    private byte[] photoBytes;
    private String photoFileName;
    private String photoMimeType;
    private String selectedDob;

    private ActivityResultLauncher<PickVisualMediaRequest> pickPhotoLauncher;

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        session = new SessionManager(this);
        api = new ApiClient(session);

        String baseUrl = getIntent().getStringExtra(EXTRA_BASE_URL);
        if (baseUrl != null && !baseUrl.trim().isEmpty()) {
            session.setBaseUrl(baseUrl.trim());
        } else if (session.getBaseUrl().isEmpty()) {
            session.setBaseUrl(AppConfig.DEFAULT_BASE_URL);
        }

        pickPhotoLauncher = registerForActivityResult(
                new ActivityResultContracts.PickVisualMedia(),
                this::onPhotoSelected);

        registerProgress = findViewById(R.id.registerProgress);
        classesProgress = findViewById(R.id.classesProgress);
        registerButton = findViewById(R.id.btnRegister);
        classCheckboxContainer = findViewById(R.id.classCheckboxContainer);
        sessionSpinner = findViewById(R.id.sessionSpinner);
        photoNameView = findViewById(R.id.tvPhotoName);
        dobInput = findViewById(R.id.inputDob);

        TextInputEditText name = findViewById(R.id.inputName);
        TextInputEditText mobile = findViewById(R.id.inputMobile);
        TextInputEditText password = findViewById(R.id.inputPassword);
        TextInputEditText email = findViewById(R.id.inputEmail);
        TextInputEditText address = findViewById(R.id.inputAddress);
        TextInputEditText fatherName = findViewById(R.id.inputFatherName);
        TextInputEditText school = findViewById(R.id.inputSchool);
        MaterialButton choosePhoto = findViewById(R.id.btnChoosePhoto);

        dobInput.setOnClickListener(v -> showDatePicker());
        choosePhoto.setOnClickListener(v -> pickPhotoLauncher.launch(
                new PickVisualMediaRequest.Builder()
                        .setMediaType(ActivityResultContracts.PickVisualMedia.ImageOnly.INSTANCE)
                        .build()));

        findViewById(R.id.linkLogin).setOnClickListener(v -> finish());

        registerButton.setOnClickListener(v -> submitRegistration(
                name, mobile, password, email, address, fatherName, school));

        loadRegistrationOptions();
    }

    private void showDatePicker() {
        MaterialDatePicker<Long> picker = MaterialDatePicker.Builder.datePicker()
                .setTitleText(getString(R.string.date_of_birth))
                .setSelection(MaterialDatePicker.todayInUtcMilliseconds())
                .build();
        picker.addOnPositiveButtonClickListener(selection -> {
            SimpleDateFormat formatter = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
            formatter.setTimeZone(TimeZone.getTimeZone("UTC"));
            selectedDob = formatter.format(new Date(selection));
            dobInput.setText(selectedDob);
        });
        picker.show(getSupportFragmentManager(), "dob_picker");
    }

    private void onPhotoSelected(Uri uri) {
        if (uri == null) {
            return;
        }
        try {
            ContentResolver resolver = getContentResolver();
            photoMimeType = resolver.getType(uri);
            if (photoMimeType == null) {
                photoMimeType = "image/jpeg";
            }
            String extension = photoMimeType.contains("png") ? "png" : "jpg";
            photoFileName = "registration." + extension;
            try (InputStream input = resolver.openInputStream(uri)) {
                if (input == null) {
                    throw new IllegalStateException("Unable to read photo");
                }
                photoBytes = ApiClient.readAllBytes(input);
            }
            if (photoBytes.length > 5 * 1024 * 1024) {
                clearPhoto();
                UiUtils.toast(this, getString(R.string.attachment_too_large));
                return;
            }
            photoNameView.setText(photoFileName);
        } catch (Exception e) {
            clearPhoto();
            UiUtils.toast(this, e.getMessage() == null ? getString(R.string.network_error) : e.getMessage());
        }
    }

    private void clearPhoto() {
        photoBytes = null;
        photoFileName = null;
        photoMimeType = null;
        photoNameView.setText(R.string.no_file_chosen);
    }

    private void loadRegistrationOptions() {
        UiUtils.setLoaderVisible(classesProgress, true);
        api.get("/registration-options", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(classesProgress, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        UiUtils.toast(RegisterActivity.this, getString(R.string.network_error));
                        return;
                    }
                    populateClasses(data.optJSONArray("classes"));
                    populateSessions(data.optJSONArray("sessions"));
                });
            }

            @Override
            public void onError(String message) {
                runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(classesProgress, false);
                    UiUtils.toast(RegisterActivity.this, message);
                });
            }
        });
    }

    private void populateClasses(JSONArray classes) {
        classCheckboxContainer.removeAllViews();
        if (classes == null) {
            return;
        }
        for (int i = 0; i < classes.length(); i++) {
            String className = classes.optString(i);
            if (className.isEmpty()) {
                continue;
            }
            CheckBox checkBox = new CheckBox(this);
            checkBox.setText(className);
            checkBox.setTag(className);
            checkBox.setPadding(0, 8, 0, 8);
            classCheckboxContainer.addView(checkBox);
        }
    }

    private void populateSessions(JSONArray sessions) {
        List<String> items = new ArrayList<>();
        items.add(getString(R.string.select_session_hint));
        if (sessions != null) {
            for (int i = 0; i < sessions.length(); i++) {
                String sessionName = sessions.optString(i);
                if (!sessionName.isEmpty()) {
                    items.add(sessionName);
                }
            }
        }
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this, android.R.layout.simple_spinner_item, items);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        sessionSpinner.setAdapter(adapter);
    }

    private void submitRegistration(TextInputEditText name, TextInputEditText mobile,
                                    TextInputEditText password, TextInputEditText email,
                                    TextInputEditText address, TextInputEditText fatherName,
                                    TextInputEditText school) {
        String nameValue = textOf(name);
        String mobileValue = textOf(mobile);
        String passwordValue = textOf(password);
        String emailValue = textOf(email);
        String addressValue = textOf(address);
        String fatherValue = textOf(fatherName);
        String schoolValue = textOf(school);

        if (nameValue.isEmpty() || mobileValue.isEmpty() || passwordValue.isEmpty()
                || emailValue.isEmpty() || addressValue.isEmpty() || fatherValue.isEmpty()
                || schoolValue.isEmpty()) {
            UiUtils.toast(this, getString(R.string.fill_all_fields));
            return;
        }
        if (!mobileValue.matches("\\d{10}")) {
            UiUtils.toast(this, getString(R.string.invalid_mobile));
            return;
        }

        List<String> selectedClasses = new ArrayList<>();
        for (int i = 0; i < classCheckboxContainer.getChildCount(); i++) {
            View child = classCheckboxContainer.getChildAt(i);
            if (child instanceof CheckBox && ((CheckBox) child).isChecked()) {
                selectedClasses.add((String) child.getTag());
            }
        }
        if (selectedClasses.isEmpty()) {
            UiUtils.toast(this, getString(R.string.select_class_error));
            return;
        }

        String sessionValue = (String) sessionSpinner.getSelectedItem();
        if (sessionValue == null || sessionValue.equals(getString(R.string.select_session_hint))) {
            UiUtils.toast(this, getString(R.string.select_session_error));
            return;
        }

        if (photoBytes == null || photoFileName == null) {
            UiUtils.toast(this, getString(R.string.select_photo_error));
            return;
        }

        Map<String, String> fields = new HashMap<>();
        fields.put("name", nameValue);
        fields.put("mobile_number", mobileValue);
        fields.put("password", passwordValue);
        fields.put("email", emailValue);
        fields.put("address", addressValue);
        fields.put("father_name", fatherValue);
        fields.put("school_name", schoolValue);
        fields.put("class", String.join(", ", selectedClasses));
        fields.put("session", sessionValue);
        if (selectedDob != null && !selectedDob.isEmpty()) {
            fields.put("date_of_birth", selectedDob);
        }

        setLoading(true);
        api.postMultipart("/register", fields, "image", photoBytes, photoFileName, photoMimeType, false,
                new ApiClient.Callback() {
                    @Override
                    public void onSuccess(JSONObject json) {
                        runOnUiThread(() -> {
                            setLoading(false);
                            String message = json.optString("message",
                                    getString(R.string.registration_submitted));
                            if (message.isEmpty()) {
                                message = getString(R.string.registration_submitted);
                            }
                            new MaterialAlertDialogBuilder(RegisterActivity.this)
                                    .setTitle(R.string.registration_success_title)
                                    .setMessage(message)
                                    .setCancelable(false)
                                    .setPositiveButton(R.string.ok, (dialog, which) -> finish())
                                    .show();
                        });
                    }

                    @Override
                    public void onError(String message) {
                        runOnUiThread(() -> {
                            setLoading(false);
                            UiUtils.toast(RegisterActivity.this, message);
                        });
                    }
                });
    }

    private String textOf(TextInputEditText input) {
        return input.getText() == null ? "" : input.getText().toString().trim();
    }

    private void setLoading(boolean loading) {
        UiUtils.setLoaderVisible(registerProgress, loading);
        registerButton.setEnabled(!loading);
    }
}
