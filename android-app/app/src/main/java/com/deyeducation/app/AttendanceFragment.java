package com.deyeducation.app;

import android.graphics.drawable.GradientDrawable;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.NumberPicker;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.dialog.MaterialAlertDialogBuilder;

import org.json.JSONArray;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;

public class AttendanceFragment extends Fragment {
    private ApiClient api;
    private View progressBar;
    private SwipeRefreshLayout swipeRefresh;
    private TextView selectedMonthView;
    private TextView emptyView;
    private TextView studentNameView;
    private TextView studentClassView;
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
        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        selectedMonthView = view.findViewById(R.id.tvSelectedMonth);
        emptyView = view.findViewById(R.id.tvEmptyAttendance);
        studentNameView = view.findViewById(R.id.tvStudentName);
        studentClassView = view.findViewById(R.id.tvStudentClass);
        RecyclerView list = view.findViewById(R.id.attendanceList);

        adapter = new AttendanceAdapter();
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        selectedMonthView.setText(formatMonthLabel(selectedMonth));
        selectedMonthView.setOnClickListener(v -> showMonthPicker());
        view.findViewById(R.id.btnApplyMonth).setOnClickListener(v -> loadAttendance());
        view.findViewById(R.id.btnCurrentMonth).setOnClickListener(v -> {
            selectedMonth = monthKey(Calendar.getInstance());
            selectedMonthView.setText(formatMonthLabel(selectedMonth));
            loadAttendance();
        });

        UiUtils.setupColorfulSwipeRefresh(swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadAttendance);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) ->
                view.findViewById(R.id.attendanceScroll).canScrollVertically(-1));

        loadAttendance();
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.attendance_report_title));
        }
    }

    private void showMonthPicker() {
        View dialogView = LayoutInflater.from(requireContext())
                .inflate(R.layout.dialog_month_year_picker, null, false);
        NumberPicker monthPicker = dialogView.findViewById(R.id.monthPicker);
        NumberPicker yearPicker = dialogView.findViewById(R.id.yearPicker);

        String[] monthLabels = new SimpleDateFormat("MMMM", Locale.getDefault())
                .getDateFormatSymbols().getMonths();
        List<String> months = new ArrayList<>();
        for (String label : monthLabels) {
            if (label != null && !label.trim().isEmpty()) {
                months.add(label);
            }
        }
        monthPicker.setMinValue(0);
        monthPicker.setMaxValue(months.size() - 1);
        monthPicker.setDisplayedValues(months.toArray(new String[0]));
        monthPicker.setWrapSelectorWheel(false);

        Calendar now = Calendar.getInstance();
        int currentYear = now.get(Calendar.YEAR);
        yearPicker.setMinValue(currentYear - 10);
        yearPicker.setMaxValue(currentYear + 1);
        yearPicker.setWrapSelectorWheel(false);

        int initialMonth = now.get(Calendar.MONTH);
        int initialYear = currentYear;
        if (selectedMonth != null && !selectedMonth.isEmpty()) {
            try {
                String[] parts = selectedMonth.split("-");
                initialYear = Integer.parseInt(parts[0]);
                initialMonth = Integer.parseInt(parts[1]) - 1;
            } catch (Exception ignored) {
            }
        }
        monthPicker.setValue(Math.max(0, Math.min(initialMonth, months.size() - 1)));
        yearPicker.setValue(Math.max(yearPicker.getMinValue(),
                Math.min(initialYear, yearPicker.getMaxValue())));

        new MaterialAlertDialogBuilder(requireContext())
                .setTitle(R.string.set_month)
                .setView(dialogView)
                .setPositiveButton(R.string.apply_filter, (dialog, which) -> {
                    int monthIndex = monthPicker.getValue();
                    int year = yearPicker.getValue();
                    selectedMonth = String.format(Locale.US, "%04d-%02d", year, monthIndex + 1);
                    selectedMonthView.setText(formatMonthLabel(selectedMonth));
                })
                .setNegativeButton(android.R.string.cancel, null)
                .show();
    }

    private void loadAttendance() {
        if (!swipeRefresh.isRefreshing()) {
            UiUtils.setLoaderVisible(progressBar, true);
        }
        emptyView.setVisibility(View.GONE);
        api.get("/attendance?month=" + selectedMonth + "&per_page=100", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    JSONObject student = json.optJSONObject("student");
                    if (student != null) {
                        studentNameView.setText(student.optString("name", ""));
                        studentClassView.setText(student.optString("class", ""));
                    }
                    adapter.setItems(parseRecords(json));
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
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private List<JSONObject> parseRecords(JSONObject json) {
        List<JSONObject> items = new ArrayList<>();
        if (json == null) {
            return items;
        }
        Object data = json.opt("data");
        JSONArray rows;
        if (data instanceof JSONArray) {
            rows = (JSONArray) data;
        } else if (data instanceof JSONObject) {
            rows = ((JSONObject) data).optJSONArray("data");
        } else {
            rows = null;
        }
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
            holder.date.setText(formatDisplayDate(row.optString("attendance_date", "")));
            String title = row.optString("attendance_title", "");
            if (title.isEmpty() || "null".equals(title)) {
                title = row.optString("class_name", "");
            }
            holder.title.setText(title);
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

            int bgColor = position % 2 == 0
                    ? ContextCompat.getColor(holder.itemView.getContext(), R.color.surface_card)
                    : ContextCompat.getColor(holder.itemView.getContext(), R.color.secondary_bg);
            holder.itemView.setBackgroundColor(bgColor);
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        private String formatDisplayDate(String value) {
            try {
                SimpleDateFormat input = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
                SimpleDateFormat output = new SimpleDateFormat("dd MMM yyyy", Locale.getDefault());
                return output.format(input.parse(value));
            } catch (Exception e) {
                return value;
            }
        }

        static class Holder extends RecyclerView.ViewHolder {
            final TextView date;
            final TextView title;
            final TextView status;

            Holder(@NonNull View itemView) {
                super(itemView);
                date = itemView.findViewById(R.id.tvDate);
                title = itemView.findViewById(R.id.tvTitle);
                status = itemView.findViewById(R.id.tvStatus);
            }
        }
    }
}
