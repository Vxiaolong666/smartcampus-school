<script>
	import feature from './utils/feature.js';

	export default {
		onLaunch: function() {
			console.log('App Launch')
			// 预取功能开关并缓存，避免进入业务页面时因等待网络请求而卡顿
			feature.getFeatureSettings();
			// #ifdef APP-PLUS
			// 全站头部均为深色渐变背景（rgba(0,0,0,0.8)），将状态栏文字/图标设为浅色
			// （白色），避免打包成 App / APK 后状态栏黑字叠在深底色上看不清。
			try {
				plus.navigator.setStatusBarStyle('light');
			} catch (e) {}
			// #endif
			// 启动时检查是否处于维护状态，是则跳转系统维护页
			this.checkMaintenance();
		},
		onShow: function() {
			console.log('App Show')
			// #ifdef APP-PLUS
			// App 从后台回到前台时再设置一次，防止系统状态栏样式被还原
			try {
				plus.navigator.setStatusBarStyle('light');
			} catch (e) {}
			// #endif
			// 从后台回到前台时再次检查维护状态
			this.checkMaintenance();
		},
		onHide: function() {
			console.log('App Hide')
		},
		/**
		 * 检查是否处于维护状态并跳转系统维护页
		 * 条件1：当前时间在维护时间段（23:00 - 次日 6:00，受学校教务系统每晚关闭维护影响）
		 * 条件2：维护页面功能开关启用（weihu_open 的 is_enabled = 1，即管理员手动关闭服务）
		 * 满足任一条件则跳转到 pages/maintenance/index
		 * 逻辑与小程序端 app.js 的 checkMaintenanceTime 保持一致
		 */
		async checkMaintenance() {
			try {
				// 条件1：检查时间
				const now = new Date();
				const hour = now.getHours();
				const isMaintenanceTime = hour >= 23 || hour < 6;

				// 条件2：检查功能开关（管理员手动关闭服务）
				const featureStatus = await feature.checkFeature('weihu_open');
				const isFeatureEnabled = featureStatus && featureStatus.enabled === true;

				// 满足任一条件则跳转
				if (isMaintenanceTime || isFeatureEnabled) {
					const pages = getCurrentPages();
					const currentPage = pages[pages.length - 1];
					const currentRoute = currentPage ? currentPage.route : '';

					// 当前已在维护页则不再跳转
					if (currentRoute !== 'pages/maintenance/index') {
						uni.redirectTo({
							url: '/pages/maintenance/index',
							fail: () => {
								// redirectTo 失败（如当前为 tabBar 页）时改用 reLaunch
								uni.reLaunch({
									url: '/pages/maintenance/index'
								});
							}
						});
					}
				}
			} catch (error) {
				// 检查失败时只按时间判断，保证夜间维护仍可正常提示
				const now = new Date();
				const hour = now.getHours();
				const isMaintenanceTime = hour >= 23 || hour < 6;

				if (isMaintenanceTime) {
					const pages = getCurrentPages();
					const currentPage = pages[pages.length - 1];
					const currentRoute = currentPage ? currentPage.route : '';

					if (currentRoute !== 'pages/maintenance/index') {
						uni.redirectTo({
							url: '/pages/maintenance/index',
							fail: () => {
								uni.reLaunch({
									url: '/pages/maintenance/index'
								});
							}
						});
					}
				}
			}
		}
	}
</script>

<style>
	/*每个页面公共css */

	/* AI 结果浮层：遮罩本身作为滚动容器并禁止溢出滚动链式传递给背景页面，
	   避免手指在遮罩上滑动时页面跟着滚动 */
	.ai-mask {
		overflow: hidden;
		overscroll-behavior: contain;
	}

	/* 浮层内部正文滚动到顶/到底时，不要把剩余滚动量继续传递给背景页面 */
	.ai-modal-body,
	.ai-modal-body .uni-scroll-view {
		overscroll-behavior: contain;
	}

	/* 拖拽调整浮层高度时不触发任何原生滚动 */
	.ai-modal-handle {
		touch-action: none;
	}
</style>
