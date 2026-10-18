<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">账号注销</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<!-- 加载中 -->
			<cb-load-fail />
			<cb-loading v-if="loading" />

			<!-- 未开启 -->
			<view v-else-if="!enabled" class="state-box">
				<text class="state-text">账号注销功能维护中，如有相关问题，请联系客服处理</text>
			</view>

			<block v-else>
				<!-- 已在冷静期 -->
				<view v-if="isCancelling" class="state-box">
					<text class="state-text">您的账号已提交注销申请，将于 {{ expireTimeText }} 正式注销。</text>
					<text class="state-tip">如需撤销，重新登录即可自动撤销。</text>
				</view>

				<block v-else>
					<!-- 步骤指示器 -->
					<view class="steps">
						<view class="step" v-for="(label, i) in stepLabels" :key="i">
							<view class="step-dot" :class="dotClass(i)">
								<text v-if="i + 1 < currentStep" class="dot-check">✓</text>
								<text v-else class="dot-num">{{ i + 1 }}</text>
							</view>
							<view class="step-label" :class="labelClass(i)">{{ label }}</view>
							<view v-if="i < stepLabels.length - 1" class="step-line" :class="{ 'line-done': (i + 2) < currentStep }"></view>
						</view>
					</view>

					<view class="form-card">
						<!-- 步骤1：阅读协议（协议未发布时不展示协议、无需勾选） -->
						<block v-if="currentStep === 1">
							<view class="card-title">{{ hasAgreement ? '阅读并同意《用户注销服务协议》' : '协议确认' }}</view>
							<block v-if="hasAgreement">
								<scroll-view scroll-y class="agreement-box">
									<rich-text :nodes="agreementHtml" class="agreement-text"></rich-text>
								</scroll-view>
								<view class="check-row" @click="agreeAgreement = !agreeAgreement">
									<view class="check-box" :class="{ checked: agreeAgreement }">
										<text v-if="agreeAgreement" class="check-icon">✓</text>
									</view>
									<text class="check-text">我已完整阅读并同意《用户注销服务协议》</text>
								</view>
							</block>
							<view v-else class="alert alert-warning">
								<text>确定要注销账号吗？您的所有数据与珍贵回忆都将被清除，无法找回。我们很不舍与您告别，如果只是遇到问题，欢迎联系我们帮您解决。若仍要注销，请点击下一步继续，注销操作不可逆。</text>
							</view>
						</block>

						<!-- 步骤2：风险告知 -->
						<block v-else-if="currentStep === 2">
							<view class="card-title">注销风险告知</view>
							<view class="alert alert-danger">
								<text v-if="coolingEnable">提交后进入 {{ coolingDays }} 天冷静期，到期后账号及以下数据将被永久删除且无法恢复；冷静期内登录账号即可撤销。</text>
								<text v-else>提交后账号及以下数据将被立即永久删除且无法恢复。</text>
							</view>
							<view class="risk-list">
								<text class="risk-title">注销后将删除以下数据：</text>
								<text class="risk-item">· 账号基本资料（用户名、邮箱、昵称、头像、积分、等级、微信绑定等）</text>
								<text class="risk-item">· 宿舍绑定：<text class="risk-value">{{ summary.dorm_count }}</text> 个</text>
								<text class="risk-item">· 教务账号绑定：<text class="risk-value">{{ summary.academic_bound ? summary.academic_student_id : '未绑定' }}</text></text>
								<text class="risk-item">· 第三方登录绑定：<text class="risk-value">{{ summary.oauth_count }}</text> 个</text>
								<text class="risk-item">· 微信订阅记录：<text class="risk-value">{{ summary.sub_count }}</text> 条；推送设置：<text class="risk-value">{{ summary.push_count }}</text> 条</text>
								<text class="risk-item">· 成绩/课程/电费等各类推送记录与日志</text>
							</view>
							<view class="check-row" @click="agreeRisk = !agreeRisk">
								<view class="check-box" :class="{ checked: agreeRisk }">
									<text v-if="agreeRisk" class="check-icon">✓</text>
								</view>
								<text class="check-text">我已阅读并知晓上述后果，确认自愿注销</text>
							</view>
						</block>

						<!-- 步骤3：身份核验 -->
						<block v-else-if="currentStep === 3">
							<view class="card-title">身份核验</view>

							<view v-if="needPassword" class="form-item">
								<view class="form-label">登录密码</view>
								<input class="form-input" type="password" placeholder="请输入当前登录密码" :value="password" @input="onPasswordInput" />
							</view>

							<view v-if="needEmail" class="form-item">
								<view class="form-label">邮箱验证码</view>
								<view class="code-row">
									<input class="form-input code-input" type="number" placeholder="请输入邮箱验证码" maxlength="6" :value="emailCode" @input="onEmailCodeInput" />
									<button class="code-btn" :disabled="sending || countdown > 0" @click="handleSendCode">
										{{ countdown > 0 ? countdown + 's' : (sending ? '发送中' : '发送验证码') }}
									</button>
								</view>
								<text class="form-tip">验证码将发送至您的注册邮箱</text>
							</view>

							<!-- 安全验证：H5 / App 端为腾讯云滑块（异常可改用图片验证码），小程序端不渲染 -->
							<captcha-verify ref="captcha" variant="form" />
						</block>

						<!-- 步骤4：确认提交 -->
						<block v-else>
							<view class="card-title">最终确认</view>
							<view class="alert alert-warning">
								<text v-if="coolingEnable">确认提交后，账号将进入 {{ coolingDays }} 天冷静期；冷静期内登录即可撤销。</text>
								<text v-else>确认提交后，账号及全部个人数据将被立即永久删除。</text>
							</view>
							<view class="form-label">请手动输入以下确认短语：</view>
							<view class="phrase-box">
								<text class="phrase-text" :user-select="false">{{ confirmPhrase }}</text>
							</view>
							<input class="form-input phrase-input" placeholder="请手动输入上方短语" :value="confirmText" @input="onConfirmTextInput" />
							<text class="form-tip">为确保系本人操作，请手动输入上方短语。</text>
						</block>

						<!-- 导航按钮 -->
						<view class="nav-buttons">
							<button v-if="currentStep === 1" class="btn btn-ghost" @click="goCancel">取消</button>
							<button v-else class="btn btn-ghost" @click="goPrev">上一步</button>
							<button v-if="currentStep < 4" class="btn btn-primary" :disabled="!nextEnabled" @click="goNext">下一步</button>
							<button v-else class="btn btn-danger" :disabled="!submitEnabled || submitting" :loading="submitting" @click="handleSubmit">提交注销申请</button>
						</view>
					</view>
				</block>
			</block>
		</view>
	</view>
