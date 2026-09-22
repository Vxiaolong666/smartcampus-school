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

					<!-- 蓝奏云文件下载密码（如有）：自动下载失败时，可在浏览器打开分享页并使用该密码 -->
					<view v-if="downloadPwd" class="update-pwd">
						<text class="update-pwd-label">下载密码</text>
						<text class="update-pwd-value">{{ downloadPwd }}</text>
						<text class="update-pwd-copy" :class="{ 'update-pwd-copy-done': copied }" @click="copyPwd">{{ copied ? '已复制' : '复制' }}</text>
					</view>

					<scroll-view scroll-y class="update-log">
						<view class="update-log-item" v-for="(line, idx) in logLines" :key="idx">
							<view class="update-log-dot"></view>
							<text class="update-log-text">{{ line }}</text>
						</view>
					</scroll-view>
				</view>

				<view v-if="!askVisible" class="update-actions">
					<view v-if="!isForce && !downloading" class="update-btn update-btn-ghost" @click="onClose">稍后再说</view>
					<view class="update-btn update-btn-primary" :class="{ 'update-btn-loading': downloading }" @click="onUpdate">
						{{ downloading ? ('下载中 ' + progress + '%') : '立即更新' }}
					</view>
				</view>

				<!-- 下载方式选择（蓝奏云）：做进弹窗内部，各端一致且不会被弹窗遮挡 -->
				<view v-else class="update-ask">
					<view class="update-ask-title">请选择下载方式</view>
					<view class="update-ask-btns">
						<view class="update-btn update-btn-ghost" @click="chooseBrowser">跳转浏览器下载</view>
						<view class="update-btn update-btn-primary" @click="chooseDirect">直接下载更新包</view>
					</view>
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
		downloadUrl: { type: String, default: '' },
		// direct：apk 等直链，使用 App 内置下载器下载并拉起安装；external：用浏览器打开
		downloadType: { type: String, default: 'external' },
		// 下载来源：lanzou=蓝奏云分享链接；direct=直链文件；external=其它
		downloadSource: { type: String, default: 'external' },
		// 蓝奏云文件下载密码（如有），用于弹窗展示与复制
		downloadPwd: { type: String, default: '' },
		// 原始分享页地址，作为直链解析/下载失败时的兜底
		downloadPage: { type: String, default: '' }
	},
	data() {
		return {
			downloading: false,
			progress: 0,
			copied: false,
			askVisible: false
		};
	},
	watch: {
		// 弹窗关闭时复位内部状态，避免下次打开残留
		show(val) {
			if (!val) {
				this.askVisible = false;
				this.downloading = false;
				this.copied = false;
				this.progress = 0;
			}
		}
	},
	computed: {
		// 兼容布尔或 0/1 传值
		isForce() {
			return this.force === true || parseInt(this.force, 10) === 1;
		},
		// 是否可用内置下载器直接下载安装
		isDirect() {
			return this.downloadType === 'direct';
		},
		// 是否蓝奏云来源（用于让用户选择下载方式）
		isLanzou() {
			return this.downloadSource === 'lanzou';
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
			if (this.downloading) return;
			this.$emit('close');
		},
		onMaskClick() {
			this.onClose();
		},
		// 复制下载密码（内联反馈 + toast 双保险：弹窗遮罩层级高，H5 的 toast 可能被盖住）
		copyPwd() {
			if (!this.downloadPwd) return;
			const pwd = String(this.downloadPwd);
			const onDone = () => {
				this.copied = true;
				if (this._copyTimer) clearTimeout(this._copyTimer);
				this._copyTimer = setTimeout(() => { this.copied = false; }, 1600);
				uni.showToast({ title: '密码已复制', icon: 'none' });
			};
			const onFail = () => {
				// H5 兼容兜底：非安全上下文时 navigator.clipboard 不可用，改用临时输入框复制
				// #ifdef H5
				try {
					const ta = document.createElement('textarea');
					ta.value = pwd;
					ta.style.position = 'fixed';
					ta.style.opacity = '0';
					document.body.appendChild(ta);
					ta.select();
					const ok = document.execCommand('copy');
					document.body.removeChild(ta);
					if (ok) { onDone(); return; }
				} catch (e) {}
				// #endif
				this.copied = false;
				uni.showToast({ title: '复制失败，请长按选择复制', icon: 'none' });
			};
			uni.setClipboardData({
				data: pwd,
				success: onDone,
				fail: onFail
			});
		},
		// 用系统浏览器打开（App 端）
		openExternal(url) {
			if (!url) return;
			// #ifdef APP-PLUS
			plus.runtime.openURL(url);
			// #endif
		},
		onUpdate() {
			this.$emit('update');
			if (this.downloading) return;
			const url = this.downloadUrl;
			const page = this.downloadPage || url;
			if (!url && !page) {
				uni.showToast({ title: '暂无下载地址', icon: 'none' });
				return;
			}
			// 蓝奏云且已解析出直链：弹窗内提示用户选择下载方式（各端一致，避免 H5 系统提示被弹窗遮挡）
			if (this.isLanzou && this.isDirect && url) {
				this.askVisible = true;
				return;
			}
			// 蓝奏云但未解析出直链：无法应用内下载，改用浏览器打开分享页
			if (this.isLanzou && !this.isDirect) {
				// #ifdef APP-PLUS
				this.openExternal(page || url);
				uni.showToast({ title: '未解析出直链，已用浏览器打开', icon: 'none' });
				// #endif
				// #ifndef APP-PLUS
				this.openBrowser(page || url);
				// #endif
				return;
			}
			// #ifdef APP-PLUS
			// 直链（apk / wgt 等）：使用 App 内置下载器，下载完成后拉起安装
			if (this.isDirect && url) {
				this.startDownload(url, page);
				return;
			}
			// 非直链（应用商店等）：交给系统浏览器打开
			this.openExternal(page || url);
			// #endif
			// #ifndef APP-PLUS
			// 浏览器端无法内置安装，直接用浏览器打开
			this.openBrowser(page || url);
			// #endif
		},
		// 蓝奏云：直接下载更新包
		chooseDirect() {
			this.askVisible = false;
			const url = this.downloadUrl;
			const page = this.downloadPage || url;
			// #ifdef APP-PLUS
			// 应用内：内置下载器下载，完成后拉起安装
			this.startDownload(url, page);
			// #endif
			// #ifndef APP-PLUS
			// 浏览器端：直接用浏览器下载直链文件
			this.openBrowser(url);
			// #endif
		},
		// 蓝奏云：跳转浏览器下载（打开分享页）
		chooseBrowser() {
			this.askVisible = false;
			const page = this.downloadPage || this.downloadUrl;
			// #ifdef APP-PLUS
			this.openExternal(page);
			// #endif
			// #ifndef APP-PLUS
			this.openBrowser(page);
			// #endif
		},
		// 浏览器端打开链接（H5 / H5 打包）
		openBrowser(url) {
			if (!url) return;
			// #ifndef APP-PLUS
			try {
				window.open(url, '_blank');
			} catch (e) {}
			// #endif
		},
		// 内置下载器下载安装包，完成后调用 plus.runtime.install 拉起安装
		startDownload(url, fallbackUrl) {
			this.downloading = true;
			this.progress = 0;
			const task = uni.downloadFile({
				url: url,
				success: (res) => {
					if (res.statusCode === 200 && res.tempFilePath) {
						this.installApk(res.tempFilePath, fallbackUrl || url);
					} else {
						this.downloading = false;
						this.downloadFailed(fallbackUrl || url);
					}
				},
				fail: () => {
					this.downloading = false;
					this.downloadFailed(fallbackUrl || url);
				}
			});
			if (task && typeof task.onProgressUpdate === 'function') {
				task.onProgressUpdate((p) => {
					this.progress = (p && typeof p.progress === 'number') ? p.progress : 0;
				});
			}
		},
		installApk(tempPath, fallbackUrl) {
			// #ifdef APP-PLUS
			plus.runtime.install(tempPath, { force: true }, () => {
				this.downloading = false;
				this.$emit('close');
			}, () => {
				this.downloading = false;
				this.installFailed(fallbackUrl);
			});
			// #endif
			// #ifndef APP-PLUS
			this.downloading = false;
			// #endif
		},
		// 兜底：下载失败时询问是否改用浏览器打开（优先分享页）
		downloadFailed(fallbackUrl) {
			uni.showModal({
				title: '下载失败',
				content: '未能通过 App 内置下载器下载安装包，是否改用浏览器打开下载？',
				confirmText: '浏览器下载',
				cancelText: '取消',
				success: (r) => {
					if (r.confirm) this.openExternal(fallbackUrl);
				}
			});
		},
		// 兜底：安装失败时询问是否改用浏览器打开
		installFailed(fallbackUrl) {
			uni.showModal({
				title: '安装失败',
				content: '安装包解析或安装失败，是否改用浏览器打开下载？',
				confirmText: '浏览器下载',
				cancelText: '取消',
				success: (r) => {
					if (r.confirm) this.openExternal(fallbackUrl);
				}
			});
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

/* 下载密码行 */
.update-pwd {
	display: flex;
	align-items: center;
	padding: 16rpx 20rpx;
	margin-bottom: 20rpx;
	background: #eef9fb;
	border: 1rpx solid #cdeef2;
	border-radius: 16rpx;
}

.update-pwd-label {
	font-size: 24rpx;
	color: #6b7280;
	flex-shrink: 0;
}

.update-pwd-value {
	flex: 1;
	margin-left: 16rpx;
	font-size: 28rpx;
	font-weight: bold;
	color: #0e8c8c;
	letter-spacing: 2rpx;
	word-break: break-all;
}

.update-pwd-copy {
	flex-shrink: 0;
	margin-left: 16rpx;
	padding: 6rpx 20rpx;
	font-size: 22rpx;
	color: #0e8c8c;
	background: #ffffff;
	border-radius: 999rpx;
}

.update-pwd-copy:active {
	opacity: 0.7;
}

/* 复制成功：按钮变为主色，提供明确的内联反馈 */
.update-pwd-copy-done {
	color: #ffffff;
	background: #13c2c2;
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

/* 下载方式选择面板（蓝奏云）：与按钮区一致，做进弹窗内部 */
.update-ask {
	padding: 22rpx 36rpx 36rpx;
}

.update-ask-title {
	text-align: center;
	font-size: 26rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
}

.update-ask-btns {
	display: flex;
	gap: 20rpx;
}

.update-ask-btns .update-btn {
	flex: 1;
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

/* 下载中：按钮不可点击的视觉反馈 */
.update-btn-loading {
	opacity: 0.75;
}
</style>
