import App from './App'
import { formatDate, formatDateTime } from './utils/common.js'

// 全局日期展示方法：所有页面均可直接使用
//   模板中：{{ fmtDateTime(item.create_time) }} / {{ fmtDate(item.create_time) }}
//   逻辑中：this.fmtDate(x) / this.fmtDateTime(x)
// （日期展示统一为 2026/9/21、2026/9/21 12:30）
const dateFormatMixin = {
  methods: {
    fmtDate: formatDate,
    fmtDateTime: formatDateTime
  }
}

// #ifndef VUE3
import Vue from 'vue'
import './uni.promisify.adaptor'
Vue.config.productionTip = false
Vue.mixin(dateFormatMixin)
App.mpType = 'app'
const app = new Vue({
  ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
export function createApp() {
  const app = createSSRApp(App)
  app.mixin(dateFormatMixin)
  return {
    app
  }
}
// #endif
