<?php
// Profile photos (users.avatar_path). Files live in uploads/profiles/ under a random name; the
// database stores only the relative path "profiles/<32 hex>.jpg|png". Everything that reads,
// writes, deletes or renders an avatar goes through these functions.
if (!defined('UKN_AVATAR_MAX_BYTES')) {
    define('UKN_AVATAR_MAX_BYTES', 2 * 1024 * 1024);
    define('UKN_AVATAR_MIN_SIZE', 200);
    define('UKN_AVATAR_PATH_PATTERN', '/^profiles\/[a-f0-9]{32}\.(jpg|png)$/D');
}

if (!function_exists('uknAvatarDir')) {
    /** Filesystem directory of stored profile photos (with trailing slash). */
    function uknAvatarDir(): string
    {
        return __DIR__ . '/../../uploads/profiles/';
    }
}
if (!function_exists('uknAvatarIsValidPath')) {
    /** True only for a path this feature generates, so nothing else can be read or deleted. */
    function uknAvatarIsValidPath($path): bool
    {
        return is_string($path) && preg_match(UKN_AVATAR_PATH_PATTERN, $path) === 1;
    }
}
if (!function_exists('uknAvatarUrl')) {
    /** Public URL of a stored avatar, or null (no avatar, unexpected value or missing file). */
    function uknAvatarUrl($path): ?string
    {
        if (!uknAvatarIsValidPath($path) || !is_file(uknAvatarDir() . basename($path))) {
            return null;
        }
        return uknBaseUrl() . '/uploads/' . $path;
    }
}
if (!function_exists('uknAvatarHtml')) {
    /** The avatar span: the stored photo when there is one, otherwise the initials (as before). */
    function uknAvatarHtml($path, string $initials, string $class, string $attrs = ''): string
    {
        $url = uknAvatarUrl($path);
        $inner = $url !== null
            ? '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="">'
            : htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        return '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . $inner . '</span>';
    }
}
if (!function_exists('uknAvatarValidateUpload')) {
    /**
     * Checks $_FILES['avatar']. Returns null when no file was chosen, ['error' => message] when
     * the file is rejected, or ['tmp' => path, 'ext' => 'jpg'|'png'] for a valid photo. The
     * original name and the browser-supplied type are never used.
     */
    function uknAvatarValidateUpload($file): ?array
    {
        if ($file === null) {
            return null;
        }
        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['size']) || is_array($file['error'])) {
            return ['error' => 'Invalid photo upload.'];
        }
        switch ((int) $file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return null;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['error' => 'The photo must be 2 MB or smaller.'];
            case UPLOAD_ERR_PARTIAL:
                return ['error' => 'The photo was only partly uploaded. Please try again.'];
            default: // UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION
                return ['error' => 'The photo could not be uploaded. Please try again.'];
        }
        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            return ['error' => 'Invalid photo upload.'];
        }
        $size = filesize($tmp);
        if ($size === false || $size <= 0 || $size > UKN_AVATAR_MAX_BYTES) {
            return ['error' => 'The photo must be 2 MB or smaller.'];
        }
        // Real content type (magic bytes), not the name or the browser's claim.
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $types = ['image/jpeg' => [IMAGETYPE_JPEG, 'jpg'], 'image/png' => [IMAGETYPE_PNG, 'png']];
        if (!isset($types[$mime])) {
            return ['error' => 'Upload a JPG or PNG image.'];
        }
        $info = @getimagesize($tmp);
        if ($info === false || (int) $info[2] !== $types[$mime][0]) {
            return ['error' => 'The photo is not a valid image.'];
        }
        if ((int) $info[0] < UKN_AVATAR_MIN_SIZE || (int) $info[1] < UKN_AVATAR_MIN_SIZE) {
            return ['error' => 'The photo must be at least ' . UKN_AVATAR_MIN_SIZE . '×' . UKN_AVATAR_MIN_SIZE . ' pixels.'];
        }
        // A complete file ends with the format's end marker (JPEG EOI, PNG IEND chunk); this
        // rejects truncated/corrupt files that still have a valid header.
        $handle = fopen($tmp, 'rb');
        $tail = '';
        if ($handle !== false) {
            fseek($handle, -12, SEEK_END);
            $tail = (string) fread($handle, 12);
            fclose($handle);
        }
        $complete = $types[$mime][1] === 'jpg'
            ? substr(rtrim($tail, "\0"), -2) === "\xFF\xD9"
            : substr($tail, -8) === "IEND\xAE\x42\x60\x82";
        if (!$complete) {
            return ['error' => 'The photo is not a valid image.'];
        }
        return ['tmp' => $tmp, 'ext' => $types[$mime][1]];
    }
}
if (!function_exists('uknAvatarStore')) {
    /** Moves a validated upload into uploads/profiles/ under a new random name; returns its relative path or null. */
    function uknAvatarStore(array $valid): ?string
    {
        $name = bin2hex(random_bytes(16)) . '.' . $valid['ext'];
        $target = uknAvatarDir() . $name;
        if (!is_dir(uknAvatarDir()) || !move_uploaded_file($valid['tmp'], $target)) {
            return null;
        }
        @chmod($target, 0644);
        return 'profiles/' . $name;
    }
}
if (!function_exists('uknAvatarDelete')) {
    /** Deletes a stored avatar file; ignores anything that is not a generated profile path. */
    function uknAvatarDelete($path): void
    {
        if (!uknAvatarIsValidPath($path)) {
            return;
        }
        $file = uknAvatarDir() . basename($path);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
