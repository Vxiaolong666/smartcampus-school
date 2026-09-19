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

// NDJSON 行解析器：把字符串流按行拆分（内部缓冲跨块残行），逐行回调
const createNdjsonParser = (handleLine) => {
  let buf = '';
  return {
    push(text) {
      buf += text || '';
      const parts = buf.split('\n');
      buf = parts.pop() || '';
      parts.forEach((ln) => {
        const t = ln.trim();
        if (t) handleLine(t);
      });
    },
    end() {
      const t = buf.trim();
      if (t) handleLine(t);
      buf = '';
    }
  };
};

// UTF-8 字节流解码器：处理跨块截断的多字节字符（微信小程序 onChunkReceived 场景）
const createUtf8Decoder = () => {
  let carry = [];
  return (chunk, final) => {
    const u8 = chunk instanceof Uint8Array ? chunk : new Uint8Array(chunk || 0);
    if (typeof TextDecoder !== 'undefined') {
      try { return new TextDecoder('utf-8').decode(u8, { stream: !final }); } catch (e) { /* 回退手动解码 */ }
    }
    const bytes = carry.concat(Array.prototype.slice.call(u8));
    let out = '';
    let i = 0;
    while (i < bytes.length) {
      const b = bytes[i];
      let len = 0;
      if (b < 0x80) len = 1;
      else if ((b & 0xE0) === 0xC0) len = 2;
      else if ((b & 0xF0) === 0xE0) len = 3;
      else if ((b & 0xF8) === 0xF0) len = 4;
      else { out += '\uFFFD'; i += 1; continue; }
      if (i + len > bytes.length) {
        if (final) { out += '\uFFFD'; i = bytes.length; }
        break; // 等待后续分块
      }
      let cp = b & (len === 1 ? 0x7F : len === 2 ? 0x1F : len === 3 ? 0x0F : 0x07);
      let ok = true;
      for (let j = 1; j < len; j++) {
        if ((bytes[i + j] & 0xC0) !== 0x80) { ok = false; break; }
        cp = (cp << 6) | (bytes[i + j] & 0x3F);
      }
      if (!ok) { out += '\uFFFD'; i += 1; continue; }
      out += String.fromCodePoint(cp);
      i += len;
    }
    carry = bytes.slice(i);
    return out;
  };
};

/**
 * AI 对话（真流式，与网页版交互一致）
 * - POST api.php?act=ai_chat，NDJSON 行协议：每行 {content:"增量"} / {error:"..."} / {done:true}
 * - H5：fetch + ReadableStream 逐行读取，支持中断（abort 保留已生成内容）
 * - 微信小程序：enableChunked 分块接收逐行解析；低版本基础库自动回退整包解析
 * @param {Object}   opts
 * @param {Array}    opts.messages OpenAI 格式消息数组（system 角色会被后端丢弃）
 * @param {string}   [opts.model] 模型名（来自 ai_models 接口）
 * @param {Function} opts.onChunk 收到增量文本时回调(delta:string)
 * @param {Function} [opts.onDone] 结束时回调（成功/失败/中断都会触发一次）
 * @param {Function} [opts.onError] 出错时回调(msg:string)
 * @returns {Promise} promise.abort() 可中断当前流
 */
