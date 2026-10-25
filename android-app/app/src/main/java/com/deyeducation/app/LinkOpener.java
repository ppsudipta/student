package com.deyeducation.app;

import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.text.SpannableString;
import android.text.Spanned;
import android.text.TextPaint;
import android.text.method.LinkMovementMethod;
import android.text.style.ClickableSpan;
import android.view.View;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;

import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Opens https links (YouTube, Google Meet, etc.) in the matching installed app when possible.
 */
public final class LinkOpener {
    private static final Pattern URL_PATTERN = Pattern.compile(
            "(?i)\\b((?:https?://|www\\.)[^\\s<>\"']+|youtu\\.be/[^\\s<>\"']+|meet\\.google\\.com/[^\\s<>\"']+)");

    private LinkOpener() {
    }

    @Nullable
    public static String extractFirstUrl(@Nullable String text) {
        if (text == null || text.isEmpty()) {
            return null;
        }
        Matcher matcher = URL_PATTERN.matcher(text);
        if (!matcher.find()) {
            return null;
        }
        return normalizeUrl(matcher.group());
    }

    @NonNull
    public static String normalizeUrl(@Nullable String raw) {
        if (raw == null) {
            return "";
        }
        String url = raw.trim();
        while (url.endsWith(".") || url.endsWith(",") || url.endsWith(")") || url.endsWith("]")
                || url.endsWith(";") || url.endsWith(":")) {
            url = url.substring(0, url.length() - 1);
        }
        if (url.isEmpty()) {
            return "";
        }
        if (url.startsWith("http://") || url.startsWith("https://")) {
            return url;
        }
        return "https://" + url;
    }

    public static boolean open(@Nullable Context context, @Nullable String rawUrl) {
        if (context == null) {
            return false;
        }
        String url = normalizeUrl(rawUrl);
        if (url.isEmpty()) {
            return false;
        }
        // YouTube @handles often open as Search in the YouTube app — use dedicated opener.
        if (isYoutubeUrl(url)) {
            return openYoutubeChannel(context, url);
        }
        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        try {
            context.startActivity(intent);
            return true;
        } catch (ActivityNotFoundException e) {
            UiUtils.toast(context, context.getString(R.string.link_open_failed));
            return false;
        } catch (Exception e) {
            UiUtils.toast(context, context.getString(R.string.link_open_failed));
            return false;
        }
    }

    public static boolean isYoutubeUrl(@Nullable String url) {
        if (url == null) {
            return false;
        }
        String lower = url.toLowerCase();
        return lower.contains("youtube.com") || lower.contains("youtu.be");
    }

    /**
     * Opens a YouTube channel (and subscribe prompt) reliably.
     * Encodes {@code @} so the app does not fall back to a search page.
     */
    public static boolean openYoutubeChannel(@Nullable Context context, @Nullable String rawUrl) {
        if (context == null) {
            return false;
        }
        String url = normalizeYoutubeChannelUrl(rawUrl);
        if (url.isEmpty()) {
            url = AppConfig.DEFAULT_YOUTUBE_URL;
        }
        if (!url.contains("sub_confirmation=")) {
            url = url + (url.contains("?") ? "&" : "?") + "sub_confirmation=1";
        }

        // Encode @ → %40 so YouTube app opens the channel, not Search.
        Uri uri = Uri.parse(url.replace("@", "%40"));

        Intent appIntent = new Intent(Intent.ACTION_VIEW, uri);
        appIntent.setPackage("com.google.android.youtube");
        appIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        try {
            context.startActivity(appIntent);
            return true;
        } catch (Exception ignored) {
            // Fall through to browser / any handler.
        }

        Intent browser = new Intent(Intent.ACTION_VIEW, uri);
        browser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        try {
            context.startActivity(browser);
            return true;
        } catch (ActivityNotFoundException e) {
            UiUtils.toast(context, context.getString(R.string.link_open_failed));
            return false;
        } catch (Exception e) {
            UiUtils.toast(context, context.getString(R.string.link_open_failed));
            return false;
        }
    }

    @NonNull
    public static String normalizeYoutubeChannelUrl(@Nullable String raw) {
        String url = normalizeUrl(raw);
        if (url.isEmpty()) {
            return AppConfig.DEFAULT_YOUTUBE_URL;
        }
        // Bare handle: TanoyKumarDey or @TanoyKumarDey
        if (!url.contains("://") && !url.contains("youtube") && !url.contains("youtu.be")) {
            String handle = url.startsWith("@") ? url : "@" + url;
            return "https://www.youtube.com/" + handle;
        }
        // youtube.com without www / with mobile host
        url = url.replace("://m.youtube.com", "://www.youtube.com")
                .replace("://youtube.com", "://www.youtube.com");
        return url;
    }

    /** Makes every URL in the text tappable and opens it in the related app/browser. */
    public static void bindClickableLinks(@NonNull TextView textView, @Nullable String content) {
        String text = content == null ? "" : content;
        if (text.isEmpty()) {
            textView.setText("");
            return;
        }
        SpannableString spannable = new SpannableString(text);
        Matcher matcher = URL_PATTERN.matcher(text);
        boolean found = false;
        while (matcher.find()) {
            found = true;
            final String url = normalizeUrl(matcher.group());
            int start = matcher.start();
            int end = matcher.end();
            spannable.setSpan(new ClickableSpan() {
                @Override
                public void onClick(@NonNull View widget) {
                    open(widget.getContext(), url);
                }

                @Override
                public void updateDrawState(@NonNull TextPaint ds) {
                    super.updateDrawState(ds);
                    ds.setUnderlineText(true);
                    ds.setColor(ContextCompat.getColor(textView.getContext(), R.color.primary));
                }
            }, start, end, Spanned.SPAN_EXCLUSIVE_EXCLUSIVE);
        }
        textView.setText(spannable);
        if (found) {
            textView.setMovementMethod(LinkMovementMethod.getInstance());
            textView.setHighlightColor(0x33000000);
        }
    }
}
