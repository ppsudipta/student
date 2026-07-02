<?php

/**
 * Resolve a student profile image path for pages under /pages/.
 * Student uploads live in /img/; legacy defaults live in /admin/.
 */
function student_image_url(?string $path): string
{
    $default = '../admin/user.png';
    if ($path === null || $path === '') {
        return $default;
    }

    $path = str_replace('\\/', '/', trim($path));
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '../img/')) {
        return '../img/' . ltrim(substr($path, 7), '/');
    }
    if (str_starts_with($path, 'img/')) {
        return '../' . $path;
    }

    while (str_starts_with($path, '../')) {
        $path = substr($path, 3);
    }
    while (str_starts_with($path, './')) {
        $path = substr($path, 2);
    }

    return '../admin/' . ltrim($path, '/');
}
