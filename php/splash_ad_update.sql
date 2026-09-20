-- ============================================================
-- 开屏广告（小程序 / Uniapp 启动时展示）
-- 在数据库中执行本文件即可（对应后台「开屏广告」页面 php/Adminq/splash_config.php）
-- ============================================================

CREATE TABLE IF NOT EXISTS `nteam_splash_ads` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `title`      varchar(100) DEFAULT ''  COMMENT '名称（管理备注，不展示）',
  `image`      varchar(512) DEFAULT ''  COMMENT '图片路径（站点相对路径，如 /uploads/splash/xxx.jpg）',
  `url`        varchar(512) DEFAULT ''  COMMENT '通用跳转链接（三端未单独设置时兜底）',
  `url_wx`     varchar(512) DEFAULT ''  COMMENT '小程序跳转链接（空则回退到通用链接）',
  `url_app`    varchar(512) DEFAULT ''  COMMENT 'Uniapp(App)跳转链接（空则回退到通用链接）',
  `duration`   int(11)      DEFAULT 3   COMMENT '展示时长（秒），倒计时结束自动关闭',
  `sort`       int(11)      DEFAULT 0   COMMENT '排序，数字越小越靠前',
  `status`     tinyint(1)   DEFAULT 1   COMMENT '状态：1=启用 0=停用',
  `created_at` datetime     DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序/uniapp 开屏广告';

-- 全局开关「开启开屏广告」存放于 nteam_config（key: splash_enabled）
-- 无需手动建列：后台首次切换时会由 saveSetting() 自动 ALTER 增加该列，默认未开启。
