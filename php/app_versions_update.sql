-- App 版本管理：建表 + 全局开关
-- 在数据库中执行本文件即可（对应后台「版本管理」页面 php/Adminq/version_manage.php）

CREATE TABLE IF NOT EXISTS `nteam_app_versions` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `version`      VARCHAR(32)  NOT NULL                COMMENT '版本号，如 1.3.26',
  `version_code` INT(11)      NOT NULL DEFAULT 0      COMMENT '数字版本，用于比较大小，如 1002',
  `platform`     VARCHAR(16)  NOT NULL DEFAULT 'all'  COMMENT '平台：all/android/ios/harmony',
  `force_update` TINYINT(1)   NOT NULL DEFAULT 0      COMMENT '是否强制更新：0否 1是',
  `download_url` VARCHAR(512) NOT NULL DEFAULT ''     COMMENT '下载/更新地址（商店或 apk 直链）',
  `changelog`    TEXT                             NULL COMMENT '更新日志，支持多行',
  `status`       TINYINT(1)   NOT NULL DEFAULT 1      COMMENT '版本开关：1已发布(上架) 0停用(下架)',
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_platform_status` (`platform`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='App 版本管理';

-- 全局开关「开启 App 版本更新检测」存放于 nteam_config（key: app_update_enabled）
-- 无需手动建列：后台首次保存时会由 saveSetting() 自动 ALTER 增加该列，默认未开启。
