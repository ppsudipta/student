package com.deyeducation.app;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.graphics.Typeface;
import android.net.Uri;
import android.os.Bundle;
import android.text.InputType;
import android.view.Gravity;
import android.view.View;
import android.webkit.WebView;
import android.widget.Button;
import android.widget.EditText;
import android.widget.FrameLayout;
import android.widget.HorizontalScrollView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends Activity {
    private static final String DEFAULT_BASE_URL = "http://10.0.2.2/admin/laravel-api/public/api";
    private static final int PRIMARY = Color.rgb(204, 102, 0);
    private static final int PRIMARY_DARK = Color.rgb(146, 64, 14);
    private static final int BG = Color.rgb(247, 241, 234);
    private static final int TEXT = Color.rgb(31, 41, 55);
    private static final int MUTED = Color.rgb(107, 114, 128);

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private LinearLayout root;
    private SharedPreferences prefs;
    private String baseUrl;
    private String token;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        prefs = getSharedPreferences("dey_app", MODE_PRIVATE);
        baseUrl = prefs.getString("base_url", DEFAULT_BASE_URL);
        token = prefs.getString("access_token", "");
        if (token.isEmpty()) {
            showLogin();
        } else {
            showDashboard();
        }
    }

    private void setRoot() {
        root = new LinearLayout(this);
        root.setOrientation(LinearLayout.VERTICAL);
        root.setBackgroundColor(BG);
        setContentView(root);
    }

    private void showLogin() {
        setRoot();
        TextView hero = text("Dey Education", 28, Color.WHITE, true);
        hero.setGravity(Gravity.CENTER);
        TextView sub = text("Student learning app", 14, Color.WHITE, false);
        sub.setGravity(Gravity.CENTER);

        LinearLayout header = box(PRIMARY, 0, 0);
        header.setGravity(Gravity.CENTER);
        header.setPadding(dp(24), dp(52), dp(24), dp(44));
        header.addView(hero);
        header.addView(sub);
        root.addView(header, new LinearLayout.LayoutParams(-1, dp(190)));

        LinearLayout form = box(Color.WHITE, dp(28), 0);
        form.setPadding(dp(24), dp(28), dp(24), dp(24));
        root.addView(form, new LinearLayout.LayoutParams(-1, -1));

        EditText base = input("API Base URL");
        base.setText(baseUrl);
        EditText mobile = input("Mobile Number");
        mobile.setInputType(InputType.TYPE_CLASS_PHONE);
        mobile.setText("9038495748");
        EditText password = input("Password");
        password.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_VARIATION_PASSWORD);
        password.setText("12345677");

        form.addView(label("API Server"));
        form.addView(base);
        form.addView(label("Mobile Number"));
        form.addView(mobile);
        form.addView(label("Password"));
        form.addView(password);

        Button login = primaryButton("Sign In");
        form.addView(login);
        form.addView(text("For emulator use 10.0.2.2. For a real phone use your Mac LAN IP, for example http://192.168.x.x/admin/laravel-api/public/api", 12, MUTED, false));

        login.setOnClickListener(v -> {
            baseUrl = base.getText().toString().trim();
            prefs.edit().putString("base_url", baseUrl).apply();
            JSONObject body = new JSONObject();
            try {
                body.put("mobile_number", mobile.getText().toString().trim());
                body.put("password", password.getText().toString());
            } catch (Exception ignored) {
            }
            request("POST", "/login", body, false, json -> {
                token = json.optString("access_token");
                if (token.isEmpty()) {
                    toast("Login failed");
                    return;
                }
                prefs.edit().putString("access_token", token).apply();
                showDashboard();
            });
        });
    }

    private void showDashboard() {
        setRoot();
        root.addView(topBar("Dashboard"));
        ScrollView scroll = new ScrollView(this);
        LinearLayout content = box(BG, 0, 0);
        content.setPadding(dp(18), dp(16), dp(18), dp(86));
        scroll.addView(content);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));
        root.addView(nav());

        content.addView(text("Loading dashboard...", 15, MUTED, false));
        request("GET", "/home", null, true, json -> {
            content.removeAllViews();
            JSONObject student = json.optJSONObject("student");
            JSONObject company = json.optJSONObject("company");
            content.addView(text(company != null ? company.optString("name", "Dey Education") : "Dey Education", 22, TEXT, true));
            if (student != null) {
                content.addView(text("Hi, " + student.optString("name", "Student"), 16, MUTED, false));
            }
            content.addView(section("Quick Actions"));
            LinearLayout grid = new LinearLayout(this);
            grid.setOrientation(LinearLayout.VERTICAL);
            content.addView(grid);
            grid.addView(actionRow("Materials", "Study files and app-only video playback", v -> showList("Materials", "/materials", "material_title", true)));
            grid.addView(actionRow("Fees", "Dues, paid fees, and payment history", v -> showFees()));
            grid.addView(actionRow("Progress", "Marks and teacher feedback", v -> showList("Progress", "/progress", "subject", true)));
            grid.addView(actionRow("Homework", "Assignments and submissions", v -> showList("Homework", "/homework", "title", true)));
            grid.addView(actionRow("Notices", "Messages from admin", v -> showList("Notices", "/notices", "notice_content", true)));
            grid.addView(actionRow("Attendance", "Present and absent records", v -> showList("Attendance", "/attendance", "attendance_title", true)));
            grid.addView(actionRow("Enquiries", "Ask questions and see replies", v -> showEnquiries()));
            grid.addView(actionRow("Polls", "Vote in active class polls", v -> showPolls()));
            grid.addView(actionRow("Courses", "Explore available courses", v -> showList("Courses", "/courses", "name", false)));
            grid.addView(actionRow("Teachers", "Teacher profiles and subjects", v -> showList("Teachers", "/teachers", "name", false)));
            grid.addView(actionRow("Gallery", "Promotional and gallery photos", v -> showList("Gallery", "/gallery", "name", false)));

            addHorizontal(content, "Events", json.optJSONArray("events"), "name");
            addHorizontal(content, "Promotions", json.optJSONArray("promotions"), "name");
        });
    }

    private void showFees() {
        showScreen("Fees", "/fees", json -> {
            JSONObject summary = json.optJSONObject("summary");
            LinearLayout content = currentContent();
            content.addView(section("Summary"));
            if (summary != null) {
                content.addView(card("Total Fees", summary.optString("total_fees", "0")));
                content.addView(card("Paid Fees", summary.optString("paid_fees", "0")));
                content.addView(card("Due Fees", summary.optString("due_fees", "0")));
            }
            JSONObject payments = json.optJSONObject("payments");
            JSONArray rows = payments != null ? payments.optJSONArray("data") : new JSONArray();
            addRows(content, rows, "payment_reason", "amount", false);
        });
    }

    private void showList(String title, String path, String titleKey, boolean auth) {
        showScreen(title, path + "?per_page=20", auth, json -> {
            LinearLayout content = currentContent();
            Object container = json.opt("data");
            JSONArray rows;
            if (container instanceof JSONObject) {
                rows = ((JSONObject) container).optJSONArray("data");
            } else {
                rows = container instanceof JSONArray ? (JSONArray) container : new JSONArray();
            }
            addRows(content, rows, titleKey, "material_description", true);
        });
    }

    private LinearLayout screenContent;

    private interface JsonHandler {
        void handle(JSONObject json) throws Exception;
    }

    private void showScreen(String title, String path, JsonHandler handler) {
        showScreen(title, path, true, handler);
    }

    private void showScreen(String title, String path, boolean auth, JsonHandler handler) {
        setRoot();
        root.addView(topBar(title));
        ScrollView scroll = new ScrollView(this);
        screenContent = box(BG, 0, 0);
        screenContent.setPadding(dp(18), dp(16), dp(18), dp(86));
        scroll.addView(screenContent);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));
        root.addView(nav());
        screenContent.addView(text("Loading...", 15, MUTED, false));
        request("GET", path, null, auth, json -> {
            screenContent.removeAllViews();
            handler.handle(json);
        });
    }

    private LinearLayout currentContent() {
        return screenContent;
    }

    private void addRows(LinearLayout content, JSONArray rows, String titleKey, String subKey, boolean openVideo) {
        if (rows == null || rows.length() == 0) {
            content.addView(text("No records found.", 15, MUTED, false));
            return;
        }
        for (int i = 0; i < rows.length(); i++) {
            JSONObject item = rows.optJSONObject(i);
            if (item == null) continue;
            LinearLayout card = cardBox();
            card.addView(text(first(item, titleKey, "name", "title", "subject", "id"), 16, TEXT, true));
            card.addView(text(first(item, subKey, "details", "description", "message", "status", "date", "upload_date"), 13, MUTED, false));
            JSONObject playback = item.optJSONObject("playback");
            if (openVideo && playback != null && playback.optString("embed_url").length() > 0) {
                Button play = secondaryButton("Play Video");
                play.setOnClickListener(v -> showVideo(playback.optString("embed_url")));
                card.addView(play);
            }
            content.addView(card);
        }
    }

    private void showEnquiries() {
        showScreen("Enquiries", "/enquiries?per_page=20", true, json -> {
            LinearLayout content = currentContent();
            Button create = primaryButton("New Enquiry");
            create.setOnClickListener(v -> showEnquiryForm());
            content.addView(create);
            JSONObject data = json.optJSONObject("data");
            JSONArray rows = data != null ? data.optJSONArray("data") : new JSONArray();
            addRows(content, rows, "subject", "message", false);
        });
    }

    private void showEnquiryForm() {
        setRoot();
        root.addView(topBar("New Enquiry"));
        ScrollView scroll = new ScrollView(this);
        LinearLayout form = box(BG, 0, 0);
        form.setPadding(dp(18), dp(16), dp(18), dp(86));
        scroll.addView(form);
        root.addView(scroll, new LinearLayout.LayoutParams(-1, 0, 1));
        root.addView(nav());

        EditText type = input("Type");
        type.setText("general");
        EditText subject = input("Subject");
        EditText message = input("Message");
        message.setSingleLine(false);
        message.setMinLines(5);
        form.addView(label("Enquiry Type"));
        form.addView(type);
        form.addView(label("Subject"));
        form.addView(subject);
        form.addView(label("Message"));
        form.addView(message);
        Button submit = primaryButton("Submit Enquiry");
        form.addView(submit);
        submit.setOnClickListener(v -> {
            JSONObject body = new JSONObject();
            try {
                body.put("enquiry_type", type.getText().toString().trim());
                body.put("subject", subject.getText().toString().trim());
                body.put("message", message.getText().toString().trim());
            } catch (Exception ignored) {
            }
            request("POST", "/enquiries", body, true, json -> {
                toast("Enquiry submitted");
                showEnquiries();
            });
        });
    }

    private void showPolls() {
        showScreen("Polls", "/polls", true, json -> {
            LinearLayout content = currentContent();
            JSONArray rows = json.optJSONArray("data");
            if (rows == null || rows.length() == 0) {
                content.addView(text("No active polls.", 15, MUTED, false));
                return;
            }
            for (int i = 0; i < rows.length(); i++) {
                JSONObject wrapper = rows.optJSONObject(i);
                if (wrapper == null) continue;
                JSONObject poll = wrapper.optJSONObject("poll");
                JSONArray options = wrapper.optJSONArray("options");
                LinearLayout card = cardBox();
                card.addView(text(poll != null ? poll.optString("question", "Poll") : "Poll", 16, TEXT, true));
                if (options != null) {
                    for (int j = 0; j < options.length(); j++) {
                        JSONObject option = options.optJSONObject(j);
                        if (option == null) continue;
                        Button vote = secondaryButton(option.optString("option_text", "Option"));
                        int pollId = poll != null ? poll.optInt("id") : 0;
                        int optionId = option.optInt("id");
                        vote.setOnClickListener(v -> votePoll(pollId, optionId));
                        card.addView(vote);
                    }
                }
                content.addView(card);
            }
        });
    }

    private void votePoll(int pollId, int optionId) {
        JSONObject body = new JSONObject();
        try {
            body.put("option_id", optionId);
        } catch (Exception ignored) {
        }
        request("POST", "/polls/" + pollId + "/vote", body, true, json -> {
            toast("Vote saved");
            showPolls();
        });
    }

    private void showVideo(String embedUrl) {
        setRoot();
        root.addView(topBar("Video"));
        WebView web = new WebView(this);
        web.getSettings().setJavaScriptEnabled(true);
        web.getSettings().setDomStorageEnabled(true);
        web.loadUrl(embedUrl);
        root.addView(web, new LinearLayout.LayoutParams(-1, 0, 1));
        root.addView(nav());
    }

    private void addHorizontal(LinearLayout parent, String title, JSONArray rows, String key) {
        parent.addView(section(title));
        if (rows == null || rows.length() == 0) {
            parent.addView(text("No " + title.toLowerCase() + " yet.", 13, MUTED, false));
            return;
        }
        HorizontalScrollView scroller = new HorizontalScrollView(this);
        LinearLayout rail = new LinearLayout(this);
        rail.setOrientation(LinearLayout.HORIZONTAL);
        scroller.addView(rail);
        for (int i = 0; i < rows.length(); i++) {
            JSONObject row = rows.optJSONObject(i);
            LinearLayout item = box(Color.WHITE, dp(8), dp(10));
            item.setPadding(dp(14), dp(12), dp(14), dp(12));
            item.addView(text(row != null ? row.optString(key, "Item") : "Item", 14, TEXT, true));
            LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(dp(190), dp(96));
            lp.setMargins(0, 0, dp(10), 0);
            rail.addView(item, lp);
        }
        parent.addView(scroller);
    }

    private LinearLayout topBar(String title) {
        LinearLayout bar = new LinearLayout(this);
        bar.setOrientation(LinearLayout.HORIZONTAL);
        bar.setGravity(Gravity.CENTER_VERTICAL);
        bar.setPadding(dp(16), dp(24), dp(16), dp(12));
        bar.setBackgroundColor(PRIMARY);
        TextView tv = text(title, 20, Color.WHITE, true);
        bar.addView(tv, new LinearLayout.LayoutParams(0, -2, 1));
        Button logout = new Button(this);
        logout.setText("Logout");
        logout.setTextColor(PRIMARY);
        logout.setBackgroundColor(Color.WHITE);
        logout.setOnClickListener(v -> {
            prefs.edit().remove("access_token").apply();
            token = "";
            showLogin();
        });
        bar.addView(logout, new LinearLayout.LayoutParams(dp(104), dp(42)));
        return bar;
    }

    private LinearLayout nav() {
        LinearLayout nav = new LinearLayout(this);
        nav.setOrientation(LinearLayout.HORIZONTAL);
        nav.setGravity(Gravity.CENTER);
        nav.setPadding(dp(8), dp(8), dp(8), dp(8));
        nav.setBackgroundColor(Color.WHITE);
        nav.addView(navButton("Home", v -> showDashboard()));
        nav.addView(navButton("Materials", v -> showList("Materials", "/materials", "material_title", true)));
        nav.addView(navButton("Fees", v -> showFees()));
        nav.addView(navButton("Ask", v -> showEnquiries()));
        nav.addView(navButton("Profile", v -> showScreen("Profile", "/me", json -> {
            JSONObject s = json.optJSONObject("student");
            if (s != null) {
                currentContent().addView(card("Name", s.optString("name")));
                currentContent().addView(card("Mobile", s.optString("mobile_number")));
                currentContent().addView(card("Class", s.optString("class")));
                currentContent().addView(card("Session", s.optString("session")));
            }
        })));
        return nav;
    }

    private Button navButton(String text, View.OnClickListener listener) {
        Button b = new Button(this);
        b.setText(text);
        b.setTextColor(PRIMARY_DARK);
        b.setTextSize(12);
        b.setAllCaps(false);
        b.setOnClickListener(listener);
        b.setBackgroundColor(Color.TRANSPARENT);
        navLp(b);
        return b;
    }

    private void navLp(Button b) {
        b.setLayoutParams(new LinearLayout.LayoutParams(0, dp(52), 1));
    }

    private LinearLayout actionRow(String title, String body, View.OnClickListener click) {
        LinearLayout row = cardBox();
        row.setOnClickListener(click);
        row.addView(text(title, 16, TEXT, true));
        row.addView(text(body, 13, MUTED, false));
        return row;
    }

    private TextView section(String value) {
        TextView tv = text(value, 18, TEXT, true);
        tv.setPadding(0, dp(18), 0, dp(8));
        return tv;
    }

    private TextView label(String value) {
        TextView tv = text(value, 13, MUTED, true);
        tv.setPadding(0, dp(14), 0, dp(6));
        return tv;
    }

    private EditText input(String hint) {
        EditText e = new EditText(this);
        e.setHint(hint);
        e.setSingleLine(true);
        e.setTextColor(TEXT);
        e.setHintTextColor(MUTED);
        e.setTextSize(15);
        e.setPadding(dp(14), 0, dp(14), 0);
        e.setBackgroundColor(Color.rgb(248, 250, 252));
        e.setLayoutParams(new LinearLayout.LayoutParams(-1, dp(52)));
        return e;
    }

    private Button primaryButton(String value) {
        Button b = new Button(this);
        b.setText(value);
        b.setTextColor(Color.WHITE);
        b.setTextSize(16);
        b.setTypeface(Typeface.DEFAULT_BOLD);
        b.setAllCaps(false);
        b.setBackgroundColor(PRIMARY);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(-1, dp(54));
        lp.setMargins(0, dp(24), 0, dp(12));
        b.setLayoutParams(lp);
        return b;
    }

    private Button secondaryButton(String value) {
        Button b = new Button(this);
        b.setText(value);
        b.setTextColor(PRIMARY_DARK);
        b.setAllCaps(false);
        b.setBackgroundColor(Color.rgb(255, 247, 237));
        b.setLayoutParams(new LinearLayout.LayoutParams(-1, dp(44)));
        return b;
    }

    private LinearLayout cardBox() {
        LinearLayout card = box(Color.WHITE, dp(8), dp(10));
        card.setPadding(dp(16), dp(14), dp(16), dp(14));
        return card;
    }

    private LinearLayout card(String title, String body) {
        LinearLayout c = cardBox();
        c.addView(text(title, 13, MUTED, false));
        c.addView(text(body == null ? "" : body, 18, TEXT, true));
        return c;
    }

    private LinearLayout box(int color, int radius, int marginBottom) {
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        android.graphics.drawable.GradientDrawable bg = new android.graphics.drawable.GradientDrawable();
        bg.setColor(color);
        bg.setCornerRadius(radius);
        layout.setBackground(bg);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(-1, -2);
        lp.setMargins(0, 0, 0, marginBottom);
        layout.setLayoutParams(lp);
        return layout;
    }

    private TextView text(String value, int sp, int color, boolean bold) {
        TextView tv = new TextView(this);
        tv.setText(value == null ? "" : value);
        tv.setTextSize(sp);
        tv.setTextColor(color);
        tv.setLineSpacing(dp(2), 1.0f);
        if (bold) tv.setTypeface(Typeface.DEFAULT_BOLD);
        return tv;
    }

    private String first(JSONObject obj, String... keys) {
        for (String key : keys) {
            if (obj.has(key) && !obj.optString(key).isEmpty() && !"null".equals(obj.optString(key))) {
                String value = obj.optString(key);
                return value.replaceAll("<[^>]*>", "").replace("&nbsp;", " ").trim();
            }
        }
        return "";
    }

    private interface Callback {
        void done(JSONObject json) throws Exception;
    }

    private void request(String method, String path, JSONObject body, boolean auth, Callback callback) {
        ProgressBar progress = new ProgressBar(this);
        runOnUiThread(() -> root.addView(progress, new LinearLayout.LayoutParams(-1, dp(4))));
        io.execute(() -> {
            try {
                URL url = new URL(baseUrl + path);
                HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                conn.setRequestMethod(method);
                conn.setConnectTimeout(12000);
                conn.setReadTimeout(12000);
                conn.setRequestProperty("Accept", "application/json");
                if (auth && token != null && !token.isEmpty()) {
                    conn.setRequestProperty("Authorization", "Bearer " + token);
                }
                if (body != null) {
                    conn.setRequestProperty("Content-Type", "application/json");
                    conn.setDoOutput(true);
                    try (OutputStream os = conn.getOutputStream()) {
                        os.write(body.toString().getBytes(StandardCharsets.UTF_8));
                    }
                }
                int code = conn.getResponseCode();
                BufferedReader reader = new BufferedReader(new InputStreamReader(
                        code >= 400 ? conn.getErrorStream() : conn.getInputStream(), StandardCharsets.UTF_8));
                StringBuilder out = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) out.append(line);
                JSONObject json = new JSONObject(out.toString());
                runOnUiThread(() -> {
                    root.removeView(progress);
                    try {
                        if (code >= 400) {
                            toast(json.optString("message", "Request failed"));
                        } else {
                            callback.done(json);
                        }
                    } catch (Exception e) {
                        toast(e.getMessage());
                    }
                });
            } catch (Exception e) {
                runOnUiThread(() -> {
                    root.removeView(progress);
                    toast("Network error: " + e.getMessage());
                });
            }
        });
    }

    private void toast(String value) {
        Toast.makeText(this, value, Toast.LENGTH_LONG).show();
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density + 0.5f);
    }
}
