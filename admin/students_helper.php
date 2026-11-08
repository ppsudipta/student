<?php
/**
 * Shared student class/session helpers for admin dropdowns.
 * Student.class / student.session may be comma-separated; always expose unique singles.
 */

if (!function_exists('students_unique_classes')) {
    /**
     * All class names for admin dropdowns: active class_session rows first,
     * plus any orphan values still stored on students.
     *
     * @return list<string>
     */
    function students_unique_classes(mysqli $con): array
    {
        $list = [];

        $master = $con->query("SELECT DISTINCT class FROM class_session WHERE class IS NOT NULL AND class != '' AND (status = 'active' OR status IS NULL OR status = '') ORDER BY class");
        if ($master) {
            while ($row = $master->fetch_assoc()) {
                $class = trim((string) $row['class']);
                if ($class !== '') {
                    $list[] = $class;
                }
            }
        }

        $res = $con->query("SELECT class FROM students WHERE class IS NOT NULL AND class != '' AND class != '0'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                foreach (array_map('trim', explode(',', (string) $row['class'])) as $part) {
                    if ($part !== '') {
                        $list[] = $part;
                    }
                }
            }
        }

        $list = array_values(array_unique($list));
        sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        return $list;
    }
}

if (!function_exists('students_unique_sessions')) {
    /**
     * All sessions for admin dropdowns: active class_session rows first,
     * plus any orphan values still stored on students.
     *
     * @return list<string>
     */
    function students_unique_sessions(mysqli $con): array
    {
        $list = [];

        $master = $con->query("SELECT DISTINCT session FROM class_session WHERE session IS NOT NULL AND session != '' AND (status = 'active' OR status IS NULL OR status = '') ORDER BY session DESC");
        if ($master) {
            while ($row = $master->fetch_assoc()) {
                $session = trim((string) $row['session']);
                if ($session !== '') {
                    $list[] = $session;
                }
            }
        }

        $res = $con->query("SELECT session FROM students WHERE session IS NOT NULL AND session != ''");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                foreach (array_map('trim', explode(',', (string) $row['session'])) as $part) {
                    if ($part !== '') {
                        $list[] = $part;
                    }
                }
            }
        }

        $list = array_values(array_unique($list));
        rsort($list, SORT_NATURAL | SORT_FLAG_CASE);
        return $list;
    }
}

if (!function_exists('students_split_unique_values')) {
    /**
     * Split comma-separated column values from a query into unique sorted singles.
     *
     * @return list<string>
     */
    function students_split_unique_values(mysqli $con, string $sql, string $column, bool $desc = false): array
    {
        $list = [];
        $res = $con->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $raw = (string) ($row[$column] ?? '');
                foreach (array_map('trim', explode(',', $raw)) as $part) {
                    if ($part !== '') {
                        $list[] = $part;
                    }
                }
            }
        }
        $list = array_values(array_unique($list));
        if ($desc) {
            rsort($list, SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            sort($list, SORT_NATURAL | SORT_FLAG_CASE);
        }
        return $list;
    }
}
