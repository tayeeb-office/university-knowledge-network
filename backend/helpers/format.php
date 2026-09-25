<?php
if (!function_exists('ukn_time_ago')) {
    function ukn_time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'just now';
        }
        $units = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'min',
        ];
        foreach ($units as $seconds => $label) {
            $count = intdiv($diff, $seconds);
            if ($count >= 1) {
                return $count . ' ' . $label . ($count > 1 && $label !== 'min' ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}
if (!function_exists('ukn_excerpt')) {
    function ukn_excerpt(?string $text, int $length = 220): string
    {
        $text = trim((string) $text);
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        $truncated = mb_substr($text, 0, $length, 'UTF-8');
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }
        return rtrim($truncated) . '…';
    }
}
if (!function_exists('ukn_role_label')) {
    function ukn_role_label(?string $role): string
    {
        // 'dual' = learner + mentor capability (an approved mentor); shown as a mentor in the community.
        $labels = ['learner' => 'Learner', 'dual' => 'Mentor'];
        return $labels[$role] ?? ucfirst((string) $role);
    }
}
if (!function_exists('ukn_availability_bucket')) {
    /**
     * Collapses a mentor's enabled mentor_availability.day_of_week values (0 = Sunday .. 6 = Saturday,
     * matching PHP's date('w')) into the single label the existing find-mentors/recommendations UI
     * already filters on: 'Available Today', 'Weekend', 'Available This Week', or '' if unset.
     */
    function ukn_availability_bucket(array $enabledDaysOfWeek): string
    {
        if ($enabledDaysOfWeek === []) {
            return '';
        }
        $today = (int) date('w');
        if (in_array($today, $enabledDaysOfWeek, true)) {
            return 'Available Today';
        }
        if (array_intersect([0, 6], $enabledDaysOfWeek) !== []) {
            return 'Weekend';
        }
        return 'Available This Week';
    }
}
