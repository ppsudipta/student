package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import org.json.JSONObject;

public class CourseDetailFragment extends Fragment {
    public static final String ARG_ID = "course_id";
    public static final String ARG_TITLE = "course_title";

    private ApiClient api;
    private SessionManager session;

    public static CourseDetailFragment newInstance(int id, String title) {
        Bundle args = new Bundle();
        args.putInt(ARG_ID, id);
        args.putString(ARG_TITLE, title);
        CourseDetailFragment fragment = new CourseDetailFragment();
        fragment.setArguments(args);
        return fragment;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_course_detail, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        Bundle args = getArguments();
        int id = args == null ? 0 : args.getInt(ARG_ID, 0);
        String fallbackTitle = args == null ? null : args.getString(ARG_TITLE);

        ImageView image = view.findViewById(R.id.courseImage);
        TextView title = view.findViewById(R.id.courseTitle);
        TextView meta = view.findViewById(R.id.courseMeta);
        TextView description = view.findViewById(R.id.courseDescription);
        View progress = view.findViewById(R.id.courseProgress);

        String initialTitle = fallbackTitle == null ? getString(R.string.course_details) : fallbackTitle;
        title.setText(initialTitle);

        if (id <= 0) {
            UiUtils.toast(requireContext(), getString(R.string.no_records));
            activity.popBackStackIfPossible();
            return;
        }

        UiUtils.setLoaderVisible(progress, true);
        api.get("/events/" + id, false, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progress, false);
                    JSONObject data = json.optJSONObject("data");
                    if (data == null) {
                        UiUtils.toast(requireContext(), getString(R.string.no_records));
                        return;
                    }

                    String name = data.optString("name", initialTitle);
                    title.setText(name);
                    if (getActivity() instanceof MainActivity) {
                        ((MainActivity) getActivity()).setScreenTitle(name);
                    }

                    String price = data.optString("price", "0");
                    String date = data.optString("date", "");
                    StringBuilder metaText = new StringBuilder(getString(R.string.course_price, price));
                    if (!date.isEmpty() && !"null".equals(date)) {
                        metaText.append("\n").append(getString(R.string.course_date, date));
                    }
                    meta.setText(metaText.toString());

                    UiUtils.bindHtml(description, data.optString("description", ""));
                    UiUtils.loadImage(requireContext(),
                            UrlHelper.imageFromJson(session.getBaseUrl(), data), image, 0);
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
            Bundle args = getArguments();
            String fallbackTitle = args == null ? null : args.getString(ARG_TITLE);
            String initialTitle = fallbackTitle == null ? getString(R.string.course_details) : fallbackTitle;
            ((MainActivity) getActivity()).setScreenTitle(initialTitle);
        }
    }
}
