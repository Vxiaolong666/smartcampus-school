<template>
	<view v-if="show" class="contact-mask" @click="onMaskClick">
		<view class="contact-dialog" @click.stop>
			<view class="contact-header">
				<text class="contact-title">联系客服</text>
				<text class="contact-subtitle">我们会在工作时间内尽快回复你</text>
				<view class="contact-close" @click="onClose">×</view>
			</view>
			<scroll-view scroll-y class="contact-body">
				<view v-for="(item, idx) in items" :key="idx" class="contact-item-wrap">
					<text v-if="!item.label" class="contact-item-hint">{{ item.value }}</text>
					<view v-else class="contact-item">
						<text class="contact-item-label" :class="labelClass(item.label)">{{ item.label }}</text>
						<text class="contact-item-value">{{ item.value }}</text>
					</view>
				</view>
			</scroll-view>
			<view class="contact-tip">
				<text class="contact-tip-text">温馨提示：请通过以上官方渠道联系我们，谨防冒充客服的诈骗行为。</text>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 联系客服弹窗（自定义样式，参考版本更新弹窗 update-modal）
 * 展示后台「首页配置」中维护的联系方式：每行按「标签：内容」解析为标签胶囊 + 内容，并附底部提示。
 */
export default {
	name: 'contact-modal',
	props: {
		show: { type: Boolean, default: false },
		lines: { type: Array, default: () => [] }
	},
	computed: {
		// 将 "标签：内容" 的行拆分为 { label, value }，无冒号的行作为纯提示文本
		items() {
			return (this.lines || []).map((line) => {
				const str = String(line == null ? '' : line);
				let idx = str.indexOf('：');
				if (idx === -1) idx = str.indexOf(':');
				if (idx === -1) return { label: '', value: str };
				return { label: str.slice(0, idx), value: str.slice(idx + 1) };
			});
		}
	},
	methods: {
		onClose() {
			this.$emit('close');
		},
		onMaskClick() {
			this.onClose();
		},
		// 根据标签关键字匹配不同配色，让各类联系方式更易区分
		labelClass(label) {
			const l = String(label || '');
			if (/地址/.test(l)) return 'is-address';
			if (/电话|手机/.test(l)) return 'is-phone';
			if (/邮箱|邮件|email/i.test(l)) return 'is-mail';
			if (/qq/i.test(l)) return 'is-qq';
			if (/微信|wechat/i.test(l)) return 'is-wechat';
			if (/时间/.test(l)) return 'is-time';
			if (/说明|备注/.test(l)) return 'is-desc';
			return 'is-default';
		}
	}
};
</script>

<style scoped>
.contact-mask {
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
	animation: contactMaskFade 0.2s ease;
}

@keyframes contactMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.contact-dialog {
	width: 100%;
	max-width: 620rpx;
	background: #ffffff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 24rpx 60rpx rgba(50, 44, 84, 0.22);
	animation: contactDialogPop 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes contactDialogPop {
	from { transform: scale(0.9); opacity: 0; }
	to { transform: scale(1); opacity: 1; }
}

.contact-header {
	position: relative;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 40rpx 36rpx 28rpx;
	background: #ffffff;
	border-bottom: 1rpx solid #f0f0f0;
}

.contact-title {
	max-width: 100%;
	font-size: 36rpx;
	font-weight: bold;
	color: #333333;
	letter-spacing: 2rpx;
	text-align: center;
}

.contact-subtitle {
	margin-top: 12rpx;
	font-size: 24rpx;
	color: #98a2b3;
	letter-spacing: 1rpx;
}

.contact-close {
	position: absolute;
	right: 36rpx;
	top: 36rpx;
	width: 52rpx;
	height: 52rpx;
	line-height: 52rpx;
	text-align: center;
	font-size: 44rpx;
	color: #999999;
	background: #f4f5fb;
	border-radius: 50%;
	box-shadow: 0 4rpx 12rpx rgba(50, 44, 84, 0.12);
}

.contact-body {
	max-height: 560rpx;
	padding: 16rpx 36rpx 8rpx;
	white-space: normal;
	box-sizing: border-box;
}

.contact-item-wrap {
	width: 100%;
}

.contact-item {
	display: flex;
	align-items: flex-start;
	padding: 22rpx 0;
	border-bottom: 1rpx solid #f2f3f7;
	white-space: normal;
}

.contact-item-wrap:last-child .contact-item {
	border-bottom: none;
}

.contact-item-label {
	flex-shrink: 0;
	min-width: 116rpx;
	max-width: 180rpx;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	text-align: center;
	padding: 6rpx 16rpx;
	margin-right: 20rpx;
	margin-top: 2rpx;
	font-size: 24rpx;
	border-radius: 10rpx;
	background: rgba(24, 144, 255, 0.12);
	color: #1890ff;
}

.contact-item-label.is-phone {
	background: rgba(24, 144, 255, 0.12);
	color: #1890ff;
}

.contact-item-label.is-mail {
	background: rgba(114, 46, 209, 0.12);
	color: #722ed1;
}

.contact-item-label.is-qq {
	background: rgba(19, 194, 194, 0.12);
	color: #13c2c2;
}

.contact-item-label.is-wechat {
	background: rgba(82, 196, 26, 0.12);
	color: #52c41a;
}

.contact-item-label.is-time {
	background: rgba(250, 140, 22, 0.12);
	color: #fa8c16;
}

.contact-item-label.is-address {
	background: rgba(235, 47, 150, 0.12);
	color: #eb2f96;
}

.contact-item-label.is-desc {
	background: rgba(140, 140, 140, 0.12);
	color: #8c8c8c;
}

.contact-item-value {
	flex: 1 1 auto;
	width: 0;
	min-width: 0;
	font-size: 29rpx;
	color: #333333;
	line-height: 1.6;
	white-space: normal;
	word-break: break-all;
	word-wrap: break-word;
	overflow-wrap: anywhere;
}

.contact-item-hint {
	display: block;
	padding: 24rpx 8rpx;
	text-align: center;
	font-size: 28rpx;
	color: #999999;
	line-height: 1.7;
	word-break: break-all;
}

.contact-tip {
	margin: 20rpx 36rpx 32rpx;
	padding: 20rpx 24rpx;
	background: #f7f8fc;
	border-radius: 14rpx;
}

.contact-tip-text {
	font-size: 24rpx;
	color: #98a2b3;
	line-height: 1.7;
	word-break: break-all;
	overflow-wrap: anywhere;
}
</style>
