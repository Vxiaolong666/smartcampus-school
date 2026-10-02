-- ============================================================
-- 每日维护时段（独立配置）升级脚本
-- 用途：为 nteam_config 增加独立的「每日维护时段」字段
--       Maintenance_Start / Maintenance_End，
--       不再复用 nteam_power_config 的「服务停止时段」(quiet_hours_*)。
-- 说明：
--   1) 这两个字段仅在「网站信息配置」(Adminq/set.php) 中维护；
--   2) 字段留空时，接口 get_maintenance_config 使用内置默认 23:00 ~ 06:00；
--      该时段与「电费推送配置 → 服务停止时段」(quiet_hours_*) 完全独立，互不取值；
--   3) 执行本脚本后，请到后台「网站信息配置 → 每日维护时段」填写期望时段，
--      否则客户端仍按内置默认 23:00 ~ 06:00 判断。
-- 注意：可重复执行（列已存在时会报 1060 错误，可忽略）
-- ============================================================

ALTER TABLE `nteam_config`
  ADD COLUMN `Maintenance_Start` VARCHAR(5) NOT NULL DEFAULT '' COMMENT '每日维护开始时间（HH:MM，留空=沿用旧配置）',
  ADD COLUMN `Maintenance_End`   VARCHAR(5) NOT NULL DEFAULT '' COMMENT '每日维护结束时间（HH:MM，留空=沿用旧配置）';
