<template>
	<view class="home-skeleton" :class="{ 'home-skeleton--static': !animated }">
		<!-- 外层只做入场淡入，内层只做呼吸，均单条动画（小程序对多动画简写支持不稳定） -->
		<view class="home-skeleton__inner">
			<!-- 轮播图 -->
			<view class="sk-banner sk-shine"></view>

			<!-- 快捷入口（3 个） -->
			<view class="sk-menus">
				<view class="sk-menu" v-for="i in 3" :key="'menu' + i">
					<view class="sk-circle sk-shine"></view>
					<view class="sk-line sk-line--menu sk-shine"></view>
				</view>
			</view>

			<!-- 功能入口（2 个） -->
			<view class="sk-menus sk-menus--sub">
				<view class="sk-menu sk-menu--wide" v-for="i in 2" :key="'sub' + i">
					<view class="sk-circle sk-circle--sm sk-shine"></view>
					<view class="sk-line sk-line--wide sk-shine"></view>
				</view>
			</view>

			<!-- 今日速览 -->
			<view class="sk-title sk-shine"></view>
			<view class="sk-cards">
				<view class="sk-card" v-for="i in 2" :key="'card' + i">
					<view class="sk-line sk-line--xs sk-shine"></view>
					<view class="sk-line sk-line--lg sk-shine"></view>
					<view class="sk-line sk-line--md sk-shine"></view>
				</view>
			</view>

			<!-- 通知公告 -->
			<view class="sk-title sk-shine"></view>
			<view class="sk-notice" v-for="i in 2" :key="'notice' + i">
				<view class="sk-notice-cover sk-shine"></view>
				<view class="sk-notice-info">
					<view class="sk-line sk-line--lg sk-shine"></view>
					<view class="sk-line sk-line--sm sk-shine"></view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 首页骨架屏（仅首页使用）
 * 结构与首页内容区一一对应：轮播 → 快捷入口 → 功能入口 → 今日速览 → 通知公告
 * 采用浅紫灰底 + 白色高光扫过，贴合项目淡紫风格
 */
export default {
	name: 'home-skeleton',
	props: {
		// 低端机可传 false 关闭呼吸/扫光动画（纯静态骨架）
		animated: {
			type: Boolean,
			default: true
		}
	}
};
</script>

<style scoped>
/* 外层：入场淡入（单条动画，避免多动画简写在部分端失效） */
.home-skeleton {
	padding-top: 4rpx;
	animation: sk-fade-in 0.22s ease-out both;
}

/* 内层：淡入结束（0.22s）后再开始整体呼吸 */
.home-skeleton__inner {
	animation: sk-pulse 1.6s ease-in-out 0.22s infinite;
}

/* 静态模式：关闭全部动画 */
.home-skeleton--static,
.home-skeleton--static .home-skeleton__inner,
.home-skeleton--static .sk-banner::after {
	animation: none;
}

.home-skeleton--static .sk-banner::after {
	opacity: 0;
}

/* 骨架块底色 */
.sk-shine {
	background-color: #e9ecf8;
}

/* 仅轮播块做高光扫过，进一步控制同时运行的动画数量 */
.sk-banner {
	position: relative;
	overflow: hidden;
	width: 100%;
	height: 280rpx;
	border-radius: 20rpx;
	margin-bottom: 30rpx;
}

.sk-banner::after {
	content: '';
	position: absolute;
	top: 0;
	bottom: 0;
	left: 0;
	width: 60%;
	background: linear-gradient(
		90deg,
		rgba(255, 255, 255, 0) 0%,
		rgba(255, 255, 255, 0.8) 50%,
		rgba(255, 255, 255, 0) 100%
	);
	transform: translate3d(-120%, 0, 0);
	animation: sk-shimmer 1.8s linear infinite;
}

/* 快捷入口 / 功能入口 */
.sk-menus {
	display: flex;
	margin-bottom: 30rpx;
}

.sk-menus--sub {
	margin-top: -16rpx;
}

.sk-menu {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	background: #fff;
	border-radius: 24rpx;
	padding: 20rpx 0;
	margin-right: 16rpx;
}

.sk-menu:last-child {
	margin-right: 0;
}

.sk-menu--wide {
	flex-direction: row;
	justify-content: center;
	padding: 22rpx 0;
}

.sk-circle {
	width: 76rpx;
	height: 76rpx;
	border-radius: 50%;
	margin-bottom: 12rpx;
}

.sk-circle--sm {
	width: 48rpx;
	height: 48rpx;
	margin-bottom: 0;
	margin-right: 14rpx;
}

.sk-line {
	height: 20rpx;
	border-radius: 10rpx;
}

.sk-line--menu {
	width: 90rpx;
}

.sk-line--wide {
	width: 160rpx;
}

.sk-line--xs {
	width: 70rpx;
	height: 16rpx;
}

.sk-line--sm {
	width: 70%;
	height: 18rpx;
	margin-top: 14rpx;
}

.sk-line--md {
	width: 55%;
	height: 18rpx;
	margin-top: 14rpx;
}

.sk-line--lg {
	width: 88%;
	height: 26rpx;
	margin-top: 16rpx;
}

/* 区块标题 */
.sk-title {
	width: 150rpx;
	height: 28rpx;
	border-radius: 8rpx;
	margin: 6rpx 0 22rpx;
}

/* 速览卡片 */
.sk-cards {
	display: flex;
	margin-bottom: 34rpx;
}

.sk-card {
	flex: 1;
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx 24rpx;
	margin-right: 16rpx;
}

.sk-card:last-child {
	margin-right: 0;
}

.sk-card .sk-line:first-child {
	margin-top: 0;
}

/* 通知公告 */
.sk-notice {
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 20rpx;
	padding: 22rpx;
	margin-bottom: 18rpx;
}

.sk-notice-cover {
	width: 120rpx;
	height: 90rpx;
	border-radius: 12rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.sk-notice-info {
	flex: 1;
}

.sk-notice-info .sk-line:first-child {
	margin-top: 0;
}

@keyframes sk-shimmer {
	to {
		transform: translate3d(220%, 0, 0);
	}
}

@keyframes sk-fade-in {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@keyframes sk-pulse {
	0%,
	100% {
		opacity: 1;
	}
	50% {
		opacity: 0.62;
	}
}
</style>
