<template>
	<view v-if="visible" class="announcement" :class="typeClass">
		<image class="announcement-icon" :src="icon" mode="aspectFit"></image>
		<view class="announcement-content" @click="onTap">{{ content }}</view>
		<view v-if="closable" class="announcement-close" @click="onClose">
			<text>×</text>
		</view>
		<!-- 联系客服弹窗：复用项目统一的 contact-modal（fixed 定位，不占公告布局） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
/**
 * 公告提示组件
 * 支持多种类型的提示信息展示，可关闭，点击可查看详情
 */
import api from '../../utils/api.js';

const TYPE_CLASSES = {
	info: 'announcement-info',
	success: 'announcement-success',
	warning: 'announcement-warning',
	danger: 'announcement-danger'
};

const ICONS = {
	info: '/static/assets/wxmp-info.svg',
	success: '/static/assets/wxmp-check.svg',
	warning: '/static/assets/wxmp-warning.svg',
	danger: '/static/assets/wxmp-error.svg'
};

export default {
	name: 'announcement',
	props: {
		content: {
			type: String,
			default: ''
		},
		type: {
			type: String,
			default: 'info'
		},
		closable: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			visible: true,
			// 联系客服弹窗（复用统一的 contact-modal）
			contactShow: false,
			contactLines: []
		};
	},
	computed: {
		typeClass() {
			return TYPE_CLASSES[this.type] || TYPE_CLASSES['info'];
		},
		icon() {
			return ICONS[this.type] || ICONS['info'];
		}
	},
	methods: {
		onClose() {
			this.visible = false;
			this.$emit('close');
		},
		onTap() {
			const content = this.content;
			if (!content) return;
			uni.showModal({
				title: '公告',
				content: content,
				showCancel: true,
				cancelText: '联系客服',
				confirmText: '我知道了',
				success: (res) => {
					// 点击「联系客服」：展示后台「官网配置」维护的联系方式
					if (res.cancel) this.openContact();
				}
			});
		},

		// 联系客服：拉取后台联系方式后弹出 contact-modal（与协议页 / 维护页 / 我的页一致）
		async openContact() {
			uni.showLoading({ title: '加载中...' });
			let info = null;
			try {
				const res = await api.getContactInfo();
				info = (res && res.data) || null;
			} catch (e) {
				info = null;
			}
			uni.hideLoading();

			const lines = [];
			if (info) {
				if (info.phone) lines.push('联系电话：' + info.phone);
				if (info.email) lines.push('电子邮箱：' + info.email);
				if (info.qq) lines.push('QQ：' + info.qq);
				if (info.worktime) lines.push('工作时间：' + info.worktime);
				if (info.address) lines.push('联系地址：' + info.address);
				if (info.desc) lines.push('说明：' + info.desc);
			}
			if (!lines.length) {
				lines.push('暂未配置联系方式，请在「我的」页面通过「联系客服」功能与我们取得联系。');
			}

			this.contactLines = lines;
			this.contactShow = true;
		},
		show() {
			this.visible = true;
		},
		hide() {
			this.visible = false;
		}
	}
};
</script>

<style scoped>
.announcement {
	display: flex;
	align-items: flex-start;
	padding: 12rpx 24rpx;
	margin: 0 0 16rpx;
	border-radius: 20rpx;
	font-size: 26rpx;
	line-height: 1.5;
}

.announcement-icon {
	width: 36rpx;
	height: 36rpx;
	flex-shrink: 0;
	margin-right: 16rpx;
	margin-top: 4rpx;
}

.announcement-content {
	flex: 1;
	word-break: break-all;
}

.announcement-close {
	flex-shrink: 0;
	width: 40rpx;
	height: 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-left: 16rpx;
	opacity: 0.6;
}

.announcement-close text {
	font-size: 36rpx;
	line-height: 1;
}

.announcement-info {
	background-color: rgba(59, 130, 246, 0.1);
	color: #3b82f6;
	border: 1rpx solid rgba(59, 130, 246, 0.2);
}

.announcement-success {
	background-color: rgba(34, 197, 94, 0.1);
	color: #16a34a;
	border: 1rpx solid rgba(34, 197, 94, 0.2);
}

.announcement-warning {
	background-color: rgba(245, 158, 11, 0.1);
	color: #d97706;
	border: 1rpx solid rgba(245, 158, 11, 0.2);
}

.announcement-danger {
	background-color: rgba(239, 68, 68, 0.1);
	color: #dc2626;
	border: 1rpx solid rgba(239, 68, 68, 0.2);
}
</style>
