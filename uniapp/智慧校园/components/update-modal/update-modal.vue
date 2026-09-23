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
import api from '../../utils/api.js';

// 本站蓝奏云中转地址（php/lanzhouyun.php?type=file）：属于可下载直链
const LANZOU_PROXY_RE = /lanzhouyun\.php\?[^\s]*type=file/i;

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
		// 本站中转地址（lanzhouyun.php?type=file）本身就是可下载的直链，
		// 服务端未标注 direct（或旧版接口）时也按直链处理，避免被错判成「用浏览器打开」
		isDirect() {
			return this.downloadType === 'direct' || LANZOU_PROXY_RE.test(this.downloadUrl || '');
		},
		// 是否蓝奏云来源（用于让用户选择下载方式）：来源标记、本站中转地址、蓝奏云链接都算
		isLanzou() {
			return this.downloadSource === 'lanzou'
				|| LANZOU_PROXY_RE.test(this.downloadUrl || '')
				|| /(^|\/\/)([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(\/|$)/i.test(this.downloadUrl || '');
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
		// 把弹窗上的「下载密码」注入下载地址：地址里没有 pwd 时补上。
		// 蓝奏云分享页与其服务端中转地址都必须带 pwd 才能解析/下载，
		// 只对这两类地址注入，避免污染普通直链。
		withPwd(url) {
			if (!url) return url;
			const pwd = this.downloadPwd ? String(this.downloadPwd).trim() : '';
			if (!pwd) return url;
			if (/[?&]pwd=/i.test(url)) return url; // 已带密码，不重复注入
			const isLanzouRelated = /lanzhouyun\.php/i.test(url)
				|| /(^|\/\/)([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(\/|$)/i.test(url);
			if (!isLanzouRelated) return url;
			// 已含 ? 或 & 时用 & 追加（与后台「链接末尾追加 &pwd=密码」的写法一致）
			const sep = /[?&]/.test(url) ? '&' : '?';
			return url + sep + 'pwd=' + encodeURIComponent(pwd);
		},
		onUpdate() {
			this.$emit('update');
			if (this.downloading) return;
			const url = this.withPwd(this.downloadUrl);
			const page = this.withPwd(this.downloadPage || this.downloadUrl);
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
			// 直链（apk / wgt 等）：点击时才向服务端取最新下载地址（链接有时效），再交给内置下载器
			if (this.isDirect && url) {
				this.startDownloadWithFreshUrl(url, page);
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
			const url = this.withPwd(this.downloadUrl);
			const page = this.withPwd(this.downloadPage || this.downloadUrl);
			// #ifdef APP-PLUS
			// 应用内：点击时才向服务端取最新下载地址，再交给内置下载器
			this.startDownloadWithFreshUrl(url, page);
			// #endif
			// #ifndef APP-PLUS
			// 浏览器端：直接用浏览器下载直链文件
			this.openBrowser(url);
			// #endif
		},
		// 蓝奏云：跳转浏览器下载（打开分享页，带上密码）
		chooseBrowser() {
			this.askVisible = false;
			const page = this.withPwd(this.downloadPage || this.downloadUrl);
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
		// 向服务端索要「当前最新」的下载地址（下载链接有时效性，必须在用户点击下载时获取）
		async fetchFreshDownload() {
			// #ifdef APP-PLUS
			let platform = 'all';
			try {
				const p = uni.getSystemInfoSync().platform;
				if (p === 'android') platform = 'android';
				else if (p === 'ios') platform = 'ios';
			} catch (e) {}
			const params = { platform: platform };
			if (this.version) params.version = this.version;
			const res = await api.getAppDownload(params);
			const d = (res && res.data) || {};
			if (!d.download_url) {
				return null;
			}
			return d;
			// #endif
			// #ifndef APP-PLUS
			return null;
			// #endif
		},
		// 点击下载时才取最新地址；取不到或返回的不是直链时，退回检查更新阶段的地址 / 用浏览器打开
		async startDownloadWithFreshUrl(fallbackUrl, fallbackPage) {
			if (this.downloading) return;
			this.downloading = true; // 取址期间也锁定按钮，避免重复点击
			uni.showLoading({ title: '获取下载地址...', mask: true });
			let info = null;
			try {
				info = await this.fetchFreshDownload();
			} catch (e) {
				// 取址失败：用检查更新阶段返回的地址兜底
			}
			uni.hideLoading();

			// 兜底：取址结果或回退地址都再注入一次下载密码，避免拿到不带 pwd 的地址
			const url = this.withPwd((info && info.download_url) ? info.download_url : fallbackUrl);
			const page = this.withPwd((info && (info.download_page || info.download_url)) || fallbackPage || fallbackUrl);
			if (!url) {
				this.downloading = false;
				uni.showToast({ title: '暂无下载地址', icon: 'none' });
				return;
			}
			// 服务端返回的不是内置下载器可用的直链：按类型降级为浏览器打开
			if (info && info.download_type && info.download_type !== 'direct') {
				this.downloading = false;
				this.openExternal(page);
				return;
			}
			this.startDownload(url, page);
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
						this.downloadFailed(fallbackUrl || url, 'HTTP ' + res.statusCode);
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
			this.prepareApk(tempPath, (apkPath, reason) => {
				if (!apkPath) {
					this.downloading = false;
					this.downloadFailed(fallbackUrl, reason || '下载到的文件不是有效安装包');
					return;
				}
				plus.runtime.install(apkPath, { force: true }, () => {
					this.downloading = false;
					this.$emit('close');
				}, (err) => {
					// 系统返回的真实原因（如未授权「安装未知应用」）随弹窗提示一并给出
					this.downloading = false;
					this.installFailed(fallbackUrl, err);
				});
			});
			// #endif
			// #ifndef APP-PLUS
			this.downloading = false;
			// #endif
		},
		/**
		 * 校验并把下载到的临时文件落地为 _downloads/xxx.apk，回调可安装的绝对路径。
		 * 原因：
		 *  1) 蓝奏云等直链末尾常没有 .apk 扩展名，uni.downloadFile 的临时文件也跟着没扩展名，
		 *     直接交给系统安装器会报「安装包解析失败」；
		 *  2) _downloads 为应用私有下载目录，安装器可直接访问（沙箱下 _doc 临时目录不保证可读）。
		 * 回调 null 表示文件明显不是安装包（多为直链返回了错误页）。
		 */
		prepareApk(tempPath, cb) {
			// #ifdef APP-PLUS
			const safeVer = String(this.version || 'update').replace(/[^\w.\-]/g, '_');
			const fileName = 'smartcampus_' + safeVer + '_' + Date.now() + '.apk';
			// 回退：复制/定位失败时仍用原始临时路径尝试安装（比直接放弃更好）
			const fallback = () => cb(tempPath);
			const copy = (entry) => {
				try {
					plus.io.resolveLocalFileSystemURL('_downloads/', (dir) => {
						entry.copyTo(dir, fileName, (file) => {
							let path = '';
							try { path = file && file.toLocalURL ? file.toLocalURL() : ''; } catch (e) {}
							if (!path) path = (file && file.fullPath) || '';
							// toLocalURL 形如 file:///storage/...，转成绝对路径最稳
							if (path.indexOf('file://') === 0) path = path.substring(7);
							cb(path || tempPath);
						}, () => fallback('写入 _downloads 失败'));
					}, () => fallback('_downloads 目录不可用'));
				} catch (e) {
					fallback('复制异常：' + (e && e.message));
				}
			};
			try {
				plus.io.resolveLocalFileSystemURL(tempPath, (entry) => {
					// 体积异常小：多半是直链返回的错误页/HTML，而不是安装包
					try {
						entry.getMetadata((meta) => {
							const size = (meta && meta.size) || 0;
							if (size && size < 10240) {
								// 小文件通常是服务端返回的错误信息（JSON）或错误页：读出来便于直接看到原因
								this.readSmallFileText(entry, (text) => {
									const m = String(text).match(/"msg"\s*:\s*"([^"]+)"/);
									cb(null, (m && m[1]) ? m[1] : '下载到的文件不是有效安装包');
								});
								return;
							}
							copy(entry);
						}, () => copy(entry));
					} catch (e) {
						copy(entry);
					}
				}, () => fallback('临时文件不存在或不可读'));
			} catch (e) {
				fallback('读取临时文件异常：' + (e && e.message));
			}
			// #endif
			// #ifndef APP-PLUS
			cb(tempPath);
			// #endif
		},
		// 读取小文件（<10KB）文本内容：用于把服务端返回的 JSON 错误信息展示给用户
		readSmallFileText(entry, cb) {
			// #ifdef APP-PLUS
			try {
				const reader = new plus.io.FileReader();
				reader.onloadend = (e) => {
					const text = (e && e.target && e.target.result) || '';
					cb(typeof text === 'string' ? text.slice(0, 300) : '');
				};
				reader.onerror = () => cb('');
				entry.file((file) => reader.readAsText(file), () => cb(''));
			} catch (e) {
				cb('');
			}
			// #endif
			// #ifndef APP-PLUS
			cb('');
			// #endif
		},
		// 兜底：下载失败时询问是否改用浏览器打开（优先分享页）
		downloadFailed(fallbackUrl, reason) {
			uni.showModal({
				title: '下载失败',
				content: (reason ? reason + '。' : '未能通过 App 内置下载器下载安装包。') + '是否改用浏览器打开下载？',
				confirmText: '浏览器下载',
				cancelText: '取消',
				success: (r) => {
					if (r.confirm) this.openExternal(this.withPwd(fallbackUrl));
				}
			});
		},
		// 兜底：安装失败时说明原因/引导开启「安装未知应用」，并询问是否改用浏览器打开
		installFailed(fallbackUrl, err) {
			const reason = err && err.message ? String(err.message) : '';
			const tip = '安装包解析或安装失败。若系统提示未允许安装应用，请在「设置 → 应用 → 安装未知应用」中允许本应用后重试。';
			uni.showModal({
				title: '安装失败',
				content: tip + (reason ? '\n（' + reason + '）' : '') + '\n是否改用浏览器打开下载？',
				confirmText: '浏览器下载',
				cancelText: '取消',
				success: (r) => {
					if (r.confirm) this.openExternal(this.withPwd(fallbackUrl));
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
