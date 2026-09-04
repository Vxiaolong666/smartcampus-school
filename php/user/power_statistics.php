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
                            <option value="30">近30天</option>
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
                
                <div id="statsContent">
                    <div class="text-center py-5">
                        <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2">加载中...</p>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
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

    $('#statsContent').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">加载中...</p></div>');
    
    $.ajax({
        url: '../api.php',
        method: 'GET',
        data: params,
        dataType: 'json',
        success: function(res) {
            if (res.code == 0) {
                renderStatistics(res.data);
            } else {
                $('#statsContent').html('<div class="alert alert-danger">' + (res.msg || '获取数据失败') + '</div>');
            }
        },
        error: function() {
            $('#statsContent').html('<div class="alert alert-danger">网络错误，请稍后重试</div>');
        }
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
    
    var html = `
        <div class="row mb-4">
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
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="block block-rounded mb-0">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-clock me-2"></i>用电量趋势</h3>
                    </div>
                    <div class="block-content">
                        <div style="position: relative; height: 250px;">
                            <canvas id="recentChart"></canvas>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fa fa-info-circle me-1"></i>共 ${data.recent_trend.length} 个数据点，电价: ${data.price_per_kwh} 元/度
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="block block-rounded mb-0">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-calendar me-2"></i>每日用电总量趋势</h3>
                    </div>
                    <div class="block-content">
                        <div style="position: relative; height: 250px;">
                            <canvas id="dailyChart"></canvas>
                        </div>
                        <div class="text-muted small mt-2">
                            <i class="fa fa-info-circle me-1"></i>共 ${data.daily_trend.length} 天数据
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded mb-0">
                    <div class="block-header">
                        <h3 class="block-title"><i class="fa fa-list me-2"></i>历史电费记录</h3>
                    </div>
                    <div class="block-content">
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
                                <tbody>
                                    ${data.records.length > 0 ? data.records.map(r => `
                                        <tr>
                                            <td>${r.record_time || r.created_at}</td>
                                            <td class="text-center">¥${r.balance}</td>
                                            <td class="text-center">${formatUsage(r.usage_kwh, false)}</td>
                                            <td class="text-center">${formatUsage(r.usage_cost, true)}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="4" class="text-center text-muted">暂无记录</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                        <div class="text-muted small">
                            <i class="fa fa-info-circle me-1"></i>共 ${data.record_count} 条记录，用电量根据余额变化和电价(${data.price_per_kwh}元/度)自动计算
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#statsContent').html(html);
    
    setTimeout(function() {
        renderRecentChart(data.recent_trend);
        renderDailyChart(data.daily_trend);
    }, 100);
}

function renderRecentChart(data) {
    var ctx = document.getElementById('recentChart');
    if (!ctx) return;
    
    if (recentChart) {
        recentChart.destroy();
    }
    
    if (!data || data.length === 0) {
        return;
    }
    
    var labels = data.map(d => d.time ? d.time.substring(5, 16) : '');
    var values = data.map(d => d.usage);
    
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
    
    if (dailyChart) {
        dailyChart.destroy();
    }
    
    if (!data || data.length === 0) {
        return;
    }
    
    var labels = data.map(d => d.date ? d.date.substring(5) : '');
    var values = data.map(d => d.usage);
    
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
    var d = window.__powerStatsData;
    if (!d) {
        layer.msg('请先加载统计数据后再使用 AI 分析', {icon: 0});
        return;
    }
    var roomText = $('#roomSelect option:selected').text() || '';
    var payload = {
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
    };

    var modal = (window.bootstrap && bootstrap.Modal) ? new bootstrap.Modal(document.getElementById('aiPowerModal')) : null;
    if (modal) { modal.show(); } else { $('#aiPowerModal').modal('show'); }
    $('#aiPowerResult').html('<div class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin me-2"></i>AI 正在分析，请稍候…</div>');
    $.ajax({
        url: 'ajax.php?act=ai_analyze_power',
        type: 'POST',
        data: { data: JSON.stringify(payload) },
        dataType: 'json',
        success: function(res) {
            if (res.code == 0) {
                if (window.marked) {
                    $('#aiPowerResult').html('<div class="markdown-body">' + window.marked.parse(res.content) + '</div>');
                } else {
                    $('#aiPowerResult').text(res.content);
                }
            } else {
                $('#aiPowerResult').html('<div class="alert alert-danger">' + (res.msg || '分析失败') + '</div>');
            }
        },
        error: function() {
            $('#aiPowerResult').html('<div class="alert alert-danger">请求失败，请稍后重试</div>');
        }
    });
}
</script>

<?php include 'foot.php'; ?>
