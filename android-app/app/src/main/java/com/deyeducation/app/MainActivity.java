package com.deyeducation.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.MenuItem;
import android.view.View;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.activity.OnBackPressedCallback;
import androidx.annotation.NonNull;
import androidx.appcompat.app.ActionBarDrawerToggle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;
import androidx.fragment.app.Fragment;

import com.google.android.material.appbar.MaterialToolbar;
import com.google.android.material.badge.BadgeDrawable;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationView;

import org.json.JSONObject;

public class MainActivity extends AppCompatActivity implements NavigationView.OnNavigationItemSelectedListener {
    public static final String EXTRA_SCREEN = "screen";

    private DrawerLayout drawerLayout;
    private BottomNavigationView bottomNav;
    private SessionManager session;
    private ApiClient api;
    private String whatsappPhone = "";
    private ImageView navAvatar;
    private ImageButton toolbarProfileBtn;
    private TextView navUserName;
    private ActionBarDrawerToggle drawerToggle;
    private MaterialToolbar toolbar;
    private String rootTitle = "";
    private long lastBackPressTime = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        session = new SessionManager(this);
        api = new ApiClient(session);

        if (!session.isLoggedIn()) {
            startActivity(new Intent(this, LoginActivity.class));
            finish();
            return;
        }

        setContentView(R.layout.activity_main);
        drawerLayout = findViewById(R.id.drawerLayout);
        bottomNav = findViewById(R.id.bottomNav);
        toolbar = findViewById(R.id.toolbar);
        NavigationView navigationView = findViewById(R.id.navigationView);
        ImageButton profileBtn = findViewById(R.id.btnToolbarProfile);

        setSupportActionBar(toolbar);
        drawerToggle = new ActionBarDrawerToggle(
                this, drawerLayout, toolbar, R.string.menu, R.string.menu);
        drawerLayout.addDrawerListener(drawerToggle);
        drawerToggle.syncState();
        toolbar.setNavigationOnClickListener(v -> {
            if (getSupportFragmentManager().getBackStackEntryCount() > 0) {
                popBackStackIfPossible();
            } else {
                drawerLayout.openDrawer(GravityCompat.START);
            }
        });

        getSupportFragmentManager().addOnBackStackChangedListener(this::updateToolbarNavigation);

        navigationView.setNavigationItemSelectedListener(this);
        View navHeader = navigationView.getHeaderView(0);
        navUserName = navHeader.findViewById(R.id.navUserName);
        navAvatar = navHeader.findViewById(R.id.navAvatar);
        navUserName.setText(session.getStudentName());
        toolbarProfileBtn = profileBtn;
        loadNavProfileImage();

