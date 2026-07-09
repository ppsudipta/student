package com.deyeducation.app;

import android.graphics.drawable.GradientDrawable;
import android.net.Uri;
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

public class FeesFragment extends Fragment {
    private ApiClient api;
    private View progressBar;
    private SwipeRefreshLayout swipeRefresh;
    private TextView selectedMonthView;
    private TextView emptyView;
    private TextView studentNameView;
    private TextView registrationCodeView;
    private TextView mobileView;
    private TextView emailView;
    private FeesAdapter adapter;
    private String selectedPeriod = "";

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_fees, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        api = ((MainActivity) requireActivity()).getApi();

        progressBar = view.findViewById(R.id.feesProgress);
        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        selectedMonthView = view.findViewById(R.id.tvSelectedMonth);
        emptyView = view.findViewById(R.id.tvEmptyFees);
        studentNameView = view.findViewById(R.id.tvStudentName);
        registrationCodeView = view.findViewById(R.id.tvRegistrationCode);
        mobileView = view.findViewById(R.id.tvMobile);
        emailView = view.findViewById(R.id.tvEmail);
        RecyclerView list = view.findViewById(R.id.feesList);

        adapter = new FeesAdapter();
        list.setLayoutManager(new LinearLayoutManager(requireContext()));
        list.setAdapter(adapter);

        updatePeriodLabel();
        selectedMonthView.setOnClickListener(v -> showMonthPicker());
        view.findViewById(R.id.btnApplyFilter).setOnClickListener(v -> loadFees());
        view.findViewById(R.id.btnClearFilter).setOnClickListener(v -> {
            selectedPeriod = "";
            updatePeriodLabel();
            loadFees();
        });
        view.findViewById(R.id.btnCurrentMonth).setOnClickListener(v -> {
            selectedPeriod = periodKey(Calendar.getInstance());
            updatePeriodLabel();
            loadFees();
        });

        UiUtils.setupColorfulSwipeRefresh(swipeRefresh);
        swipeRefresh.setOnRefreshListener(this::loadFees);
        swipeRefresh.setOnChildScrollUpCallback((parent, child) ->
                view.findViewById(R.id.feesScroll).canScrollVertically(-1));

