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

if (!function_exists('students_csv_normalize_sql')) {
    /**
     * Normalize comma-separated class/session values for FIND_IN_SET matching.
     * Converts "A, B" / "A ,B" / "A,,B" into "A,B".
     */
    function students_csv_normalize_sql(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE($column, ', ', ','), ' ,', ','), ',,', ','), ' , ', ',')";
    }
}

if (!function_exists('students_class_match_sql')) {
    /**
     * SQL: student has at least this class in a comma-separated column.
     * Use with a bound ? parameter for the class name.
     *
     * Example: "... WHERE " . students_class_match_sql('s.class')
     */
    function students_class_match_sql(string $column = 'class'): string
    {
        return 'FIND_IN_SET(?, ' . students_csv_normalize_sql($column) . ')';
    }
}

if (!function_exists('students_class_match_literal')) {
    /**
     * Same as students_class_match_sql but with an already-escaped class string.
     */
    function students_class_match_literal(string $column, string $escapedClass): string
    {
        return "FIND_IN_SET('$escapedClass', " . students_csv_normalize_sql($column) . ')';
    }
}

if (!function_exists('students_has_class')) {
    /**
     * PHP-side: does comma-separated student class list include $needle?
     */
    function students_has_class(?string $studentClasses, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '' || $studentClasses === null || trim($studentClasses) === '') {
            return false;
        }
        foreach (array_map('trim', explode(',', $studentClasses)) as $part) {
            if ($part !== '' && strcasecmp($part, $needle) === 0) {
                return true;
            }
        }
        return false;
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
