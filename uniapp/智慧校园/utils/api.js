/**
 * 接口请求模块
 *
 * 响应状态码约定：
 * - code === 0：请求成功，resolve 返回完整响应数据
 * - code === -2：登录失效/token 过期，自动清除本地认证信息后 reject
 * - 其他 code：业务错误，reject 返回服务端错误信息
 * - 网络异常：reject 返回 { code: -1, msg: '网络请求失败' }
 */

let API_BASE_URL = 'http://127.0.0.12/api.php';
//https://mc.xzwmc.com/csgocs/api.php
//http://127.0.0.12/api.php

/**
 * 统一请求方法
 * @param {Object} options - 请求配置对象
 * @param {string} options.act - 接口动作名称（必填），对应服务端 act 参数
 * @param {string} [options.method='GET'] - 请求方法，可选值：'GET'、'POST'
 * @param {Object} [options.data={}] - 请求数据
 * @returns {Promise<Object>} 成功时 resolve(res.data)，失败时 reject(错误信息)
 */
const request = (options) => {
  return new Promise((resolve, reject) => {
    const token = uni.getStorageSync('api_token');

    uni.request({
      url: API_BASE_URL + '?act=' + options.act,
      method: options.method || 'GET',
      data: options.data || {},
      header: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token ? 'Bearer ' + token : ''
      },
      success: (res) => {
        if (res.data.code === 0) {
          resolve(res.data);
        } else if (res.data.code === -2) {
          uni.removeStorageSync('api_token');
          uni.removeStorageSync('api_userInfo');
          reject(res.data);
        } else {
          reject(res.data);
        }
      },
      fail: () => {
        reject({ code: -1, msg: '网络请求失败' });
      }
    });
  });
};

/* ==================== 认证相关接口 ==================== */

const login = (username, password) => {
  return request({
    act: 'login',
    method: 'POST',
    data: { username, password }
  });
};

const register = (username, password, email, confirmPassword) => {
  return request({
    act: 'register',
    method: 'POST',
    data: { username, password, email, confirm_password: confirmPassword }
  });
};

const forgotPassword = (email) => {
  return request({
    act: 'forgot_password',
    method: 'POST',
    data: { email }
  });
};

const sendVerifyEmail = (email) => {
  return request({
    act: 'send_verify_email',
    method: 'POST',
    data: { email }
  });
};

const resetPassword = (token, password, confirmPassword) => {
  return request({
    act: 'reset_password',
    method: 'POST',
    data: { token, password, confirm_password: confirmPassword }
  });
};

const logout = () => {
  return request({
    act: 'logout',
    method: 'GET'
  });
};

const changePassword = (oldPassword, newPassword) => {
  return request({
    act: 'change_password',
    method: 'POST',
    data: { old_password: oldPassword, new_password: newPassword }
  });
};

/* ==================== 用户信息相关接口 ==================== */

const getUserInfo = () => {
  return request({
    act: 'get_user_info',
    method: 'GET'
  });
};

/* ==================== 教务系统相关接口 ==================== */

const getAcademic = () => {
  return request({
    act: 'get_academic',
    method: 'GET'
  });
};

const bindAcademic = (studentId, password, realName) => {
  const data = { student_id: studentId, password };
  if (realName) data.real_name = realName;
  return request({
    act: 'bind_academic',
    method: 'POST',
    data
  });
};

const unbindAcademic = () => {
  return request({
    act: 'unbind_academic',
    method: 'POST'
  });
};

const queryGrade = (semester) => {
  const data = {};
  if (semester) data.semester = semester;
  return request({
    act: 'query_grade',
    method: 'POST',
    data
  });
};

