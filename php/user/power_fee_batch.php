<?php
$title = '批量电费查询';
$requireLogin = true;
include('./head.php');
/** @var array $dbconfig */
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'power_fee_batch')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'power_fee_batch');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $areas = DormitorySystem::getAreas($pdo);
} catch (PDOException $e) {
    $areas = ['code' => 0, 'msg' => '数据库连接失败'];
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-2"></i>批量电费查询
                </h3>
                <div class="block-options">
                    <a href="power_fee.php" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-home me-1"></i>返回查询
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'power_fee_batch'); ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">选择校区</label>
                        <select class="form-select" id="areaSelect">
                            <option value="">请选择校区</option>
                            <?php if(isset($areas['code']) && $areas['code'] == 1): ?>
                                <?php foreach($areas['data'] as $area): ?>
                                <option value="<?php echo htmlspecialchars($area); ?>"><?php echo htmlspecialchars($area); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">选择楼栋</label>
                        <select class="form-select" id="buildingSelect" disabled>
                            <option value="">请先选择校区</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">筛选条件</label>
                        <select class="form-select" id="filterSelect">
                            <option value="all">显示全部</option>
                            <option value="low">余额 < 10度</option>
                            <option value="warning">余额 < 30度</option>
                            <option value="normal">余额 >= 30度</option>
                            <option value="arrears">已欠费 (余额 ≤ 0)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-alt-primary w-100" id="queryBtn" disabled>
                            <i class="fa fa-search me-1"></i> 开始查询
                        </button>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="ignoreArrears">
                            <label class="form-check-label" for="ignoreArrears">忽略已欠费房间</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="ignoreAbnormal" checked>
                            <label class="form-check-label" for="ignoreAbnormal">忽略异常数据 (欠费 > 100度)</label>
                        </div>
                    </div>
                </div>
                
                <div id="roomCountSection" style="display:none;">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        共发现 <strong id="roomCount">0</strong> 条宿舍信息，点击"开始查询"进行批量查询
                    </div>
                </div>
                
                <div id="progressSection" style="display:none;">
                    <div class="alert alert-info d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">加载中...</span>
                        </div>
                        <div>
                            <span id="progressText">正在查询...</span>
                            <span id="progressCount" class="badge bg-primary ms-2">0/0</span>
                        </div>
                    </div>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                </div>
                
                <div id="resultSection" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">
                            <i class="fa fa-list me-2"></i>查询结果
                            <span id="resultCount" class="badge bg-primary ms-2">0</span>
                        </h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportResult()">
                                <i class="fa fa-download me-1"></i>导出结果
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mb-3" id="statsCards">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body py-2 text-center">
                                    <div class="fs-4 fw-bold" id="normalCount">0</div>
                                    <small>电量充足 (>=30度)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body py-2 text-center">
                                    <div class="fs-4 fw-bold" id="warningCount">0</div>
                                    <small>电量偏低 (10-30度)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body py-2 text-center">
                                    <div class="fs-4 fw-bold" id="lowCount">0</div>
                                    <small>电量不足 (<10度)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body py-2 text-center">
                                    <div class="fs-4 fw-bold" id="arrearsCount">0</div>
                                    <small>已欠费 (≤0度)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="resultTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">序号</th>
                                    <th>房间号</th>
                                    <th>宿舍编号</th>
                                    <th class="text-center">电量余额</th>
                                    <th class="text-center">状态</th>
                                </tr>
                            </thead>
                            <tbody id="resultBody">
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mobile-results d-md-none" id="mobileResults">
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script>
var allResults = [];
var queryRunning = false;
var hasQueried = false;

