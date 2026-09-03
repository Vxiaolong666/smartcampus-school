<template>
	<view class="container">
		<cb-nav-bar :show-back="false">
			<view class="nav-title">今日课程</view>
			<view slot="right" class="nav-action" @click="goToFullSchedule">
				<text class="action-text">完整课表</text>
			</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view v-if="announcement">
				<announcement :content="announcement.content" :type="announcement.type" :closable="true" />
			</view>

			<view class="day-selector">
				<view
					class="day-item"
					:class="{ active: selectedDay === index, today: todayDayOfWeek === index }"
					v-for="(item, index) in weekDates"
					:key="item.dateStr"
					:data-day="index"
					@click="onDaySelect"
				>
					<view class="day-name">{{ dayNames[index] }}</view>
					<view class="day-date">{{ item.date }}</view>
					<view class="day-tag" v-if="todayDayOfWeek === index">今天</view>
				</view>
			</view>

			<view class="info-cards">
				<view class="info-card date-card">
					<image class="info-icon" src="/static/assets/wxmp-calendar.svg" mode="widthFix"></image>
					<view class="info-content">
						<view class="info-title">{{ fullDayNames[selectedDay] }}{{ selectedDay === todayDayOfWeek ? ' (今天)' : '' }}</view>
						<view class="info-sub">{{ (weekDates[selectedDay] && weekDates[selectedDay].full) || '' }} · 第{{ currentWeek }}周</view>
					</view>
				</view>
				<view class="info-card time-card">
					<image class="info-icon" src="/static/assets/wxmp-clock.svg" mode="widthFix"></image>
					<view class="info-content">
						<view class="info-title">{{ currentTime }}</view>
						<view class="info-sub">
							<block v-if="selectedDay !== todayDayOfWeek">
								<text class="status-free">非今日</text>
							</block>
							<block v-else-if="allCoursesEnded">
								<text class="status-ended">今日课程结束了</text>
							</block>
							<block v-else-if="isEndingSoon">
								<text class="status-ending">即将下课</text>
							</block>
							<block v-else-if="currentCourse">
								<text class="status-current">上课中</text>
							</block>
							<block v-else-if="notStarted || inBreak">
								<text class="status-waiting">即将上课</text>
							</block>
							<block v-else>
								<text class="status-free">课余时间</text>
							</block>
						</view>
					</view>
				</view>
			</view>

			<view v-if="currentCourse || nextCourse || allCoursesEnded || notStarted" class="status-cards">
				<view v-if="allCoursesEnded && selectedDay === todayDayOfWeek" class="status-card ended">
					<view class="status-header">
						<image class="status-label-icon" src="/static/assets/wxmp-check.svg" mode="widthFix"></image>
						<text class="status-label">今日课程已结束</text>
					</view>
					<view class="status-body">
						<view class="status-title">今日的课上完了，好好休息吧~</view>
					</view>
				</view>

				<view
					v-if="notStarted && selectedDay === todayDayOfWeek && nextCourse"
					class="status-card waiting"
					:style="'border-color: ' + nextCourse.color.text + ';'"
				>
					<view class="status-header" :style="'background-color: ' + nextCourse.color.text + ';'">
						<image class="status-label-icon large" src="/static/assets/wxmp-alarm-white.svg" mode="widthFix"></image>
						<text class="status-label">课程未开始</text>
						<text class="status-time">还有{{ minutesToNext }}分钟上课</text>
					</view>
					<view class="status-body" :style="'background: linear-gradient(135deg, ' + nextCourse.color.bg + ' 0%, #ffffff 100%);'">
						<view class="status-title" :style="'color: ' + nextCourse.color.text + ';'">{{ nextCourse.courseName }}</view>
						<view class="status-info-row" v-if="nextCourse.location || nextCourse.teacher">
							<view class="status-info-item" v-if="nextCourse.location">
								<image class="status-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
								<text>{{ nextCourse.location }}</text>
							</view>
							<view class="status-info-item" v-if="nextCourse.teacher">
								<image class="status-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
								<text>{{ nextCourse.teacher }}</text>
							</view>
						</view>
					</view>
				</view>

				<view
					v-if="currentCourse && selectedDay === todayDayOfWeek"
					class="status-card current"
					:style="'border-color: ' + currentCourse.color.text + ';'"
				>
					<view class="status-header" :style="'background-color: ' + currentCourse.color.text + ';'">
						<image class="status-label-icon large" src="/static/assets/wxmp-play-white.svg" mode="widthFix"></image>
						<text class="status-label">正在上课</text>
						<text class="status-time">还有{{ minutesToEnd }}分钟下课</text>
					</view>
					<view class="status-body" :style="'background: linear-gradient(135deg, ' + currentCourse.color.bg + ' 0%, #ffffff 100%);'">
						<view class="status-title" :style="'color: ' + currentCourse.color.text + ';'">{{ currentCourse.courseName }}</view>
						<view class="status-info-row" v-if="currentCourse.location || currentCourse.teacher">
							<view class="status-info-item" v-if="currentCourse.location">
								<image class="status-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
								<text>{{ currentCourse.location }}</text>
							</view>
							<view class="status-info-item" v-if="currentCourse.teacher">
								<image class="status-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
								<text>{{ currentCourse.teacher }}</text>
							</view>
						</view>
					</view>
				</view>

				<view
					v-if="inBreak && nextCourse && selectedDay === todayDayOfWeek"
					class="status-card next"
					:style="'border-color: ' + nextCourse.color.text + ';'"
				>
					<view class="status-header" :style="'background-color: ' + nextCourse.color.text + ';'">
						<image class="status-label-icon large" src="/static/assets/wxmp-alarm-white.svg" mode="widthFix"></image>
						<text class="status-label">课间休息</text>
						<text class="status-time">还有{{ minutesToNext }}分钟上课</text>
					</view>
					<view class="status-body" :style="'background: linear-gradient(135deg, ' + nextCourse.color.bg + ' 0%, #ffffff 100%);'">
						<view class="status-title" :style="'color: ' + nextCourse.color.text + ';'">{{ nextCourse.courseName }}</view>
						<view class="status-info-row" v-if="nextCourse.location || nextCourse.teacher">
							<view class="status-info-item" v-if="nextCourse.location">
								<image class="status-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
								<text>{{ nextCourse.location }}</text>
							</view>
							<view class="status-info-item" v-if="nextCourse.teacher">
								<image class="status-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
								<text>{{ nextCourse.teacher }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view v-if="loading" class="loading-wrapper">
				<view class="loading-text">加载中...</view>
			</view>

			<view v-else-if="dayCourses.length > 0" class="course-list-wrapper">
				<view v-if="morningCourses.length > 0" class="course-section">
					<view class="section-header">
						<view class="section-title">
							<image class="section-icon" src="/static/assets/wxmp-sun.svg" mode="widthFix"></image>
							<text>上午</text>
						</view>
						<text class="section-count">{{ morningCourses.length }}节</text>
					</view>
					<view class="course-timeline">
						<view
							class="course-card-wrapper"
							:class="{ current: currentCourse && currentCourse.courseName === item.courseName, ended: item.isEnded }"
							v-for="(item, index) in morningCourses"
							:key="item.classTime"
							data-group="morning"
							:data-index="index"
							@click="onCourseTap"
						>
							<view class="timeline-left">
								<view class="timeline-line-top" v-if="index > 0"></view>
								<view class="timeline-dot" :style="'background-color: ' + (item.isEnded ? '#cccccc' : item.color.text) + ';'"></view>
								<view class="timeline-line-bottom" v-if="index < morningCourses.length - 1"></view>
							</view>
							<view
								class="course-card"
								:style="currentCourse && currentCourse.courseName === item.courseName ? 'border: 2rpx solid ' + item.color.text : ''"
							>
								<view class="course-content">
									<view class="course-header">
										<view class="course-slot" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">第{{ item.slots[0] }}节</view>
										<view class="course-time">{{ item.timeDisplay }}</view>
									</view>
									<view class="course-name-row">
										<view class="course-icon-book" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'"></view>
										<text class="course-name" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">{{ item.courseName }}</text>
									</view>
									<view class="course-info-row" v-if="item.location || item.teacher">
										<view class="course-info-item" v-if="item.location">
											<image class="course-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
											<text>{{ item.location }}</text>
										</view>
										<view class="course-info-item" v-if="item.teacher">
											<image class="course-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
											<text>{{ item.teacher }}</text>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>

				<view v-if="afternoonCourses.length > 0" class="course-section">
					<view class="section-header">
						<view class="section-title">
							<image class="section-icon" src="/static/assets/wxmp-cloud.svg" mode="widthFix"></image>
							<text>下午</text>
						</view>
						<text class="section-count">{{ afternoonCourses.length }}节</text>
					</view>
					<view class="course-timeline">
						<view
							class="course-card-wrapper"
							:class="{ current: currentCourse && currentCourse.courseName === item.courseName, ended: item.isEnded }"
							v-for="(item, index) in afternoonCourses"
							:key="item.classTime"
							data-group="afternoon"
							:data-index="index"
							@click="onCourseTap"
						>
							<view class="timeline-left">
								<view class="timeline-line-top" v-if="index > 0"></view>
								<view class="timeline-dot" :style="'background-color: ' + (item.isEnded ? '#cccccc' : item.color.text) + ';'"></view>
								<view class="timeline-line-bottom" v-if="index < afternoonCourses.length - 1"></view>
							</view>
							<view
								class="course-card"
								:style="currentCourse && currentCourse.courseName === item.courseName ? 'border: 2rpx solid ' + item.color.text : ''"
							>
								<view class="course-content">
									<view class="course-header">
										<view class="course-slot" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">第{{ item.slots[0] }}节</view>
										<view class="course-time">{{ item.timeDisplay }}</view>
									</view>
									<view class="course-name-row">
										<view class="course-icon-book" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'"></view>
										<text class="course-name" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">{{ item.courseName }}</text>
									</view>
									<view class="course-info-row" v-if="item.location || item.teacher">
										<view class="course-info-item" v-if="item.location">
											<image class="course-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
											<text>{{ item.location }}</text>
										</view>
										<view class="course-info-item" v-if="item.teacher">
											<image class="course-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
											<text>{{ item.teacher }}</text>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>

				<view v-if="eveningCourses.length > 0" class="course-section">
					<view class="section-header">
						<view class="section-title">
							<image class="section-icon" src="/static/assets/wxmp-moon.svg" mode="widthFix"></image>
							<text>晚上</text>
						</view>
						<text class="section-count">{{ eveningCourses.length }}节</text>
					</view>
					<view class="course-timeline">
						<view
							class="course-card-wrapper"
							:class="{ current: currentCourse && currentCourse.courseName === item.courseName, ended: item.isEnded }"
							v-for="(item, index) in eveningCourses"
							:key="item.classTime"
							data-group="evening"
							:data-index="index"
							@click="onCourseTap"
						>
							<view class="timeline-left">
								<view class="timeline-line-top" v-if="index > 0"></view>
								<view class="timeline-dot" :style="'background-color: ' + (item.isEnded ? '#cccccc' : item.color.text) + ';'"></view>
								<view class="timeline-line-bottom" v-if="index < eveningCourses.length - 1"></view>
							</view>
							<view
								class="course-card"
								:style="currentCourse && currentCourse.courseName === item.courseName ? 'border: 2rpx solid ' + item.color.text : ''"
							>
								<view class="course-content">
									<view class="course-header">
										<view class="course-slot" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">第{{ item.slots[0] }}节</view>
										<view class="course-time">{{ item.timeDisplay }}</view>
									</view>
									<view class="course-name-row">
										<view class="course-icon-book" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'"></view>
										<text class="course-name" :style="'color: ' + (item.isEnded ? '#999999' : item.color.text) + ';'">{{ item.courseName }}</text>
									</view>
									<view class="course-info-row" v-if="item.location || item.teacher">
										<view class="course-info-item" v-if="item.location">
											<image class="course-icon" src="/static/assets/wxmp-location.svg" mode="widthFix"></image>
											<text>{{ item.location }}</text>
										</view>
										<view class="course-info-item" v-if="item.teacher">
											<image class="course-icon" src="/static/assets/wxmp-user.svg" mode="widthFix"></image>
											<text>{{ item.teacher }}</text>
										</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>

			<view v-else class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-book-gray.svg" mode="widthFix"></image>
				<view class="empty-title">{{ fullDayNames[selectedDay] }}没有课程</view>
				<view class="empty-text">
					{{ selectedDay === todayDayOfWeek ? '今天没有课程，好好休息一下吧~' : '这一天没有安排课程，看看其他天吧~' }}
				</view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 今日课程页面
 * 展示当天课程安排和实时上课状态
 */
import api from '../../utils/api.js';
import feature from '../../utils/feature.js';
import { getCourseColor, timeSlots, weekDays } from '../../utils/common.js';

const DAY_NAMES = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
const FULL_DAY_NAMES = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

/**
 * 判断两节课是否同一节，用于避免对象引用变化导致的无谓重渲染
 */
const sameCourse = (a, b) => {
	if (a === b) return true;
	if (!a || !b) return false;
	return a.courseName === b.courseName && a.slots[0] === b.slots[0];
};

export default {
	data() {
		return {
			loading: true,
			featureEnabled: true,
			announcement: null,
			currentWeek: 1,
			selectedDay: 0,
			todayDayOfWeek: 0,
			weekDates: [],
			allCourses: [],
			dayCourses: [],
			morningCourses: [],
			afternoonCourses: [],
			eveningCourses: [],
			currentTime: '',
			currentSection: null,
			currentCourse: null,
			nextCourse: null,
			inBreak: false,
			minutesToEnd: 0,
			minutesToNext: 0,
			allCoursesEnded: false,
			notStarted: false,
			isEndingSoon: false,
			dayNames: DAY_NAMES,
			fullDayNames: FULL_DAY_NAMES,
			timeInterval: null,
			// 内部标记，放在 data 中以保证读写都被正确代理
			_lastMinute: -1,
			_featureChecked: false,
			_pageVisible: false
		};
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.redirectTo({ url: '/pages/login/index' });
			return;
		}

		this.checkFeature().then(canProceed => {
			if (!canProceed) return;

			const today = new Date();
			const weekDates = this.getWeekDates();

			this.todayDayOfWeek = today.getDay();
			this.selectedDay = today.getDay();
			this.weekDates = weekDates;

			this.loadSchedule();

			this.updateTime();
			if (this._pageVisible) this.startTimer();
		});
	},

	onShow() {
		// 从其它页面返回时恢复计时；首次进入由 onLoad 启动
		this._pageVisible = true;
		if (this._featureChecked) this.startTimer();
	},

	onHide() {
		// 页面不可见时必须停止计时，否则后台仍在做整页状态计算，拖慢页面切换
		this._pageVisible = false;
		this.stopTimer();
	},

	onUnload() {
		this.stopTimer();
	},

	methods: {
		async checkFeature() {
			try {
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
				this._featureChecked = true;
				return true;
			} catch (err) {
				console.error('检查功能开关失败', err);
				this._featureChecked = true;
				return true;
			}
		},

		startTimer() {
			this.stopTimer();
			this.timeInterval = setInterval(() => this.tick(), 1000);
		},

		stopTimer() {
			if (this.timeInterval) {
				clearInterval(this.timeInterval);
				this.timeInterval = null;
			}
		},

		tick() {
			// 每秒只比对一次分钟数，跨分钟才做状态计算
			if (new Date().getMinutes() === this._lastMinute) return;
			this.updateTime();
		},

		setIfChanged(key, value) {
			if (this[key] !== value) this[key] = value;
		},

		getWeekDates() {
			const today = new Date();
			const weekStart = new Date(today);
			weekStart.setDate(today.getDate() - today.getDay());

			return Array.from({ length: 7 }, (_, i) => {
				const date = new Date(weekStart);
				date.setDate(weekStart.getDate() + i);
				return {
					date: date.getDate(),
					full: `${date.getFullYear()}年${String(date.getMonth() + 1).padStart(2, '0')}月${String(date.getDate()).padStart(2, '0')}日`,
					dateStr: date.toISOString().split('T')[0]
				};
			});
		},

		updateTime() {
			const now = new Date();
			const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
			let currentSection = null;

			for (let i = 1; i <= 11; i++) {
				const [start, end] = timeSlots[i].time.split('-');
				if (currentTime >= start && currentTime <= end) {
					currentSection = i;
					break;
				}
			}

			this._lastMinute = now.getMinutes();
			this.setIfChanged('currentTime', currentTime);
			this.setIfChanged('currentSection', currentSection);
			this.updateCourseStatus();
		},

		timeToMinutes(timeStr) {
			const [h, m] = timeStr.split(':');
			return parseInt(h) * 60 + parseInt(m);
		},

		updateCourseStatus() {
			const { dayCourses, currentSection, selectedDay, todayDayOfWeek, currentTime } = this;
			const isToday = selectedDay === todayDayOfWeek;

			if (!isToday || dayCourses.length === 0) {
				if (this.currentCourse !== null) this.currentCourse = null;
				if (this.nextCourse !== null) this.nextCourse = null;
				this.setIfChanged('inBreak', false);
				this.setIfChanged('minutesToEnd', 0);
				this.setIfChanged('minutesToNext', 0);
				this.setIfChanged('allCoursesEnded', false);
				this.setIfChanged('notStarted', false);
				this.setIfChanged('isEndingSoon', false);
				return;
			}

			const currentMinutes = this.timeToMinutes(currentTime);
			let currentCourse = null,
				nextCourse = null,
				minutesToEnd = 0,
				minutesToNext = 0;
			let allCoursesEnded = false,
				notStarted = false,
				isEndingSoon = false;

			const updatedCourses = dayCourses.map(course => {
				if (course.slots && course.slots.length > 0) {
					const lastSlot = Math.max(...course.slots);
					const endTime = (timeSlots[lastSlot] && timeSlots[lastSlot].time.split('-')[1]) || '';
					return { ...course, isEnded: currentMinutes > this.timeToMinutes(endTime) };
				}
				return { ...course, isEnded: false };
			});

			if (currentSection) {
				currentCourse = updatedCourses.find(c => c.slots && c.slots.includes(currentSection));
			}

			if (currentCourse) {
				const lastSlot = Math.max(...currentCourse.slots);
				const endTime = (timeSlots[lastSlot] && timeSlots[lastSlot].time.split('-')[1]) || '';
				const firstSlot = Math.min(...currentCourse.slots);
				const startTime = (timeSlots[firstSlot] && timeSlots[firstSlot].time.split('-')[0]) || '';
				minutesToEnd = Math.max(0, this.timeToMinutes(endTime) - currentMinutes);
				const totalDuration = this.timeToMinutes(endTime) - this.timeToMinutes(startTime);
				isEndingSoon = totalDuration > 0 && minutesToEnd <= totalDuration * 0.2;
			} else {
				const firstCourse = updatedCourses[0];
				const firstStartMinutes = this.timeToMinutes(
					(timeSlots[Math.min(...firstCourse.slots)] && timeSlots[Math.min(...firstCourse.slots)].time.split('-')[0]) || '23:59'
				);

				if (currentMinutes < firstStartMinutes) {
					notStarted = true;
					nextCourse = firstCourse;
					minutesToNext = firstStartMinutes - currentMinutes;
				} else {
					for (const course of updatedCourses) {
						if (course.slots && course.slots.length > 0) {
							const startMinutes = this.timeToMinutes(
								(timeSlots[Math.min(...course.slots)] && timeSlots[Math.min(...course.slots)].time.split('-')[0]) || '23:59'
							);
							if (
								startMinutes > currentMinutes &&
								(!nextCourse ||
									startMinutes <
										this.timeToMinutes(
											(timeSlots[Math.min(...nextCourse.slots)] &&
												timeSlots[Math.min(...nextCourse.slots)].time.split('-')[0]) ||
												'23:59'
										))
							) {
								nextCourse = course;
								minutesToNext = startMinutes - currentMinutes;
							}
						}
					}

					if (!nextCourse && updatedCourses.length > 0) {
						const lastCourse = updatedCourses[updatedCourses.length - 1];
						const endTime =
							(timeSlots[Math.max(...lastCourse.slots)] && timeSlots[Math.max(...lastCourse.slots)].time.split('-')[1]) || '';
						allCoursesEnded = currentMinutes > this.timeToMinutes(endTime);
					}
				}
			}

			// 仅在结课状态真正变化时重建列表，避免每分钟触发整页 diff
			const endedChanged = updatedCourses.some((c, i) => c.isEnded !== dayCourses[i].isEnded);
			if (endedChanged) {
				this.dayCourses = updatedCourses;
				this.splitGroups(updatedCourses);
			}

			if (!sameCourse(this.currentCourse, currentCourse)) this.currentCourse = currentCourse || null;
			if (!sameCourse(this.nextCourse, nextCourse)) this.nextCourse = nextCourse || null;
			this.setIfChanged('inBreak', !currentCourse && !!nextCourse && !notStarted);
			this.setIfChanged('minutesToEnd', minutesToEnd);
			this.setIfChanged('minutesToNext', minutesToNext);
			this.setIfChanged('allCoursesEnded', allCoursesEnded);
			this.setIfChanged('notStarted', notStarted);
			this.setIfChanged('isEndingSoon', isEndingSoon);
		},

		splitGroups(list) {
			this.morningCourses = list.filter(c => c.slots[0] >= 1 && c.slots[0] <= 4);
			this.afternoonCourses = list.filter(c => c.slots[0] >= 5 && c.slots[0] <= 7);
			this.eveningCourses = list.filter(c => c.slots[0] >= 8 && c.slots[0] <= 11);
		},

		loadSchedule() {
			this.loading = true;
			api.querySchedule('', '')
				.then(res => this.processScheduleData(res.data || []))
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '查询失败', icon: 'none' });
				});
		},

		processScheduleData(data) {
			let currentWeek = 1;
			const allCourses = [];

			if (data && data.length > 0 && data[0].courses) {
				currentWeek = data[0].week || 1;
				for (const course of data[0].courses) {
					const { weekDay, sections } = this.parseClassTime(course.classTime || '');
					if (weekDay < 0 || weekDay >= 7) continue;

					const courseName = course.courseName || '';
					const color = getCourseColor(courseName);

					for (const section of sections) {
						allCourses.push({
							courseName,
							weekDay,
							weekDayName: weekDays[weekDay + 1],
							slots: [section],
							location: course.location || course.classroomName || '',
							teacher: course.teacherName || '',
							weeks: course.classWeek || '',
							classTime: course.classTime || '',
							timeDisplay: (timeSlots[section] && timeSlots[section].time) || '',
							color
						});
					}
				}
			}

			this.currentWeek = currentWeek;
			this.allCourses = allCourses;
			this.filterDayCourses();
		},

		parseClassTime(classTime) {
			if (!classTime || classTime.length < 2) return { weekDay: 0, sections: [] };

			const weekDay = parseInt(classTime.charAt(0)) - 1;
			const timeStr = classTime.substring(1);
			const sections = [];

			for (let i = 0; i < timeStr.length; i += 2) {
				const section = parseInt(timeStr.substr(i, 2));
				if (section >= 1 && section <= 11) sections.push(section);
			}

			if (sections.length === 0) {
				for (let i = 0; i < timeStr.length; i++) {
					const section = parseInt(timeStr.charAt(i));
					if (section >= 1 && section <= 11) sections.push(section);
				}
			}

			return { weekDay, sections };
		},

		filterDayCourses() {
			const { allCourses, selectedDay, currentWeek } = this;
			const targetDay = selectedDay === 0 ? 7 : selectedDay;

			const dayCourses = allCourses
				.filter(course => {
					if (course.weekDay !== targetDay - 1) return false;
					if (!course.weeks) return true;

					const match = course.weeks.match(/^(\d+)-(\d+)$/);
					if (match) {
						const [_, start, end] = match.map(Number);
						return currentWeek >= start && currentWeek <= end;
					}
					return course.weeks.includes(String(currentWeek));
				})
				.map(c => ({ ...c, isEnded: false }))
				.sort((a, b) => (a.slots[0] || 99) - (b.slots[0] || 99));

			this.dayCourses = dayCourses;
			this.splitGroups(dayCourses);
			this.loading = false;

			this.updateCourseStatus();
		},

		onDaySelect(e) {
			this.selectedDay = e.currentTarget.dataset.day;
			this.filterDayCourses();
		},

		onCourseTap(e) {
			const { group, index } = e.currentTarget.dataset;
			const list = group === 'morning' ? this.morningCourses : group === 'afternoon' ? this.afternoonCourses : this.eveningCourses;
			const course = list[index];
			if (!course) return;

			const lines = [
				`课程：${course.courseName || '未知'}`,
				`时间：${course.weekDayName || ''} 第${(course.slots && course.slots[0]) || '-'}节`,
				`时段：${course.timeDisplay || '待定'}`,
				`地点：${course.location || '待定'}`,
				`教师：${course.teacher || '待定'}`,
				`周次：${course.weeks || '-'}`
			];

			uni.showModal({
				title: '课程详情',
				content: lines.join('\n'),
				showCancel: false,
				confirmText: '关闭'
			});
		},

		goToFullSchedule() {
			uni.navigateTo({ url: '/pages/academic-schedule/index' });
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
	-webkit-tap-highlight-color: transparent;
	user-select: none;
}
</style>

