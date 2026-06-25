<?php
// helpers/Validator.php
// Kumpulan fungsi bantu untuk validasi dan sanitasi input
// Tujuan: mencegah XSS dan memastikan data yang masuk sesuai format

class Validator
{
    /**
     * Membersihkan string dari tag HTML/script untuk mencegah XSS.
     */
    public static function sanitize(string $input): string
    {
        $input = trim($input);
        $input = strip_tags($input);
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validasi format email.
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validasi agar field tidak kosong.
     */
    public static function isNotEmpty(string $value): bool
    {
        return trim($value) !== '';
    }

    /**
     * Validasi panjang minimal password.
     */
    public static function isValidPassword(string $password, int $minLength = 8): bool
    {
        return strlen($password) >= $minLength;
    }

    /**
     * Validasi tipe file lampiran (whitelist ekstensi).
     */
    public static function isAllowedFileType(string $filename, array $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'docx']): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowed);
    }

    /**
     * Validasi ukuran file maksimal (dalam bytes). Default 2MB.
     */
    public static function isAllowedFileSize(int $sizeInBytes, int $maxBytes = 2097152): bool
    {
        return $sizeInBytes <= $maxBytes;
    }
}
