/**
 * Markdown -> HTML 转换器（零依赖，面向小程序 / uniapp 的 rich-text）
 * ------------------------------------------------------------------
 * Markdown：标题 h1~h6、段落、引用、有序/无序列表（缩进嵌套、任务列表）、表格、
 *           代码块、分割线；粗体、斜体、删除线、行内代码、链接、图片、转义、自动链接。
 * 原生 HTML：直接透传并自动补齐默认内联样式
 *           （div p br span strong b em i u ins del s strike sub sup mark small big center
 *             a img h1~h6 ul ol li dl dt dd blockquote pre code table thead tbody tfoot
 *             tr th td hr font label figure figcaption address 等）。
 * 安全处理：script/style/iframe/form/input/video/audio/canvas/svg 等标签剔除
 *           （script、style 连内容一起丢弃）；标签属性只保留白名单；style 只保留
 *           rich-text 能渲染的属性（去掉 url()、expression()、javascript: 等）；
 *           HTML 实体（&nbsp; &amp; &#39; 等）先解码再按文本输出，避免显示成源码。
 * 说明：rich-text 不解析页面的 class 样式，因此所有样式统一输出为内联 style；
 *       相对路径图片请通过 options.resolveUrl 补全为完整地址（真机无法加载相对路径）。
 */

/* ==================== 1. 默认样式 / 白名单 ==================== */

const P_STYLE = 'margin:0 0 10px;line-height:1.7;font-size:14px;';
const LIST_STYLE = 'padding-left:20px;margin:0 0 10px;font-size:14px;';
const LI_STYLE = 'margin:4px 0;line-height:1.7;';
const QUOTE_STYLE = 'border-left:3px solid #1989fa;padding:6px 12px;margin:0 0 10px;color:#666;background-color:#f7f8fa;line-height:1.7;font-size:14px;';
const PRE_STYLE = 'background-color:#1e1e1e;color:#e6e6e6;padding:12px;border-radius:6px;white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.5;margin:0 0 10px;';
const CODE_STYLE = 'background-color:#f2f2f2;padding:1px 5px;border-radius:3px;font-size:13px;color:#d6336c;';
const LINK_STYLE = 'color:#1989fa;text-decoration:underline;';
const IMG_STYLE = 'max-width:100%;width:100%;border-radius:8px;margin:12px 0;display:block;';
const TABLE_STYLE = 'width:100%;table-layout:fixed;border-collapse:collapse;margin:0 0 10px;font-size:14px;';
const TH_STYLE = 'border:1px solid #ddd;padding:12px 14px;background-color:#f5f5f5;text-align:left;word-break:break-word;box-sizing:border-box;';
const TD_STYLE = 'border:1px solid #ddd;padding:12px 14px;text-align:left;word-break:break-word;box-sizing:border-box;';
const HR_STYLE = 'border:none;border-top:1px solid #e5e5e5;margin:12px 0;';

// <details> 折叠块降级样式（rich-text 不支持下述标签与折叠交互，这里渲染成「标题栏 + 正文」卡片）
const DETAILS_BOX = 'border:1px solid #e5e5e5;border-radius:8px;margin:0 0 12px;overflow:hidden;background-color:#ffffff;';
const DETAILS_HEAD = 'padding:10px 14px;background-color:#f7f8fa;border-bottom:1px solid #e5e5e5;font-size:14px;font-weight:bold;color:#48416f;line-height:1.6;';
const DETAILS_BODY = 'padding:12px 14px;';

const HEADING_SIZE = { 1: '20px', 2: '18px', 3: '16px', 4: '15px', 5: '14px', 6: '13px' };

function headingStyle(level) {
	return 'font-size:' + HEADING_SIZE[level] + ';font-weight:bold;margin:14px 0 8px;line-height:1.4;color:#333;';
}

