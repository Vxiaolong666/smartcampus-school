
const API_BASE_URL = 'http://127.0.0.12/api.php';
//https://mc.xzwmc.com/csgocs/api.php
//http://127.0.0.12/api.php
/**
 * 响应状态码约定：
 - code === 0：请求成功，resolve返回完整响应数据
 - code === -2：登录失效/token过期，自动清除本地认证信息后reject
 - 其他code：业务错误，reject返回服务端错误信息
 - 网络异常：reject返回 { code: -1, msg: '网络请求失败' }
 * 
 * @param {Object} options - 请求配置对象
 * @param {string} options.act - 接口动作名称（必填），对应服务端act参数
 * @param {string} [options.method='GET'] - 请求方法，可选值：'GET'、'POST'
 * @param {Object} [options.data={}] - 请求数据，GET时作为query参数，POST时作为请求体
 * @returns {Promise<Object>} 返回 Promise 对象，成功时resolve(res.data)，失败时reject(错误信息)
 */
const request = (options) => {
  return new Promise((resolve, reject) => {
    // 从本地缓存读取认证token，用于接口鉴权
    const token = wx.getStorageSync('api_token');
    
    // 调用微信小程序原生网络请求API
    wx.request({
      // 拼接完整请求地址：基础URL + act参数，服务端通过act路由到对应接口
      url: API_BASE_URL + '?act=' + options.act,
      // HTTP请求方法，未指定时默认GET
      method: options.method || 'GET',
      // 请求参数，未指定时默认空对象
      data: options.data || {},
      // 请求头配置
      header: {
        // 表单编码格式，服务端按form-data方式解析
        'Content-Type': 'application/x-www-form-urlencoded',
        // Bearer Token认证，token存在时携带，不存在时传空字符串
        'Authorization': token ? 'Bearer ' + token : ''
      },
      // HTTP请求成功回调
      success: (res) => {
        // 业务状态码为0，表示请求成功，返回完整响应数据
        if (res.data.code === 0) {
          resolve(res.data);
        } 
        // 业务状态码为-2，表示token失效或登录过期
        // 需要清除本地存储的token和用户信息，引导用户重新登录
        else if (res.data.code === -2) {
          wx.removeStorageSync('api_token');
          wx.removeStorageSync('api_userInfo');
          reject(res.data);
        } 
        // 其他业务错误
        else {
          reject(res.data);
        }
      },
      // 网络请求失败回调
      fail: (err) => {
        reject({ code: -1, msg: '网络请求失败' });
      }
    });
  });
};

/* ==================== 认证相关接口 ==================== */

/**
 * 用户登录
 * @param {string} username - 用户名
 * @param {string} password - 密码
 * @returns {Promise}
 */
const login = (username, password) => {
  return request({
    act: 'login',
    method: 'POST',
    data: { username, password }
  });
};

/**
 * 用户注册
 * @param {string} username - 用户名
 * @param {string} password - 密码
 * @param {string} email - 邮箱
 * @param {string} confirmPassword - 确认密码
 * @returns {Promise}
 */
const register = (username, password, email, confirmPassword) => {
  return request({
    act: 'register',
    method: 'POST',
    data: { username, password, email, confirm_password: confirmPassword }
  });
};

/**
 * 忘记密码 - 发送重置邮件
 * @param {string} email - 邮箱地址
 * @returns {Promise}
 */
const forgotPassword = (email) => {
  return request({
    act: 'forgot_password',
    method: 'POST',
    data: { email }
  });
};

/**
 * 发送验证邮件
 * @param {string} email - 邮箱地址
 * @returns {Promise}
 */
const sendVerifyEmail = (email) => {
  return request({
    act: 'send_verify_email',
    method: 'POST',
    data: { email }
  });
};

/**
 * 重置密码
 * @param {string} token - 重置令牌
 * @param {string} password - 新密码
 * @param {string} confirmPassword - 确认密码
 * @returns {Promise}
 */
const resetPassword = (token, password, confirmPassword) => {
  return request({
    act: 'reset_password',
    method: 'POST',
    data: { token, password, confirm_password: confirmPassword }
  });
};