        loadFees();
    }

    @Override
    public void onResume() {
        super.onResume();
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.fees));
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
        if (selectedPeriod != null && !selectedPeriod.isEmpty()) {
            try {
                String[] parts = selectedPeriod.split("-");
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
                    selectedPeriod = String.format(Locale.US, "%04d-%02d", year, monthIndex + 1);
                    updatePeriodLabel();
                })
                .setNegativeButton(android.R.string.cancel, null)
                .show();
    }

    private void updatePeriodLabel() {
        if (selectedPeriod == null || selectedPeriod.isEmpty()) {
            selectedMonthView.setText(R.string.all_months);
        } else {
            selectedMonthView.setText(formatPeriodLabel(selectedPeriod));
        }
    }

    private void loadFees() {
        if (!swipeRefresh.isRefreshing()) {
            UiUtils.setLoaderVisible(progressBar, true);
        }
        emptyView.setVisibility(View.GONE);

        String path = "/fees?per_page=100";
        if (selectedPeriod != null && !selectedPeriod.isEmpty()) {
            String[] parts = selectedPeriod.split("-");
            if (parts.length == 2) {
                path += "&year=" + parts[0];
                path += "&month=" + Uri.encode(monthNameFromPeriod(selectedPeriod));
            }
        }

        api.get(path, true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) {
                    return;
                }
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    bindStudent(json.optJSONObject("student"));
                    adapter.setItems(parsePayments(json));
                    boolean empty = adapter.getItemCount() == 0;
                    emptyView.setVisibility(empty ? View.VISIBLE : View.GONE);
                    if (empty && selectedPeriod != null && !selectedPeriod.isEmpty()) {
                        emptyView.setText(getString(R.string.no_fee_records_for_month,
                                formatPeriodLabel(selectedPeriod)));
                    } else {
                        emptyView.setText(R.string.no_fee_records);
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
                    swipeRefresh.setRefreshing(false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void bindStudent(@Nullable JSONObject student) {
        if (student == null) {
            return;
        }
        studentNameView.setText(getString(R.string.label_name_value,
                student.optString("name", "")));
        registrationCodeView.setText(getString(R.string.label_registration_value,
                student.optString("registration_code", "")));
        mobileView.setText(getString(R.string.label_mobile_value,
                student.optString("mobile_number", getString(R.string.not_provided))));
        emailView.setText(getString(R.string.label_email_value,
                student.optString("email", getString(R.string.not_provided))));
    }

    private List<JSONObject> parsePayments(JSONObject json) {
        List<JSONObject> items = new ArrayList<>();
        if (json == null) {
            return items;
        }
        JSONObject payments = json.optJSONObject("payments");
        JSONArray rows = extractArray(payments);
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row != null) {
                items.add(row);
            }
        }
        return items;
    }

    private JSONArray extractArray(@Nullable JSONObject container) {
        if (container == null) {
            return new JSONArray();
        }
        Object data = container.opt("data");
        if (data instanceof JSONArray) {
            return (JSONArray) data;
        }
        return new JSONArray();
    }

    private String periodKey(Calendar calendar) {
        return String.format(Locale.US, "%04d-%02d",
                calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH) + 1);
    }

    private String formatPeriodLabel(String period) {
        try {
            String[] parts = period.split("-");
            Calendar calendar = Calendar.getInstance();
            calendar.set(Integer.parseInt(parts[0]), Integer.parseInt(parts[1]) - 1, 1);
            SimpleDateFormat output = new SimpleDateFormat("MMMM yyyy", Locale.getDefault());
            return output.format(calendar.getTime());
        } catch (Exception e) {
            return period;
        }
    }

    private String monthNameFromPeriod(String period) {
        try {
            String[] parts = period.split("-");
            Calendar calendar = Calendar.getInstance();
            calendar.set(Integer.parseInt(parts[0]), Integer.parseInt(parts[1]) - 1, 1);
            return new SimpleDateFormat("MMMM", Locale.ENGLISH).format(calendar.getTime());
        } catch (Exception e) {
            return "";
        }
    }

    private static class FeesAdapter extends RecyclerView.Adapter<FeesAdapter.Holder> {
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
                    .inflate(R.layout.item_fee_record, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            JSONObject row = items.get(position);
            holder.date.setText(formatDisplayDate(row.optString("donation_date", "")));
            holder.month.setText(formatFeeMonth(row));
            String status = row.optString("status", "");
            if (status.isEmpty()) {
                status = holder.itemView.getContext().getString(R.string.payment_success);
            } else {
                status = status.substring(0, 1).toUpperCase(Locale.ROOT)
                        + status.substring(1).toLowerCase(Locale.ROOT);
            }
            holder.status.setText(status);
            boolean success = "success".equalsIgnoreCase(row.optString("status", "success"));
            int color = ContextCompat.getColor(holder.itemView.getContext(),
                    success ? R.color.success : R.color.alert_text);
            holder.status.setTextColor(color);
            GradientDrawable bg = new GradientDrawable();
            bg.setCornerRadius(24f);
            bg.setColor(ContextCompat.getColor(holder.itemView.getContext(),
                    success ? R.color.service_mint_bg : R.color.alert_bg));
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

        private String formatFeeMonth(JSONObject row) {
            String period = row.optString("fee_period", "");
            if (period != null && !period.isEmpty() && !"null".equals(period)) {
                return period;
            }
            String month = row.optString("payment_reason", "");
            String date = row.optString("donation_date", "");
            if (month.isEmpty() || "null".equals(month)) {
                return "";
            }
            try {
                String year = date.length() >= 4 ? date.substring(0, 4) : "";
                return year.isEmpty() ? month : month + " " + year;
            } catch (Exception e) {
                return month;
            }
        }

        private String formatDisplayDate(String value) {
            if (value == null || value.isEmpty() || "null".equals(value)) {
                return "";
            }
            try {
                SimpleDateFormat input = new SimpleDateFormat("yyyy-MM-dd", Locale.US);
                SimpleDateFormat output = new SimpleDateFormat("dd MMM yyyy", Locale.getDefault());
                return output.format(input.parse(value));
            } catch (Exception e) {
                try {
                    SimpleDateFormat input = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.US);
                    SimpleDateFormat output = new SimpleDateFormat("dd MMM yyyy", Locale.getDefault());
                    return output.format(input.parse(value));
                } catch (Exception ignored) {
                    return value;
                }
            }
        }

        static class Holder extends RecyclerView.ViewHolder {
            final TextView date;
            final TextView month;
            final TextView status;

            Holder(@NonNull View itemView) {
                super(itemView);
                date = itemView.findViewById(R.id.tvDate);
                month = itemView.findViewById(R.id.tvMonth);
                status = itemView.findViewById(R.id.tvStatus);
            }
        }
    }
}
