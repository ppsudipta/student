package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.provider.OpenableColumns;
import android.webkit.MimeTypeMap;

import androidx.annotation.Nullable;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.InputStream;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

public final class AttachmentHelper {
    public static final long MAX_BYTES = 5L * 1024L * 1024L;

    private static final Set<String> ALLOWED_EXTENSIONS = new HashSet<>(Arrays.asList(
            "jpg", "jpeg", "png", "gif", "webp", "pdf", "doc", "docx", "xls", "xlsx"
    ));

    private static final String[] OPEN_DOCUMENT_MIME_TYPES = new String[]{
            "image/*",
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    };

    private AttachmentHelper() {
    }

    public static String[] openDocumentMimeTypes() {
        return OPEN_DOCUMENT_MIME_TYPES;
    }

    public static class SelectedFile {
        public final byte[] bytes;
        public final String fileName;
        public final String mimeType;

        public SelectedFile(byte[] bytes, String fileName, String mimeType) {
            this.bytes = bytes;
            this.fileName = fileName;
            this.mimeType = mimeType;
        }
    }

    public static void ensureWithinSizeLimit(Context context, Uri uri) {
        long reportedSize = querySize(context, uri);
        if (reportedSize > MAX_BYTES) {
            throw new IllegalArgumentException(context.getString(R.string.attachment_too_large));
        }
    }

    public static void ensureWithinSizeLimit(Context context, byte[] bytes) {
        if (bytes != null && bytes.length > MAX_BYTES) {
            throw new IllegalArgumentException(context.getString(R.string.attachment_too_large));
        }
    }

    @Nullable
    public static SelectedFile readSelectedFile(Context context, Uri uri) throws Exception {
        String fileName = queryDisplayName(context, uri);
        if (fileName == null || fileName.isEmpty()) {
            fileName = "attachment";
        }
        String extension = extensionFromName(fileName);
        if (!ALLOWED_EXTENSIONS.contains(extension)) {
            throw new IllegalArgumentException(context.getString(R.string.attachment_type_not_allowed));
        }

        ensureWithinSizeLimit(context, uri);

        String mimeType = context.getContentResolver().getType(uri);
        if (mimeType == null || mimeType.isEmpty()) {
            mimeType = MimeTypeMap.getSingleton().getMimeTypeFromExtension(extension);
        }
        if (mimeType == null || mimeType.isEmpty()) {
            mimeType = "application/octet-stream";
        }

        try (InputStream input = context.getContentResolver().openInputStream(uri)) {
            if (input == null) {
                throw new IllegalStateException(context.getString(R.string.network_error));
            }
            byte[] bytes = ApiClient.readAllBytes(input);
            ensureWithinSizeLimit(context, bytes);
            if (!fileName.contains(".")) {
                fileName = fileName + "." + extension;
            }
            return new SelectedFile(bytes, fileName, mimeType);
        }
    }

    public static void openAttachment(Context context, String baseUrl, JSONObject row) {
        String url = UrlHelper.enquiryAttachmentFromJson(baseUrl, row);
        if (url == null || url.isEmpty()) {
            return;
        }
        List<String> names = attachmentNamesFromJson(row);
        String name = names.isEmpty() ? row.optString("attachment", "attachment") : names.get(0);
        if (name.contains(",")) {
            name = name.split(",")[0].trim();
        }
        openAttachmentUrl(context, name, url);
    }

    public static void openAttachmentUrl(Context context, String name, String url) {
        if (url == null || url.isEmpty()) {
            return;
        }
        String displayName = displayFileName(name == null || name.isEmpty() ? "attachment" : name);
        String lower = displayName.toLowerCase(Locale.US);
        if (lower.endsWith(".jpg") || lower.endsWith(".jpeg") || lower.endsWith(".png")
                || lower.endsWith(".gif") || lower.endsWith(".webp")) {
            ImageViewerActivity.open(context, displayName, url, true);
            return;
        }
        if (lower.endsWith(".pdf")) {
            Intent intent = new Intent(context, PdfActivity.class);
            intent.putExtra(PdfActivity.EXTRA_URL, url);
            intent.putExtra(PdfActivity.EXTRA_TITLE, displayName);
            intent.putExtra(PdfActivity.EXTRA_CAN_DOWNLOAD, true);
            context.startActivity(intent);
            return;
        }
        Intent view = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
        context.startActivity(Intent.createChooser(view, context.getString(R.string.open_attachment)));
    }

