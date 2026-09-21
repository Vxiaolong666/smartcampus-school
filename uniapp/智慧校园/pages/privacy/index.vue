<template>
	<view class="container">
		<cb-nav-bar :show-back="true">
			<view class="nav-title">隐私协议</view>
		</cb-nav-bar>

		<view class="background-wrapper">
			<image class="background" src="/static/assets/wxmp-banner.jpg" mode="aspectFill"></image>
			<view class="background-mask"></view>
		</view>

		<view class="content-wrapper">
			<view class="policy-card">
				<view class="card-header">
					<image class="card-logo" src="/static/assets/wxmp-logo.svg" mode="widthFix"></image>
					<view class="card-title">隐私协议</view>
				</view>

				<view class="policy-content">
					<view class="policy-update">最后更新日期：2026年9月21日　生效日期：2026年9月1日</view>

					<view class="policy-note">
						我们深知个人信息对您的重要性，并会尽全力保护您的个人信息安全可靠。请您在使用本服务前，仔细阅读并充分理解本政策，特别是以粗体标识的、与您的权利及个人信息处理规则相关的条款。当您勾选同意或开始使用本服务，即表示您已充分理解并同意本政策的全部内容。
					</view>

					<view class="policy-section">
						<view class="section-title">一、引言</view>
						<view class="section-text">
							本《隐私政策》（以下简称"本政策"）适用于本小程序及配套应用（以下统称"本服务"）。我们承诺依照《中华人民共和国个人信息保护法》《中华人民共和国网络安全法》《中华人民共和国数据安全法》等法律法规，遵循合法、正当、必要和诚信原则处理您的个人信息，并恪守权责一致、目的明确、选择同意、最少够用、确保安全、主体参与、公开透明等原则。
						</view>
						<view class="section-text">
							如您不同意本政策的任何内容，请立即停止使用本服务；您继续使用本服务，即视为您已充分理解并同意本政策。
						</view>
					</view>

					<view class="policy-section">
						<view class="section-title">二、定义与释义</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>个人信息：以电子或者其他方式记录的与已识别或者可识别的自然人有关的各种信息，不包括匿名化处理后的信息。</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>敏感个人信息：一旦泄露或者非法使用，容易导致自然人的人格尊严受到侵害或者人身、财产安全受到危害的个人信息，如账号密码、行踪轨迹等。</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>个人信息处理：包括个人信息的收集、存储、使用、加工、传输、提供、公开、删除等。</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>儿童：指未满十四周岁的未成年人。</text></view>
					</view>

					<view class="policy-section">
						<view class="section-title">三、我们如何收集和使用您的个人信息</view>
						<view class="section-text">我们仅出于本政策所述目的，收集和使用实现服务功能所必需的信息。若您不提供相关信息，可能无法使用对应功能，但不影响您使用其他功能。</view>

						<view class="section-sub">3.1 注册与登录</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>账号信息：您注册或登录时提供的手机号，以及经加密存储的登录密码；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>微信授权信息：使用微信登录时，获取的微信昵称、头像及用户唯一标识（OpenID／UnionID）。</text></view>

						<view class="section-sub">3.2 身份与绑定信息</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>教务账号：您主动绑定的学号及教务系统登录凭证（仅用于向学校教务系统发起查询）；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>宿舍信息：校区、楼栋、房间号及电表户号等用于电费查询的绑定信息。</text></view>

						<view class="section-sub">3.3 为您提供的核心服务</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>电费查询与用电统计：基于余额变化计算并展示用电量与费用记录；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>教务信息查询：课程表、成绩、考试安排等信息的查询与展示；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>AI 智能分析：将您主动触发的查询结果用于生成分析建议（详见第十一条）。</text></view>

						<view class="section-sub">3.4 消息通知与订阅</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>在您订阅后，向您推送课表、成绩、电费等提醒消息；您可随时在设置中关闭推送或取消订阅。</text></view>

						<view class="section-sub">3.5 设备、日志与安全保障</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>为保障服务稳定运行与账号安全，我们可能收集设备型号、操作系统、网络类型、访问时间及必要的运行日志。</text></view>

						<view class="section-sub">3.6 系统权限调用说明</view>
						<view class="section-text">本服务可能申请以下系统权限，且仅在您主动使用对应功能时触发；您可随时在系统设置中关闭授权：</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>相册／相机：在您更换头像或上传图片时使用；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>剪贴板：在您点击"复制"查询结果等操作时使用；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>网络状态：用于判断网络环境并保障数据加载。</text></view>
					</view>

					<view class="policy-section">
						<view class="section-title">四、我们如何使用您的个人信息</view>
						<view class="section-text">我们收集的信息将用于下列目的：</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>向您提供校园信息查询、用电统计、消息推送等核心功能；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>验证您的身份，保障账号与数据安全；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>改进和优化产品功能，提升服务质量与使用体验；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>识别、预防和调查欺诈、危害安全等违法违规行为。</text></view>
						<view class="section-text">我们遵循最小必要原则，仅在实现上述目的所必需的范围内处理您的个人信息。若我们超出上述范围使用您的个人信息，将再次征得您的同意。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">五、我们如何共享、转让、公开披露您的信息</view>
						<view class="section-sub">5.1 共享</view>
						<view class="section-text">我们不会向任何第三方出售您的个人信息。仅在下列情形下，我们可能共享必要的个人信息：已获得您的明确同意；为实现您所请求的服务，需向学校官方系统（如教务系统、水电系统）发起查询；依据法律法规或有权机关的要求。</view>
						<view class="section-sub">5.2 转让</view>
						<view class="section-text">我们不会转让您的个人信息，除非事先取得您的明确同意，或依据法律法规及强制性的行政、司法要求必须转让。</view>
						<view class="section-sub">5.3 公开披露</view>
						<view class="section-text">我们仅会在获得您明确同意或法律法规要求的情况下，公开披露您的个人信息。</view>
						<view class="section-sub">5.4 依法可免于征得同意的情形</view>
						<view class="section-text">根据法律法规规定，在下列情形中我们处理您的个人信息无需事先征得您的同意：为订立、履行您作为一方当事人的合同所必需；为履行法定职责或法定义务所必需；为应对突发公共卫生事件或紧急情况下为保护自然人的生命健康和财产安全所必需；依照法律规定在合理的范围内处理您自行公开或其他已经合法公开的个人信息；法律法规规定的其他情形。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">六、我们如何存储和保护您的信息</view>
						<view class="section-sub">6.1 存储地点</view>
						<view class="section-text">您的个人信息将存储于中华人民共和国境内，我们不会将您的个人信息传输至境外。</view>
						<view class="section-sub">6.2 保存期限</view>
						<view class="section-text">除法律法规另有规定外，我们仅在为您提供服务所必需的最短期限内保留您的信息。超出保留期限后，我们将对您的个人信息进行删除或匿名化处理。</view>
						<view class="section-sub">6.3 安全措施</view>
						<view class="section-text">我们采用加密存储、传输加密、访问控制、权限管理等合理的技术与管理措施，防止您的个人信息被未经授权地访问、泄露、篡改或丢失。</view>
						<view class="section-sub">6.4 安全事件处置</view>
						<view class="section-text">若不幸发生个人信息安全事件，我们将立即启动应急预案，采取补救措施，并依法及时以推送、公告等方式告知您事件情况、可能的影响及您可采取的应对措施，同时向有关主管部门报告。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">七、Cookie 及同类技术</view>
						<view class="section-text">为提升服务体验，我们可能使用 Cookie、本地缓存等同类技术保存您的登录状态与偏好设置。您可以通过系统设置清除本地缓存，但清除后可能需要重新登录，或影响部分功能的正常使用。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">八、我们如何使用自动化决策</view>
						<view class="section-text">本服务不基于自动化决策方式向您进行信息推送或商业营销。消息推送均基于您主动订阅的关系进行；如您不希望接收，可随时关闭推送或取消订阅。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">九、您的权利</view>
						<view class="section-text">按照相关法律法规，您对自己的个人信息享有以下权利：</view>
						<view class="section-sub">9.1 查阅与复制</view>
						<view class="section-text">您可在"我的"页面查看您的账号信息与绑定信息。</view>
						<view class="section-sub">9.2 更正与补充</view>
						<view class="section-text">如发现信息有误，您可自行修改或联系我们协助更正。</view>
						<view class="section-sub">9.3 删除</view>
						<view class="section-text">在符合法定情形时，您可要求我们删除您的个人信息。</view>
						<view class="section-sub">9.4 撤回同意</view>
						<view class="section-text">您可解绑相关账号、关闭消息推送或撤回相应授权；撤回同意不影响撤回前基于同意已进行的处理。</view>
						<view class="section-sub">9.5 注销账户</view>
						<view class="section-text">您可通过"注销账户"功能申请注销，注销后我们将删除或匿名化您的个人信息。</view>
						<view class="section-sub">9.6 投诉与举报</view>
						<view class="section-text">如您认为个人信息处理活动侵害了您的合法权益，可通过本政策第十四条所述方式向我们投诉、举报。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十、未成年人保护</view>
						<view class="section-text">我们高度重视未成年人个人信息的保护。若您是未满十四周岁的儿童，请在监护人陪同下阅读本政策，并在取得监护人同意后使用本服务；若您是未满十八周岁的未成年人，请在监护人指导下使用本服务。若我们发现在未事先取得可证实的监护人同意的情况下收集了儿童个人信息，将尽快予以删除。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十一、第三方服务</view>
						<view class="section-text">为实现微信登录、微信消息推送、AI 智能分析等功能，本服务可能接入微信开放平台及相关第三方服务。相关数据将由第三方依据其隐私政策进行处理，我们建议您在使用前查阅并了解第三方的隐私条款。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十二、本政策的更新</view>
						<view class="section-text">我们可能适时修订本政策。修订后，我们将在本页面发布更新版本并同步更新"最后更新日期"；对于重大变更，我们将通过弹窗、站内通知等显著方式另行告知。若您在政策更新后继续使用本服务，即表示您同意受修订后政策的约束。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十三、适用法律与争议解决</view>
						<view class="section-text">本政策的订立、生效、履行、解释及争议解决均适用中华人民共和国法律。如您与我们之间就本政策发生争议，双方应友好协商解决；协商不成的，您可依法向有管辖权的人民法院提起诉讼。</view>
					</view>

					<view class="policy-section">
						<view class="section-title">十四、如何联系我们</view>
						<view class="section-text">如您对本政策或个人信息处理有任何疑问、意见或投诉，可通过以下方式联系我们：</view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>在"我的"页面使用"联系客服"功能与我们取得联系；</text></view>
						<view class="bullet-item"><text class="bullet-dot">·</text><text>通过客服提供的电话、邮箱等联系方式与我们沟通。</text></view>
						<view class="section-text">我们将在收到您的请求后，于合理期限内予以答复。</view>
					</view>
				</view>

				<view class="policy-footer">
					<text class="footer-text">感谢您信任并使用本服务</text>
				</view>
			</view>

			<view class="policy-actions">
				<view class="policy-action-btn" @click="goAbout">
					<image class="policy-action-icon" src="/static/assets/icon_cvoieudado/a-199_zhuye.svg" mode="widthFix"></image>
					<text class="policy-action-text">关于我们</text>
				</view>

				<!-- #ifdef MP-WEIXIN -->
				<button class="policy-action-btn" open-type="contact">
					<image class="policy-action-icon" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
					<text class="policy-action-text">联系客服</text>
				</button>
				<!-- #endif -->

				<!-- #ifndef MP-WEIXIN -->
				<view class="policy-action-btn" @click="onContact">
					<image class="policy-action-icon" src="/static/assets/icon_cvoieudado/a-199_duihua.svg" mode="widthFix"></image>
					<text class="policy-action-text">联系客服</text>
				</view>
				<!-- #endif -->
			</view>
		</view>

		<!-- 联系客服自定义弹窗（非小程序端） -->
		<contact-modal :show="contactShow" :lines="contactLines" @close="contactShow = false" />
	</view>
