-- ============================================================
-- 用户端「账号注销」功能 数据库更新脚本
-- 适用：已安装的旧库增量升级（请仅执行一次）
-- ============================================================

-- 1) nteam_users 增加冷静期字段
ALTER TABLE `nteam_users`
  ADD COLUMN `is_cancelling`      TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否处于注销冷静期 0否 1是',
  ADD COLUMN `cancel_time`        DATETIME   DEFAULT NULL COMMENT '提交注销申请时间',
  ADD COLUMN `cancel_expire_time` DATETIME   DEFAULT NULL COMMENT '冷静期结束（正式注销）时间',
  ADD INDEX `idx_is_cancelling` (`is_cancelling`, `cancel_expire_time`);

-- 2) 账号注销审计表
CREATE TABLE IF NOT EXISTS `nteam_account_deletions` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `user_id`           INT NOT NULL COMMENT '被注销用户ID',
  `username`          VARCHAR(50)  NOT NULL COMMENT '用户名快照',
  `email`             VARCHAR(100) DEFAULT NULL COMMENT '邮箱快照',
  `method`            VARCHAR(20)  NOT NULL DEFAULT 'self' COMMENT '方式：self自助/oauth第三方登录入口',
  `status`            VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT '状态：pending冷静期/cancelled已撤销/deleted已删除',
  `verify_mode`       VARCHAR(20)  DEFAULT NULL COMMENT '注销时的验证方式快照',
  `cooling_enabled`   TINYINT(1)   DEFAULT NULL COMMENT '注销时冷静期是否开启',
  `cooling_days`      INT          DEFAULT NULL COMMENT '注销时冷静期天数',
  `agreement_agreed`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '是否已同意注销服务协议',
  `agreement_time`    DATETIME     DEFAULT NULL COMMENT '协议同意时间',
  `agreement_ip`      VARCHAR(50)  DEFAULT NULL COMMENT '协议同意IP',
  `academic_retained` TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '是否保留教务账号记录（内部字段）',
  `request_ip`        VARCHAR(50)  DEFAULT NULL COMMENT '申请IP',
  `cancel_time`       DATETIME     DEFAULT NULL COMMENT '申请时间',
  `expire_time`       DATETIME     DEFAULT NULL COMMENT '冷静期结束时间',
  `revoke_time`       DATETIME     DEFAULT NULL COMMENT '撤销（登录恢复）时间',
  `delete_time`       DATETIME     DEFAULT NULL COMMENT '实际删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号注销审计表';

-- 3) nteam_config 增加注销相关配置列（saveSetting 也会按需自动补列）
ALTER TABLE `nteam_config`
  ADD COLUMN `Cancel_Enable`         TINYINT(1)  NOT NULL DEFAULT 0          COMMENT '注销功能总开关 0关 1开',
  ADD COLUMN `Cancel_Verify_Mode`    VARCHAR(20) NOT NULL DEFAULT 'password' COMMENT '验证方式 password/email/password_email',
  ADD COLUMN `Cancel_Cooling_Enable` TINYINT(1)  NOT NULL DEFAULT 1          COMMENT '冷静期开关 0关 1开',
  ADD COLUMN `Cancel_Cooling_Days`   INT         NOT NULL DEFAULT 10         COMMENT '冷静期天数（1-90）',
  ADD COLUMN `Cancel_Mail_Submit`    TINYINT(1)  NOT NULL DEFAULT 1          COMMENT '提交注销时是否发送通知邮件',
  ADD COLUMN `Cancel_Mail_Deleted`   TINYINT(1)  NOT NULL DEFAULT 1          COMMENT '注销成功时是否发送通知邮件',
  ADD COLUMN `Cancel_Agreement`      TEXT                                    COMMENT '《用户注销服务协议》全文';

-- 5) nteam_config 追加"撤销通知"开关
--    （若第 3 步已执行过，可单独执行本语句；saveSetting 保存配置时也会自动补列）
ALTER TABLE `nteam_config`
  ADD COLUMN `Cancel_Mail_Revoked`   TINYINT(1)  NOT NULL DEFAULT 1          COMMENT '注销申请被自动撤销时是否发送通知邮件';
