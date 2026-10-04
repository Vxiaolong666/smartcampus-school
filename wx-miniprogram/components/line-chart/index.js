/**
 * 通用折线图组件（微信小程序，与 uniapp 端 components/line-chart 保持一致）
 *
 * 用法：
 *   <line-chart chartData="{{[{ label: '10-01', value: 1.2 }]}}" color="#52c41a" unit="度" />
 *
 * properties：
 *   chartData  Array   [{ label, value }]，label 用于 X 轴与提示，value 为数值
 *   color      String  主色（折线 / 数据点 / 提示），可按业务动态传入（如健康度绿/橙/红）
 *   height     Number  画布高度（px）
 *   smooth     Boolean 是否使用平滑曲线
 *   zeroFloor  Boolean 负值是否按 0 处理（如电费充值为负）
 *   maxTicks   Number  X 轴最多显示的标签数
 *   unit       String  提示数值的单位（如「度」）
 *   precision  Number  提示数值的小数位（默认 2）
 *   animate    Boolean 是否播放进入动画（折线自基线升起，默认 true）
 *
 * 说明：微信小程序中 this.data 是组件数据对象，故数据属性命名为 chartData（属性名同项目其它组件，使用驼峰）。
 */

const { formatDateTime } = require('../../utils/common.js');

// 每个实例分配唯一 canvasId，避免同页多个图表互相覆盖
let uid = 0;

