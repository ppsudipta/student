package com.deyeducation.app;

import android.graphics.drawable.GradientDrawable;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import org.json.JSONArray;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

public class PollsFragment extends Fragment {
    private ApiClient api;
    private SwipeRefreshLayout swipeRefresh;
    private View progressBar;
    private TextView emptyView;
    private PollsAdapter adapter;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_polls, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        api = ((MainActivity) requireActivity()).getApi();

        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        progressBar = view.findViewById(R.id.pollsProgress);
        emptyView = view.findViewById(R.id.tvEmptyPolls);
        RecyclerView list = view.findViewById(R.id.pollsList);

        adapter = new PollsAdapter();
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        UiUtils.setupColorfulSwipeRefresh(swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadPolls);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) ->
                list.canScrollVertically(-1));

        loadPolls();
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.polls));
        }
    }

    private void loadPolls() {
        if (!swipeRefresh.isRefreshing()) {
            UiUtils.setLoaderVisible(progressBar, true);
        }
        emptyView.setVisibility(View.GONE);

        api.get("/polls", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    List<JSONObject> items = parsePolls(json);
                    adapter.setItems(items);
                    emptyView.setVisibility(items.isEmpty() ? View.VISIBLE : View.GONE);
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private List<JSONObject> parsePolls(JSONObject json) {
        List<JSONObject> items = new ArrayList<>();
        JSONArray rows = json.optJSONArray("data");
        if (rows == null) {
            return items;
        }
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row != null) {
                items.add(row);
            }
        }
        return items;
    }

    private class PollsAdapter extends RecyclerView.Adapter<PollsAdapter.Holder> {
        private final List<JSONObject> items = new ArrayList<>();

        void setItems(List<JSONObject> next) {
            items.clear();
            items.addAll(next);
            notifyDataSetChanged();
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext())
                    .inflate(R.layout.item_poll_card, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            JSONObject item = items.get(position);
            JSONObject poll = item.optJSONObject("poll");
            if (poll == null) {
                return;
            }

            int pollId = poll.optInt("id", 0);
            String question = poll.optString("question", "");
            boolean hasVoted = item.optBoolean("has_voted", false)
                    || item.optJSONObject("my_vote") != null;

            holder.question.setText(question);
            holder.meta.setText(buildMeta(poll, item.optJSONArray("options")));
            holder.status.setText(hasVoted ? R.string.poll_voted : R.string.poll_vote_now);

            int color = ContextCompat.getColor(requireContext(),
                    hasVoted ? R.color.success : R.color.primary);
            int bgColor = ContextCompat.getColor(requireContext(),
                    hasVoted ? R.color.service_mint_bg : R.color.primary_light);
            holder.status.setTextColor(color);
            GradientDrawable bg = new GradientDrawable();
            bg.setCornerRadius(24f);
            bg.setColor(bgColor);
            holder.status.setBackground(bg);

            holder.itemView.setOnClickListener(v -> {
                if (getActivity() instanceof MainActivity) {
                    ((MainActivity) getActivity()).showFragment(
                            PollDetailFragment.newInstance(pollId, question),
                            question,
                            true);
                }
            });
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        private String buildMeta(JSONObject poll, @Nullable JSONArray options) {
            int optionCount = options == null ? 0 : options.length();
            String expiry = poll.optString("expiry_date", "");
            StringBuilder meta = new StringBuilder();
            meta.append(optionCount).append(' ')
                    .append(getString(optionCount == 1 ? R.string.poll_option_singular : R.string.poll_options_plural));
            if (expiry != null && !expiry.isEmpty() && !"null".equals(expiry)) {
                meta.append(" · ").append(getString(R.string.poll_expires, formatDate(expiry)));
            }
            return meta.toString();
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

        class Holder extends RecyclerView.ViewHolder {
            final TextView question;
            final TextView meta;
            final TextView status;

            Holder(@NonNull View itemView) {
                super(itemView);
                question = itemView.findViewById(R.id.tvPollQuestion);
                meta = itemView.findViewById(R.id.tvPollMeta);
                status = itemView.findViewById(R.id.tvPollStatus);
            }
        }
    }
}
