<?php
/**
 * 协议中心：协议内容的读写
 * ------------------------------------------------------------------
 * 数据表 nteam_agreements（由一次性更新脚本 php/agreements_update.sql 创建并写入预设协议），
 * 后台「协议管理」（Adminq/agreement_manage.php）维护，
 * 用户端经 api.php?act=get_agreements（清单）/ act=get_agreement（单份内容）获取。
 *
 * 说明：本模块只做读写，不自动建表、不写入任何预设协议内容。
 *       表未创建时相关方法返回空/失败，调用方需自行降级处理。
 */

/**
 * 协议表是否可用（存在）
 */
function agreementTableReady($DB)
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = false;
    try {
        $rs = @$DB->query("SELECT 1 FROM nteam_agreements LIMIT 1");
        if ($rs) {
            $ready = true;
        }
    } catch (Exception $e) {
        $ready = false;
    }
    return $ready;
}

/**
 * 协议清单
 *
 * @param bool $onlyEnabled 仅返回启用项（用户端）
 * @param bool $withContent 是否包含正文（后台编辑回填时需要）
 */
function agreementList($DB, $onlyEnabled = true, $withContent = false)
{
    if (!agreementTableReady($DB)) {
        return [];
    }
    $where = $onlyEnabled ? "WHERE `status`=1" : "";
    $fields = '`id`,`akey`,`title`,`sort_order`,`status`,`is_system`,`updated_at`';
    if ($withContent) {
        $fields .= ',`content`';
    }
    $rows = $DB->getAll("SELECT $fields FROM nteam_agreements $where ORDER BY `sort_order` ASC, `id` ASC");
    return is_array($rows) ? $rows : [];
}

/**
 * 按 key 取协议（含正文）
 */
function agreementGet($DB, $key)
{
    if (!agreementTableReady($DB)) {
        return null;
    }
    $key = addslashes(trim((string) $key));
    if ($key === '') {
        return null;
    }
    $row = $DB->getRow("SELECT * FROM nteam_agreements WHERE `akey`='$key' LIMIT 1");
    return $row ? $row : null;
}

/**
 * 按 id 取协议
 */
function agreementGetById($DB, $id)
{
    if (!agreementTableReady($DB)) {
        return null;
    }
    $id = intval($id);
    if ($id <= 0) {
        return null;
    }
    $row = $DB->getRow("SELECT * FROM nteam_agreements WHERE `id`='$id' LIMIT 1");
    return $row ? $row : null;
}

/**
 * 新增 / 修改协议
 *
 * @return array{code:int,msg:string,id:int}
 */
function agreementSave($DB, $data)
{
    if (!agreementTableReady($DB)) {
        return ['code' => -1, 'msg' => '协议表未创建，请先执行 php/agreements_update.sql', 'id' => 0];
    }
    $id = intval($data['id'] ?? 0);
    $title = trim((string) ($data['title'] ?? ''));
    $content = (string) ($data['content'] ?? '');
    $akey = strtolower(trim((string) ($data['akey'] ?? '')));
    $sort = intval($data['sort_order'] ?? 0);
    $status = intval($data['status'] ?? 1) === 1 ? 1 : 0;

    if ($title === '') {
        return ['code' => -1, 'msg' => '请填写协议名称', 'id' => 0];
    }
    if (mb_strlen($title, 'UTF-8') > 100) {
        return ['code' => -1, 'msg' => '协议名称过长（最多 100 字）', 'id' => 0];
    }
    if (trim($content) === '') {
        return ['code' => -1, 'msg' => '请填写协议内容', 'id' => 0];
    }
    // 标识：仅允许字母数字下划线短横线；留空自动生成
    if ($akey !== '' && !preg_match('/^[a-z0-9_-]{1,50}$/', $akey)) {
        return ['code' => -1, 'msg' => '协议标识仅支持字母、数字、下划线或短横线（最多 50 位）', 'id' => 0];
    }
    if ($akey === '') {
        $akey = 'ag' . ($id > 0 ? $id : substr(md5(uniqid('', true)), 0, 10));
    }

    // 标识唯一
    $existKey = $DB->getRow("SELECT id FROM nteam_agreements WHERE `akey`='" . addslashes($akey) . "' AND `id`<>'$id' LIMIT 1");
    if ($existKey) {
        return ['code' => -1, 'msg' => '协议标识「' . $akey . '」已存在，请更换', 'id' => 0];
    }

    $titleSql = addslashes($title);
    $contentSql = addslashes($content);
    $keySql = addslashes($akey);

    if ($id > 0) {
        $row = agreementGetById($DB, $id);
        if (!$row) {
            return ['code' => -1, 'msg' => '协议不存在', 'id' => 0];
        }
        // 系统内置协议不允许修改标识
        if (intval($row['is_system']) === 1 && $keySql !== $row['akey']) {
            $keySql = addslashes($row['akey']);
        }
        $ok = $DB->exec("UPDATE nteam_agreements SET `akey`='$keySql', `title`='$titleSql', `content`='$contentSql',
            `sort_order`='$sort', `status`='$status', `updated_at`=NOW() WHERE `id`='$id'");
        if ($ok === false) {
            return ['code' => -1, 'msg' => '保存失败[' . $DB->error() . ']', 'id' => $id];
        }
        return ['code' => 0, 'msg' => '保存成功', 'id' => $id];
    }

    $ok = $DB->exec("INSERT INTO nteam_agreements (`akey`,`title`,`content`,`sort_order`,`status`,`is_system`,`created_at`,`updated_at`)
        VALUES ('$keySql','$titleSql','$contentSql','$sort','$status',0,NOW(),NOW())");
    if ($ok === false) {
        return ['code' => -1, 'msg' => '添加失败[' . $DB->error() . ']', 'id' => 0];
    }
    return ['code' => 0, 'msg' => '添加成功', 'id' => intval($DB->lastInsertId())];
}

/**
 * 删除协议（系统内置不可删除）
 *
 * @return array{code:int,msg:string}
 */
function agreementDelete($DB, $id)
{
    if (!agreementTableReady($DB)) {
        return ['code' => -1, 'msg' => '协议表未创建'];
    }
    $row = agreementGetById($DB, $id);
    if (!$row) {
        return ['code' => -1, 'msg' => '协议不存在'];
    }
    if (intval($row['is_system']) === 1) {
        return ['code' => -1, 'msg' => '系统内置协议不可删除，可将其「停用」'];
    }
    if ($DB->exec("DELETE FROM nteam_agreements WHERE `id`='" . intval($id) . "'") === false) {
        return ['code' => -1, 'msg' => '删除失败[' . $DB->error() . ']'];
    }
    return ['code' => 0, 'msg' => '删除成功'];
}

/**
 * 启用 / 停用协议
 *
 * @return array{code:int,msg:string}
 */
function agreementSetStatus($DB, $id, $status)
{
    if (!agreementTableReady($DB)) {
        return ['code' => -1, 'msg' => '协议表未创建'];
    }
    $row = agreementGetById($DB, $id);
    if (!$row) {
        return ['code' => -1, 'msg' => '协议不存在'];
    }
    $status = intval($status) === 1 ? 1 : 0;
    if ($DB->exec("UPDATE nteam_agreements SET `status`='$status', `updated_at`=NOW() WHERE `id`='" . intval($id) . "'") === false) {
        return ['code' => -1, 'msg' => '操作失败[' . $DB->error() . ']'];
    }
    return ['code' => 0, 'msg' => $status === 1 ? '已启用' : '已停用'];
}