    public static List<String> attachmentNamesFromJson(@Nullable JSONObject row) {
        List<String> names = new ArrayList<>();
        if (row == null) {
            return names;
        }
        JSONArray attachments = row.optJSONArray("attachments");
        if (attachments != null && attachments.length() > 0) {
            for (int i = 0; i < attachments.length(); i++) {
                String name = attachments.optString(i, "");
                if (!name.isEmpty() && !"null".equalsIgnoreCase(name)) {
                    names.add(name);
                }
            }
            return names;
        }
        String raw = row.optString("attachment", "");
        if (raw.isEmpty() || "null".equalsIgnoreCase(raw)) {
            return names;
        }
        if (raw.contains(",")) {
            for (String part : raw.split(",")) {
                String trimmed = part.trim();
                if (!trimmed.isEmpty()) {
                    names.add(trimmed);
                }
            }
        } else {
            names.add(raw);
        }
        return names;
    }

    public static List<String> attachmentUrlsFromJson(String baseUrl, @Nullable JSONObject row) {
        List<String> urls = new ArrayList<>();
        if (row == null) {
            return urls;
        }
        JSONArray directUrls = row.optJSONArray("attachment_urls");
        if (directUrls != null && directUrls.length() > 0) {
            for (int i = 0; i < directUrls.length(); i++) {
                String url = directUrls.optString(i, "");
                if (!url.isEmpty() && !"null".equalsIgnoreCase(url)) {
                    if (url.startsWith("http://") || url.startsWith("https://")) {
                        urls.add(url);
                    } else {
                        String resolved = UrlHelper.enquiryAttachmentUrl(baseUrl, url);
                        if (resolved != null) {
                            urls.add(resolved);
                        }
                    }
                }
            }
            if (!urls.isEmpty()) {
                return urls;
            }
        }
        for (String name : attachmentNamesFromJson(row)) {
            String url = UrlHelper.enquiryAttachmentUrl(baseUrl, name);
            if (url != null) {
                urls.add(url);
            }
        }
        return urls;
    }

    @Nullable
    private static String queryDisplayName(Context context, Uri uri) {
        try (Cursor cursor = context.getContentResolver()
                .query(uri, new String[]{OpenableColumns.DISPLAY_NAME}, null, null, null)) {
            if (cursor != null && cursor.moveToFirst()) {
                int index = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME);
                if (index >= 0) {
                    return cursor.getString(index);
                }
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    private static long querySize(Context context, Uri uri) {
        try (Cursor cursor = context.getContentResolver()
                .query(uri, new String[]{OpenableColumns.SIZE}, null, null, null)) {
            if (cursor != null && cursor.moveToFirst()) {
                int index = cursor.getColumnIndex(OpenableColumns.SIZE);
                if (index >= 0 && !cursor.isNull(index)) {
                    return cursor.getLong(index);
                }
            }
        } catch (Exception ignored) {
        }
        return -1L;
    }

    private static String extensionFromName(String fileName) {
        int dot = fileName.lastIndexOf('.');
        if (dot < 0 || dot == fileName.length() - 1) {
            return "";
        }
        return fileName.substring(dot + 1).toLowerCase(Locale.US);
    }

    public static String displayFileName(@Nullable String attachment) {
        if (attachment == null || attachment.isEmpty()) {
            return "";
        }
        int slash = Math.max(attachment.lastIndexOf('/'), attachment.lastIndexOf('\\'));
        return slash >= 0 ? attachment.substring(slash + 1) : attachment;
    }
}
