/**
 * 成绩查询页面
 * 展示学生成绩信息和统计
 */
const api = require('../../utils/api.js');
const feature = require('../../utils/feature.js');

Page({
  data: {
    semesters: [],           // 学期列表
    semesterIndex: 0,        // 当前选中的学期索引
    currentSemester: '',     // 当前学期ID
    loading: false,          // 加载状态
    grades: [],              // 成绩列表
    summary: {               // 成绩统计
      totalCourses: 0,       // 总课程数
      totalCredits: 0,       // 总学分
      avgGpa: 0,             // 平均绩点
      earnedCredits: 0       // 已获学分
    },
    announcement: null       // 公告信息
  },

  //页面加载
  onLoad(options) {
    // 检查用户是否已登录
    if (!api.isLoggedIn()) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      // 延迟1秒后跳转到登录页
      setTimeout(() => {
        wx.navigateTo({ url: '/pages/login/index' });
      }, 1000);
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
    this.setData({ loading: true });

    // 调用API获取学期列表
    api.getSemesterList()
      .then(res => {
        // 取出学期列表数据，无数据时默认空数组
        const semesters = res.data || [];
        // 当前选中的学期ID
        let currentSemester = '';
        // 当前选中的学期索引
        let semesterIndex = 0;

        // 如果有学期数据
        if (semesters.length > 0) {
          // 遍历查找当前学期（isdqxq === '1' 表示当前学期）
          for (let i = 0; i < semesters.length; i++) {
            if (semesters[i].isdqxq === '1') {
              // 记录当前学期的ID和索引
              currentSemester = semesters[i].semesterId;
              semesterIndex = i;
              break;
            }
          }
          
          // 如果没有找到当前学期标记，默认选择第一个学期
          if (!currentSemester) {
            currentSemester = semesters[0].semesterId;
          }
        }

        // 更新页面数据：学期列表、选中索引、当前学期、取消加载状态
        this.setData({ 
          semesters: semesters,
          semesterIndex: semesterIndex,
          currentSemester: currentSemester,
          loading: false
        });

        // 如果有当前学期，自动加载该学期的成绩
        if (currentSemester) {
          this.loadGrades();
        }
      })
      .catch(err => {
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
    // 解构获取当前选中的学期ID
    const { currentSemester } = this.data;

    // 未选择学期时提示并返回
    if (!currentSemester) {
      wx.showToast({ title: '请选择学期', icon: 'none' });
      return;
    }

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
  }
});
