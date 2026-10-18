/**
 * 首页骨架屏（仅首页使用）
 * 结构与首页内容区一一对应：轮播 → 快捷入口 → 功能入口 → 今日速览 → 通知公告
 * 采用浅紫灰底 + 白色高光扫过，贴合项目淡紫风格
 */
Component({
  properties: {
    // 低端机可传 false 关闭呼吸/扫光动画（纯静态骨架）
    animated: {
      type: Boolean,
      value: true
    }
  },

  data: {
    menuList: [1, 2, 3],
    subList: [1, 2],
    cardList: [1, 2],
    noticeList: [1, 2]
  }
});
