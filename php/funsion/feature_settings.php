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

function getFeatureAnnouncement($DB, $featureKey) {
    // 总公告开启时：所有页面统一使用总公告（覆盖各功能自己的公告）
    $global = getGlobalAnnouncement($DB);
    if ($global !== null) {
        return $global;
    }
    $setting = getFeatureSetting($DB, $featureKey);
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