const aiChatStream = (opts) => {
  const { messages, model, onChunk, onDone, onError } = opts || {};
  const token = uni.getStorageSync('api_token');
  const url = API_BASE_URL + '?act=ai_chat';
  const body = 'messages=' + encodeURIComponent(JSON.stringify(messages || [])) +
    (model ? '&model=' + encodeURIComponent(model) : '');

  let aborted = false;
  let done = false;
  let abortFn = null;

  const promise = new Promise((resolve) => {
    const finish = () => {
      if (done) return;
      done = true;
      try { onDone && onDone(); } catch (e) {}
      resolve();
    };
    const fail = (msg) => {
      if (!aborted && !done) {
        try { onError && onError(msg); } catch (e) {}
      }
      finish();
    };
    const parser = createNdjsonParser((line) => {
      if (done) return;
      let obj = null;
      try { obj = JSON.parse(line); } catch (e) { return; }
      if (!obj || typeof obj !== 'object') return;
      if (obj.error) { fail(obj.error); return; }
      if (obj.done) { finish(); return; }
      if (typeof obj.content === 'string' && obj.content) {
        try { onChunk && onChunk(obj.content); } catch (e) {}
      }
    });

    // #ifdef H5
    const controller = new AbortController();
    abortFn = () => { aborted = true; try { controller.abort(); } catch (e) {} };
    fetch(url, {
      method: 'POST',
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token ? 'Bearer ' + token : ''
      },
      body,
      signal: controller.signal
    }).then((res) => {
      if (!res.ok) { fail('请求失败（HTTP ' + res.status + '）'); return; }
      if (!res.body || typeof res.body.getReader !== 'function') {
        // 浏览器不支持流式读取时整体解析
        return res.text().then((t) => { parser.push(t); parser.end(); finish(); });
      }
      const reader = res.body.getReader();
      const decoder = new TextDecoder('utf-8');
      const pump = () => reader.read().then((r) => {
        if (r.done) { parser.push(decoder.decode()); parser.end(); finish(); return; }
        parser.push(decoder.decode(r.value, { stream: true }));
        return pump();
      });
      return pump();
    }).catch(() => {
      if (aborted) finish(); else fail('网络请求失败');
    });
    // #endif

    // #ifndef H5
    let gotChunks = false;
    const task = uni.request({
      url,
      method: 'POST',
      data: body,
      timeout: 300000,
      header: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token ? 'Bearer ' + token : ''
      },
      // 微信小程序支持分块接收
      enableChunked: true,
      success: (res) => {
        // 分块接收不可用时回退整包解析
        if (!gotChunks && res && res.data) {
          parser.push(typeof res.data === 'string' ? res.data : JSON.stringify(res.data));
        }
        parser.end();
        finish();
      },
      fail: () => {
        if (aborted) finish(); else fail('网络请求失败');
      }
    });
    abortFn = () => { aborted = true; try { task && task.abort(); } catch (e) {} };
    if (task && typeof task.onChunkReceived === 'function') {
      const byteDec = createUtf8Decoder();
      task.onChunkReceived((res) => {
        if (done) return;
        gotChunks = true;
        try { parser.push(byteDec(res.data || new ArrayBuffer(0), false)); } catch (e) {}
      });
    }
    // #endif
  });

  promise.abort = () => {
    aborted = true;
    if (abortFn) abortFn(); else finish();
  };
  return promise;
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

/**
 * 获取课程节次时间（后台「课程时间配置」按课程模式维护的作息）
 * @param {string} [modeId] 课程模式 ID（教务 kbjcmsid），留空使用默认模式
 * @returns {Promise} 成功时 data.periods 为节次数组
 */
const getScheduleTimes = (modeId) => {
  const data = {};
  if (modeId) data.mode_id = modeId;
  return request({
    act: 'get_schedule_times',
    method: 'GET',
    data
  });
};

/**
 * 获取首页轮播图（后台「轮播图配置」维护，免登录）
 * @returns {Promise} data: [{id,title,image,image_url,url}]
 */
const getBanners = () => {
  return request({
    act: 'get_banners',
    method: 'GET'
  });
};

/**
 * 获取当前 AI 提供方支持的模型列表（供对话页下拉选择）
 * @returns {Promise} data: {models:[], default:''}
 */
const getAiModels = () => {
  return request({
    act: 'ai_models',
    method: 'GET'
  });
};

/**
 * 获取联系方式（后台「首页配置」维护，免登录）
 * @returns {Promise} data: {email, phone, qq, address, worktime, desc}
 */
const getContactInfo = () => {
  return request({
    act: 'get_contact_info',
    method: 'GET'
  });
};

