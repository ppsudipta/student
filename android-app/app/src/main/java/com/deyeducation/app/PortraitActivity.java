package com.deyeducation.app;

import android.annotation.SuppressLint;
import android.content.pm.ActivityInfo;
import android.os.Bundle;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

/**
 * Portrait screens. Orientation is applied in code, not the manifest.
 * ColorOS (Oppo) crashes with "Only fullscreen opaque activities can request
 * orientation" when android:screenOrientation is set and a new activity starts
 * right after login — on both Android 11 and Android 15.
 */
public class PortraitActivity extends AppCompatActivity {
    @SuppressLint("SourceLockedOrientationActivity")
    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        // targetSdk 36 forces edge-to-edge on Android 15. Keep the old window
        // insets so DrawerLayout / toolbars do not crash on ColorOS 15.
        WindowCompat.setDecorFitsSystemWindows(getWindow(), true);
        super.onCreate(savedInstanceState);
        try {
            setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);
        } catch (IllegalStateException ignored) {
            // Window is not opaque yet (permission dialog, activity transition).
        }
    }
}