</template>

<script>
import api from '../../utils/api.js';

export default {
	data() {
		return {
			contactShow: false,
			contactLines: []
		};
	},
	methods: {
		goAbout() {
			uni.navigateTo({ url: '/pages/about/index' });
		},
		// 联系客服（非小程序端）：弹窗展示后台「首页配置」中维护的联系方式
		async onContact() {
			uni.showLoading({ title: '加载中...' });
			let info = null;
			try {
				const res = await api.getContactInfo();
				info = (res && res.data) || null;
			} catch (e) {
				info = null;
			}
			uni.hideLoading();

			const lines = [];
			if (info) {
				if (info.phone) lines.push('联系电话：' + info.phone);
				if (info.email) lines.push('电子邮箱：' + info.email);
				if (info.qq) lines.push('QQ：' + info.qq);
				if (info.worktime) lines.push('工作时间：' + info.worktime);
				if (info.address) lines.push('联系地址：' + info.address);
				if (info.desc) lines.push('说明：' + info.desc);
			}
			if (!lines.length) {
				lines.push('暂未配置联系方式，请在「我的」页面通过「联系客服」功能与我们取得联系。');
			}

			this.contactLines = lines;
			this.contactShow = true;
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
	padding: 30rpx;
	padding-bottom: 30rpx;
}

.policy-card {
	background: white;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
}

.card-header {
	display: flex;
	align-items: center;
	padding: 40rpx 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.card-logo {
	width: 60rpx;
	height: 60rpx;
	margin-right: 20rpx;
}

.card-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #48416f;
}

.policy-content {
	padding: 30rpx;
}

.policy-update {
	font-size: 24rpx;
	color: #948eb3;
	margin-bottom: 24rpx;
	text-align: right;
}

.policy-note {
	background: #f0fbfb;
	border-radius: 12rpx;
	padding: 20rpx 24rpx;
	margin-bottom: 30rpx;
	font-size: 25rpx;
	color: #48416f;
	line-height: 1.8;
}

.policy-section {
	margin-bottom: 36rpx;
}

.policy-section:last-child {
	margin-bottom: 0;
}

.section-title {
	font-size: 28rpx;
	font-weight: bold;
	color: #48416f;
	margin-bottom: 16rpx;
	padding-left: 16rpx;
	border-left: 6rpx solid #13c2c2;
}

.section-sub {
	font-size: 26rpx;
	font-weight: 600;
	color: #48416f;
	margin: 20rpx 0 12rpx;
}

.section-text {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	text-indent: 2em;
}

.bullet-item {
	font-size: 26rpx;
	color: #767676;
	line-height: 1.8;
	display: flex;
	align-items: flex-start;
	padding-left: 2em;
}

.bullet-dot {
	margin-right: 12rpx;
	color: #13c2c2;
	font-weight: bold;
}

.policy-footer {
	padding: 30rpx;
	text-align: center;
	border-top: 1rpx solid #f0f0f0;
}

.footer-text {
	font-size: 22rpx;
	color: #948eb3;
}

.policy-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 30rpx;
}

.policy-action-btn {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	height: 96rpx;
	padding: 0;
	margin: 0;
	background: #ffffff;
	border-radius: 16rpx;
	box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.05);
	font-size: 28rpx;
	font-weight: 500;
	color: #48416f;
	line-height: normal;
}

.policy-action-btn::after {
	border: none;
}

.policy-action-icon {
	width: 36rpx;
	height: 36rpx;
}

.policy-action-text {
	font-size: 28rpx;
	color: #48416f;
}
</style>
