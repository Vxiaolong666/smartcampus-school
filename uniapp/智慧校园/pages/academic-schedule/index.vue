<template>
	<view class="container" :class="{ 'ai-open': showAiResult }">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">课程表</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view class="filter-card" v-if="!needBind && (modes.length > 0 || !loading)">
				<view class="filter-row">
					<view class="filter-item">
						<view class="filter-label">类别</view>
						<picker
							mode="selector"
							:range="modes"
							range-key="kbjcmsmc"
							:value="modeIndex"
							@change="onModeChange"
						>
							<view class="filter-value">{{ (modes[modeIndex] && modes[modeIndex].kbjcmsmc) || '请选择' }}</view>
						</picker>
					</view>
					<view class="filter-item">
						<view class="filter-label">周次</view>
						<picker mode="selector" :range="weeks" :value="weekIndex" @change="onWeekChange">
							<view class="filter-value">{{ weeks[weekIndex] }}</view>
						</picker>
					</view>
					<button class="query-btn" @click="onQuery" :loading="loading">查询</button>
				</view>
			</view>

			<cb-load-fail />
			<cb-loading v-if="loading" />

			<cb-bind-tip v-else-if="needBind" type="academic" desc="绑定后可查看本周课程表安排" />

			<view v-else-if="schedule.length === 0" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-book-gray.svg" mode="widthFix"></image>
				<view class="empty-text">暂无课程数据</view>
			</view>

			<view v-else class="schedule-wrapper">
				<view class="schedule-header">
					<view class="header-item">节次</view>
					<view class="header-item" v-for="(day, i) in weekHeaders" :key="i">
						<view>{{ day }}</view>
						<view style="font-size:16rpx;font-weight:normal;opacity:0.85;margin-top:2rpx;">{{ weekDates[i] }}</view>
					</view>
				</view>

				<view class="schedule-body">
					<view class="schedule-row" v-for="(row, rowIndex) in schedule" :key="rowIndex">
						<view class="row-label" style="flex-direction: column;">
						<view>第{{ rowIndex + 1 }}节</view>
						<view style="font-size:18rpx;color:#948eb3;margin-top:2rpx;">{{ sectionTime(rowIndex) }}</view>
					</view>
						<view class="row-cells">
							<view
								class="cell"
								:class="{ 'has-course': cell.hasCourse }"
								v-for="(cell, dayIndex) in row"
								:key="dayIndex"
								@click="onCourseTap"
								:data-day="dayIndex"
								:data-section="rowIndex"
								:style="cell.hasCourse && cell.color ? 'background-color: ' + cell.color.bg + '; border-left: 4rpx solid ' + cell.color.border : ''"
							>
								<block v-if="cell.hasCourse">
									<view class="course-name" :style="cell.color ? 'color: ' + cell.color.text : ''">{{ cell.courseName }}</view>
									<view class="course-info">{{ cell.location }}</view>
								</block>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view class="course-list" v-if="!loading && courses.length > 0">
				<view class="list-title">本周课程详情</view>
				<view
					class="course-item"
					v-for="(item, index) in courses"
					:key="index"
					:data-index="index"
					@click="onCourseItemTap"
				>
					<view class="course-header">
						<view class="course-name">
							<view class="course-color-dot" :style="'background-color: ' + item.color.text + ';'"></view>
							{{ item.courseName }}
						</view>
						<view class="course-time" :style="'background-color: ' + item.color.bg + '; color: ' + item.color.text + ';'">
							{{ item.weekDay }} 第{{ item.section }}节
						</view>
					</view>
					<view class="course-detail">
						<view class="detail-item">
							<text class="detail-label">地点：</text>
							<text class="detail-value">{{ item.location || '待定' }}</text>
						</view>
						<view class="detail-item">
							<text class="detail-label">教师：</text>
							<text class="detail-value">{{ item.teacher || '待定' }}</text>
						</view>
						<view class="detail-item">
							<text class="detail-label">周次：</text>
							<text class="detail-value">{{ item.weeks || '-' }}</text>
						</view>
					</view>
				</view>
			</view>

			<view v-if="!loading && courses.length > 0" class="ai-analyze-wrap">
				<view class="ai-analyze-btn" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
					<text v-if="!aiLoading">AI 智能分析课表</text>
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
					<view class="ai-modal-title-wrap">
						<text class="ai-modal-badge">AI</text>
						<text class="ai-modal-title">智能课表分析</text>
					</view>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<!-- 正在分析：复用全站统一的圆环 loading，垂直居中 -->
				<view v-if="aiLoading" class="ai-modal-center-body">
					<cb-loading
						class="ai-modal-center-item"
						compact
						large
						text="AI 正在分析…"
						:sub-texts="['读取课表数据，约需十几秒', '正在梳理课程与节次…', '正在撰写课表分析…', '马上就好，请稍候…']"
						:slow-after="16000"
						slow-text="分析时间有点久…"
						slow-sub-text="可稍候，或关闭后重新分析"
					/>
				</view>
				<view v-else-if="aiError" class="ai-modal-center-body">
					<!-- 分析失败：复用全站统一的网络异常样式，垂直居中 -->
					<view class="ai-error ai-modal-center-item">
						<image class="ai-error-icon" src="/static/assets/wxmp-network-error.svg" mode="widthFix"></image>
						<view class="ai-error-title">分析失败</view>
						<view class="ai-error-desc">{{ aiError }}</view>
						<view class="ai-error-btn" @click="aiAnalyze">重新分析</view>
					</view>
				</view>
				<scroll-view v-else scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<!-- 分析结果 -->
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
						<view class="ai-result-foot">内容由 AI 生成，仅供参考</view>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 右侧浮动 AI 分析按钮（圆形） -->
		<view v-if="!loading && !needBind && courses.length > 0" class="ai-fab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
			<text class="ai-fab-icon">AI</text>
			<text class="ai-fab-label">分析</text>
		</view>

		<!-- 课程详情自定义弹窗 -->
		<view class="detail-mask" v-if="showDetail" @click="closeDetail">
			<view class="detail-modal" @click.stop>
				<view class="detail-modal-header" :style="'background: linear-gradient(160deg, ' + detailCourse.color.bg + ' 0%, #ffffff 100%);'">
					<view class="detail-title-wrap">
						<view class="detail-title" :style="'color: ' + detailCourse.color.text + ';'">{{ detailCourse.courseName }}</view>
						<view class="detail-sub">
							<text class="detail-sub-chip" :style="'background-color: ' + detailCourse.color.text + ';'">{{ detailCourse.weekDay }} · 第{{ detailCourse.section }}节</text>
						</view>
					</view>
					<view class="detail-close" @click="closeDetail">✕</view>
				</view>

				<view class="detail-modal-body">
					<view class="detail-row" v-if="detailCourse.startTime">
						<view class="detail-row-icon" :style="'background-color: ' + detailCourse.color.bg + ';'">
							<image class="detail-row-icon-img" :src="detailIcons.time" mode="aspectFit"></image>
						</view>
						<text class="detail-row-label">上课时段</text>
						<text class="detail-row-value">{{ detailCourse.startTime }} - {{ detailCourse.endTIme }}</text>
					</view>
					<view class="detail-row">
						<view class="detail-row-icon" :style="'background-color: ' + detailCourse.color.bg + ';'">
							<image class="detail-row-icon-img" :src="detailIcons.location" mode="aspectFit"></image>
						</view>
						<text class="detail-row-label">上课地点</text>
						<text class="detail-row-value">{{ detailCourse.location || '待定' }}</text>
					</view>
					<view class="detail-row">
						<view class="detail-row-icon" :style="'background-color: ' + detailCourse.color.bg + ';'">
							<image class="detail-row-icon-img" :src="detailIcons.user" mode="aspectFit"></image>
						</view>
						<text class="detail-row-label">授课教师</text>
						<text class="detail-row-value">{{ detailCourse.teacher || '待定' }}</text>
					</view>
					<view class="detail-row">
						<view class="detail-row-icon" :style="'background-color: ' + detailCourse.color.bg + ';'">
							<image class="detail-row-icon-img" :src="detailIcons.calendar" mode="aspectFit"></image>
						</view>
						<text class="detail-row-label">上课周次</text>
						<text class="detail-row-value">{{ detailCourse.weeks || '-' }}</text>
					</view>

					<view class="detail-grid" v-if="detailCourse.credit || detailCourse.courseNature">
						<view class="detail-grid-item" v-if="detailCourse.credit">
							<view class="detail-grid-num">{{ detailCourse.credit }}</view>
							<view class="detail-grid-label">学分</view>
						</view>
						<view class="detail-grid-item" v-if="detailCourse.courseNature">
							<view class="detail-grid-num">{{ detailCourse.courseNature }}</view>
							<view class="detail-grid-label">课程性质</view>
						</view>
					</view>

					<button class="detail-btn" @click="closeDetail" :style="'background: linear-gradient(90deg, ' + detailCourse.color.text + ', ' + detailBtnBgLight + '); box-shadow: 0 10rpx 24rpx ' + detailBtnShadow + ';'">我知道了</button>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 课程表页面
 * 展示学生课程表信息
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import { getCourseColor, weekDays, timeSlots } from '../../utils/common.js';
import { loadScheduleTimes as fetchScheduleTimes } from '../../utils/schedule-time.js';
import markdownToHtml from '../../utils/markdown.js';
import { aiScrollLockMixin } from '../../utils/scroll-lock.js';

