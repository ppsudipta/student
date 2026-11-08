<?php
/**
 * Fee month/year helpers. payment_reason may be "March" (legacy) or "March 2025".
 */

if (!function_exists('fees_month_names')) {
    /** @return list<string> */
    function fees_month_names(): array
    {
        return [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
    }
}

if (!function_exists('fees_year_options')) {
    /** @return list<int> */
    function fees_year_options(int $span = 6): array
    {
        $current = (int) date('Y');
        return range($current + 1, $current - $span);
    }
}

if (!function_exists('fees_format_period')) {
    function fees_format_period(string $month, $year): string
    {
        return trim($month) . ' ' . (int) $year;
    }
}

if (!function_exists('fees_parse_period')) {
    /**
     * @return array{month:string,year:int,month_num:int}|null
     */
    function fees_parse_period(string $paymentReason, ?string $donationDate = null): ?array
    {
        $months = fees_month_names();
        $reason = trim($paymentReason);
        if ($reason === '') {
            return null;
        }

        if (preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)(?:\s+(\d{4}))?$/i', $reason, $m)) {
            $month = null;
            foreach ($months as $name) {
                if (strcasecmp($name, $m[1]) === 0) {
                    $month = $name;
                    break;
                }
            }
            if ($month === null) {
                return null;
            }
            $year = isset($m[2]) && $m[2] !== ''
                ? (int) $m[2]
                : (int) date('Y', strtotime($donationDate ?: 'now'));
            return [
                'month' => $month,
                'year' => $year,
                'month_num' => array_search($month, $months, true) + 1,
            ];
        }

        return null;
    }
}

if (!function_exists('fees_period_match_sql')) {
    /**
     * SQL fragment matching a fee month and optional year against payment_reason
     * (supports legacy month-only rows via donation_date year).
     */
    function fees_period_match_sql(mysqli $con, string $month, string $year = '', string $alias = 'd'): string
    {
        $monthEsc = mysqli_real_escape_string($con, $month);
        $a = preg_replace('/[^a-zA-Z_]/', '', $alias) ?: 'd';

        if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
            $yearEsc = mysqli_real_escape_string($con, $year);
            $exact = fees_format_period($month, $year);
            $exactEsc = mysqli_real_escape_string($con, $exact);
            return "(
                {$a}.payment_reason = '$exactEsc'
                OR ({$a}.payment_reason = '$monthEsc' AND YEAR({$a}.donation_date) = '$yearEsc')
            )";
        }

        return "(
            {$a}.payment_reason = '$monthEsc'
            OR {$a}.payment_reason LIKE '$monthEsc %'
        )";
    }
}

if (!function_exists('fees_year_match_sql')) {
    /** Match year in "Month YYYY" or legacy donation_date year for month-only rows. */
    function fees_year_match_sql(mysqli $con, string $year, string $alias = 'd'): string
    {
        $yearEsc = mysqli_real_escape_string($con, $year);
        $a = preg_replace('/[^a-zA-Z_]/', '', $alias) ?: 'd';
        $monthsSql = "'" . implode("','", fees_month_names()) . "'";
        return "(
            {$a}.payment_reason LIKE '% $yearEsc'
            OR ({$a}.payment_reason IN ($monthsSql) AND YEAR({$a}.donation_date) = '$yearEsc')
        )";
    }
}

if (!function_exists('fees_last_paid_for_student')) {
    /**
     * @return array{last_paid:string,month:string,year:int|null}
     */
    function fees_last_paid_for_student(mysqli $con, string $registrationCode): array
    {
        $empty = ['last_paid' => 'No payment found', 'month' => '', 'year' => null];
        $regEsc = mysqli_real_escape_string($con, $registrationCode);
        $monthsSql = "'" . implode("','", fees_month_names()) . "'";

        $sql = "SELECT payment_reason, donation_date
                FROM donations
                WHERE student_registration_code = '$regEsc'
                  AND status = 'success'
                  AND (
                    payment_reason IN ($monthsSql)
                    OR payment_reason REGEXP '^(January|February|March|April|May|June|July|August|September|October|November|December) [0-9]{4}$'
                  )
                ORDER BY id DESC
                LIMIT 50";
        $res = mysqli_query($con, $sql);
        if (!$res) {
            return $empty;
        }

        $best = null;
        $bestKey = -1;
        while ($row = mysqli_fetch_assoc($res)) {
            $parsed = fees_parse_period((string) $row['payment_reason'], (string) $row['donation_date']);
            if ($parsed === null) {
                continue;
            }
            $key = ($parsed['year'] * 100) + $parsed['month_num'];
            if ($key > $bestKey) {
                $bestKey = $key;
                $best = $parsed;
            }
        }

        if ($best === null) {
            return $empty;
        }

        return [
            'last_paid' => fees_format_period($best['month'], $best['year']),
            'month' => $best['month'],
            'year' => $best['year'],
        ];
    }
}
