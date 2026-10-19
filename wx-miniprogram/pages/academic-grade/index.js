/**
 * 成绩查询页面
 * 展示学生成绩信息和统计
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');
const markdownToHtml = require('../../utils/markdown.js');

Page({
  data: {
    semesters: [],           // 学期列表
    semesterIndex: 0,        // 当前选中的学期索引
    currentSemester: '',     // 当前学期ID
    loading: true,           // 加载状态（初始为 true，避免首屏闪现空态）
    needBind: false,         // 是否未绑定教务账号
    grades: [],              // 成绩列表
    summary: {               // 成绩统计
      totalCourses: 0,       // 总课程数
      totalCredits: 0,       // 总学分
      avgGpa: 0,             // 平均绩点
      earnedCredits: 0       // 已获学分
    },
    announcement: null,      // 公告信息
    showAiResult: false,     // AI 分析结果浮层
    aiResult: '',            // AI 分析结果（Markdown）
    aiResultHtml: '',        // 渲染用 HTML
    aiError: '',             // 分析失败提示（空字符串表示无错误）
    aiLoading: false,        // 是否正在分析
    aiCache: {},             // 分析结果缓存（按学期）
    windowHeight: 0,
    sheetHeight: 0,          // 浮层高度（视口 80%）
    // 成绩详情自定义弹窗
    showDetail: false,
    detailItem: {},
    detailTint: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
  },

  //页面加载
  onLoad(options) {
    // 检查用户是否已登录
    if (!api.isLoggedIn()) {
      // 先说明登录用途（合规），再跳转登录页
      api.goLoginWithTip();
      return;
    }
    
    // 检查功能开关
    this.checkFeature().then(canProceed => {
      if (!canProceed) return;
      // 加载学期列表
      this.loadSemesters();
    });
  },

  //检查功能开关
  async checkFeature() {
    try {
      const result = await feature.checkFeature('grade');
      if (!result.enabled) {
        this.setData({ announcement: null });
        feature.showDisabledAlert(result.featureName, result.announcement);
        return false;
      }
      if (result.announcement) {
        this.setData({ announcement: result.announcement });
      }
      return true;
    } catch (err) {
      console.error('检查功能开关失败', err);
      return true;
    }
  },

  //加载学期列表
  loadSemesters() {
    // 设置加载状态
    this.setData({ loading: true, needBind: false });

    // 调用API获取学期列表
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

        // 更新页面数据：学期列表、选中索引、当前学期、取消加载状态
        this.setData({
          semesters: semesters,
          semesterIndex: 0,
          currentSemester: defaultOption.semesterId,
          loading: false
        });

        this.loadGrades();
      })
      .catch(err => {
        // 未绑定教务账号：展示「请先绑定」空态，不弹「无数据」提示
        if (feature.isAcademicNotBound(err)) {
          this.setData({ loading: false, needBind: true });
          return;
        }
        // 获取学期列表失败，取消加载状态并提示
        this.setData({ loading: false });
        wx.showToast({ title: err.msg || '获取学期列表失败', icon: 'none' });
      });
  },

  //学期选择变化
  onSemesterChange(e) {
    // 获取picker选中的索引
    const index = e.detail.value;
    // 根据索引取出对应学期对象
    const semester = this.data.semesters[index];
    
    // 更新选中的学期索引和学期ID
    this.setData({ 
      semesterIndex: index,
      currentSemester: semester.semesterId
    });
    
    // 切换学期后重新加载成绩
    this.loadGrades();
  },

  //加载成绩数据
  loadGrades() {
    // 解构获取当前选中的学期ID（为空时后端使用后台配置学期）
    const { currentSemester } = this.data;

    // 设置加载状态
    this.setData({ loading: true });

    // 调用API查询指定学期的成绩
    api.queryGrade(currentSemester)
      .then(res => {
        // 取出原始成绩数据，无数据时默认空数组
        const rawData = res.data || [];
        // 处理后的成绩列表
        let grades = [];
        // 成绩统计摘要
        let summary = {
          totalCourses: 0,     // 总课程数
          totalCredits: 0,     // 总学分
          avgGpa: 0,           // 平均绩点
          earnedCredits: 0     // 已获学分
        };

        // 如果有成绩数据且achievement数组不为空
        if (rawData.length > 0 && rawData[0].achievement && rawData[0].achievement.length > 0) {
          // 遍历achievement数组，将后端字段映射为前端展示字段
          grades = rawData[0].achievement.map(item => ({
            courseName: item.courseName || '',              // 课程名称
            score: item.fraction || '-',                    // 分数，无值时显示'-'
            scoreClass: this.scoreClass(item.fraction),     // 分数颜色：<60 红；>=85 绿；其它蓝
            credit: item.credit || 0,                       // 学分
            gpa: item.gpa !== undefined ? item.gpa : this.calculateGpa(parseFloat(item.fraction)),  // 绩点：有则用后端值，无则根据分数计算
            courseType: item.curriculumAttributes || ''     // 课程类型（必修/选修等）
          }));

          // 计算成绩统计摘要
          summary = {
            totalCourses: grades.length,                    // 总课程数
            totalCredits: grades.reduce((sum, g) => sum + (parseFloat(g.credit) || 0), 0).toFixed(1),  // 总学分，累加并保留1位小数
            avgGpa: rawData[0].pjxfjd || '-',               // 平均绩点，取后端计算值
            earnedCredits: rawData[0].yxzxf || '-'          // 已获学分，取后端计算值
          };
        }

        // 更新页面数据：成绩列表、统计摘要、取消加载状态
        this.setData({ 
          grades: grades,
          summary: summary,
          loading: false 
        });
      })
      .catch(err => {
        // 查询失败，取消加载状态并提示
        this.setData({ loading: false });
        wx.showToast({ title: err.msg || '查询失败', icon: 'none' });
      });
  },

  //成绩颜色：60 以下红；85 及以上绿；其它（含非数字）蓝（主题色）
  scoreClass(score) {
    const n = parseFloat(score);
    if (!isNaN(n)) {
      if (n < 60) return 'fail';
      if (n >= 85) return 'excellent';
    }
    return 'normal';
  },

  //根据分数计算绩点
  calculateGpa(score) {
    // 如果不是数字，返回 '-'
    if (isNaN(score)) return '-';
    
    // 根据分数段返回对应绩点
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

  //打开成绩详情（自定义弹窗，与课程详情风格一致）
  openDetail(e) {
    const index = e.currentTarget.dataset.index;
    const item = this.data.grades[index];
    if (!item) return;
    const tints = {
      fail: { text: '#ff4d4f', bg: '#fff0f0', light: '#ff7875', shadow: 'rgba(255, 77, 79, 0.3)' },
      excellent: { text: '#52c41a', bg: '#f6ffed', light: '#95de64', shadow: 'rgba(82, 196, 26, 0.3)' },
      normal: { text: '#13c2c2', bg: '#e8f9f9', light: '#36cfc9', shadow: 'rgba(19, 194, 194, 0.3)' }
    };
    this.setData({
      detailTint: tints[item.scoreClass] || tints.normal,
      detailItem: item,
      showDetail: true
    });
  },

  //关闭成绩详情
  closeDetail() {
    this.setData({ showDetail: false, detailItem: {} });
  },

  // 跳转教务账号绑定页
  goToBind() {
    wx.navigateTo({ url: '/pages/academic-bind/index' });
  },

  // ===== AI 智能分析成绩 =====
  async aiAnalyze() {
    if (this.data.aiLoading) {
      this.setData({ showAiResult: true });
      wx.showToast({ title: 'AI 正在分析...', icon: 'none' });
      return;
    }
    if (this.data.grades.length === 0) {
      wx.showToast({ title: '暂无成绩数据', icon: 'none' });
      return;
    }
    const semesterName = (this.data.semesters[this.data.semesterIndex] && this.data.semesters[this.data.semesterIndex].semesterName) || '';
    if (this.data.aiCache[semesterName]) {
      this.setData({
        aiError: '',
        aiResult: this.data.aiCache[semesterName],
        aiResultHtml: markdownToHtml(this.data.aiCache[semesterName]),
        showAiResult: true
      });
      this.initSheetHeight();
      return;
    }
    // 先算好浮层高度再展示，避免首帧 sheetHeight 为 0 的遮罩空窗
    this.initSheetHeight();
    this.setData({
      aiLoading: true,
      aiError: '',
      aiResult: '',
      aiResultHtml: '',
      showAiResult: true
    });
    const payload = {
      achievement: this.data.grades.map(g => ({
        courseName: g.courseName,
        fraction: g.score,
        credit: g.credit,
        gpa: g.gpa,
        curriculumAttributes: g.courseType
      })),
      gpa: this.data.summary.avgGpa,
      credit: this.data.summary.earnedCredits,
      semester: semesterName
    };
    try {
      const res = await api.aiAnalyzeGrades(payload);
      const content = (res.data && res.data.content) ? String(res.data.content).trim() : '';
      if (!content) {
        this.setData({ aiError: 'AI 未返回有效内容，请稍后重试' });
        return;
      }
      const cache = Object.assign({}, this.data.aiCache);
      cache[semesterName] = content;
      this.setData({
        aiCache: cache,
        aiError: '',
        aiResult: content,
        aiResultHtml: markdownToHtml(content),
        showAiResult: true
      });
    } catch (err) {
      this.setData({ aiError: (err && err.msg) || '网络异常，请检查网络后重试' });
    } finally {
      this.setData({ aiLoading: false });
    }
  },

  //关闭 AI 分析结果浮层
  closeAiResult() {
    this.setData({ showAiResult: false });
  },

  //初始化浮层高度（视口 80%）
  initSheetHeight() {
    let h = this.data.windowHeight;
    if (!h) {
      try {
        h = wx.getSystemInfoSync().windowHeight || 0;
      } catch (e) {
        h = 0;
      }
      this.setData({ windowHeight: h });
    }
    // 兜底 600px：极端情况下取不到视口高度时，仍保证浮层有可见面板而不是 0 高度
    h = h || 600;
    this.setData({ sheetHeight: Math.round(h * 0.8) });
  },

  // ===== AI 浮层拖拽结束：同步 WXS 计算出的最终高度 =====
  // 拖动过程由 WXS（utils/ai-drag.wxs）直接修改视图层样式，避免高频 setData 造成卡顿
  onSheetDragEnd(e) {
    const height = e && e.detail && e.detail.height ? Math.round(e.detail.height) : 0;
    if (height > 0) this.setData({ sheetHeight: height });
  },

  noop() {}
});