<style scoped>
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

.nav-action {
	padding: 0 30rpx;
}

.action-text {
	font-size: 26rpx;
	color: white;
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

.day-selector {
	display: flex;
	justify-content: space-between;
	background: white;
	border-radius: 20rpx;
	padding: 20rpx 10rpx;
	margin-top: 25rpx;
	margin-bottom: 24rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.day-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 16rpx 0;
	border-radius: 16rpx;
	transition: background-color 0.2s;
	position: relative;
}

.day-item.active {
	background: #13c2c2;
}

.day-item.active .day-name,
.day-item.active .day-date,
.day-item.active .day-tag {
	color: white;
}

.day-item.today:not(.active) .day-tag {
	color: #13c2c2;
}

.day-name {
	font-size: 22rpx;
	color: #948eb3;
	margin-bottom: 4rpx;
}

.day-date {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
}

.day-tag {
	font-size: 18rpx;
	color: #948eb3;
	margin-top: 2rpx;
}

.info-cards {
	display: flex;
	gap: 20rpx;
	margin-bottom: 24rpx;
}

.info-card {
	flex: 1;
	background: white;
	border-radius: 20rpx;
	padding: 24rpx;
	display: flex;
	align-items: center;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.info-icon {
	width: 48rpx;
	height: 48rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.info-content {
	flex: 1;
}

.info-title {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 8rpx;
}

.info-sub {
	font-size: 24rpx;
	color: #948eb3;
}

.status-current {
	color: #52c41a;
}

.status-free {
	color: #948eb3;
}

.status-ended {
	color: #52c41a;
}

.status-ending {
	color: #faad14;
}

.status-waiting {
	color: #1890ff;
}

.status-cards {
	display: flex;
	gap: 20rpx;
	margin-bottom: 24rpx;
}

.status-card {
	flex: 1;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
	background: white;
}

.status-card.current {
	border: 2rpx solid;
}

.status-card.next {
	border: 2rpx solid;
}

.status-card.ended {
	border: 2rpx solid #52c41a;
}

.status-card.waiting {
	border: 2rpx solid;
}

.status-header {
	padding: 20rpx 24rpx;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.status-card.ended .status-header {
	background: #52c41a;
}

.status-card.waiting .status-header {
	background: #1890ff;
}

.status-label {
	font-size: 26rpx;
	font-weight: bold;
	color: white;
	display: flex;
	align-items: center;
}

.status-label-icon {
	width: 24rpx;
	height: 24rpx;
	margin-right: 8rpx;
}

.status-label-icon.large {
	width: 32rpx;
	height: 32rpx;
	margin-right: 12rpx;
}

.status-time {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.9);
}

.status-body {
	background: white;
	padding: 24rpx;
}

.status-title {
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.status-info-row {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.status-info-item {
	font-size: 24rpx;
	color: #666666;
	display: flex;
	align-items: center;
}

.status-info:last-child {
	margin-bottom: 0;
}

.status-icon {
	width: 24rpx;
	height: 24rpx;
	margin-right: 8rpx;
	flex-shrink: 0;
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

.course-list-wrapper {
	display: flex;
	flex-direction: column;
	gap: 24rpx;
}

.course-section {
	background: white;
	border-radius: 20rpx;
	padding: 30rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.section-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 24rpx;
}

.section-title {
	display: flex;
	align-items: center;
	font-size: 30rpx;
	font-weight: bold;
	color: #48416f;
}

.section-icon {
	width: 32rpx;
	height: 32rpx;
	margin-right: 12rpx;
}

.section-count {
	font-size: 24rpx;
	color: #13c2c2;
	background: #e8f9f9;
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
}

.course-timeline {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.course-card-wrapper {
	display: flex;
	align-items: stretch;
	overflow: hidden;
	-webkit-tap-highlight-color: transparent;
	user-select: none;
}

.course-card-wrapper.ended .course-card {
	opacity: 0.6;
}

.course-card-wrapper.ended .course-time {
	color: #999999;
}

.course-card-wrapper.ended .course-info-item {
	color: #999999;
}

.timeline-left {
	display: flex;
	flex-direction: column;
	align-items: center;
	width: 40rpx;
	flex-shrink: 0;
	margin-right: 16rpx;
}

.timeline-dot {
	width: 16rpx;
	height: 16rpx;
	border-radius: 50%;
	flex-shrink: 0;
	margin: 24rpx 0;
	box-shadow: 0 0 0 4rpx rgba(0, 0, 0, 0.05);
}

.timeline-line-top,
.timeline-line-bottom {
	flex: 1;
	width: 2rpx;
	background: linear-gradient(to bottom, #e0e0e0, #d0d0d0);
}

.course-card {
	flex: 1;
	min-width: 0;
	background: white;
	border-radius: 16rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.03);
	cursor: pointer;
	transition: transform 0.2s ease, box-shadow 0.2s ease;
	overflow: hidden;
	-webkit-tap-highlight-color: transparent;
	user-select: none;
}

.course-card:active {
	transform: scale(0.99);
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}

.course-content {
	padding: 20rpx 24rpx;
}

.course-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.course-slot {
	font-size: 22rpx;
	font-weight: bold;
}

.course-time {
	font-size: 20rpx;
	color: #948eb3;
}

.course-name-row {
	display: flex;
	align-items: center;
	margin-bottom: 8rpx;
}

.course-icon-book {
	width: 24rpx;
	height: 24rpx;
	margin-right: 10rpx;
	flex-shrink: 0;
	border-radius: 6rpx;
	position: relative;
	background: currentColor;
}

.course-icon-book::before {
	content: '';
	position: absolute;
	left: 6rpx;
	top: 4rpx;
	width: 2rpx;
	height: 16rpx;
	background: rgba(255, 255, 255, 0.6);
}

.course-icon-book::after {
	content: '';
	position: absolute;
	left: 10rpx;
	top: 6rpx;
	width: 2rpx;
	height: 12rpx;
	background: rgba(255, 255, 255, 0.4);
}

.course-name {
	flex: 1;
	min-width: 0;
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.course-info-row {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}

.course-info-item {
	font-size: 22rpx;
	color: #666666;
	display: flex;
	align-items: center;
}

.course-icon {
	width: 20rpx;
	height: 20rpx;
	margin-right: 6rpx;
	flex-shrink: 0;
}

.empty-wrapper {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 0;
}

.empty-icon {
	width: 100rpx;
	height: 100rpx;
	margin-bottom: 20rpx;
}

.empty-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.empty-text {
	font-size: 26rpx;
	color: #948eb3;
}
</style>
