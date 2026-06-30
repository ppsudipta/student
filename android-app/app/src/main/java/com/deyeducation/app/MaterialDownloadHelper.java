package com.deyeducation.app;

import android.content.ContentValues;
import android.content.Context;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;

import java.io.File;
import java.io.FileInputStream;
import java.io.OutputStream;

public final class MaterialDownloadHelper {
    private MaterialDownloadHelper() {
    }

    public static boolean saveToDownloads(Context context, File sourceFile, String displayName, String mimeType) {
        if (context == null || sourceFile == null || !sourceFile.exists()) {
            return false;
        }
        String safeName = sanitizeFileName(displayName);
        if (!safeName.contains(".")) {
            String ext = extensionForMime(mimeType);
            if (!ext.isEmpty()) {
                safeName = safeName + ext;
            }
        }
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                ContentValues values = new ContentValues();
                values.put(MediaStore.Downloads.DISPLAY_NAME, safeName);
                values.put(MediaStore.Downloads.MIME_TYPE, mimeType);
                values.put(MediaStore.Downloads.RELATIVE_PATH, Environment.DIRECTORY_DOWNLOADS);
                values.put(MediaStore.Downloads.IS_PENDING, 1);
                android.net.Uri uri = context.getContentResolver().insert(
                        MediaStore.Downloads.EXTERNAL_CONTENT_URI, values);
                if (uri == null) {
                    return false;
                }
                try (OutputStream out = context.getContentResolver().openOutputStream(uri);
                     FileInputStream in = new FileInputStream(sourceFile)) {
                    if (out == null) {
                        return false;
                    }
                    copyStream(in, out);
                }
                values.clear();
                values.put(MediaStore.Downloads.IS_PENDING, 0);
                context.getContentResolver().update(uri, values, null, null);
                return true;
            }

            File downloads = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS);
            if (!downloads.exists() && !downloads.mkdirs()) {
                return false;
            }
            File target = uniqueFile(downloads, safeName);
            try (FileInputStream in = new FileInputStream(sourceFile);
                 java.io.FileOutputStream out = new java.io.FileOutputStream(target)) {
                copyStream(in, out);
            }
            return true;
        } catch (Exception ignored) {
            return false;
        }
    }

    private static void copyStream(FileInputStream in, OutputStream out) throws java.io.IOException {
        byte[] buffer = new byte[8192];
        int read;
        while ((read = in.read(buffer)) != -1) {
            out.write(buffer, 0, read);
        }
        out.flush();
    }

    private static File uniqueFile(File dir, String name) {
        File file = new File(dir, name);
        if (!file.exists()) {
            return file;
        }
        int dot = name.lastIndexOf('.');
        String base = dot > 0 ? name.substring(0, dot) : name;
        String ext = dot > 0 ? name.substring(dot) : "";
        for (int i = 1; i < 100; i++) {
            File candidate = new File(dir, base + " (" + i + ")" + ext);
            if (!candidate.exists()) {
                return candidate;
            }
        }
        return new File(dir, base + "_" + System.currentTimeMillis() + ext);
    }

    private static String sanitizeFileName(String name) {
        if (name == null || name.trim().isEmpty()) {
            return "material";
        }
        return name.trim().replaceAll("[\\\\/:*?\"<>|]", "_");
    }

    private static String extensionForMime(String mimeType) {
        if (mimeType == null) {
            return "";
        }
        switch (mimeType) {
            case "application/pdf":
                return ".pdf";
            case "image/jpeg":
                return ".jpg";
            case "image/png":
                return ".png";
            case "image/gif":
                return ".gif";
            case "image/webp":
                return ".webp";
            default:
                return "";
        }
    }
}
