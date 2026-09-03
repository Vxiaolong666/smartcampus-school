<script>
	import feature from './utils/feature.js';

	export default {
		onLaunch: function() {
			console.log('App Launch')
			// 预取功能开关并缓存，避免进入业务页面时因等待网络请求而卡顿
			feature.getFeatureSettings();
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
		}
	}
</script>

<style>
	/*每个页面公共css */
</style>
