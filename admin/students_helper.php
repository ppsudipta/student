<?php
/**
 * Shared student class/session helpers for admin dropdowns.
 * Student.class / student.session may be comma-separated; always expose unique singles.
 */

if (!function_exists('students_unique_classes')) {
    /**
     * @return list<string>
     */
    function students_unique_classes(mysqli $con): array
    {
        $list = [];
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
     * @return list<string>
     */
    function students_unique_sessions(mysqli $con): array
    {
        $list = [];
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
