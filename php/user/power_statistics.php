<?php
$title = '用电统计';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'power_statistics')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'power_statistics');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$bindings = $DB->getAll("SELECT * FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1 ORDER BY id DESC");
$config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
$savingThreshold = floatval($config['usage_saving_threshold'] ?? 0.5);
$normalThreshold = floatval($config['usage_normal_threshold'] ?? 1.0);
$pricePerKwh = floatval($config['price_per_kwh'] ?? 0.61);
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-line me-2"></i>用电统计
                </h3>
                <div class="block-options">
                    <a href="power_fee.php" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-bolt me-1"></i>电费查询
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'power_statistics'); ?>
                
                <?php if(empty($bindings)): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>您还没有绑定宿舍，请先<a href="dormitory_bind.php">绑定宿舍</a>
                </div>
                <?php else: ?>
                
                <div class="row mb-4">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">选择宿舍</label>
                        <select class="form-select" id="roomSelect">
                            <?php foreach($bindings as $binding): ?>
                            <?php 
                            if ($binding['alias']) {
                                $displayName = $binding['alias'] . '（' . $binding['school_area'] . $binding['building'] . ' ' . $binding['room'] . '）';
                            } else {
                                $displayName = $binding['school_area'] . $binding['building'] . ' ' . $binding['room'];
                            }
                            ?>
                            <option value="<?php echo htmlspecialchars($binding['room_num']); ?>">
                                <?php echo htmlspecialchars($displayName); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <label class="form-label">时间范围</label>
                        <select class="form-select" id="timeRange">
                            <option value="all">全部</option>
                            <option value="7">近7天</option>
                            <option value="30" selected>近30天</option>
                            <option value="custom">自定义</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0" id="customStartWrap" style="display:none;">
                        <label class="form-label">开始日期</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0" id="customEndWrap" style="display:none;">
                        <label class="form-label">结束日期</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <button class="btn btn-primary" id="refreshBtn">
                            <i class="fa fa-refresh me-1"></i>刷新数据
                        </button>
                        <button class="btn btn-alt-primary ms-2" onclick="aiAnalyzePower()">
                            <i class="fa fa-robot me-1"></i>AI 智能分析
                        </button>
                    </div>
                </div>
                
                <!-- 统计概览：4 个统计卡片放在本卡片内（由 renderStatistics 填充） -->
                <div id="statsTiles">
                    <div class="text-center py-4">
                        <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2 mb-0">加载中...</p>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(!empty($bindings)): ?>
        <!-- 以下各区块（近24小时趋势 / 每日趋势 / 历史记录 / 趣味账单）均为独立卡片 -->
        <div id="statsContent">
            <div class="text-center py-5">
                <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                <p class="text-muted mt-2">加载中...</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var savingThreshold = <?php echo $savingThreshold; ?>;
var normalThreshold = <?php echo $normalThreshold; ?>;
var pricePerKwh = <?php echo $pricePerKwh; ?>;
var recentChart = null;
var dailyChart = null;

function formatUsage(value, isCost) {
    if (!value || value == 0) {
        return '<span class="text-muted">-</span>';
    }
    
    var absValue = Math.abs(value);
    var formatted = absValue.toFixed(isCost ? 2 : 4);
    
    if (value > 0) {
        return '<span class="text-danger"><i class="fa fa-arrow-down me-1"></i>' + formatted + '</span>';
    } else {
        return '<span class="text-success"><i class="fa fa-arrow-up me-1"></i>' + formatted + '</span>';
    }
}

