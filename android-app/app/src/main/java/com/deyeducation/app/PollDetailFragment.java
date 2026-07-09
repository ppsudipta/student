package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;
import com.google.android.material.progressindicator.LinearProgressIndicator;

import org.json.JSONArray;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.HashMap;
import java.util.Locale;
import java.util.Map;

public class PollDetailFragment extends Fragment {
    public static final String ARG_ID = "poll_id";
    public static final String ARG_QUESTION = "poll_question";

    private ApiClient api;
    private int pollId;
    private View progressBar;
    private TextView questionView;
    private TextView expiryView;
    private RadioGroup optionsGroup;
    private MaterialButton submitButton;
    private MaterialCardView resultsCard;
    private LinearLayout resultsContainer;
    private final Map<Integer, RadioButton> optionButtons = new HashMap<>();

    public static PollDetailFragment newInstance(int id, String question) {
        Bundle args = new Bundle();
        args.putInt(ARG_ID, id);
        args.putString(ARG_QUESTION, question);
        PollDetailFragment fragment = new PollDetailFragment();
        fragment.setArguments(args);
        return fragment;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_poll_detail, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        Bundle args = getArguments();
        pollId = args == null ? 0 : args.getInt(ARG_ID, 0);
        String question = args == null ? null : args.getString(ARG_QUESTION);
        api = ((MainActivity) requireActivity()).getApi();

        progressBar = view.findViewById(R.id.pollDetailProgress);
        questionView = view.findViewById(R.id.tvPollQuestion);
        expiryView = view.findViewById(R.id.tvPollExpiry);
        optionsGroup = view.findViewById(R.id.optionsGroup);
        submitButton = view.findViewById(R.id.btnSubmitVote);
        resultsCard = view.findViewById(R.id.resultsCard);
        resultsContainer = view.findViewById(R.id.resultsContainer);

        if (question != null && !question.isEmpty()) {
            questionView.setText(question);
            ((MainActivity) requireActivity()).setScreenTitle(question);
        }

        submitButton.setOnClickListener(v -> submitVote());
        loadPoll();
    }

    private void loadPoll() {
        UiUtils.setLoaderVisible(progressBar, true);
        api.get("/polls/" + pollId, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    bindPoll(json.optJSONObject("data"));
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void bindPoll(@Nullable JSONObject data) {
        if (data == null) {
            return;
        }
        JSONObject poll = data.optJSONObject("poll");
        if (poll != null) {
            String question = poll.optString("question", "");
            questionView.setText(question);
            if (getActivity() instanceof MainActivity && !question.isEmpty()) {
                ((MainActivity) getActivity()).setScreenTitle(question);
            }
            String expiry = poll.optString("expiry_date", "");
            if (expiry != null && !expiry.isEmpty() && !"null".equals(expiry)) {
                expiryView.setVisibility(View.VISIBLE);
                expiryView.setText(getString(R.string.poll_expires, formatDate(expiry)));
            } else {
                expiryView.setVisibility(View.GONE);
            }
        }

        boolean hasVoted = data.optBoolean("has_voted", false) || data.optJSONObject("my_vote") != null;
        bindOptions(data.optJSONArray("options"), data.optJSONObject("my_vote"), hasVoted);

        if (hasVoted) {
            submitButton.setVisibility(View.GONE);
            optionsGroup.setEnabled(false);
            for (int i = 0; i < optionsGroup.getChildCount(); i++) {
                optionsGroup.getChildAt(i).setEnabled(false);
            }
            showResults(data.optJSONArray("results"));
        } else {
            resultsCard.setVisibility(View.GONE);
            submitButton.setVisibility(View.VISIBLE);
        }
    }

    private void bindOptions(@Nullable JSONArray options, @Nullable JSONObject myVote, boolean hasVoted) {
        optionsGroup.removeAllViews();
        optionButtons.clear();
        if (options == null) {
            return;
        }
        int selectedOptionId = myVote == null ? 0 : myVote.optInt("option_id", 0);
        for (int i = 0; i < options.length(); i++) {
            JSONObject option = options.optJSONObject(i);
            if (option == null) {
                continue;
            }
            int optionId = option.optInt("id", 0);
            RadioButton button = new RadioButton(requireContext());
            button.setId(View.generateViewId());
            button.setText(option.optString("option_text", ""));
            button.setTextColor(requireContext().getColor(R.color.primary_text));
            button.setPadding(0, 16, 0, 16);
            button.setTag(optionId);
            if (hasVoted && optionId == selectedOptionId) {
                button.setChecked(true);
            }
            optionsGroup.addView(button);
            optionButtons.put(optionId, button);
        }
    }

    private void submitVote() {
        int selectedId = optionsGroup.getCheckedRadioButtonId();
        if (selectedId == -1) {
            UiUtils.toast(requireContext(), getString(R.string.poll_select_option));
            return;
        }
        RadioButton selected = optionsGroup.findViewById(selectedId);
        if (selected == null || selected.getTag() == null) {
            return;
        }
        int optionId = (int) selected.getTag();

        submitButton.setEnabled(false);
        UiUtils.setLoaderVisible(progressBar, true);

        try {
            JSONObject body = new JSONObject();
            body.put("option_id", optionId);
            api.post("/polls/" + pollId + "/vote", body, true, new ApiClient.Callback() {
                @Override
                public void onSuccess(JSONObject json) {
                    if (!isAdded()) {
                        return;
                    }
                    requireActivity().runOnUiThread(() -> {
                        UiUtils.setLoaderVisible(progressBar, false);
                        UiUtils.toast(requireContext(), getString(R.string.poll_vote_saved));
                        JSONObject data = json.optJSONObject("data");
                        if (data != null) {
                            bindPoll(data);
                        } else {
                            loadPoll();
                        }
                    });
                }

                @Override
                public void onError(String message) {
                    if (!isAdded()) {
                        return;
                    }
                    requireActivity().runOnUiThread(() -> {
                        UiUtils.setLoaderVisible(progressBar, false);
                        submitButton.setEnabled(true);
                        UiUtils.toast(requireContext(), message);
                    });
                }
            });
        } catch (Exception e) {
            submitButton.setEnabled(true);
            UiUtils.setLoaderVisible(progressBar, false);
            UiUtils.toast(requireContext(), e.getMessage());
        }
    }

    private void showResults(@Nullable JSONArray results) {
        resultsContainer.removeAllViews();
        if (results == null || results.length() == 0) {
            resultsCard.setVisibility(View.GONE);
            return;
        }
        resultsCard.setVisibility(View.VISIBLE);
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < results.length(); i++) {
            JSONObject row = results.optJSONObject(i);
            if (row == null) {
                continue;
            }
            View item = inflater.inflate(R.layout.item_poll_result, resultsContainer, false);
            TextView label = item.findViewById(R.id.tvResultLabel);
            TextView stats = item.findViewById(R.id.tvResultStats);
            LinearProgressIndicator progress = item.findViewById(R.id.resultProgress);

            String text = row.optString("option_text", "");
            int votes = row.optInt("votes", 0);
            int percentage = row.optInt("percentage", 0);
            label.setText(text);
            stats.setText(getString(R.string.poll_result_stats, votes, percentage));
            progress.setProgress(percentage);
            resultsContainer.addView(item);
        }
    }

    private String formatDate(String value) {
        try {
            SimpleDateFormat input = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
            SimpleDateFormat output = new SimpleDateFormat("dd MMM yyyy", Locale.getDefault());
            return output.format(input.parse(value));
        } catch (Exception e) {
            return value;
        }
    }
}
