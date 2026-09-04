<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">课程表</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<announcement v-if="announcement" :content="announcement.content" type="info" />

			<view class="filter-card">
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

			<view v-if="loading" class="loading-wrapper">
				<view class="loading-text">加载中...</view>
			</view>

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

			<view v-if="courses.length > 0" class="ai-action">
				<button class="ai-btn" @click="aiAnalyze" :loading="aiLoading">AI 智能分析课表</button>
			</view>
		</view>

		<view v-if="showAiResult" class="ai-mask" @click="closeAiResult">
			<view class="ai-modal" :style="{ height: sheetHeight + 'px' }" @click.stop>
				<view class="ai-modal-handle"
					@touchstart="onSheetDragStart"
					@touchmove="onSheetDragMove"
					@touchend="onSheetDragEnd"></view>
				<view class="ai-modal-header">
					<text class="ai-modal-title">AI 智能课表分析</text>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<scroll-view scroll-y="true" class="ai-modal-body">
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
 * 课程表页面
 * 展示学生课程表信息
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import { getCourseColor, weekDays, timeSlots } from '../../utils/common.js';
import markdownToHtml from '../../utils/markdown.js';

export default {
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
			loading: false,
			schedule: [],
			courses: [],
			currentWeek: 1,
			weekDates: [],
			weekHeaders: weekDays.slice(1),
			courseColors: {},
			showAiResult: false,
			aiResult: '',
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
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => uni.navigateTo({ url: '/pages/login/index' }), 1000);
			return;
		}
		this.checkFeature().then(canProceed => {
			if (canProceed) {
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

			api.getScheduleMode()
				.then(res => {
					const modes = res.data || [];
					const currentMode = modes.length > 0 ? modes[0].kbjcmsid || '' : '';
					this.modes = modes;
					this.modeIndex = 0;
					this.currentMode = currentMode;
					this.loading = false;
					if (currentMode) this.loadSchedule();
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '获取课程表类别失败', icon: 'none' });
				});
		},

		onModeChange(e) {
			const mode = this.modes[e.detail.value];
			this.modeIndex = e.detail.value;
			this.currentMode = mode.kbjcmsid || '';
			this.loadSchedule();
		},

		onWeekChange(e) {
			this.weekIndex = e.detail.value;
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
			const schedule = Array.from({ length: 12 }, () =>
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
						if (section >= 0 && section < 12) {
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

		sectionTime(rowIndex) {
			const t = timeSlots[rowIndex + 1];
			return t ? t.time : '';
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
			const pushSection = n => { if (n >= 1 && n <= 12) sections.push(n - 1); };

			// 优先使用 weekNoteDetail（如 "1节,2节"）
			const weekNoteDetail = course.weekNoteDetail || '';
			if (weekNoteDetail && weekNoteDetail.trim()) {
				const parts = weekNoteDetail.split(/[,，]/);
				for (const part of parts) {
					const p = part.trim();
					if (p.length >= 2) {
						const num = parseInt(p.slice(-2));
						if (num >= 1 && num <= 11) pushSection(num);
					}
				}
			}

			// 其次按 "1XX" 节次编码扫描 classTime（如 "105106" 表示第5、6节），与网页 getCourseTimeSlots 一致
			if (sections.length === 0) {
				for (let i = 1; i <= 11; i++) {
					const code = '1' + ('0' + i).slice(-2);
					if (classTime.indexOf(code) !== -1) pushSection(i);
				}
			}

			// 兼容旧格式："星期(1位) + 两位节次拼接"（如 "20506" 表示周二第5、6节）
			if (sections.length === 0) {
				const timeStr = weekDay >= 0 ? classTime.substring(1) : classTime;
				for (let i = 0; i < timeStr.length; i += 2) {
					const section = parseInt(timeStr.substr(i, 2));
					if (section >= 1 && section <= 12) pushSection(section);
				}
				if (sections.length === 0) {
					for (let i = 0; i < timeStr.length; i++) {
						const section = parseInt(timeStr.charAt(i));
						if (section >= 1 && section <= 12) pushSection(section);
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
			if (this.aiLoading) return;
			if (this.courses.length === 0) {
				uni.showToast({ title: '暂无课程数据', icon: 'none' });
				return;
			}
			const key = 'w' + this.weekIndex + '|' + this.currentMode + '|' + this.courses.length;
			if (this.aiCache[key]) {
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			this.aiLoading = true;
			this.aiResult = '### AI 正在分析…\n\n正在读取课表数据并生成分析报告，请稍候（通常需要十几秒）。';
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
					this.aiResult = '### 暂无分析结果\n\nAI 未返回有效内容，请稍后重试。';
					return;
				}
				this.aiCache[key] = content;
				this.aiResult = content;
			} catch (err) {
				const msg = (err && err.msg) || '分析失败';
				this.aiResult = '### 分析失败\n\n' + msg + '\n\n请稍后重试，或联系管理员检查「AI 配置」是否正确、额度是否充足。';
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

		showCourseDetail(course) {
			const lines = [
				`课程：${course.courseName || '未知'}`,
				`时间：${course.weekDay || ''} 第${course.section || '-'}节`,
				course.startTime ? `时段：${course.startTime} - ${course.endTIme || ''}` : '',
				`地点：${course.location || '待定'}`,
				`教师：${course.teacher || '待定'}`,
				`周次：${course.weeks || '-'}`,
				course.credit ? `学分：${course.credit}` : '',
				course.courseNature ? `性质：${course.courseNature}` : ''
			].filter(Boolean);

			uni.showModal({
				title: '课程详情',
				content: lines.join('\n'),
				showCancel: false,
				confirmText: '关闭'
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

.ai-action {
	margin: 24rpx 0 8rpx;
}

.ai-btn {
	width: 100%;
	background: linear-gradient(135deg, #13c2c2 0%, #1890ff 100%);
	color: white;
	font-size: 28rpx;
	height: 96rpx;
	line-height: 96rpx;
	padding: 0;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 10rpx 24rpx rgba(19, 194, 194, 0.25);
}

.ai-btn::after {
	border: none;
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
