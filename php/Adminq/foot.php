</main>
    <footer id="page-footer">
        <div class="content py-3">
            <div class="row fs-sm">
                <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                    <a class="fw-semibold" href="https://beian.miit.gov.cn/" target="_blank"><?php echo conf('ICP');?></a>
                </div>
                <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                    &copy; <span data-toggle="year-copy"></span> <a class="fw-semibold" href="//<?php echo conf('Url');?>" target="_blank"><?php echo conf('Name');?></a>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
(function () {
    // 教务学期/课程模式下拉采用异步回填：页面打开时只读缓存，
    // 缺失时后台拉取并刷新一次，避免首屏等待教务接口。
    var formTouched = false;
    $(document).on('input change', 'form', function () { formTouched = true; });

    function autoRefreshAcademicMeta() {
        if (!$('#grade_semester').length && !$('#modeKbjcmsid').length) return;
        if ($('#grade_semester option').length > 1 || $('#modeKbjcmsid option').length > 1) return;
        var flag = 'academic_meta_refreshed_' + location.pathname + location.search;
        try {
            var last = Number(sessionStorage.getItem(flag) || 0);
            if (last && (Date.now() - last) < 300000) return;
        } catch (e) {}
        $.getJSON('Ajax.php?act=academic_meta_refresh', function (res) {
            if (res && res.code === 0 && res.changed && !formTouched) {
                try { sessionStorage.setItem(flag, String(Date.now())); } catch (e) {}
                window.location.reload();
            }
        });
    }
    $(autoRefreshAcademicMeta);
    $(document).on('pjax:end', function () {
        formTouched = false;
        autoRefreshAcademicMeta();
    });
})();
</script>

</body>
</html>