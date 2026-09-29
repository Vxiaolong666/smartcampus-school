<template>
	<view class="fb-page">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">意见反馈</view>
		</cb-nav-bar>

		<view class="fb-bg-wrapper">
			<image class="fb-bg" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="fb-bg-mask"></view>
		</view>

		<view class="fb-content">
			<!-- 未登录：反馈需要账号，先引导登录 -->
			<view v-if="!isLoggedIn" class="fb-card">
				<view class="fb-empty">
					<image class="fb-empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
					<view class="fb-empty-title">登录后即可提交反馈</view>
					<view class="fb-empty-desc">登录后可提交意见反馈，并随时查看处理进度与历史记录。</view>
				</view>
				<view class="fb-btn primary" @click="goLogin">去登录</view>
			</view>

			<template v-else>
				<!-- 提交反馈 -->
				<view class="fb-card">
					<view class="fb-card-title">提交反馈</view>
					<view class="fb-card-desc" v-if="formOpen">使用中遇到问题或有建议，都可以在这里反馈；提交后按「待处理 → 处理中 → 已解决 / 已驳回 / 已忽略」流转。</view>
					<view class="fb-card-desc" v-else>留言功能暂未开放，可通过「我的 → 联系客服」与我们联系。</view>

					<template v-if="formOpen">
						<view class="fb-field">
							<view class="fb-label">主题</view>
							<input
								class="fb-input"
								type="text"
								:value="subject"
								maxlength="100"
								placeholder="如：电费查询异常"
								placeholder-class="fb-ph"
								@input="onSubjectInput"
							/>
						</view>
						<view class="fb-field">
							<view class="fb-label">反馈内容 <text class="fb-required">*</text></view>
							<textarea
								class="fb-textarea"
								:value="content"
								maxlength="2000"
								placeholder="请描述你遇到的问题或建议（最多 2000 字）"
								placeholder-class="fb-ph"
								@input="onContentInput"
							></textarea>
						</view>
						<view class="fb-tip">联系方式默认使用账号信息{{ contactSummary ? '（' + contactSummary + '）' : '' }}，如需修改请在「个人资料」中更新。</view>
						<button class="fb-btn primary" :disabled="submitting" :loading="submitting" @click="onSubmit">提交反馈</button>
					</template>
				</view>

				<!-- 我的反馈记录 -->
				<view class="fb-card">
					<view class="fb-card-head">
						<view class="fb-card-title">我的反馈</view>
						<view class="fb-count">共 {{ total }} 条</view>
					</view>

					<view v-if="list.length === 0 && !loading" class="fb-empty">
						<image class="fb-empty-icon" src="/static/assets/wxmp-empty.svg" mode="widthFix"></image>
						<view class="fb-empty-title">还没有提交过反馈</view>
						<view class="fb-empty-desc">提交后可在这里查看处理状态与处理备注。</view>
					</view>

					<view v-for="item in list" :key="item.id" class="fb-item" @click="openDetail(item)">
						<view class="fb-item-head">
							<view class="fb-item-title">#{{ item.id }} {{ item.subject || '（无主题）' }}</view>
							<view class="fb-badge" :class="statusClass(item.status)">{{ item.status_text }}</view>
						</view>
						<view class="fb-item-body">{{ item.content }}</view>
						<view v-if="item.remark" class="fb-item-remark">
							<text class="fb-item-remark-label">处理备注</text>
							<text class="fb-item-remark-text">{{ item.remark }}</text>
						</view>
						<view class="fb-item-foot">
							<text>提交于 {{ item.create_time }}</text>
							<text v-if="item.update_time">更新于 {{ item.update_time }}</text>
						</view>
						<view class="fb-item-more">查看详情 ›</view>
					</view>

					<view v-if="loading" class="fb-loading">加载中…</view>
					<view v-else-if="hasMore" class="fb-more" @click="loadMore">点击加载更多</view>
					<view v-else-if="list.length" class="fb-no-more">没有更多了</view>
				</view>
			</template>
		</view>

		<!-- 反馈详情弹窗 -->
		<view v-if="detailShow && detail" class="fb-modal-mask" @click="closeDetail">
			<view class="fb-modal" @click.stop>
				<view class="fb-modal-head">
					<view class="fb-modal-title">反馈详情</view>
					<view class="fb-modal-close" @click="closeDetail">×</view>
				</view>
				<scroll-view scroll-y class="fb-modal-body" style="max-height: 60vh;">
					<view class="fb-modal-top">
						<view class="fb-modal-subject">#{{ detail.id }} {{ detail.subject || '（无主题）' }}</view>
						<view class="fb-badge" :class="statusClass(detail.status)">{{ detail.status_text }}</view>
					</view>
					<view class="fb-modal-block">
						<view class="fb-modal-label">反馈内容</view>
						<view class="fb-modal-content">{{ detail.content }}</view>
					</view>
					<view v-if="detail.remark" class="fb-modal-block">
						<view class="fb-modal-label">处理备注</view>
						<view class="fb-modal-remark">{{ detail.remark }}</view>
					</view>
					<view class="fb-modal-row">
						<text class="fb-modal-label">提交时间</text>
						<text class="fb-modal-value">{{ detail.create_time }}</text>
					</view>
					<view v-if="detail.update_time" class="fb-modal-row">
						<text class="fb-modal-label">更新时间</text>
						<text class="fb-modal-value">{{ detail.update_time }}</text>
					</view>
				</scroll-view>
				<view class="fb-modal-foot">
					<view class="fb-modal-btn plain" @click="closeDetail">关闭</view>
					<view class="fb-modal-btn" @click="onContact">联系客服</view>
				</view>
			</view>
		</view>

		<!-- 联系客服弹窗（与协议中心 / 我的页共用 contact-modal） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