        profileBtn.setOnClickListener(v -> showFragment(new ProfileFragment(), getString(R.string.profile)));

        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                showFragment(new HomeFragment(), getString(R.string.home));
                return true;
            }
            if (id == R.id.nav_explore) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_MATERIALS), getString(R.string.materials));
                return true;
            }
            if (id == R.id.nav_notices) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_NOTICES), getString(R.string.notices));
                return true;
            }
            if (id == R.id.nav_gallery) {
                showFragment(new GalleryFragment(), getString(R.string.gallery));
                return true;
            }
            if (id == R.id.nav_profile) {
                showFragment(new ProfileFragment(), getString(R.string.profile));
                return true;
            }
            return false;
        });

        api.get("/company", false, new ApiClient.Callback() {
            @Override
            public void onSuccess(org.json.JSONObject json) {
                org.json.JSONObject company = json.optJSONObject("data");
                if (company != null) {
                    whatsappPhone = company.optString("ph1", company.optString("phone", ""));
                }
            }

            @Override
            public void onError(String message) {
            }
        });

        if (savedInstanceState == null) {
            String screen = getIntent().getStringExtra(EXTRA_SCREEN);
            if ("materials".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_MATERIALS), getString(R.string.materials));
                bottomNav.setSelectedItemId(R.id.nav_explore);
            } else if ("notices".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_NOTICES), getString(R.string.notices));
                bottomNav.setSelectedItemId(R.id.nav_notices);
            } else if ("gallery".equals(screen)) {
                showFragment(new GalleryFragment(), getString(R.string.gallery));
                bottomNav.setSelectedItemId(R.id.nav_gallery);
            } else if ("profile".equals(screen)) {
                showFragment(new ProfileFragment(), getString(R.string.profile));
                bottomNav.setSelectedItemId(R.id.nav_profile);
            } else if ("fees".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_FEES), getString(R.string.fees));
            } else if ("enquiry".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_ENQUIRIES), getString(R.string.enquiry));
            } else {
                showFragment(new HomeFragment(), getString(R.string.home));
                bottomNav.setSelectedItemId(R.id.nav_home);
            }
            refreshUnreadNoticesBadge();
        }

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
                    drawerLayout.closeDrawer(GravityCompat.START);
                } else if (popBackStackIfPossible()) {
                    // handled
                } else if (!isOnHomeTab()) {
                    goHome();
                } else {
                    long now = System.currentTimeMillis();
                    if (now - lastBackPressTime < 2000) {
                        setEnabled(false);
                        getOnBackPressedDispatcher().onBackPressed();
                    } else {
                        lastBackPressTime = now;
                        UiUtils.toast(MainActivity.this, getString(R.string.press_back_again));
                    }
                }
            }
        });
    }

    public ApiClient getApi() {
        return api;
    }

    public SessionManager getSession() {
        return session;
    }

    public void openWhatsapp() {
        if (whatsappPhone == null || whatsappPhone.isEmpty()) {
            UiUtils.toast(this, "WhatsApp number not available");
            return;
        }
        String digits = whatsappPhone.replaceAll("\\D+", "");
        startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/" + digits)));
    }

    public void showFragment(Fragment fragment, String title) {
        showFragment(fragment, title, false);
    }

    public void showFragment(Fragment fragment, String title, boolean addToBackStack) {
        setScreenTitle(title);
        if (!addToBackStack) {
            rootTitle = title;
        }
        var transaction = getSupportFragmentManager()
                .beginTransaction()
                .replace(R.id.fragmentContainer, fragment);
        if (addToBackStack) {
            transaction.addToBackStack(title);
        } else {
            getSupportFragmentManager().popBackStackImmediate(null,
                    androidx.fragment.app.FragmentManager.POP_BACK_STACK_INCLUSIVE);
        }
        transaction.commit();
        updateToolbarNavigation();
    }

    public void setScreenTitle(String title) {
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle(title);
        }
    }

    public boolean popBackStackIfPossible() {
        if (getSupportFragmentManager().getBackStackEntryCount() > 0) {
            getSupportFragmentManager().popBackStack();
            return true;
        }
        return false;
    }

    private boolean isOnHomeTab() {
        Fragment current = getSupportFragmentManager().findFragmentById(R.id.fragmentContainer);
        return current instanceof HomeFragment
                && getSupportFragmentManager().getBackStackEntryCount() == 0
                && bottomNav.getSelectedItemId() == R.id.nav_home;
    }

    private void goHome() {
        showFragment(new HomeFragment(), getString(R.string.home));
        bottomNav.setSelectedItemId(R.id.nav_home);
    }

    private void updateToolbarNavigation() {
        if (toolbar == null || drawerToggle == null) {
            return;
        }
        boolean hasBackStack = getSupportFragmentManager().getBackStackEntryCount() > 0;
        drawerToggle.setDrawerIndicatorEnabled(!hasBackStack);
        if (hasBackStack) {
            toolbar.setNavigationIcon(androidx.appcompat.R.drawable.abc_ic_ab_back_material);
            toolbar.setNavigationContentDescription(R.string.back);
        } else {
            drawerToggle.syncState();
            if (getSupportActionBar() != null && rootTitle != null && !rootTitle.isEmpty()) {
                getSupportActionBar().setTitle(rootTitle);
            }
        }
    }

    public void selectBottomNav(int itemId) {
        bottomNav.setSelectedItemId(itemId);
    }

    public void updateNoticesBadge(int count) {
        if (count > 0) {
            BadgeDrawable badge = bottomNav.getOrCreateBadge(R.id.nav_notices);
            badge.setVisible(true);
            badge.setNumber(Math.min(count, 99));
        } else {
            bottomNav.removeBadge(R.id.nav_notices);
        }
        Fragment current = getSupportFragmentManager().findFragmentById(R.id.fragmentContainer);
        if (current instanceof HomeFragment) {
            ((HomeFragment) current).updateNotificationDot(count);
        }
    }

    public void refreshUnreadNoticesBadge() {
        api.get("/home", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(org.json.JSONObject json) {
                runOnUiThread(() -> updateNoticesBadge(json.optInt("notices_count", 0)));
            }

            @Override
            public void onError(String message) {
            }
        });
    }

    public void refreshNavProfileImage(JSONObject student) {
        if (student == null) {
            loadNavProfileImage();
            return;
        }
        applyNavProfileImage(student);
    }

    private void loadNavProfileImage() {
        api.get("/me", true, new ApiClient.Callback() {
            @Override
            public void onSuccess(JSONObject json) {
                JSONObject student = json.optJSONObject("student");
                if (student == null) {
                    return;
                }
                runOnUiThread(() -> {
                    session.setStudentName(student.optString("name"));
                    if (navUserName != null) {
                        navUserName.setText(student.optString("name"));
                    }
                    applyNavProfileImage(student);
                });
            }

            @Override
            public void onError(String message) {
            }
        });
    }

    private void applyNavProfileImage(JSONObject student) {
        String imageUrl = UrlHelper.imageFromJson(session.getBaseUrl(), student);
        if (navAvatar != null) {
            UiUtils.loadImage(this, imageUrl, navAvatar, 28);
        }
        if (toolbarProfileBtn != null) {
            UiUtils.loadImage(this, imageUrl, toolbarProfileBtn, 20);
        }
    }

    @Override
    public boolean onNavigationItemSelected(@NonNull MenuItem item) {
        drawerLayout.closeDrawer(GravityCompat.START);
        int id = item.getItemId();
        if (id == R.id.drawer_home) {
            selectBottomNav(R.id.nav_home);
            return true;
        }
        if (id == R.id.drawer_notices) {
            selectBottomNav(R.id.nav_notices);
            return true;
        }
        if (id == R.id.drawer_materials) {
            selectBottomNav(R.id.nav_explore);
            return true;
        }
        if (id == R.id.drawer_fees) {
            showFragment(ListFragment.newInstance(ListFragment.TYPE_FEES), getString(R.string.fees));
            return true;
        }
        if (id == R.id.drawer_enquiry) {
            showFragment(ListFragment.newInstance(ListFragment.TYPE_ENQUIRIES), getString(R.string.enquiry));
            return true;
        }
        if (id == R.id.drawer_logout) {
            session.clear();
            startActivity(new Intent(this, LoginActivity.class));
            finish();
            return true;
        }
        return false;
    }
}
