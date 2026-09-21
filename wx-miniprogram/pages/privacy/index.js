
Page({
  // 关于我们
  goAbout() {
    wx.navigateTo({ url: '/pages/about/index' });
  },

  // 联系客服：先询问「在线客服 / 其他联系方式」
  openContactGuide() {
    const guide = this.selectComponent('#contactGuide');
    if (guide) guide.open();
  }
});
