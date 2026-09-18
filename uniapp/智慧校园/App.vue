<script>
	import api from './utils/api.js';
	import feature from './utils/feature.js';

	// 内置兜底时段（后台未配置时使用）：23:00 - 次日 06:00
	const DEFAULT_START = '23:00';
	const DEFAULT_END = '06:00';

	const toMinutes = (hhmm) => {
		const parts = String(hhmm || '').split(':');
		const h = parseInt(parts[0] || '0', 10);
		const m = parseInt(parts[1] || '0', 10);
		return (isNaN(h) ? 0 : h) * 60 + (isNaN(m) ? 0 : m);
	};

	// 支持跨天时段（如 22:00 - 次日 08:00）
	const inWindow = (nowMin, startMin, endMin) => {
		if (startMin === endMin) return false;
		if (startMin < endMin) return nowMin >= startMin && nowMin < endMin;
		return nowMin >= startMin || nowMin < endMin;
	};

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
		methods: {
		/**
		 * 读取后台「服务停止时段」配置（带缓存，失败时用内置默认值）
		 */
		async getMaintenanceWindow() {
			if (this._maintWindow) return this._maintWindow;
			let start = DEFAULT_START;
			let end = DEFAULT_END;
			try {
				const res = await api.getMaintenanceConfig();
				start = (res.data && res.data.start) || DEFAULT_START;
				end = (res.data && res.data.end) || DEFAULT_END;
			} catch (e) {
				// 沿用默认值
			}
			this._maintWindow = { start, end };
			return this._maintWindow;
		},

		/**
		 * 检查是否处于维护状态并跳转系统维护页
		 * 条件1：当前时间在维护时间段（后台配置的服务停止时段，默认 23:00 - 次日 6:00）
		 * 条件2：维护页面功能开关启用（weihu_open 的 is_enabled = 1，即管理员手动关闭服务）
		 * 满足任一条件则跳转到 pages/maintenance/index
		 * 逻辑与小程序端 app.js 的 checkMaintenanceTime 保持一致
		 */
		async checkMaintenance() {
			try {
				// 条件1：检查时间（使用后台配置的时段）
				const win = await this.getMaintenanceWindow();
				const now = new Date();
				const nowMin = now.getHours() * 60 + now.getMinutes();
				const isMaintenanceTime = inWindow(nowMin, toMinutes(win.start), toMinutes(win.end));

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
				// （时段同样取自后台配置，取不到时用内置默认值）
				let winStart = DEFAULT_START;
				let winEnd = DEFAULT_END;
				try {
					const win = await this.getMaintenanceWindow();
					winStart = win.start;
					winEnd = win.end;
				} catch (e) {}
				const now = new Date();
				const nowMin = now.getHours() * 60 + now.getMinutes();
				const isMaintenanceTime = inWindow(nowMin, toMinutes(winStart), toMinutes(winEnd));

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

	/* ===== AI 智能分析：页内触发按钮（全站统一样式，与「成绩查询」页一致） ===== */
	.ai-analyze-wrap {
		display: flex;
		justify-content: center;
		margin-top: 30rpx;
	}

	.ai-analyze-btn {
		background: linear-gradient(135deg, #13c2c2, #1890ff);
		color: #ffffff;
		font-size: 28rpx;
		padding: 20rpx 60rpx;
		border-radius: 40rpx;
		box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
	}

	.ai-analyze-btn.ai-loading {
		opacity: 0.7;
	}

	/* ===== AI 智能分析：右侧浮动圆形触发按钮（各 AI 页面通用） ===== */
	.ai-fab {
		position: fixed;
		right: 28rpx;
		bottom: 160rpx;
		width: 104rpx;
		height: 104rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, #13c2c2, #1890ff);
		box-shadow: 0 12rpx 30rpx rgba(19, 194, 194, 0.45);
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		z-index: 90;
		animation: aiFabPulse 2.4s ease-out infinite;
	}

	/* tabBar 页面（如今日课程）需再抬高，避免被底部标签栏遮挡 */
	.ai-fab--tab {
		bottom: 250rpx;
	}

	.ai-fab-icon {
		color: #ffffff;
		font-size: 32rpx;
		font-weight: bold;
		line-height: 1.05;
	}

	.ai-fab-label {
		color: #ffffff;
		font-size: 20rpx;
		line-height: 1.05;
		opacity: 0.92;
	}

	.ai-fab:active {
		transform: scale(0.92);
	}

	.ai-fab.ai-loading {
		opacity: 0.75;
	}

	/* 呼吸光圈，提示「可点击生成 AI 分析」 */
	@keyframes aiFabPulse {
		0% {
			box-shadow: 0 12rpx 30rpx rgba(19, 194, 194, 0.45), 0 0 0 0 rgba(19, 194, 194, 0.4);
		}
		70% {
			box-shadow: 0 12rpx 30rpx rgba(19, 194, 194, 0.45), 0 0 0 20rpx rgba(19, 194, 194, 0);
		}
		100% {
			box-shadow: 0 12rpx 30rpx rgba(19, 194, 194, 0.45), 0 0 0 0 rgba(19, 194, 194, 0);
		}
	}
</style>
