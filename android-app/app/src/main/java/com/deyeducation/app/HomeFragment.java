package com.deyeducation.app;

import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.GridLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;
import androidx.viewpager2.widget.ViewPager2;

import com.google.android.material.bottomsheet.BottomSheetDialog;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

public class HomeFragment extends Fragment {
    private static final long PROMO_INTERVAL_MS = 4000L;

    private ApiClient api;
    private SessionManager session;
    private View progressBar;
    private SwipeRefreshLayout swipeRefresh;
    private TextView feeBanner;
    private View pollAlertSection;
    private LinearLayout pollAlertContainer;
    private View notificationDot;
    private ViewPager2 promoPager;
    private ViewPager2.OnPageChangeCallback promoPageCallback;
    private final Handler promoHandler = new Handler(Looper.getMainLooper());
    private boolean promoAutoScrollEnabled;
    private boolean promoUserDragging;
    private final Runnable promoAutoTick = new Runnable() {
        @Override
        public void run() {
            if (!promoAutoScrollEnabled || promoPager == null || !isResumed()) {
                return;
            }
            RecyclerView.Adapter<?> adapter = promoPager.getAdapter();
            if (adapter == null || adapter.getItemCount() <= 1) {
                return;
            }
            int next = (promoPager.getCurrentItem() + 1) % adapter.getItemCount();
            promoPager.setCurrentItem(next, true);
            promoHandler.postDelayed(this, PROMO_INTERVAL_MS);
        }
    };

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
        swipeRefresh = view.findViewById(R.id.swipeRefresh);
        feeBanner = view.findViewById(R.id.feeAlertBanner);
        pollAlertSection = view.findViewById(R.id.pollAlertSection);
        pollAlertContainer = view.findViewById(R.id.pollAlertContainer);
        notificationDot = view.findViewById(R.id.notificationDot);
        GridLayout serviceGrid = view.findViewById(R.id.serviceGrid);
        LinearLayout coursesContainer = view.findViewById(R.id.coursesContainer);
        View promoSection = view.findViewById(R.id.promoSection);
        promoPager = view.findViewById(R.id.promoPager);
        LinearLayout promoDots = view.findViewById(R.id.promoDots);

        addServiceItem(serviceGrid, R.drawable.ic_menu_fees, getString(R.string.academy_details), v ->
                activity.showFragment(
                        AboutContentFragment.newInstance(AboutContentFragment.MODE_ACADEMY),
                        getString(R.string.academy_details),
                        true));
        addServiceItem(serviceGrid, R.drawable.ic_menu_about, getString(R.string.about_us), v ->
                activity.showFragment(
                        AboutContentFragment.newInstance(AboutContentFragment.MODE_ABOUT),
                        getString(R.string.about_us_title),
                        true));
        addServiceItem(serviceGrid, R.drawable.ic_menu_gallery, getString(R.string.gallery), v ->
                activity.selectBottomNav(R.id.nav_gallery));
        addServiceItem(serviceGrid, R.drawable.ic_menu_more, getString(R.string.more), v -> showMoreSheet(activity));

        view.findViewById(R.id.btnNotifications).setOnClickListener(v ->
                activity.selectBottomNav(R.id.nav_notices));
        view.findViewById(R.id.btnWhatsapp).setOnClickListener(v -> activity.openWhatsapp());

        UiUtils.setupColorfulSwipeRefresh(swipeRefresh);
        swipeRefresh.setOnRefreshListener(() -> loadHome(view, coursesContainer, promoSection, promoDots));
        swipeRefresh.setOnChildScrollUpCallback((parent, child) ->
                view.findViewById(R.id.homeScroll).canScrollVertically(-1));

