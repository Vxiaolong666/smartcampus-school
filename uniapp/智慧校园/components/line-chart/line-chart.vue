<template>
	<view
		class="line-chart"
		:style="{ height: height + 'px' }"
		@touchstart="onTouch"
		@touchmove="onTouch"
		@touchend="onTouchEnd"
		@touchcancel="onTouchEnd"
	>
		<!-- #ifdef MP-WEIXIN -->
		<canvas
			class="line-chart-canvas"
			type="2d"
			:id="canvasId"
			:style="{ width: '100%', height: height + 'px' }"
		></canvas>
		<!-- #endif -->
		<!-- #ifndef MP-WEIXIN -->
		<canvas
			class="line-chart-canvas"
			:id="canvasId"
			:canvas-id="canvasId"
			:style="{ width: '100%', height: height + 'px' }"
		></canvas>
		<!-- #endif -->

		<!-- 触摸交互层：十字指示虚线 + 高亮数据点 + 数值卡片 -->
		<view v-if="active" class="lc-overlay">
			<view class="lc-guide" :style="guideVStyle"></view>
			<view class="lc-guide-x" :style="guideHStyle"></view>
			<view class="lc-dot" :style="dotStyle"></view>
			<view class="lc-card" :style="cardStyle">
				<view class="lc-card-head">
					<view class="lc-card-marker" :style="{ backgroundColor: color }"></view>
					<text class="lc-card-label">{{ active.label }}</text>
				</view>
				<view class="lc-card-value-row">
					<text class="lc-card-value" :style="{ color: color }">{{ active.valueText }}</text>
					<text v-if="unit" class="lc-card-unit">{{ unit }}</text>
				</view>
				<view
					class="lc-card-caret"
					:class="{ 'is-below': !active.above }"
					:style="caretStyle"
				></view>
			</view>
		</view>
	</view>
</template>

<script>
/**
 * 通用折线图组件（免第三方依赖，跨 H5 / App / 小程序）
 *
 * 用法：
 *   <line-chart :data="[{ label: '10-01', value: 1.2 }]" color="#52c41a" unit="度" />
 *
 * props：
 *   data       Array   [{ label, value }]，label 用于 X 轴与提示，value 为数值
 *   color      String  主色（折线 / 数据点 / 提示），可按业务动态传入（如健康度绿/橙/红）
 *   height     Number  画布高度（px）
 *   smooth     Boolean 是否使用平滑曲线
 *   zeroFloor  Boolean 负值是否按 0 处理（如电费充值为负）
 *   maxTicks   Number  X 轴最多显示的标签数
 *   unit       String  提示数值的单位（如「度」）
 *   precision  Number  提示数值的小数位（默认 2）
 *   animate    Boolean 是否播放进入动画（折线自基线升起，默认 true）
 */

import { formatDateTime } from '../../utils/common.js';

// 每个实例分配唯一 canvasId，避免同页多个图表互相覆盖
let uid = 0;

