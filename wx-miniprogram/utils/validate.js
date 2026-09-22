/**
 * 表单校验工具（微信小程序端）
 * ------------------------------------------------------------------
 * 目的：提交前在本地拦掉明显不合法的输入，避免发起必然失败的请求、浪费服务器资源。
 * 规则与后端保持一致：
 *  - 用户名：/^[a-zA-Z0-9_]{4,20}$/（api.php register 的校验）
 *  - 邮箱：等价 PHP filter_var(..., FILTER_VALIDATE_EMAIL) 的常用写法
 *  - 密码：长度 6-64（后端要求 >= 6）
 *  - 验证码：纯数字，可按需指定位数（邮箱验证码 6 位、图片验证码 4 位）
 */

// 邮箱：覆盖常见合法写法，拒绝缺 @ / 缺顶级域 / 含空格的输入
const EMAIL_RE = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/;
// 用户名：仅字母、数字、下划线，长度 4-20（与后端一致）
const USERNAME_RE = /^[a-zA-Z0-9_]{4,20}$/;

const PASS_MIN = 6;
const PASS_MAX = 64;

// 统一转字符串并去首尾空格
function trim(v) {
  return String(v == null ? '' : v).trim();
}

// 邮箱格式
function isEmail(v) {
  const s = trim(v);
  return s.length > 0 && s.length <= 254 && EMAIL_RE.test(s);
}

// 用户名格式（注册等强制场景使用；登录页不强制，兼容历史账号）
function isUsername(v) {
  return USERNAME_RE.test(trim(v));
}

// 密码长度（不校验字符种类，与后端一致）
function isPassword(v, min, max) {
  const len = String(v == null ? '' : v).length;
  return len >= (min || PASS_MIN) && len <= (max || PASS_MAX);
}

// 纯数字校验；len 传具体数字时要求位数完全一致（如 6 位邮箱验证码）
function isDigits(v, len) {
  const s = trim(v);
  if (!/^\d+$/.test(s)) return false;
  return len ? s.length === len : true;
}

// 数值：是否为合法数字且在 [min, max] 区间（用于推送阈值等设置项）
function isNumberInRange(v, min, max) {
  const n = parseFloat(v);
  if (!isFinite(n)) return false;
  if (min != null && n < min) return false;
  if (max != null && n > max) return false;
  return true;
}

module.exports = {
  trim,
  isEmail,
  isUsername,
  isPassword,
  isDigits,
  isNumberInRange
};