/**
 * 用户登出
 * @returns {Promise}
 */
const logout = () => {
  return request({
    act: 'logout',
    method: 'GET'
  });
};

/**
 * 修改密码
 * @param {string} oldPassword - 原密码
 * @param {string} newPassword - 新密码
 * @returns {Promise}
 */
const changePassword = (oldPassword, newPassword) => {
  return request({
    act: 'change_password',
    method: 'POST',
    data: { old_password: oldPassword, new_password: newPassword }
  });
};

/* ==================== 用户信息相关接口 ==================== */

/**
 * 获取用户信息
 * @returns {Promise}
 */
const getUserInfo = () => {
  return request({
    act: 'get_user_info',
    method: 'GET'
  });
};

/* ==================== 教务系统相关接口 ==================== */

/**
 * 获取教务账号绑定信息
 * @returns {Promise}
 */
const getAcademic = () => {
  return request({
    act: 'get_academic',
    method: 'GET'
  });
};

/**
 * 绑定教务账号
 * @param {string} studentId - 学号
 * @param {string} password - 教务密码
 * @param {string} [realName] - 真实姓名（可选）
 * @returns {Promise}
 */
const bindAcademic = (studentId, password, realName) => {
  const data = { student_id: studentId, password };
  if (realName) data.real_name = realName;
  return request({
    act: 'bind_academic',
    method: 'POST',
    data
  });
};

/**
 * 解绑教务账号
 * @returns {Promise}
 */
const unbindAcademic = () => {
  return request({
    act: 'unbind_academic',
    method: 'POST'
  });
};

/**
 * 查询成绩
 * @param {string} [semester] - 学期（可选）
 * @returns {Promise}
 */
const queryGrade = (semester) => {
  const data = {};
  if (semester) data.semester = semester;
  return request({
    act: 'query_grade',
    method: 'POST',
    data
  });
};

/**
 * 查询课程表
 * @param {number} [week] - 周次（可选）
 * @param {string} [kbjcmsid] - 课表教学模式ID（可选）
 * @returns {Promise}
 */
const querySchedule = (week, kbjcmsid) => {
  const data = {};
  if (week) data.week = week;
  if (kbjcmsid) data.kbjcmsid = kbjcmsid;
  return request({
    act: 'query_schedule',
    method: 'POST',
    data
  });
};

/**
 * 查询学生信息
 * @returns {Promise}
 */
const queryStudentInfo = () => {
  return request({
    act: 'query_student_info',
    method: 'POST'
  });
};

/**
 * 获取学期列表
 * @returns {Promise}
 */
const getSemesterList = () => {
  return request({
    act: 'get_semester_list',
    method: 'POST'
  });
};

/**
 * 获取课表教学模式
 * @returns {Promise}
 */
const getScheduleMode = () => {
  return request({
    act: 'get_schedule_mode',
    method: 'POST'
  });
};

/**
 * 获取课程时间配置（作息）：各节次具体时间由后台按课程模式维护
 * @param {string} [modeId] 课程模式 ID（教务 kbjcmsid），留空表示使用「默认模式」
 * @returns {Promise}
 */
const getScheduleTimes = (modeId = '') => {
  const data = {};
  if (modeId) data.mode_id = modeId;
  return request({
    act: 'get_schedule_times',
    method: 'GET',
    data
  });
};

/* ==================== 宿舍相关接口 ==================== */

/**
 * 获取用户绑定的宿舍列表
 * @returns {Promise}
 */
const getDormitories = () => {
  return request({
    act: 'get_dormitories',
    method: 'GET'
  });
};

/**
 * 获取宿舍区域列表
 * @returns {Promise}
 */
const getDormAreas = () => {
  return request({
    act: 'get_dorm_areas',
    method: 'GET'
  });
};

/**
 * 获取宿舍楼栋列表
 * @param {string} area - 区域名称
 * @returns {Promise}
 */
const getDormBuildings = (area) => {
  return request({
    act: 'get_dorm_buildings&area=' + encodeURIComponent(area),
    method: 'GET'
  });
};

/**
 * 获取宿舍房间列表
 * @param {string} area - 区域名称
 * @param {string} building - 楼栋名称
 * @returns {Promise}
 */
