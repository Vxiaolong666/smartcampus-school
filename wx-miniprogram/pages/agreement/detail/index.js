/**
 * 协议通用详情页（全部协议统一入口）
 * 所有协议都由此页渲染：
 *   /pages/agreement/detail/index?key=xxx
 * 内容一律取后台「协议管理」（本地不留存任何协议预留信息）：
 * 未发布时展示「当前协议未发布」，拉取失败时展示「加载失败 + 重新加载」；正文支持图片。
 */
const agreements = require('../../../utils/agreements.js');

Page(agreements.pageConfig('', '协议详情'));
