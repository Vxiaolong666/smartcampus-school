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

			<view class="filter-card" v-if="!needBind && (semesters.length > 0 || !loading)">
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

			<!-- 本学期统计（学期筛选下方） -->
			<view class="summary-card" v-if="!loading && grades.length > 0">
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

			<cb-loading v-if="loading" />

			<cb-bind-tip v-else-if="needBind" type="academic" desc="绑定后可查询成绩" />

			<view v-else-if="grades.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-read.svg" mode="widthFix"></image>
				<view class="empty-text">暂无成绩数据</view>
			</view>

			<view v-else class="grade-list">
				<view class="grade-item" v-for="(item, index) in grades" :key="index" @click="openDetail(item)">
					<view class="grade-header">
						<view class="grade-name">{{ item.courseName }}</view>
						<view class="grade-score" :class="scoreClass(item.score)">{{ item.score }}</view>
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

		<!-- 右侧浮动 AI 分析按钮（圆形） -->
		<view v-if="!loading && !needBind && grades.length > 0" class="ai-fab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
			<text class="ai-fab-icon">AI</text>
			<text class="ai-fab-label">分析</text>
		</view>

		<!-- 成绩详情自定义弹窗 -->
		<view class="detail-mask" v-if="showDetail" @click="closeDetail">
			<view class="detail-modal" @click.stop>
				<view class="detail-modal-header" :style="'background: linear-gradient(160deg, ' + detailTint.bg + ' 0%, #ffffff 100%);'">
					<view class="detail-title-wrap">
						<view class="detail-title" :style="'color: ' + detailTint.text + ';'">{{ detailItem.courseName }}</view>
						<view class="detail-sub">
							<text class="detail-sub-chip" :style="'background-color: ' + detailTint.text + ';'">{{ detailItem.score }} 分</text>
						</view>
					</view>
					<view class="detail-close" @click="closeDetail">✕</view>
				</view>

				<view class="detail-modal-body">
					<view class="detail-grid">
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.credit }}</view>
							<view class="detail-grid-label">学分</view>
						</view>
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.gpa || '-' }}</view>
							<view class="detail-grid-label">绩点</view>
						</view>
						<view class="detail-grid-item">
							<view class="detail-grid-num" :style="'color: ' + detailTint.text + ';'">{{ detailItem.courseType || '-' }}</view>
							<view class="detail-grid-label">课程类型</view>
						</view>
					</view>

					<button class="detail-btn" @click="closeDetail" :style="'background: linear-gradient(90deg, ' + detailTint.text + ', ' + detailTint.light + '); box-shadow: 0 10rpx 24rpx ' + detailTint.shadow + ';'">我知道了</button>
				</view>
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
			loading: true,
			needBind: false,
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
			dragging: false,
			// 成绩详情自定义弹窗
			showDetail: false,
			detailItem: {},
			detailTint: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
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
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip();
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
			this.needBind = false;

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
					if (feature.isAcademicNotBound(err)) {
						this.needBind = true;
						return;
					}
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

		// 打开成绩详情（自定义弹窗，与课程详情风格一致）
		openDetail(item) {
			if (!item) return;
			const tints = {
				fail: { text: '#ff4d4f', bg: '#fff0f0', light: '#ff7875', shadow: 'rgba(255, 77, 79, 0.3)' },
				excellent: { text: '#52c41a', bg: '#f6ffed', light: '#95de64', shadow: 'rgba(82, 196, 26, 0.3)' },
				normal: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
			};
			this.detailTint = tints[this.scoreClass(item.score)] || tints.normal;
			this.detailItem = item;
			this.showDetail = true;
		},

		// 关闭成绩详情
		closeDetail() {
			this.showDetail = false;
			this.detailItem = {};
		},

		// 跳转教务账号绑定页
		goToBind() {
			uni.navigateTo({ url: '/pages/academic-bind/index' });
		},

		// 成绩颜色：60 以下红；85 及以上绿；其它（含非数字）蓝（主题色）
		scoreClass(score) {
			const n = parseFloat(score);
			if (!isNaN(n)) {
				if (n < 60) return 'fail';
				if (n >= 85) return 'excellent';
			}
			return 'normal';
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
			if (this.aiLoading) {
			// 正在分析中允许再次点击：重新聚焦分析弹窗并提示，不重复发起请求
			this.aiResult = '### AI 正在分析…\n\n正在生成分析报告，请稍候......\n（该过程通常需要十几秒）';
			this.showAiResult = true;
			uni.showToast({ title: 'AI 正在分析...', icon: 'none' });
			return;
		}
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

/* 60 以下：红色 */
.grade-score.fail {
	background: #fff0f0;
	color: #ff4d4f;
}

/* 85 及以上：绿色 */
.grade-score.excellent {
	background: #f6ffed;
	color: #52c41a;
}

/* 其它（60~85 及非数字）：蓝色主题色 */
.grade-score.normal {
	background: #e8f9f9;
	color: #13c2c2;
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
	margin-bottom: 24rpx;
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

/* AI 分析按钮与浮动按钮样式已统一到 App.vue 全局（.ai-analyze-wrap / .ai-analyze-btn / .ai-fab） */

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

/* ==================== 成绩详情自定义弹窗 ==================== */

.grade-item:active {
	opacity: 0.92;
}

.detail-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(50, 44, 84, 0.45);
	backdrop-filter: blur(4px);
	-webkit-backdrop-filter: blur(4px);
	z-index: 998;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 60rpx;
	animation: detailMaskFade 0.25s ease;
}

@keyframes detailMaskFade {
	from { opacity: 0; }
	to { opacity: 1; }
}

.detail-modal {
	width: 100%;
	max-width: 620rpx;
	background: white;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 24rpx 80rpx rgba(50, 44, 84, 0.25);
	animation: detailModalUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes detailModalUp {
	from { transform: translateY(80rpx) scale(0.96); opacity: 0; }
	to { transform: translateY(0) scale(1); opacity: 1; }
}

.detail-modal-header {
	display: flex;
	align-items: flex-start;
	padding: 36rpx 32rpx 28rpx;
}

.detail-title-wrap {
	flex: 1;
	min-width: 0;
}

.detail-title {
	font-size: 36rpx;
	font-weight: bold;
	line-height: 1.35;
	word-break: break-all;
	letter-spacing: 1rpx;
}

.detail-sub {
	margin-top: 16rpx;
}

.detail-sub-chip {
	display: inline-block;
	font-size: 22rpx;
	color: white;
	padding: 8rpx 22rpx;
	border-radius: 999rpx;
	letter-spacing: 1rpx;
}

.detail-close {
	width: 52rpx;
	height: 52rpx;
	line-height: 50rpx;
	text-align: center;
	font-size: 26rpx;
	color: #948eb3;
	background: rgba(255, 255, 255, 0.85);
	border-radius: 50%;
	flex-shrink: 0;
	margin-left: 16rpx;
	box-shadow: 0 4rpx 12rpx rgba(50, 44, 84, 0.08);
}

.detail-close:active {
	background: #f0eef6;
	transform: scale(0.92);
}

.detail-modal-body {
	padding: 8rpx 36rpx 40rpx;
}

.detail-grid {
	display: flex;
	gap: 20rpx;
	margin-top: 12rpx;
}

.detail-grid-item {
	flex: 1;
	background: #f7f8fc;
	border-radius: 20rpx;
	padding: 24rpx 20rpx;
	text-align: center;
}

.detail-grid-num {
	font-size: 30rpx;
	font-weight: bold;
	color: #13c2c2;
	word-break: break-all;
}

.detail-grid-label {
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 8rpx;
}

.detail-btn {
	margin-top: 36rpx;
	height: 84rpx;
	line-height: 84rpx;
	border-radius: 42rpx;
	color: white;
	font-size: 30rpx;
	font-weight: bold;
	letter-spacing: 4rpx;
	padding: 0;
}

.detail-btn:active {
	opacity: 0.85;
	transform: scale(0.98);
}

.detail-btn::after {
	border: none;
}
</style>