$('#areaSelect').on('change', function() {
    var area = $(this).val();
    var buildingSelect = $('#buildingSelect');
    var queryBtn = $('#queryBtn');
    
    buildingSelect.empty().append('<option value="">加载中...</option>').prop('disabled', true);
    queryBtn.prop('disabled', true);
    $('#roomCountSection').hide();
    $('#resultSection').hide();
    hasQueried = false;
    
    if (!area) {
        buildingSelect.empty().append('<option value="">请先选择校区</option>');
        return;
    }
    
    $.ajax({
        url: 'ajax.php?act=get_buildings',
        type: 'POST',
        data: { area: area },
        dataType: 'json',
        success: function(res) {
            buildingSelect.empty().append('<option value="">请选择楼栋</option>');
            if (res.code == 1 && res.data.length > 0) {
                res.data.forEach(function(item) {
                    buildingSelect.append('<option value="' + item.building_no + '" data-building="' + item.building + '">' + item.building + '</option>');
                });
                buildingSelect.prop('disabled', false);
            } else {
                buildingSelect.append('<option value="">该校区暂无楼栋数据</option>');
            }
        },
        error: function() {
            buildingSelect.empty().append('<option value="">加载失败</option>');
        }
    });
});

$('#buildingSelect').on('change', function() {
    var buildingNo = $(this).val();
    var area = $('#areaSelect').val();
    
    $('#resultSection').hide();
    hasQueried = false;
    
    if (!buildingNo) {
        $('#queryBtn').prop('disabled', true);
        $('#roomCountSection').hide();
        return;
    }
    
    $('#queryBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>加载中...');
    
    $.ajax({
        url: 'ajax.php?act=get_rooms',
        type: 'POST',
        data: { 
            area: area, 
            building_no: buildingNo 
        },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1 && res.data.length > 0) {
                $('#roomCount').text(res.data.length);
                $('#roomCountSection').show();
                $('#queryBtn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> 开始查询');
            } else {
                $('#roomCountSection').hide();
                $('#queryBtn').prop('disabled', true).html('<i class="fa fa-search me-1"></i> 开始查询');
                layer.msg('该楼栋暂无房间数据', {icon: 0});
            }
        },
        error: function() {
            $('#roomCountSection').hide();
            $('#queryBtn').prop('disabled', true).html('<i class="fa fa-search me-1"></i> 开始查询');
            layer.msg('获取房间列表失败', {icon: 2});
        }
    });
});

$('#queryBtn').on('click', function() {
    if (queryRunning) return;
    
    var area = $('#areaSelect').val();
    var buildingNo = $('#buildingSelect').val();
    var buildingName = $('#buildingSelect option:selected').data('building');
    
    if (!area || !buildingNo) {
        layer.msg('请选择校区和楼栋', {icon: 0});
        return;
    }
    
    queryRunning = true;
    hasQueried = false;
    allResults = [];
    $('#queryBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>查询中...');
    
    $('#progressSection').show();
    $('#resultSection').hide();
    $('#roomCountSection').hide();
    
    $.ajax({
        url: 'ajax.php?act=get_rooms',
        type: 'POST',
        data: { 
            area: area, 
            building_no: buildingNo 
        },
        dataType: 'json',
        success: function(res) {
            if (res.code == 1 && res.data.length > 0) {
                var rooms = res.data;
                var total = rooms.length;
                var completed = 0;
                var successCount = 0;
                
                $('#progressText').text('正在查询 ' + buildingName + ' 共 ' + total + ' 个房间...');
                $('#progressCount').text('0/' + total);
                $('#progressBar').css('width', '0%').text('0%');
                
                var queryRoom = function(index) {
                    if (index >= rooms.length) {
                        queryRunning = false;
                        hasQueried = true;
                        $('#queryBtn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> 开始查询');
                        $('#progressSection').hide();
                        displayResults();
                        return;
                    }
                    
                    var room = rooms[index];
                    var roomNum = room.room_num;
                    var schoolAreaNo = room.school_area_no || '';
                    var buildingNoParam = room.building_no || '';
                    
                    $.ajax({
                        url: 'ajax.php?act=query_power_fee',
                        type: 'POST',
                        data: {
                            room_num: roomNum,
                            school_area_no: schoolAreaNo,
                            building_no: buildingNoParam
                        },
                        dataType: 'json',
                        success: function(result) {
                            completed++;
                            var percent = Math.round((completed / total) * 100);
                            $('#progressCount').text(completed + '/' + total);
                            $('#progressBar').css('width', percent + '%').text(percent + '%');
                            
                            if (result.code == 1) {
                                successCount++;
                                allResults.push({
                                    room: room.room,
                                    roomNum: roomNum,
                                    balance: result.data.balance,
                                    status: result.data.status,
                                    msg: result.data.msg
                                });
                            } else {
                                allResults.push({
                                    room: room.room,
                                    roomNum: roomNum,
                                    balance: null,
                                    status: 'error',
                                    msg: result.msg || '查询失败'
                                });
                            }
                        },
                        error: function() {
                            completed++;
                            var percent = Math.round((completed / total) * 100);
                            $('#progressCount').text(completed + '/' + total);
                            $('#progressBar').css('width', percent + '%').text(percent + '%');
                            
                            allResults.push({
                                room: room.room,
                                roomNum: roomNum,
                                balance: null,
                                status: 'error',
                                msg: '请求失败'
                            });
                        },
                        complete: function() {
                            setTimeout(function() {
                                queryRoom(index + 1);
                            }, 200);
                        }
                    });
                };
                
                queryRoom(0);
            } else {
                queryRunning = false;
                $('#queryBtn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> 开始查询');
                layer.msg('该楼栋暂无房间数据', {icon: 0});
                $('#progressSection').hide();
            }
        },
        error: function() {
            queryRunning = false;
            $('#queryBtn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> 开始查询');
            layer.msg('获取房间列表失败', {icon: 2});
            $('#progressSection').hide();
        }
    });
});

