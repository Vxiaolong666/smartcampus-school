-- ============================================================
-- 聚合登录（第三方登录）数据库更新
-- 数据源：黎洛云聚合 https://u.daib.cn/doc.php#doc4
-- ============================================================

-- 1) 聚合登录配置表：存放 AppID / AppKey、总开关、已启用渠道
CREATE TABLE IF NOT EXISTS `nteam_oauth_config` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `enable`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '聚合登录总开关 1开 0关',
  `appid`      VARCHAR(64) NOT NULL DEFAULT '' COMMENT '聚合登录 AppID',
  `appkey`     VARCHAR(64) NOT NULL DEFAULT '' COMMENT '聚合登录 AppKey',
  `channels`   TEXT COMMENT '已启用的登录渠道(type)，JSON 数组，如 ["qq","wx","alipay"]',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聚合登录配置表';

-- 2) 第三方授权绑定表：存放用户与第三方账号的绑定关系
--    唯一索引 (oauth_type, oauth_uid)：保证同一平台同一第三方账号不会重复插入
CREATE TABLE IF NOT EXISTS `nteam_user_oauth` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `user_id`      INT NOT NULL COMMENT '关联用户ID，关联 nteam_users.id',
  `oauth_type`   VARCHAR(20) NOT NULL COMMENT '第三方平台类型 qq/wx/alipay/sina/...',
  `oauth_uid`    VARCHAR(64) NOT NULL COMMENT '第三方平台唯一标识 social_uid',
  `access_token` VARCHAR(255) DEFAULT NULL COMMENT '第三方授权 token',
  `nickname`     VARCHAR(100) DEFAULT NULL COMMENT '第三方昵称',
  `avatar`       VARCHAR(512) DEFAULT NULL COMMENT '第三方头像地址',
  `gender`       VARCHAR(10) DEFAULT NULL COMMENT '性别',
  `create_time`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `update_time`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_oauth` (`oauth_type`, `oauth_uid`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方授权绑定表';

-- 3) 可选：插入默认配置（已填入你提供的 AppID / AppKey，默认关闭）
--    如需开启，将 enable 改为 1 并在后台“聚合登录配置”中勾选渠道
INSERT INTO `nteam_oauth_config` (`enable`, `appid`, `appkey`, `channels`)
VALUES (0, '2746', '7f45cd060102b5753e0e7de0afff68f1', '["qq","wx","alipay","sina"]');
