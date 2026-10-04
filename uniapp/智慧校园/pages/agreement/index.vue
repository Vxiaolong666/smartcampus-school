<template>
	<view class="ag-page">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">协议中心</view>
		</cb-nav-bar>

		<view class="ag-bg-wrapper">
			<image class="ag-bg" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="ag-bg-mask"></view>
		</view>

		<view class="ag-content">
			<view class="ag-intro">
				<view class="ag-intro-title">协议中心</view>
				<view class="ag-intro-desc">这里汇总了本服务的全部协议与声明。请在使用相关服务前仔细阅读并充分理解相关内容，点击即可查看完整条款。</view>
			</view>

			<view v-if="list.length" class="ag-list-card">
				<view
					class="ag-item"
					v-for="item in list"
					:key="item.key"
					@click="openAgreement(item)"
				>
					<image class="ag-item-icon" src="/static/assets/icon_cvoieudado/a-199_wenjian.svg" mode="widthFix"></image>
					<view class="ag-item-main">
						<view class="ag-item-title">{{ item.title }}</view>
						<view v-if="item.desc" class="ag-item-desc">{{ item.desc }}</view>
					</view>
					<image class="ag-item-arrow" src="/static/assets/wxmp-right.svg" mode="widthFix"></image>
				</view>
			</view>

			<!-- 后台未发布任何协议：空状态兜底 -->
			<view v-else class="ag-list-card">
				<view class="ag-empty">
					<image class="ag-empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
					<view class="ag-empty-title">暂无协议</view>
					<view class="ag-empty-desc">当前没有已发布的协议，如有疑问请联系管理员。</view>
				</view>
			</view>

			<view v-if="list.length" class="ag-tips">继续使用本服务，即表示您已阅读并同意上述协议与声明。</view>

			<!-- 底部操作：关于我们 / 联系客服 -->
			<view class="ag-actions">
				<view class="ag-action-btn" @click="goAbout">
					<image class="ag-action-icon" src="/static/assets/icon_cvoieudado/a-199_zhuye.svg" mode="widthFix"></image>
					<text class="ag-action-text">关于我们</text>
				</view>

				<!-- #ifdef MP-WEIXIN -->
				<button class="ag-action-btn" open-type="contact">
					<image class="ag-action-icon" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
					<text class="ag-action-text">联系客服</text>
				</button>
				<!-- #endif -->

				<!-- #ifndef MP-WEIXIN -->
				<view class="ag-action-btn" @click="onContact">
					<image class="ag-action-icon" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
					<text class="ag-action-text">联系客服</text>
				</view>
				<!-- #endif -->
			</view>
		</view>

		<!-- 联系客服自定义弹窗（非小程序端） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
/**
 * 协议中心
 * 清单优先取后台「协议管理」（接口下发）：后台未发布任何协议时展示「暂无协议」；
 * 请求失败（网络异常 / 协议表未初始化）时回退到内置清单，保证离线可读；
 * 每项协议跳转到各自的独立页面（内置协议）或通用详情页。
 */
import api from '../../utils/api.js';
import agreements from '../../utils/agreements.js';

export default {
	data() {
		return {
			// 先用内置清单渲染，避免等待网络
			list: agreements.list.map(it => ({
				key: it.key,
				title: it.title,
				desc: it.desc,
				route: agreements.resolvePath(it.key)
			})),
			// 联系客服弹窗（非小程序端）
			contactShow: false,
			contactLines: []
		};
	},
	onLoad() {
		this.loadFromServer();
	},
	methods: {
		async loadFromServer() {
			try {
				const res = await api.getAgreements();
				const rows = (res && Array.isArray(res.data)) ? res.data : null;
				// 响应异常（非数组）：沿用内置清单
				if (!rows) return;
				const descMap = {};
				agreements.list.forEach(it => { descMap[it.key] = it.desc; });
				// 以服务端清单为准（可能为空：后台把协议全部停用 → 展示「暂无协议」）
				this.list = rows.map(r => ({
					key: r.key,
					title: r.title,
					desc: descMap[r.key] || (r.updated_at ? ('最近更新：' + this.fmtDate(r.updated_at)) : ''),
					route: agreements.resolvePath(r.key)
				}));
			} catch (e) {
				// 拉取失败（网络异常 / 协议表未初始化）：沿用内置清单
			}
		},
		openAgreement(item) {
			if (!item) return;
			const url = item.route || agreements.resolvePath(item.key);
			if (!url) return;
			uni.navigateTo({ url });
		},
		// 关于我们
		goAbout() {
			uni.navigateTo({ url: '/pages/about/index' });
		},
		// 联系客服（非小程序端）：弹窗展示后台「官网配置」中维护的联系方式
		async onContact() {
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
		}
	}
};
</script>