</template>

<script>
/**
 * 账号注销页面（分步表单 + 步骤指示器）
 * 与 Web 端流程一致：协议 → 风险告知 → 身份核验 → 确认提交
 */
import api from '../../utils/api.js';
import markdownToHtml from '../../utils/markdown.js';
import validate from '../../utils/validate.js';

export default {
	data() {
		return {
			loading: true,
			enabled: false,
			needPassword: false,
			needEmail: false,
			coolingEnable: false,
			coolingDays: 10,
			agreement: '',
			agreementPublished: true, // 协议是否已发布（后台停用后为 false：不展示协议、无需勾选）
			confirmPhrase: '我已确认注销后用户数据无法找回并确认注销',
			isCancelling: false,
			expireTime: '',
			summary: {
				dorm_count: 0,
				academic_bound: 0,
				academic_student_id: '',
				oauth_count: 0,
				sub_count: 0,
				push_count: 0
			},

			stepLabels: ['阅读协议', '风险告知', '身份核验', '确认提交'],
			currentStep: 1,

			agreeAgreement: false,
			agreeRisk: false,
			password: '',
			emailCode: '',
			confirmText: '',

			sending: false,
			countdown: 0,
			timer: null,
			submitting: false,
			verifying: false,
			identityPassed: false,
			verifyPass: ''
		};
	},

	computed: {
		expireTimeText() {
			return this.fmtDate(this.expireTime);
		},
		agreementHtml() {
			// 协议正文支持图片：相对路径补全为完整地址
			return markdownToHtml(this.agreement || '', { resolveUrl: api.getFileUrl });
		},
		// 是否存在需要阅读确认的协议（后台未发布时不需要）
		hasAgreement() {
			return !!this.agreementPublished && String(this.agreement || '').trim() !== '';
		},
		nextEnabled() {
			if (this.currentStep === 1) return this.hasAgreement ? this.agreeAgreement : true;
			if (this.currentStep === 2) return this.agreeRisk;
			if (this.currentStep === 3) {
				if (this.needPassword && !this.password.trim()) return false;
				// 邮箱验证码为 6 位数字（与后端生成规则一致），避免无效核验请求
				if (this.needEmail && !validate.isDigits(this.emailCode, 6)) return false;
				return true;
			}
			return true;
		},
		submitEnabled() {
			return (!this.hasAgreement || this.agreeAgreement) && this.agreeRisk && this.identityPassed && this.confirmText.trim() === this.confirmPhrase;
		}
	},

	onLoad() {
		if (!api.isLoggedIn()) {
			uni.showToast({ title: '请先登录', icon: 'none' });
			setTimeout(() => uni.navigateBack(), 800);
			return;
		}
		this.loadConfig();
	},

	onUnload() {
		if (this.timer) {
			clearInterval(this.timer);
			this.timer = null;
		}
	},

	methods: {
		loadConfig() {
			this.loading = true;
			api.getCancelConfig()
				.then(res => {
					const d = res.data || {};
					this.enabled = parseInt(d.enabled) === 1;
					this.needPassword = !!d.need_password;
					this.needEmail = !!d.need_email;
					this.coolingEnable = parseInt(d.cooling_enable) === 1;
					this.coolingDays = parseInt(d.cooling_days) || 10;
					this.agreement = d.agreement || '';
					// 协议未发布（后台停用 → agreement_published=0 且内容为空）：不展示协议、无需勾选
					this.agreementPublished = String(this.agreement).trim() !== '' && String(d.agreement_published) !== '0';
					this.stepLabels = this.agreementPublished
						? ['阅读协议', '风险告知', '身份核验', '确认提交']
						: ['协议确认', '风险告知', '身份核验', '确认提交'];
					if (d.confirm_phrase) this.confirmPhrase = d.confirm_phrase;
					this.isCancelling = parseInt(d.is_cancelling) === 1;
					this.expireTime = d.expire_time || '';
					if (d.summary) {
						this.summary = Object.assign({
							dorm_count: 0,
							academic_bound: 0,
							academic_student_id: '',
							oauth_count: 0,
							sub_count: 0,
							push_count: 0
						}, d.summary);
					}
					this.loading = false;
				})
				.catch(err => {
					this.loading = false;
					uni.showToast({ title: err.msg || '加载失败', icon: 'none' });
				});
		},

		dotClass(i) {
			const step = i + 1;
			if (step < this.currentStep) return 'done';
			if (step === this.currentStep) return 'active';
			return '';
		},

		labelClass(i) {
			const step = i + 1;
			if (step < this.currentStep) return 'label-done';
			if (step === this.currentStep) return 'label-active';
			return '';
		},

		onPasswordInput(e) {
			this.password = e.detail.value;
		},

		onEmailCodeInput(e) {
			this.emailCode = e.detail.value;
		},

		onConfirmTextInput(e) {
			this.confirmText = e.detail.value;
		},

		goCancel() {
			uni.showModal({
				title: '提示',
				content: '确定要取消注销操作吗？',
				success: res => {
					if (!res.confirm) return;
					const pages = typeof getCurrentPages === 'function' ? getCurrentPages() : [];
					if (pages && pages.length > 1) {
						uni.navigateBack();
					} else {
						uni.switchTab({ url: '/pages/white-home/index' });
					}
				}
			});
		},

		goNext() {
			// 第三步「下一步」：先向服务端核验身份，通过后才进入第四步
			if (this.currentStep === 3) {
				if (this.identityPassed) {
					this.currentStep = 4;
					return;
				}
				if (!this.nextEnabled) {
					uni.showToast({ title: '请先完成当前步骤', icon: 'none' });
					return;
				}

				// 安全验证（仅 H5 / App 端存在验证码组件）：与官网注销流程一致，核验前先完成
				let captchaData = {};
				// #ifdef H5 || APP-PLUS
				const cap = this.$refs.captcha;
				if (cap) {
					const capCheck = cap.check();
					if (!capCheck.ok) {
						uni.showToast({ title: capCheck.msg || '请先完成安全验证', icon: 'none' });
						return;
					}
					captchaData = capCheck.data || {};
				}
				// #endif

				this.verifyIdentity(captchaData);
				return;
			}
			if (!this.nextEnabled) {
				uni.showToast({ title: '请先完成当前步骤', icon: 'none' });
				return;
			}
			if (this.currentStep < 4) {
				this.currentStep++;
			}
		},

		// captchaData：安全验证参数（仅 H5 / App 端由验证码组件提供，其它端为空对象）
		verifyIdentity(captchaData) {
			if (this.verifying) return;
			this.verifying = true;
			uni.showLoading({ title: '核验中...' });
			api.verifyCancelIdentity(Object.assign({
				password: this.password,
				email_code: this.emailCode
			}, captchaData || {}))
				.then(res => {
					uni.hideLoading();
					this.verifying = false;
					this.identityPassed = true;
					this.verifyPass = (res.data && res.data.pass) ? res.data.pass : '';
					this.currentStep = 4;
					uni.showToast({ title: '身份核验通过', icon: 'none' });
				})
				.catch(err => {
					uni.hideLoading();
					this.verifying = false;
					uni.showToast({ title: (err && err.msg) || '身份核验失败', icon: 'none' });
				});
		},

		goPrev() {
			if (this.currentStep > 1) {
				this.currentStep--;
			}
		},

		handleSendCode() {
			if (this.sending || this.countdown > 0) return;

			// 发送前先通过安全验证（仅 H5 / App 端存在验证码组件）
			let captchaData = {};
			// #ifdef H5 || APP-PLUS
			const cap = this.$refs.captcha;
			if (cap) {
				const capCheck = cap.check();
				if (!capCheck.ok) {
					uni.showToast({ title: capCheck.msg || '请先完成安全验证', icon: 'none' });
					return;
				}
				captchaData = capCheck.data || {};
			}
			// #endif

			this.sending = true;
			api.sendCancelCode(captchaData)
				.then(res => {
					this.sending = false;
					uni.showToast({ title: res.msg || '验证码已发送', icon: 'none' });
					this.startCountdown();
				})
				.catch(err => {
					this.sending = false;
					uni.showToast({ title: err.msg || '发送失败', icon: 'none' });
				});
		},

		startCountdown() {
			this.countdown = 60;
			if (this.timer) clearInterval(this.timer);
			this.timer = setInterval(() => {
				this.countdown--;
				if (this.countdown <= 0) {
					clearInterval(this.timer);
					this.timer = null;
				}
			}, 1000);
		},

		handleSubmit() {
			if (!this.submitEnabled || this.submitting) return;

			const warn = this.coolingEnable
				? '确定要提交注销申请吗？提交后将进入冷静期，到期账号将被永久删除。'
				: '确定要注销账号吗？账号及全部个人数据将被立即永久删除，且无法恢复！';

			uni.showModal({
				title: '提示',
				content: warn,
				success: res => {
					if (!res.confirm) return;
					this.doSubmit();
				}
			});
		},

		doSubmit() {
			this.submitting = true;
			uni.showLoading({ title: '处理中...' });

			api.cancelAccount({
				agree: 1,
				password: this.password,
				email_code: this.emailCode,
				confirm_text: this.confirmText.trim(),
				pass: this.verifyPass
			})
				.then(res => {
					uni.hideLoading();
					this.submitting = false;
					api.clearAuth();
					uni.showModal({
						title: '操作成功',
						content: res.msg || '账号已处理',
						showCancel: false,
						success: () => {
							uni.reLaunch({ url: '/pages/login/index' });
						}
					});
				})
				.catch(err => {
					uni.hideLoading();
					this.submitting = false;
					if (err && err.code === -3) {
						// 核验凭据已失效：退回第三步重新核验
						this.identityPassed = false;
						this.verifyPass = '';
						this.currentStep = 3;
					}
					uni.showToast({ title: (err && err.msg) || '注销失败', icon: 'none' });
				});
		}
	}
};
</script>

