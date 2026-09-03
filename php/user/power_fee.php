<?php
$title = '电费查询';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'electricity')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'electricity');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$dormitories = DormitorySystem::getUserDormitories($DB, $userId);
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-2"></i>电费查询
                </h3>
                <div class="block-options">
                    <a href="push_settings.php" class="btn btn-sm btn-alt-success">
                        <i class="fa fa-bell me-1"></i>推送设置
                    </a>
                    <a href="power_fee_batch.php" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-layer-group me-1"></i>批量查询
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'electricity'); ?>
                <?php if (count($dormitories) > 0): ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">选择宿舍</label>
                        <select class="form-select" id="dormSelect">
                            <option value="">请选择要查询的宿舍</option>
                            <?php foreach ($dormitories as $dorm): ?>
                            <?php 
                            if ($dorm['alias']) {
                                $displayName = $dorm['alias'] . '（' . $dorm['school_area'] . $dorm['building'] . ' ' . $dorm['room'] . '）';
                            } else {
                                $displayName = $dorm['school_area'] . $dorm['building'] . ' ' . $dorm['room'];
                            }
                            ?>
                            <option value="<?php echo $dorm['id']; ?>" 
                                    data-room-num="<?php echo $dorm['room_num']; ?>"
                                    data-school-area-no="<?php echo $dorm['school_area_no'] ?? ''; ?>"
                                    data-building-no="<?php echo $dorm['building_no'] ?? ''; ?>">
                                <?php echo htmlspecialchars($displayName); ?>
                                <?php if ($dorm['is_default'] == 1): ?>(默认)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-alt-primary" onclick="queryPowerFee()">
                            <i class="fa fa-search me-1"></i> 查询电费
                        </button>
                        <button type="button" class="btn btn-outline-secondary ms-2" onclick="queryAllPowerFee()">
                            <i class="fa fa-list me-1"></i> 查询全部
                        </button>
                    </div>
                </div>
                
                <div id="powerFeeResult" style="display:none;">
                    <h4 class="mb-3">查询结果</h4>
                    <div id="powerFeeContent"></div>
                </div>
                
                <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>未绑定宿舍</strong> - 请先<a href="dormitory_bind.php">绑定宿舍</a>后再查询电费
                    </div>
                </div>
                <a href="dormitory_bind.php" class="btn btn-alt-primary">
                    <i class="fa fa-home me-1"></i> 前往绑定宿舍
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (count($dormitories) > 0): ?>
        <div class="block block-rounded block-bordered mt-4">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-info-circle me-2"></i>电费说明
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="alert alert-success mb-0">
                            <h5><i class="fa fa-check-circle me-2"></i>电量充足</h5>
                            <p class="mb-0">余额 ≥ 30度，无需担心</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="alert alert-warning mb-0">
                            <h5><i class="fa fa-exclamation-triangle me-2"></i>电量偏低</h5>
                            <p class="mb-0">10度 ≤ 余额 < 30度，建议充值</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-danger mb-0">
                            <h5><i class="fa fa-times-circle me-2"></i>电量不足</h5>
                            <p class="mb-0">余额 < 10度，请及时充值</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function queryPowerFee() {
    var selected = $('#dormSelect').find(':selected');
    var dormId = $('#dormSelect').val();
    
    if (!dormId) {
        layer.msg('请选择要查询的宿舍', {icon: 0});
        return;
    }
    
    var roomNum = selected.data('room-num');
    var schoolAreaNo = selected.data('school-area-no') || '';
    var buildingNo = selected.data('building-no') || '';
    var dormName = selected.text();
    
    $('#powerFeeResult').show();
    $('#powerFeeContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">加载中...</span></div><p class="mt-2">正在查询...</p></div>');
    
    $.ajax({
        url: 'ajax.php?act=query_power_fee',
        type: 'POST',
        data: {
            room_num: roomNum,
            school_area_no: schoolAreaNo,
            building_no: buildingNo
        },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                var data = res.data;
                var statusClass = '';
                var statusIcon = '';
                
                if (data.status == 'normal') {
                    statusClass = 'success';
                    statusIcon = 'check-circle';
                } else if (data.status == 'warning') {
                    statusClass = 'warning';
                    statusIcon = 'exclamation-triangle';
                } else {
                    statusClass = 'danger';
                    statusIcon = 'times-circle';
                }
                
                var html = '<div class="card">' +
                    '<div class="card-header bg-light">' +
                        '<h5 class="mb-0"><i class="fa fa-home me-2"></i>' + dormName + '</h5>' +
                    '</div>' +
                    '<div class="card-body">' +
                        '<div class="row text-center">' +
                            '<div class="col-md-3">' +
                                '<div class="display-4 text-' + statusClass + ' fw-bold">' + data.balance + '</div>' +
                                '<div class="text-muted">电量余额（元）</div>' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<div class="display-4 text-primary fw-bold">' + (data.remaining_kwh !== null ? data.remaining_kwh : '-') + '</div>' +
                                '<div class="text-muted">剩余电量（度）</div>' +
                            '</div>' +
                            '<div class="col-md-3">' +
                                '<div class="display-4 text-info fw-bold">' + (data.estimated_days !== null ? data.estimated_days : '-') + '</div>' +
                                '<div class="text-muted">预计可用（天）</div>' +
                            '</div>' +
                            '<div class="col-md-3 d-flex align-items-center justify-content-center">' +
                                '<div class="alert alert-' + statusClass + ' mb-0">' +
                                    '<i class="fa fa-' + statusIcon + ' me-2"></i>' + data.msg +
                                '</div>' +
                            '</div>' +
                        '</div>';
                
                if (data.waterBalance !== undefined || data.hotWaterBalance !== undefined) {
                    html += '<hr><div class="row text-center">';
                    if (data.waterBalance !== undefined) {
                        html += '<div class="col-md-6"><div class="h4 text-primary">' + data.waterBalance + '</div><div class="text-muted">水费余额（元）</div></div>';
                    }
                    if (data.hotWaterBalance !== undefined) {
                        html += '<div class="col-md-6"><div class="h4 text-info">' + data.hotWaterBalance + '</div><div class="text-muted">热水余额（元）</div></div>';
                    }
                    html += '</div>';
                }
                
                html += '</div>' +
                    '<div class="card-footer text-muted">' +
                        '<small>查询时间：' + new Date().toLocaleString() + '</small>' +
                    '</div>' +
                '</div>';
                
                $('#powerFeeContent').html(html);
            } else {
                $('#powerFeeContent').html('<div class="alert alert-danger"><i class="fa fa-times-circle me-2"></i>' + res.msg + '</div>');
            }
        },
        error: function() {
            $('#powerFeeContent').html('<div class="alert alert-danger"><i class="fa fa-times-circle me-2"></i>查询失败，请稍后重试</div>');
        }
    });
}

