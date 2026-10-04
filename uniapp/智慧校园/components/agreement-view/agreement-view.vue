<template>
	<view class="ag-page">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">{{ title }}</view>
		</cb-nav-bar>

		<view class="ag-bg-wrapper">
			<image class="ag-bg" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="ag-bg-mask"></view>
		</view>

		<view class="ag-content">
			<view class="ag-card">
				<view class="ag-header">
					<image class="ag-logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
					<view class="ag-title">{{ title }}</view>
				</view>
				<view v-if="notPublished" class="ag-body">
					<view class="ag-empty">
						<image class="ag-empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
						<view class="ag-empty-title">当前协议未发布</view>
						<view class="ag-empty-desc">该协议尚未开放或已停用，如有疑问请联系管理员。</view>
					</view>
				</view>
				<!-- 正文始终从接口获取：加载中 -->
				<view v-else-if="loading" class="ag-body">
					<view class="ag-loading-box">
						<view class="ag-loading-spinner"></view>
						<view class="ag-loading-tip">加载中…</view>
					</view>
				</view>
				<!-- 拉取失败（网络异常 / 协议表未初始化）：不再回退本地内置内容 -->
				<view v-else-if="failed" class="ag-body">
					<view class="ag-empty">
						<image class="ag-empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
						<view class="ag-empty-title">协议加载失败</view>
						<view class="ag-empty-desc">网络异常或服务暂不可用，请稍后重试。</view>
						<view class="ag-retry-btn" @click="retryLoad">重新加载</view>
					</view>
				</view>
				<view v-else class="ag-body">
					<view v-if="updatedAt" class="ag-update">最后更新日期：{{ updatedAt }}</view>
					<!-- 正文拆成「文本块 + 图片块」：文本交 rich-text，图片用原生 image（各端稳定显示、可点击放大） -->
					<md-blocks :blocks="blocks" @preview="previewImage" />
				</view>
				<view class="ag-footer">感谢您信任并使用本服务</view>
			</view>

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
 * 协议内容渲染（协议中心各协议页共用）
 * ------------------------------------------------------------------
 * 传入协议标识 akey，内容一律来源于后台「协议管理」（接口下发 Markdown），
 * 本地不留存任何协议预留信息：
 *   - 后台已停用 / 未配置（not_published）：展示「当前协议未发布」；
 *   - 其它情况（网络异常、协议表未初始化等）：展示「加载失败 + 重新加载」。
 */
import api from '../../utils/api.js';
import { markdownToBlocks } from '../../utils/markdown.js';

export default {
	name: 'agreement-view',
	props: {
		akey: { type: String, default: '' }
	},
	data() {
		return {
			title: '',
			updatedAt: '',
			blocks: [],          // 正文块：{type:'html',html} 或 {type:'image',src}
			loading: true,       // 正文始终从接口获取
			failed: false,       // 拉取失败（网络异常 / 协议表未初始化等）
			notPublished: false, // 后台未配置 / 已停用
			// 联系客服弹窗（非小程序端）
			contactShow: false,
			contactLines: []
		};
	},
	watch: {
		akey() {
			this.load();
		}
	},
	created() {
		this.load();
	},
	methods: {
		// Markdown → 块序列（图片相对路径自动补全为完整地址）
		toBlocks(md) {
			return markdownToBlocks(md || '', { resolveUrl: api.getFileUrl });
		},
		// 内容一律来自接口（本地不留存任何协议预留信息）
		async load() {
			if (!this.akey) {
				this.loading = false;
				this.failed = true;
				this.notPublished = false;
				this.blocks = [];
				this.updatedAt = '';
				return;
			}
			this.loading = true;
			this.failed = false;
			this.notPublished = false;
			try {
				const res = await api.getAgreement(this.akey);
				const d = (res && res.data) || {};
				this.title = d.title || this.title;
				this.updatedAt = d.updated_at ? this.fmtDate(d.updated_at) : '';
				this.blocks = d.content ? this.toBlocks(d.content) : [];
				this.loading = false;
				// 接口成功但正文为空：按「未发布」处理，避免出现空白页
				this.notPublished = !d.content;
			} catch (e) {
				// 后台明确「未发布」：展示「当前协议未发布」
				// 其它错误（网络异常、协议表未初始化等）：展示「加载失败」，不再回退本地内容
				const notPublished = !!(e && e.data && e.data.not_published);
				this.loading = false;
				this.notPublished = notPublished;
				this.failed = !notPublished;
				this.blocks = [];
				this.updatedAt = '';
			}
		},

		// 拉取失败后「重新加载」
		retryLoad() {
			this.load();
		},
		// 正文图片点击放大
		previewImage(src) {
			if (!src) return;
			uni.previewImage({ current: src, urls: [src] });
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
