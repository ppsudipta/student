package com.deyeducation.app;

import android.animation.AnimatorSet;
import android.animation.ObjectAnimator;
import android.animation.ValueAnimator;
import android.content.Context;
import android.content.res.TypedArray;
import android.os.Handler;
import android.os.Looper;
import android.util.AttributeSet;
import android.view.LayoutInflater;
import android.view.View;
import android.widget.FrameLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;

import com.google.android.material.card.MaterialCardView;

public class FunLoaderView extends FrameLayout {

    public enum Kind {
        GENERAL, HOME, MATERIALS, VIDEO, PDF, LOGIN, PROFILE, ENQUIRY, GALLERY, NOTICES, ATTENDANCE
    }

    private View scrim;
    private MaterialCardView card;
    private TextView emojiView;
    private TextView titleView;
    private TextView tipView;
    private View dot1;
    private View dot2;
    private View dot3;

    private Kind kind = Kind.GENERAL;
    private boolean fullSize = true;
    private boolean animating;

    private final Handler handler = new Handler(Looper.getMainLooper());
    private int tipIndex;
    private AnimatorSet dotAnimators;
    private ObjectAnimator emojiWiggle;

    private final Runnable tipTicker = new Runnable() {
        @Override
        public void run() {
            if (!animating || tipView == null) {
                return;
            }
            String[] tips = getTips();
            if (tips.length == 0) {
                return;
            }
            tipIndex = (tipIndex + 1) % tips.length;
            tipView.setText(tips[tipIndex]);
            handler.postDelayed(this, 2800);
        }
    };

    public FunLoaderView(@NonNull Context context) {
        super(context);
        init(context, null);
    }

    public FunLoaderView(@NonNull Context context, @Nullable AttributeSet attrs) {
        super(context, attrs);
        init(context, attrs);
    }

    public FunLoaderView(@NonNull Context context, @Nullable AttributeSet attrs, int defStyleAttr) {
        super(context, attrs, defStyleAttr);
        init(context, attrs);
    }

    private void init(Context context, @Nullable AttributeSet attrs) {
        LayoutInflater.from(context).inflate(R.layout.view_fun_loader, this, true);
        scrim = findViewById(R.id.loaderScrim);
        card = findViewById(R.id.loaderCard);
        emojiView = findViewById(R.id.loaderEmoji);
        titleView = findViewById(R.id.loaderTitle);
        tipView = findViewById(R.id.loaderTip);
        dot1 = findViewById(R.id.loaderDot1);
        dot2 = findViewById(R.id.loaderDot2);
        dot3 = findViewById(R.id.loaderDot3);

        if (attrs != null) {
            TypedArray a = context.obtainStyledAttributes(attrs, R.styleable.FunLoaderView);
            int kindVal = a.getInt(R.styleable.FunLoaderView_loaderKind, 0);
            if (kindVal >= 0 && kindVal < Kind.values().length) {
                kind = Kind.values()[kindVal];
            }
            fullSize = a.getInt(R.styleable.FunLoaderView_loaderSize, 1) == 1;
            a.recycle();
        }
        applyKind();
        applySize();
        super.setVisibility(GONE);
    }

    public void setKind(Kind k) {
        if (k == null) {
            return;
        }
        kind = k;
        applyKind();
    }

    private void applyKind() {
        if (emojiView == null || titleView == null) {
            return;
        }
        switch (kind) {
            case HOME:
                emojiView.setText("🏠");
                titleView.setText(R.string.loader_title_home);
                break;
            case MATERIALS:
                emojiView.setText("📚");
                titleView.setText(R.string.loader_title_materials);
                break;
            case VIDEO:
                emojiView.setText("🎬");
                titleView.setText(R.string.loader_title_video);
                break;
            case PDF:
                emojiView.setText("📄");
                titleView.setText(R.string.loader_title_pdf);
                break;
            case LOGIN:
                emojiView.setText("🔐");
                titleView.setText(R.string.loader_title_login);
                break;
            case PROFILE:
                emojiView.setText("👤");
                titleView.setText(R.string.loader_title_profile);
                break;
            case ENQUIRY:
                emojiView.setText("💬");
                titleView.setText(R.string.loader_title_enquiry);
                break;
            case GALLERY:
                emojiView.setText("🖼️");
                titleView.setText(R.string.loader_title_gallery);
                break;
            case NOTICES:
                emojiView.setText("📢");
                titleView.setText(R.string.loader_title_notices);
                break;
            case ATTENDANCE:
                emojiView.setText("📅");
                titleView.setText(R.string.loader_title_attendance);
                break;
            default:
                emojiView.setText("✨");
                titleView.setText(R.string.loader_title_general);
                break;
        }
        String[] tips = getTips();
        tipIndex = 0;
        if (tips.length > 0 && tipView != null) {
            tipView.setText(tips[0]);
        }
    }

