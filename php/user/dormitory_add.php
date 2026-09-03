<?php
$title = '添加宿舍';
$requireLogin = true;
include('./head.php');

$userId = $_SESSION['userId'];
$countRow = $DB->getRow("SELECT COUNT(*) as cnt FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1");
$currentCount = $countRow ? $countRow['cnt'] : 0;

if ($currentCount >= 5) {
    echo '<div id="pjax-container"><div class="content animated fadeIn"><div class="block block-rounded block-bordered"><div class="block-content block-content-full"><div class="alert alert-warning"><i class="fa fa-exclamation-triangle me-2"></i>您已绑定5个宿舍，无法继续添加</div><a href="dormitory_bind.php" class="btn btn-alt-primary"><i class="fa fa-arrow-left me-1"></i>返回宿舍绑定</a></div></div></div></div>';
    include 'foot.php';
    exit;
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-plus me-2"></i>添加新宿舍
                </h3>
                <div class="block-options">
                    <span class="badge bg-info">已绑定 <?php echo $currentCount; ?>/5 个宿舍</span>
                </div>
            </div>
            <div class="block-content block-content-full">
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>提示</strong> - 请通过下拉选择或搜索的方式找到您的宿舍并绑定
                    </div>
                </div>
                
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="select-tab" data-bs-toggle="tab" data-bs-target="#select-panel" type="button" role="tab">
                            <i class="fa fa-list me-1"></i>下拉选择
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search-panel" type="button" role="tab">
                            <i class="fa fa-search me-1"></i>搜索添加
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="select-panel" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">校区 <span class="text-danger">*</span></label>
                                <select class="form-select" id="areaSelect" onchange="loadBuildings()">
                                    <option value="">请选择校区</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">楼栋 <span class="text-danger">*</span></label>
                                <select class="form-select" id="buildingSelect" onchange="loadRooms()" disabled>
                                    <option value="">请先选择校区</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">房间号 <span class="text-danger">*</span></label>
                                <select class="form-select" id="roomSelect" disabled>
                                    <option value="">请先选择楼栋</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="search-panel" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label">搜索宿舍</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchKeyword" placeholder="输入宿舍号/楼栋名称搜索...">
                                    <button class="btn btn-alt-primary" type="button" onclick="searchDormitory()">
                                        <i class="fa fa-search"></i> 搜索
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="searchResults" style="display:none;">
                            <h5 class="mb-3">搜索结果</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>校区</th>
                                            <th>楼栋</th>
                                            <th>房间号</th>
                                            <th>宿舍编号</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="searchResultsBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">宿舍别名（可选）</label>
                        <input type="text" class="form-control" id="dormAlias" placeholder="例如：我的宿舍">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">设为默认宿舍</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="isDefault" value="1">
                            <label class="form-check-label" for="isDefault">设为默认宿舍</label>
                        </div>
                    </div>
                </div>
                
                <div id="selectedInfo" class="alert alert-secondary" style="display:none;">
                    <i class="fa fa-check-circle me-2"></i>
                    已选择宿舍：<strong id="selectedRoomDisplay"></strong>
                </div>
                
                <div class="d-flex">
                    <button type="button" class="btn btn-alt-primary me-2" onclick="bindDormitory()">
                        <i class="fa fa-plus me-1"></i> 绑定宿舍
                    </button>
                    <a href="dormitory_bind.php" class="btn btn-alt-secondary">
                        <i class="fa fa-arrow-left me-1"></i> 返回
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var selectedRoomNum = '';
var selectedSchoolAreaNo = '';
var selectedBuildingNo = '';
var selectedDisplay = '';

$(document).ready(function() {
    loadAreas();
});

function loadAreas() {
    $.ajax({
        url: 'ajax.php?act=get_dorm_areas',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                var options = '<option value="">请选择校区</option>';
                res.data.forEach(function(area) {
                    options += '<option value="' + area + '">' + area + '</option>';
                });
                $('#areaSelect').html(options);
            }
        }
    });
}

function loadBuildings() {
    var area = $('#areaSelect').val();
    if (!area) {
        $('#buildingSelect').html('<option value="">请先选择校区</option>').prop('disabled', true);
        $('#roomSelect').html('<option value="">请先选择楼栋</option>').prop('disabled', true);
        return;
    }
    
    $.ajax({
        url: 'ajax.php?act=get_dorm_buildings&area=' + encodeURIComponent(area),
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                var options = '<option value="">请选择楼栋</option>';
                res.data.forEach(function(item) {
                    options += '<option value="' + item.building_no + '" data-building="' + item.building + '">' + item.building + '</option>';
                });
                $('#buildingSelect').html(options).prop('disabled', false);
                $('#roomSelect').html('<option value="">请先选择楼栋</option>').prop('disabled', true);
            }
        }
    });
}

