<?php
// Member reports (backend/reports/create.php + the report modal). A report is one row in
// `reports` (target_type + target_id); the reported post/comment only keeps its report_count.
if (!defined('UKN_REPORT_DESCRIPTION_MAX')) {
    define('UKN_REPORT_DESCRIPTION_MAX', 500);
}
if (!function_exists('uknReportReasons')) {
    /** reports.reason ENUM value => label, in the order the modal lists them. */
    function uknReportReasons(): array
    {
        return [
            'spam' => 'Spam',
            'inappropriate' => 'Inappropriate content',
            'harassment' => 'Harassment / conduct',
            'off-topic' => 'Off-topic',
            'academic-integrity' => 'Academic integrity concern',
            'other' => 'Other',
        ];
    }
}
