-- ============================================================
-- 轮播图配置（后台「轮播图配置」功能）
-- 在数据库中执行本文件以创建 nteam_banners 表并写入默认的 3 张轮播图
-- 图片文件已复制到 php/uploads/banners/（站点路径 /uploads/banners/）
-- ============================================================

CREATE TABLE IF NOT EXISTS `nteam_banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT '' COMMENT '名称（管理备注，不展示）',
  `image` varchar(512) DEFAULT '' COMMENT '图片路径（站点相对路径，如 /uploads/banners/banner1.jpg）',
  `url` varchar(512) DEFAULT '' COMMENT '通用跳转链接（三端未单独设置时的兜底；空=不跳转；支持站内路径或完整 http(s) URL）',
  `url_wx` varchar(512) DEFAULT '' COMMENT '小程序跳转链接（空则回退到 url）',
  `url_app` varchar(512) DEFAULT '' COMMENT 'Uniapp(App)跳转链接（空则回退到 url）',
  `url_web` varchar(512) DEFAULT '' COMMENT '网页端跳转链接（空则用通用链接中的站外 http(s) 地址）',
  `sort` int(11) DEFAULT 0 COMMENT '排序，数字越小越靠前',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1=启用 0=禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序/uniapp 首页轮播图配置';

-- 迁移当前硬编码的 3 张轮播图（图片对应 uniapp/static/assets/banner 与 wx-miniprogram/assets/banner）
INSERT INTO `nteam_banners` (`title`, `image`, `url`, `sort`, `status`) VALUES
('轮播图1', '/uploads/banners/banner1.jpg', '', 1, 1),
('轮播图2', '/uploads/banners/banner2.jpg', '/pages/academic-push-settings/index', 2, 1),
('轮播图3', '/uploads/banners/banner3.jpg', '/pages/academic-grade/index', 3, 1);
