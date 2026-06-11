<?php

if (!function_exists('ensure_preprint_moderation_schema')) {
    function ensure_preprint_moderation_schema(): void
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        global $conn;

        $columns = [
            'moderation_status' => "ALTER TABLE preprints ADD COLUMN moderation_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER project_id",
            'moderated_by' => "ALTER TABLE preprints ADD COLUMN moderated_by INT NULL DEFAULT NULL AFTER moderation_status",
            'moderated_at' => "ALTER TABLE preprints ADD COLUMN moderated_at TIMESTAMP NULL DEFAULT NULL AFTER moderated_by",
        ];

        foreach ($columns as $column => $alterSql) {
            $safeColumn = $conn->real_escape_string($column);
            $check = $conn->query("SHOW COLUMNS FROM preprints LIKE '{$safeColumn}'");

            if ($check && $check->num_rows === 0) {
                $conn->query($alterSql);
            }
        }

        $ensured = true;
    }
}

if (!function_exists('preprint_is_visible_to_user')) {
    function preprint_is_visible_to_user(array $preprint, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            return true;
        }

        if ((int)($preprint['author_id'] ?? 0) === $userId) {
            return true;
        }

        return ($preprint['moderation_status'] ?? 'approved') === 'approved';
    }
}

