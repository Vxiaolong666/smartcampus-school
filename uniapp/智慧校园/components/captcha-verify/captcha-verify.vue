<template>
	<!-- 人机验证：仅 H5 / App 端启用（小程序端由条件编译整体移除）
	     优先腾讯云滑块验证（TCaptcha，与官网登录同一套），异常时兜底图片验证码 -->
	<view class="cap-root">
		<!-- #ifdef H5 || APP-PLUS -->
		<view class="cap-box" :class="{ 'cap-box-form': variant === 'form' }">
			<!-- 滑块验证 -->
			<block v-if="!useImage">
				<view v-if="!passed" class="cap-btn" @click="startVerify">
					<image class="cap-btn-icon" src="/static/assets/wxmp-lock.svg" mode="widthFix"></image>
					<text class="cap-btn-text">{{ loading ? '验证中…' : '进行滑块验证' }}</text>
				</view>
				<view v-else class="cap-passed">
					<image class="cap-passed-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
					<text class="cap-passed-text">安全验证已通过</text>
				</view>
				<view v-if="failed && !passed" class="cap-switch" @click="useImageCaptcha">滑块异常？改用图片验证码</view>
			</block>

			<!-- 图片验证码兜底 -->
			<block v-else>
				<view class="cap-img-row">
					<input
						class="cap-img-input"
						type="text"
						maxlength="4"
						placeholder="请输入验证码"
						:value="code"
						@input="onCodeInput"
					/>
					<image class="cap-img" :src="imgUrl" mode="widthFix" @click="refreshImage"></image>
				</view>
				<view class="cap-switch" @click="backToSlider">改用滑块验证</view>
			</block>
		</view>
		<!-- #endif -->

		<!-- #ifdef APP-PLUS -->
		<!-- App 端：TCaptcha 依赖浏览器环境，用本地 H5 页承载，结果经 opener 回传 -->
		<web-view v-if="showWV" :src="wvUrl" @message="onWvMessage"></web-view>
		<!-- #endif -->
	</view>
</template>

<script>
/**
 * 人机验证组件（腾讯云验证码 TCaptcha + 图片验证码兜底）
 * ------------------------------------------------------------------
 * - 仅在 H5 / App 端启用（条件编译），小程序端不渲染、不影响登录流程；
 * - H5：动态加载 https://ssl.captcha.qq.com/TCaptcha.js，
 *       new TencentCaptcha(appid, cb, {}) 并 show()（与官网登录写法一致）；
 * - App：<web-view> 打开本地页 hybrid/html/tcaptcha.html（TCaptcha 需要浏览器 DOM），
 *       页面验证完成后通过 plus opener.evalJS 即时回传 ticket / randstr；
 * - 兜底：滑块异常时可切换为图片验证码（server-submit/captcha.php，与网站同一张图）；
 * - 页面在提交前调用 check() 判断是否通过，并把 getPayload() 一并提交给后端。
 */
import api from '../../utils/api.js';

// 与官网登录使用同一个验证码 AppID 与 SDK，保证前后端口径一致
const TC_APPID = '2047155482';
const TC_SDK = 'https://ssl.captcha.qq.com/TCaptcha.js';

export default {
	name: 'captcha-verify',
	props: {
		appid: { type: String, default: TC_APPID },
		// 表单风格：'default' 适配登录/注册等页面；'form' 适配注销等使用 .form-input 的页面
		variant: { type: String, default: 'default' }
	},
	data() {
		return {
			passed: false,     // 滑块是否已通过
			failed: false,     // 滑块是否失败（用于引导切换图片验证码）
			loading: false,
			ticket: '',
			randstr: '',
			useImage: false,   // 是否改用图片验证码兜底
			code: '',
			imgUrl: '',
			showWV: false      // App：是否展示本地验证页
		};
	},
	computed: {
		wvUrl() {
			return '/hybrid/html/tcaptcha.html?appid=' + encodeURIComponent(this.appid);
		}
	},
	mounted() {
		// #ifdef APP-PLUS
		// 本地验证页通过 opener.evalJS 回传结果；重复进页面时会覆盖旧引用
		window.__tcResult = (payload) => { this.handleTcResult(payload); };
		// #endif
	},
	methods: {
		/* ==================== 对外方法 ==================== */

		// 提交前校验：是否已完成人机验证
		check() {
			if (this.passed) return { ok: true, msg: '', data: this.getPayload() };
			if (this.useImage) {
				if (String(this.code).trim() === '') return { ok: false, msg: '请输入图片验证码', data: {} };
				return { ok: true, msg: '', data: this.getPayload() };
			}
			return { ok: false, msg: this.failed ? '验证未通过，请重新验证' : '请先完成人机验证', data: {} };
		},

		// 提交给后端的验证参数（与官网字段名保持一致）
		getPayload() {
			return {
				tc_ticket: this.passed ? this.ticket : '',
				tc_randstr: this.passed ? this.randstr : '',
				captcha: this.useImage ? String(this.code).trim() : ''
			};
		},

		// 重置验证状态（清空滑块结果与图片验证码；页面如需强制重新验证可调用本方法）
		reset() {
			this.passed = false;
			this.failed = false;
			this.loading = false;
			this.ticket = '';
			this.randstr = '';
			this.code = '';
			this.showWV = false;
			if (this.useImage) this.refreshImage();
		},

		/* ==================== 滑块验证 ==================== */

		startVerify() {
			if (this.loading) return;
			this.failed = false;
			// #ifdef H5
			this.startH5();
			// #endif
			// #ifdef APP-PLUS
			this.showWV = true;
			// #endif
		},

		// H5：动态加载 TCaptcha SDK 后唤起（与官网登录同一套 SDK / AppID）
		startH5() {
			// #ifdef H5
			this.loading = true;
			loadTcSdk().then(() => {
				this.loading = false;
				try {
					const cap = new window.TencentCaptcha(this.appid, (res) => {
						this.handleTcResult(res && res.ret === 0
							? { type: 'success', ticket: res.ticket, randstr: res.randstr }
							: { type: 'fail', ret: (res && res.ret) || -1 });
					}, {});
					cap.show();
				} catch (e) {
					this.failed = true;
				}
			}).catch(() => {
				this.loading = false;
				this.failed = true;
			});
			// #endif
		},

		// 校验结果统一入口（H5 回调 / App 本地页回传共用）
		handleTcResult(payload) {
			// #ifdef APP-PLUS
			this.showWV = false; // 关闭本地验证页
			// #endif
			this.loading = false;
			if (!payload || payload.type !== 'success') {
				this.passed = false;
				this.failed = true;
				return;
			}
			this.ticket = payload.ticket || '';
			this.randstr = payload.randstr || '';
			this.passed = !!(this.ticket && this.randstr);
			this.failed = !this.passed;
		},

		// App：<web-view @message>（不同平台触发时机不同，作为 opener 回传的补充）
		onWvMessage(e) {
			if (!this.showWV) return;
			let d = e && e.detail ? e.detail.data : null;
			if (Array.isArray(d)) d = d[d.length - 1];
			if (!d || typeof d !== 'object') return;
			this.handleTcResult(d);
		},

		/* ==================== 图片验证码兜底 ==================== */

		useImageCaptcha() {
			this.useImage = true;
			this.failed = false;
			this.refreshImage();
		},

		backToSlider() {
			this.useImage = false;
			this.code = '';
		},

		// 刷新验证码图片（加时间戳避免浏览器缓存复用，与网站 refreshCaptcha 一致）
		refreshImage() {
			this.code = '';
			this.imgUrl = api.getFileUrl('/server-submit/captcha.php?t=' + Date.now());
		},

		onCodeInput(e) {
			this.code = e.detail.value;
		}
	}
};