<style>
page {
	background: #eff1ff;
}
</style>

<style scoped>
.nav-title {
	height: 100%;
	display: flex;
	align-items: center;
	font-size: 32rpx;
	font-weight: bold;
	position: relative;
	z-index: 1;
	color: white;
	letter-spacing: 6rpx;
}

.background-wrapper {
	position: fixed;
	width: 100%;
	top: 0;
}

.background {
	width: 100%;
	position: absolute;
	top: 0;
	height: 800rpx;
}

.background-mask {
	background-image: linear-gradient(rgba(0, 0, 0, 0.8), transparent);
	height: 600rpx;
	position: absolute;
	width: 100%;
	top: 0;
}

.content-wrapper {
	position: relative;
	z-index: 1;
	background: #eff1ff;
	top: 30rpx;
	min-height: calc(100vh - 200rpx);
	border-top-left-radius: 60rpx;
	border-top-right-radius: 60rpx;
	box-shadow: 0 -80rpx 80rpx 40rpx rgba(0, 0, 0, 0.2);
	padding: 40rpx 30rpx 80rpx;
}

.state-box {
	background: #fff;
	border-radius: 24rpx;
	padding: 80rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.06);
}

.state-text {
	font-size: 28rpx;
	color: #48416f;
	text-align: center;
	line-height: 1.6;
}