export default {
	name: 'line-chart',
	props: {
		data: { type: Array, default: () => [] },
		color: { type: String, default: '#007bff' },
		height: { type: Number, default: 200 },
		smooth: { type: Boolean, default: true },
		zeroFloor: { type: Boolean, default: true },
		maxTicks: { type: Number, default: 7 },
		unit: { type: String, default: '' },
		precision: { type: Number, default: 2 },
		animate: { type: Boolean, default: true }
	},
	data() {
		uid += 1;
		return {
			canvasId: 'lineChart' + uid,
			active: null // 触摸提示：当前高亮的数据点
		};
	},
	computed: {
		guideVStyle() {
			const a = this.active;
			if (!a) return {};
			return {
				left: a.x + 'px',
				top: a.guideTop + 'px',
				height: a.guideHeight + 'px',
				borderColor: this.color
			};
		},
		guideHStyle() {
			const a = this.active;
			if (!a) return {};
			return {
				left: a.lineLeft + 'px',
				top: a.y + 'px',
				width: a.lineWidth + 'px',
				borderColor: this.color
			};
		},
		dotStyle() {
			const a = this.active;
			if (!a) return {};
			return { left: a.x + 'px', top: a.y + 'px', borderColor: this.color };
		},
		// 卡片宽度自适应内容：未测量前用 translateX(-50%) 居中，测量后按实际宽度定位
		cardStyle() {
			const a = this.active;
			if (!a) return {};
			const centered = a.cardLeft === null || a.cardLeft === undefined;
			const ty = a.above ? 'translateY(-100%)' : 'translateY(0)';
			return {
				left: (centered ? a.x : a.cardLeft) + 'px',
				top: a.cardTop + 'px',
				transform: (centered ? 'translateX(-50%) ' : '') + ty
			};
		},
		caretStyle() {
			const a = this.active;
			if (!a) return {};
			const centered = a.caretLeft === null || a.caretLeft === undefined;
			return { left: centered ? '50%' : a.caretLeft + 'px' };
		}
	},
	watch: {
		// 数据 / 高度 / 主题色变化时重绘（主题色随健康度动态变化）
		data: {
			handler() {
				this.scheduleDraw();
			},
			deep: true
		},
		height() {
			this.scheduleDraw();
		},
		color() {
			this.scheduleDraw();
		}
	},
	mounted() {
		this.scheduleDraw();
	},
	beforeUnmount() {
		this._animToken = (this._animToken || 0) + 1;
		if (this._animTimer) {
			clearTimeout(this._animTimer);
			this._animTimer = null;
		}
		if (this._tipTimer) {
			clearTimeout(this._tipTimer);
			this._tipTimer = null;
		}
	},
	methods: {
		// 等布局完成后绘制（首帧 canvas 尺寸可能未就绪）
		scheduleDraw() {
			this.$nextTick(() => {
				setTimeout(() => this.draw(), 50);
			});
		},

		async draw() {
			const list = Array.isArray(this.data) ? this.data : [];
			const target = await this.getCanvasContext();
			if (!target) return;

			// 取消进行中的进入动画
			this._animToken = (this._animToken || 0) + 1;
			const token = this._animToken;
			if (this._animTimer) {
				clearTimeout(this._animTimer);
				this._animTimer = null;
			}

			// 数据变化时清除触摸提示，避免残留旧点位
			this.active = null;

			const { ctx, width, height, left, top, legacy } = target;

			// 清屏
			ctx.fillStyle = '#ffffff';
			ctx.fillRect(0, 0, width, height);

			if (!list.length) {
				this._layout = null;
				if (legacy && ctx.draw) ctx.draw();
				return;
			}

			const padding = { top: 18, right: 14, bottom: 34, left: 44 };
			const chartWidth = Math.max(1, width - padding.left - padding.right);
			const chartHeight = Math.max(1, height - padding.top - padding.bottom);
			const baseY = height - padding.bottom;

			// 负值（电费充值）按 0 处理，避免折线跌破 0 轴溢出
			const values = list.map(d => {
				const v = Number(d && d.value) || 0;
				return this.zeroFloor ? Math.max(0, v) : v;
			});

			let maxVal = Math.max.apply(null, values);
			let minVal = Math.min.apply(null, values);
			if (maxVal === minVal) maxVal = minVal + 1;
			const span = maxVal - minVal || 1;
			const yTop = maxVal + span * 0.12;
			const yBottom = Math.max(0, minVal - span * 0.12);
			const yRange = (yTop - yBottom) || 1;

			// 数据点坐标
			const n = values.length;
			const xStep = n > 1 ? chartWidth / (n - 1) : 0;
			const points = values.map((v, i) => {
				const x = padding.left + (n > 1 ? i * xStep : chartWidth / 2);
				const y = baseY - ((v - yBottom) / yRange) * chartHeight;
				return { x, y, value: v, label: String((list[i] && list[i].label) || '') };
			});

			// 记录布局与点位（最终状态），供触摸交互定位最近数据点
			this._layout = { left: left || 0, top: top || 0, width, height, padding, baseY, points };

			const labelStep = Math.max(1, Math.ceil(n / this.maxTicks));
			const gridCount = 4;

			// 单帧绘制：progress 0→1 时折线 / 面积自基线升起
			const paint = progress => {
				ctx.fillStyle = '#ffffff';
				ctx.fillRect(0, 0, width, height);

				// 横向网格 + Y 轴刻度
				for (let i = 0; i <= gridCount; i++) {
					const y = padding.top + (chartHeight * i) / gridCount;
					ctx.beginPath();
					ctx.moveTo(padding.left, y);
					ctx.lineTo(width - padding.right, y);
					ctx.strokeStyle = '#f0f0f0';
					ctx.lineWidth = 1;
					ctx.stroke();

					const val = yTop - (yRange * i) / gridCount;
					ctx.fillStyle = '#999999';
					ctx.font = '10px sans-serif';
					ctx.textAlign = 'right';
					ctx.fillText(this.formatValue(val), padding.left - 5, y + 3);
				}

				const drawn = points.map(p => ({ x: p.x, y: baseY + (p.y - baseY) * progress }));

				// 面积填充
				this.traceLine(ctx, drawn);
				ctx.lineTo(drawn[n - 1].x, baseY);
				ctx.lineTo(drawn[0].x, baseY);
				ctx.closePath();
				ctx.fillStyle = this.hexToRgba(this.color, 0.14);
				ctx.fill();

				// 折线
				this.traceLine(ctx, drawn);
				ctx.strokeStyle = this.color;
				ctx.lineWidth = 2;
				ctx.stroke();

				// 数据点
				drawn.forEach(p => {
					ctx.beginPath();
					ctx.arc(p.x, p.y, 3, 0, 2 * Math.PI);
					ctx.fillStyle = this.color;
					ctx.fill();
					ctx.strokeStyle = '#ffffff';
					ctx.lineWidth = 1.5;
					ctx.stroke();
				});

				// X 轴标签（自动抽稀）
				ctx.fillStyle = '#666666';
				ctx.font = '9px sans-serif';
				ctx.textAlign = 'center';
				list.forEach((d, i) => {
					if (i % labelStep === 0 || i === n - 1) {
						const x = padding.left + (n > 1 ? i * xStep : chartWidth / 2);
						ctx.fillText(this.formatLabel(d && d.label), x, baseY + 16);
					}
				});

				if (legacy && ctx.draw) ctx.draw();
			};

			if (this.animate) {
				const start = Date.now();
				const duration = 620;
				const ease = t => 1 - Math.pow(1 - t, 3);
				const step = () => {
					if (token !== this._animToken) return;
					const t = Math.min(1, (Date.now() - start) / duration);
					paint(ease(t));
					if (t < 1) {
						this._animTimer = setTimeout(step, 16);
					} else {
						this._animTimer = null;
					}
				};
				step();
			} else {
				paint(1);
			}
		},

		// 描出折线路径：Catmull-Rom 平滑，曲线严格经过每个数据点
		traceLine(ctx, points) {
			ctx.beginPath();
			ctx.moveTo(points[0].x, points[0].y);
			const n = points.length;
			if (this.smooth && n > 2) {
				for (let i = 0; i < n - 1; i++) {
					const p0 = points[i - 1] || points[i];
					const p1 = points[i];
					const p2 = points[i + 1];
					const p3 = points[i + 2] || points[i + 1];
					// Catmull-Rom 转三次贝塞尔：控制点由相邻点推导，曲线必然经过 p1、p2
					const c1x = p1.x + (p2.x - p0.x) / 6;
					const c2x = p2.x - (p3.x - p1.x) / 6;
					let c1y = p1.y + (p2.y - p0.y) / 6;
					let c2y = p2.y - (p3.y - p1.y) / 6;
					// 约束控制点高度：避免峰谷处过冲（压过峰顶或跌破 0 轴）
					const lo = Math.min(p1.y, p2.y);
					const hi = Math.max(p1.y, p2.y);
					c1y = Math.min(hi, Math.max(lo, c1y));
					c2y = Math.min(hi, Math.max(lo, c2y));
					ctx.bezierCurveTo(c1x, c1y, c2x, c2y, p2.x, p2.y);
				}
			} else {
				for (let i = 1; i < n; i++) {
					ctx.lineTo(points[i].x, points[i].y);
				}
			}
		},

		// 触摸：定位最近数据点并显示提示（只更新覆盖层，不重绘画布）
		onTouch(e) {
			const layout = this._layout;
			if (!layout || !layout.points || !layout.points.length) return;

			const t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0]) || null;
			if (!t) return;

			// 触摸点相对画布的 X 坐标（兼容小程序 x 与 H5/App clientX）
			let localX;
			if (typeof t.clientX === 'number') {
				localX = t.clientX - (layout.left || 0);
			} else if (typeof t.x === 'number') {
				localX = t.x;
			} else {
				return;
			}

			let best = 0;
			let bestDist = Infinity;
			layout.points.forEach((p, i) => {
				const d = Math.abs(p.x - localX);
				if (d < bestDist) {
					bestDist = d;
					best = i;
				}
			});

			if (this._tipTimer) {
				clearTimeout(this._tipTimer);
				this._tipTimer = null;
			}

			// 同一数据点无需重复计算
			if (this.active && this.active.index === best) return;

			const p = layout.points[best];
			const above = p.y > 92;

			// 先按居中渲染（卡片宽度由内容决定），随后按实际宽度做边界纠正
			this.active = {
				index: best,
				x: p.x,
				y: p.y,
				cardLeft: null,
				caretLeft: null,
				cardTop: above ? p.y - 12 : p.y + 14,
				above,
				guideTop: layout.padding.top,
				guideHeight: Math.max(0, layout.baseY - layout.padding.top),
				lineLeft: layout.padding.left,
				lineWidth: Math.max(0, layout.width - layout.padding.left - layout.padding.right),
				label: formatDateTime(p.label),
				valueText: this.formatTooltip(p.value)
			};

			this.scheduleMeasure(best);
		},

		// 依据卡片实际宽度做左右边界纠正：卡片不溢出且箭头对准数据点
		scheduleMeasure(index) {
			this.$nextTick(() => {
				uni.createSelectorQuery()
					.in(this)
					.select('.lc-card')
					.boundingClientRect()
					.exec(res => {
						const layout = this._layout;
						const a = this.active;
						const rect = res && res[0];
						if (!layout || !a || a.index !== index || !rect || !rect.width) return;

						const cardW = rect.width;
						let cardLeft = a.x - cardW / 2;
						const maxLeft = layout.width - cardW - 4;
						if (cardLeft > maxLeft) cardLeft = maxLeft;
						if (cardLeft < 4) cardLeft = 4;

						this.active = Object.assign({}, a, {
							cardLeft,
							caretLeft: a.x - cardLeft
						});
					});
			});
		},

		// 触摸结束：保留一小段时间后隐藏提示
		onTouchEnd() {
			if (this._tipTimer) clearTimeout(this._tipTimer);
			this._tipTimer = setTimeout(() => {
				this.active = null;
				this._tipTimer = null;
			}, 1200);
		},

		// 提示数值：去掉多余的 0（单位在卡片中单独展示）
		formatTooltip(value) {
			const n = Number(value) || 0;
			let s = n.toFixed(Math.max(0, this.precision));
			if (s.indexOf('.') > -1) {
				s = s.replace(/0+$/, '').replace(/\.$/, '');
			}
			return s;
		},

		// 统一获取画布上下文：微信小程序用 Canvas 2D，其他平台用旧版 canvas-id
		getCanvasContext() {
			const canvasId = this.canvasId;
			return new Promise(resolve => {
				// #ifdef MP-WEIXIN
				uni.createSelectorQuery()
					.in(this)
					.select('#' + canvasId)
					.fields({ node: true, size: true, rect: true })
					.exec(res => {
						if (!res || !res[0] || !res[0].node) {
							resolve(null);
							return;
						}
						const canvas = res[0].node;
						const ctx = canvas.getContext('2d');
						const width = res[0].width;
						const height = res[0].height;
						const left = res[0].left;
						const top = res[0].top;
						const sysInfo = uni.getWindowInfo ? uni.getWindowInfo() : uni.getSystemInfoSync();
						const dpr = sysInfo.pixelRatio || 1;
						canvas.width = width * dpr;
						canvas.height = height * dpr;
						ctx.scale(dpr, dpr);
						resolve({ ctx, width, height, left, top, legacy: false });
					});
				// #endif

				// #ifndef MP-WEIXIN
				uni.createSelectorQuery()
					.in(this)
					.select('#' + canvasId)
					.boundingClientRect()
					.exec(res => {
						const rect = res && res[0];
						const width = (rect && rect.width) || 320;
						const height = (rect && rect.height) || this.height;
						const left = (rect && rect.left) || 0;
						const top = (rect && rect.top) || 0;
						const ctx = uni.createCanvasContext(canvasId, this);
						resolve({ ctx: this.wrapLegacyContext(ctx), width, height, left, top, legacy: true });
					});
				// #endif
			});
		},

		// 将旧版 canvas 上下文适配为 Canvas 2D 风格接口
		wrapLegacyContext(ctx) {
			return {
				set fillStyle(v) {
					ctx.setFillStyle(v);
				},
				set strokeStyle(v) {
					ctx.setStrokeStyle(v);
				},
				set lineWidth(v) {
					ctx.setLineWidth(v);
				},
				set font(v) {
					const m = /(\d+)px/.exec(v);
					if (m) ctx.setFontSize(parseInt(m[1], 10));
				},
				set textAlign(v) {
					ctx.setTextAlign(v);
				},
				fillRect: (...a) => ctx.fillRect(...a),
				beginPath: () => ctx.beginPath(),
				moveTo: (...a) => ctx.moveTo(...a),
				lineTo: (...a) => ctx.lineTo(...a),
				quadraticCurveTo: (...a) => ctx.quadraticCurveTo(...a),
				bezierCurveTo: (...a) => ctx.bezierCurveTo(...a),
				stroke: () => ctx.stroke(),
				fill: () => ctx.fill(),
				closePath: () => ctx.closePath(),
				arc: (...a) => ctx.arc(...a),
				fillText: (...a) => ctx.fillText(...a),
				scale: () => {},
				draw: () => ctx.draw()
			};
		},

		// X 轴标签：去掉 YYYY- / YYYY/ 年份前缀，保留 9/5（或 9/21 12:00）
		formatLabel(label) {
			const s = String(label == null ? '' : label);
			return s.replace(/^\d{4}[-\/]/, '');
		},

		// Y 轴刻度值：整数直接显示，小数保留 2 位
		formatValue(v) {
			const n = Number(v) || 0;
			if (Math.abs(n - Math.round(n)) < 0.005) return String(Math.round(n));
			return n.toFixed(2);
		},

		hexToRgba(hex, alpha) {
			let h = String(hex || '').replace('#', '');
			if (h.length === 3) h = h.split('').map(c => c + c).join('');
			const r = parseInt(h.slice(0, 2), 16) || 0;
			const g = parseInt(h.slice(2, 4), 16) || 0;
			const b = parseInt(h.slice(4, 6), 16) || 0;
			return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
		}
	}
};
</script>

