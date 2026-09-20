<template>
	<view v-if="show" class="update-mask" @click="onMaskClick">
		<view class="update-dialog" @click.stop>
			<!-- 头部图片：透明底，浮于遮罩之上（弹窗顶部不画白底，透出背后变暗的页面） -->
			<image class="update-header" src="/static/assets/up_up.png" mode="widthFix"></image>

			<view class="update-body">
				<view class="update-content">
					<view class="update-version-row">
						<text class="update-version">发现新版本 v{{ version }}</text>
						<text v-if="isForce" class="update-force-tag">强制更新</text>
					</view>
					<view class="update-sub">建议立即更新，以获得更稳定流畅的体验</view>
					<scroll-view scroll-y class="update-log">
						<view class="update-log-item" v-for="(line, idx) in logLines" :key="idx">
							<view class="update-log-dot"></view>
							<text class="update-log-text">{{ line }}</text>
						</view>
					</scroll-view>
				</view>

				<view class="update-actions">
					<view v-if="!isForce" class="update-btn update-btn-ghost" @click="onClose">稍后再说</view>
					<view class="update-btn update-btn-primary" @click="onUpdate">立即更新</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 版本更新弹窗
 * 头部使用 /static/assets/up_up.png，展示版本号与更新日志，支持强制更新（隐藏「稍后再说」且不可点遮罩关闭）。
 */
export default {
	name: 'update-modal',
	props: {
		show: { type: Boolean, default: false },
		version: { type: String, default: '' },
		changelog: { type: String, default: '' },
		force: { type: [Boolean, Number], default: false },
		downloadUrl: { type: String, default: '' }
	},
	computed: {
		// 兼容布尔或 0/1 传值
		isForce() {
			return this.force === true || parseInt(this.force, 10) === 1;
		},
		// 更新日志按行拆分为条目，去掉行首的符号标记
		logLines() {
			const text = this.changelog || '修复了一些问题，体验更流畅';
			return String(text)
				.split('\n')
				.map(line => line.replace(/^\s*[-•*·]\s*/, '').replace(/^\s*\d+[.、)]\s*/, '').trim())
				.filter(line => line.length > 0);
		}
	},
	methods: {
		onClose() {
			if (this.isForce) return;
			this.$emit('close');
		},
		onMaskClick() {
			this.onClose();
		},
		onUpdate() {
			this.$emit('update');
			const url = this.downloadUrl;
			if (!url) {
				uni.showToast({ title: '暂无下载地址', icon: 'none' });
				return;
			}
			// #ifdef APP-PLUS
			plus.runtime.openURL(url);
			// #endif
			// #ifndef APP-PLUS
			try {
				window.open(url, '_blank');
			} catch (e) {}
			// #endif
		}
	}
};
</script>

<style scoped>
.update-mask {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.55);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40rpx 60rpx;
	animation: updateMaskFade 0.2s ease;
}

@keyframes updateMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.update-dialog {
	width: 100%;
	max-width: 620rpx;
	background: transparent;
	animation: updateDialogPop 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes updateDialogPop {
	from { transform: scale(0.9); opacity: 0; }
	to { transform: scale(1); opacity: 1; }
}

.update-header {
	width: 100%;
	display: block;
}

/* 正文白卡片：头图浮于遮罩之上后，由该卡片承载内容 */
.update-body {
	position: relative;
	margin-top: -24rpx;
	background: #ffffff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 24rpx 60rpx rgba(50, 44, 84, 0.22);
}

.update-content {
	padding: 32rpx 36rpx 8rpx;
}

.update-version-row {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	margin-bottom: 12rpx;
}

.update-version {
	font-size: 34rpx;
	font-weight: bold;
	color: #48416f;
}

.update-force-tag {
	margin-left: 16rpx;
	padding: 4rpx 16rpx;
	font-size: 20rpx;
	color: #ffffff;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	border-radius: 20rpx;
}

.update-sub {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 22rpx;
}

.update-log {
	max-height: 420rpx;
}

.update-log-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 14rpx;
}

.update-log-item:last-child {
	margin-bottom: 0;
}

.update-log-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #13c2c2;
	margin-top: 14rpx;
	margin-right: 16rpx;
	flex-shrink: 0;
}

.update-log-text {
	flex: 1;
	font-size: 26rpx;
	color: #767676;
	line-height: 1.7;
	word-break: break-word;
}

.update-actions {
	display: flex;
	gap: 20rpx;
	padding: 28rpx 36rpx 36rpx;
}

.update-btn {
	flex: 1;
	height: 84rpx;
	line-height: 84rpx;
	text-align: center;
	font-size: 30rpx;
	border-radius: 999rpx;
}

.update-btn-ghost {
	color: #948eb3;
	background: #f4f5fb;
}

.update-btn-primary {
	color: #ffffff;
	font-weight: 500;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.3);
}
</style>