const aiAnalyzeGrades = (payload) => {
  return request({
    act: 'ai_analyze_grades',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

const aiAnalyzePower = (payload) => {
  return request({
    act: 'ai_analyze_power',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

const aiAnalyzeSchedule = (payload) => {
  return request({
    act: 'ai_analyze_schedule',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

/* ==================== AI 对话 ==================== */

/**
 * AI 对话（统一非流式：后端 stream=0 一次性返回完整内容）
 * @param {Object} opts
 * @param {Array}    opts.messages OpenAI 格式消息数组
 * @param {Function} opts.onChunk  收到完整文本时回调(content:string)
 * @param {Function} [opts.onDone] 正常结束时回调
 * @param {Function} [opts.onError] 出错时回调(msg:string)
 * @returns {Promise<void>}
 */
const aiChatStream = (opts) => {
  const { messages, onChunk, onDone, onError, onDebug } = opts || {};
  const dbg = (t) => { try { onDebug && onDebug(t); } catch (e) {} };
  const url = API_BASE_URL + '?act=ai_chat&stream=0';
  const token = uni.getStorageSync('api_token');
  const body = 'messages=' + encodeURIComponent(JSON.stringify(messages || []));
  const callDone = () => { try { onDone && onDone(); } catch (e) {} };

  // 从响应中取出 content / error，兼容多种返回结构：
  // - 单层对象：{content, done} / {error}
  // - 信封包裹：{code, data:{content, done}}
  // - 流式 NDJSON 多行：每行 {content} 或末尾 {done:true}/{error}
  // - SSE：以 "data:" 开头的行
  const extract = (raw) => {
    let content = '';
    let err = '';
    const collect = (obj) => {
      if (!obj || typeof obj !== 'object') return;
      if (typeof obj.content === 'string') content += obj.content;
      if (obj.error) err = obj.error;
      if (obj.data && typeof obj.data === 'object') collect(obj.data);
      else if (typeof obj.data === 'string') content += obj.data;
    };
    if (raw && typeof raw === 'object') {
      collect(raw);
    } else if (typeof raw === 'string') {
      const text = raw.trim();
      if (text) {
        try {
          const o = JSON.parse(text);
          if (o && typeof o === 'object') collect(o);
        } catch (e) { /* 不是单行 JSON，继续按行解析 */ }
        const lines = raw.split('\n');
        for (const ln of lines) {
          let t = ln.trim();
          if (!t) continue;
          if (t.startsWith('data:')) t = t.slice(5).trim();
          if (t === '[DONE]' || t === 'done') continue;
          try {
            const o = JSON.parse(t);
            if (o && typeof o === 'object') collect(o);
          } catch (e) { /* 忽略非 JSON 行 */ }
        }
        if (!content && !err) {
          const m = raw.match(/"content"\s*:\s*"((?:\\.|[^"\\])*)"/);
          if (m) {
            content = m[1]
              .replace(/\\n/g, '\n')
              .replace(/\\t/g, '\t')
              .replace(/\\r/g, '\r')
              .replace(/\\"/g, '"')
              .replace(/\\\\/g, '\\');
          }
        }
      }
    }
    return { content, err };
  };

  // H5 端 uni.request 对 text/plain chunked 响应会出现 success/fail 均不回调的问题，
  // 因此在浏览器环境使用原生 fetch 兜底。
  if (typeof window !== 'undefined' && window.fetch) {
    dbg('start fetch ' + url + ' token=' + (token ? 'yes' : 'no'));
    return new Promise((resolve) => {
      fetch(url, {
        method: 'POST',
        cache: 'no-cache',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': token ? 'Bearer ' + token : ''
        },
        body
      })
        .then(response => response.text().then(text => ({ status: response.status, text })))
        .then(({ status, text }) => {
          dbg('fetch status=' + status + ' textLen=' + text.length + ' preview=' + text.slice(0, 160));
          const { content, err } = extract(text);
          dbg('extract contentLen=' + content.length + ' err=' + (err || ''));
          if (err) onError && onError(err);
          else if (content) { onChunk && onChunk(content); dbg('onChunk called len=' + content.length); }
          else dbg('extract produced empty content and no error');
        })
        .catch(e => {
          dbg('fetch error: ' + (e && e.message));
          try { onError && onError('网络请求失败'); } catch (e) {}
        })
        .finally(() => {
          callDone();
          resolve();
        });
    });
  }

  return new Promise((resolve) => {
    dbg('start uni.request ' + url + ' token=' + (token ? 'yes' : 'no'));
    uni.request({
      url,
      method: 'POST',
      data: body,
      timeout: 120000,
      header: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token ? 'Bearer ' + token : ''
      },
      success: (res) => {
        dbg('success statusCode=' + res.statusCode + ' dataType=' + typeof res.data + ' preview=' + String(res.data).slice(0, 160));
        try {
          const { content, err } = extract(res.data);
          dbg('extract contentLen=' + content.length + ' err=' + (err || ''));
          if (err) onError && onError(err);
          else if (content) { onChunk && onChunk(content); dbg('onChunk called len=' + content.length); }
          else dbg('extract produced empty content and no error');
        } catch (e) {
          dbg('success handler threw: ' + (e && e.message));
        } finally {
          callDone();
          resolve();
        }
      },
      fail: (err) => {
        dbg('fail ' + JSON.stringify(err));
        try { onError && onError('网络请求失败'); } catch (e) {}
        resolve();
      }
    });
  });
};

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

const queryStudentInfo = () => {
  return request({
    act: 'query_student_info',
    method: 'POST'
  });
};

const getSemesterList = () => {
  return request({
    act: 'get_semester_list',
    method: 'POST'
  });
};

const getScheduleMode = () => {
  return request({
    act: 'get_schedule_mode',
    method: 'POST'
  });
};

/* ==================== 宿舍相关接口 ==================== */

const getDormitories = () => {
  return request({
    act: 'get_dormitories',
    method: 'GET'
  });
};

const getDormAreas = () => {
  return request({
    act: 'get_dorm_areas',
    method: 'GET'
  });
};

const getDormBuildings = (area) => {
  return request({
    act: 'get_dorm_buildings&area=' + encodeURIComponent(area),
    method: 'GET'
  });
};

const getDormRooms = (area, building) => {
  return request({
    act: 'get_dorm_rooms&area=' + encodeURIComponent(area) + '&building=' + encodeURIComponent(building),
    method: 'GET'
  });
};

const bindDormitory = (data) => {
  return request({
    act: 'bind_dormitory',
    method: 'POST',
    data
  });
};

const unbindDormitory = (id) => {
  return request({
    act: 'unbind_dormitory',
    method: 'POST',
    data: { id }
  });
};

const setDefaultDormitory = (id) => {
  return request({
    act: 'set_default_dormitory',
    method: 'POST',
    data: { id }
  });
};

/* ==================== 电费相关接口 ==================== */

const queryPowerFee = (roomNum) => {
  return request({
    act: 'query_power_fee',
    method: 'POST',
    data: { room_num: roomNum }
  });
};

const getPowerConfig = () => {
  return request({
    act: 'get_power_config',
    method: 'GET'
  });
};

/* ==================== 公告相关接口 ==================== */

const getNotices = (page = 1, size = 10) => {
  return request({
    act: 'get_notices&page=' + page + '&size=' + size,
    method: 'GET'
  });
};

const getNoticeDetail = (id) => {
  return request({
    act: 'get_notice_detail&id=' + id,
    method: 'GET'
  });
};

/* ==================== 推送设置相关接口 ==================== */

const getPushSettings = () => {
  return request({
    act: 'get_push_settings',
    method: 'GET'
  });
};

const savePushSettings = (data) => {
  return request({
    act: 'save_push_settings',
    method: 'POST',
    data
  });
};

const getAcademicPushSettings = (params = {}) => {
  return request({
    act: 'get_academic_push_settings',
    method: 'GET',
    data: params
  });
};

const saveAcademicPushSettings = (data) => {
  return request({
    act: 'save_academic_push_settings',
    method: 'POST',
    data
  });
};

const getAcademicPushLogs = () => {
  return request({
    act: 'get_academic_push_logs',
    method: 'GET'
  });
};

/* ==================== 订阅历史相关接口 ==================== */

const getSubscriptionHistory = (page = 1, size = 10, type = 'all') => {
  return request({
    act: 'get_subscription_history&page=' + page + '&size=' + size + '&type=' + type,
    method: 'GET'
  });
};

/* ==================== 速览相关接口 ==================== */

const getGlanceData = () => {
  return request({
    act: 'get_glance_data',
    method: 'GET'
  });
};

/* ==================== 功能开关相关接口 ==================== */

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

const getCancelConfig = () => {
  return request({
    act: 'get_cancel_config',
    method: 'GET'
  });
};

const sendCancelCode = () => {
  return request({
    act: 'send_cancel_code',
    method: 'POST'
  });
};

const verifyCancelIdentity = (data) => {
  return request({
    act: 'verify_cancel_identity',
    method: 'POST',
    data: data || {}
  });
};

const cancelAccount = (data) => {
  return request({
    act: 'cancel_account',
    method: 'POST',
    data: data || {}
  });
};

/* ==================== 本地存储工具方法 ==================== */

const isLoggedIn = () => {
  return !!uni.getStorageSync('api_token');
};

const getToken = () => {
  return uni.getStorageSync('api_token');
};

const setToken = (token) => {
  uni.setStorageSync('api_token', token);
};

const getUserInfoCache = () => {
  return uni.getStorageSync('api_userInfo');
};

const setUserInfoCache = (userInfo) => {
  uni.setStorageSync('api_userInfo', userInfo);
};

const clearAuth = () => {
  uni.removeStorageSync('api_token');
  uni.removeStorageSync('api_userInfo');
};

export default {
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
  aiAnalyzeGrades,
  aiAnalyzePower,
  aiAnalyzeSchedule,
  aiChatStream,
  querySchedule,
  queryStudentInfo,
  getSemesterList,
  getScheduleMode,
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