$('#filterSelect').on('change', function() {
    if (hasQueried) {
        displayResults();
    }
});

$('#ignoreArrears, #ignoreAbnormal').on('change', function() {
    if (hasQueried) {
        displayResults();
    }
});

function displayResults() {
    if (!hasQueried) return;
    
    var filter = $('#filterSelect').val();
    var ignoreArrears = $('#ignoreArrears').is(':checked');
    var ignoreAbnormal = $('#ignoreAbnormal').is(':checked');
    
    var filteredResults = allResults.filter(function(item) {
        if (item.balance === null) return true;
        if (ignoreArrears && item.balance < 0) return false;
        if (ignoreAbnormal && item.balance < -100) return false;
        return true;
    });
    
    if (filter !== 'all') {
        filteredResults = filteredResults.filter(function(item) {
            if (item.balance === null) return false;
            if (filter === 'low') return item.balance > 0 && item.balance < 10;
            if (filter === 'warning') return item.balance >= 10 && item.balance < 30;
            if (filter === 'normal') return item.balance >= 30;
            if (filter === 'arrears') return item.balance <= 0;
            return true;
        });
    }
    
    filteredResults.sort(function(a, b) {
        if (a.balance === null) return 1;
        if (b.balance === null) return -1;
        return a.balance - b.balance;
    });
    
    $('#resultSection').show();
    $('#resultCount').text(filteredResults.length);
    
    var normalCount = 0;
    var warningCount = 0;
    var lowCount = 0;
    var arrearsCount = 0;
    
    allResults.forEach(function(item) {
        if (item.balance !== null) {
            if (item.balance >= 30) normalCount++;
            else if (item.balance >= 10) warningCount++;
            else if (item.balance > 0) lowCount++;
            else arrearsCount++;
        }
    });
    
    $('#normalCount').text(normalCount);
    $('#warningCount').text(warningCount);
    $('#lowCount').text(lowCount);
    $('#arrearsCount').text(arrearsCount);
    
    var tableHtml = '';
    var mobileHtml = '';
    
    filteredResults.forEach(function(item, index) {
        var statusClass = '';
        var statusBadge = '';
        
        if (item.status === 'error') {
            statusClass = 'text-muted';
            statusBadge = '<span class="badge bg-secondary">查询失败</span>';
        } else if (item.balance <= 0) {
            statusClass = 'text-dark fw-bold';
            statusBadge = '<span class="badge bg-dark">已欠费</span>';
        } else if (item.balance < 10) {
            statusClass = 'text-danger fw-bold';
            statusBadge = '<span class="badge bg-danger">电量不足</span>';
        } else if (item.balance < 30) {
            statusClass = 'text-warning fw-bold';
            statusBadge = '<span class="badge bg-warning text-dark">电量偏低</span>';
        } else {
            statusClass = 'text-success fw-bold';
            statusBadge = '<span class="badge bg-success">电量充足</span>';
        }
        
        tableHtml += '<tr>';
        tableHtml += '<td class="text-center">' + (index + 1) + '</td>';
        tableHtml += '<td>' + item.room + '</td>';
        tableHtml += '<td><code>' + item.roomNum + '</code></td>';
        tableHtml += '<td class="text-center ' + statusClass + '">' + (item.balance !== null ? item.balance + ' 度' : '-') + '</td>';
        tableHtml += '<td class="text-center">' + statusBadge + '</td>';
        tableHtml += '</tr>';
        
        mobileHtml += '<div class="card mb-2">';
        mobileHtml += '<div class="card-body py-2 px-3">';
        mobileHtml += '<div class="d-flex justify-content-between align-items-center">';
        mobileHtml += '<div>';
        mobileHtml += '<div class="fw-bold">' + item.room + '</div>';
        mobileHtml += '<small class="text-muted">' + item.roomNum + '</small>';
        mobileHtml += '</div>';
        mobileHtml += '<div class="text-end">';
        mobileHtml += '<div class="fs-5 ' + statusClass + '">' + (item.balance !== null ? item.balance + '度' : '-') + '</div>';
        mobileHtml += statusBadge;
        mobileHtml += '</div>';
        mobileHtml += '</div>';
        mobileHtml += '</div>';
        mobileHtml += '</div>';
    });
    
    $('#resultBody').html(tableHtml);
    $('#mobileResults').html(mobileHtml);
}