Component({
    properties: {
        chartData: { type: Array, value: [] },
        color: { type: String, value: '#007bff' },
        height: { type: Number, value: 200 },
        smooth: { type: Boolean, value: true },
        zeroFloor: { type: Boolean, value: true },
        maxTicks: { type: Number, value: 7 },
        unit: { type: String, value: '' },
        precision: { type: Number, value: 2 },
        animate: { type: Boolean, value: true }
    },

    data: {
        canvasId: '',
        active: null // 触摸提示：当前高亮的数据点
    },

    observers: {
        // 数据 / 高度 / 主题色变化时重绘（主题色随健康度动态变化）
        'chartData': function () {
            this.scheduleDraw();
        },
        'height, color': function () {
            this.scheduleDraw();
        }
    },

    lifetimes: {
        attached() {
            uid += 1;
            this._animToken = 0;
            this.setData({ canvasId: 'lineChart' + uid });
        },
        ready() {
            this.scheduleDraw();
        },
        detached() {
            this._animToken = (this._animToken || 0) + 1;
            if (this._animTimer) {
                clearTimeout(this._animTimer);
                this._animTimer = null;
            }
            if (this._tipTimer) {
                clearTimeout(this._tipTimer);
                this._tipTimer = null;
            }
        }
    },

    methods: {
        // 等布局完成后绘制（首帧 canvas 尺寸可能未就绪）
        scheduleDraw() {
            if (!this.data.canvasId) return;
            setTimeout(() => this.draw(), 50);
        },

        async draw() {
            const list = Array.isArray(this.data.chartData) ? this.data.chartData : [];
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
            if (this.data.active) this.setData({ active: null });

            const { ctx, width, height, left, top } = target;

            // 清屏
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);

            if (!list.length) {
                this._layout = null;
                return;
            }

            const padding = { top: 18, right: 14, bottom: 34, left: 44 };
            const chartWidth = Math.max(1, width - padding.left - padding.right);
            const chartHeight = Math.max(1, height - padding.top - padding.bottom);
            const baseY = height - padding.bottom;

            // 负值（电费充值）按 0 处理，避免折线跌破 0 轴溢出
            const values = list.map(d => {
                const v = Number(d && d.value) || 0;
                return this.data.zeroFloor ? Math.max(0, v) : v;
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

            const labelStep = Math.max(1, Math.ceil(n / this.data.maxTicks));
            const gridCount = 4;
            const color = this.data.color;

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
                ctx.fillStyle = this.hexToRgba(color, 0.14);
                ctx.fill();

                // 折线
                this.traceLine(ctx, drawn);
                ctx.strokeStyle = color;
                ctx.lineWidth = 2;
                ctx.stroke();

                // 数据点
                drawn.forEach(p => {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, 3, 0, 2 * Math.PI);
                    ctx.fillStyle = color;
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
            };

            if (this.data.animate) {
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
            if (this.data.smooth && n > 2) {
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

            // 触摸点相对画布的 X 坐标
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
            if (this.data.active && this.data.active.index === best) return;

            const p = layout.points[best];
            const above = p.y > 92;

            // 先按居中渲染（卡片宽度由内容决定），随后按实际宽度做边界纠正
            this.setData({
                active: this.buildActive({
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
                })
            });

            this.scheduleMeasure(best);
        },

        // 依据卡片实际宽度做左右边界纠正：卡片不溢出且箭头对准数据点
        scheduleMeasure(index) {
            setTimeout(() => {
                wx.createSelectorQuery()
                    .in(this)
                    .select('.lc-card')
                    .boundingClientRect()
                    .exec(res => {
                        const layout = this._layout;
                        const a = this.data.active;
                        const rect = res && res[0];
                        if (!layout || !a || a.index !== index || !rect || !rect.width) return;

                        const cardW = rect.width;
                        let cardLeft = a.x - cardW / 2;
                        const maxLeft = layout.width - cardW - 4;
                        if (cardLeft > maxLeft) cardLeft = maxLeft;
                        if (cardLeft < 4) cardLeft = 4;

                        this.setData({
                            active: this.buildActive(Object.assign({}, a, {
                                cardLeft,
                                caretLeft: a.x - cardLeft
                            }))
                        });
                    });
            }, 30);
        },

        // 触摸结束：保留一小段时间后隐藏提示
        onTouchEnd() {
            if (this._tipTimer) clearTimeout(this._tipTimer);
            this._tipTimer = setTimeout(() => {
                this.setData({ active: null });
                this._tipTimer = null;
            }, 1200);
        },

        // 依据数据点生成（含内联样式字符串，便于 WXML 绑定）
        buildActive(a) {
            const color = this.data.color;
            const centeredCard = a.cardLeft === null || a.cardLeft === undefined;
            const centeredCaret = a.caretLeft === null || a.caretLeft === undefined;
            return Object.assign({}, a, {
                guideVStyle: 'left:' + a.x + 'px;top:' + a.guideTop + 'px;height:' + a.guideHeight + 'px;border-color:' + color + ';',
                guideHStyle: 'left:' + a.lineLeft + 'px;top:' + a.y + 'px;width:' + a.lineWidth + 'px;border-color:' + color + ';',
                dotStyle: 'left:' + a.x + 'px;top:' + a.y + 'px;border-color:' + color + ';',
                cardStyle: 'left:' + (centeredCard ? a.x : a.cardLeft) + 'px;top:' + a.cardTop + 'px;transform:' +
                    (centeredCard ? 'translateX(-50%) ' : '') + (a.above ? 'translateY(-100%)' : 'translateY(0)') + ';',
                caretStyle: 'left:' + (centeredCaret ? '50%' : (a.caretLeft + 'px')) + ';'
            });
        },

        // 提示数值：去掉多余的 0（单位在卡片中单独展示）
        formatTooltip(value) {
            const n = Number(value) || 0;
            let s = n.toFixed(Math.max(0, this.data.precision));
            if (s.indexOf('.') > -1) {
                s = s.replace(/0+$/, '').replace(/\.$/, '');
            }
            return s;
        },

        // 获取画布上下文（微信小程序 Canvas 2D）
        getCanvasContext() {
            const canvasId = this.data.canvasId;
            return new Promise(resolve => {
                wx.createSelectorQuery()
                    .in(this)
                    .select('#' + canvasId)
                    .fields({ node: true, size: true, rect: true })
                    .exec(res => {
                        if (!res || !res[0] || !res[0].node) {
                            resolve(null);
                            return;
                        }
                        const node = res[0].node;
                        const ctx = node.getContext('2d');
                        const width = res[0].width;
                        const height = res[0].height;
                        const dpr = wx.getSystemInfoSync().pixelRatio || 1;
                        node.width = width * dpr;
                        node.height = height * dpr;
                        ctx.scale(dpr, dpr);
                        resolve({ ctx, width, height, left: res[0].left, top: res[0].top });
                    });
            });
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
});
