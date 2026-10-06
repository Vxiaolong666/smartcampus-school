<template>
	<view class="container">
		<cb-nav-bar :show-back="false" compact>
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

			<view class="day-selector" v-if="!needBind">
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

			<view class="info-cards" v-if="!needBind">
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
								<text class="status-free">非本日课程</text>
							</block>
							<block v-else-if="allCoursesEnded">
								<text class="status-ended">今日课程结束了</text>
							</block>
							<block v-else-if="isEndingSoon">
								<text class="status-ending">即将下课</text>
							</block>
							<block v-else-if="currentCourse">
								<text class="status-current">{{ currentPeriod }}上课中</text>
							</block>
							<block v-else-if="inBreak && isStartingSoon">
								<text class="status-waiting">{{ nextPeriod }}课间休息</text>
							</block>
							<block v-else-if="notStarted && isStartingSoon">
								<text class="status-waiting">{{ nextPeriod }}即将上课</text>
							</block>
							<block v-else-if="notStarted || inBreak">
								<text class="status-free">{{ nextPeriod }}课程未开始</text>
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
						<text class="status-label">{{ nextPeriod }}课程未开始</text>
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
						<text class="status-label">{{ currentPeriod }}正在上课</text>
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
						<text class="status-label">{{ nextPeriod }}课间休息</text>
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

			<view v-else-if="needBind" class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-book-gray.svg" mode="widthFix"></image>
				<view class="empty-title">请先绑定教务账号</view>
				<view class="empty-text">绑定后可查看今日与本周课程安排</view>
				<button class="bind-btn" @click="goToBind">前往绑定</button>
			</view>

			<view v-else class="empty-wrapper">
				<image class="empty-icon" src="/static/assets/wxmp-book-gray.svg" mode="widthFix"></image>
				<view class="empty-title">{{ fullDayNames[selectedDay] }}没有课程</view>
				<view class="empty-text">
					{{ selectedDay === todayDayOfWeek ? '今天没有课程，好好休息一下吧~' : '这一天没有安排课程，看看其他天吧~' }}
				</view>
			</view>

			<view v-if="dayCourses.length > 0" class="ai-analyze-wrap">
				<view class="ai-analyze-btn" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
					<text v-if="!aiLoading">AI 智能分析今日安排</text>
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
					<text class="ai-modal-title">AI 智能日程分析</text>
					<text class="ai-modal-close" @click="closeAiResult">✕</text>
				</view>
				<scroll-view scroll-y="true" class="ai-modal-body" @touchstart="onAiBodyTouchStart" @touchmove.stop="onAiBodyTouchMove">
					<view class="ai-modal-body-inner">
						<rich-text :nodes="aiResultHtml" class="ai-modal-content"></rich-text>
					</view>
				</scroll-view>
			</view>
		</view>

		<!-- 右侧浮动 AI 分析按钮（圆形，tabBar 页面抬高避免被标签栏遮挡） -->
		<view class="ai-fab ai-fab--tab" :class="{ 'ai-loading': aiLoading }" @click="aiAnalyze">
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
							<text class="detail-sub-chip" :style="'background-color: ' + detailCourse.color.text + ';'">{{ detailCourse.weekDayName }} · 第{{ detailCourse.slots[0] }}节</text>
						</view>
					</view>
					<view class="detail-close" @click="closeDetail">✕</view>
				</view>

				<view class="detail-modal-body">
					<view class="detail-row" v-if="detailCourse.timeDisplay">
						<view class="detail-row-icon" :style="'background-color: ' + detailCourse.color.bg + ';'">
							<image class="detail-row-icon-img" :src="detailIcons.time" mode="aspectFit"></image>
						</view>
						<text class="detail-row-label">上课时段</text>
						<text class="detail-row-value">{{ detailCourse.timeDisplay }}</text>
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

					<button class="detail-btn" @click="closeDetail" :style="'background: linear-gradient(90deg, ' + detailCourse.color.text + ', ' + detailBtnBgLight + '); box-shadow: 0 10rpx 24rpx ' + detailBtnShadow + ';'">我知道了</button>
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
import { loadScheduleTimes as fetchScheduleTimes } from '../../utils/schedule-time.js';
import markdownToHtml from '../../utils/markdown.js';
import { aiScrollLockMixin } from '../../utils/scroll-lock.js';

const DAY_NAMES = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
const FULL_DAY_NAMES = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

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

