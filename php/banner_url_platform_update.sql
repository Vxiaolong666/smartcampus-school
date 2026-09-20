-- ============================================================
-- 轮播图：支持「小程序 / 网页端 / Uniapp」分别设置跳转链接
-- 已部署过 banner_update.sql 的环境，执行本文件即可（全新部署无需执行，
-- 直接执行 banner_update.sql 已含以下字段）。
-- ============================================================

ALTER TABLE `nteam_banners`
  ADD COLUMN `url_wx`  varchar(512) DEFAULT '' COMMENT '小程序跳转链接（空则回退到 url）' AFTER `url`,
  ADD COLUMN `url_app` varchar(512) DEFAULT '' COMMENT 'Uniapp(App)跳转链接（空则回退到 url）' AFTER `url_wx`,
  ADD COLUMN `url_web` varchar(512) DEFAULT '' COMMENT '网页端跳转链接（空则用通用链接中的站外 http(s) 地址）' AFTER `url_app`;

-- 迁移历史数据：原有 url 视为小程序与 Uniapp 的通用链接（两者页面路由一致）
UPDATE `nteam_banners` SET `url_wx`  = `url` WHERE (`url_wx`  IS NULL OR `url_wx`  = '') AND `url` <> '';
UPDATE `nteam_banners` SET `url_app` = `url` WHERE (`url_app` IS NULL OR `url_app` = '') AND `url` <> '';
