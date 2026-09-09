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
		},
		onShow: function() {
			console.log('App Show')
			// #ifdef APP-PLUS
			// App 从后台回到前台时再设置一次，防止系统状态栏样式被还原
			try {
				plus.navigator.setStatusBarStyle('light');
			} catch (e) {}
			// #endif
		},
		onHide: function() {
			console.log('App Hide')
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