        loadHome(view, coursesContainer, promoSection, promoDots);
    }

    @Override
    public void onResume() {
        super.onResume();
        if (promoPager != null) {
            promoPager.post(this::startPromoAutoScroll);
        }
        if (getActivity() instanceof MainActivity) {
            ((MainActivity) getActivity()).setScreenTitle(getString(R.string.home));
            ((MainActivity) getActivity()).refreshUnreadNoticesBadge();
        }
    }

    public void updateNotificationDot(int count) {
        if (notificationDot != null) {
            notificationDot.setVisibility(count > 0 ? View.VISIBLE : View.GONE);
        }
    }

    @Override
    public void onPause() {
        stopPromoAutoScroll();
        super.onPause();
    }

    @Override
    public void onDestroyView() {
        stopPromoAutoScroll();
        if (promoPager != null && promoPageCallback != null) {
            promoPager.unregisterOnPageChangeCallback(promoPageCallback);
        }
        promoPageCallback = null;
        promoPager = null;
        super.onDestroyView();
    }

    private void startPromoAutoScroll() {
        stopPromoAutoScroll();
        if (promoPager == null || promoPager.getAdapter() == null) {
            return;
        }
        if (promoPager.getAdapter().getItemCount() <= 1) {
            return;
        }
        promoAutoScrollEnabled = true;
        promoHandler.postDelayed(promoAutoTick, PROMO_INTERVAL_MS);
    }

    private void stopPromoAutoScroll() {
        promoAutoScrollEnabled = false;
        promoHandler.removeCallbacks(promoAutoTick);
    }

    private void addServiceItem(GridLayout grid, int menuIconRes, String label,
                                View.OnClickListener click) {
        View item = LayoutInflater.from(requireContext()).inflate(R.layout.item_service_grid, grid, false);
        View circle = item.findViewById(R.id.serviceCircle);
        circle.setBackground(null);
        ImageView icon = item.findViewById(R.id.serviceIcon);
        int size = UiUtils.dp(requireContext(), 56);
        ViewGroup.LayoutParams circleParams = circle.getLayoutParams();
        circleParams.width = size;
        circleParams.height = size;
        circle.setLayoutParams(circleParams);
        ViewGroup.LayoutParams iconParams = icon.getLayoutParams();
        iconParams.width = size;
        iconParams.height = size;
        icon.setLayoutParams(iconParams);
        icon.setImageResource(menuIconRes);
        icon.setColorFilter(null);
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
        addServiceItem(moreGrid, R.drawable.ic_menu_gallery, getString(R.string.gallery), v -> {
            dialog.dismiss();
            activity.selectBottomNav(R.id.nav_gallery);
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_fees, getString(R.string.courses), v -> {
            dialog.dismiss();
            activity.showFragment(new CoursesGridFragment(), getString(R.string.courses), true);
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_whatsapp, getString(R.string.contact), v -> {
            dialog.dismiss();
            activity.openWhatsapp();
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_enquiry, getString(R.string.enquiry), v -> {
            dialog.dismiss();
            activity.showFragment(ListFragment.newInstance(ListFragment.TYPE_ENQUIRIES), getString(R.string.enquiry));
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_materials, getString(R.string.materials), v -> {
            dialog.dismiss();
            activity.selectBottomNav(R.id.nav_explore);
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_fees, getString(R.string.fees), v -> {
            dialog.dismiss();
            activity.showFragment(new FeesFragment(), getString(R.string.fees));
        });
        addServiceItem(moreGrid, R.drawable.ic_menu_poll, getString(R.string.polls), v -> {
            dialog.dismiss();
            activity.showFragment(new PollsFragment(), getString(R.string.polls));
        });
        dialog.setContentView(sheet);
        dialog.show();
    }

    private void loadHome(View root, LinearLayout coursesContainer, View promoSection, LinearLayout promoDots) {
        if (!swipeRefresh.isRefreshing()) {
            UiUtils.setLoaderVisible(progressBar, true);
        }
        api.get("/home", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    JSONObject student = json.optJSONObject("student");
                    JSONObject company = json.optJSONObject("company");
                    TextView greeting = root.findViewById(R.id.tvGreeting);
                    TextView address = root.findViewById(R.id.tvAddress);
                    if (student != null) {
                        greeting.setText("Hey, " + student.optString("name", "Student") + " 👋");
                        address.setText(student.optString("address", ""));
                        session.setStudentName(student.optString("name"));
                    } else if (company != null) {
                        greeting.setText(company.optString("name", "Dey Education"));
                    }
                    feeBanner.setVisibility(json.optBoolean("has_pending_fees") ? View.VISIBLE : View.GONE);
                    notificationDot.setVisibility(json.optInt("notices_count") > 0 ? View.VISIBLE : View.GONE);
                    bindUnvotedPolls(json.optJSONArray("unvoted_polls"));
                    bindCourses(coursesContainer, json.optJSONArray("events"));
                    bindPromotions(promoSection, promoDots, json.optJSONArray("promotions"));
                });
            }

            @Override
            public void onError(String message) {
                if (!isAdded()) return;
                requireActivity().runOnUiThread(() -> {
                    UiUtils.setLoaderVisible(progressBar, false);
                    swipeRefresh.setRefreshing(false);
                    UiUtils.toast(requireContext(), message);
                });
            }
        });
    }

    private void bindUnvotedPolls(@Nullable JSONArray rows) {
        if (pollAlertSection == null || pollAlertContainer == null) {
            return;
        }
        pollAlertContainer.removeAllViews();
        if (rows == null || rows.length() == 0) {
            pollAlertSection.setVisibility(View.GONE);
            return;
        }
        pollAlertSection.setVisibility(View.VISIBLE);
        MainActivity activity = (MainActivity) requireActivity();
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) {
                continue;
            }
            int pollId = row.optInt("id", 0);
            String question = row.optString("question", getString(R.string.polls));
            View item = inflater.inflate(R.layout.item_home_poll_alert, pollAlertContainer, false);
            ((TextView) item.findViewById(R.id.tvPollQuestion)).setText(question);
            item.setOnClickListener(v -> activity.showFragment(
                    PollDetailFragment.newInstance(pollId, question),
                    question,
                    true));
            pollAlertContainer.addView(item);
        }
    }

    private void bindPromotions(View promoSection, LinearLayout dotsContainer, JSONArray rows) {
        if (promoSection == null || promoPager == null) {
            return;
        }
        stopPromoAutoScroll();
        List<JSONObject> items = new ArrayList<>();
        String baseUrl = session.getBaseUrl();
        if (rows != null) {
            for (int i = 0; i < rows.length(); i++) {
                JSONObject row = rows.optJSONObject(i);
                if (row != null) {
                    items.add(row);
                }
            }
        }
        if (items.isEmpty()) {
            promoSection.setVisibility(View.GONE);
            return;
        }
        promoSection.setVisibility(View.VISIBLE);
        promoPager.setOffscreenPageLimit(Math.min(items.size() - 1, 3));
        promoPager.setAdapter(new PromoAdapter(items, baseUrl));
        bindPromoDots(promoPager, dotsContainer, items.size());
        promoPager.post(this::startPromoAutoScroll);
    }

    private void bindPromoDots(ViewPager2 pager, LinearLayout dotsContainer, int count) {
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
        updatePromoDot(dots, 0);
        if (promoPageCallback != null) {
            pager.unregisterOnPageChangeCallback(promoPageCallback);
        }
        promoPageCallback = new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                updatePromoDot(dots, position);
            }

            @Override
            public void onPageScrollStateChanged(int state) {
                if (state == ViewPager2.SCROLL_STATE_DRAGGING) {
                    promoUserDragging = true;
                    stopPromoAutoScroll();
                } else if (state == ViewPager2.SCROLL_STATE_IDLE && promoUserDragging) {
                    promoUserDragging = false;
                    startPromoAutoScroll();
                }
            }
        };
        pager.registerOnPageChangeCallback(promoPageCallback);
    }

    private void updatePromoDot(List<View> dots, int selected) {
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
        MainActivity activity = (MainActivity) requireActivity();
        LayoutInflater inflater = LayoutInflater.from(requireContext());
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            if (row == null) continue;
            View card = inflater.inflate(R.layout.item_course_card, container, false);
            TextView title = card.findViewById(R.id.courseTitle);
            ImageView image = card.findViewById(R.id.courseImage);
            String courseName = row.optString("name", row.optString("title", "Course"));
            title.setText(courseName);
            int eventId = row.optInt("id", 0);
            UiUtils.loadImage(requireContext(), UrlHelper.imageFromJson(session.getBaseUrl(), row), image, 12);
            card.setOnClickListener(v -> {
                if (eventId > 0) {
                    activity.showFragment(
                            CourseDetailFragment.newInstance(eventId, courseName),
                            courseName,
                            true);
                }
            });
            container.addView(card);
        }
    }

    private static class PromoAdapter extends RecyclerView.Adapter<PromoAdapter.Holder> {
        private final List<JSONObject> items;
        private final String baseUrl;

        PromoAdapter(List<JSONObject> items, String baseUrl) {
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