// 允许透传的标签 + 默认内联样式（作者自带的 style 优先）
const TAG_STYLE = {
	h1: headingStyle(1),
	h2: headingStyle(2),
	h3: headingStyle(3),
	h4: headingStyle(4),
	h5: headingStyle(5),
	h6: headingStyle(6),
	p: P_STYLE,
	div: 'line-height:1.7;font-size:14px;',
	section: 'line-height:1.7;font-size:14px;',
	span: '',
	center: 'display:block;text-align:center;',
	strong: 'font-weight:bold;',
	b: 'font-weight:bold;',
	em: 'font-style:italic;',
	i: 'font-style:italic;',
	u: 'text-decoration:underline;',
	ins: 'text-decoration:underline;',
	del: 'text-decoration:line-through;',
	s: 'text-decoration:line-through;',
	strike: 'text-decoration:line-through;',
	sub: 'font-size:12px;vertical-align:sub;',
	sup: 'font-size:12px;vertical-align:super;',
	mark: 'background-color:#fff3a0;',
	small: 'font-size:12px;',
	big: 'font-size:16px;',
	abbr: '',
	q: '',
	cite: '',
	dfn: '',
	kbd: '',
	samp: '',
	tt: '',
	var: '',
	label: '',
	font: '',
	a: LINK_STYLE,
	img: IMG_STYLE,
	ul: 'list-style:disc;' + LIST_STYLE,
	ol: 'list-style:decimal;' + LIST_STYLE,
	li: LI_STYLE,
	dl: 'margin:0 0 10px;font-size:14px;',
	dt: 'font-weight:bold;line-height:1.7;',
	dd: 'margin:0 0 6px 16px;line-height:1.7;',
	blockquote: QUOTE_STYLE,
	pre: PRE_STYLE,
	code: CODE_STYLE,
	table: TABLE_STYLE,
	thead: '',
	tbody: '',
	tfoot: '',
	tr: '',
	th: TH_STYLE,
	td: TD_STYLE,
	hr: HR_STYLE,
	br: '',
	figure: 'margin:0 0 10px;',
	figcaption: 'margin-top:6px;font-size:12px;color:#999;text-align:center;',
	address: 'font-style:normal;line-height:1.7;font-size:14px;'
};

// 块级标签（整行只有它时不再包 <p>，避免 <p><div>…</div></p> 这类非法嵌套）
const BLOCK_TAGS = ('h1 h2 h3 h4 h5 h6 p div section center blockquote pre hr ul ol li dl dt dd ' +
	'table thead tbody tfoot tr th td figure figcaption address').split(' ');

// 自闭合标签
const VOID_TAGS = ['br', 'hr', 'img'];

// 标签允许保留的属性（其余标签只保留 style）
const TAG_ATTRS = {
	img: ['src', 'alt', 'title', 'width', 'height'],
	a: ['href', 'title', 'target'],
	font: ['color', 'size', 'face'],
	ol: ['start', 'type'],
	li: ['value'],
	th: ['colspan', 'rowspan', 'width', 'align', 'valign'],
	td: ['colspan', 'rowspan', 'width', 'align', 'valign'],
	table: ['width', 'border', 'cellpadding', 'cellspacing', 'align']
};

// rich-text 能渲染的 CSS 属性白名单
const STYLE_PROPS = ('color background background-color font font-size font-weight font-style font-family ' +
	'line-height letter-spacing word-spacing text-align text-indent text-decoration text-shadow ' +
	'white-space word-break word-wrap overflow-wrap vertical-align list-style list-style-type list-style-position ' +
	'margin margin-top margin-right margin-bottom margin-left ' +
	'padding padding-top padding-right padding-bottom padding-left ' +
	'border border-top border-right border-bottom border-left border-color border-style border-width ' +
	'border-radius border-collapse border-spacing ' +
	'width min-width max-width height min-height max-height display float clear opacity box-sizing ' +
	'table-layout caption-side').split(' ');

// <font size="1~7"> 映射
const FONT_SIZE = { 1: '10px', 2: '13px', 3: '16px', 4: '18px', 5: '24px', 6: '32px', 7: '48px' };

// 常用 HTML 实体（rich-text 会把 &xxx; 当源码显示，统一解码成字符）
const ENTITIES = {
	nbsp: '\u00a0', amp: '&', lt: '<', gt: '>', quot: '"', apos: '\'', middot: '·', bull: '•',
	copy: '\u00a9', reg: '\u00ae', trade: '\u2122', sect: '\u00a7', para: '\u00b6', deg: '\u00b0',
	hellip: '\u2026', mdash: '\u2014', ndash: '\u2013', minus: '\u2212', times: '\u00d7', divide: '\u00f7',
	laquo: '\u00ab', raquo: '\u00bb', ldquo: '\u201c', rdquo: '\u201d', lsquo: '\u2018', rsquo: '\u2019',
	euro: '\u20ac', pound: '\u00a3', yen: '\u00a5', cent: '\u00a2', dollar: '$',
	plusmn: '\u00b1', frac12: '\u00bd', frac14: '\u00bc', frac34: '\u00be', sup2: '\u00b2', sup3: '\u00b3',
	rarr: '\u2192', larr: '\u2190', uarr: '\u2191', darr: '\u2193', harr: '\u2194',
	check: '\u2713', cross: '\u2717', star: '\u2605', heart: '\u2665', smile: '\u263a', sun: '\u2600',
	ensp: '\u2002', emsp: '\u2003', thinsp: '\u2009', shy: '\u00ad', zwj: '\u200d', zwnj: '\u200c'
};

