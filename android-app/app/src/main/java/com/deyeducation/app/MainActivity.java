package com.deyeducation.app;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.MenuItem;
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
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationView;

public class MainActivity extends AppCompatActivity implements NavigationView.OnNavigationItemSelectedListener {
    public static final String EXTRA_SCREEN = "screen";

    private DrawerLayout drawerLayout;
    private BottomNavigationView bottomNav;
    private SessionManager session;
    private ApiClient api;
    private String whatsappPhone = "";

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
        MaterialToolbar toolbar = findViewById(R.id.toolbar);
        NavigationView navigationView = findViewById(R.id.navigationView);
        ImageButton profileBtn = findViewById(R.id.btnToolbarProfile);

        setSupportActionBar(toolbar);
        ActionBarDrawerToggle toggle = new ActionBarDrawerToggle(
                this, drawerLayout, toolbar, R.string.menu, R.string.menu);
        drawerLayout.addDrawerListener(toggle);
        toggle.syncState();

        navigationView.setNavigationItemSelectedListener(this);
        TextView navUserName = navigationView.getHeaderView(0).findViewById(R.id.navUserName);
        navUserName.setText(session.getStudentName());

        profileBtn.setOnClickListener(v -> showFragment(new ProfileFragment(), getString(R.string.profile)));

        bottomNav.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                showFragment(new HomeFragment(), getString(R.string.home));
                return true;
            }
            if (id == R.id.nav_explore) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_MATERIALS), getString(R.string.explore));
                return true;
            }
            if (id == R.id.nav_notices) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_NOTICES), getString(R.string.notices));
                return true;
            }
            if (id == R.id.nav_gallery) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_GALLERY), getString(R.string.gallery));
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
                showFragment(ListFragment.newInstance(ListFragment.TYPE_MATERIALS), getString(R.string.explore));
                bottomNav.setSelectedItemId(R.id.nav_explore);
            } else if ("notices".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_NOTICES), getString(R.string.notices));
                bottomNav.setSelectedItemId(R.id.nav_notices);
            } else if ("gallery".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_GALLERY), getString(R.string.gallery));
                bottomNav.setSelectedItemId(R.id.nav_gallery);
            } else if ("profile".equals(screen)) {
                showFragment(new ProfileFragment(), getString(R.string.profile));
                bottomNav.setSelectedItemId(R.id.nav_profile);
            } else if ("fees".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_FEES), getString(R.string.fees));
            } else if ("homework".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_HOMEWORK), getString(R.string.homework));
            } else if ("enquiry".equals(screen)) {
                showFragment(ListFragment.newInstance(ListFragment.TYPE_ENQUIRIES), getString(R.string.enquiry));
            } else {
                showFragment(new HomeFragment(), getString(R.string.home));
                bottomNav.setSelectedItemId(R.id.nav_home);
            }
        }

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (drawerLayout.isDrawerOpen(GravityCompat.START)) {
                    drawerLayout.closeDrawer(GravityCompat.START);
                } else {
                    setEnabled(false);
                    getOnBackPressedDispatcher().onBackPressed();
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
        if (getSupportActionBar() != null) {
            getSupportActionBar().setTitle(title);
        }
        getSupportFragmentManager()
                .beginTransaction()
                .replace(R.id.fragmentContainer, fragment)
                .commit();
    }

    public void selectBottomNav(int itemId) {
        bottomNav.setSelectedItemId(itemId);
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
        if (id == R.id.drawer_homework) {
            showFragment(ListFragment.newInstance(ListFragment.TYPE_HOMEWORK), getString(R.string.homework));
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