function loadStatistics() {
    var roomNum = $('#roomSelect').val();
    
    if (!roomNum) {
        $('#statsTiles').empty();
        $('#statsContent').html('<div class="alert alert-warning">请选择宿舍</div>');
        return;
    }
    
    // 时间筛选
    var timeRange = $('#timeRange').val();
    var params = { act: 'get_power_statistics', room_num: roomNum };
    if (timeRange === '7' || timeRange === '30') {
        var days = timeRange === '7' ? 6 : 29;
        var end = new Date();
        var start = new Date();
        start.setDate(start.getDate() - days);
        params.start = formatYmd(start);
        params.end = formatYmd(end);
    } else if (timeRange === 'custom') {
        if ($('#startDate').val()) params.start = $('#startDate').val();
        if ($('#endDate').val()) params.end = $('#endDate').val();
    }

    // 统计概览（卡片内）与下方各卡片：同时显示加载中
    var statsLoading = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2 mb-0">加载中...</p></div>';
    $('#statsTiles').html(statsLoading);
    $('#statsContent').html(statsLoading);
    
    $.ajax({
        url: '../api.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
            if (res.code == 0) {
                renderStatistics(res.data);
            } else {
                $('#statsTiles').empty();
                $('#statsContent').html('<div class="alert alert-danger">' + (res.msg || '获取数据失败') + '</div>');
            }
        },
        error: function() {
            $('#statsTiles').empty();
            $('#statsContent').html('<div class="alert alert-danger">网络错误，请稍后重试</div>');
        }
    });
}

// ===== 趣味账单（周账单 / 半月账单 / 月账单，支持往前翻看历史周期）=====
// 账单数据由 api.php 的 get_power_bill 按周期独立返回，不受页面上方筛选条件影响
var funBillState = { range: 'week', offset: 0, room: '' };

// 账单类型切换按钮（高亮用 OneUI 工具类 bg-primary，切换主题配色时同步变化）
function funBillTabsHtml() {
    return [
        { value: 'week', label: '周账单' },
        { value: 'half', label: '半月账单' },
        { value: 'month', label: '月账单' }
    ].map(function(t) {
        var active = (t.value === funBillState.range);
        return '<button type="button" class="fun-tab' + (active ? ' active bg-primary' : '')
            + '" data-range="' + t.value + '">' + t.label + '</button>';
    }).join('');
}

// 卡片外壳：账单类型 + 周期选择 + 内容容器（内容由 loadFunBill 填充）
function funBillShell() {
    return '<div class="row">'
        + '<div class="col-12">'
        + '<div class="block block-rounded">'
        + '<div class="block-header"><h3 class="block-title"><i class="fa fa-gift me-2"></i>趣味账单</h3>'
        + '<div class="block-options"><span id="funBillBadge" class="fun-badge bg-primary d-none"></span></div></div>'
        + '<div class="block-content">'
        + '<div class="fun-toolbar">'
        + '<div class="fun-tabs" id="funBillTabs">' + funBillTabsHtml() + '</div>'
        + '<div class="fun-nav">'
        + '<button type="button" class="fun-nav-btn text-primary" id="funBillPrev"><i class="fa fa-chevron-left"></i></button>'
        + '<div class="fun-nav-mid"><div class="fun-period" id="funBillPeriod">加载中…</div>'
        + '<div class="fun-tag" id="funBillTag"></div></div>'
        + '<button type="button" class="fun-nav-btn text-primary" id="funBillNext"><i class="fa fa-chevron-right"></i></button>'
        + '</div>'
        + '</div>'
        + '<div id="funBillBody"></div>'
        + '</div></div></div></div>';
}