/**
 * 判断两节课是否同一节，用于避免对象引用变化导致的无谓重渲染
 */
const sameCourse = (a, b) => {
	if (a === b) return true;
	if (!a || !b) return false;
	return a.courseName === b.courseName && a.slots[0] === b.slots[0];
};

export default {
	// AI 结果浮层弹出时锁定背景页面滚动，避免页面与浮层同时滚动
	mixins: [aiScrollLockMixin],

	data() {
		return {
			loading: true,
			needBind: false,
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
			_pageVisible: false,
			_pageInited: false,
			showAiResult: false,
			aiResult: '',
			aiLoading: false,
			aiCache: {},
			// 课程详情自定义弹窗
			showDetail: false,
			detailCourse: null,
			detailIcons: null,
			detailBtnBgLight: '#36cfc9',
			detailBtnShadow: 'rgba(19, 194, 194, 0.35)',
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
		// 当前正在上的课属于哪个时段（上午 / 下午 / 晚上）
		currentPeriod() {
			const c = this.currentCourse;
			if (!c || !c.slots || c.slots.length === 0) return '';
			const session = this.sectionSession(Math.min(...c.slots));
			return session === 'morning' ? '上午' : session === 'afternoon' ? '下午' : '晚上';
		},
		// 下一节课属于哪个时段
		nextPeriod() {
			const c = this.nextCourse;
			if (!c || !c.slots || c.slots.length === 0) return '';
			const session = this.sectionSession(Math.min(...c.slots));
			return session === 'morning' ? '上午' : session === 'afternoon' ? '下午' : '晚上';
		},
		// 距下节课是否足够近（≤30 分钟），用于区分「即将上课」与「课程未开始」，
		// 避免下节课还差很久（如 200 分钟）却显示「即将上课」
		isStartingSoon() {
			return (this.notStarted || this.inBreak) && this.minutesToNext > 0 && this.minutesToNext <= 30;
		},
		aiResultHtml() {
			return markdownToHtml(this.aiResult);
		}
	},

	onLoad() {
		try {
			this.windowHeight = uni.getSystemInfoSync().windowHeight || 0;
		} catch (e) {
			this.windowHeight = 0;
		}
		// 页面初始化统一放到 onShow：tab 页只加载一次，未登录提示必须每次进入都执行
	},

	onShow() {
		this._pageVisible = true;

		// 未登录：每次切到本页都要提示。tab 页的 onLoad 只会执行一次，
		// 若只在 onLoad 提示，第二次点「课程」Tab 就不会再提示。
		if (!api.isLoggedIn()) {
			// 先说明登录用途（合规），再跳转登录页
			api.goLoginWithTip({ mode: 'redirect' });
			return;
		}

		// 已登录：首次进入初始化数据，之后每次回到本页仅恢复计时
		if (this._pageInited) {
			this.startTimer();
			return;
		}
		this._pageInited = true;

		this.checkFeature().then(canProceed => {
			if (!canProceed) return;

			const today = new Date();
			const weekDates = this.getWeekDates();

			this.todayDayOfWeek = today.getDay();
			this.selectedDay = today.getDay();
			this.weekDates = weekDates;

			// 先用内置作息即时渲染，再拉后台「课程时间配置」，最后按后台作息查询课程
			this.updateTime();
			if (this._pageVisible) this.startTimer();

			fetchScheduleTimes().then(() => {
				this.loadSchedule();
				this.updateTime();
			});
		});
	},

	// 点击底部「课程」导航（含重复点击当前 Tab）：异步刷新课程表，不显示 loading
	onTabItemTap() {
		if (!api.isLoggedIn()) return;
		// 首次进入尚未初始化时交给 onShow 处理，避免重复/未初始化请求
		if (!this._pageInited) return;
		this.loadSchedule({ silent: true });
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

			// 节次按后台作息动态取（数量可能不是 11 节），避免漏判或取值报错
			const sectionKeys = Object.keys(timeSlots)
				.map(key => parseInt(key, 10))
				.filter(n => n > 0)
				.sort((a, b) => a - b);
			for (const i of sectionKeys) {
				const slot = timeSlots[i];
				if (!slot || !slot.time) continue;
				const [start, end] = slot.time.split('-');
				if (start && end && currentTime >= start && currentTime <= end) {
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

		//按节次时间判断所属时段（morning / afternoon / evening）。
		//不再写死节次边界：后台作息中「下午」可能不止 5~7 节（如下午到第 8 节），
		//写死「>=8 归晚上」会把下午最后一节误分到晚上。
		sectionSession(section) {
			const slot = timeSlots[section];
			const start = (slot && slot.time) ? String(slot.time).split('-')[0] : '';
			if (start) {
				const m = this.timeToMinutes(start);
				if (!isNaN(m)) {
					if (m < 12 * 60) return 'morning';
					if (m < 18 * 60) return 'afternoon';
					return 'evening';
				}
			}
			//取不到时间时回退到节次边界（兼容内置默认作息）
			if (section <= 4) return 'morning';
			if (section <= 7) return 'afternoon';
			return 'evening';
		},

		splitGroups(list) {
			const morning = [], afternoon = [], evening = [];
			list.forEach(c => {
				const session = this.sectionSession((c.slots && c.slots.length) ? c.slots[0] : 0);
				if (session === 'morning') morning.push(c);
				else if (session === 'afternoon') afternoon.push(c);
				else evening.push(c);
			});
			this.morningCourses = morning;
			this.afternoonCourses = afternoon;
			this.eveningCourses = evening;
		},

		// 节次上限：至少 12 节，后台作息配置更多时按配置
		maxSection() {
			let max = 0;
			Object.keys(timeSlots).forEach(key => {
				const n = parseInt(key, 10);
				if (n > max) max = n;
			});
			return Math.max(12, max || 11);
		},

		// options.silent = true 表示点击 Tab 静默刷新：不显示 loading，失败保留原数据、不打扰用户
		loadSchedule(options) {
			const silent = !!(options && options.silent);
			if (!silent) {
				this.loading = true;
				this.needBind = false;
			}
			api.querySchedule('', '')
				.then(res => this.processScheduleData(res.data || []))
				.catch(err => {
					if (feature.isAcademicNotBound(err)) {
						this.loading = false;
						this.needBind = true;
						return;
					}
					if (silent) return;
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
			// 节次上限按后台作息（至少 12 节），第 12 节及以后的课程不再被丢弃
			const maxSection = this.maxSection();

			for (let i = 0; i < timeStr.length; i += 2) {
				const section = parseInt(timeStr.substr(i, 2));
				if (section >= 1 && section <= maxSection) sections.push(section);
			}

			if (sections.length === 0) {
				for (let i = 0; i < timeStr.length; i++) {
					const section = parseInt(timeStr.charAt(i));
					if (section >= 1 && section <= maxSection) sections.push(section);
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

		//点击课程查看详情（自定义弹窗，与页面风格一致）
		onCourseTap(e) {
			const { group, index } = e.currentTarget.dataset;
			const list = group === 'morning' ? this.morningCourses : group === 'afternoon' ? this.afternoonCourses : this.eveningCourses;
			const course = list[index];
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

		goToFullSchedule() {
			uni.navigateTo({ url: '/pages/academic-schedule/index' });
		},

		// 跳转教务账号绑定页
		goToBind() {
			uni.navigateTo({ url: '/pages/academic-bind/index' });
		},

		async aiAnalyze() {
			if (this.aiLoading) {
			// 正在分析中允许再次点击：重新聚焦分析弹窗并提示，不重复发起请求
			this.aiResult = '### AI 正在分析…\n\n正在生成分析报告，请稍候......\n（该过程通常需要十几秒）';
			this.showAiResult = true;
			uni.showToast({ title: 'AI 正在分析...', icon: 'none' });
			return;
		}
			if (this.dayCourses.length === 0) {
				uni.showToast({ title: '今日暂无课程', icon: 'none' });
				return;
			}
			const key = 'k' + this.selectedDay;
			if (this.aiCache[key]) {
				this.aiResult = this.aiCache[key];
				this.showAiResult = true;
				return;
			}
			this.aiLoading = true;
			this.aiResult = '### AI 正在分析…\n\n正在读取今日课程安排并生成分析报告，请稍候......\n（该过程通常需要十几秒）';
			this.showAiResult = true;
			const courses = this.dayCourses.map(c => ({
				name: c.courseName,
				time: c.timeDisplay,
				location: c.location,
				teacher: c.teacher
			}));
			const dateInfo = (this.weekDates[this.selectedDay] && this.weekDates[this.selectedDay].full) || '';
			try {
				const res = await api.aiAnalyzeSchedule({
					type: 'today',
					dayName: this.fullDayNames[this.selectedDay],
					date: dateInfo,
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