.state-tip {
	font-size: 24rpx;
	color: #948eb3;
	margin-top: 16rpx;
}

/* 步骤指示器 */
.steps {
	display: flex;
	align-items: flex-start;
	margin-bottom: 30rpx;
}

.step {
	position: relative;
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.step-dot {
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	border: 4rpx solid #dee2e6;
	background: #fff;
	color: #adb5bd;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 26rpx;
	font-weight: bold;
	position: relative;
	z-index: 1;
	box-sizing: border-box;
}

.step-dot.active {
	border-color: #4c78dd;
	color: #4c78dd;
}

.step-dot.done {
	background: #4c78dd;
	border-color: #4c78dd;
	color: #fff;
}

.dot-check {
	color: #fff;
	font-size: 26rpx;
}

.step-label {
	margin-top: 12rpx;
	font-size: 22rpx;
	color: #948eb3;
	text-align: center;
}

.step-label.label-active {
	color: #4c78dd;
	font-weight: bold;
}

.step-label.label-done {
	color: #495057;
}

.step-line {
	position: absolute;
	top: 27rpx;
	left: 50%;
	width: 100%;
	height: 4rpx;
	background: #e5e7eb;
	z-index: 0;
}

.step-line.line-done {
	background: #4c78dd;
}

/* 表单卡片 */
.form-card {
	background: #fff;
	border-radius: 24rpx;
	padding: 40rpx 30rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.06);
}