// 账单内容：周期用电/电费 + 生活化换算 + 用电之最 + 夜猫子指数 + 评语
function funBillBodyHtml(fb) {
    // 生活化换算：每项一张小卡片（图标 / 数值 / 名称）
    var eqHtml = (fb.equivalents || []).map(function(eq) {
        return '<div class="fun-eq">'
            + '<div class="fun-eq-icon">' + eq.icon + '</div>'
            + '<div class="fun-eq-value text-primary">' + eq.value + '<span>' + eq.unit + '</span></div>'
            + '<div class="fun-eq-label">' + eq.label + '</div>'
            + '</div>';
    }).join('');
    if (fb.cost_fun) {
        eqHtml += '<div class="fun-eq">'
            + '<div class="fun-eq-icon">' + fb.cost_fun.icon + '</div>'
            + '<div class="fun-eq-value text-primary">' + fb.cost_fun.value + '<span>' + fb.cost_fun.unit + '</span></div>'
            + '<div class="fun-eq-label">' + fb.cost_fun.label + '</div>'
            + '</div>';
    }

    var extremeHtml = '';
    if (fb.max_day) {
        extremeHtml += '<div class="fun-extreme"><span class="fun-pill high text-danger">最猛</span>'
            + '<span class="fun-extreme-date">' + fb.max_day.date + '</span>'
            + '<span class="fun-extreme-value">' + fb.max_day.usage + ' 度</span></div>';
        if (fb.min_day) {
            extremeHtml += '<div class="fun-extreme"><span class="fun-pill low text-success">最省</span>'
                + '<span class="fun-extreme-date">' + fb.min_day.date + '</span>'
                + '<span class="fun-extreme-value">' + fb.min_day.usage + ' 度</span></div>';
        }
    }

    var nightHtml = '';
    if (fb.night) {
        nightHtml = '<div class="fun-block-title">夜猫子指数</div>'
            + '<div class="fun-night">'
            + '<div class="fun-night-head"><span>夜间用电占比</span><span class="fun-night-level text-primary">' + fb.night.level + '</span></div>'
            + '<div class="fun-night-bar"><div class="fun-night-fill" style="width:' + fb.night.ratio + '%;"></div></div>'
            + '<div class="fun-night-tip">最近 24 小时里，夜间（23:00-06:00）用了 ' + fb.night.usage
            + ' 度，占全部用电的 ' + fb.night.ratio + '%</div>'
            + '</div>';
    }

    return '<div class="fun-caption">' + fb.period_text + ' · 共 ' + fb.days + ' 天</div>'
        + '<div class="fun-summary">'
        + '<div class="fun-summary-item"><div class="fun-summary-value text-primary">' + fb.total_usage
        + '<span>度</span></div><div class="fun-summary-label">周期用电</div></div>'
        + '<div class="fun-summary-split"></div>'
        + '<div class="fun-summary-item"><div class="fun-summary-value text-primary">' + fb.total_cost
        + '<span>元</span></div><div class="fun-summary-label">周期电费</div></div>'
        + '</div>'
        + '<div class="fun-block-title">这些电都够干什么</div>'
        + '<div class="fun-eq-grid">' + eqHtml + '</div>'
        + (extremeHtml ? '<div class="fun-block-title">用电之最</div>' + extremeHtml : '')
        + nightHtml
        + '<div class="fun-comment"><i class="fa fa-comment-dots me-2"></i>' + fb.comment + '</div>';
}

// 按当前 range/offset 拉取账单并渲染
function loadFunBill() {
    $('#funBillBody').html('<div class="text-center text-muted small py-4"><i class="fa fa-spinner fa-spin me-1"></i>加载中…</div>');
    $.ajax({
        url: '../api.php',
        method: 'GET',
        data: {
            act: 'get_power_bill',
            room_num: funBillState.room,
            bill_range: funBillState.range,
            bill_offset: funBillState.offset
        },
        dataType: 'json',
        success: function(res) {
            if (res.code != 0) {
                $('#funBillBody').html('<div class="alert alert-danger mb-0">' + (res.msg || '账单加载失败') + '</div>');
                return;
            }
            var d = res.data || {};
            $('#funBillPeriod').text(d.period_text || '');
            $('#funBillTag').text(d.tag || '');
            $('#funBillBadge').toggleClass('d-none', !d.bill).text(d.bill ? d.bill.title : '');
            $('#funBillPrev').prop('disabled', d.can_prev === false);
            $('#funBillNext').prop('disabled', !d.can_next);
            $('#funBillBody').html(d.bill
                ? funBillBodyHtml(d.bill)
                : '<div class="text-center text-muted small py-4">该周期暂无用电数据，可切换周期查看</div>');
        },
        error: function() {
            $('#funBillBody').html('<div class="alert alert-danger mb-0">网络错误，请稍后重试</div>');
        }
    });
}