const getDormRooms = (area, building) => {
  return request({
    act: 'get_dorm_rooms&area=' + encodeURIComponent(area) + '&building=' + encodeURIComponent(building),
    method: 'GET'
  });
};

/**
 * 绑定宿舍
 * @param {Object} data - 宿舍信息
 * @param {string} data.room_num - 房间编号
 * @param {string} data.school_area - 校区
 * @param {string} data.building - 楼栋
 * @param {string} data.room - 房间号
 * @param {string} [data.alias] - 别名
 * @returns {Promise}
 */
const bindDormitory = (data) => {
  return request({
    act: 'bind_dormitory',
    method: 'POST',
    data
  });
};

/**
 * 解绑宿舍
 * @param {number} id - 宿舍绑定ID
 * @returns {Promise}
 */
const unbindDormitory = (id) => {
  return request({
    act: 'unbind_dormitory',
    method: 'POST',
    data: { id }
  });
};

/**
 * 设置默认宿舍
 * @param {number} id - 宿舍绑定ID
 * @returns {Promise}
 */
const setDefaultDormitory = (id) => {
  return request({
    act: 'set_default_dormitory',
    method: 'POST',
    data: { id }
  });
};

/* ==================== 电费相关接口 ==================== */

/**
 * 查询电费余额
 * @param {string} roomNum - 房间编号
 * @returns {Promise} 返回余额、预计可用天数等信息
 */
const queryPowerFee = (roomNum) => {
  return request({
    act: 'query_power_fee',
    method: 'POST',
    data: { room_num: roomNum }
  });
};

/**
 * 获取电费配置
 * @returns {Promise}
 */
const getPowerConfig = () => {
  return request({
    act: 'get_power_config',
    method: 'GET'
  });
};

/* ==================== 公告相关接口 ==================== */

/**
 * 获取公告列表
 * @param {number} [page=1] - 页码
 * @param {number} [size=10] - 每页数量
 * @returns {Promise}
 */
const getNotices = (page = 1, size = 10) => {
  return request({
    act: 'get_notices&page=' + page + '&size=' + size,
    method: 'GET'
  });
};

/**
 * 获取公告详情
 * @param {number} id - 公告ID
 * @returns {Promise}
 */
const getNoticeDetail = (id) => {
  return request({
    act: 'get_notice_detail&id=' + id,
    method: 'GET'
  });
};

/* ==================== 推送设置相关接口 ==================== */

/**
 * 获取电费推送设置
 * @returns {Promise}
 */
const getPushSettings = () => {
  return request({
    act: 'get_push_settings',
    method: 'GET'
  });
};

/**
 * 保存电费推送设置
 * @param {Object} data - 推送设置数据
 * @returns {Promise}
 */
const savePushSettings = (data) => {
  return request({
    act: 'save_push_settings',
    method: 'POST',
    data
  });
};

/**
 * 获取教务推送设置
 * @returns {Promise}
 */
const getAcademicPushSettings = (params = {}) => {
  return request({
    act: 'get_academic_push_settings',
    method: 'GET',
    data: params
  });
};

/**
 * 保存教务推送设置
 * @param {Object} data - 推送设置数据
 * @returns {Promise}
 */
const saveAcademicPushSettings = (data) => {
  return request({
    act: 'save_academic_push_settings',
    method: 'POST',
    data
  });
};

/**
 * 获取教务推送日志
 * @returns {Promise}
 */
const getAcademicPushLogs = () => {
  return request({
    act: 'get_academic_push_logs',
    method: 'GET'
  });
};

/* ==================== 订阅历史相关接口 ==================== */

/**
 * 获取订阅历史记录（订阅授权 + 消息推送）
 * @param {number} [page=1] - 页码
 * @param {number} [size=10] - 每页数量
 * @param {string} [type='all'] - 类型：all（全部）/ subscribe（订阅记录）/ push（推送记录）
 * @returns {Promise} 返回 { list, total, page, size }
 */
const getSubscriptionHistory = (page = 1, size = 10, type = 'all') => {
  return request({
    act: 'get_subscription_history&page=' + page + '&size=' + size + '&type=' + type,
    method: 'GET'
  });
};