function queryAllPowerFee() {
    var dorms = [];
    $('#dormSelect option').each(function() {
        if ($(this).val()) {
            dorms.push({
                id: $(this).val(),
                name: $(this).text(),
                roomNum: $(this).data('room-num'),
                schoolAreaNo: $(this).data('school-area-no') || '',
                buildingNo: $(this).data('building-no') || ''
            });
        }
    });
    
    if (dorms.length == 0) {
        layer.msg('没有可查询的宿舍', {icon: 0});
        return;
    }
    
    $('#powerFeeResult').show();
    $('#powerFeeContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">加载中...</span></div><p class="mt-2">正在查询所有宿舍...</p></div>');
    
    var results = [];
    var completed = 0;
    
    dorms.forEach(function(dorm, index) {
        $.ajax({
            url: 'ajax.php?act=query_power_fee',
            type: 'POST',
            data: {
                room_num: dorm.roomNum,
                school_area_no: dorm.schoolAreaNo,
                building_no: dorm.buildingNo
            },
            dataType: 'json',
            success: function(res) {
                results.push({
                    name: dorm.name,
                    roomNum: dorm.roomNum,
                    result: res
                });
            },
            complete: function() {
                completed++;
                if (completed == dorms.length) {
                    renderAllResults(results);
                }
            }
        });
    });
}

function renderAllResults(results) {
    var html = '<div class="table-responsive"><table class="table table-bordered table-striped">' +
        '<thead><tr><th>宿舍</th><th>宿舍编号</th><th>电量余额(元)</th><th>剩余电量(度)</th><th>预计可用(天)</th><th>状态</th></tr></thead><tbody>';
    
    results.forEach(function(item) {
        var result = item.result;
        if (result.code == 1) {
            var data = result.data;
            var statusClass = '';
            var statusIcon = '';
            
            if (data.status == 'normal') {
                statusClass = 'success';
                statusIcon = 'check-circle';
            } else if (data.status == 'warning') {
                statusClass = 'warning';
                statusIcon = 'exclamation-triangle';
            } else {
                statusClass = 'danger';
                statusIcon = 'times-circle';
            }
            
            html += '<tr>' +
                '<td>' + item.name + '</td>' +
                '<td>' + item.roomNum + '</td>' +
                '<td><strong class="text-' + statusClass + '">' + data.balance + ' 元</strong></td>' +
                '<td><strong class="text-primary">' + (data.remaining_kwh !== null ? data.remaining_kwh + ' 度' : '-') + '</strong></td>' +
                '<td><strong class="text-info">' + (data.estimated_days !== null ? data.estimated_days : '-') + '</strong></td>' +
                '<td><span class="badge bg-' + statusClass + '"><i class="fa fa-' + statusIcon + ' me-1"></i>' + data.msg + '</span></td>' +
            '</tr>';
        } else {
            html += '<tr>' +
                '<td>' + item.name + '</td>' +
                '<td>' + item.roomNum + '</td>' +
                '<td colspan="4"><span class="text-danger">' + result.msg + '</span></td>' +
            '</tr>';
        }
    });
    
    html += '</tbody></table></div>';
    html += '<p class="text-muted mt-2"><small>查询时间：' + new Date().toLocaleString() + '</small></p>';
    
    $('#powerFeeContent').html(html);
}
</script>

<?php include 'foot.php'; ?>
<style>
@media (max-width: 768px) {
    .row.mb-4 > .col-md-6 {
        margin-bottom: 0.75rem;
    }
    .col-md-6.d-flex {
        flex-wrap: wrap;
    }
    .col-md-6.d-flex .btn {
        flex: 1;
        font-size: 14px;
    }
    .col-md-6.d-flex .btn.ms-2 {
        margin-left: 0.5rem !important;
    }
    .display-4 {
        font-size: 2rem !important;
    }
    .table-responsive {
        font-size: 13px;
    }
    .alert h5 {
        font-size: 14px;
    }
    .alert p {
        font-size: 12px;
        margin-bottom: 0;
    }
}
</style>
