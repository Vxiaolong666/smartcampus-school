<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">第三方账号绑定</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="bind-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/icon_cvoieudado/a-199_gongkai.svg" mode="widthFix"></image>
					<view class="card-title">第三方账号绑定</view>
					<view class="card-desc">绑定后可使用对应平台一键登录本系统</view>
				</view>

				<view class="alert-tip">
					<text class="alert-text">绑定前请确认已通过账号密码登录本账号；每个第三方账号只能绑定一个本平台账号。</text>
					<text class="alert-refresh" @click="loadBinds">刷新</text>
				</view>

				<view v-if="loading" class="state-line">
					<text class="state-spin">◌</text> 加载中…
				</view>
				<view v-else-if="!enabled" class="state-line">管理员未开启聚合登录功能</view>
				<view v-else-if="channels.length === 0" class="state-line">暂无可绑定的登录渠道</view>

				<view v-else class="channel-list">
					<view class="channel-item" v-for="(c, i) in channels" :key="i">
						<view class="channel-main">
							<view class="channel-badge" :style="{ backgroundColor: c.color }">{{ c.short }}</view>
							<view class="channel-info">
								<view class="channel-name">{{ c.name }}</view>
								<view class="channel-status" :class="{ 'status-bound': c.bound }">
									{{ c.bound ? (c.nickname ? c.nickname : '已绑定') : '未绑定' }}
								</view>
							</view>
						</view>
						<button v-if="c.bound" class="op-btn op-unbind" :loading="acting && actingType === c.type" @click="onUnbind(c)">解绑</button>
						<button v-else class="op-btn op-bind" :loading="acting && actingType === c.type" @click="onBind(c)">绑定</button>
					</view>
				</view>
			</view>

			<view class="tips-card">
				<view class="tips-title">温馨提示</view>
				<view class="tips-list">
					<view class="tips-item">• 点击「绑定」后将跳转第三方平台完成授权登录</view>
					<view class="tips-item">• 若该第三方账号已被其它账号绑定，需先在原账号解绑</view>
					<view class="tips-item">• 解绑后将无法再使用该平台一键登录</view>
					<view class="tips-item">• 授权完成后返回本页即可看到最新绑定状态</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 第三方账号绑定页面（聚合登录）
 * 参考 Web 端 user/oauth_bind.php：列出已启用渠道，支持绑定 / 解绑
 * 绑定通过内置 WebView 打开第三方授权页，回调由服务端绑定票据完成
 */
import api from '../../utils/api.js';

// 渠道短标识（无图标字体环境下用文字徽标区分渠道）
const SHORT_NAMES = {
	qq: 'QQ',
	wx: '微信',
	alipay: '支',
	sina: '博',
	baidu: '百',
	douyin: '抖',
	huawei: '华',
	xiaomi: '米',
	google: 'G',
	microsoft: 'MS',
	facebook: 'f',
	twitter: 'T',
	feishu: '飞',
	wework: '企',
	dingtalk: '钉',
	gitee: 'Gt',
	github: 'GH',
	telegram: 'Tg'
};