// 课程详情弹窗图标 path 数据（与小程序端一致，fill 颜色按课程色动态生成）
const ICON_CLOCK_PATHS = [
	'M512 96C282.2 96 96 282.2 96 512s186.2 416 416 416 416-186.2 416-416S741.8 96 512 96z m0 752c-185.6 0-336-150.4-336-336S326.4 176 512 176s336 150.4 336 336-150.4 336-336 336z',
	'M512 248c-4.4 0-8 3.6-8 8v256c0 4.4 3.6 8 8 8h200c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H568V256c0-4.4-3.6-8-8-8h-48z'
];
const ICON_LOCATION_PATHS = [
	'M512 64C323.5 64 168 219.5 168 408c0 193.5 184.5 412.5 302.5 540.5 23 25 62 25 85 0C673.5 820.5 858 601.5 858 408 858 219.5 700.5 64 512 64z m0 448c-70.7 0-128-57.3-128-128s57.3-128 128-128 128 57.3 128 128-57.3 128-128 128z'
];
const ICON_USER_PATHS = [
	'M512 512c123.8 0 224-100.2 224-224S635.8 64 512 64 288 164.2 288 288s100.2 224 224 224z m0-352c70.7 0 128 57.3 128 128s-57.3 128-128 128-128-57.3-128-128 57.3-128 128-128z',
	'M858.5 764.4C781.8 656.9 654.5 592 512 592s-269.8 64.9-346.5 172.4c-10.3 14.8-6.1 35.2 8.7 45.5 14.8 10.3 35.2 6.1 45.5-8.7C281.6 714.3 389.8 664 512 664s230.4 50.3 292.3 137.2c6.3 9.1 16.4 14 26.6 14 6.4 0 12.9-1.9 18.6-5.9 14.8-10.3 18.9-30.7 8.6-45.5z'
];
const ICON_CALENDAR_PATHS = [
	'M880 184H712v-64c0-4.4-3.6-8-8-8h-56c-4.4 0-8 3.6-8 8v64H384v-64c0-4.4-3.6-8-8-8h-56c-4.4 0-8 3.6-8 8v64H144c-17.7 0-32 14.3-32 32v664c0 17.7 14.3 32 32 32h736c17.7 0 32-14.3 32-32V216c0-17.7-14.3-32-32-32z m-40 600H184V272h128v48c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8v-48h256v48c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8v-48h128v512z',
	'M424 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
	'M552 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
	'M680 432h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
	'M424 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
	'M552 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z',
	'M680 560h-80c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h80c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8z'
];

