-- ============================================================
-- 课程时间（作息）配置
-- 功能：教务接口只返回「节数」（如 classTime=105106 表示第5、6节），
--       各节次的起止时间改为在后台「课程时间配置」中按「课程模式」维护。
-- 说明：可重复执行；已存在同名表时不会重复建表，默认模式仅在缺失时补一条。
-- ============================================================

CREATE TABLE IF NOT EXISTS `nteam_schedule_modes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mode_id` varchar(50) NOT NULL DEFAULT '' COMMENT '课程模式ID（教务课表模式的 kbjcmsid，留空表示默认模式/通用作息）',
  `mode_name` varchar(100) NOT NULL DEFAULT '' COMMENT '课程模式名称',
  `periods` text COMMENT '节次时间配置，JSON：[{"name":"第1节","start":"08:15","end":"08:55"}]',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认模式（未匹配到模式时使用）',
  `sort_order` int(11) NOT NULL DEFAULT '0' COMMENT '排序（越小越前）',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mode_id` (`mode_id`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='课程模式与节次时间配置';

-- ------------------------------------------------------------
-- 默认模式（与旧版写死的作息一致，若已存在则不重复写入）
-- ------------------------------------------------------------
INSERT INTO `nteam_schedule_modes` (`mode_id`, `mode_name`, `periods`, `is_default`, `sort_order`)
SELECT '', '默认作息（标准）',
'[{"name":"第1节","start":"08:15","end":"08:55"},{"name":"第2节","start":"09:05","end":"09:45"},{"name":"第3节","start":"10:05","end":"10:45"},{"name":"第4节","start":"10:55","end":"11:35"},{"name":"第5节","start":"14:30","end":"15:10"},{"name":"第6节","start":"15:20","end":"16:00"},{"name":"第7节","start":"16:15","end":"16:55"},{"name":"第8节","start":"18:50","end":"19:30"},{"name":"第9节","start":"19:40","end":"20:20"},{"name":"第10节","start":"20:30","end":"21:10"},{"name":"第11节","start":"21:20","end":"22:00"}]',
1, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nteam_schedule_modes` WHERE `mode_id` = '');