/**
 * 获取每日例行维护时段（后台「服务停止时段」配置，免登录）
 * @returns {Promise} data: {start:'23:00', end:'06:00'}
 */
const getMaintenanceConfig = () => {
  return request({
    act: 'get_maintenance_config',
    method: 'GET'
  });
};

// 文件基础地址：由 API 地址自动推导（api.php 所在目录），更换部署域名时只需改 API_BASE_URL
const API_FILE_BASE = API_BASE_URL.replace(/\/api\.php(\?.*)?$/i, '');

/**
 * 相对路径转完整文件 URL（页面展示用）
 * 空值返回空串；已是 http(s) 完整地址的原样返回；否则拼接 API 站点基础地址
 */
const getFileUrl = (path) => {
  if (!path) return '';
  if (/^https?:\/\//i.test(path)) return path;
  return API_FILE_BASE + path;
};

/**
 * 完整文件 URL 转回 API 站点相对路径（把地址存回后端时用）
 * 非 API 站点的完整地址（如第三方头像）原样返回
 */
const toApiPath = (url) => {
  if (!url) return '';
  if (/^https?:\/\//i.test(url) && url.indexOf(API_FILE_BASE) === 0) {
    return url.slice(API_FILE_BASE.length) || '';
  }
  return url;
};

/**
 * 上传头像（POST multipart 到 upload_avatar 接口）
 * @param {string} filePath 本地图片临时路径
 * @returns {Promise} resolve(res)，res.data.avatar 为完整头像 URL
 */
const uploadAvatar = (filePath) => {
  return new Promise((resolve, reject) => {
    const token = uni.getStorageSync('api_token');
    uni.uploadFile({
      url: API_BASE_URL + '?act=upload_avatar',
      filePath: filePath,
      name: 'avatar',
      header: {
        Authorization: token ? 'Bearer ' + token : ''
      },
      success: (res) => {
        try {
          const data = JSON.parse(res.data);
          if (data.code === 0) resolve(data); else reject(data);
        } catch (e) {
          reject({ code: -1, msg: '响应解析失败' });
        }
      },
      fail: () => reject({ code: -1, msg: '上传失败' })
    });
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

// AI 智能总结（页面加载自动异步生成，命中历史缓存不重复消耗额度）
const getNoticeAiSummary = (id) => {
  return request({
    act: 'ai_analyze_announcement&id=' + id,
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

/* ==================== 第三方账号绑定（聚合登录） ==================== */

const getOauthBinds = () => {
  return request({
    act: 'get_oauth_binds',
    method: 'GET'
  });
};

const getOauthUrl = (type) => {
  return request({
    act: 'get_oauth_url',
    method: 'POST',
    data: { type, state: 'bind' }
  });
};

const unbindOauth = (type) => {
  return request({
    act: 'unbind_oauth',
    method: 'POST',
    data: { type }
  });
};

/* ==================== 第三方账号登录（聚合登录） ==================== */

const getOauthChannels = () => {
  return request({
    act: 'get_oauth_channels',
    method: 'GET'
  });
};

const getOauthLoginUrl = (type) => {
  return request({
    act: 'get_oauth_login_url',
    method: 'POST',
    data: { type }
  });
};

const oauthLoginPoll = (ticket) => {
  return request({
    act: 'oauth_login_poll',
    method: 'POST',
    data: { ticket }
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
  getScheduleTimes,
  getBanners,
  getAiModels,
  getContactInfo,
  getMaintenanceConfig,
  getFileUrl,
  toApiPath,
  uploadAvatar,
  getDormAreas,
  getDormBuildings,
  getDormRooms,
  bindDormitory,
  unbindDormitory,
  setDefaultDormitory,
  getNotices,
  getNoticeDetail,
  getNoticeAiSummary,
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
  cancelAccount,
  getOauthBinds,
  getOauthUrl,
  unbindOauth,
  getOauthChannels,
  getOauthLoginUrl,
  oauthLoginPoll
};