// #ifdef H5
// 动态加载 TCaptcha SDK（同一页面只加载一次）
function loadTcSdk() {
	if (window.TencentCaptcha) return Promise.resolve();
	if (window.__tcSdkPromise) return window.__tcSdkPromise;
	window.__tcSdkPromise = new Promise((resolve, reject) => {
		const s = document.createElement('script');
		s.src = TC_SDK;
		s.onload = () => resolve();
		s.onerror = () => {
			window.__tcSdkPromise = null;
			reject(new Error('TCaptcha SDK 加载失败'));
		};
		document.head.appendChild(s);
	});
	return window.__tcSdkPromise;
}
// #endif
</script>

<style scoped>
.cap-root {
	width: 100%;
}

.cap-box {
	padding: 0 30rpx;
	margin-top: 30rpx;
}

/* 滑块验证按钮 */
.cap-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 96rpx;
	background: #f7f8fc;
	border: 2rpx dashed #d9dce6;
	border-radius: 16rpx;
}

.cap-btn:active {
	opacity: 0.86;
}

.cap-btn-icon {
	width: 34rpx;
	height: 34rpx;
	margin-right: 12rpx;
}

.cap-btn-text {
	font-size: 28rpx;
	color: #48416f;
}

/* 已通过 */
.cap-passed {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 96rpx;
	padding: 0 24rpx;
	background: #ecfdf5;
	border: 2rpx solid #34d399;
	border-radius: 16rpx;
	box-sizing: border-box;
}

.cap-passed-icon {
	width: 34rpx;
	height: 34rpx;
	margin-right: 12rpx;
}

.cap-passed-text {
	font-size: 28rpx;
	font-weight: 500;
	color: #059669;
}

/* 切换文案 */
.cap-switch {
	margin-top: 14rpx;
	text-align: right;
	font-size: 24rpx;
	color: #13c2c2;
}

/* 图片验证码兜底 */
.cap-img-row {
	display: flex;
	align-items: center;
}

.cap-img-input {
	flex: 1;
	height: 96rpx;
	padding: 0 20rpx;
	background: #f7f8fc;
	border-radius: 16rpx;
	font-size: 28rpx;
	color: #48416f;
	box-sizing: border-box;
	margin-right: 20rpx;
}

.cap-img {
	width: 220rpx;
	border-radius: 10rpx;
	background: #f2f3f7;
}

/* variant="form"：适配「注销流程」等使用 .form-item / .form-input 风格的页面
   （这类页面的 .form-item 无左右内边距、.form-input 为带边框的 88rpx 输入框） */
.cap-box-form {
	padding: 0;
	margin-top: 0;
}

.cap-box-form .cap-btn,
.cap-box-form .cap-passed {
	height: 88rpx;
	border-radius: 20rpx;
}

.cap-box-form .cap-img-input {
	height: 88rpx;
	padding: 0 30rpx;
	border: 2rpx solid #e5e5e5;
	border-radius: 20rpx;
}

/* 服务端出图为 100×40，宽度 220rpx 时高度正好 88rpx，与输入框齐平 */
.cap-box-form .cap-img {
	width: 220rpx;
	border-radius: 20rpx;
}

.cap-box-form .cap-switch {
	text-align: left;
}
</style>
