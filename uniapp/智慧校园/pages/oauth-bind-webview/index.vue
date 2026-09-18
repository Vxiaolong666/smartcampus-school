<template>
	<view class="container">
		<web-view v-if="url" :src="url" @error="onError"></web-view>
		<view v-else class="empty">
			<text class="empty-text">授权地址无效</text>
		</view>
	</view>
</template>

<script>
/**
 * 第三方授权 WebView 容器页
 * 仅承载聚合登录的授权地址；授权完成后服务端跳转到结果页，
 * 用户返回上一页时由绑定页 onShow 刷新最新绑定状态。
 */
export default {
	data() {
		return {
			url: ''
		};
	},

	onLoad(options) {
		const url = (options && options.url) ? decodeURIComponent(options.url) : '';
		if (!url) {
			uni.showToast({ title: '参数错误', icon: 'none' });
			setTimeout(() => {
				uni.navigateBack();
			}, 800);
			return;
		}
		this.url = url;
	},

	methods: {
		onError() {
			uni.showToast({ title: '页面加载失败', icon: 'none' });
		}
	}
};
</script>

<style>
page {
	background: #ffffff;
}
</style>

<style scoped>
.empty {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 120rpx 0;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}
</style>
