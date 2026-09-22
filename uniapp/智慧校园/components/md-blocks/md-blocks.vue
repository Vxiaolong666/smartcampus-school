<template>
	<view class="md-blocks">
		<block v-for="(block, idx) in blocks" :key="block.key === undefined ? idx : block.key">
			<!-- 折叠块：rich-text 不支持 <details>，用原生 view + 状态实现展开/折叠 -->
			<view v-if="block.type === 'details'" class="md-details">
				<view class="md-details-head" @click="toggle(block)">
					<rich-text class="md-details-title" :nodes="block.titleHtml || block.title"></rich-text>
					<text class="md-details-caret">{{ isOpen(block) ? '▾' : '▸' }}</text>
				</view>
				<view v-if="isOpen(block)" class="md-details-body">
					<block v-for="(sub, si) in block.blocks" :key="sub.key === undefined ? si : sub.key">
						<rich-text v-if="sub.type === 'html'" class="md-rich" :nodes="sub.html"></rich-text>
						<image
							v-else
							class="md-img"
							:src="sub.src"
							mode="widthFix"
							@click="$emit('preview', sub.src)"
						></image>
					</block>
				</view>
			</view>

			<!-- 文本块 -->
			<rich-text v-else-if="block.type === 'html'" class="md-rich" :nodes="block.html"></rich-text>

			<!-- 图片块 -->
			<image
				v-else
				class="md-img"
				:src="block.src"
				mode="widthFix"
				@click="$emit('preview', block.src)"
			></image>
		</block>
	</view>
</template>

<script>
/**
 * 正文块渲染组件（公告 / 协议通用）
 * ------------------------------------------------------------------
 * 渲染 utils/markdown.js 的 markdownToBlocks 产出的三类块：
 *  - { type:'html', html }        → rich-text 渲染
 *  - { type:'image', src, alt }   → 原生 image，点击触发 preview 事件（页面负责放大）
 *  - { type:'details', title, titleHtml, open, blocks }
 *        → 原生可折叠区块（rich-text 不支持 <details> 与折叠交互），
 *          内部同样支持 文本 / 图片 块；默认展开状态取 <details open>
 */
export default {
	name: 'md-blocks',

	props: {
		blocks: {
			type: Array,
			default: () => []
		}
	},

	data() {
		return {
			toggled: {}   // 折叠块展开状态覆盖（块 key → true/false）
		};
	},

	methods: {
		// 默认取 <details open>；用户点击后以 toggled 为准
		isOpen(block) {
			const key = block.key;
			return this.toggled[key] === undefined ? !!block.open : !!this.toggled[key];
		},
		// 展开 / 折叠（整体替换对象，兼容 Vue2 / Vue3 响应式）
		toggle(block) {
			const key = block.key;
			this.toggled = Object.assign({}, this.toggled, { [key]: !this.isOpen(block) });
		}
	}
};
</script>

<style scoped>
/* 正文块渲染：与 rich-text 内的内联样式风格保持一致 */
.md-blocks {
	width: 100%;
}

.md-rich {
	display: block;
	width: 100%;
}

.md-img {
	display: block;
	width: 100%;
	margin: 16rpx 0;
	border-radius: 12rpx;
}

/* 折叠块：rich-text 不支持 <details>，这里用原生 view 实现展开/折叠 */
.md-details {
	margin: 16rpx 0;
	border: 2rpx solid #e9ecf4;
	border-radius: 16rpx;
	overflow: hidden;
}

.md-details-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 22rpx 26rpx;
	background: #f7f8fc;
}

.md-details-title {
	flex: 1;
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
}

.md-details-caret {
	margin-left: 16rpx;
	font-size: 26rpx;
	color: #948eb3;
}

.md-details-body {
	padding: 12rpx 26rpx 20rpx;
}
</style>