/* ==================== 2. 基础工具 ==================== */

function fromCode(code) {
	if (!code || code < 0 || code > 0x10ffff) return '';
	if (typeof String.fromCodePoint === 'function') return String.fromCodePoint(code);
	return code <= 0xffff ? String.fromCharCode(code) : '';
}

// HTML 实体 -> 字符（&nbsp; &#160; &#xA0; 等）
function decodeEntities(str) {
	return String(str).replace(/&(#[xX]?[0-9a-fA-F]+|[a-zA-Z]+);/g, function (m, body) {
		if (body.charAt(0) === '#') {
			const hex = body.charAt(1) === 'x' || body.charAt(1) === 'X';
			const code = parseInt(body.slice(hex ? 2 : 1), hex ? 16 : 10);
			return fromCode(code) || m;
		}
		const key = body.toLowerCase();
		return Object.prototype.hasOwnProperty.call(ENTITIES, key) ? ENTITIES[key] : m;
	});
}

function escapeHtml(str) {
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

// 文本片段：先解码实体再转义，保证输出是合法且可读的 HTML
function escapeText(str) {
	return escapeHtml(decodeEntities(str));
}

// 解析标签属性（事件属性 on* 一律丢弃）
function parseAttrs(str) {
	const attrs = {};
	const re = /([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s"'`=<>]+)))?/g;
	let m;
	while ((m = re.exec(str)) !== null) {
		const name = m[1].toLowerCase();
		if (name.slice(0, 2) === 'on') continue;
		let val = m[2] !== undefined ? m[2] : (m[3] !== undefined ? m[3] : (m[4] !== undefined ? m[4] : ''));
		attrs[name] = decodeEntities(val);
	}
	return attrs;
}

// 过滤 style，只保留 rich-text 可渲染的属性
function sanitizeStyle(value) {
	const out = [];
	String(value || '').split(';').forEach(function (item) {
		const i = item.indexOf(':');
		if (i < 0) return;
		const prop = item.slice(0, i).trim().toLowerCase();
		if (!prop || STYLE_PROPS.indexOf(prop) < 0) return;
		let val = item.slice(i + 1).trim();
		if (!val) return;
		// 去掉外链背景图与表达式，避免越权 / 真机异常
		val = val.replace(/url\s*\([^)]*\)/gi, '')
			.replace(/expression\s*\(/gi, '')
			.replace(/javascript:/gi, '')
			.trim();
		if (!val) return;
		out.push(prop + ':' + val);
	});
	return out;
}

// 合并样式：默认样式在前，作者样式在后（后者覆盖前者）
function mergeStyle(defaultStyle, props) {
	const map = {};
	function put(item) {
		const i = item.indexOf(':');
		if (i < 0) return;
		const key = item.slice(0, i).trim().toLowerCase();
		const val = item.slice(i + 1).trim();
		if (key && val) map[key] = val;
	}
	String(defaultStyle || '').split(';').forEach(put);
	(props || []).forEach(put);
	const keys = Object.keys(map);
	if (!keys.length) return '';
	let out = '';
	keys.forEach(function (key, idx) {
		out += (idx ? ';' : '') + key + ':' + map[key];
	});
	return out + ';';
}

// 拼装开始标签：默认样式 + 白名单属性 + 作者 style
function buildOpen(name, attrs, props) {
	const allowed = TAG_ATTRS[name] || [];
	const parts = [];
	allowed.forEach(function (key) {
		let val = attrs ? attrs[key] : '';
		if (val === undefined || val === null || val === '') return;
		val = String(val);
		// 危险协议直接丢弃（src 允许 data:image 内联图）
		if (key === 'href' && /^\s*(javascript|vbscript|data):/i.test(val)) return;
		if (key === 'src' && /^\s*(javascript|vbscript):/i.test(val)) return;
		parts.push(key + '="' + val.replace(/"/g, '&quot;') + '"');
	});
	let styleProps = props || [];
	if (attrs && attrs.style) styleProps = styleProps.concat(sanitizeStyle(attrs.style));
	const style = mergeStyle(TAG_STYLE[name] || '', styleProps);
	if (style) parts.push('style="' + style + '"');
	const open = '<' + name + (parts.length ? ' ' + parts.join(' ') : '');
	return VOID_TAGS.indexOf(name) >= 0 ? open + ' />' : open + '>';
}

// 生成完整标签（成对标签补结束标签，自闭合标签输出 <xxx />）
function htmlTag(name, inner, attrs, props) {
	const open = buildOpen(name, attrs, props);
	if (VOID_TAGS.indexOf(name) >= 0) return open;
	return open + (inner || '') + '</' + name + '>';
}

// 旧式属性 -> 内联样式（align / font / img 尺寸等）
function sizeValue(val) {
	const s = String(val || '').trim();
	return /^\d+$/.test(s) ? s + 'px' : s;
}

function legacyProps(name, attrs) {
	const props = [];
	if (!attrs) return props;
	if (attrs.align && ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'th', 'td'].indexOf(name) >= 0) {
		props.push('text-align:' + attrs.align);
	}
	if (attrs.valign && (name === 'td' || name === 'th')) props.push('vertical-align:' + attrs.valign);
	if (name === 'font') {
		if (attrs.color) props.push('color:' + attrs.color);
		if (attrs.face) props.push('font-family:' + attrs.face);
		if (attrs.size && FONT_SIZE[String(attrs.size).trim()]) props.push('font-size:' + FONT_SIZE[String(attrs.size).trim()]);
	}
	if (name === 'img') {
		if (attrs.width) props.push('width:' + sizeValue(attrs.width));
		if (attrs.height) props.push('height:' + sizeValue(attrs.height));
	}
	if ((name === 'td' || name === 'th' || name === 'table') && attrs.width) props.push('width:' + sizeValue(attrs.width));
	return props;
}

// 图片地址解析：优先 resolveUrl，其次 fileBase 拼接相对路径
function makeResolver(options) {
	if (options && typeof options.resolveUrl === 'function') {
		return function (src) {
			const s = String(src == null ? '' : src).trim();
			if (!s) return '';
			return options.resolveUrl(s) || s;
		};
	}
	const base = options && options.fileBase ? String(options.fileBase).replace(/\/$/, '') : '';
	return function (src) {
		const s = String(src == null ? '' : src).trim();
		if (!s) return '';
		if (!base || /^(https?:)?\/\//i.test(s) || /^data:/i.test(s)) return s;
		return base + (s.charAt(0) === '/' ? s : '/' + s);
	};
}

// 计算代码块（```）区间 [start, end)：围栏内的内容不做 Markdown / HTML 解析
function fenceRanges(text) {
	const ranges = [];
	const src = String(text);
	const re = /^[ \t]*```[^\n]*$/gm;
	let m;
	let start = -1;
	while ((m = re.exec(src)) !== null) {
		if (start < 0) {
			start = m.index;
		} else {
			ranges.push([start, m.index + m[0].length]);
			start = -1;
		}
	}
	if (start >= 0) ranges.push([start, src.length]);
	return ranges;
}

// 仅在代码块之外执行替换（避免破坏代码示例）
function replaceOutsideFence(text, re, replacer) {
	const src = String(text);
	const ranges = fenceRanges(src);
	if (!ranges.length) return src.replace(re, replacer);
	let out = '';
	let cursor = 0;
	ranges.forEach(function (r) {
		out += src.slice(cursor, r[0]).replace(re, replacer);
		out += src.slice(r[0], r[1]);
		cursor = r[1];
	});
	return out + src.slice(cursor).replace(re, replacer);
}

/* ==================== 3. 原生 HTML 提取 ==================== */

/**
 * 把源码里的原生 HTML 标签换成占位符（@@H0@@），避免被 Markdown 规则误伤 / 被转义。
 * 非白名单标签直接丢弃标签本身（保留其中文本）；script / style 连内容一起丢弃。
 */
function extractHtml(src, ctx) {
	const re = /<(\/?)([a-zA-Z][a-zA-Z0-9-]*)((?:"[^"]*"|'[^']*'|[^'">])*?)(\/?)>/g;
	let out = '';
	let last = 0;
	let m;

	const fences = fenceRanges(src);
	while ((m = re.exec(src)) !== null) {
		// 代码块内的标签不做解析（作为普通文本输出）
		const inFence = fences.some(function (r) {
			return m.index >= r[0] && m.index < r[1];
		});
		if (inFence) continue;

		out += escapeText(src.slice(last, m.index));
		last = re.lastIndex;

		const closing = m[1] === '/';
		const name = m[2].toLowerCase();
		const attrStr = m[3] || '';

		// <details> 折叠块：rich-text 不支持该标签与折叠交互，
		// 整块交给 ctx.addDetails 降级渲染（内部内容由它递归解析）
		if (!closing && name === 'details' && typeof ctx.addDetails === 'function') {
			const close = /<\/details\s*>/i.exec(src.slice(last));
			if (close) {
				const inner = src.slice(last, last + close.index);
				last = last + close.index + close[0].length;
				re.lastIndex = last;
				out += ctx.add(ctx.addDetails(inner), true);
				continue;
			}
		}

		// script / style：连同内容一起丢弃
		if (!closing && (name === 'script' || name === 'style')) {
			const closeRe = new RegExp('</' + name + '\\s*>', 'i');
			const cm = closeRe.exec(src.slice(last));
			if (cm) last = last + cm.index + cm[0].length;
			continue;
		}
		// 非白名单标签：丢弃标签本身，保留其中文本
		if (!Object.prototype.hasOwnProperty.call(TAG_STYLE, name)) continue;

		const block = BLOCK_TAGS.indexOf(name) >= 0;
		if (closing) {
			out += ctx.add('</' + name + '>', block);
			continue;
		}

		const attrs = parseAttrs(attrStr);
		if (name === 'img') {
			const src = ctx.resolve(attrs.src || attrs['data-src'] || '');
			if (!src) continue; // 没有可用图源，忽略
			attrs.src = src;
		}
		out += ctx.add(buildOpen(name, attrs, legacyProps(name, attrs)), block);
	}

	out += escapeText(src.slice(last));
	return out;
}

/* ==================== 4. 行内解析 ==================== */

function inline(str, options) {
	// 0. 反斜杠转义：先取出，最后还原成实体，避免 \* 被当成强调符
	str = str.replace(/\\([\\`*_{}\[\]()#+\-.!~|>])/g, function (m, c) {
		return '\u0002' + c.charCodeAt(0) + '\u0002';
	});
	// 1. 行内代码（最先处理，避免其中 * 等被后续规则误伤）
	str = str.replace(/(`+)([\s\S]*?)\1/g, function (m, tick, code) {
		return htmlTag('code', code);
	});
	// 2. 图片 ![alt](url)——必须在链接规则之前，否则会被 [text](url) 误判
	str = str.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, function (m, alt, url) {
		const resolved = resolveSrc(url, options);
		if (!resolved) return '';
		return htmlTag('img', '', { src: resolved, alt: alt });
	});
	// 3. 粗斜体 ***x*** / ___x___
	str = str.replace(/\*\*\*([^*]+)\*\*\*/g, function (m, t) {
		return htmlTag('strong', htmlTag('em', t));
	});
	str = str.replace(/___([^_]+)___/g, function (m, t) {
		return htmlTag('strong', htmlTag('em', t));
	});
	// 4. 粗体
	str = str.replace(/\*\*([^*]+)\*\*/g, function (m, t) {
		return htmlTag('strong', t);
	});
	str = str.replace(/__([^_]+)__/g, function (m, t) {
		return htmlTag('strong', t);
	});
	// 5. 斜体
	str = str.replace(/\*([^*]+)\*/g, function (m, t) {
		return htmlTag('em', t);
	});
	str = str.replace(/_([^_]+)_/g, function (m, t) {
		return htmlTag('em', t);
	});
	// 6. 删除线
	str = str.replace(/~~([^~]+)~~/g, function (m, t) {
		return htmlTag('del', t);
	});
	// 7. 链接 [text](url)
	str = str.replace(/\[([^\]]*)\]\(\s*([^)]+?)\s*\)/g, function (m, text, url) {
		const href = String(url || '').trim();
		if (!href) return text;
		return htmlTag('a', text, { href: href });
	});
	// 8. 还原转义字符（输出实体，避免二次解析）
	str = str.replace(/\u0002(\d+)\u0002/g, function (m, code) {
		return '&#' + code + ';';
	});
	return str;
}

// 行内图片地址补全（与 makeResolver 同规则）
function resolveSrc(url, options) {
	const s = String(url == null ? '' : url).trim();
	if (!s) return '';
	if (options && typeof options.resolveUrl === 'function') return options.resolveUrl(s) || s;
	if (options && options.fileBase && !/^(https?:)?\/\//i.test(s) && !/^data:/i.test(s)) {
		return String(options.fileBase).replace(/\/$/, '') + (s.charAt(0) === '/' ? s : '/' + s);
	}
	return s;
}

/* ==================== 5. 表格工具 ==================== */

function splitTableRow(row) {
	return row.split('|').slice(1, -1).map(function (c) {
		return c.trim();
	});
}

function parseTableAlign(sep) {
	return splitTableRow(sep).map(function (c) {
		const s = c.replace(/\s/g, '');
		if (s.charAt(0) === ':' && s.charAt(s.length - 1) === ':') return 'center';
		if (s.charAt(s.length - 1) === ':') return 'right';
		return 'left';
	});
}

/* ==================== 6. Markdown 主体 ==================== */

function markdownToHtml(md, options) {
	if (!md) return '';

	const ctx = {
		tags: [],
		isBlock: [],
		resolve: makeResolver(options)
	};
	ctx.add = function (tag, block) {
		const id = ctx.tags.length;
		ctx.tags.push(tag);
		ctx.isBlock.push(!!block);
		return '@@H' + id + '@@';
	};

	// <details> 折叠块：rich-text 不支持该标签与折叠交互，这里降级为「标题栏 + 正文」卡片（内容始终可见）；
	// 需要真正可折叠的页面请用 markdownToBlocks 的 details 块 + 原生渲染组件（components/md-blocks）
	ctx.addDetails = function (inner) {
		const summary = inner.match(/<summary\b[^>]*>([\s\S]*?)<\/summary>/i);
		const title = decodeEntities((summary ? summary[1] : '').replace(/<[^>]*>/g, '')).trim();
		const body = summary ? inner.replace(summary[0], '') : inner;
		return '<div style="' + DETAILS_BOX + '">' +
			'<div style="' + DETAILS_HEAD + '">' + inline(escapeText(title || '详情'), options) + '</div>' +
			'<div style="' + DETAILS_BODY + '">' + markdownToHtml(body, options) + '</div>' +
			'</div>';
	};

	// 自动链接 <https://x> / <mailto:x>：须在提取原生 HTML 之前转成 Markdown 链接
	// HTML 注释（<!-- -->）直接剔除，避免在页面上显示成源码
	const src = String(md)
		.replace(/\r\n?/g, '\n')
		.replace(/<!--[\s\S]*?-->/g, '')
		.replace(/<((?:https?|mailto):[^>\s]+)>/gi, '[$1]($1)');

	const lines = extractHtml(src, ctx).split('\n');
	let html = '';
	const listStack = [];   // [{ type, indent }]
	const paragraph = [];

	function listStyle(type) {
		return (type === 'ol' ? 'list-style:decimal;' : 'list-style:disc;') + LIST_STYLE;
	}

	function hasBlockToken(text) {
		const re = /@@H(\d+)@@/g;
		let t;
		while ((t = re.exec(text)) !== null) {
			if (ctx.isBlock[Number(t[1])]) return true;
		}
		return false;
	}

	function flushParagraph() {
		if (!paragraph.length) return;
		const raw = paragraph.join('<br/>');
		const content = inline(raw, options);
		// 含块级原生 HTML 时不再包 <p>（避免 <p><div>…</div></p> 这类非法嵌套）
		html += hasBlockToken(raw) ? content : '<p style="' + P_STYLE + '">' + content + '</p>';
		paragraph.length = 0;
	}

	function closeLists() {
		while (listStack.length) {
			html += '</li></' + listStack.pop().type + '>';
		}
	}

	function pushListItem(indent, type, content) {
		// 缩进退层：先关掉更深的列表
		while (listStack.length && indent < listStack[listStack.length - 1].indent) {
			html += '</li></' + listStack.pop().type + '>';
		}
		const top = listStack[listStack.length - 1];
		if (!top || indent > top.indent || type !== top.type) {
			if (top && type !== top.type && indent === top.indent) {
				// 同层切换类型（ul ↔ ol）：结束当前列表后另起一个
				html += '</li></' + listStack.pop().type + '>';
			}
			html += '<' + type + ' style="' + listStyle(type) + '"><li style="' + LI_STYLE + '">' + content;
			listStack.push({ type: type, indent: indent });
			return;
		}
		html += '</li><li style="' + LI_STYLE + '">' + content;
	}

	function listItemHtml(raw) {
		// 任务列表：- [x] / - [ ] -> ☑ / ☐（rich-text 不支持 input）
		const task = raw.match(/^\[([ xX])\]\s+([\s\S]*)$/);
		if (task) {
			const box = htmlTag('span', task[1].toLowerCase() === 'x' ? '☑' : '☐', null, ['margin-right:6px']);
			return box + inline(task[2], options);
		}
		return inline(raw, options);
	}

	let i = 0;
	while (i < lines.length) {
		const line = lines[i];

		// 代码块 ```
		const fence = line.match(/^```(.*)$/);
		if (fence) {
			flushParagraph();
			closeLists();
			const codeLines = [];
			i++;
			while (i < lines.length && !/^```/.test(lines[i])) {
				codeLines.push(lines[i]);
				i++;
			}
			i++; // 跳过结束 ```
			html += '<pre style="' + PRE_STYLE + '"><code>' + codeLines.join('\n') + '</code></pre>';
			continue;
		}

		// 表格
		if (line.charAt(0) === '|') {
			flushParagraph();
			closeLists();
			const tableLines = [];
			while (i < lines.length && lines[i].charAt(0) === '|') {
				tableLines.push(lines[i]);
				i++;
			}
			if (tableLines.length >= 2 && /^\|\s*:?-+:?\s*(\|\s*:?-+:?\s*)+\|?$/.test(tableLines[1].trim())) {
				const headers = splitTableRow(tableLines[0]);
				const aligns = parseTableAlign(tableLines[1]);
				let tableHtml = '<table style="' + TABLE_STYLE + '"><thead><tr>';
				headers.forEach(function (h, idx) {
					tableHtml += '<th style="' + mergeStyle(TH_STYLE, ['text-align:' + (aligns[idx] || 'left')]) + '">' + inline(h, options) + '</th>';
				});
				tableHtml += '</tr></thead><tbody>';
				for (let r = 2; r < tableLines.length; r++) {
					const cells = splitTableRow(tableLines[r]);
					tableHtml += '<tr>';
					headers.forEach(function (cell, idx) {
						const text = cells[idx] || '';
						tableHtml += '<td style="' + mergeStyle(TD_STYLE, ['text-align:' + (aligns[idx] || 'left')]) + '">' + inline(text, options) + '</td>';
					});
					tableHtml += '</tr>';
				}
				tableHtml += '</tbody></table>';
				html += tableHtml;
			} else {
				tableLines.forEach(function (l) {
					html += '<p style="' + P_STYLE + '">' + inline(l, options) + '</p>';
				});
			}
			continue;
		}

		// 整行只有原生 HTML（如 <div align="center">…</div> 的起止行）：原样输出
		if (/^(?:\s*@@H\d+@@)+\s*$/.test(line)) {
			flushParagraph();
			closeLists();
			html += line.trim();
			i++;
			continue;
		}

		// 标题
		const heading = line.match(/^(#{1,6})\s+(.*)$/);
		if (heading) {
			flushParagraph();
			closeLists();
			html += htmlTag('h' + heading[1].length, inline(heading[2], options));
			i++;
			continue;
		}

		// 分割线
		if (/^(-{3,}|\*{3,}|_{3,})$/.test(line.trim())) {
			flushParagraph();
			closeLists();
			html += htmlTag('hr');
			i++;
			continue;
		}

		// 引用（注意 > 已被转义为 &gt;）
		if (/^&gt;\s?/.test(line)) {
			flushParagraph();
			closeLists();
			const quoteLines = [];
			while (i < lines.length && /^&gt;\s?/.test(lines[i])) {
				quoteLines.push(lines[i].replace(/^&gt;\s?/, ''));
				i++;
			}
			html += htmlTag('blockquote', inline(quoteLines.join('<br/>'), options));
			continue;
		}

		// 列表（支持缩进嵌套与任务列表）
		const item = line.match(/^(\s*)([-*+]|\d{1,9}[.)])\s+(.*)$/);
		if (item) {
			flushParagraph();
			const indent = item[1].replace(/\t/g, '    ').length;
			const type = /^[-*+]/.test(item[2]) ? 'ul' : 'ol';
			pushListItem(indent, type, listItemHtml(item[3]));
			i++;
			continue;
		}

		// 空行
		if (line.trim() === '') {
			flushParagraph();
			closeLists();
			i++;
			continue;
		}

		// 普通段落行
		paragraph.push(line);
		i++;
	}
	flushParagraph();
	closeLists();

	// 还原原生 HTML / 折叠块占位符
	return html.replace(/@@H(\d+)@@/g, function (m, id) {
		const tag = ctx.tags[Number(id)];
		return tag === undefined ? '' : tag;
	});
}

/* ==================== 7. 块序列（文本块 + 图片块） ==================== */

/**
 * 把 Markdown 拆成「文本块 + 图片块 + 折叠块」序列（高可用渲染方案）
 * - 文本块：仍由 markdownToHtml 生成 HTML，交给 rich-text 渲染（各端解析一致）
 * - 图片块：交给原生 <image mode="widthFix"> 渲染
 * - 折叠块（<details><summary>…</summary>…</details>）：rich-text 不支持该标签与折叠交互，
 *   交给页面用原生 view 渲染（见 components/md-blocks），其内部同样拆成 文本/图片 块
 * 原因：rich-text 会忽略 <img> 内联样式、且真机上相对路径/百分比高度易失效，
 *       导致图片不显示或尺寸异常；拆出图片用原生 image 可保证所有端稳定显示。
 * 支持 Markdown 图片 ![alt](url) 与原生 <img src="..."> 两种写法。
 * @param {string} md Markdown 源码
 * @param {Object} [options] 同 markdownToHtml，支持 resolveUrl / fileBase
 * @returns {Array<{type:'html',html:string}|{type:'image',src:string,alt:string}|{type:'details',open:boolean,title:string,blocks:Array}>}
 */
function markdownToBlocks(md, options) {
	if (!md) return [];
	const src = replaceOutsideFence(
		String(md).replace(/\r\n?/g, '\n').replace(/<!--[\s\S]*?-->/g, ''),
		/<(\/?)(details|summary)\b([^>]*)>/gi,
		// 折叠标签规范化到独立行，便于逐行识别（代码块内的不处理）
		function (m) {
			return '\n' + m + '\n';
		}
	);
	return splitBlocks(src.split('\n'), {
		options: options,
		resolve: makeResolver(options),
		details: true
	});
}

/**
 * 行级拆分（markdownToBlocks 与折叠块内部共用）
 * @param {string[]} lines 行数组
 * @param {{options:Object,resolve:Function,details:boolean}} ctx details 为 true 时识别 <details>
 */
function splitBlocks(lines, ctx) {
	const blocks = [];
	const buf = [];
	const IMG_RE = /!\[([^\]]*)\]\(([^)]+)\)|<img\b[^>]*>/gi;
	let inFence = false;
	let box = null;   // 正在收集的折叠块

	function push(block) {
		block.key = blocks.length;
		blocks.push(block);
	}

	function flush() {
		if (!buf.length) return;
		const html = markdownToHtml(buf.join('\n'), ctx.options);
		if (html) push({ type: 'html', html: html });
		buf.length = 0;
	}

	lines.forEach(function (line) {
		const trimmed = line.trim();
		// 折叠块收集期间，行归属到「标题」或「正文」
		const target = box ? (box.inSummary ? box.title : box.body) : buf;

		// 代码块（```）内不解析任何标记
		if (/^```/.test(trimmed)) {
			inFence = !inFence;
			target.push(line);
			return;
		}
		if (inFence) {
			target.push(line);
			return;
		}

		// 折叠块 <details>
		if (ctx.details) {
			if (!box) {
				const open = trimmed.match(/^<details\b([^>]*)>$/i);
				if (open) {
					flush();
					box = { open: /\bopen\b/i.test(open[1]), title: [], body: [], inSummary: false };
					return;
				}
			} else {
				if (/^<summary\b[^>]*>$/i.test(trimmed)) {
					box.inSummary = true;
					return;
				}
				if (/^<\/summary>$/i.test(trimmed)) {
					box.inSummary = false;
					return;
				}
				if (/^<\/details>$/i.test(trimmed)) {
					const current = box;
					box = null;
					const title = decodeEntities(current.title.join(' ').replace(/<[^>]*>/g, '')).trim() || '详情';
					push({
						type: 'details',
						open: current.open,
						title: title,
						titleHtml: inline(escapeText(title), ctx.options),
						blocks: splitBlocks(current.body, {
							options: ctx.options,
							resolve: ctx.resolve,
							details: false
						})
					});
					return;
				}
				target.push(line);
				return;
			}
		}

		// 图片拆分（Markdown 图片 / 原生 <img>）
		IMG_RE.lastIndex = 0;
		let cursor = 0;
		let m;
		while ((m = IMG_RE.exec(line)) !== null) {
			buf.push(line.slice(cursor, m.index));
			flush();

			let src = '';
			let alt = '';
			if (m[0].charAt(0) === '!') {
				alt = m[1] || '';
				src = m[2] || '';
			} else {
				const attrs = parseAttrs(m[0]);
				src = attrs.src || attrs['data-src'] || '';
				alt = attrs.alt || '';
			}
			src = ctx.resolve(src);
			if (src) push({ type: 'image', src: src, alt: alt });
			cursor = IMG_RE.lastIndex;
		}
		buf.push(line.slice(cursor));
	});

	// 未闭合的 <details>：按普通内容渲染，避免内容丢失（markdownToHtml 会降级成卡片）
	if (box) {
		buf.push('<details' + (box.open ? ' open' : '') + '>');
		buf.push('<summary>' + box.title.join(' ') + '</summary>');
		Array.prototype.push.apply(buf, box.body);
	}
	flush();
	return blocks;
}

// 保持 module.exports 仍为可调用的 markdownToHtml（兼容所有既有调用方），
// 同时以属性 / 具名导出形式提供 markdownToBlocks
markdownToHtml.markdownToBlocks = markdownToBlocks;
module.exports = markdownToHtml;
