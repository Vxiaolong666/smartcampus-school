<?php
$mod = 'admin';
$title='用户管理';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}

if(isset($_GET['value']) && !empty($_GET['value'])) {
    if ($_GET['column'] == 1) {
        $sql=" 1";
        $numrows=$DB->getColumn("SELECT count(*) from nteam_users WHERE{$sql}");
    }else{
        $sql=" `{$_GET['column']}` LIKE '%{$_GET['value']}%'";
        $numrows=$DB->getColumn("SELECT count(*) from nteam_users WHERE{$sql}");
    }
    $link='&my=search&column='.$_GET['column'].'&value='.$_GET['value'];
}else{
    $numrows=$DB->getColumn("SELECT count(*) from nteam_users WHERE 1");
    $sql=" 1";
}
include './head.php';
?>
<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <?php echo $title ?>
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="javascript:void(0)">后台首页</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            <?php echo $title ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="content animated fadeIn">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"> 用户列表 </h3>
                <div class="block-options">
                    <a class="btn btn-alt-success" href="./uset_add.php?my=add"><b><i class="fa fa-plus"></i> 新增</b></a>
                    <button type="button" onclick="location.reload()" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                        <i class="si si-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <style>
                        .table-responsive table th {
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 60px;">ID</th>
                                <th style="min-width: 100px;">用户名</th>
                                <th style="min-width: 150px;">邮箱</th>
                                <th style="min-width: 100px;">QQ</th>
                                <th style="min-width: 60px;">积分</th>
                                <th style="min-width: 60px;">等级</th>
                                <th style="min-width: 80px;">状态</th>
                                <th style="min-width: 150px;">注册时间</th>
                                <th class="text-center" style="min-width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagesize=30;
                            $pages=ceil($numrows/$pagesize);
                            $page=isset($_GET['page'])?intval($_GET['page']):1;
                            $offset=$pagesize*($page - 1);

                            $rs=$DB->query("SELECT * FROM nteam_users WHERE{$sql} order by id limit $offset,$pagesize");
                            $hasData = false;
                            while($res = $rs->fetch())
                            {
                                $hasData = true;
                                echo '
                                <tr>
                                  <td class="text-center">'.$res['id'].'</td>
                                  <td class="fw-semibold">'.$res['username'].'</td>
                                  <td>'.$res['email'].'</td>
                                  <td>'.$res['qq'].'</td>
                                  <td>'.$res['points'].'</td>
                                  <td>'.$res['level'].'</td>
                                  <td>'.($res['is_banned']==1?'<span class="badge bg-danger">已封禁</span>':'<span class="badge bg-success">正常</span>').'</td>
                                  <td>'.$res['register_time'].'</td>
                                  <td class="text-center" style="white-space: nowrap;">';
                                      echo '<a href="./uset.php?my=edit&id='.$res['id'].'" class="btn btn-sm btn btn-primary"><b>编辑</b></a> ';
                                      if($res['is_banned'] == 0) {
                                          echo '<a href="javascript:banUser('.$res['id'].')" class="btn btn-sm btn btn-danger"><b>封禁</b></a> ';
                                      } else {
                                          echo '<a href="javascript:unbanUser('.$res['id'].')" class="btn btn-sm btn btn-success"><b>解封</b></a> ';
                                      }
                                      echo '<a href="javascript:deleteUser('.$res['id'].')" class="btn btn-sm btn btn-secondary"><b>删除</b></a>';
                                  echo '</td>
                                </tr>
                                ';
                            }
                            
                            if (!$hasData) {
                                echo '<tr><td colspan="9">
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-users-slash"></i>
                                        <span>暂无用户数据</span>
                                    </div>
                                </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php 
echo'<div class="content"><div class="dataTables_paginate paging_full_numbers d-flex justify-content-center"><ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
    echo '<li class="paginate_button page-item next"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$first.$link.'\')"><i class="fa fa-angle-double-left"></i></a></li>';
    echo '<li class="paginate_button page-item last"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$prev.$link.'\')"><i class="fa fa-angle-left"></i></a></li>';
} else {
    echo '<li class="paginate_button page-item next"><a class="page-link"><i class="fa fa-angle-double-left"></i></a></li>';
    echo '<li class="paginate_button page-item last"><a class="page-link"><i class="fa fa-angle-left"></i></a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
    echo '<li class="paginate_button page-item"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
echo '<li class="paginate_button page-item active"><a class="page-link">'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
    echo '<li class="paginate_button page-item "><a  class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
if ($page<$pages)
{
    echo '<li class="paginate_button page-item next"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$next.$link.'\')"><i class="fa fa-angle-right"></i></a></li>';
    echo '<li class="paginate_button page-item last"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$last.$link.'\')"><i class="fa fa-angle-double-right"></i></a></li>';
} else {
    echo '<li class="paginate_button page-item next"><a class="page-link"><i class="fa fa-angle-right"></i></a></li>';
    echo '<li class="paginate_button page-item last"><a class="page-link"><i class="fa fa-angle-double-right"></i></a></li>';
}
echo'</ul></div></div>';
?>
<?php include 'foot.php'?>
<script>
function banUser(id) {
    layer.confirm('确定要封禁该用户吗？', {
        btn: ['确定', '取消']
    }, function() {
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=setUser&type=ban',
            data: {id: id},
            dataType: 'json',
            success: function(data) {
                if (data.code == 0) {
                    layer.msg('封禁成功', {icon: 1});
                    setTimeout(function(){
                        location.reload();
                    }, 1000);
                } else {
                    layer.msg(data.msg, {icon: 2});
                }
            }
        });
    });
}

function unbanUser(id) {
    layer.confirm('确定要解封该用户吗？', {
        btn: ['确定', '取消']
    }, function() {
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=setUser&type=unban',
            data: {id: id},
            dataType: 'json',
            success: function(data) {
                if (data.code == 0) {
                    layer.msg('解封成功', {icon: 1});
                    setTimeout(function(){
                        location.reload();
                    }, 1000);
                } else {
                    layer.msg(data.msg, {icon: 2});
                }
            }
        });
    });
}

function deleteUser(id) {
    layer.confirm('确定要删除该用户吗？删除后无法恢复！', {
        btn: ['确定', '取消']
    }, function() {
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=setUser&type=del',
            data: {id: id},
            dataType: 'json',
            success: function(data) {
                if (data.code == 0) {
                    layer.msg('删除成功', {icon: 1});
                    setTimeout(function(){
                        location.reload();
                    }, 1000);
                } else {
                    layer.msg(data.msg, {icon: 2});
                }
            }
        });
    });
}
</script>
<style>
.info-empty-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #9ca3af;
    background: #f9fafb;
    border-radius: 0.375rem;
    border: 2px dashed #e5e7eb;
}

.info-empty-overlay i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.info-empty-overlay span {
    font-size: 0.95rem;
}

#page-container.dark-mode .info-empty-overlay {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
</style>
</body>
</html>
