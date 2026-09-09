<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">成绩查询</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view class="filter-card">
				<view class="filter-row">
					<view class="filter-item">
						<view class="filter-label">学期</view>
						<picker
							mode="selector"
							:range="semesters"
							range-key="semesterName"
							:value="semesterIndex"
							@change="onSemesterChange"
						>
							<view class="filter-value">{{ (semesters[semesterIndex] && semesters[semesterIndex].semesterName) || '请选择' }}</view>
						</picker>
					</view>
				</view>
			</view>

			<view v-if="loading" class="loading-wrapper">
				<view class="loading-text">加载中...</view>
			</view>

			<view v-else-if="grades.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-read.svg" mode="widthFix"></image>
				<view class="empty-text">暂无成绩数据</view>
			</view>

			<view v-else class="grade-list">
				<view class="grade-item" v-for="(item, index) in grades" :key="index">
					<view class="grade-header">
						<view class="grade-name">{{ item.courseName }}</view>
						<view class="grade-score" :class="item.score >= 60 ? 'pass' : 'fail'">{{ item.score }}</view>
					</view>
					<view class="grade-info">
						<view class="grade-info-item">
							<text class="info-label">学分：</text>
							<text class="info-value">{{ item.credit }}</text>
						</view>
						<view class="grade-info-item">
							<text class="info-label">绩点：</text>
							<text class="info-value">{{ item.gpa || '-' }}</text>
						</view>
						<view class="grade-info-item">
							<text class="info-label">类型：</text>
							<text class="info-value">{{ item.courseType || '-' }}</text>
						</view>
					</view>
				</view>

				<view class="summary-card">
					<view class="summary-title">本学期统计</view>
					<view class="summary-row">
						<view class="summary-item">
							<view class="summary-value">{{ summary.totalCourses }}</view>
							<view class="summary-label">课程数</view>
						</view>
						<view class="summary-item">
							<view class="summary-value">{{ summary.earnedCredits }}</view>
							<view class="summary-label">已修学分</view>
						</view>
						<view class="summary-item">
							<view class="summary-value">{{ summary.avgGpa }}</view>
							<view class="summary-label">平均绩点</view>
						</view>
					</view>
				</view>
			</view>

			<view class="ai-analyze-wrap" v-if="!loading && grades.length > 0">
				<view class="ai-analyze-btn" @click="aiAnalyze" :class="{ 'ai-loading': aiLoading }">
					<text v-if="!aiLoading">AI 智能分析成绩</text>
					<text v-else>分析中…</text>
				</view>
			</view>

		</view>
		<view v-if="showAiResult" class="ai-mask" @click="closeAiResult" @touchmove.prevent>
			<view class="ai-modal" :style="{ height: sheetHeight + 'px' }" @click.stop>
				<view class="ai-modal-handle"
					@touchstart="onSheetDragStart"
					@touchmove="onSheetDragMove"
					@touchend="onSheetDragEnd"></view>
				<view class="ai-modal-header">
					<text class="ai-modal-title">AI 智能成绩分析</text>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<scroll-view scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
					</view>
				</scroll-view>
			</view>
		</view>

	</view>
</template>