function exportResult() {
    if (allResults.length === 0) {
        layer.msg('暂无数据可导出', {icon: 0});
        return;
    }
    
    var csv = '\uFEFF序号,房间号,宿舍编号,电量余额,状态\n';
    
    allResults.forEach(function(item, index) {
        var statusText = '';
        if (item.status === 'error') {
            statusText = '查询失败';
        } else if (item.balance <= 0) {
            statusText = '已欠费';
        } else if (item.balance < 10) {
            statusText = '电量不足';
        } else if (item.balance < 30) {
            statusText = '电量偏低';
        } else {
            statusText = '电量充足';
        }
        
        csv += (index + 1) + ',' + item.room + ',' + item.roomNum + ',' + (item.balance !== null ? item.balance : '-') + ',' + statusText + '\n';
    });
    
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = '电费查询结果_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}
</script>

<?php include 'foot.php'; ?>
<style>
.card.bg-success {
    background-color: #28a745 !important;
}
.card.bg-warning {
    background-color: #ffc107 !important;
}
.card.bg-danger {
    background-color: #dc3545 !important;
}
.card.bg-secondary {
    background-color: #6c757d !important;
}
.text-warning {
    color: #e67e00 !important;
}
.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}
.badge.bg-dark {
    background-color: #343a40 !important;
    color: #fff !important;
}
@media (max-width: 768px) {
    .row.mb-4 > .col-md-3 {
        margin-bottom: 0.75rem;
    }
    .form-select, .btn {
        font-size: 14px;
    }
    .table-responsive {
        display: none;
    }
    .mobile-results {
        display: block !important;
    }
    #statsCards > .col-md-3 {
        margin-bottom: 0.5rem;
    }
    .form-check-inline {
        margin-right: 1rem;
    }
}
@media (min-width: 769px) {
    .mobile-results {
        display: none !important;
    }
}
</style>
