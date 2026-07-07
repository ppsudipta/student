package com.deyeducation.app;

import android.graphics.drawable.GradientDrawable;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.datepicker.MaterialDatePicker;

import org.json.JSONArray;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;
import java.util.TimeZone;

public class AttendanceFragment extends Fragment {
    private ApiClient api;
    private ProgressBar progressBar;
    private TextView selectedMonthView;
    private TextView recordsTitleView;
    private TextView emptyView;
    private TextView studentNameView;
    private TextView studentClassView;
    private TextView statTotalValue;
    private TextView statPresentValue;
    private TextView statAbsentValue;
    private TextView statPercentValue;
    private AttendanceAdapter adapter;
    private String selectedMonth;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_attendance, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        api = ((MainActivity) requireActivity()).getApi();
        selectedMonth = monthKey(Calendar.getInstance());

        progressBar = view.findViewById(R.id.attendanceProgress);
        selectedMonthView = view.findViewById(R.id.tvSelectedMonth);
        recordsTitleView = view.findViewById(R.id.tvRecordsTitle);
        emptyView = view.findViewById(R.id.tvEmptyAttendance);
        studentNameView = view.findViewById(R.id.tvStudentName);
        studentClassView = view.findViewById(R.id.tvStudentClass);
        RecyclerView list = view.findViewById(R.id.attendanceList);

        bindStatCard(view.findViewById(R.id.statTotal), getString(R.string.total_days));
        bindStatCard(view.findViewById(R.id.statPresent), getString(R.string.present_days));
        bindStatCard(view.findViewById(R.id.statAbsent), getString(R.string.absent_days));
        bindStatCard(view.findViewById(R.id.statPercent), getString(R.string.attendance_percent));

        statTotalValue = view.findViewById(R.id.statTotal).findViewById(R.id.statValue);
        statPresentValue = view.findViewById(R.id.statPresent).findViewById(R.id.statValue);
        statAbsentValue = view.findViewById(R.id.statAbsent).findViewById(R.id.statValue);
        statPercentValue = view.findViewById(R.id.statPercent).findViewById(R.id.statValue);

        adapter = new AttendanceAdapter();
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        selectedMonthView.setText(formatMonthLabel(selectedMonth));
        selectedMonthView.setOnClickListener(v -> showMonthPicker());
        view.findViewById(R.id.btnApplyMonth).setOnClickListener(v -> loadAttendance());
        ((MaterialButton) view.findViewById(R.id.btnCurrentMonth))
                .setOnClickListener(v -> {
                    selectedMonth = monthKey(Calendar.getInstance());
                    selectedMonthView.setText(formatMonthLabel(selectedMonth));
                    loadAttendance();
                });

