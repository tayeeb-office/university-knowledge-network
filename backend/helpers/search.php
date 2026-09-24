<?php
// Phase G (Step 42): search input handling shared by the header search box and the results page.

if (!defined('UKN_SEARCH_MAX_LENGTH')) {
    define('UKN_SEARCH_MAX_LENGTH', 100);
    define('UKN_SEARCH_MAX_TERMS', 8);
    define('UKN_SEARCH_RESULT_LIMIT', 30);
}

if (!function_exists('uknSearchQueryFromRequest')) {
    /** ?q= as a trimmed, whitespace-collapsed string of at most UKN_SEARCH_MAX_LENGTH characters; arrays become ''. */
    function uknSearchQueryFromRequest(): string
    {
        $raw = $_GET['q'] ?? '';
        if (!is_string($raw) || !mb_check_encoding($raw, 'UTF-8')) {
            return '';
        }
        $q = trim((string) preg_replace('/\s+/u', ' ', $raw));
        return mb_substr($q, 0, UKN_SEARCH_MAX_LENGTH, 'UTF-8');
    }
}
if (!function_exists('uknLikeContains')) {
    /** '%value%' with LIKE wildcards escaped; pair with ESCAPE '!' in the SQL. */
    function uknLikeContains(string $value): string
    {
        return '%' . strtr($value, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    }
}
if (!function_exists('uknFulltextTerms')) {
    /**
     * Boolean-mode AGAINST() string for posts' FULLTEXT(title, content) index: each word becomes
     * a prefix term ("word*"). Operators and punctuation never reach MySQL because only letter/
     * digit runs are kept. Words shorter than innodb_ft_min_token_size (3) or on InnoDB's default
     * stopword list are not indexed, so they are dropped; '' means no indexable word.
     */
    function uknFulltextTerms(string $q): string
    {
        static $stopwords = [
            'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how',
            'i', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what',
            'when', 'where', 'who', 'will', 'with', 'und', 'www',
        ];
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($q, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = [];
        foreach ($words as $word) {
            if (mb_strlen($word, 'UTF-8') >= 3 && !in_array($word, $stopwords, true)) {
                $terms[$word] = $word . '*';
            }
            if (count($terms) >= UKN_SEARCH_MAX_TERMS) {
                break;
            }
        }
        return implode(' ', $terms);
    }
}
