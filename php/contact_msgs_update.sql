-- ============================================================
-- 联系我们留言 / 用户反馈 功能升级脚本
-- 用途：为 nteam_contact_msgs 增加反馈功能所需字段与索引
--       （user_id / source / handle_status / remark / update_time）
-- 说明：代码中 ContactMsgs.php 的 contactMsgEnsureSchema() 会自动幂等补齐，
--       本脚本用于不方便开放 ALTER 权限时手动导入；两种方式等效。
-- 注意：可重复执行（列已存在时会报 1060 错误，可忽略）
-- ============================================================

ALTER TABLE `nteam_contact_msgs`
  ADD COLUMN `user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '关联用户ID（0=官网访客）',
  ADD COLUMN `source` VARCHAR(16) NOT NULL DEFAULT 'site' COMMENT '来源：site=官网，user=用户端',
  ADD COLUMN `handle_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '处理状态：0待处理 1处理中 2已解决 3已驳回 4已忽略',
  ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注（用户端可见）',
  ADD COLUMN `update_time` DATETIME NULL DEFAULT NULL COMMENT '状态最后更新时间';

ALTER TABLE `nteam_contact_msgs`
  ADD INDEX `user_id` (`user_id`);

-- 说明：原有 `status` 字段继续表示「已读 / 未读」（0 未读、1 已读），
--       与 `handle_status`（处理进度）是两个维度，互不覆盖。
