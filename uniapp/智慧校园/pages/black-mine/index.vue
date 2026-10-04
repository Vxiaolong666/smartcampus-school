<template>
	<view class="page-container">
		<cb-nav-bar compact>
			<view class="nav-title">我的</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="profile-card" @click="isLoggedIn ? handleProfile() : handleLogin()">
				<view class="profile-content">
					<block v-if="isLoggedIn && apiUserInfo">
						<view class="profile-avatar-wrap">
							<image v-if="userAvatar" class="profile-avatar-img" :src="userAvatar" mode="aspectFill"></image>
							<view v-else class="profile-avatar-text">{{ apiUserInfo.nickname || initial }}</view>
						</view>
						<view class="profile-info">
							<view class="profile-name">{{ apiUserInfo.nickname || apiUserInfo.username }}</view>
							<view class="profile-meta">
								<view class="profile-meta-item">
									<text class="meta-value">{{ apiUserInfo.points || 0 }}</text>
									<text class="meta-label">积分</text>
								</view>
								<view class="meta-divider"></view>
								<view class="profile-meta-item">
									<text class="meta-value">Lv.{{ apiUserInfo.level || 1 }}</text>
									<text class="meta-label">等级</text>
								</view>
							</view>
						</view>
						<image class="profile-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
					</block>
					<block v-else>
						<view class="profile-avatar-wrap">
							<view class="profile-avatar-placeholder">
								<image class="avatar-placeholder-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
							</view>
						</view>
						<view class="profile-info">
							<view class="profile-name">未登录</view>
							<view class="profile-meta">
								<view class="profile-login-hint">点击登录账号</view>
							</view>
						</view>
						<image class="profile-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
					</block>
				</view>
			</view>

			<view class="item-bar">
				<block v-if="isLoggedIn">
					<view class="item-group">
						<view class="item-group-title">账号绑定</view>
						<view class="item-group-content">
							<view class="item-item" @click="handleAcademic">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_dangan-07.svg" mode="widthFix"></image>
								<view class="item-text">教务账号绑定</view>
								<text class="status-text" :class="academicBound ? 'status-bound' : 'status-unbound'">{{ academicBound ? '已绑定' : '未绑定' }}</text>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
							<view class="item-item" @click="handleDormitory">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_zhuye.svg" mode="widthFix"></image>
								<view class="item-text">宿舍绑定</view>
								<text class="status-text" :class="dormitoryCount > 0 ? 'status-bound' : 'status-unbound'">
									{{ dormitoryCount > 0 ? '已绑定 ' + dormitoryCount + '/' + maxDormitories : '未绑定' }}
								</text>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
						</view>
					</view>

					<view class="item-group">
						<view class="item-group-title">订阅功能设置</view>
						<view class="item-group-content">
							<view class="item-item" @click="handleSubscription">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_biaoji.svg" mode="widthFix"></image>
								<view class="item-text">订阅管理</view>
								<text class="status-text" :class="subscriptionStatusClass">{{ subscriptionStatus }}</text>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
							<view class="item-item" @click="handleAcademicPushSettings('course')">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_tixing.svg" mode="widthFix"></image>
								<view class="item-text">课程推送设置</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
							<view class="item-item" @click="handleAcademicPushSettings('grade')">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_jiangzhang.svg" mode="widthFix"></image>
								<view class="item-text">成绩推送设置</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
							<view class="item-item" @click="handlePowerPushSettings">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_jishi.svg" mode="widthFix"></image>
								<view class="item-text">电费推送设置</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
						</view>
					</view>

					<view class="item-group">
						<view class="item-group-title">账户与安全</view>
						<view class="item-group-content">
							<view class="item-item" @click="handleProfile">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_bianji-24.svg" mode="widthFix"></image>
								<view class="item-text">编辑资料</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>

							<view class="item-item" @click="handleChangePassword">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_anquan.svg" mode="widthFix"></image>
								<view class="item-text">修改密码</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>

							<view class="item-item" @click="handleWxBind">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_zhangdan.svg" mode="widthFix"></image>
								<view class="item-text">微信绑定</view>
								<text class="status-text" :class="wxBound ? 'status-bound' : 'status-unbound'">{{ wxBound ? '已绑定' : '未绑定' }}</text>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>

							<view class="item-item" @click="handleOauthBind">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_xiaopiao.svg" mode="widthFix"></image>
								<view class="item-text">第三方账号绑定</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>

							<view class="item-item" @click="handleCancelAccount">
								<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_gongkai.svg" mode="widthFix"></image>
								<view class="item-text">账号注销</view>
								<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
							</view>
						</view>
					</view>
				</block>

				<view v-else class="item-group">
					<view class="item-group-content">
						<view class="item-item" @click="handleLogin">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_gongkai.svg" mode="widthFix"></image>
							<view class="item-text">登录 / 注册</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>
					</view>
				</view>

				<view class="item-group">
					<view class="item-group-title">客服与支持</view>
					<view class="item-group-content">
						<!-- #ifdef MP-WEIXIN -->
						<button class="item-item" open-type="contact">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
							<view class="item-text">联系客服</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</button>
						<!-- #endif -->

						<!-- #ifndef MP-WEIXIN -->
						<view class="item-item" @click="handleContact">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
							<view class="item-text">联系客服</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>
						<!-- #endif -->

						<view class="item-item" @click="handleFeedback">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_bianji-24.svg" mode="widthFix"></image>
							<view class="item-text">意见反馈</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>

						<view class="item-item" @click="handleAgreement">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_wenjian.svg" mode="widthFix"></image>
							<view class="item-text">协议中心</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>

						<view class="item-item" @click="handleAbout">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_zhuye.svg" mode="widthFix"></image>
							<view class="item-text">关于我们</view>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>

						<view class="item-item" @click="handleClearCache">
							<image class="item-logo" src="/static/assets/icon_cvoieudado/a-199_huishou.svg" mode="widthFix"></image>
							<view class="item-text">清理缓存</view>
							<text class="item-extra">{{ cacheSizeText }}</text>
							<image class="item-right" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
						</view>
					</view>
				</view>

				<view v-if="isLoggedIn" class="bottom-buttons">
					<button class="bottom-btn switch-btn" @click="handleSwitchAccount">
						<image class="bottom-btn-icon" src="/static/assets/wxmp-switch.svg" mode="widthFix"></image>
						<text>切换账号</text>
					</button>
					<button class="bottom-btn logout-btn" @click="handleLogout">
						<image class="bottom-btn-icon" src="/static/assets/wxmp-logout.svg" mode="widthFix"></image>
						<text>退出登录</text>
					</button>
				</view>
			</view>
		</view>
		<!-- 联系客服自定义弹窗（参考更新弹窗样式） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
