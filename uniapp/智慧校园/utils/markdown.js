/**
 * 轻量 Markdown -> HTML 转换器（零依赖，适用于 uniapp rich-text）
 * 支持：标题、粗体、斜体、行内代码、代码块、表格、引用、有序/无序列表、分割线、链接、段落与换行
 */

function escapeHtml(str) {
	return str
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

function inline(str) {
	// 行内代码（先处理，避免其中 * 被后续规则误伤）
	str = str.replace(/`([^`]+)`/g, (m, c) => {
		return '<code style="background:#f2f2f2;padding:1px 5px;border-radius:3px;font-size:13px;color:#d6336c;">' + c + '</code>';
	});
	// 粗体
	str = str.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
	str = str.replace(/__([^_]+)__/g, '<strong>$1</strong>');
	// 斜体
	str = str.replace(/\*([^*]+)\*/g, '<em>$1</em>');
	str = str.replace(/_([^_]+)_/g, '<em>$1</em>');
	// 链接
	str = str.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (m, t, u) => {
		return '<a href="' + u + '" style="color:#1989fa;text-decoration:underline;">' + t + '</a>';
	});
	return str;
}

function splitTableRow(row) {
	return row.split('|').slice(1, -1).map(c => c.trim());
}

function parseTableAlign(sep) {
	return splitTableRow(sep).map(c => {
		const s = c.replace(/\s/g, '');
		if (s.startsWith(':') && s.endsWith(':')) return 'center';
		if (s.endsWith(':')) return 'right';
		return 'left';
	});
}

const HEADING_SIZE = { 1: '20px', 2: '18px', 3: '16px', 4: '15px', 5: '14px', 6: '13px' };

function markdownToHtml(md) {
	if (!md) return '';
	const raw = escapeHtml(md).replace(/\r\n?/g, '\n');
	const lines = raw.split('\n');
	let html = '';
	let listType = null; // 'ul' | 'ol'
	let paragraph = [];

	function flushParagraph() {
		if (paragraph.length) {
			html += '<p style="margin:0 0 10px;line-height:1.7;font-size:14px;">' + inline(paragraph.join('<br/>')) + '</p>';
			paragraph = [];
		}
	}
	function closeList() {
		if (listType) {
			html += '</' + listType + '>';
			listType = null;
		}
	}

	let i = 0;
	while (i < lines.length) {
		const line = lines[i];

		// 代码块 ```
		const fence = line.match(/^```(.*)$/);
		if (fence) {
			flushParagraph();
			closeList();
			const codeLines = [];
			i++;
			while (i < lines.length && !/^```/.test(lines[i])) {
				codeLines.push(lines[i]);
				i++;
			}
			i++; // 跳过结束 ```
			html += '<pre style="background:#1e1e1e;color:#e6e6e6;padding:12px;border-radius:6px;white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.5;margin:0 0 10px;"><code>' + codeLines.join('\n') + '</code></pre>';
			continue;
		}

		// 表格
		if (line.startsWith('|')) {
			flushParagraph();
			closeList();
			const tableLines = [];
			while (i < lines.length && lines[i].startsWith('|')) {
				tableLines.push(lines[i]);
				i++;
			}
			if (tableLines.length >= 2 && /^\|\s*:?-+:?\s*(\|\s*:?-+:?\s*)+\|?$/.test(tableLines[1].trim())) {
				const headers = splitTableRow(tableLines[0]);
				const aligns = parseTableAlign(tableLines[1]);
				let tableHtml = '<table style="width:100%;table-layout:fixed;border-collapse:collapse;margin:0 0 10px;font-size:14px;">';
				tableHtml += '<thead><tr>';
				headers.forEach((h, idx) => {
					tableHtml += '<th style="border:1px solid #ddd;padding:12px 14px;background:#f5f5f5;text-align:' + (aligns[idx] || 'left') + ';word-break:break-word;box-sizing:border-box;">' + inline(h) + '</th>';
				});
				tableHtml += '</tr></thead><tbody>';
				for (let r = 2; r < tableLines.length; r++) {
					const cells = splitTableRow(tableLines[r]);
					tableHtml += '<tr>';
					headers.forEach((_, idx) => {
						const cell = cells[idx] || '';
						tableHtml += '<td style="border:1px solid #ddd;padding:12px 14px;text-align:' + (aligns[idx] || 'left') + ';word-break:break-word;box-sizing:border-box;">' + inline(cell) + '</td>';
					});
					tableHtml += '</tr>';
				}
				tableHtml += '</tbody></table>';
				html += tableHtml;
			} else {
				tableLines.forEach(l => {
					html += '<p style="margin:0 0 10px;line-height:1.7;font-size:14px;">' + inline(l) + '</p>';
				});
			}
			continue;
		}

		// 标题
		const h = line.match(/^(#{1,6})\s+(.*)$/);
		if (h) {
			flushParagraph();
			closeList();
			const level = h[1].length;
			html += '<h' + level + ' style="font-size:' + HEADING_SIZE[level] + ';font-weight:bold;margin:14px 0 8px;line-height:1.4;color:#333;">' + inline(h[2]) + '</h' + level + '>';
			i++;
			continue;
		}

		// 分割线
		if (/^(-{3,}|\*{3,}|_{3,})$/.test(line.trim())) {
			flushParagraph();
			closeList();
			html += '<hr style="border:none;border-top:1px solid #e5e5e5;margin:12px 0;"/>';
			i++;
			continue;
		}

		// 引用（注意 > 已被转义为 &gt;）
		if (/^&gt;\s?/.test(line)) {
			flushParagraph();
			closeList();
			const quoteLines = [];
			while (i < lines.length && /^&gt;\s?/.test(lines[i])) {
				quoteLines.push(lines[i].replace(/^&gt;\s?/, ''));
				i++;
			}
			html += '<blockquote style="border-left:3px solid #1989fa;padding:6px 12px;margin:0 0 10px;color:#666;background:#f7f8fa;line-height:1.7;font-size:14px;">' + inline(quoteLines.join('<br/>')) + '</blockquote>';
			continue;
		}

		// 无序列表
		const ul = line.match(/^[-*+]\s+(.*)$/);
		if (ul) {
			flushParagraph();
			if (listType !== 'ul') {
				closeList();
				html += '<ul style="padding-left:20px;margin:0 0 10px;font-size:14px;">';
				listType = 'ul';
			}
			html += '<li style="margin:4px 0;line-height:1.7;">' + inline(ul[1]) + '</li>';
			i++;
			continue;
		}

		// 有序列表
		const ol = line.match(/^\d+\.\s+(.*)$/);
		if (ol) {
			flushParagraph();
			if (listType !== 'ol') {
				closeList();
				html += '<ol style="padding-left:20px;margin:0 0 10px;font-size:14px;">';
				listType = 'ol';
			}
			html += '<li style="margin:4px 0;line-height:1.7;">' + inline(ol[1]) + '</li>';
			i++;
			continue;
		}

		// 空行
		if (line.trim() === '') {
			flushParagraph();
			closeList();
			i++;
			continue;
		}

		// 普通段落行
		paragraph.push(line);
		i++;
	}
	flushParagraph();
	closeList();
	return html;
}

export default markdownToHtml;