export default {
	// AI 结果浮层弹出时锁定背景页面滚动，避免页面与浮层同时滚动
	mixins: [aiScrollLockMixin],

	data() {
		return {
			featureEnabled: true,
			announcement: null,
			modes: [],
			modeIndex: 0,
			currentMode: '',
			weeks: [
				'当前周',
				'第1周',
				'第2周',
				'第3周',
				'第4周',
				'第5周',
				'第6周',
				'第7周',
				'第8周',
				'第9周',
				'第10周',
				'第11周',
				'第12周',
				'第13周',
				'第14周',
				'第15周',
				'第16周',
				'第17周',
				'第18周',
				'第19周',
				'第20周'
			],
			weekIndex: 0,
			loading: true,
			needBind: false,
			schedule: [],
			courses: [],
			currentWeek: 1,
			weekDates: [],
			weekHeaders: weekDays.slice(1),
			courseColors: {},
			// 节次时间（后台「课程时间配置」，索引即节次：1=第1节）
			sectionTimes: [],
			// 课程详情自定义弹窗
			showDetail: false,
			detailCourse: null,
			detailIcons: null,
			detailBtnBgLight: '#36cfc9',
			detailBtnShadow: 'rgba(19, 194, 194, 0.35)',
			showAiResult: false,
			aiResult: '',
			aiError: '',
			aiLoading: false,
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

	computed: {
		aiResultHtml() {
			return markdownToHtml(this.aiResult);
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
			if (canProceed) {
				// 先用内置作息渲染，后台作息到达后再刷新
				this.syncSectionTimes();
				this.loadScheduleModes();
			}
		});
	},

	methods: {
		async checkFeature() {
			const result = await feature.checkFeature('schedule');
			if (!result.enabled) {
				this.featureEnabled = false;
				this.announcement = null;
				feature.showDisabledAlert(result.featureName, result.announcement);
				return false;
			}
			if (result.announcement) {
				this.announcement = result.announcement;
			}
			return true;
		},

		loadScheduleModes() {
			this.loading = true;
			this.needBind = false;

			api.getScheduleMode()
				.then(res => {
					const modes = res.data || [];
					const currentMode = modes.length > 0 ? modes[0].kbjcmsid || '' : '';
					this.modes = modes;
					this.modeIndex = 0;
					this.currentMode = currentMode;
					this.loading = false;
					// 先取该课程模式的后台作息，再查课程，保证节次时间与后台配置一致
					this.loadScheduleTimes(currentMode);
					if (currentMode) this.loadSchedule();
				})
				.catch(err => {
					this.loading = false;
					if (feature.isAcademicNotBound(err)) {
						this.needBind = true;
						return;
					}
					uni.showToast({ title: err.msg || '获取课程表类别失败', icon: 'none' });
				});
		},

		onModeChange(e) {
			const mode = this.modes[e.detail.value];
			this.modeIndex = e.detail.value;
			this.currentMode = mode.kbjcmsid || '';
			// 不同课程模式可有各自的作息时间
			this.loadScheduleTimes(this.currentMode);
			this.loadSchedule();
		},

		//周次选择变化：选择后自动查询课程表
		onWeekChange(e) {
			this.weekIndex = e.detail.value;
			this.loadSchedule();
		},

		onQuery() {
			this.loadSchedule();
		},

		loadSchedule() {
			const { weekIndex, currentMode } = this;
			const week = weekIndex === 0 ? '' : weekIndex.toString();

			this.loading = true;

			api.querySchedule(week, currentMode)
				.then(res => {
					const { schedule, courses, currentWeek, courseColors, weekDates } = this.processScheduleData(res.data || []);
					this.schedule = schedule;
					this.courses = courses;
					this.currentWeek = currentWeek;
					this.courseColors = courseColors;
					this.weekDates = weekDates || [];
					this.loading = false;
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
				});
		},

		processScheduleData(data) {
			// 行数至少 12 行，后台作息配置更多节次时按配置补齐（如第 12 节及以后）
			const schedule = Array.from({ length: this.maxSection() }, () =>
				Array.from({ length: 7 }, () => ({ hasCourse: false }))
			);
			const courses = [];
			const courseColors = {};
			let currentWeek = 1;
		let weekDates = [];

			if (data && data.length > 0 && data[0].courses) {
				const scheduleData = data[0];
				currentWeek = scheduleData.week || 1;

			const dateByXq = {};
			if (scheduleData.date && scheduleData.date.length) {
				for (const d of scheduleData.date) {
					const xq = (d.xqmc || '').trim();
					if (xq) dateByXq[xq] = (d.mxrq || '').slice(-5);
				}
			}
			weekDates = weekDays.slice(1).map(day => dateByXq[day.charAt(1)] || '');

				for (const course of scheduleData.courses) {
					const { weekDay, sections } = this.parseClassTime(course);
					if (weekDay < 0 || weekDay >= 7) continue;

					const courseName = course.courseName || '';
					const color = getCourseColor(courseName);
					if (!courseColors[courseName]) courseColors[courseName] = color;

					const courseInfo = {
						courseName,
						weekDay: weekDays[weekDay + 1],
						section: sections.map(s => s + 1).join(','),
						_sections: sections.map(s => s + 1),
						location: course.location || course.classroomName || '',
						teacher: course.teacherName || '',
						weeks: course.classWeek || '',
						courseNature: course.courseNature || '',
						examinationNature: course.examinationNature || '',
						credit: course.credit || '',
						buildingName: course.buildingName || '',
						classroomNub: course.classroomNub || '',
						startTime: course.startTime || '',
						endTIme: course.endTIme || '',
						color
					};

					for (const section of sections) {
						if (section >= 0 && section < schedule.length) {
							schedule[section][weekDay] = {
								hasCourse: true,
								courseName,
								location: courseInfo.location,
								teacher: courseInfo.teacher,
								weeks: courseInfo.weeks,
								courseInfo,
								color
							};
						}
					}
					courses.push(courseInfo);
				}
			}

			const mergedCourses = this.mergeCourseSections(courses);

			return { schedule, courses: mergedCourses, currentWeek, courseColors, weekDates };
		},

		mergeCourseSections(courses) {
			const getKey = c => [c.courseName, c.weekDay, c.location, c.teacher, c.weeks].join('|');
			const formatSection = sections => {
				if (!sections || sections.length === 0) return '';
				const sorted = [...sections].sort((a, b) => a - b);
				const parts = [];
				let start = sorted[0], prev = sorted[0];
				for (let i = 1; i < sorted.length; i++) {
					if (sorted[i] === prev + 1) {
						prev = sorted[i];
					} else {
						parts.push(start === prev ? String(start) : start + '-' + prev);
						start = prev = sorted[i];
					}
				}
				parts.push(start === prev ? String(start) : start + '-' + prev);
				return parts.join(',');
			};

			courses = courses.slice().sort((a, b) => {
				const ka = getKey(a);
				const kb = getKey(b);
				if (ka !== kb) return ka.localeCompare(kb);
				return Math.min(...a._sections) - Math.min(...b._sections);
			});

			const groups = [];
			for (const c of courses) {
				if (!c._sections || c._sections.length === 0) continue;
				const key = getKey(c);
				const cMin = Math.min(...c._sections);
				const cMax = Math.max(...c._sections);
				let merged = false;
				for (const g of groups) {
					if (g._mergeKey !== key) continue;
					const gMin = Math.min(...g._sections);
					const gMax = Math.max(...g._sections);
					if (cMin <= gMax + 1 && gMin <= cMax + 1) {
						g._sections = Array.from(new Set([...g._sections, ...c._sections])).sort((a, b) => a - b);
						g.section = formatSection(g._sections);
						if (c.startTime && (!g.startTime || c.startTime < g.startTime)) g.startTime = c.startTime;
						if (c.endTIme && (!g.endTIme || c.endTIme > g.endTIme)) g.endTIme = c.endTIme;
						merged = true;
						break;
					}
				}
				if (!merged) {
					groups.push({ ...c, _mergeKey: key, _sectionFirst: cMin });
				}
			}

			return groups.sort((a, b) => {
				const wa = weekDays.indexOf(a.weekDay);
				const wb = weekDays.indexOf(b.weekDay);
				if (wa !== wb) return wa - wb;
				return (a._sectionFirst || 0) - (b._sectionFirst || 0);
			});
		},

		// 课程表行数 / 节次上限：至少 12 节，后台作息配置更多时按配置
		maxSection() {
			return Math.max(12, this.sectionTotalCount());
		},

		// 取当前作息里最大的节次数（课程表默认 12 行，配置更多节次时按配置补齐）
		sectionTotalCount() {
			let max = 0;
			Object.keys(timeSlots).forEach(key => {
				const n = parseInt(key, 10);
				if (n > max) max = n;
			});
			return max;
		},

		// 把作息同步到响应式数据，供模板渲染（后台配置到达后会自动刷新）
		syncSectionTimes() {
			const total = Math.max(12, this.sectionTotalCount());
			const list = [];
			for (let i = 1; i <= total; i++) {
				const t = timeSlots[i];
				list[i] = t && t.time ? t.time : '';
			}
			this.sectionTimes = list;
		},

		// 加载指定课程模式的后台作息（留空表示默认模式）
		loadScheduleTimes(modeId) {
			return fetchScheduleTimes(modeId).then(() => {
				this.syncSectionTimes();
			});
		},

		sectionTime(rowIndex) {
			return this.sectionTimes[rowIndex + 1] || '';
		},

		parseClassTime(course) {
			const classTime = course.classTime || '';
			// 星期几：优先使用接口返回的 weekDay 字段（1-7），与网页版保持一致
			let weekDay = -1;
			if (course.weekDay !== undefined && course.weekDay !== null && course.weekDay !== '') {
				const wd = parseInt(course.weekDay);
				if (wd >= 1 && wd <= 7) weekDay = wd - 1;
			}
			if (weekDay < 0 && classTime.length >= 1) {
				const wd = parseInt(classTime.charAt(0));
				if (wd >= 1 && wd <= 7) weekDay = wd - 1;
			}

			const sections = [];
			// 节次上限按后台作息（至少 12 节），避免第 12 节及以后的课程被丢弃
			const maxSection = this.maxSection();
			const pushSection = n => { if (n >= 1 && n <= maxSection) sections.push(n - 1); };

			// 优先使用 weekNoteDetail（如 "1节,2节"）
			const weekNoteDetail = course.weekNoteDetail || '';
			if (weekNoteDetail && weekNoteDetail.trim()) {
				const parts = weekNoteDetail.split(/[,，]/);
				for (const part of parts) {
					const p = part.trim();
					if (p.length >= 2) {
						const num = parseInt(p.slice(-2));
						if (num >= 1 && num <= maxSection) pushSection(num);
					}
				}
			}

			// 其次按 "1XX" 节次编码扫描 classTime（如 "105106" 表示第5、6节），与网页 getCourseTimeSlots 一致
			if (sections.length === 0) {
				for (let i = 1; i <= maxSection; i++) {
					const code = '1' + ('0' + i).slice(-2);
					if (classTime.indexOf(code) !== -1) pushSection(i);
				}
			}

			// 兼容旧格式："星期(1位) + 两位节次拼接"（如 "20506" 表示周二第5、6节）
			if (sections.length === 0) {
				const timeStr = weekDay >= 0 ? classTime.substring(1) : classTime;
				for (let i = 0; i < timeStr.length; i += 2) {
					const section = parseInt(timeStr.substr(i, 2));
					if (section >= 1 && section <= maxSection) pushSection(section);
				}
				if (sections.length === 0) {
					for (let i = 0; i < timeStr.length; i++) {
						const section = parseInt(timeStr.charAt(i));
						if (section >= 1 && section <= maxSection) pushSection(section);
					}
				}
			}

			return { weekDay, sections: [...new Set(sections)].sort((a, b) => a - b) };
		},

		onCourseTap(e) {
			const { day, section } = e.currentTarget.dataset;
			const cell = (this.schedule[section] || [])[day];
			if (cell && cell.hasCourse && cell.courseInfo) this.showCourseDetail(cell.courseInfo);
		},

		onCourseItemTap(e) {
			const index = e.currentTarget.dataset.index;
			const course = this.courses[index];
			if (course) this.showCourseDetail(course);
		},

		async aiAnalyze() {
			if (this.aiLoading) {
				// 正在分析中允许再次点击：重新聚焦分析弹窗并提示，不重复发起请求
				this.showAiResult = true;
				uni.showToast({ title: 'AI 正在分析...', icon: 'none' });
				return;
			}
			if (this.courses.length === 0) {
				uni.showToast({ title: '暂无课程数据', icon: 'none' });
				return;
			}
			const key = 'w' + this.weekIndex + '|' + this.currentMode + '|' + this.courses.length;
			if (this.aiCache[key]) {
				this.aiError = '';
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			// 先算好浮层高度再展示，避免首帧 sheetHeight 为 0 的遮罩空窗
			this.initSheetHeight();
			this.aiLoading = true;
			this.aiError = '';
			this.aiResult = '';
			this.showAiResult = true;
			const courses = this.courses.map(c => ({
				name: c.courseName,
				weekDay: c.weekDay,
				section: c.section,
				location: c.location,
				teacher: c.teacher,
				weeks: c.weeks,
				credit: c.credit,
				nature: c.courseNature
			}));
			try {
				const res = await api.aiAnalyzeSchedule({
					type: 'semester',
					week: this.weekIndex === 0 ? this.currentWeek : this.weekIndex,
					courses
				});
				const content = res.data && res.data.content ? String(res.data.content).trim() : '';
				if (!content) {
					this.aiError = 'AI 未返回有效内容，请稍后重试';
					return;
				}
				this.aiCache[key] = content;
				this.aiResult = content;
				this.aiError = '';
			} catch (err) {
				this.aiError = (err && err.msg) || '网络异常，请检查网络后重试';
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
		},

		//显示课程详情（自定义弹窗，与页面风格一致）
		showCourseDetail(course) {
			if (!course) return;
			// 课程主色（文字色），无则回退主题青色
			const color = (course.color && course.color.text) ? course.color.text : '#13c2c2';
			this.showDetail = true;
			this.detailCourse = course;
			this.detailIcons = {
				time: this.buildIconDataUri(ICON_CLOCK_PATHS, color),
				location: this.buildIconDataUri(ICON_LOCATION_PATHS, color),
				user: this.buildIconDataUri(ICON_USER_PATHS, color),
				calendar: this.buildIconDataUri(ICON_CALENDAR_PATHS, color)
			};
			// 按钮渐变终点色：课程色向白色混合 40%
			this.detailBtnBgLight = this.mixWithWhite(color, 0.4);
			this.detailBtnShadow = this.hexToRgba(color, 0.35);
		},

		//生成指定颜色的 SVG 图标 data URI（供 <image> 渲染）
		buildIconDataUri(paths, color) {
			const body = paths.map(d => `<path d="${d}" fill="${color}"/>`).join('');
			const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">${body}</svg>`;
			return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
		},

		//十六进制颜色转 rgba 字符串
		hexToRgba(hex, alpha) {
			let h = String(hex || '#13c2c2').replace('#', '');
			if (h.length === 3) h = h.split('').map(c => c + c).join('');
			const r = parseInt(h.slice(0, 2), 16) || 0;
			const g = parseInt(h.slice(2, 4), 16) || 0;
			const b = parseInt(h.slice(4, 6), 16) || 0;
			return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
		},

		//颜色向白色混合（ratio 为白色的占比 0~1）
		mixWithWhite(hex, ratio) {
			let h = String(hex || '#13c2c2').replace('#', '');
			if (h.length === 3) h = h.split('').map(c => c + c).join('');
			const mix = v => Math.round(v + (255 - v) * ratio);
			const r = mix(parseInt(h.slice(0, 2), 16) || 0);
			const g = mix(parseInt(h.slice(2, 4), 16) || 0);
			const b = mix(parseInt(h.slice(4, 6), 16) || 0);
			const toHex = v => ('0' + v.toString(16)).slice(-2);
			return '#' + toHex(r) + toHex(g) + toHex(b);
		},

		//关闭课程详情弹窗
		closeDetail() {
			this.showDetail = false;
			this.detailCourse = null;
		},

		// 跳转教务账号绑定页
		goToBind() {
			uni.navigateTo({ url: '/pages/academic-bind/index' });
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
}

/* 弹窗打开时隐藏固定的 banner 背景（与用电统计页保持一致的行为，
   避免 scroll-lock 锁定 body 后半透明遮罩下方整张透出 banner 图片；
   弹窗打开时改为 absolute 让其随容器铺满、只露顶部装饰条，还原“含 banner 的原页面” */
.ai-open .background-wrapper {
	position: absolute;
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

.empty-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #948eb3;
}

/* 未绑定教务账号提示：参考「今日课程」页（图标更清晰、标题 + 说明两行、间距一致） */
.empty-bind {
	padding: 80rpx 0;
}

.empty-bind .empty-icon {
	width: 100rpx;
	height: 100rpx;
	opacity: 1;
}

.schedule-wrapper {
	background: white;
	border-radius: 20rpx;
	overflow-x: auto;
	overflow-y: hidden;
	-webkit-overflow-scrolling: touch;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.schedule-header {
	display: flex;
	background: #13c2c2;
	width: 970rpx;
}

.header-item {
	flex: 1;
	text-align: center;
	color: white;
	font-size: 24rpx;
	padding: 16rpx 0;
	font-weight: bold;
}

.header-item:first-child {
	flex: 0 0 130rpx;
}

.schedule-body {
	display: flex;
	flex-direction: column;
}

.schedule-row {
	display: flex;
	width: 970rpx;
	border-bottom: 2rpx solid #f0f0f0;
}

.schedule-row:last-child {
	border-bottom: none;
}

.row-label {
	width: 130rpx;
	flex: 0 0 130rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	font-size: 28rpx;
	color: #948eb3;
	background: #f7f8fc;
}

.row-cells {
	flex: 1;
	display: flex;
}

.cell {
	flex: 1;
	min-height: 80rpx;
	border-right: 2rpx solid #f0f0f0;
	padding: 8rpx;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
}

.cell:last-child {
	border-right: none;
}

.cell.has-course {
	background: #e8f9f9;
	border-left: 4rpx solid #13c2c2;
}

.cell.has-course:active {
	opacity: 0.8;
}

.course-name {
	font-size: 18rpx;
	color: #48416f;
	text-align: center;
	word-break: break-all;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
}

.course-color-dot {
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	margin-right: 8rpx;
	flex-shrink: 0;
}

.course-info {
	font-size: 16rpx;
	color: #948eb3;
	text-align: center;
	margin-top: 4rpx;
}

.course-list {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.list-title {
	font-size: 28rpx;
	color: #48416f;
	font-weight: bold;
	margin-bottom: 20rpx;
}

.course-item {
	padding: 24rpx 0;
	border-bottom: 2rpx solid #f0f0f0;
	cursor: pointer;
	transition: background-color 0.2s ease;
}

.course-item:active {
	background-color: #f5f5f5;
}

.course-item:last-child {
	border-bottom: none;
}

.course-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;
}

.course-header .course-name {
	font-size: 30rpx;
	color: #48416f;
	font-weight: bold;
	display: flex;
	align-items: center;
}

.course-header .course-color-dot {
	width: 16rpx;
	height: 16rpx;
	border-radius: 50%;
	margin-right: 12rpx;
	flex-shrink: 0;
}

.course-name {
	font-size: 30rpx;
	color: #48416f;
	font-weight: bold;
}

.course-time {
	font-size: 24rpx;
	color: #13c2c2;
	background: #e8f9f9;
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
}

.course-detail {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.detail-item {
	font-size: 24rpx;
}

.detail-label {
	color: #948eb3;
}

.detail-value {
	color: #48416f;
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
	/* 渐变从弹窗最顶部（含拖动条区域）开始，与标题区连成一片，避免生硬的白色分界 */
	background: linear-gradient(180deg, #f7fbfd 0%, #ffffff 220rpx);
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
	position: relative;
	width: 72rpx;
	height: 8rpx;
	border-radius: 8rpx;
	background: #d8d4e8;
	margin: 18rpx auto 2rpx;
	transition: background 0.2s ease;
}

/* 扩大拖动热区：视觉仍是细条，但触摸范围向四周扩展，方便按住拖动 */
.ai-modal-handle::after {
	content: '';
	position: absolute;
	left: -80rpx;
	right: -80rpx;
	top: -24rpx;
	bottom: -24rpx;
}

.ai-modal-handle:active {
	background: #b9b2d6;
}

.ai-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-shrink: 0;
	padding: 16rpx 44rpx 22rpx;
	border-bottom: 1rpx solid #f0f0f4;
	/* 背景渐变由 .ai-modal 统一提供，这里保持透明以便与拖动条区域连续 */
	background: transparent;
}

.ai-modal-title-wrap {
	display: flex;
	align-items: center;
}

/* AI 徽标：与全站主题渐变一致 */
.ai-modal-badge {
	margin-right: 14rpx;
	padding: 4rpx 14rpx;
	font-size: 22rpx;
	font-weight: bold;
	line-height: 1.4;
	color: #ffffff;
	letter-spacing: 1rpx;
	border-radius: 10rpx;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	box-shadow: 0 4rpx 12rpx rgba(19, 194, 194, 0.28);
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

/* 加载态：空间充足时垂直居中；被拖小后自动贴顶并可滚动，避免内容溢出压住标题 */
.ai-modal-center-body {
	flex: 1;
	min-height: 0;
	display: flex;
	padding: 20rpx 44rpx;
	overflow-y: auto;
	/* overflow-y 非 visible 时 x 轴会变成 auto，必须显式隐藏，避免内容可左右拖动 */
	overflow-x: hidden;
	box-sizing: border-box;
}

/* margin: auto 实现「安全居中」：放得下就居中，放不下就顶部对齐 */
.ai-modal-center-item {
	margin: auto;
	flex-shrink: 0;
	width: 100%;
	max-width: 100%;
	/* 必须 border-box：否则 width:100% + 内边距会撑出容器，导致内容整体偏右 */
	box-sizing: border-box;
}

/* 失败态：容器已提供左右内边距，这里去掉全部留白保证真正居中 */
.ai-modal-center-item.ai-error {
	padding: 0;
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

/* ===== 分析失败：与全站 cb-load-fail 视觉一致 ===== */
.ai-error {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 20rpx 20rpx;
	text-align: center;
}

.ai-error-icon {
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 24rpx;
	opacity: 0.85;
}

.ai-error-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.ai-error-desc {
	font-size: 26rpx;
	color: #948eb3;
	line-height: 1.6;
	/* 长串（接口报错信息里的 URL / 哈希）不撑破容器，避免出现横向滚动 */
	word-break: break-word;
	overflow-wrap: anywhere;
}

.ai-error-btn {
	margin-top: 32rpx;
	height: 72rpx;
	line-height: 72rpx;
	padding: 0 60rpx;
	font-size: 28rpx;
	font-weight: bold;
	color: #ffffff;
	border-radius: 36rpx;
	background: linear-gradient(135deg, #13c2c2, #1890ff);
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.28);
}

.ai-error-btn:active {
	opacity: 0.9;
	transform: scale(0.98);
}

/* ===== 结果页脚注 ===== */
.ai-result-foot {
	margin-top: 28rpx;
	padding-top: 20rpx;
	border-top: 1rpx dashed #eceaf5;
	text-align: center;
	font-size: 22rpx;
	color: #b0abc7;
	letter-spacing: 0.5rpx;
}

/* AI 弹窗打开时隐藏固定在视口的 banner 背景。
   原因：scroll-lock 把 body 设为 fixed 时，body 不会成为其内部 fixed 子元素的包含块，
   导致 .background-wrapper 仍钉在视口顶部，而 .content-wrapper 随 body 上移，
   课表内容较短时半透明遮罩下方会整张透出 banner 图片而非原课程表页面。
   （正常态 banner 本就被不透明的 .content-wrapper 覆盖，弹窗打开时改为 absolute 使其随已固定的
   容器一起铺满视口，只露出顶部 30rpx 装饰条，从而在下层还原出“含 banner 的原页面”效果） */
.ai-open .background-wrapper {
	position: absolute;
}

/* ==================== 课程详情自定义弹窗 ==================== */

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

.detail-row {
	display: flex;
	align-items: center;
	padding: 22rpx 0;
}

.detail-row-icon {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	margin-right: 22rpx;
	flex-shrink: 0;
}

.detail-row-icon-img {
	width: 32rpx;
	height: 32rpx;
}

.detail-row-label {
	font-size: 26rpx;
	color: #948eb3;
	width: 150rpx;
	flex-shrink: 0;
}

.detail-row-value {
	flex: 1;
	font-size: 27rpx;
	color: #48416f;
	font-weight: 500;
	text-align: right;
	word-break: break-all;
	line-height: 1.5;
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
