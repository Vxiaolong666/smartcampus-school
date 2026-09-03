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
					<view class="header-item">周一</view>
					<view class="header-item">周二</view>
					<view class="header-item">周三</view>
					<view class="header-item">周四</view>
					<view class="header-item">周五</view>
					<view class="header-item">周六</view>
					<view class="header-item">周日</view>
				</view>

				<view class="schedule-body">
					<view class="schedule-row" v-for="(row, rowIndex) in schedule" :key="rowIndex">
						<view class="row-label">第{{ rowIndex + 1 }}节</view>
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
import { getCourseColor, weekDays } from '../../utils/common.js';

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
			courseColors: {}
		};
	},

	onLoad(options) {
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
					const { schedule, courses, currentWeek, courseColors } = this.processScheduleData(res.data || []);
					this.schedule = schedule;
					this.courses = courses;
					this.currentWeek = currentWeek;
					this.courseColors = courseColors;
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

			if (data && data.length > 0 && data[0].courses) {
				const scheduleData = data[0];
				currentWeek = scheduleData.week || 1;

				for (const course of scheduleData.courses) {
					const { weekDay, sections } = this.parseClassTime(course.classTime || '');
					if (weekDay < 0 || weekDay >= 7) continue;

					const courseName = course.courseName || '';
					const color = getCourseColor(courseName);
					if (!courseColors[courseName]) courseColors[courseName] = color;

					const courseInfo = {
						courseName,
						weekDay: weekDays[weekDay + 1],
						section: sections.map(s => s + 1).join(','),
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

			const seen = new Set();
			const uniqueCourses = courses.filter(c => {
				const key = c.courseName + c.weekDay + c.section;
				return seen.has(key) ? false : (seen.add(key), true);
			});

			return { schedule, courses: uniqueCourses, currentWeek, courseColors };
		},

		parseClassTime(classTime) {
			if (!classTime || classTime.length < 2) return { weekDay: 0, sections: [] };

			const weekDay = parseInt(classTime.charAt(0)) - 1;
			const timeStr = classTime.substring(1);
			const sections = [];

			for (let i = 0; i < timeStr.length; i += 2) {
				const section = parseInt(timeStr.substr(i, 2));
				if (section >= 1 && section <= 12) sections.push(section - 1);
			}

			if (sections.length === 0) {
				for (let i = 0; i < timeStr.length; i++) {
					const section = parseInt(timeStr.charAt(i));
					if (section >= 1 && section <= 12) sections.push(section - 1);
				}
			}

			return { weekDay, sections };
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
	overflow: hidden;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.schedule-header {
	display: flex;
	background: #13c2c2;
}

.header-item {
	flex: 1;
	text-align: center;
	color: white;
	font-size: 22rpx;
	padding: 16rpx 0;
	font-weight: bold;
}

.header-item:first-child {
	flex: 0 0 80rpx;
}

.schedule-body {
	display: flex;
	flex-direction: column;
}

.schedule-row {
	display: flex;
	border-bottom: 2rpx solid #f0f0f0;
}

.schedule-row:last-child {
	border-bottom: none;
}

.row-label {
	width: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20rpx;
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
</style>
