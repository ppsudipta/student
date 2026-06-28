package com.deyeducation.app;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.GridLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;
import androidx.viewpager2.widget.ViewPager2;

import com.google.android.material.bottomsheet.BottomSheetDialog;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class HomeFragment extends Fragment {
    private ApiClient api;
    private SessionManager session;
    private ProgressBar progressBar;
    private TextView feeBanner;
    private View notificationDot;
    private ViewPager2.OnPageChangeCallback sliderPageCallback;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_home, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        MainActivity activity = (MainActivity) requireActivity();
        api = activity.getApi();
        session = activity.getSession();

        progressBar = view.findViewById(R.id.homeProgress);
        feeBanner = view.findViewById(R.id.feeAlertBanner);
        notificationDot = view.findViewById(R.id.notificationDot);
        GridLayout serviceGrid = view.findViewById(R.id.serviceGrid);
        LinearLayout coursesContainer = view.findViewById(R.id.coursesContainer);
        LinearLayout promotionsContainer = view.findViewById(R.id.promotionsContainer);
        View sliderSection = view.findViewById(R.id.sliderSection);
        ViewPager2 sliderPager = view.findViewById(R.id.sliderPager);
        LinearLayout sliderDots = view.findViewById(R.id.sliderDots);

        addServiceItem(serviceGrid, R.drawable.ic_service_academy, getString(R.string.academy_details), v ->
                DetailActivity.open(requireContext(), getString(R.string.academy_details), "/company"));
        addServiceItem(serviceGrid, R.drawable.ic_service_about, getString(R.string.about_us), v ->
                DetailActivity.open(requireContext(), getString(R.string.about_us), "/company"));
        addServiceItem(serviceGrid, R.drawable.ic_service_gallery, getString(R.string.gallery), v ->
                activity.selectBottomNav(R.id.nav_gallery));
        addServiceItem(serviceGrid, R.drawable.ic_service_more, getString(R.string.more), v -> showMoreSheet(activity));

        view.findViewById(R.id.btnNotifications).setOnClickListener(v ->
                activity.selectBottomNav(R.id.nav_notices));
        view.findViewById(R.id.btnWhatsapp).setOnClickListener(v -> activity.openWhatsapp());

        loadHome(view, coursesContainer, promotionsContainer, sliderSection, sliderPager, sliderDots);
    }

    @Override
    public void onDestroyView() {
        ViewPager2 pager = getView() != null ? getView().findViewById(R.id.sliderPager) : null;
        if (pager != null && sliderPageCallback != null) {
            pager.unregisterOnPageChangeCallback(sliderPageCallback);
        }
        sliderPageCallback = null;
        super.onDestroyView();
    }

    private void addServiceItem(GridLayout grid, int iconRes, String label, View.OnClickListener click) {
        View item = LayoutInflater.from(requireContext()).inflate(R.layout.item_service_grid, grid, false);
        ((ImageView) item.findViewById(R.id.serviceIcon)).setImageResource(iconRes);
        ((TextView) item.findViewById(R.id.serviceLabel)).setText(label);
        item.setOnClickListener(click);
        GridLayout.LayoutParams params = new GridLayout.LayoutParams();
        params.width = 0;
        params.columnSpec = GridLayout.spec(GridLayout.UNDEFINED, 1f);
        item.setLayoutParams(params);
        grid.addView(item);
    }

    private void showMoreSheet(MainActivity activity) {
        BottomSheetDialog dialog = new BottomSheetDialog(requireContext());
        View sheet = LayoutInflater.from(requireContext()).inflate(R.layout.bottom_sheet_more, null);
        GridLayout moreGrid = sheet.findViewById(R.id.moreGrid);
        addServiceItem(moreGrid, R.drawable.ic_service_gallery, getString(R.string.gallery), v -> {
            dialog.dismiss();
            activity.selectBottomNav(R.id.nav_gallery);
        });
        addServiceItem(moreGrid, R.drawable.ic_service_academy, getString(R.string.courses), v -> {
            dialog.dismiss();
            activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_COURSES), getString(R.string.courses));
        });
        addServiceItem(moreGrid, R.drawable.ic_whatsapp, getString(R.string.contact), v -> {
            dialog.dismiss();
            activity.openWhatsapp();
        });
        addServiceItem(moreGrid, R.drawable.ic_bell, getString(R.string.enquiry), v -> {
            dialog.dismiss();
            activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_ENQUIRIES), getString(R.string.enquiry));
        });
        addServiceItem(moreGrid, R.drawable.ic_nav_explore, getString(R.string.materials), v -> {
            dialog.dismiss();
            activity.selectBottomNav(R.id.nav_explore);
        });
        addServiceItem(moreGrid, R.drawable.ic_service_about, getString(R.string.fees), v -> {
            dialog.dismiss();
            activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_FEES), getString(R.string.fees));
        });
        addServiceItem(moreGrid, R.drawable.ic_service_more, getString(R.string.homework), v -> {
            dialog.dismiss();
            activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_HOMEWORK), getString(R.string.homework));
        });
        dialog.setContentView(sheet);
        dialog.show();
    }

    private void loadHome(View root, LinearLayout coursesContainer, LinearLayout promotionsContainer,
                          View sliderSection, ViewPager2 sliderPager, LinearLayout sliderDots) {
        progressBar.setVisibility(View.VISIBLE);
        api.get("/home", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    progressBar.setVisibility(View.GONE);
                    JSONObject student = json.optJSONObject("student");
                    JSONObject company = json.optJSONObject("company");
                    TextView greeting = root.findViewById(R.id.tvGreeting);
                    TextView address = root.findViewById(R.id.tvAddress);
                    if (student != null) {
                        greeting.setText("Hi, " + student.optString("name", "Student"));
                        address.setText(student.optString("address", ""));
                        session.setStudentName(student.optString("name"));
                    } else if (company != null) {
                        greeting.setText(company.optString("name", "Dey Education"));
                    }
                    feeBanner.setVisibility(json.optBoolean("has_pending_fees") ? View.VISIBLE : View.GONE);
                    notificationDot.setVisibility(json.optInt("notices_count") > 0 ? View.VISIBLE : View.GONE);
                    bindSliders(sliderSection, sliderPager, sliderDots, json.optJSONArray("sliders"));
                    bindCourses(coursesContainer, json.optJSONArray("events"));
                    bindPromotions(promotionsContainer, json.optJSONArray("promotions"));
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

    private void bindSliders(View sliderSection, ViewPager2 pager, LinearLayout dotsContainer, JSONArray rows) {
        if (sliderSection == null || pager == null) {
            return;
        }
        List<JSONObject> items = new ArrayList<>();
        String baseUrl = session.getBaseUrl();
        if (rows != null) {
            for (int i = 0; i < rows.length(); i++) {
                JSONObject row = rows.optJSONObject(i);
                if (row == null) {
                    continue;
                }
                String imageUrl = UrlHelper.imageFromJson(baseUrl, row);
                if (imageUrl == null || imageUrl.isEmpty()) {
                    continue;
                }
                items.add(row);
            }
        }
        if (items.isEmpty()) {
            sliderSection.setVisibility(View.GONE);
            return;
        }
        sliderSection.setVisibility(View.VISIBLE);
        pager.setAdapter(new SliderAdapter(items, baseUrl));
        bindSliderDots(pager, dotsContainer, items.size());
    }

    private void bindSliderDots(ViewPager2 pager, LinearLayout dotsContainer, int count) {
        if (dotsContainer == null) {
            return;
        }
        dotsContainer.removeAllViews();
        if (count <= 1) {
            dotsContainer.setVisibility(View.GONE);
            return;
        }
        dotsContainer.setVisibility(View.VISIBLE);
        int dotSize = UiUtils.dp(requireContext(), 8);
        int dotMargin = UiUtils.dp(requireContext(), 4);
        List<View> dots = new ArrayList<>();
        for (int i = 0; i < count; i++) {
            View dot = new View(requireContext());
            LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(dotSize, dotSize);
            lp.setMargins(dotMargin, 0, dotMargin, 0);
            dot.setLayoutParams(lp);
            dot.setBackgroundResource(R.drawable.bg_notification_dot);
            dots.add(dot);
            dotsContainer.addView(dot);
        }
        updateSliderDot(dots, 0);
        if (sliderPageCallback != null) {
            pager.unregisterOnPageChangeCallback(sliderPageCallback);
        }
        sliderPageCallback = new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                updateSliderDot(dots, position);
            }
        };
        pager.registerOnPageChangeCallback(sliderPageCallback);
    }

    private void updateSliderDot(List<View> dots, int selected) {
        for (int i = 0; i < dots.size(); i++) {
            View dot = dots.get(i);
            dot.setAlpha(i == selected ? 1f : 0.35f);
            dot.setScaleX(i == selected ? 1.15f : 1f);
            dot.setScaleY(i == selected ? 1.15f : 1f);
        }
    }

    private void bindCourses(LinearLayout container, JSONArray rows) {
        container.removeAllViews();
        if (rows == null) return;
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            View card = inflater.inflate(R.layout.item_course_card, container, false);
            TextView title = card.findViewById(R.id.courseTitle);
            ImageView image = card.findViewById(R.id.courseImage);
            title.setText(row.optString("name", row.optString("title", "Course")));
            UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(session.getBaseUrl(), row), image, 12);
            container.addView(card);
        }
    }

    private void bindPromotions(LinearLayout container, JSONArray rows) {
        container.removeAllViews();
        if (rows == null) return;
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            View card = inflater.inflate(R.layout.item_promo_card, container, false);
            TextView title = card.findViewById(R.id.promoTitle);
            ImageView image = card.findViewById(R.id.promoImage);
            title.setText(row.optString("name", "Promotion"));
            UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(session.getBaseUrl(), row), image, 12);
            container.addView(card);
        }
    }

    private static class SliderAdapter extends RecyclerView.Adapter<SliderAdapter.Holder> {
        private final List<JSONObject> items;
        private final String baseUrl;

        SliderAdapter(List<JSONObject> items, String baseUrl) {
            this.items = items;
            this.baseUrl = baseUrl;
        }

        @NonNull
        @Override
        public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
            View view = LayoutInflater.from(parent.getContext())
                    .inflate(R.layout.item_slider_banner, parent, false);
            return new Holder(view);
        }

        @Override
        public void onBindViewHolder(@NonNull Holder holder, int position) {
            JSONObject row = items.get(position);
            String title = row.optString("name", row.optString("title", ""));
            if (title.isEmpty() || "null".equals(title)) {
                holder.title.setVisibility(View.GONE);
            } else {
                holder.title.setVisibility(View.VISIBLE);
                holder.title.setText(title);
            }
            UiUtils.loadImage(holder.image.getContext(),
                    UrlHelper.imageFromJson(baseUrl, row), holder.image, 12);
        }

        @Override
        public int getItemCount() {
            return items.size();
        }

        static class Holder extends RecyclerView.ViewHolder {
            final ImageView image;
            final TextView title;

            Holder(@NonNull View itemView) {
                super(itemView);
                image = itemView.findViewById(R.id.sliderImage);
                title = itemView.findViewById(R.id.sliderTitle);
            }
        }
    }
}