export default {
	data() {
		return {
			loading: true,
			enabled: false,
			channels: [],
			acting: false,
			actingType: ''
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}
		this.loadBinds();
		// 首次 onShow 紧随 onLoad，避免重复请求
		this._skipNextShow = true;
	},

	onShow() {
		// 从授权页返回后刷新绑定状态
		if (this._skipNextShow) {
			this._skipNextShow = false;
			return;
		}
		if (api.isLoggedIn()) {
			this.loadBinds();
		}
	},

	methods: {
		//加载渠道与已绑定信息
		loadBinds() {
			this.loading = true;
			api.getOauthBinds()
				.then(res => {
					const d = res.data || {};
					const binds = d.binds || [];
					const channelMap = d.channels || {};
					const boundMap = {};
					binds.forEach(b => {
						boundMap[b.type] = b;
					});

					this.channels = Object.keys(channelMap).map(type => {
						const meta = channelMap[type] || {};
						const bind = boundMap[type];
						const name = meta.name || type;
						return {
							type: type,
							name: name,
							color: meta.color || '#6c757d',
							short: SHORT_NAMES[type] || String(name).substring(0, 1),
							bound: !!bind,
							nickname: bind ? (bind.nickname || '') : ''
						};
					});
					this.enabled = parseInt(d.enabled) === 1;
					this.loading = false;
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: (err && err.msg) || '加载失败', icon: 'none' });
				});
		},

		//发起绑定：获取授权地址并在 WebView 中打开
		onBind(channel) {
			if (this.acting) return;
			this.acting = true;
			this.actingType = channel.type;
			uni.showLoading({ title: '正在跳转授权...' });

			api.getOauthUrl(channel.type)
				.then(res => {
					uni.hideLoading();
					this.acting = false;
					this.actingType = '';
					const url = (res.data && res.data.url) ? res.data.url : '';
					if (!url) {
						uni.showToast({ title: '获取授权地址失败', icon: 'none' });
						return;
					}
					// #ifdef H5
					// H5 端第三方授权页通常禁止 iframe 嵌套，改用新窗口打开
					window.open(url, '_blank');
					// #endif
					// #ifndef H5
					uni.navigateTo({
						url: '/pages/oauth-bind-webview/index?url=' + encodeURIComponent(url)
					});
					// #endif
				})
				.catch(err => {
					uni.hideLoading();
					this.acting = false;
					this.actingType = '';
					uni.showToast({ title: (err && err.msg) || '获取授权地址失败', icon: 'none' });
				});
		},

		//解除绑定
		async onUnbind(channel) {
			const modalRes = await new Promise(resolve => {
				uni.showModal({
					title: '确认解绑',
					content: '解绑后将无法使用' + channel.name + '一键登录，确定要解绑吗？',
					success: resolve
				});
			});
			if (!modalRes.confirm) return;

			if (this.acting) return;
			this.acting = true;
			this.actingType = channel.type;
			uni.showLoading({ title: '正在解绑...' });

			api.unbindOauth(channel.type)
				.then(() => {
					uni.hideLoading();
					this.acting = false;
					this.actingType = '';
					uni.showToast({ title: '解绑成功', icon: 'success' });
					this.loadBinds();
				})
				.catch(err => {
					uni.hideLoading();
					this.acting = false;
					this.actingType = '';
					uni.showToast({ title: (err && err.msg) || '解绑失败', icon: 'none' });
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
.nav-title {
	height: 100%;
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
	padding-bottom: 30rpx;
}

.bind-card {
	background: white;
	border-radius: 30rpx;
	padding: 50rpx 40rpx;
	margin-top: 25rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.card-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	margin-bottom: 40rpx;
	padding-bottom: 40rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.card-logo {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 20rpx;
}

.card-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 10rpx;
}

.card-desc {
	font-size: 24rpx;
	color: #948eb3;
}

.alert-tip {
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #eef6ff;
	color: #3178c6;
	border-radius: 16rpx;
	padding: 20rpx;
	font-size: 24rpx;
	line-height: 1.6;
	margin-bottom: 30rpx;
}

.alert-text {
	flex: 1;
}

.alert-refresh {
	margin-left: 16rpx;
	flex-shrink: 0;
	color: #13c2c2;
	font-weight: bold;
}

.state-line {
	text-align: center;
	color: #948eb3;
	font-size: 26rpx;
	padding: 40rpx 0;
}

.state-spin {
	margin-right: 8rpx;
}

.channel-list {
	display: flex;
	flex-direction: column;
}

.channel-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	border: 2rpx solid #eef0f6;
	border-radius: 20rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
}

.channel-item:last-child {
	margin-bottom: 0;
}

.channel-main {
	display: flex;
	align-items: center;
	flex: 1;
	min-width: 0;
}

.channel-badge {
	width: 72rpx;
	height: 72rpx;
	border-radius: 18rpx;
	color: #fff;
	font-size: 24rpx;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	margin-right: 20rpx;
}

.channel-info {
	flex: 1;
	min-width: 0;
}

.channel-name {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 6rpx;
}

.channel-status {
	font-size: 22rpx;
	color: #948eb3;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.channel-status.status-bound {
	color: #13c2c2;
}

.op-btn {
	width: 130rpx;
	height: 60rpx;
	line-height: 60rpx;
	padding: 0;
	margin: 0 0 0 16rpx;
	font-size: 24rpx;
	border-radius: 14rpx;
	flex-shrink: 0;
}

.op-btn::after {
	border: none;
}

.op-bind {
	background: #e6fbfb;
	color: #13c2c2;
}

.op-unbind {
	background: white;
	color: #ff4d4f;
	border: 2rpx solid #ff4d4f;
}

.tips-card {
	background: white;
	border-radius: 30rpx;
	padding: 30rpx 40rpx;
	margin-top: 30rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.08);
}

.tips-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 20rpx;
}

.tips-list {
	display: flex;
	flex-direction: column;
}

.tips-item {
	font-size: 24rpx;
	color: #948eb3;
	line-height: 1.6;
	margin-bottom: 12rpx;
}

.tips-item:last-child {
	margin-bottom: 0;
}
</style>