    private void applySize() {
        if (scrim != null) {
            scrim.setVisibility(fullSize ? VISIBLE : GONE);
        }
        if (card != null) {
            int pad = fullSize ? UiUtils.dp(getContext(), 28) : UiUtils.dp(getContext(), 18);
            card.setContentPadding(pad, pad, pad, pad);
        }
        if (emojiView != null) {
            emojiView.setTextSize(fullSize ? 42f : 28f);
        }
        if (titleView != null) {
            titleView.setTextSize(fullSize ? 18f : 15f);
        }
        if (tipView != null) {
            tipView.setVisibility(fullSize ? VISIBLE : GONE);
        }
    }

    private int tipsArrayForKind() {
        switch (kind) {
            case HOME:
                return R.array.loader_tips_home;
            case MATERIALS:
                return R.array.loader_tips_materials;
            case VIDEO:
                return R.array.loader_tips_video;
            case PDF:
                return R.array.loader_tips_pdf;
            case LOGIN:
                return R.array.loader_tips_login;
            case PROFILE:
                return R.array.loader_tips_profile;
            case ENQUIRY:
                return R.array.loader_tips_enquiry;
            case GALLERY:
                return R.array.loader_tips_gallery;
            case NOTICES:
                return R.array.loader_tips_notices;
            case ATTENDANCE:
                return R.array.loader_tips_attendance;
            default:
                return R.array.loader_tips_general;
        }
    }

    private String[] getTips() {
        return getResources().getStringArray(tipsArrayForKind());
    }

    public void show() {
        if (animating && getVisibility() == VISIBLE) {
            return;
        }
        animating = true;
        applyKind();
        super.setVisibility(VISIBLE);
        startAnimations();
        handler.removeCallbacks(tipTicker);
        if (fullSize) {
            handler.postDelayed(tipTicker, 2800);
        }
    }

    public void show(Kind k) {
        setKind(k);
        show();
    }

    public void hide() {
        animating = false;
        stopAnimations();
        handler.removeCallbacks(tipTicker);
        super.setVisibility(GONE);
    }

    private void startAnimations() {
        stopAnimations();
        dotAnimators = new AnimatorSet();
        dotAnimators.playTogether(
                createBounce(dot1, 0),
                createBounce(dot2, 120),
                createBounce(dot3, 240));
        dotAnimators.start();

        if (emojiView != null) {
            emojiWiggle = ObjectAnimator.ofFloat(emojiView, View.ROTATION, -8f, 8f);
            emojiWiggle.setDuration(750);
            emojiWiggle.setRepeatMode(ValueAnimator.REVERSE);
            emojiWiggle.setRepeatCount(ValueAnimator.INFINITE);
            emojiWiggle.start();
        }
    }

    private ObjectAnimator createBounce(View dot, long delay) {
        float lift = UiUtils.dp(getContext(), 14);
        ObjectAnimator animator = ObjectAnimator.ofFloat(dot, View.TRANSLATION_Y, 0f, -lift, 0f);
        animator.setDuration(620);
        animator.setRepeatCount(ValueAnimator.INFINITE);
        animator.setStartDelay(delay);
        return animator;
    }

    private void stopAnimations() {
        if (dotAnimators != null) {
            dotAnimators.cancel();
            dotAnimators = null;
        }
        if (emojiWiggle != null) {
            emojiWiggle.cancel();
            emojiWiggle = null;
        }
        if (dot1 != null) {
            dot1.setTranslationY(0);
        }
        if (dot2 != null) {
            dot2.setTranslationY(0);
        }
        if (dot3 != null) {
            dot3.setTranslationY(0);
        }
        if (emojiView != null) {
            emojiView.setRotation(0);
        }
    }

    @Override
    protected void onDetachedFromWindow() {
        hide();
        super.onDetachedFromWindow();
    }
}
