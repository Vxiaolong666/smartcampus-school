
const API_BASE_URL = 'https://mc.xzwmc.com/csgocs/api.php';
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
 * @param {Object} [extra] - 额外参数（图片验证码：captcha）
 * @returns {Promise}
 */
const register = (username, password, email, confirmPassword, extra) => {
  return request({
    act: 'register',
    method: 'POST',
    data: Object.assign({ username, password, email, confirm_password: confirmPassword }, extra || {})
  });
};

/**
 * 忘记密码 - 发送重置邮件
 * @param {string} email - 邮箱地址
 * @param {Object} [extra] - 额外参数（图片验证码：captcha）
 * @returns {Promise}
 */
const forgotPassword = (email, extra) => {
  return request({
    act: 'forgot_password',
    method: 'POST',
    data: Object.assign({ email }, extra || {})
  });
};

/**
 * 发送验证邮件
 * @param {string} email - 邮箱地址
 * @param {Object} [extra] - 额外参数（图片验证码：captcha）
 * @returns {Promise}
 */
const sendVerifyEmail = (email, extra) => {
  return request({
    act: 'send_verify_email',
    method: 'POST',
    data: Object.assign({ email }, extra || {})
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
 * 获取开屏广告（后台「开屏广告」维护，免登录）
 * @param {string} client wx=小程序，app=Uniapp，all=通用
 * @returns {Promise} data: {id,title,image,image_url,url,duration} 或 null（未开启/无广告）
 */
const getSplashAd = (client = 'wx') => {
  return request({
    act: 'get_splash_ad',
    method: 'GET',
    data: { client }
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
 * 获取每日例行维护时段（后台「服务停止时段」配置，免登录）
 * @returns {Promise} data: {start:'23:00', end:'06:00'}
 */
const getMaintenanceConfig = () => {
  return request({
    act: 'get_maintenance_config',
    method: 'GET'
  });
};

/**
 * 获取联系方式（后台「官网配置」维护，免登录）
 * @returns {Promise} data: {email, phone, qq, address, worktime, desc}
 */
const getContactInfo = () => {
  return request({
    act: 'get_contact_info',
    method: 'GET'
  });
};

/**
 * AI 智能分析成绩
 * @param {Object} payload {achievement:[], gpa, credit, semester}
 */
const aiAnalyzeGrades = (payload) => {
  return request({
    act: 'ai_analyze_grades',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

/**
 * AI 智能分析用电（电费 / 用电统计）
 * @param {Object} payload 见各页面组装的数据
 */
const aiAnalyzePower = (payload) => {
  return request({
    act: 'ai_analyze_power',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

/**
 * AI 智能分析课表（学期课表 type=semester / 今日安排 type=today）
 * @param {Object} payload
 */
const aiAnalyzeSchedule = (payload) => {
  return request({
    act: 'ai_analyze_schedule',
    method: 'POST',
    data: { data: JSON.stringify(payload) }
  });
};

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

// UTF-8 字节流解码器：处理跨块截断的多字节字符（onChunkReceived 场景）
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
 * AI 对话（真流式）
 * - POST api.php?act=ai_chat，NDJSON 行协议：每行 {content:"增量"} / {error:"..."} / {done:true}
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
  const token = wx.getStorageSync('api_token');
  const url = API_BASE_URL + '?act=ai_chat';
  const body = 'messages=' + encodeURIComponent(JSON.stringify(messages || [])) +
    (model ? '&model=' + encodeURIComponent(model) : '');

  let aborted = false;
  let done = false;
  let promise_abort = null;

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

    let gotChunks = false;
    const task = wx.request({
      url,
      method: 'POST',
      data: body,
      timeout: 300000,
      header: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Authorization': token ? 'Bearer ' + token : ''
      },
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

    if (task && typeof task.onChunkReceived === 'function') {
      const byteDec = createUtf8Decoder();
      task.onChunkReceived((res) => {
        if (done) return;
        gotChunks = true;
        try { parser.push(byteDec(res.data || new ArrayBuffer(0), false)); } catch (e) {}
      });
    }

    promise_abort = () => { aborted = true; try { task && task.abort(); } catch (e) {} };
  });

  promise.abort = () => {
    aborted = true;
    if (promise_abort) promise_abort(); else finish();
  };
  return promise;
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
    const token = wx.getStorageSync('api_token');
    wx.uploadFile({
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

// AI 智能总结（页面加载自动异步生成，命中历史缓存不重复消耗额度）
const getNoticeAiSummary = (id) => {
  return request({
    act: 'ai_analyze_announcement&id=' + id,
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
 * @param {Object} data - { captcha } 图片验证码（小程序端，发送前须先通过校验）
 * @returns {Promise}
 */
const sendCancelCode = (data) => {
  return request({
    act: 'send_cancel_code',
    method: 'POST',
    data: data || {}
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

/* ==================== 登录合规提示 ==================== */

// 微信审核要求：不得进入页面即强制授权登录，需先说明登录用途，再由用户自行选择登录
const LOGIN_TIP_TEXT = '为准确匹配学校信息和给您提供个性化校园服务，请登录/注册并绑定学校再使用。';

/**
 * 弹窗说明登录用途，确认后跳转登录页、取消则离开当前功能页
 * 复用全局既有的 showModal 弹窗（与「功能暂未开放」提示一致），文案可完整展示不被截断
 * @param {Object} [options]
 * @param {'navigate'|'redirect'|'reLaunch'} [options.mode='navigate'] 跳转方式
 * @param {boolean} [options.stayOnCancel=false] 取消后是否原地停留（首页等自身可浏览的页面传 true）
 */
const goLoginWithTip = (options) => {
  const opts = options || {};
  const mode = opts.mode === 'redirect' ? 'redirectTo' : (opts.mode === 'reLaunch' ? 'reLaunch' : 'navigateTo');
  wx.showModal({
    title: '需要登录',
    content: LOGIN_TIP_TEXT,
    cancelText: '暂不登录',
    confirmText: '去登录',
    success: (res) => {
      if (res.confirm) {
        wx[mode]({ url: '/pages/login/index' });
        return;
      }
      // 选择「暂不登录」：当前页面本身可浏览时（如首页）原地不动
      if (opts.stayOnCancel) return;
      // 其余功能页离开当前页（未登录不请求数据，避免页面停在加载中）；
      // 页面栈里没有上一页（如标签页）时回到可免登录浏览的首页
      const pages = typeof getCurrentPages === 'function' ? getCurrentPages() : [];
      if (pages.length > 1) {
        wx.navigateBack({ delta: 1 });
      } else {
        wx.switchTab({ url: '/pages/white-home/index' });
      }
    }
  });
};

/* ==================== 协议中心相关接口（免登录） ==================== */

/**
 * 协议清单
 * @returns {Promise} data: [{ key, title, updated_at }]
 */
const getAgreements = () => {
  return request({
    act: 'get_agreements',
    method: 'GET'
  });
};

/**
 * 单份协议内容（Markdown）
 * @param {string} key - 协议标识
 * @returns {Promise} data: { key, title, content, updated_at }
 */
const getAgreement = (key) => {
  return request({
    act: 'get_agreement',
    method: 'GET',
    data: { key }
  });
};

/* ==================== 意见反馈相关接口（需登录） ==================== */

/**
 * 提交意见反馈
 * @param {Object} data { content:string, name?:string, email?:string, phone?:string, subject?:string }
 * @returns {Promise} data: { id:number }
 */
const submitFeedback = (data) => {
  return request({
    act: 'submit_feedback',
    method: 'POST',
    data: data || {}
  });
};

/**
 * 我的反馈记录（含处理状态与处理备注）
 * @param {Object} [params] { page:number, size:number }
 * @returns {Promise} data: { list, total, page, size, has_more, form_open }
 */
const getMyFeedbacks = (params) => {
  const p = params || {};
  const page = p.page || 1;
  const size = p.size || 10;
  return request({
    act: 'get_my_feedbacks&page=' + page + '&size=' + size,
    method: 'GET'
  });
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
  getAgreements,
  getAgreement,
  submitFeedback,
  getMyFeedbacks,
  queryGrade,
  querySchedule,
  queryStudentInfo,
  getSemesterList,
  getScheduleMode,
  getScheduleTimes,
  getBanners,
  getSplashAd,
  getAiModels,
  getMaintenanceConfig,
  getContactInfo,
  aiAnalyzeGrades,
  aiAnalyzePower,
  aiAnalyzeSchedule,
  getFileUrl,
  toApiPath,
  uploadAvatar,
  aiChatStream,
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
  LOGIN_TIP_TEXT,
  goLoginWithTip,
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
