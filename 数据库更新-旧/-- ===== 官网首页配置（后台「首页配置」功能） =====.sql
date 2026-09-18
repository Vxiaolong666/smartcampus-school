-- ===== 官网首页配置（后台「首页配置」功能） =====

-- 1) 基础配置（单条记录 id=1）
CREATE TABLE IF NOT EXISTS `nteam_home_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `site_url` varchar(255) NOT NULL DEFAULT '' COMMENT '网站地址',
    `contact_email` varchar(128) NOT NULL DEFAULT '' COMMENT '联系邮箱',
    `contact_phone` varchar(64) NOT NULL DEFAULT '' COMMENT '联系电话',
    `contact_qq` varchar(64) NOT NULL DEFAULT '' COMMENT '联系QQ',
    `contact_address` varchar(255) NOT NULL DEFAULT '' COMMENT '公司地址',
    `contact_worktime` varchar(128) NOT NULL DEFAULT '' COMMENT '工作时间',
    `contact_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '联系我们页简介',
    `contact_form_open` tinyint(1) NOT NULL DEFAULT 1 COMMENT '联系我们表单开关 1开 0关',
    `contact_form_captcha` tinyint(1) NOT NULL DEFAULT 1 COMMENT '提交前是否需滑块/验证码 1是 0否',
    `download_intro` varchar(255) NOT NULL DEFAULT '' COMMENT '下载页说明',
    `faq_intro` varchar(255) NOT NULL DEFAULT '' COMMENT '常见问题页说明',
    `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='官网首页配置';

INSERT INTO `nteam_home_config` (`id`,`site_url`,`contact_email`,`contact_phone`,`contact_qq`,
        `contact_address`,`contact_worktime`,`contact_desc`,`contact_form_open`,`contact_form_captcha`,
        `download_intro`,`faq_intro`)
SELECT 1,'','support@zhixiaoyun.com','','','','周一至周五 9:00–18:00（GMT+8）','',1,1,'',''
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `nteam_home_config` WHERE `id`=1);

-- 2) 下载方式（下载地址）
CREATE TABLE IF NOT EXISTS `nteam_home_download` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(128) NOT NULL DEFAULT '' COMMENT '名称，如 Android APK',
    `url` varchar(512) NOT NULL DEFAULT '' COMMENT '下载地址',
    `icon` varchar(64) NOT NULL DEFAULT '' COMMENT '图标 class，如 fab fa-android',
    `description` varchar(255) NOT NULL DEFAULT '' COMMENT '说明',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序（小在前）',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1显示 0隐藏',
    `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='官网下载方式';

-- 3) 常见问题
CREATE TABLE IF NOT EXISTS `nteam_home_faq` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category` varchar(64) NOT NULL DEFAULT '常见问题' COMMENT '分类',
    `question` varchar(255) NOT NULL DEFAULT '' COMMENT '问题',
    `answer` text COMMENT '答案（支持简单 HTML）',
    `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序（小在前）',
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1显示 0隐藏',
    `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `update_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='官网常见问题';

-- 4) 联系我们留言
CREATE TABLE IF NOT EXISTS `nteam_contact_msgs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(64) NOT NULL DEFAULT '' COMMENT '姓名',
    `phone` varchar(32) NOT NULL DEFAULT '' COMMENT '手机',
    `email` varchar(128) NOT NULL DEFAULT '' COMMENT '邮箱',
    `subject` varchar(255) NOT NULL DEFAULT '' COMMENT '主题',
    `content` text COMMENT '留言内容',
    `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '提交 IP',
    `ua` varchar(255) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未读 1已读',
    `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='官网联系我们留言';
