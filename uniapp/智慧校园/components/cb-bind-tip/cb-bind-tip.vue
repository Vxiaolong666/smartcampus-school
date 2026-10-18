<template>
	<view class="cb-bind-tip" :class="{ 'cb-bind-tip--compact': compact }">
		<image class="cb-bind-tip__icon" :src="tipIcon" mode="widthFix"></image>
		<view class="cb-bind-tip__title">{{ tipTitle }}</view>
		<view class="cb-bind-tip__desc">{{ tipDesc }}</view>
		<button v-if="showBtn" class="cb-bind-tip__btn" @click="onBind">{{ btnText }}</button>
	</view>
</template>

<script>
/**
 * 统一的「未绑定宿舍 / 未绑定教务账号」提示遮罩
 * 规范：图标 + 标题 + 说明 + 前往绑定按钮，与原生小程序端保持一致
 */
export default {
	name: 'cb-bind-tip',
	props: {
		// academic=教务账号，dorm=宿舍
		type: {
			type: String,
			default: 'academic'
		},
		// 自定义标题/说明，不传则使用统一默认文案
		title: {
			type: String,
			default: ''
		},
		desc: {
			type: String,
			default: ''
		},
		btnText: {
			type: String,
			default: '前往绑定'
		},
		// 是否展示前往绑定按钮
		showBtn: {
			type: Boolean,
			default: true
		},
		compact: {
			type: Boolean,
			default: false
		}
	},

	computed: {
		isDorm() {
			return this.type === 'dorm';
		},
		tipIcon() {
			return this.isDorm ? '/static/assets/wxmp-home2-gray.svg' : '/static/assets/wxmp-book-gray.svg';
		},
		tipTitle() {
			if (this.title) return this.title;
			return this.isDorm ? '请先绑定宿舍' : '请先绑定教务账号';
		},
		tipDesc() {
			if (this.desc) return this.desc;
			return this.isDorm ? '绑定宿舍后可查看电费余额与用电统计' : '绑定后可查看课程、成绩与学籍信息';
		}
	},

	methods: {
		onBind() {
			const url = this.isDorm ? '/pages/dormitory-bind/index' : '/pages/academic-bind/index';
			uni.navigateTo({ url });
			this.$emit('bind');
		}
	}
};
</script>

<style scoped>
.cb-bind-tip {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 80rpx 0;
	animation: cbt-fade-in 0.2s ease-out both;
}

.cb-bind-tip--compact {
	padding: 40rpx 0;
}

/* 图标→标题→说明→按钮 依次入场，形成轻量的层次感 */
.cb-bind-tip__icon {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 24rpx;
	animation: cbt-pop-in 0.4s cubic-bezier(0.34, 1.4, 0.64, 1) 0.04s both;
}

.cb-bind-tip__title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
	animation: cbt-rise-in 0.32s ease-out 0.1s both;
}

.cb-bind-tip__desc {
	font-size: 28rpx;
	color: #948eb3;
	text-align: center;
	line-height: 1.6;
	animation: cbt-rise-in 0.32s ease-out 0.16s both;
}

.cb-bind-tip__btn {
	margin-top: 32rpx;
	height: 72rpx;
	line-height: 72rpx;
	padding: 0 60rpx;
	background: #13c2c2;
	color: #fff;
	font-size: 28rpx;
	border-radius: 36rpx;
	font-weight: bold;
	animation: cbt-rise-in 0.32s ease-out 0.22s both;
}

.cb-bind-tip__btn::after {
	border: none;
}

@keyframes cbt-fade-in {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

@keyframes cbt-pop-in {
	from {
		opacity: 0;
		transform: scale(0.8) translateY(8rpx);
	}
	to {
		opacity: 1;
		transform: scale(1) translateY(0);
	}
}

@keyframes cbt-rise-in {
	from {
		opacity: 0;
		transform: translateY(12rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>