<style scoped>
.line-chart {
	position: relative;
	width: 100%;
}

.line-chart-canvas {
	width: 100%;
	display: block;
}

/* ===== 触摸交互层 ===== */
.lc-overlay {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	z-index: 5;
}

/* 垂直指示线（虚线，颜色由内联 borderColor 指定） */
.lc-guide {
	position: absolute;
	width: 0;
	border-left: 1px dashed;
	opacity: 0.55;
}

/* 水平指示线（与垂直指示线组成十字准星） */
.lc-guide-x {
	position: absolute;
	height: 0;
	border-top: 1px dashed;
	opacity: 0.55;
}

/* 高亮数据点：白色内芯 + 主题色描边 */
.lc-dot {
	position: absolute;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	background-color: #ffffff;
	border: 3px solid #007bff;
	box-sizing: border-box;
	transform: translate(-50%, -50%);
	box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
}

/* 数值卡片（宽度自适应内容，最大 220px） */
.lc-card {
	position: absolute;
	box-sizing: border-box;
	max-width: 220px;
	padding: 11px 14px;
	background-color: #ffffff;
	border-radius: 10px;
	box-shadow: 0 6px 20px rgba(50, 44, 84, 0.16);
	animation: lcCardIn 0.16s ease-out;
}

@keyframes lcCardIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

.lc-card-head {
	display: flex;
	align-items: center;
}

.lc-card-marker {
	width: 6px;
	height: 6px;
	border-radius: 50%;
	margin-right: 6px;
	flex-shrink: 0;
}

.lc-card-label {
	flex: 1;
	min-width: 0;
	font-size: 11px;
	line-height: 1.4;
	color: #8a86a0;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.lc-card-value-row {
	display: flex;
	align-items: baseline;
	margin-top: 4px;
}

.lc-card-value {
	font-size: 18px;
	font-weight: bold;
	line-height: 1.2;
}

.lc-card-unit {
	margin-left: 3px;
	font-size: 11px;
	color: #8a86a0;
}

/* 卡片箭头 */
.lc-card-caret {
	position: absolute;
	left: 50%;
	bottom: -4px;
	width: 10px;
	height: 10px;
	background-color: #ffffff;
	border-radius: 2px;
	transform: translateX(-50%) rotate(45deg);
}

.lc-card-caret.is-below {
	bottom: auto;
	top: -4px;
}
</style>