/**
 * 意见反馈
 * ------------------------------------------------------------------
 * 提交反馈（act=submit_feedback）与查看自己的反馈记录（act=get_my_feedbacks）。
 * 处理状态：0 待处理、1 处理中、2 已解决、3 已驳回、4 已忽略；
 * 管理员填写的处理备注（remark）会在此展示，列表只显示摘要，点击卡片弹窗查看完整详情，
 * 详情弹窗底部可「联系客服」（复用项目统一的 contact-modal）。
 * 联系人信息由服务端自动取当前账号，客户端只提交主题与内容。
 * 头部结构与「协议中心」保持一致（fb- 前缀样式统一在 App.vue 全局定义）。
 */
import api from '../../utils/api.js';

export default {
	data() {
		return {
			isLoggedIn: false,
			formOpen: true,
			subject: '',
			content: '',
			contactSummary: '',
			submitting: false,
			loading: false,
			list: [],
			total: 0,
			page: 1,
			size: 10,
			hasMore: false,
			detail: null,
			detailShow: false,
			contactShow: false,
			contactLines: []
		};
	},
	onLoad() {
		this.isLoggedIn = api.isLoggedIn();
		if (!this.isLoggedIn) {
			// 与其它二级页面一致：未登录先说明用途再跳登录页
			api.goLoginWithTip();
			return;
		}
		this.fillContactSummary();
		this.loadList(true);
	},
	methods: {
		goLogin() {
			api.goLoginWithTip();
		},
		// 展示当前账号用于反馈的联系方式（服务端也会按账号自动补全）
		fillContactSummary() {
			const info = (typeof api.getUserInfoCache === 'function' ? api.getUserInfoCache() : null) || {};
			const parts = [];
			const name = info.nickname || info.username || '';
			if (name) parts.push(name);
			if (info.email) parts.push(info.email);
			this.contactSummary = parts.join(' / ');
		},
		onSubjectInput(e) {
			this.subject = e.detail.value;
		},
		onContentInput(e) {
			this.content = e.detail.value;
		},
		// 处理状态 → 徽章配色
		statusClass(status) {
			const s = Number(status);
			if (s === 1) return 'fb-badge-doing';
			if (s === 2) return 'fb-badge-done';
			if (s === 3) return 'fb-badge-reject';
			if (s === 4) return 'fb-badge-ignore';
			return 'fb-badge-pending';
		},
		// 查看单条反馈详情
		openDetail(item) {
			if (!item) return;
			this.detail = item;
			this.detailShow = true;
		},
		closeDetail() {
			this.detailShow = false;
		},
		// 联系客服：弹窗展示后台「首页配置」中维护的联系方式（与协议中心页一致）
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
		},
		loadList(reset) {
			if (this.loading) return;
			if (reset) {
				this.page = 1;
				this.hasMore = false;
			}
			this.loading = true;
			api.getMyFeedbacks({ page: this.page, size: this.size })
				.then((res) => {
					const data = (res && res.data) || {};
					const rows = Array.isArray(data.list) ? data.list : [];
					this.list = reset ? rows : this.list.concat(rows);
					this.total = Number(data.total) || 0;
					this.hasMore = !!data.has_more;
					if (data.form_open !== undefined) {
						this.formOpen = Number(data.form_open) === 1;
					}
					this.loading = false;
				})
				.catch((err) => {
					this.loading = false;
					uni.showToast({ title: (err && err.msg) || '记录加载失败', icon: 'none' });
				});
		},
		loadMore() {
			if (this.loading || !this.hasMore) return;
			this.page += 1;
			this.loadList(false);
		},
		onSubmit() {
			const content = (this.content || '').trim();
			if (!content) {
				uni.showToast({ title: '请填写反馈内容', icon: 'none' });
				return;
			}
			if (this.submitting) return;
			this.submitting = true;
			uni.showLoading({ title: '提交中...' });
			api.submitFeedback({ subject: (this.subject || '').trim(), content: content })
				.then((res) => {
					uni.hideLoading();
					this.submitting = false;
					this.subject = '';
					this.content = '';
					uni.showToast({ title: (res && res.msg) || '提交成功', icon: 'success' });
					this.loadList(true);
				})
				.catch((err) => {
					uni.hideLoading();
					this.submitting = false;
					uni.showToast({ title: (err && err.msg) || '提交失败，请稍后重试', icon: 'none' });
				});
		}
	}
};
</script>