/**
 * 我的页面
 * 展示用户信息和功能入口
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			visible: false,
			avatarUrl: '',
			userName: '',
			agree: false,
			isLoggedIn: false,
			apiUserInfo: null,
			wxBindText: '绑定微信',
			userAvatar: '',
			academicBound: false,
			dormitoryCount: 0,
			maxDormitories: 5,
			subscriptionStatus: '',
			wxBound: false,
			// 缓存占用展示文案（如 "1.24 MB"）
			cacheSizeText: '--',
			// 上次加载数据的时间戳，用于避免频繁切换页面时重复请求
			_lastLoadAt: 0,
			contactShow: false,
			contactLines: []
		};
	},

	computed: {
		initial() {
			const u = this.apiUserInfo && this.apiUserInfo.username;
			return u ? u[0] : '';
		},
		subscriptionStatusClass() {
			if (this.subscriptionStatus === '正常') return 'status-ok';
			if (this.subscriptionStatus === '即将不足') return 'status-low';
			return 'status-empty';
		}
	},

	onShow() {
		this.checkLoginStatus();
		this.loadPageData();
		this.refreshCacheSize();
	},

	// 点击底部「我的」导航（含重复点击当前 Tab）：强制刷新页面数据
	onTabItemTap() {
		this.checkLoginStatus();
		this.loadPageData(true);
		this.refreshCacheSize();
	},

	methods: {
		// 刷新缓存占用：优先取本地存储真实占用（KB→MB）；受限时按「上次清理时间」推算 0~3MB
		refreshCacheSize() {
			let currentSize = -1;
			try {
				const info = uni.getStorageInfoSync ? uni.getStorageInfoSync() : null;
				if (info && typeof info.currentSize === 'number') {
					currentSize = info.currentSize;
				}
			} catch (e) {}
			if (currentSize >= 0) {
				let mb = Math.round(currentSize / 1024 * 100) / 100;
				// 最小显示 0.01MB，避免出现 0.00MB
				if (mb < 0.01) mb = 0.01;
				this.cacheSizeText = mb.toFixed(2) + ' MB';
			} else {
				this.cacheSizeText = this._estimateCacheSize().toFixed(2) + ' MB';
			}
		},

		// 技术受限时的兜底：以「距上次清理的时长」为种子的确定性伪随机，落在 0~3MB
		_estimateCacheSize() {
			let last = 0;
			try { last = uni.getStorageSync('cache_cleared_at') || 0; } catch (e) {}
			const elapsedMin = last ? Math.max(0, (Date.now() - last) / 60000) : 0;
			let mb = Math.abs(Math.sin(elapsedMin)) * 3;
			if (mb < 0.1) mb = 0.1;
			return Math.round(mb * 100) / 100;
		},

		// 清理缓存（保留登录态 api_token / api_userInfo，不会退出登录）
		handleClearCache() {
			uni.showModal({
				title: '清理缓存',
				content: '将清除本地缓存数据（不会退出登录），是否继续？',
				confirmText: '清理',
				success: (r) => {
					if (!r.confirm) return;
					const KEEP = ['api_token', 'api_userInfo'];
					try {
						const info = uni.getStorageInfoSync ? uni.getStorageInfoSync() : null;
						const keys = (info && info.keys) ? info.keys : [];
						keys.forEach((k) => {
							if (KEEP.indexOf(k) === -1) {
								try { uni.removeStorageSync(k); } catch (e) {}
							}
						});
					} catch (e) {}
					try { uni.setStorageSync('cache_cleared_at', Date.now()); } catch (e) {}
					this.refreshCacheSize();
					uni.showToast({ title: '清理完成', icon: 'success' });
				}
			});
		},

		// force = true 时跳过节流（点击 Tab 强制刷新使用）
		loadPageData(force) {
			// 短时间内反复回到本页时不再重复发请求，减少低端设备的网络等待
			const now = Date.now();
			if (!force && this._lastLoadAt && now - this._lastLoadAt < 30000) return;
			this._lastLoadAt = now;

			this.loadUserInfo();
			this.checkAcademicStatus();
			this.checkDormitoryStatus();
			this.checkSubscriptionStatus();
		},

		checkLoginStatus() {
			const isLoggedIn = api.isLoggedIn();
			const apiUserInfo = api.getUserInfoCache();

			if (!isLoggedIn) {
				this.isLoggedIn = false;
				this.apiUserInfo = null;
				this.userAvatar = '';
				this.wxBound = false;
			} else {
				this.isLoggedIn = isLoggedIn;
				this.apiUserInfo = apiUserInfo;
				this.checkWxBindStatus();
			}
		},

		checkWxBindStatus() {
			const userInfo = this.apiUserInfo;
			if (userInfo && userInfo.wx_openid) {
				this.wxBindText = '解绑微信';
				this.wxBound = true;
			} else {
				this.wxBindText = '绑定微信';
				this.wxBound = false;
			}
		},

		loadUserInfo() {
			if (!api.isLoggedIn()) return;

			api.getUserInfo()
				.then(res => {
					const userInfo = res.data;

					this.apiUserInfo = userInfo;
					this.userAvatar = api.getFileUrl(userInfo.avatar);

					api.setUserInfoCache(userInfo);
					this.checkWxBindStatus();
				})
				.catch(err => {
					if (err.code === -2) {
						this.isLoggedIn = false;
						this.apiUserInfo = null;
					}
				});
		},

		checkAcademicStatus() {
			if (!api.isLoggedIn()) {
				this.academicBound = false;
				return;
			}

			api.getAcademic()
				.then(res => {
					this.academicBound = !!(res.data && res.data.student_id);
				})
				.catch(() => {
					this.academicBound = false;
				});
		},

		checkDormitoryStatus() {
			if (!api.isLoggedIn()) {
				this.dormitoryCount = 0;
				return;
			}

			api.getDormitories()
				.then(res => {
					this.dormitoryCount = (res.data || []).length;
				})
				.catch(() => {
					this.dormitoryCount = 0;
				});
		},

		checkSubscriptionStatus() {
			if (!api.isLoggedIn()) {
				this.subscriptionStatus = '无法发送';
				return;
			}

			api.request({
				act: 'get_user_subscriptions',
				method: 'GET'
			})
				.then(res => {
					const list = res.data.list || [];

					if (list.length === 0) {
						this.subscriptionStatus = '无法发送';
						return;
					}

					const hasEmpty = list.some(item => (item.subscribe_count || 0) === 0);
					const hasLow = list.some(item => (item.subscribe_count || 0) < 2);

					let status = '正常';
					if (hasEmpty) {
						status = '无法发送';
					} else if (hasLow) {
						status = '即将不足';
					}

					this.subscriptionStatus = status;
				})
				.catch(() => {
					this.subscriptionStatus = '无法发送';
				});
		},

		handleAgreement() {
			// 统一进入「协议中心」（小程序端不再直接跳微信隐私契约）
			uni.navigateTo({
				url: '/pages/agreement/index'
			});
		},

		// 意见反馈：提交反馈并查看处理进度
		handleFeedback() {
			uni.navigateTo({
				url: '/pages/feedback/index'
			});
		},

		handleAbout() {
			uni.navigateTo({
				url: '/pages/about/index'
			});
		},

		// 联系客服（非小程序端）：弹窗展示后台「官网配置」中维护的联系方式
		async handleContact() {
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
				lines.push('暂未配置联系方式，请通过「我的」页面中的「联系客服」与我们取得联系。');
			}

			this.contactLines = lines;
			this.contactShow = true;
			},

		handleLogin() {
			uni.navigateTo({
				url: '/pages/login/index'
			});
		},

		handleProfile() {
			if (!this.isLoggedIn) {
				this.handleLogin();
				return;
			}
			uni.navigateTo({
				url: '/pages/profile/index'
			});
		},

		handleChangePassword() {
			uni.navigateTo({
				url: '/pages/change-password/index'
			});
		},

		handleWxBind() {
			uni.navigateTo({
				url: '/pages/wx-bind/index'
			});
		},

		handleCancelAccount() {
			uni.navigateTo({
				url: '/pages/cancel-account/index'
			});
		},

		handleOauthBind() {
			uni.navigateTo({
				url: '/pages/oauth-bind/index'
			});
		},

		handleAcademic() {
			uni.navigateTo({
				url: '/pages/academic-bind/index'
			});
		},

		handleDormitory() {
			uni.navigateTo({
				url: '/pages/dormitory-bind/index'
			});
		},

		// 课程/成绩推送设置共用同一页面，通过 scene 参数让目标页按入口展示标题
		handleAcademicPushSettings(scene) {
			uni.navigateTo({
				url: '/pages/academic-push-settings/index' + (scene ? '?scene=' + scene : '')
			});
		},

		handlePowerPushSettings() {
			uni.navigateTo({
				url: '/pages/push-settings/index'
			});
		},

		handleSubscription() {
			uni.navigateTo({
				url: '/pages/subscription/index'
			});
		},

		handleLogout() {
			uni.showModal({
				title: '提示',
				content: '确定要退出登录吗？',
				success: res => {
					if (res.confirm) {
						api.logout()
							.catch(() => {})
							.finally(() => {
								api.clearAuth();
								this.isLoggedIn = false;
								this.apiUserInfo = null;
								this.userAvatar = '';
								this._lastLoadAt = 0;
								uni.showToast({ title: '已退出登录', icon: 'success' });
							});
					}
				}
			});
		},

		handleSwitchAccount() {
			uni.showModal({
				title: '提示',
				content: '确定要切换账号吗？',
				success: res => {
					if (res.confirm) {
						api.clearAuth();
						this.isLoggedIn = false;
						this.apiUserInfo = null;
						this.userAvatar = '';
						this._lastLoadAt = 0;
						uni.navigateTo({
							url: '/pages/login/index'
						});
					}
				}
			});
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
}
</style>

<style scoped>
.page-container {
	position: relative;
}

.nav-title {
	display: flex;
	align-items: center;
	font-size: 32rpx;
	font-weight: bold;
	position: relative;
	z-index: 1;
	color: white;
	letter-spacing: 6rpx;
}

.background-wrapper {
	position: fixed;
	width: 100%;
	top: 0;
}

.background {
	width: 100%;
	position: absolute;
	top: 0;
	height: 800rpx;
}

.background-mask {
	background-image: linear-gradient(rgba(0, 0, 0, 0.8), transparent);
	height: 600rpx;
	position: absolute;
	width: 100%;
	top: 0;
}

.content-wrapper {
	position: relative;
	z-index: 1;
	background: #eff1ff;
	top: 30rpx;
	min-height: calc(100vh - 200rpx);
	border-top-left-radius: 60rpx;
	border-top-right-radius: 60rpx;
	box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
	padding: 30rpx;
	padding-bottom: 100rpx;
}

.profile-card {
	border-radius: 24rpx;
	background: white;
	overflow: hidden;
	border: 1rpx solid #f0f0f5;
	margin-bottom: 20rpx;
}

.profile-content {
	display: flex;
	align-items: center;
	padding: 40rpx 30rpx;
}

.profile-avatar-wrap {
	flex-shrink: 0;
}

.profile-avatar-img {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	border: 4rpx solid #f0eef5;
}

.profile-avatar-text {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #ff6348 0%, #ee5a24 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	color: white;
	font-size: 40rpx;
	font-weight: bold;
}

.profile-avatar-placeholder {
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	background: #f0eef5;
	display: flex;
	align-items: center;
	justify-content: center;
}

.profile-avatar-placeholder .avatar-placeholder-icon {
	width: 44rpx;
	height: 44rpx;
}

.profile-info {
	flex: 1;
	margin-left: 28rpx;
	overflow: hidden;
}

.profile-name {
	font-size: 34rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.profile-meta {
	display: flex;
	align-items: center;
}

.profile-meta-item {
	display: flex;
	align-items: baseline;
	gap: 6rpx;
}

.meta-value {
	font-size: 30rpx;
	color: #48416f;
	font-weight: bold;
}

.meta-label {
	font-size: 22rpx;
	color: #948eb3;
}

.meta-divider {
	width: 2rpx;
	height: 24rpx;
	background: #e8e5f0;
	margin: 0 24rpx;
}

.profile-login-hint {
	font-size: 24rpx;
	color: #948eb3;
}

.profile-arrow {
	width: 30rpx;
	height: 30rpx;
	flex-shrink: 0;
	opacity: 0.9;
}

.item-bar {
	display: flex;
	flex-direction: column;
	padding-bottom: 3rpx;
}

.item-group {
	margin-bottom: 20rpx;
}

.item-group:last-child {
	margin-bottom: 0;
}

.item-group-title {
	font-size: 24rpx;
	color: #948eb3;
	padding: 0 10rpx;
	margin-bottom: 16rpx;
}

.item-group-content {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
}

.item-item {
	display: flex;
	align-items: center;
	height: 100rpx;
	padding: 0 30rpx;
	background: white;
	margin-bottom: 2rpx;
	margin-left: 0;
	margin-right: 0;
	width: 100% !important;
	box-sizing: border-box;
	text-align: inherit;
	font-weight: normal;
	border-radius: 0;
	line-height: inherit;
}

.item-group-content .item-item:first-child {
	border-radius: 20rpx 20rpx 0 0;
}

.item-group-content .item-item:last-child {
	border-radius: 0 0 20rpx 20rpx;
	margin-bottom: 0;
}

.item-group-content .item-item:only-child {
	border-radius: 20rpx;
}

.item-logo {
	width: 50rpx;
	height: 50rpx;
}

.item-text {
	font-size: 28rpx;
	margin-left: 20rpx;
	color: rgba(0, 0, 0, 0.65);
	flex: 1;
}

.item-right {
	width: 30rpx;
	height: 30rpx;
}

.item-extra {
	font-size: 24rpx;
	color: #948eb3;
	margin-right: 12rpx;
}

.status-text {
	font-size: 24rpx;
	margin-right: 16rpx;
}

.status-bound {
	color: #27ae60;
}

.status-unbound {
	color: #e74c3c;
}

.status-ok {
	color: #27ae60;
}

.status-low {
	color: #f39c12;
}

.status-empty {
	color: #e74c3c;
}

.bottom-buttons {
	display: flex;
	gap: 30rpx;
	margin-top: 30rpx;
	padding-bottom: calc(10rpx + env(safe-area-inset-bottom));
}

.bottom-btn {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	height: 88rpx;
	border-radius: 16rpx;
	font-weight: bold;
	margin: 0;
	padding: 0;
}

.bottom-btn::after {
	display: none;
}

.bottom-btn-icon {
	width: 36rpx;
	height: 36rpx;
	margin-right: 12rpx;
}

.switch-btn {
	background: white;
	color: #13c2c2;
	border: 2rpx solid rgba(19, 194, 194, 0.4);
	box-sizing: border-box;
}

.logout-btn {
	background: white;
	color: #e74c3c;
	border: 2rpx solid rgba(231, 76, 60, 0.4);
	box-sizing: border-box;
}
</style>