<script>
/**
 * 成绩查询页面
 * 展示学生成绩信息和统计
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import markdownToHtml from '../../utils/markdown.js';
import { aiScrollLockMixin } from '../../utils/scroll-lock.js';

export default {
	// AI 结果浮层弹出时锁定背景页面滚动，避免页面与浮层同时滚动
	mixins: [aiScrollLockMixin],

	data() {
		return {
			semesters: [],
			semesterIndex: 0,
			currentSemester: '',
			loading: false,
			grades: [],
			summary: {
				totalCourses: 0,
				totalCredits: 0,
				avgGpa: 0,
				earnedCredits: 0
			},
			announcement: null,
			aiLoading: false,
			aiResult: '',
			showAiResult: false,
			aiCache: {},
			windowHeight: 0,
			sheetHeight: 0,
			dragStartY: 0,
			dragStartHeight: 0,
			dragging: false
		};
	},

	watch: {
		showAiResult(val) {
			if (val) {
				this.initSheetHeight();
			}
		}
	},

	onLoad(options) {
		try {
			this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
		} catch (e) {
			this.windowHeight = 0;
		}
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => {
				uni.navigateTo({ url: '/pages/login/index' });
			}, 1000);
			return;
		}

		this.checkFeature().then(canProceed => {
			if (!canProceed) return;
			this.loadSemesters();
		});
	},

	computed: {
		aiResultHtml() {
			return markdownToHtml(this.aiResult);
		}
	},

	methods: {
		async checkFeature() {
			try {
				const result = await feature.checkFeature('grade');
				if (!result.enabled) {
					this.announcement = null;
					feature.showDisabledAlert(result.featureName, result.announcement);
					return false;
				}
				if (result.announcement) {
					this.announcement = result.announcement;
				}
				return true;
			} catch (err) {
				console.error('检查功能开关失败', err);
				return true;
			}
		},

		loadSemesters() {
			this.loading = true;

			api.getSemesterList()
				.then(res => {
					const data = res.data || {};
					const list = data.list || [];
					const def = data.default || null;
					// 默认项展示「学期名称（默认）」，选中时把对应学期 id 传给后端
					const defaultOption = {
						semesterId: def && def.id ? def.id : '',
						semesterName: (def && def.name) ? def.name + '（默认）' : '默认'
					};
					const semesters = [defaultOption].concat(list);

					this.semesters = semesters;
					this.semesterIndex = 0;
					this.currentSemester = defaultOption.semesterId;
					this.loading = false;

					this.loadGrades();
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '获取学期列表失败', icon: 'none' });
				});
		},

		onSemesterChange(e) {
			const index = e.detail.value;
			const semester = this.semesters[index];

			this.semesterIndex = index;
			this.currentSemester = semester.semesterId;

			this.loadGrades();
		},

		loadGrades() {
			const { currentSemester } = this;

			this.loading = true;

			api.queryGrade(currentSemester)
				.then(res => {
					const rawData = res.data || [];
					let grades = [];
					let summary = {
						totalCourses: 0,
						totalCredits: 0,
						avgGpa: 0,
						earnedCredits: 0
					};

					if (rawData.length > 0 && rawData[0].achievement && rawData[0].achievement.length > 0) {
						grades = rawData[0].achievement.map(item => ({
							courseName: item.courseName || '',
							score: item.fraction || '-',
							credit: item.credit || 0,
							gpa: item.gpa !== undefined ? item.gpa : this.calculateGpa(parseFloat(item.fraction)),
							courseType: item.curriculumAttributes || ''
						}));

						summary = {
							totalCourses: grades.length,
							totalCredits: grades.reduce((sum, g) => sum + (parseFloat(g.credit) || 0), 0).toFixed(1),
							avgGpa: rawData[0].pjxfjd || '-',
							earnedCredits: rawData[0].yxzxf || '-'
						};
					}

					this.grades = grades;
					this.summary = summary;
					this.loading = false;
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
				});
		},

		calculateGpa(score) {
			if (isNaN(score)) return '-';

			if (score >= 90) return '4.0';
			if (score >= 85) return '3.7';
			if (score >= 82) return '3.3';
			if (score >= 78) return '3.0';
			if (score >= 75) return '2.7';
			if (score >= 72) return '2.3';
			if (score >= 68) return '2.0';
			if (score >= 64) return '1.5';
			if (score >= 60) return '1.0';
			return '0';
			},

			async aiAnalyze() {
			if (this.aiLoading) return;
			if (this.grades.length === 0) {
				uni.showToast({ title: '暂无成绩数据', icon: 'none' });
				return;
			}
			const semesterName = (this.semesters[this.semesterIndex] && this.semesters[this.semesterIndex].semesterName) || '';
			// 页面内已缓存，直接展示，不重新生成、不发请求
			if (this.aiCache[semesterName]) {
				this.aiResult = this.aiCache[semesterName];
				this.showAiResult = true;
				return;
			}
			this.aiLoading = true;
			const payload = {
				achievement: this.grades.map(g => ({
					courseName: g.courseName,
					fraction: g.score,
					credit: g.credit,
					gpa: g.gpa,
					curriculumAttributes: g.courseType
				})),
				gpa: this.summary.avgGpa,
				credit: this.summary.earnedCredits,
				semester: semesterName
			};
			try {
				const res = await api.aiAnalyzeGrades(payload);
				const content = (res.data && res.data.content) ? String(res.data.content).trim() : '';
				if (!content) {
					uni.showToast({ title: '暂无分析结果，请重试', icon: 'none' });
					return;
				}
				this.aiCache[semesterName] = content;
				this.aiResult = content;
				this.showAiResult = true;
			} catch (err) {
				uni.showToast({ title: (err && err.msg) || '分析失败', icon: 'none' });
			} finally {
				this.aiLoading = false;
			}
		},

		closeAiResult() {
			this.showAiResult = false;
		},

		initSheetHeight() {
			if (!this.windowHeight) {
				try {
					this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
				} catch (e) {
					this.windowHeight = 0;
				}
			}
			this.sheetHeight = Math.round(this.windowHeight * 0.8);
		},

		onSheetDragStart(e) {
			this.dragging = true;
			this.dragStartY = e.touches[0].clientY;
			this.dragStartHeight = this.sheetHeight;
		},

		onSheetDragMove(e) {
			if (!this.dragging) return;
			const deltaY = e.touches[0].clientY - this.dragStartY;
			let newH = this.dragStartHeight - deltaY;
			const maxH = this.windowHeight || 99999;
			const minH = Math.round((this.windowHeight || 0) * 0.25);
			if (newH > maxH) newH = maxH;
			if (newH < minH) newH = minH;
			this.sheetHeight = newH;
		},

		onSheetDragEnd() {
			this.dragging = false;
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

.filter-card {
	background: white;
	border-radius: 20rpx;
	padding: 24rpx 30rpx;
	margin-top: 25rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.filter-row {
	display: flex;
	align-items: center;
	gap: 20rpx;
}

.filter-item {
	flex: 1;
}

.filter-label {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 8rpx;
}

.filter-value {
	font-size: 28rpx;
	color: #48416f;
	background: #f7f8fc;
	padding: 16rpx 20rpx;
	border-radius: 12rpx;
}

.query-btn {
	background: #13c2c2;
	color: white;
	font-size: 28rpx;
	padding: 0 30rpx;
	height: 72rpx;
	line-height: 72rpx;
	border-radius: 12rpx;
	margin: 0;
}

.loading-wrapper {
	display: flex;
	justify-content: center;
	padding: 100rpx 0;
}

.loading-text {
	font-size: 28rpx;
	color: #948eb3;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 100rpx 0;
}

.empty-icon {
	width: 120rpx;
	height: 120rpx;
	opacity: 0.5;
	margin-bottom: 20rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}

.grade-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.grade-item {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.grade-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20rpx;
}

.grade-name {
	font-size: 30rpx;
	color: #48416f;
	font-weight: bold;
	flex: 1;
	margin-right: 20rpx;
}

.grade-score {
	font-size: 36rpx;
	font-weight: bold;
	padding: 8rpx 24rpx;
	border-radius: 12rpx;
}

.grade-score.pass {
	background: #e8f9f9;
	color: #13c2c2;
}

.grade-score.fail {
	background: #fff0f0;
	color: #ff7b7b;
}

.grade-info {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.grade-info-item {
	font-size: 24rpx;
}

.info-label {
	color: #948eb3;
}

.info-value {
	color: #48416f;
}

.summary-card {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	margin-top: 20rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.summary-title {
	font-size: 28rpx;
	color: #48416f;
	font-weight: bold;
	margin-bottom: 24rpx;
	text-align: center;
}

.summary-row {
	display: flex;
	justify-content: space-around;
}

.summary-item {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.summary-value {
	font-size: 40rpx;
	color: #13c2c2;
	font-weight: bold;
}

.summary-label {
	font-size: 24rpx;
	color: #948eb3;
	margin-top: 8rpx;
}

.ai-analyze-wrap {
	display: flex;
	justify-content: center;
	margin-top: 30rpx;
}

.ai-analyze-btn {
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	color: white;
	font-size: 28rpx;
	padding: 20rpx 60rpx;
	border-radius: 40rpx;
	box-shadow: 0 10rpx 30rpx rgba(19, 194, 194, 0.3);
}

.ai-analyze-btn.ai-loading {
	opacity: 0.7;
}

.ai-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.55);
	z-index: 999;
	display: flex;
	align-items: flex-end;
	justify-content: center;
	animation: aiMaskFade 0.25s ease;
}

@keyframes aiMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.ai-modal {
	width: 100%;
	background: #fff;
	border-radius: 32rpx 32rpx 0 0;
	display: flex;
	flex-direction: column;
	overflow: hidden;
	box-shadow: 0 -8rpx 40rpx rgba(0, 0, 0, 0.18);
	animation: aiModalUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes aiModalUp {
	from { transform: translateY(100%); }
	to { transform: translateY(0); }
}

.ai-modal-handle {
	width: 72rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #d8d4e8;
	margin: 18rpx auto 2rpx;
	transition: background 0.2s ease;
}

.ai-modal-handle:active {
	background: #b9b2d6;
}

.ai-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 14rpx 44rpx 22rpx;
	border-bottom: 1rpx solid #f0f0f4;
}

.ai-modal-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #322c54;
}

.ai-modal-close {
	width: 56rpx;
	height: 56rpx;
	line-height: 56rpx;
	text-align: center;
	font-size: 30rpx;
	color: #948eb3;
	background: #f3f2f8;
	border-radius: 50%;
}

.ai-modal-body {
	flex: 1;
	min-height: 0;
}

.ai-modal-body-inner {
	padding: 30rpx 44rpx;
}

.ai-modal-content {
	display: block;
	font-size: 28rpx;
	color: #48416f;
	line-height: 1.75;
	word-break: break-word;
}
</style>
