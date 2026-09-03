<?php
$mod = 'admin';
$title='邮件发送日志';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
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
                <h3 class="block-title"> 邮件发送日志 </h3>
                <div class="block-options">
                    <button type="button" onclick="clearLogs()" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> 清空日志
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
                        <thead>
                        <tr>
                            <th class="text-center" style="min-width: 60px;">ID</th>
                            <th style="min-width: 200px;">接收邮箱</th>
                            <th style="min-width: 300px;">邮件主题</th>
                            <th style="min-width: 80px;">状态</th>
                            <th style="min-width: 200px;">错误信息</th>
                            <th style="min-width: 120px;">发送IP</th>
                            <th style="min-width: 160px;">发送时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $pageSize = 10;
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        $offset = ($page - 1) * $pageSize;

                        $totalResult = $DB->query("SELECT COUNT(*) as total FROM nteam_mail_log")->fetch();
                        $totalRows = $totalResult['total'];
                        
                        $rs=$DB->query("SELECT * FROM nteam_mail_log ORDER BY id DESC LIMIT $offset, $pageSize");
                        $hasData = false;
                        while($res = $rs->fetch())
                        {
                            $hasData = true;
                            $statusBadge = $res['status'] == 1 ? '<span class="badge bg-success">成功</span>' : '<span class="badge bg-danger">失败</span>';
                            $errorMsg = $res['error_msg'] ? '<span class="text-danger" title="' . htmlspecialchars($res['error_msg']) . '">' . mb_substr($res['error_msg'], 0, 20) . '</span>' : '-';
                            echo '
                            <tr>
                                <td class="text-center">'.$res['id'].'</td>
                                <td>'.htmlspecialchars($res['to_email']).'</td>
                                <td>'.htmlspecialchars($res['subject']).'</td>
                                <td>'.$statusBadge.'</td>
                                <td>'.$errorMsg.'</td>
                                <td>'.htmlspecialchars($res['ip']).'</td>
                                <td>'.$res['create_time'].'</td>
                            </tr>';
                        }
                        
                        if (!$hasData) {
                            echo '<tr><td colspan="7">
                                <div class="info-empty-overlay">
                                    <i class="fa fa-inbox"></i>
                                    <span>暂无邮件发送日志</span>
                                </div>
                            </td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <?php 
                $totalResult = $DB->query("SELECT COUNT(*) as total FROM nteam_mail_log")->fetch();
                $totalPages = ceil($totalResult['total'] / $pageSize);
                
                if($totalPages >= 1): 
                ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm">
                            <?php 
                            $first = 1;
                            $prev = $page - 1;
                            $next = $page + 1;
                            $last = $totalPages;
                            
                            if ($page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page='.$first.'"><i class="fa fa-angle-double-left"></i></a></li>';
                                echo '<li class="page-item"><a class="page-link" href="?page='.$prev.'"><i class="fa fa-angle-left"></i></a></li>';
                            } else {
                                echo '<li class="page-item disabled"><a class="page-link"><i class="fa fa-angle-double-left"></i></a></li>';
                                echo '<li class="page-item disabled"><a class="page-link"><i class="fa fa-angle-left"></i></a></li>';
                            }
                            
                            $start = $page - 5 > 1 ? $page - 5 : 1;
                            $end = $page + 5 < $totalPages ? $page + 5 : $totalPages;
                            
                            for ($i = $start; $i < $page; $i++) {
                                echo '<li class="page-item"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
                            }
                            
                            echo '<li class="page-item active"><a class="page-link">'.$page.'</a></li>';
                            
                            for ($i = $page + 1; $i <= $end; $i++) {
                                echo '<li class="page-item"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
                            }
                            
                            if ($page < $totalPages) {
                                echo '<li class="page-item"><a class="page-link" href="?page='.$next.'"><i class="fa fa-angle-right"></i></a></li>';
                                echo '<li class="page-item"><a class="page-link" href="?page='.$last.'"><i class="fa fa-angle-double-right"></i></a></li>';
                            } else {
                                echo '<li class="page-item disabled"><a class="page-link"><i class="fa fa-angle-right"></i></a></li>';
                                echo '<li class="page-item disabled"><a class="page-link"><i class="fa fa-angle-double-right"></i></a></li>';
                            }
                            ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
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
<script>
function clearLogs() {
    layer.confirm('确定要清空所有邮件发送日志吗？此操作不可恢复！', {
        icon: 3,
        title: '确认清空'
    }, function(index) {
        layer.close(index);
        var ii = layer.load(2, {shade:[0.1,'#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clearMailLogs',
            dataType: 'json',
            success: function(data) {
                layer.close(ii);
                if(data.code == 0){
                    layer.alert(data.msg, {
                        icon: 1,
                        closeBtn: false
                    }, function(){
                        window.location.reload()
                    });
                }else{
                    layer.alert(data.msg, {icon: 2})
                }
            },
            error:function(data){
                layer.msg('服务器错误');
            }
        });
    });
}
</script>
</body>
</html>