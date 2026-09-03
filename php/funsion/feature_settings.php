<?php

function getFeatureSetting($DB, $featureKey) {
    return $DB->getRow("SELECT * FROM nteam_feature_settings WHERE feature_key = '$featureKey'");
}

function isFeatureEnabled($DB, $featureKey) {
    $setting = getFeatureSetting($DB, $featureKey);
    return $setting && $setting['is_enabled'] == 1;
}

function getFeatureAnnouncement($DB, $featureKey) {
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