        loadAttendance();
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).showFragment(new AttendanceFragment(),
                    getString(R.string.progress_report), true);
        }
    }

    private void bindStatCard(View root, String label) {
        ((TextView) root.findViewById(R.id.statLabel)).setText(label);
    }

    private void showMonthPicker() {
        long initial = monthToUtcMillis(selectedMonth);
        MaterialDatePicker<Long> picker = MaterialDatePicker.Builder.datePicker()
                .setTitleText(getString(R.string.set_month))
                .setSelection(initial)
                .build();
        picker.addOnPositiveButtonClickListener(selection -> {
            Calendar calendar = Calendar.getInstance(TimeZone.getTimeZone("UTC"));
            calendar.setTimeInMillis(selection);
            selectedMonth = monthKey(calendar);
            selectedMonthView.setText(formatMonthLabel(selectedMonth));
        });
        picker.show(getParentFragmentManager(), "month_picker");
    }

    private void loadAttendance() {
        progressBar.setVisibility(View.VISIBLE);
        emptyView.setVisibility(View.GONE);
        api.get("/attendance?month=" + selectedMonth + "&per_page=100", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    JSONObject summary = json.optJSONObject("summary");
                    JSONObject student = json.optJSONObject("student");
                    if (student != null) {
                        studentNameView.setText(student.optString("name", ""));
                        studentClassView.setText(student.optString("class", ""));
                    }
                    if (summary != null) {
                        statTotalValue.setText(String.valueOf(summary.optInt("total_days", 0)));
                        statPresentValue.setText(String.valueOf(summary.optInt("present_days", 0)));
                        statPresentValue.setTextColor(ContextCompat.getColor(requireContext(), R.color.success));
                        statAbsentValue.setText(String.valueOf(summary.optInt("absent_days", 0)));
                        statAbsentValue.setTextColor(ContextCompat.getColor(requireContext(), R.color.alert_text));
                        statPercentValue.setText(summary.optInt("attendance_percentage", 0) + "%");
                    }
                    recordsTitleView.setText(getString(R.string.attendance_records_for,
                            formatMonthLabel(selectedMonth)));
                    adapter.setItems(parseRecords(json.optJSONObject("data")));
                    boolean empty = adapter.getItemCount() == 0;
                    emptyView.setVisibility(empty ? View.VISIBLE : View.GONE);
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

    private List<JSONObject> parseRecords(JSONObject data) {
        List<JSONObject> items = new ArrayList<>();
        if (data == null) {
            return items;
        }
        JSONArray rows = data.optJSONArray("data");
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

    private String monthKey(Calendar calendar) {
        return String.format(Locale.US, "%04d-%02d",
                calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH) + 1);
    }

    private long monthToUtcMillis(String month) {
        try {
            String[] parts = month.split("-");
            Calendar calendar = Calendar.getInstance(TimeZone.getTimeZone("UTC"));
            calendar.set(Integer.parseInt(parts[0]), Integer.parseInt(parts[1]) - 1, 1, 0, 0, 0);
            calendar.set(Calendar.MILLISECOND, 0);
            return calendar.getTimeInMillis();
        } catch (Exception e) {
            return MaterialDatePicker.todayInUtcMilliseconds();
        }
    }

    private String formatMonthLabel(String month) {
        try {
            SimpleDateFormat input = new SimpleDateFormat("yyyy-MM", Locale.US);
            SimpleDateFormat output = new SimpleDateFormat("MMMM yyyy", Locale.getDefault());
            return output.format(input.parse(month));
        } catch (Exception e) {
            return month;
        }
    }

    private static class AttendanceAdapter extends RecyclerView.Adapter<AttendanceAdapter.Holder> {
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
                    .inflate(R.layout.item_attendance_record, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            JSONObject row = items.get(position);
            String date = row.optString("attendance_date", "");
            holder.date.setText(formatDisplayDate(date));
            holder.day.setText(row.optString("day_name", ""));
            holder.className.setText(row.optString("class_name", ""));
            String title = row.optString("attendance_title", "");
            if (title.isEmpty() || "null".equals(title)) {
                holder.title.setVisibility(View.GONE);
            } else {
                holder.title.setVisibility(View.VISIBLE);
                holder.title.setText(title);
            }
            String status = row.optString("status", "");
            holder.status.setText(status);
            boolean present = "Present".equalsIgnoreCase(status);
            int color = ContextCompat.getColor(holder.itemView.getContext(),
                    present ? R.color.success : R.color.alert_text);
            holder.status.setTextColor(color);
            GradientDrawable bg = new GradientDrawable();
            bg.setCornerRadius(24f);
            bg.setColor(ContextCompat.getColor(holder.itemView.getContext(),
                    present ? R.color.service_mint_bg : R.color.alert_bg));
            holder.status.setBackground(bg);
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        private String formatDisplayDate(String value) {
            try {
                SimpleDateFormat input = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
                SimpleDateFormat output = new SimpleDateFormat("MMM d, yyyy", Locale.getDefault());
                return output.format(input.parse(value));
            } catch (Exception e) {
                return value;
            }
        }

        static class Holder extends RecyclerView.ViewHolder {
            final TextView date;
            final TextView day;
            final TextView className;
            final TextView title;
            final TextView status;

            Holder(@NonNull View itemView) {
                super(itemView);
                date = itemView.findViewById(R.id.tvDate);
                day = itemView.findViewById(R.id.tvDay);
                className = itemView.findViewById(R.id.tvClass);
                title = itemView.findViewById(R.id.tvTitle);
                status = itemView.findViewById(R.id.tvStatus);
            }
        }
    }
}
