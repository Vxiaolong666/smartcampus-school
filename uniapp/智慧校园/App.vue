<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
		}
	}
</script>

<style>
	/*每个页面公共css */

	/* 骨架屏基础占位块 */
	.sk {
		background: linear-gradient(90deg, #e9ecf5 25%, #f7f9fe 37%, #e9ecf5 63%);
		background-size: 400% 100%;
		border-radius: 8rpx;
	}

	/* 光带滚动动画 */
	.sk-anim {
		animation: sk-shimmer 1.4s ease infinite;
	}

	.sk-circle {
		border-radius: 50%;
	}

	.sk-avatar {
		width: 100rpx;
		height: 100rpx;
		border-radius: 50%;
	}

	.sk-thumb {
		width: 50rpx;
		height: 50rpx;
		border-radius: 50%;
	}

	.sk-title {
		height: 32rpx;
		width: 40%;
	}

	.sk-line {
		height: 24rpx;
		margin-bottom: 12rpx;
	}

	.sk-line:last-child {
		margin-bottom: 0;
	}

	.sk-card {
		background: #fff;
		border-radius: 20rpx;
		padding: 30rpx;
		margin-bottom: 20rpx;
	}

	.sk-item {
		height: 100rpx;
		border-radius: 12rpx;
		margin-bottom: 16rpx;
	}

	.sk-btn {
		height: 96rpx;
		border-radius: 20rpx;
	}

	@keyframes sk-shimmer {
		0% {
			background-position: 100% 50%;
		}
		100% {
			background-position: 0 50%;
		}
	}
</style>
