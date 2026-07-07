package com.deyeducation.app;

import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.provider.OpenableColumns;
import android.webkit.MimeTypeMap;

import androidx.annotation.Nullable;

import org.json.JSONObject;

import java.io.InputStream;
import java.util.Arrays;
import java.util.HashSet;
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
            if (bytes.length > MAX_BYTES) {
                throw new IllegalArgumentException(context.getString(R.string.attachment_too_large));
            }
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
        String name = row.optString("attachment", "attachment");
        String lower = name.toLowerCase(Locale.US);
        if (lower.endsWith(".jpg") || lower.endsWith(".jpeg") || lower.endsWith(".png")
                || lower.endsWith(".gif") || lower.endsWith(".webp")) {
            ImageViewerActivity.open(context, name, url, true);
            return;
        }
        if (lower.endsWith(".pdf")) {
            Intent intent = new Intent(context, PdfActivity.class);
            intent.putExtra(PdfActivity.EXTRA_URL, url);
            intent.putExtra(PdfActivity.EXTRA_TITLE, name);
            intent.putExtra(PdfActivity.EXTRA_CAN_DOWNLOAD, true);
            context.startActivity(intent);
            return;
        }
        Intent view = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
        context.startActivity(Intent.createChooser(view, context.getString(R.string.open_attachment)));
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
