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
			// 去重：H5 启动时 onLaunch 与 onShow 会几乎同时触发，避免并发检查与并发导航互相取消
			if (this._maintChecking) return;
			this._maintChecking = true;
			try {
				// 条件1：检查时间（使用后台配置的时段）
				// getMaintenanceWindow 内部已做兜底，取不到时用内置默认值，不会抛错
				const win = await this.getMaintenanceWindow();
				const now = new Date();
				const nowMin = now.getHours() * 60 + now.getMinutes();
				const isMaintenanceTime = inWindow(nowMin, toMinutes(win.start), toMinutes(win.end));

				// 条件2：管理员是否手动开启维护
				// 必须「明确读到 weihu_open 且 is_enabled=1」才算维护中；
				// 接口失败 / 未配置该开关一律按未开启处理，避免误跳维护页
				let isFeatureEnabled = false;
				try {
					const sw = await feature.checkMaintenanceSwitch();
					isFeatureEnabled = !!(sw && sw.enabled === true);
				} catch (e) {
					isFeatureEnabled = false;
				}

				// 满足任一条件则跳转
				if (isMaintenanceTime || isFeatureEnabled) {
					this.navigateToMaintenance();
				}
			} finally {
				this._maintChecking = false;
			}
		},

		/**
		 * 跳转到系统维护页
		 * 维护页需要接管整个应用，直接使用 reLaunch 清空页面栈、一次性完成，
		 * 避免 redirectTo 失败后再 reLaunch 造成的「二次导航」与被取消的导航竞态
		 * （即 uni-h5 报 Navigation cancelled / Uncaught (in promise) 的根因）。
		 * H5 首屏路由尚未就绪时会取消导航，这里做少量重试，并吞掉被取消的 Promise 拒绝。
		 */
		navigateToMaintenance() {
			const pages = getCurrentPages();
			const currentPage = pages[pages.length - 1];
			const currentRoute = currentPage ? (currentPage.route || '') : '';
			if (currentRoute === 'pages/maintenance/index') return; // 已在维护页
			if (this._maintNavigating) return; // 已有跳转进行中

			this._maintNavigating = true;
			let attempts = 0;

			const go = () => {
				attempts++;
				let task = null;
				try {
					task = uni.reLaunch({
						url: '/pages/maintenance/index',
						success: () => {
							this._maintNavigating = false;
						},
						fail: () => {
							// 导航被新导航取消（如首屏路由切换）→ 稍后重试，最多 3 次
							if (attempts < 3) {
								setTimeout(go, 300);
							} else {
								this._maintNavigating = false;
							}
						}
					});
				} catch (e) {
					this._maintNavigating = false;
					return;
				}
				// H5 下即便传入了 fail 回调，reLaunch 仍可能返回 Promise；
				// 不接管其拒绝会报 Uncaught (in promise) Navigation cancelled
				if (task && typeof task.catch === 'function') {
					task.catch(() => {});
				}
			};

			go();
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

	/* ==================== 协议中心 & 协议页（全站通用，类名统一 ag- 前缀） ==================== */
	.ag-page {
		min-height: 100vh;
		background: #eff1ff;
	}

	/* 自定义导航栏标题：与其它页面保持一致（白字），避免协议页标题显示为黑色 */
	.ag-page .nav-title {
		height: 100%;
		display: flex;
		align-items: center;
		font-size: 32rpx;
		font-weight: bold;
		position: relative;
		z-index: 1;
		color: #ffffff;
		letter-spacing: 6rpx;
	}

	.ag-bg-wrapper {
		position: fixed;
		width: 100%;
		top: 0;
	}

	.ag-bg {
		width: 100%;
		position: absolute;
		top: 0;
		height: 800rpx;
	}

	.ag-bg-mask {
		background-image: linear-gradient(rgba(0, 0, 0, .8), transparent);
		height: 600rpx;
		position: absolute;
		width: 100%;
		top: 0;
	}

	.ag-content {
		position: relative;
		z-index: 1;
		background: #eff1ff;
		top: 30rpx;
		min-height: calc(100vh - 200rpx);
		border-top-left-radius: 60rpx;
		border-top-right-radius: 60rpx;
		box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, .2);
		padding: 30rpx;
		padding-bottom: 40rpx;
	}

	/* ==================== 意见反馈页（全站通用，类名统一 fb- 前缀，头部结构同协议中心） ==================== */
	.fb-page {
		min-height: 100vh;
		background: #eff1ff;
	}

	/* 自定义导航栏标题：白字，与协议页保持一致 */
	.fb-page .nav-title {
		height: 100%;
		display: flex;
		align-items: center;
		font-size: 32rpx;
		font-weight: bold;
		position: relative;
		z-index: 1;
		color: #ffffff;
		letter-spacing: 6rpx;
	}

	.fb-bg-wrapper {
		position: fixed;
		width: 100%;
		top: 0;
	}

	.fb-bg {
		width: 100%;
		position: absolute;
		top: 0;
		height: 800rpx;
	}

	.fb-bg-mask {
		background-image: linear-gradient(rgba(0, 0, 0, .8), transparent);
		height: 600rpx;
		position: absolute;
		width: 100%;
		top: 0;
	}

	.fb-content {
		position: relative;
		z-index: 1;
		background: #eff1ff;
		top: 30rpx;
		min-height: calc(100vh - 200rpx);
		border-top-left-radius: 60rpx;
		border-top-right-radius: 60rpx;
		box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, .2);
		padding: 30rpx;
		padding-bottom: 40rpx;
	}

	/* 提交表单 */
	.fb-card {
		background: #ffffff;
		border-radius: 20rpx;
		padding: 28rpx 28rpx 30rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 6rpx 18rpx rgba(50, 44, 84, .06);
	}

	.fb-card-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 12rpx;
	}

	.fb-card-title {
		font-size: 30rpx;
		font-weight: bold;
		color: #333333;
	}

	.fb-count {
		font-size: 24rpx;
		color: #948eb3;
	}

	.fb-card-desc {
		font-size: 24rpx;
		color: #8a94a6;
		line-height: 1.7;
		margin-bottom: 24rpx;
	}

	.fb-field {
		margin-bottom: 24rpx;
	}

	.fb-label {
		font-size: 26rpx;
		color: #48416f;
		margin-bottom: 12rpx;
	}

	.fb-required {
		color: #e74c3c;
	}

	.fb-input {
		width: 100%;
		height: 80rpx;
		padding: 0 22rpx;
		box-sizing: border-box;
		font-size: 28rpx;
		color: #333333;
		background: #f7f8fc;
		border-radius: 14rpx;
	}

	.fb-textarea {
		width: 100%;
		height: 240rpx;
		padding: 20rpx 22rpx;
		box-sizing: border-box;
		font-size: 28rpx;
		color: #333333;
		line-height: 1.7;
		background: #f7f8fc;
		border-radius: 14rpx;
	}

	.fb-ph {
		color: #b0b6c7;
	}

	.fb-tip {
		font-size: 22rpx;
		color: #948eb3;
		line-height: 1.7;
		margin-bottom: 24rpx;
	}

	.fb-btn {
		/* 用 flex 居中：原生小程序 button 依赖 line-height 时文字容易偏下 */
		display: flex;
		align-items: center;
		justify-content: center;
		width: 100%;
		height: 84rpx;
		padding: 0;
		margin: 0;
		line-height: 1.2;
		box-sizing: border-box;
		text-align: center;
		border-radius: 16rpx;
		font-size: 30rpx;
		font-weight: bold;
		color: #ffffff;
		background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	}

	.fb-btn.primary {
		border: none;
	}

	.fb-btn.primary::after {
		border: none;
	}

	.fb-btn.primary:active {
		opacity: .88;
	}

	/* 反馈记录 */
	.fb-item {
		background: #fcfdff;
		border: 1rpx solid #eef0f5;
		border-radius: 16rpx;
		padding: 22rpx;
		margin-top: 20rpx;
	}

	.fb-item-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 12rpx;
	}

	.fb-item-title {
		flex: 1;
		min-width: 0;
		font-size: 28rpx;
		font-weight: 600;
		color: #334155;
		margin-right: 16rpx;
		word-break: break-all;
	}

	/* 列表只展示摘要，完整内容点击卡片弹窗查看 */
	.fb-item-body {
		font-size: 27rpx;
		color: #475569;
		line-height: 1.8;
		word-break: break-word;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}

	.fb-item-more {
		text-align: right;
		font-size: 22rpx;
		color: #13c2c2;
		margin-top: 10rpx;
	}

	.fb-item-remark {
		display: flex;
		margin-top: 16rpx;
		padding: 14rpx 18rpx;
		background: rgba(19, 194, 194, .08);
		border-left: 6rpx solid #13c2c2;
		border-radius: 0 12rpx 12rpx 0;
	}

	.fb-item-remark-label {
		flex-shrink: 0;
		font-size: 24rpx;
		color: #0f7490;
		font-weight: 600;
		margin-right: 12rpx;
	}

	.fb-item-remark-text {
		flex: 1;
		min-width: 0;
		font-size: 24rpx;
		color: #0f7490;
		line-height: 1.7;
		word-break: break-word;
	}

	.fb-item-foot {
		display: flex;
		flex-wrap: wrap;
		margin-top: 14rpx;
		font-size: 22rpx;
		color: #94a3b8;
	}

	.fb-item-foot text {
		margin-right: 24rpx;
	}

	/* 处理状态徽章：与后台「用户反馈」状态配色一致 */
	.fb-badge {
		flex-shrink: 0;
		font-size: 22rpx;
		padding: 6rpx 16rpx;
		border-radius: 999rpx;
	}

	.fb-badge-pending {
		color: #f39c12;
		background: rgba(243, 156, 18, .12);
	}

	.fb-badge-doing {
		color: #1890ff;
		background: rgba(24, 144, 255, .12);
	}

	.fb-badge-done {
		color: #27ae60;
		background: rgba(39, 174, 96, .12);
	}

	.fb-badge-reject {
		color: #e74c3c;
		background: rgba(231, 76, 60, .12);
	}

	.fb-badge-ignore {
		color: #8c8c8c;
		background: rgba(140, 140, 140, .12);
	}

	/* 空态 / 加载更多 */
	.fb-empty {
		padding: 40rpx 0 24rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.fb-empty-icon {
		width: 140rpx;
		margin-bottom: 18rpx;
		opacity: .9;
	}

	.fb-empty-title {
		font-size: 28rpx;
		color: #6b6786;
		margin-bottom: 10rpx;
	}

	.fb-empty-desc {
		font-size: 24rpx;
		color: #948eb3;
		line-height: 1.7;
		text-align: center;
	}

	.fb-loading,
	.fb-more,
	.fb-no-more {
		text-align: center;
		font-size: 24rpx;
		color: #948eb3;
		padding: 26rpx 0 6rpx;
	}

	.fb-more {
		color: #13c2c2;
	}

	/* ===== 反馈详情弹窗 ===== */
	.fb-modal-mask {
		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		bottom: 0;
		background: rgba(0, 0, 0, .55);
		/* 低于联系客服弹窗（9999），保证客服弹窗盖在反馈详情之上 */
		z-index: 9990;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 60rpx 48rpx;
	}

	.fb-modal {
		width: 100%;
		max-width: 640rpx;
		background: #ffffff;
		border-radius: 24rpx;
		overflow: hidden;
		display: flex;
		flex-direction: column;
	}

	.fb-modal-head {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 32rpx 80rpx 24rpx;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.fb-modal-title {
		font-size: 32rpx;
		font-weight: bold;
		color: #333333;
		letter-spacing: 2rpx;
	}

	.fb-modal-close {
		position: absolute;
		right: 24rpx;
		top: 24rpx;
		width: 52rpx;
		height: 52rpx;
		line-height: 52rpx;
		text-align: center;
		font-size: 40rpx;
		color: #999999;
		background: #f4f5fb;
		border-radius: 50%;
	}

	.fb-modal-body {
		padding: 24rpx 32rpx 8rpx;
		max-height: 60vh;
		box-sizing: border-box;
	}

	.fb-modal-top {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 20rpx;
	}

	.fb-modal-subject {
		flex: 1;
		min-width: 0;
		font-size: 30rpx;
		font-weight: 600;
		color: #334155;
		margin-right: 16rpx;
		word-break: break-all;
	}

	.fb-modal-block {
		margin-bottom: 22rpx;
	}

	.fb-modal-label {
		font-size: 24rpx;
		color: #948eb3;
		margin-bottom: 10rpx;
	}

	.fb-modal-content {
		font-size: 28rpx;
		color: #475569;
		line-height: 1.8;
		word-break: break-word;
		white-space: pre-wrap;
	}

	.fb-modal-remark {
		font-size: 26rpx;
		color: #0f7490;
		line-height: 1.8;
		padding: 16rpx 20rpx;
		background: rgba(19, 194, 194, .08);
		border-left: 6rpx solid #13c2c2;
		border-radius: 0 12rpx 12rpx 0;
		word-break: break-word;
		white-space: pre-wrap;
	}

	.fb-modal-row {
		display: flex;
		justify-content: space-between;
		padding: 8rpx 0;
		font-size: 24rpx;
	}

	.fb-modal-row .fb-modal-label {
		margin-bottom: 0;
	}

	.fb-modal-value {
		flex: 1;
		min-width: 0;
		color: #475569;
		text-align: right;
		margin-left: 20rpx;
	}

	.fb-modal-foot {
		display: flex;
		align-items: center;
		padding: 20rpx 32rpx 32rpx;
	}

	.fb-modal-btn {
		flex: 1;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		border-radius: 14rpx;
		font-size: 28rpx;
		color: #ffffff;
		background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	}

	/* 关闭：左侧灰底次要按钮 */
	.fb-modal-btn.plain {
		flex: 0 0 200rpx;
		margin-right: 20rpx;
		color: #6b6786;
		background: #f4f5fb;
	}

	/* 协议中心：顶部说明 */
	.ag-intro {
		background: #ffffff;
		border-radius: 20rpx;
		padding: 30rpx;
		margin-bottom: 24rpx;
		box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, .05);
	}

	.ag-intro-title {
		font-size: 32rpx;
		font-weight: bold;
		color: #48416f;
		margin-bottom: 12rpx;
	}

	.ag-intro-desc {
		font-size: 25rpx;
		color: #948eb3;
		line-height: 1.8;
	}

	/* 协议中心：列表 */
	.ag-list-card {
		background: #ffffff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, .05);
	}

	.ag-item {
		display: flex;
		align-items: center;
		padding: 32rpx 30rpx;
		border-bottom: 1rpx solid #f2f2f7;
	}

	.ag-item:last-child {
		border-bottom: none;
	}

	.ag-item:active {
		background: #fafaff;
	}

	.ag-item-icon {
		width: 56rpx;
		height: 56rpx;
		margin-right: 22rpx;
		flex-shrink: 0;
	}

	.ag-item-main {
		flex: 1;
		min-width: 0;
	}

	.ag-item-title {
		font-size: 30rpx;
		color: #48416f;
		font-weight: 500;
	}

	.ag-item-desc {
		font-size: 22rpx;
		color: #948eb3;
		margin-top: 8rpx;
		line-height: 1.6;
	}

	.ag-item-arrow {
		width: 28rpx;
		height: 28rpx;
		opacity: .5;
		margin-left: 16rpx;
		flex-shrink: 0;
	}

	/* 协议中心：底部提示 */
	.ag-tips {
		margin-top: 26rpx;
		padding: 0 10rpx;
		font-size: 22rpx;
		color: #948eb3;
		line-height: 1.8;
		text-align: center;
	}

	/* 协议详情：卡片 */
	.ag-card {
		background: #ffffff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, .05);
	}

	.ag-header {
		display: flex;
		align-items: center;
		padding: 40rpx 30rpx;
		border-bottom: 1rpx solid #f0f0f0;
	}

	.ag-logo {
		width: 60rpx;
		height: 60rpx;
		margin-right: 20rpx;
	}

	.ag-title {
		font-size: 32rpx;
		font-weight: bold;
		color: #48416f;
	}

	.ag-body {
		padding: 30rpx;
	}

	.ag-update {
		font-size: 22rpx;
		color: #948eb3;
		text-align: right;
		margin-bottom: 22rpx;
	}

	.ag-rich {
		display: block;
		font-size: 26rpx;
		color: #48416f;
		line-height: 1.8;
		word-break: break-word;
	}

	/* 协议正文图片：原生 image mode="widthFix" 渲染，宽度自适应、高度自动，各端稳定显示 */
	.ag-rich-img {
		display: block;
		width: 100%;
		margin: 20rpx 0;
		border-radius: 12rpx;
	}

	/* 协议页底部操作：关于我们 / 联系客服（与隐私协议页样式一致） */
	.ag-actions {
		display: flex;
		gap: 20rpx;
		margin-top: 30rpx;
	}

	.ag-action-btn {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 12rpx;
		height: 96rpx;
		padding: 0;
		margin: 0;
		background: #ffffff;
		border-radius: 16rpx;
		box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, .05);
		font-size: 28rpx;
		font-weight: 500;
		color: #48416f;
		line-height: normal;
	}

	.ag-action-btn::after {
		border: none;
	}

	.ag-action-icon {
		width: 36rpx;
		height: 36rpx;
	}

	.ag-action-text {
		font-size: 28rpx;
		color: #48416f;
	}

	.ag-loading {
		padding: 80rpx 0;
		text-align: center;
		font-size: 26rpx;
		color: #948eb3;
	}

	/* 协议未发布：兜底空状态 */
	.ag-empty {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 70rpx 30rpx 50rpx;
	}

	.ag-empty-icon {
		width: 140rpx;
		height: 140rpx;
		margin-bottom: 26rpx;
	}

	.ag-empty-title {
		font-size: 30rpx;
		font-weight: bold;
		color: #48416f;
		margin-bottom: 14rpx;
	}

	.ag-empty-desc {
		font-size: 24rpx;
		color: #948eb3;
		line-height: 1.8;
		text-align: center;
	}

	.ag-footer {
		padding: 30rpx;
		text-align: center;
		border-top: 1rpx solid #f0f0f0;
		font-size: 22rpx;
		color: #948eb3;
	}
</style>
