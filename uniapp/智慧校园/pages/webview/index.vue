<template>
	<view class="container">
		<web-view v-if="url" :src="url" @error="onError"></web-view>
		<view v-else class="empty">
			<text class="empty-text">链接无效</text>
		</view>
	</view>
</template>

<script>
/**
 * 通用网页容器页（应用内「内置浏览器」）
 *
 * 用于在 App / 小程序内直接打开站外链接（如首页轮播图配置的链接）；
 * H5 端不走本页（直接 window.open 新窗口打开）。
 *
 * 传参：?url=<encodeURIComponent(链接)>，可选 ?title=<encodeURIComponent(标题)>
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
			uni.showToast({ title: '链接无效', icon: 'none' });
			setTimeout(() => {
				uni.navigateBack();
			}, 800);
			return;
		}
		// 可选标题（未传时用 pages.json 里的默认标题）
		if (options && options.title) {
			uni.setNavigationBarTitle({ title: decodeURIComponent(options.title) });
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
