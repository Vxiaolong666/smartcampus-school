<?php

// 「总公告」保留的功能标识：该行作为全局统一公告的开关与内容。
// 开启后所有页面的公告统一替换为它的内容，关闭后各页面按各自的公告显示。
if (!defined('GLOBAL_ANNOUNCEMENT_KEY')) {
    define('GLOBAL_ANNOUNCEMENT_KEY', 'global_announcement');
}

/**
 * 读取「总公告」（全局统一公告）
 * 仅在「已开启」且公告内容非空时返回；其余情况返回 null（表示不启用全局替换）。
 *
 * @param \lib\PdoHelper $DB
 * @return array|null ['content' => string, 'type' => string]
 */
function getGlobalAnnouncement($DB) {
    if (empty($DB) || !is_object($DB)) {
        return null;
    }
    try {
        $row = $DB->getRow("SELECT is_enabled, announcement, announcement_type FROM nteam_feature_settings WHERE feature_key='" . GLOBAL_ANNOUNCEMENT_KEY . "'");
    } catch (Exception $e) {
        return null;
    }
    if (!$row || intval($row['is_enabled']) !== 1) {
        return null;
    }
    if (trim((string)$row['announcement']) === '') {
        return null;
    }
    return [
        'content' => $row['announcement'],
        'type'    => !empty($row['announcement_type']) ? $row['announcement_type'] : 'info',
    ];
}

function getFeatureSetting($DB, $featureKey) {
    return $DB->getRow("SELECT * FROM nteam_feature_settings WHERE feature_key = '$featureKey'");
}

function isFeatureEnabled($DB, $featureKey) {
    $setting = getFeatureSetting($DB, $featureKey);
    return $setting && $setting['is_enabled'] == 1;
}

/**
 * 兼容老库：确保 nteam_feature_settings 存在 follow_global 字段
 * follow_global —— 公告是否跟随总开关：
 *   1 = 跟随（默认，总开关开启时被总公告统一替换）
 *   0 = 不跟随（不受总开关影响，始终显示该功能自己的公告）
 * 读取不到该字段时按「跟随」处理，所以即便迁移失败也不会改变原有行为。
 */
function ensureFeatureSettingsColumn($DB) {
    static $done = null;
    if ($done !== null) {
        return $done;
    }
    $done = false;
    if (empty($DB) || !is_object($DB)) {
        return $done;
    }
    try {
        $col = $DB->getRow("SHOW COLUMNS FROM nteam_feature_settings LIKE 'follow_global'");
        if (!$col) {
            $DB->exec("ALTER TABLE nteam_feature_settings ADD COLUMN follow_global TINYINT(1) NOT NULL DEFAULT 1 COMMENT '公告是否跟随总开关：1=跟随，0=不跟随'");
        }
        $done = true;
    } catch (Exception $e) {
        $done = false;
    }
    return $done;
}

function getFeatureAnnouncement($DB, $featureKey) {
    ensureFeatureSettingsColumn($DB);
    $setting = getFeatureSetting($DB, $featureKey);
    // 未勾选「跟随总开关」的功能不受总公告影响；字段缺失（老库）按「跟随」处理，保持原有行为
    $followGlobal = !$setting || !isset($setting['follow_global']) || intval($setting['follow_global']) === 1;

    if ($followGlobal) {
        // 跟随总开关：总公告开启时统一使用总公告（覆盖该功能自己的公告）
        $global = getGlobalAnnouncement($DB);
        if ($global !== null) {
            return $global;
        }
    }
    if ($setting && !empty($setting['announcement'])) {
        return [
            'content' => $setting['announcement'],
            'type' => $setting['announcement_type'] ?? 'info'
        ];
    }
    return null;
}

function renderFeatureAnnouncement($DB, $featureKey) {
    $announcement = getFeatureAnnouncement($DB, $featureKey);
    if ($announcement) {
        $type = $announcement['type'];
        $alertClass = 'alert-' . $type;
        $icon = 'fa-info-circle';
        if ($type === 'success') $icon = 'fa-check-circle';
        if ($type === 'warning') $icon = 'fa-exclamation-triangle';
        if ($type === 'danger') $icon = 'fa-times-circle';
        
        echo '<div class="alert ' . $alertClass . ' mb-4">';
        echo '<i class="fa ' . $icon . ' me-2"></i>';
        echo htmlspecialchars($announcement['content']);
        echo '</div>';
    }
}

function checkFeatureAccess($DB, $featureKey) {
    if (!isFeatureEnabled($DB, $featureKey)) {
        $setting = getFeatureSetting($DB, $featureKey);
        $featureName = $setting ? $setting['feature_name'] : '该功能';
        $announcement = getFeatureAnnouncement($DB, $featureKey);
        
        echo '<div class="alert alert-warning mb-4">';
        echo '<i class="fa fa-lock me-2"></i>';
        if ($announcement) {
            echo htmlspecialchars($announcement['content']);
        } else {
            echo $featureName . '暂未开放，请稍后再试';
        }
        echo '</div>';
        return false;
    }
    return true;
}
