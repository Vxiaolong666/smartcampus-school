/*
 * @author: CloudZA(云之安) <2922619853@qq.com>
 * @hitokoto: 一场秋雨一场凉，秋心酌满泪为霜。
 * Copyright (c) 2022 by CloudZA, All Rights Reserved.
 */

app = {
    /**
     * Ajax请求
     *
     * @param url {string} URL
     * @param data 请求的参数，为空则是GET请求
     * @param success {function} 成功的自定义事件
     * @param load {boolean} 是否显示加载
     */
    ajax: function (url, data, success, fail) {
        let loading = layer.load(2);
        $.ajax({
            url     : url,
            data    : data,
            type    : (data === null || data === undefined) ? 'GET' : 'POST',
            cache   : false,
            dataType: 'json',
            timeout : 30000,
            success : function (data) {
                if (data.code === -1 || data.code === -2) {
                    if (typeof (fail) === 'function') {
                        fail(data)
                    } else {
                        app.notify(data.msg || '操作失败', 'danger');
                    }
                } else if (typeof (success) === 'function') {
                    success(data)
                }
            },
            error   : function (xhr, status, error) {
                if (typeof (fail) === 'function') {
                    fail(xhr)
                } else {
                    app.notify('网络链接错误', 'danger');
                }
            },
            complete: function () {
                // 成功、失败或超时都关闭遮罩，避免页面一直处于“卡住”状态
                app.close(loading);
            }
        })
    },

    /**
     *
     * @param url 链接
     * @param parameter 参数
     * @param callback 回调
     * @param ajaxType POST/GET
     */
    postData: function (url, parameter, callback, callerror, ajaxType, ajaxTime) {
        ajaxType = ajaxType || "POST";
        ajaxTime = ajaxTime || 60000;
        var loading = layer.load(2);
        $.ajax({
            type    : ajaxType,
            url     : url,
            async   : true,
            dataType: 'json',
            timeout : ajaxTime,
            data    : parameter,
            success : function (data) {
                if (callback == null) {
                    return;
                }
                if (data.code === -1 || data.code === -2) {
                    if (typeof (callerror) === 'function') {
                        callerror(data)
                    } else {
                        app.notify(data.msg || '操作失败', 'danger');
                    }
                } else {
                    callback(data);
                }
            },
            error   : function (xhr, status, error) {
                if (typeof (callerror) === 'function') {
                    callerror(xhr)
                } else {
                    app.notify('网络链接错误', 'danger');
                }
            },
            complete: function () {
                app.close(loading);
            }
        });
    },
    /**
     * 获取元素的值或者给元素添加内容
     *
     * @param {string|object} selector - 元素的ID或者对象
     * @param {string|number} [value] - 要添加的内容
     * @returns {string} - 元素的值或者空字符串
     */
    getval: function (selector, value) {
        const element = $(selector);

        if (typeof selector !== 'string' || !element.length) {
            return;
        }

        if (value === null || value === undefined) {
            // 返回元素的值
            return element[element.is('input, textarea, select') ? 'val' : 'html']();
        }

        const isInput = element.is('input, textarea, select');
        const currentValue = isInput ? element.val() : element.text();

        element[isInput ? 'val' : 'html'](value);

        if (currentValue !== value) {
            // app.notify('修改失败，请检查', 'warning');
        }

        else {
            return '';
        }
    },

    /**
     * 显示通知
     *
     * @param {string} message  描述
     * @param {string} type  类型(info/success/warning/danger)
     * @param {string} align  显示位置(top/buttom)
     */
    notify: function (message, type = 'info', align = 'right') {
        const icons = {
            'info'   : 'fa fa-info-circle me-1',
            'success': 'fa fa-check me-1',
            'warning': 'fa fa-exclamation me-1',
            'danger' : 'fa fa-times me-1'
        };
        const icon = icons[type] || icons['info'];
        One.helpers('jq-notify', {type, icon, message});
    },

    /**
     * Pjax重定向页面内容
     * @param url {string} URL地址
     */
    pjax: function (url = '') {
        if (url === '') {
            window.location.reload();
        }
        else {
            $.pjax({
                url      : url,
                container: '#pjax-container',
                fragment : '#pjax-container',
                timeout  : 8000,
            });
        }
    },

    reload: function () {
        $("#nav-main a").each(function () {
            var pageUrl = window.location.href.split(/[?#]/)[0]; // window.location.href.split(/[?#]/)[0];
            if (this.href == pageUrl && window.location.href.split(/[?#]/)[0]) {
                $(this).parent().parent().parent().addClass("open");
                $(this).addClass("active");
            }
            else {
                $(this).removeClass("active");
            }
        });
    },
    close : function (a) {
        if (typeof (a) === "number") {
            layer.close(a)
        }
        else {
            if (a) {
                layer.closeAll(a)
            }
            else {
                layer.closeAll()
            }
        }
    },

    /**
     * 弹出一个确认对话框
     *
     * @param {string} d - 弹框内容
     * @param {string} yesText - 确认按钮文本
     * @param {string} noText - 取消按钮文本
     * @param {Function} yesCallback - 确认按钮回调函数
     * @param {Function} noCallback - 取消按钮回调函数
     * @param btnClass - 按钮颜色全部为蓝色
     * @returns {number} 弹窗 index
     */
    btn: function (d, yesText, noText, yesCallback, noCallback, btnClass = false) {
        var e;
        if (d) {
            e = d;
        }
        else {
            e = 'NULL';
        }
        if (!yesText && !noText) {
            return layer.alert(e, {
                closeBtn: 0,
                btnAlign: "c",
                yes     : function (index) {
                    layer.close(index);
                }
            });
        }
        var btnArr = [];
        if (yesText) {
            btnArr.push(yesText);
        }
        if (noText) {
            btnArr.push(noText);
        }
        var BtnPop = layer.confirm(e, {
            btn     : btnArr,
            closeBtn: 2,
            btnAlign: "c",
            yes     : function (index) {
                if (yesCallback && typeof yesCallback === 'function') {
                    yesCallback(index);
                    layer.close(index);
                }
                else {
                    app.close(index);
                }
            },

            btn2: function (index) {
                if (noCallback && typeof noCallback === 'function') {
                    noCallback(index);
                    layer.close(index);
                }
                else {
                    layer.close(index);
                }
            }
        });
        if (btnClass) {
            const $link = $('a.layui-layer-btn1');
            $link.removeClass('layui-layer-btn1').addClass('layui-layer-btn0');
        }
        return BtnPop;

    },

    /**
     * 打开一个弹出层
     *
     * @param {string|HTMLElement} content - 要在弹出层中显示的内容
     * @param {function=} cancelFunction - 点击取消按钮时执行的回调函数。如果未指定，则没有取消按钮
     */
    openModal: function (content, cancelFunction) {
        if (typeof content === 'string') {
            // 如果content是节点ID，则获取该节点的HTML内容
            var $node = $(content);
            if ($node.length > 0) {
                content = $node.html();
            }
            else if (content.charAt(0) !== '<') {
                // 否则包装在div中
                content = '<div class="block block-rounded block-content" style="margin-bottom: 0 !important;"><div class="text-center fw-semibold fs-sm ">Info</div><div class="mt-4 mb-4">' + content + '</div></div>';
            }
        }
        layer.open({
            type   : 1,
            shade  : false,
            title  : false,
            content: content,
            cancel : function () {
                if (typeof cancelFunction === 'function') {
                    cancelFunction();
                }
            },
        });
    },


    ints: function (b, e, a) {
        e = nv(e, 11);
        var d = $(b).val();
        var c = event.which;
        if (a) {
            if (c == 46) {
                if (d.length < 1 || d.indexOf(".") > 0) {
                    return false
                }
                else {
                    return true
                }
            }
        }
        if (c >= 48 && c <= 57) {
            if (d.length < e) {
                return true
            }
        }
        return false
    },

    /**
     * Charts 绘制折线图和柱状图
     *
     * @param {string} id - 图表元素的ID
     * @param {string} type - 图表类型，可以是 "line" 或 "bar"
     * @param {Array} dates - X轴的标签数组
     * @param {Array} dataSets - 数据集合数组，包含每个数据集的标签、背景颜色、边框颜色、数据等信息
     */
    charts: function (id, type, dates, dataSets) {
        Chart.defaults.color = "#818d96";
        Chart.defaults.font.weight = "600";
        Chart.defaults.scale.grid.color = "rgba(0, 0, 0, .05)";
        Chart.defaults.scale.grid.zeroLineColor = "rgba(0, 0, 0, .1)";
        Chart.defaults.scale.beginAtZero = !0;
        Chart.defaults.elements.line.borderWidth = 2;
        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hoverRadius = 6;
        Chart.defaults.plugins.tooltip.radius = 3;
        Chart.defaults.plugins.legend.labels.boxWidth = 15;

        let chartData = {
            labels  : dates,
            datasets: dataSets.map(dataSet => {
                return {
                    label                    : dataSet.label,
                    fill                     : true,
                    backgroundColor          : dataSet.backgroundColor,
                    borderColor              : dataSet.borderColor,
                    pointBackgroundColor     : dataSet.pointBackgroundColor,
                    pointBorderColor         : dataSet.pointBorderColor,
                    pointHoverBackgroundColor: dataSet.pointHoverBackgroundColor,
                    pointHoverBorderColor    : dataSet.pointHoverBorderColor,
                    data                     : dataSet.data
                };
            })
        };

        let chartElement = document.getElementById(id);
        // let oldChart = Chart.getChart(bars); // 检查是否已经存在一个Chart实例
        // if (oldChart) {
        //     oldChart.destroy(); // 销毁旧的Chart实例
        // }
        if (type === "line") {
            let chart = new Chart(chartElement, {
                type   : "line",
                data   : chartData,
                options: {
                    tension: .4
                }
            });
        }
        else if (type === "bar") {
            let chart = new Chart(chartElement, {
                type: "bar",
                data: chartData,

            });
        }
    },


}
app.reload();

// Bootstrap-table
table = {
    /**
     * 初始化 Bootstrap Table
     *
     * @param {string} id               要初始化的表格的 ID
     * @param {array}  columns          表格列配置
     * @param {array}  data             表格数据
     * @param {object} queryParams      查询参数
     * @param {string} pageSize         每页条数
     */
    initTable: function (id, columns, data, queryParams, pageSize) {
        try {
            $('#' + id).bootstrapTable({
                classes: 'table table-striped table-hover table-bordered',
                // method                : 'post',
                // contentType           : "application/x-www-form-urlencoded", //必须要有！！！！
                sortable              : true, // 是否启用排序
                columns               : columns,
                data                  : data,
                queryParams           : queryParams,
                pageNumber            : 1, //初始化加载第一页，默认第一页,并记录
                pagination            : true,
                paginationClass       : 'pagination pagination-sm',
                paginationDetailHAlign: 'right', // 分页详细信息水平对齐方式
                paginationHAlign      : 'left', // 分页水平对齐方式
                paginationVAlign      : 'bottom', // 分页垂直对齐方式
                search                : true, // 是否显示搜索框
                strictSearch          : false, // 设置为 true启用全匹配搜索，否则为模糊搜索。
                searchHighlight       : true, // 搜索内容高亮
                searchAlign           : 'right col-12', // 搜索框显示位置
                showRefresh           : false, // 是否显示刷新按钮
                showToggle            : false, // 是否显示切换视图按钮
                showColumns           : true, // 是否显示切换列按钮
                showColumnsSearch     : true, // 是否在设置列表显示框中显示搜索框
                clickToSelect         : false, // 是否开启点击行选中功能
                showFullscreen        : true, // 是否显示全屏按钮

                pageSize          : pageSize ?? '10', // 每页显示的条数
                pageList          : [10, 25, 50, 100], // 每页显示条数的可选项
                paginationPreText : '<span aria-hidden="true"> <i class="fa fa-angle-left fa-sm"></i></span>', // 是否显示上一页按钮
                paginationNextText: '<span aria-hidden="true"><i class="fa fa-angle-right fa-sm"></i></span>', // 是否显示下一页按钮
                paginationAlign   : 'left', // 分页对齐方式，'left'、'center'、'right'、
                toolbar           : '#toolbar', //工具按钮用哪个容器
                buttonsClass      : 'light btn-alt-secondary btn-xs', // 工具按钮样式
                icons             : {
                    refresh    : 'fa fa-refresh fa-sm',
                    toggle     : 'fa fa-th-list fa-sm',
                    fullscreen : 'si si-size-fullscreen',
                    columns    : 'fa fa-columns fa-sm',
                    detailOpen : 'fa fa-plus-circle fa-sm',
                    detailClose: 'fa fa-minus-circle fa-sm'
                }
            });
            if ($(".input-daterange").length > 0) {
                $('.input-datepicker, .input-daterange').datepicker({
                    format   : 'yyyy-mm-dd',
                    autoclose: true,
                    clearBtn : true,
                    language : 'zh-CN'
                });
            }
        }
        catch (error) {
            console.error(error);
            alert('出现异常，请查看控制台');
        }
    }
};

/**
 * 根据已用量和总量计算使用百分比。
 *
 * @param {number} used 已用量
 * @param {number} total 总量
 * @param {number} decimal_places 结果中小数点后保留的位数，默认为 2
 * @returns {string} 格式化后的百分比字符串，包括指定数量的小数位数
 */
function calculateUsagePercentage (used, total, decimal_places = 2)
{
    if (total === 0) {
        return '0';
    }
    const percentage = (used / total) * 100;
    return percentage.toFixed(decimal_places);
}

/**
 *给指定节点设置验证码过期倒计时
 *
 * @param countdownTime {int} 过期时间
 * @param NodeID {string} 节点ID #NodeID
 */
function CodeExpiry (countdownTime, NodeID)
{
    // 获取验证码有效期元素
    var expiryElem = $(NodeID);

    // 停止之前的计时器
    if (expiryElem.data('countdownTimer')) {
        clearInterval(expiryElem.data('countdownTimer'));
    }

    // 清空原有内容
    expiryElem.html('');

    // 获取倒计时开始时间
    var startTime = new Date().getTime();

    // 定义计时器函数
    var countdownTimer = setInterval(function () {
        // 计算剩余时间
        var timeLeft = countdownTime - Math.floor((new Date().getTime() - startTime) / 1000);

        if (timeLeft > 0) {
            // 更新验证码有效期文本
            expiryElem.text(timeLeft + '秒');
        }
        else {
            // 停止计时器并更新验证码有效期文本
            clearInterval(countdownTimer);
            expiryElem.text('验证码已过期');
        }
    }, 1000);

    // 保存计时器句柄以便之后停止计时器
    expiryElem.data('countdownTimer', countdownTimer);
}


/**
 * 删除两端空格
 * @param a
 * @returns {*}
 */
function trim (a)
{
    return a.replace(/(^\s*)|(\s*$)/g, "")
}

/**
 *
 * @function isnull
 * @param { any } a - 需要检查的输入参数
 * @returns { boolean } - 如果输入参数为 null 或空字符串, "undefined", false, "false" 或 "null",则返回 true。否则返回 false。
 *
 * 'isnull' 函数用于检查输入参数 'a' 是否为 null 或空字符串。
 * 检查输入参数是否符合以下任何一种条件:
 * - a == null
 * - a == "" (空字符串)
 * - a == "undefined"
 * - a == undefined
 * - a == false
 * - a == "false"
 * - a == "null"
 * 如果任意一种条件为真, 则函数返回 true。否则返回 false。
 */
function isnull (a)
{
    if (a == null || a == "" || a === "undefined" || a === undefined || a === false || a === "false" || a === "null") {
        return true
    }
    return false
}

function nv (b, a)
{
    return (isnull(b)) ? (!isnull(a) ? a : "") : b
}

// pjax 插件已用响应片段替换 #pjax-container，旧实现又二次注入整页 HTML，
// 导致 DOM 膨胀、脚本重复执行、遮罩与监听残留，表现为“越切越卡”。
var loadedScripts = [];
$('script[src]').each(function () {
    loadedScripts.push(this.src);
});
var appPjaxUrl = '';

$(document).pjax('a[data-pjax]', '#pjax-container', {fragment: '#pjax-container', timeout: 8000});

$(document).on('pjax:send', function (event, xhr, options) {
    NProgress.start();
    appPjaxUrl = (options && options.url) ? options.url : window.location.href;
});

$(document).on('pjax:end', function () {
    // 页面级外链脚本只加载一次，公共脚本已在 head 中，不重复插入
    $('#pjax-container').find('script[src]').each(function () {
        var src = this.src;
        if (!src || $.inArray(src, loadedScripts) !== -1) {
            return;
        }
        loadedScripts.push(src);
        var s = document.createElement('script');
        s.src = src;
        document.head.appendChild(s);
    });
});

$(window).on('popstate', function () {
    $.pjax.reload('#pjax-container', {fragment: '#pjax-container', timeout: 8000});
});

$(document).on('pjax:complete', function () {
    if (window.innerWidth <= 991) {
        One.layout('sidebar_close');
    }
    NProgress.done();
    app.reload();
});
$(document).on('pjax:timeout', function () {
    NProgress.done();
    if (window.layer && layer.closeAll) {
        layer.closeAll('loading');
    }
    // 插件对 timeout 的 abort 不会自动整页跳转，这里兜底
    if (appPjaxUrl && appPjaxUrl !== window.location.href) {
        window.location.href = appPjaxUrl;
    }
});
$(document).on('pjax:error', function () {
    NProgress.done();
    if (window.layer && layer.closeAll) {
        layer.closeAll('loading');
    }
    // GET 请求失败时插件已自动整页跳转，这里只做状态清理
});

app.reload();
