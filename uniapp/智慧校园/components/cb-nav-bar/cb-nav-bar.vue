<template>
	<view class="nav-bar">
		<view :style="{ height: statusBarHeight + 'px' }"></view>
		<view v-if="topGapHeight > 0" class="nav-bar-gap" :style="{ height: topGapHeight + 'rpx' }"></view>
		<view
			class="nav-bar-content"
			:class="{ 'no-back': !showBack }"
			:style="{ height: menuBarHeight + 'px' }"
		>
			<view v-if="showBack" class="nav-bar-back" @click="handleBack">
				<view class="nav-bar-back-icon" :style="{ borderColor: backColor }"></view>
			</view>
			<!-- 标题区域：占满剩余空间并垂直居中 -->
			<view class="nav-bar-title">
				<slot></slot>
			</view>
			<!-- 右侧区域：靠右显示 -->
			<view class="nav-bar-extra">
				<slot name="right"></slot>
			</view>
		</view>

		<!-- 全局「联系客服」弹窗宿主：挂在每个页面都有的导航里，
		     让 utils/feature.js（功能暂未开放弹窗）等处能直接打开它，免逐页挂载 -->
		<contact-modal />
	</view>
</template>

<script>
/**
 * 自定义导航栏组件
 * 适配不同设备的状态栏高度，支持自定义返回按钮
 */

/**
 * 状态栏高度属于设备常量，运行期间不会变化。
 * 每个页面 mounted 都调用一次原生接口在低端机上开销明显，这里做模块级缓存。
 */
let cachedStatusBarHeight = null;

export default {
	name: 'cb-nav-bar',
	props: {
		showBack: {
			type: Boolean,
			default: false
		},
		backColor: {
			type: String,
			default: '#fff'
		},
		// 顶部额外留白（rpx）。未显式设置时，带返回按钮的二级页面自动加一点边距
		topGap: {
			type: Number,
			default: null
		},
		// 紧凑模式：减小导航栏高度，适用于只有标题的一级页面（如「课程」「我的」）
		compact: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			statusBarHeight: cachedStatusBarHeight || 0,
			menuBarHeight: 44
		};
	},
	computed: {
		topGapHeight() {
			if (this.topGap !== null && this.topGap > 0) return this.topGap;
			// H5 下没有原生状态栏，需要给一点顶部留白，避免导航栏贴着视口顶部；
			// App / 小程序已有状态栏占位，不再额外留白（compact 模式留白更小）
			// #ifdef H5
			return this.compact ? 12 : 20;
			// #endif
			// #ifndef H5
			return 0;
			// #endif
		}
	},
	mounted() {
		this.calcBarHeight();
	},
	methods: {
		calcBarHeight() {
			if (cachedStatusBarHeight === null) {
				const windowInfo = uni.getWindowInfo ? uni.getWindowInfo() : uni.getSystemInfoSync();
				cachedStatusBarHeight = windowInfo.statusBarHeight || 0;
			}
			this.statusBarHeight = cachedStatusBarHeight;

			// #ifdef MP-WEIXIN
			const menuBounding = uni.getMenuButtonBoundingClientRect();
			if (menuBounding) {
				this.menuBarHeight = menuBounding.height + (menuBounding.top - this.statusBarHeight) * 2;
			} else {
				this.menuBarHeight = this.compact ? 36 : 44;
			}
			// #endif
			// #ifndef MP-WEIXIN
			this.menuBarHeight = this.compact ? 36 : 44;
			// #endif
		},
		handleBack() {
			const pages = getCurrentPages();
			if (pages.length > 1) {
				uni.navigateBack();
			} else {
				uni.switchTab({
					url: '/pages/white-home/index'
				});
			}
		}
	}
};
</script>

<style scoped>
.nav-bar {
	position: relative;
	z-index: 999;
}

.nav-bar-content {
	display: flex;
	align-items: center;
	padding: 0 20rpx;
}

.nav-bar-title {
	flex: 1;
	min-width: 0;
	display: flex;
	align-items: center;
}

.nav-bar-extra {
	flex-shrink: 0;
	display: flex;
	align-items: center;
}

.nav-bar-back {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 60rpx;
	height: 60rpx;
	margin-right: 10rpx;
}

.nav-bar-back-icon {
	width: 20rpx;
	height: 20rpx;
	border-left: 4rpx solid #333;
	border-bottom: 4rpx solid #333;
	transform: rotate(45deg);
	margin-left: 6rpx;
}

.nav-bar-content.no-back {
	padding-left: 70rpx;
}

.nav-bar-gap {
	width: 100%;
}
</style>