function loadRooms() {
    var area = $('#areaSelect').val();
    var buildingNo = $('#buildingSelect').val();
    var buildingName = $('#buildingSelect option:selected').data('building');
    
    if (!buildingNo) {
        $('#roomSelect').html('<option value="">请先选择楼栋</option>').prop('disabled', true);
        return;
    }
    
    $.ajax({
        url: 'ajax.php?act=get_dorm_rooms&area=' + encodeURIComponent(area) + '&buildingNo=' + encodeURIComponent(buildingNo),
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.code == 1) {
                var options = '<option value="">请选择房间</option>';
                res.data.forEach(function(item) {
                    options += '<option value="' + item.room_num + '" data-school-area-no="' + (item.school_area_no || '') + '" data-building-no="' + (item.building_no || '') + '" data-room="' + item.room + '">' + item.room + '</option>';
                });
                $('#roomSelect').html(options).prop('disabled', false);
            }
        }
    });
}

$('#roomSelect').on('change', function() {
    var selected = $(this).find(':selected');
    selectedRoomNum = $(this).val();
    selectedSchoolAreaNo = selected.data('school-area-no') || '';
    selectedBuildingNo = selected.data('building-no') || '';
    var roomName = selected.data('room') || '';
    var areaName = $('#areaSelect').val();
    var buildingName = $('#buildingSelect option:selected').data('building');
    
    if (selectedRoomNum) {
        selectedDisplay = areaName + ' ' + buildingName + ' ' + roomName + ' (' + selectedRoomNum + ')';
        $('#selectedRoomDisplay').text(selectedDisplay);
        $('#selectedInfo').show();
    } else {
        $('#selectedInfo').hide();
    }
});

function searchDormitory() {
    var keyword = $('#searchKeyword').val().trim();
    if (!keyword) {
        layer.msg('请输入搜索关键词', {icon: 0});
        return;
    }
    
    $.ajax({
        url: 'ajax.php?act=search_dorm_rooms&keyword=' + encodeURIComponent(keyword),
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.code == 1 && res.data.length > 0) {
                var html = '';
                res.data.forEach(function(item) {
                    html += '<tr>' +
                        '<td>' + item.school_area + '</td>' +
                        '<td>' + item.building + '</td>' +
                        '<td>' + item.room + '</td>' +
                        '<td>' + item.room_num + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-alt-primary" onclick="selectRoom(\'' + item.room_num + '\', \'' + (item.school_area_no || '') + '\', \'' + (item.building_no || '') + '\', \'' + item.school_area + ' ' + item.building + ' ' + item.room + '\')">选择</button></td>' +
                        '</tr>';
                });
                $('#searchResultsBody').html(html);
                $('#searchResults').show();
            } else {
                layer.msg('未找到匹配的宿舍', {icon: 0});
                $('#searchResults').hide();
            }
        }
    });
}

function selectRoom(roomNum, schoolAreaNo, buildingNo, display) {
    selectedRoomNum = roomNum;
    selectedSchoolAreaNo = schoolAreaNo;
    selectedBuildingNo = buildingNo;
    selectedDisplay = display + ' (' + roomNum + ')';
    
    $('#selectedRoomDisplay').text(selectedDisplay);
    $('#selectedInfo').show();
    layer.msg('已选择宿舍', {icon: 1});
}

function bindDormitory() {
    if (!selectedRoomNum) {
        var roomSelect = $('#roomSelect').val();
        if (roomSelect) {
            selectedRoomNum = roomSelect;
            var selected = $('#roomSelect').find(':selected');
            selectedSchoolAreaNo = selected.data('school-area-no') || '';
            selectedBuildingNo = selected.data('building-no') || '';
        } else {
            layer.msg('请选择要绑定的宿舍', {icon: 0});
            return;
        }
    }
    
    var alias = $('#dormAlias').val().trim();
    var isDefault = $('#isDefault').is(':checked') ? 1 : 0;
    
    var loadIndex = layer.load(1);
    
    $.ajax({
        url: 'ajax.php?act=bind_dormitory',
        type: 'POST',
        data: {
            room_num: selectedRoomNum,
            school_area_no: selectedSchoolAreaNo,
            building_no: selectedBuildingNo,
            alias: alias,
            is_default: isDefault
        },
        dataType: 'json',
        success: function(res) {
            layer.close(loadIndex);
            if (res.code == 1) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.href = 'dormitory_bind.php';
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.close(loadIndex);
            layer.msg('请求失败，请稍后重试', {icon: 2});
        }
    });
}
</script>

<?php include 'foot.php'; ?>