/* ==================== 速览相关接口 ==================== */

/**
 * 获取速览信息（课程+电费）
 * @returns {Promise} 返回 next_course, all_courses_ended, course_load_error, power_info, no_dormitory
 */
const getGlanceData = () => {
  return request({
    act: 'get_glance_data',
    method: 'GET'
  });
};

/* ==================== 功能开关相关接口 ==================== */

/**
 * 获取功能开关设置
 * @param {string[]} [keys] - 功能标识数组（可选，不传则获取全部）
 * @returns {Promise}
 */
const getFeatureSettings = (keys) => {
  let url = 'get_feature_settings';
  if (keys && keys.length > 0) {
    url += '&keys=' + keys.join(',');
  }
  return request({
    act: url,
    method: 'GET'
  });
};

/* ==================== 账号注销相关接口 ==================== */

/**
 * 获取注销配置与当前注销状态
 * @returns {Promise}
 */
const getCancelConfig = () => {
  return request({
    act: 'get_cancel_config',
    method: 'GET'
  });
};

/**
 * 发送注销邮箱验证码
 * @returns {Promise}
 */
const sendCancelCode = () => {
  return request({
    act: 'send_cancel_code',
    method: 'POST'
  });
};

/**
 * 注销第三步「下一步」身份核验（密码 / 邮箱验证码）
 * @param {Object} data - { password, email_code }
 * @returns {Promise} 成功后 data.pass 为核验凭据
 */
const verifyCancelIdentity = (data) => {
  return request({
    act: 'verify_cancel_identity',
    method: 'POST',
    data: data || {}
  });
};

/**
 * 提交注销申请
 * @param {Object} data - { agree, confirm_text, pass, password, email_code }
 * @returns {Promise}
 */
const cancelAccount = (data) => {
  return request({
    act: 'cancel_account',
    method: 'POST',
    data: data || {}
  });
};

/* ==================== 本地存储工具方法 ==================== */

/**
 * 检查是否已登录
 * @returns {boolean}
 */
const isLoggedIn = () => {
  return !!wx.getStorageSync('api_token');
};

/**
 * 获取存储的 Token
 * @returns {string|null}
 */
const getToken = () => {
  return wx.getStorageSync('api_token');
};

/**
 * 存储 Token
 * @param {string} token
 */
const setToken = (token) => {
  wx.setStorageSync('api_token', token);
};

/**
 * 获取缓存的用户信息
 * @returns {Object|null}
 */
const getUserInfoCache = () => {
  return wx.getStorageSync('api_userInfo');
};

/**
 * 缓存用户信息
 * @param {Object} userInfo
 */
const setUserInfoCache = (userInfo) => {
  wx.setStorageSync('api_userInfo', userInfo);
};

/**
 * 清除认证信息
 */
const clearAuth = () => {
  wx.removeStorageSync('api_token');
  wx.removeStorageSync('api_userInfo');
};

/* ==================== 模块导出 ==================== */

module.exports = {
  request,
  login,
  register,
  forgotPassword,
  sendVerifyEmail,
  resetPassword,
  logout,
  changePassword,
  getUserInfo,
  getAcademic,
  bindAcademic,
  unbindAcademic,
  getDormitories,
  queryPowerFee,
  queryGrade,
  querySchedule,
  queryStudentInfo,
  getSemesterList,
  getScheduleMode,
  getScheduleTimes,
  getDormAreas,
  getDormBuildings,
  getDormRooms,
  bindDormitory,
  unbindDormitory,
  setDefaultDormitory,
  getNotices,
  getNoticeDetail,
  isLoggedIn,
  getToken,
  setToken,
  getUserInfoCache,
  setUserInfoCache,
  clearAuth,
  getPushSettings,
  savePushSettings,
  getPowerConfig,
  getAcademicPushSettings,
  saveAcademicPushSettings,
  getAcademicPushLogs,
  getFeatureSettings,
  getGlanceData,
  getSubscriptionHistory,
  getCancelConfig,
  sendCancelCode,
  verifyCancelIdentity,
  cancelAccount
};