.card-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 24rpx;
}

.agreement-box {
	height: 420rpx;
	background: #fafafa;
	border: 2rpx dashed #e0e0e6;
	border-radius: 16rpx;
	padding: 20rpx;
	margin-bottom: 24rpx;
	box-sizing: border-box;
}

/* 协议容器：内部样式由 Markdown 转换器以内联样式注入 */
.agreement-text {
	display: block;
	font-size: 26rpx;
	color: #48416f;
	line-height: 1.7;
	word-break: break-word;
}

.alert {
	border-radius: 16rpx;
	padding: 20rpx;
	font-size: 26rpx;
	line-height: 1.6;
	margin-bottom: 24rpx;
}

.alert-danger {
	background: #fdecec;
	color: #c0392b;
}

.alert-warning {
	background: #fef5e7;
	color: #b9770e;
}

.risk-list {
	margin-bottom: 24rpx;
}

.risk-title {
	display: block;
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 12rpx;
}

.risk-item {
	display: block;
	font-size: 25rpx;
	color: #6b6786;
	line-height: 1.8;
}

.risk-value {
	color: #c0392b;
	font-weight: bold;
}

.form-item {
	margin-bottom: 32rpx;
}

.form-label {
	font-size: 28rpx;
	color: #48416f;
	margin-bottom: 16rpx;
	font-weight: bold;
}