// 绑定账单交互（元素由 funBillShell 动态插入，故用事件委托）
function bindFunBill() {
    $(document).off('click', '#funBillTabs button').on('click', '#funBillTabs button', function() {
        var range = $(this).data('range');
        if (funBillState.range === range) return;
        funBillState.range = range;
        funBillState.offset = 0;
        // 重渲染类型按钮：高亮（bg-primary）与 active 一起切换
        $('#funBillTabs').html(funBillTabsHtml());
        loadFunBill();
    });
    $(document).off('click', '#funBillPrev').on('click', '#funBillPrev', function() {
        funBillState.offset += 1;
        loadFunBill();
    });
    $(document).off('click', '#funBillNext').on('click', '#funBillNext', function() {
        if (funBillState.offset <= 0) return;
        funBillState.offset -= 1;
        loadFunBill();
    });
}

function renderStatistics(data) {
    // 供 AI 智能分析使用
    window.__powerStatsData = data;

    var healthClass = 'bg-success';
    var healthIcon = 'fa-leaf';
    if (data.health_status === 'high') {
        healthClass = 'bg-danger';
        healthIcon = 'fa-exclamation-triangle';
    } else if (data.health_status === 'normal') {
        healthClass = 'bg-warning';
        healthIcon = 'fa-check';
    }
    
    // 统计概览（4 个统计卡片）：放进「用电统计」卡片内，故与外层卡片同底、行内不再加下间距
    var tilesHtml = `
        <div class="row">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 ${healthClass}">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white">
                            <i class="fa ${healthIcon} me-2"></i>${data.health_text}
                        </div>
                        <div class="text-white opacity-75">用电健康度</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 bg-info">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white">${data.yesterday_usage}</div>
                        <div class="text-white opacity-75">昨日用电量(度)</div>
                        <div class="text-white opacity-50 small">约 ¥${data.yesterday_cost}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="block block-rounded mb-0 bg-primary">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white">${data.daily_avg_usage}</div>
                        <div class="text-white opacity-75">日均用电量(度)</div>
                        <div class="text-white opacity-50 small">约 ¥${data.daily_avg_cost}/天</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded mb-0 bg-success">
                    <div class="block-content text-center py-3">
                        <div class="fs-2 fw-bold text-white">${data.latest_balance !== null ? data.latest_balance : '-'}</div>
                        <div class="text-white opacity-75">当前余额(元)</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    var html = `
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-clock me-2"></i>近24小时用电量趋势</h3>
                    </div>
                    <div class="block-content">
                        <div style="position: relative; height: 250px;">
                            <canvas id="recentChart"></canvas>
                        </div>
                        ${data.recent_trend.length > 0 ? `<div class="text-muted small mt-2"><i class="fa fa-info-circle me-1"></i>最近 24 小时共 ${data.recent_trend.length} 个采集点（固定取最近 24 小时，不受上方筛选条件影响；其中 06:00 一条为前一晚 23:00-06:00 的整晚合计），电价: ${data.price_per_kwh} 元/度</div>` : ''}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-calendar me-2"></i>每日用电总量趋势</h3>
                    </div>
                    <div class="block-content">
                        <div style="position: relative; height: 250px;">
                            <canvas id="dailyChart"></canvas>
                        </div>
                        ${data.daily_trend.length > 0 ? `<div class="text-muted small mt-2"><i class="fa fa-info-circle me-1"></i>共 ${data.daily_trend.length} 天数据</div>` : ''}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-list me-2"></i>历史电费记录</h3>
                    </div>
                    <div class="block-content">
                        ${data.record_count > 0 ? `
                        <div class="row g-2 mb-3">
                            <div class="col-auto">
                                <select id="rec-type-filter" class="form-select form-select-sm">
                                    <option value="">全部记录</option>
                                    <option value="usage">用电记录</option>
                                    <option value="recharge">充值记录</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select id="rec-sort" class="form-select form-select-sm">
                                    <option value="record_time">按时间</option>
                                    <option value="balance">按余额</option>
                                    <option value="usage_kwh">按用电量</option>
                                    <option value="usage_cost">按电费</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select id="rec-sort-dir" class="form-select form-select-sm">
                                    <option value="desc">倒序</option>
                                    <option value="asc">正序</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select id="rec-pagesize" class="form-select form-select-sm">
                                    <option value="10">每页 10 条</option>
                                    <option value="20">每页 20 条</option>
                                    <option value="50">每页 50 条</option>
                                </select>
                            </div>
                        </div>
                        ` : ''}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th>记录时间</th>
                                        <th class="text-center">余额(元)</th>
                                        <th class="text-center">用电量(度)</th>
                                        <th class="text-center">电费(元)</th>
                                    </tr>
                                </thead>
                                <tbody id="power-records-body"></tbody>
                            </table>
                        </div>
                        <div id="power-records-pager" class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2"></div>
                       
                    </div>
                </div>
            </div>
        </div>

        ${funBillShell()}
    `;
    
    // 统计概览渲染进「用电统计」卡片；趋势/记录/账单为独立卡片
    $('#statsTiles').html(tilesHtml);
    $('#statsContent').html(html);

    // 趣味账单：默认周账单（当前周期），按周期独立取数（不受上方筛选条件影响）
    funBillState.room = data.room_num || funBillState.room;
    bindFunBill();
    loadFunBill();

    // 历史电费记录：筛选 / 排序 / 分页（客户端处理）
    window.__powerAllRecords = data.records || [];
    powerRecordsState.page = 1;
    bindRecordControls();
    renderRecordsTable();

    setTimeout(function() {
        renderRecentChart(data.recent_trend);
        renderDailyChart(data.daily_trend);
    }, 100);
}

var powerRecordsState = { sortKey: 'record_time', sortDir: 'desc', typeFilter: '', page: 1, pageSize: 10 };

function bindRecordControls() {
    $('#rec-type-filter').off('change').on('change', function() {
        powerRecordsState.typeFilter = $(this).val();
        powerRecordsState.page = 1;
        renderRecordsTable();
    });
    $('#rec-sort').off('change').on('change', function() {
        powerRecordsState.sortKey = $(this).val();
        renderRecordsTable();
    });
    $('#rec-sort-dir').off('change').on('change', function() {
        powerRecordsState.sortDir = $(this).val();
        renderRecordsTable();
    });
    $('#rec-pagesize').off('change').on('change', function() {
        powerRecordsState.pageSize = intVal($(this).val());
        powerRecordsState.page = 1;
        renderRecordsTable();
    });
}

function renderRecordsTable() {
    if (!window.__powerAllRecords) {
        return;
    }
    var rows = window.__powerAllRecords.slice();

    if (powerRecordsState.typeFilter === 'usage') {
        rows = rows.filter(r => parseFloat(r.usage_cost) > 0);
    } else if (powerRecordsState.typeFilter === 'recharge') {
        rows = rows.filter(r => parseFloat(r.usage_cost) < 0);
    }

    var k = powerRecordsState.sortKey;
    var dir = powerRecordsState.sortDir === 'asc' ? 1 : -1;
    rows.sort(function(a, b) {
        var va, vb;
        if (k === 'record_time') {
            va = a.record_time || '';
            vb = b.record_time || '';
            return va < vb ? -1 * dir : (va > vb ? 1 * dir : 0);
        }
        va = parseFloat(a[k]) || 0;
        vb = parseFloat(b[k]) || 0;
        return va < vb ? -1 * dir : (va > vb ? 1 * dir : 0);
    });

    var total = rows.length;
    var pageSize = powerRecordsState.pageSize;
    var totalPages = Math.ceil(total / pageSize) || 1;
    if (powerRecordsState.page > totalPages) {
        powerRecordsState.page = totalPages;
    }
    var page = powerRecordsState.page;
    var startIdx = (page - 1) * pageSize;
    var pageRows = rows.slice(startIdx, startIdx + pageSize);

    if (pageRows.length === 0) {
        $('#power-records-body').html('<tr><td colspan="4" class="text-center text-muted">暂无记录</td></tr>');
    } else {
        var html = pageRows.map(function(r) {
            return '<tr>' +
                '<td>' + escapeHtml(r.record_time || '') + '</td>' +
                '<td class="text-center">¥' + parseFloat(r.balance).toFixed(2) + '</td>' +
                '<td class="text-center">' + formatUsage(r.usage_kwh, false) + '</td>' +
                '<td class="text-center">' + formatUsage(r.usage_cost, true) + '</td>' +
                '</tr>';
        }).join('');
        $('#power-records-body').html(html);
    }

    renderRecordsPager(total, page, totalPages);
}

function renderRecordsPager(total, page, totalPages) {
    var info = '共 ' + total + ' 条，第 ' + page + ' / ' + totalPages + ' 页';
    var prevDisabled = page <= 1 ? 'disabled' : '';
    var nextDisabled = page >= totalPages ? 'disabled' : '';
    var html = '<div class="text-muted small">' + info + '</div>' +
        '<div class="btn-group btn-group-sm">' +
        '<button type="button" class="btn btn-outline-secondary" data-rec-prev ' + prevDisabled + '><i class="fa fa-chevron-left"></i> 上一页</button>' +
        '<button type="button" class="btn btn-outline-secondary" data-rec-next ' + nextDisabled + '>下一页 <i class="fa fa-chevron-right"></i></button>' +
        '</div>';
    $('#power-records-pager').html(html);

    if (page > 1) {
        $('#power-records-pager [data-rec-prev]').off('click').on('click', function() {
            powerRecordsState.page--;
            renderRecordsTable();
        });
    }
    if (page < totalPages) {
        $('#power-records-pager [data-rec-next]').off('click').on('click', function() {
            powerRecordsState.page++;
            renderRecordsTable();
        });
    }
}

function intVal(v) {
    var n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function renderRecentChart(data) {
    var ctx = document.getElementById('recentChart');
    if (!ctx) return;
    var wrap = ctx.parentNode;

    if (recentChart) {
        recentChart.destroy();
        recentChart = null;
    }
    $(wrap).find('.chart-empty-mask').remove();

    if (!data || data.length === 0) {
        $(wrap).append('<div class="chart-empty-mask" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;"><div class="text-center text-muted"><i class="fa fa-chart-line fa-2x mb-2"></i><div>暂无用电量数据</div></div></div>');
        return;
    }
    
    // 日期已是 2026/9/21 12:30 展示格式：去掉年份前缀即可（兼容旧的 YYYY-MM-DD 数据）
    var labels = data.map(d => d.time ? String(d.time).replace(/^\d{4}[-\/]/, '').substring(0, 11) : '');
    // 用电量为负（电费充值）时按 0 展示，避免折线跌破 0 轴溢出
    var values = data.map(d => Math.max(0, parseFloat(d.usage) || 0));
    
    recentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '用电量(度)',
                data: values,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#007bff',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return '用电量: ' + context.parsed.y + ' 度';
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '用电量(度)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function renderDailyChart(data) {
    var ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    var wrap = ctx.parentNode;

    if (dailyChart) {
        dailyChart.destroy();
        dailyChart = null;
    }
    $(wrap).find('.chart-empty-mask').remove();

    if (!data || data.length === 0) {
        $(wrap).append('<div class="chart-empty-mask" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;"><div class="text-center text-muted"><i class="fa fa-calendar fa-2x mb-2"></i><div>暂无每日用电数据</div></div></div>');
        return;
    }
    
    // 日期已是 2026/9/21 展示格式：去掉年份前缀即可（兼容旧的 YYYY-MM-DD 数据）
    var labels = data.map(d => d.date ? String(d.date).replace(/^\d{4}[-\/]/, '') : '');
    // 用电量为负（电费充值）时按 0 展示，避免折线跌破 0 轴溢出
    var values = data.map(d => Math.max(0, parseFloat(d.usage) || 0));
    
    dailyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '每日用电量(度)',
                data: values,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return '用电量: ' + context.parsed.y + ' 度';
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '用电量(度)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function formatYmd(d) {
    var y = d.getFullYear();
    var m = ('0' + (d.getMonth() + 1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    return y + '-' + m + '-' + day;
}

$(document).ready(function() {
    loadStatistics();
    
    $('#roomSelect').change(function() {
        loadStatistics();
    });
    
    $('#timeRange').change(function() {
        if ($(this).val() === 'custom') {
            $('#customStartWrap, #customEndWrap').show();
        } else {
            $('#customStartWrap, #customEndWrap').hide();
            loadStatistics();
        }
    });
    
    $('#startDate, #endDate').change(function() {
        loadStatistics();
    });
    
    $('#refreshBtn').click(function() {
        loadStatistics();
    });
});
</script>

<!-- 右侧浮动 AI 分析按钮（与 uniapp 端一致） -->
<div class="ai-fab" title="AI 智能分析用电" onclick="aiAnalyzePower()">
    <span class="ai-fab-icon">AI</span>
    <span class="ai-fab-label">分析</span>
</div>

<!-- AI 智能用电分析 弹窗 -->
<div class="modal fade" id="aiPowerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-robot me-1"></i>AI 智能用电分析</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <pre id="aiPowerResult" style="white-space: pre-wrap; word-break: break-word; font-family: inherit; font-size: 14px; margin: 0;"></pre>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function aiAnalyzePower() {
    AiAnalyze.run({
        url: 'ajax.php?act=ai_analyze_power',
        modalId: 'aiPowerModal',
        resultId: 'aiPowerResult',
        fabSel: '.ai-fab',
        payload: function () {
            var d = window.__powerStatsData;
            var roomText = $('#roomSelect option:selected').text() || '';
            return {
                data: JSON.stringify({
                    type: 'statistics',
                    dormitory: roomText,
                    stats: {
                        healthText: d.health_text,
                        yesterdayUsage: d.yesterday_usage,
                        yesterdayCost: d.yesterday_cost,
                        dailyAvgUsage: d.daily_avg_usage,
                        dailyAvgCost: d.daily_avg_cost,
                        latestBalance: d.latest_balance,
                        estimatedDays: d.estimated_days
                    },
                    recentTrend: (d.recent_trend || []).map(function(p) {
                        return { label: p.time || p.date || '', usage: p.usage };
                    }),
                    records: (d.records || []).slice(0, 20).map(function(r) {
                        return {
                            time: r.record_time || r.created_at || '',
                            balance: r.balance,
                            usage: parseFloat(r.usage_kwh) || 0,
                            cost: parseFloat(r.usage_cost) || 0
                        };
                    })
                })
            };
        },
        cacheKey: function () { return 'power_statistics:' + JSON.stringify(window.__powerStatsData); },
        validate: function () {
            if (!window.__powerStatsData) {
                layer.msg('请先加载统计数据后再使用 AI 分析', {icon: 0});
                return false;
            }
            return true;
        }
    });
}
</script>

<style>
/* ==================== 区块卡片间距（对齐 OneUI 原生规则） ==================== */
/* OneUI 的 .block-content 下内边距只有 1px（主题靠末元素自身 margin 撑开），
 * 本页各区块卡片内容较多，这里补回正常下内边距，避免内容紧贴卡片下沿。 */
#statsContent .block-content { padding-bottom: 1.25rem; }
/* 卡片之间的间距由 OneUI 的 .block（margin-bottom: 1.875rem）提供，
 * 卡片上不再写 mb-0 / 外层 row 不再写 mb-4，避免把它覆盖掉。 */

/* ==================== 趣味账单（配色走 OneUI 主题，版式不变） ====================
 * 强调色（主色/危险色/成功色）由 HTML 上的 OneUI 工具类（bg-primary、text-primary、
 * text-danger、text-success）承担，切换「主题配色」时会同步变化；这里的 CSS 只负责
 * 布局与浅色底/描边等细节，颜色取自 OneUI 的 :root 变量（--bs-primary、--bs-light 等）。 */
.fun-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.fun-tabs { display: inline-flex; padding: 4px; background: var(--bs-light); border-radius: 10px; }
.fun-tab { padding: 6px 18px; font-size: 14px; color: var(--bs-secondary); background: transparent; border: 0; border-radius: 8px; cursor: pointer; transition: background .15s ease, color .15s ease; }
.fun-tab:hover { color: var(--bs-primary); }
.fun-tab.active { color: #fff; font-weight: 600; background: var(--bs-primary); box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), .28); }
.fun-nav { display: inline-flex; align-items: center; gap: 10px; min-width: 260px; }
.fun-nav-btn { width: 32px; height: 32px; color: var(--bs-primary); background: var(--bs-light); border: 0; border-radius: 50%; cursor: pointer; transition: background .15s ease; }
.fun-nav-btn:hover:not(:disabled) { background: rgba(var(--bs-primary-rgb), .14); }
.fun-nav-btn:disabled { color: #b9bfc9 !important; cursor: not-allowed; }
.fun-nav-mid { flex: 1; text-align: center; line-height: 1.3; }
.fun-period { font-size: 14px; font-weight: 600; color: var(--bs-body-color); }
.fun-tag { font-size: 12px; color: var(--bs-secondary); }
.fun-badge { display: inline-block; padding: 4px 14px; font-size: 12px; color: #fff; background: var(--bs-primary); border-radius: 20px; }
.fun-caption { margin-top: 16px; font-size: 12px; color: var(--bs-secondary); text-align: center; }
.fun-summary { display: flex; align-items: stretch; margin-top: 12px; padding: 16px 8px; background: rgba(var(--bs-primary-rgb), .06); border-radius: 14px; }
.fun-summary-item { flex: 1; text-align: center; }
.fun-summary-split { width: 1px; background: rgba(var(--bs-primary-rgb), .15); }
.fun-summary-value { font-size: 26px; font-weight: 700; line-height: 1.2; color: var(--bs-primary); }
.fun-summary-value span { margin-left: 3px; font-size: 13px; font-weight: 400; color: var(--bs-secondary); }
.fun-summary-label { margin-top: 4px; font-size: 12px; color: var(--bs-secondary); }
.fun-block-title { margin: 20px 0 10px; font-size: 13px; font-weight: 600; color: var(--bs-secondary); }
.fun-eq-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
.fun-eq { padding: 12px; text-align: center; background: #fff; border: 1px solid var(--bs-border-color); border-radius: 12px; transition: border-color .15s ease, box-shadow .15s ease; }
.fun-eq:hover { border-color: rgba(var(--bs-primary-rgb), .45); box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), .12); }
.fun-eq-icon { font-size: 22px; line-height: 1.2; }
.fun-eq-value { margin-top: 4px; font-size: 17px; font-weight: 700; color: var(--bs-primary); }
.fun-eq-value span { margin-left: 2px; font-size: 12px; font-weight: 400; color: var(--bs-secondary); }
.fun-eq-label { margin-top: 2px; font-size: 12px; color: var(--bs-secondary); }
.fun-extreme { display: flex; align-items: center; margin-bottom: 8px; padding: 8px 14px; background: var(--bs-light); border-radius: 10px; }
.fun-extreme:last-child { margin-bottom: 0; }
.fun-pill { padding: 2px 10px; font-size: 12px; border-radius: 20px; }
.fun-pill.high { color: var(--bs-danger); background: rgba(var(--bs-danger-rgb), .12); }
.fun-pill.low { color: var(--bs-success); background: rgba(var(--bs-success-rgb), .14); }
.fun-extreme-date { flex: 1; margin-left: 10px; font-size: 13px; color: var(--bs-body-color); }
.fun-extreme-value { font-size: 13px; font-weight: 600; color: var(--bs-body-color); }
.fun-night-head { display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: var(--bs-secondary); }
.fun-night-level { font-weight: 600; color: var(--bs-primary); }
.fun-night-bar { margin-top: 8px; height: 8px; background: var(--bs-light); border-radius: 6px; overflow: hidden; }
.fun-night-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--bs-primary) 0%, var(--bs-info) 100%); }
.fun-night-tip { margin-top: 8px; font-size: 12px; line-height: 1.7; color: var(--bs-secondary); }
.fun-comment { margin-top: 20px; padding: 12px 14px; font-size: 13px; line-height: 1.7; color: var(--bs-body-color); background: rgba(var(--bs-primary-rgb), .07); border-radius: 12px; }
@media (max-width: 575.98px) {
    .fun-toolbar { justify-content: center; }
    .fun-nav { width: 100%; min-width: 0; }
}
</style>

<?php include 'foot.php'; ?>
