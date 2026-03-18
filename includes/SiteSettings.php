<?php
/**
 * Configuracion general del sitio
 */

class SiteSettings {
    private const DEFAULTS = [
        'site_name' => 'CCE Certificados',
        'institution_name' => 'Casa de la Cultura Ecuatoriana',
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'logo_nav' => '',
        'logo_header' => '',
        'favicon' => ''
    ];

    public static function ensureTable(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public static function get(PDO $pdo): array {
        self::ensureTable($pdo);

        $stmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $settings = self::DEFAULTS;
        foreach ($rows as $key => $value) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = (string)$value;
            }
        }

        return $settings;
    }

    public static function save(PDO $pdo, array $data): void {
        self::ensureTable($pdo);

        $allowed = array_keys(self::DEFAULTS);
        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $stmt->execute([$key, $data[$key]]);
        }
    }

    public static function toViewModel(array $settings, string $basePath): array {
        $base = rtrim($basePath, '/');
        $logoNavPath = trim((string)($settings['logo_nav'] ?? ''));
        $logoHeaderPath = trim((string)($settings['logo_header'] ?? ''));
        $faviconPath = trim((string)($settings['favicon'] ?? ''));

        return [
            'site_name' => $settings['site_name'] ?? self::DEFAULTS['site_name'],
            'institution_name' => $settings['institution_name'] ?? self::DEFAULTS['institution_name'],
            'primary_color' => self::sanitizeColor($settings['primary_color'] ?? self::DEFAULTS['primary_color']),
            'secondary_color' => self::sanitizeColor($settings['secondary_color'] ?? self::DEFAULTS['secondary_color']),
            'logo_nav' => $logoNavPath,
            'logo_header' => $logoHeaderPath,
            'favicon' => $faviconPath,
            'logo_nav_url' => self::buildAssetUrl($base, $logoNavPath),
            'logo_header_url' => self::buildAssetUrl($base, $logoHeaderPath),
            'favicon_url' => self::buildAssetUrl($base, $faviconPath)
        ];
    }

    public static function sanitizeColor(string $color): string {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : self::DEFAULTS['primary_color'];
    }

    private static function buildAssetUrl(string $basePath, string $path): string {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}