.form-input {
	width: 100%;
	height: 88rpx;
	padding: 0 30rpx;
	border: 2rpx solid #e5e5e5;
	border-radius: 20rpx;
	font-size: 28rpx;
	box-sizing: border-box;
	background: #f7f8fc;
	color: #48416f;
}

.form-tip {
	display: block;
	font-size: 22rpx;
	color: #948eb3;
	margin-top: 12rpx;
}

.code-row {
	display: flex;
	align-items: center;
}

.code-input {
	flex: 1;
	width: auto;      /* 覆盖 .form-input 的 width:100%，避免与 flex 布局冲突 */
	min-width: 0;
	margin-right: 32rpx;
}

.code-btn {
	margin-left: 0;   /* 间距统一由 .code-input 的 margin-right 控制（button 自带 margin:auto 会吃掉间距） */
	height: 88rpx;
	line-height: 88rpx;
	padding: 0 26rpx;
	font-size: 26rpx;
	color: #13c2c2;
	background: #e6fbfb;
	border-radius: 20rpx;
	border: none;
}

.code-btn::after {
	border: none;
}

.check-row {
	display: flex;
	align-items: flex-start;
	padding: 10rpx 0;
}

.check-box {
	width: 40rpx;
	height: 40rpx;
	border: 3rpx solid #cfd3dc;
	border-radius: 8rpx;
	flex-shrink: 0;
	margin-right: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-sizing: border-box;
}

.check-box.checked {
	background: #4c78dd;
	border-color: #4c78dd;
}

.check-icon {
	color: #fff;
	font-size: 26rpx;
}

.check-text {
	flex: 1;
	font-size: 26rpx;
	color: #48416f;
	line-height: 1.5;
}

.phrase-box {
	background: #fafafa;
	border: 2rpx dashed #dc3545;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
}

.phrase-text {
	font-size: 28rpx;
	font-weight: bold;
	color: #dc3545;
	line-height: 1.6;
}

.phrase-input {
	margin-bottom: 8rpx;
}

.nav-buttons {
	display: flex;
	gap: 24rpx;
	margin-top: 40rpx;
}

.btn {
	flex: 1;
	height: 92rpx;
	line-height: 92rpx;
	border-radius: 20rpx;
	font-size: 30rpx;
	font-weight: bold;
	text-align: center;
	border: none;
}

.btn::after {
	border: none;
}

.btn-primary {
	background: linear-gradient(135deg, #13c2c2 0%, #10aeff 100%);
	color: #fff;
}

.btn-danger {
	background: #e74c3c;
	color: #fff;
}

.btn-ghost {
	background: #f0f0f5;
	color: #6b6786;
}

/* 不可点击状态：统一为实心浅灰样式（比默认置灰略深，便于辨识） */
.btn[disabled] {
	background: #cfd3dc;
	color: #ffffff;
	opacity: 1;
}

/* 提高选择器权重，覆盖小程序内置的 button[disabled] 默认浅灰样式 */
button.btn.btn-primary[disabled],
button.btn.btn-danger[disabled] {
	background: #cfd3dc;
	color: #ffffff;
	opacity: 1;
}
</style>
