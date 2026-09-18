<?php
/**
 * 课程时间（作息）配置
 * ------------------------------------------------------------------
 * 教务系统接口只返回「节数」（例如 classTime=105106 表示第 5、6 节），不返回具体时间，
 * 因此每一节的起止时间由后台「课程时间配置」维护，并按「课程模式」（教务 kbjcmsid）分别保存。
 *
 * 取值优先级：
 *   1) 指定模式（kbjcmsid）的配置；
 *   2) 后台标记为「默认模式」的配置；
 *   3) 内置默认作息（与旧版写死的作息一致）。
 *
 * 配置表尚未导入时静默回落到内置默认，保证既有功能不受影响。
 */

/**
 * 内置默认节次时间（与原写死的作息完全一致）
 *
 * @return array<int,array{name:string,start:string,end:string}>
 */
function scheduleTimeDefaultPeriods()
{
    return [
        ['name' => '第1节',  'start' => '08:15', 'end' => '08:55'],
        ['name' => '第2节',  'start' => '09:05', 'end' => '09:45'],
        ['name' => '第3节',  'start' => '10:05', 'end' => '10:45'],
        ['name' => '第4节',  'start' => '10:55', 'end' => '11:35'],
        ['name' => '第5节',  'start' => '14:30', 'end' => '15:10'],
        ['name' => '第6节',  'start' => '15:20', 'end' => '16:00'],
        ['name' => '第7节',  'start' => '16:15', 'end' => '16:55'],
        ['name' => '第8节',  'start' => '18:50', 'end' => '19:30'],
        ['name' => '第9节',  'start' => '19:40', 'end' => '20:20'],
        ['name' => '第10节', 'start' => '20:30', 'end' => '21:10'],
        ['name' => '第11节', 'start' => '21:20', 'end' => '22:00'],
    ];
}

/**
 * 单节课最多 / 最少节数（防止后台误填出超长作息）
 */
function scheduleTimeMaxPeriods()
{
    return 20;
}

/**
 * 配置表是否已导入（未导入时静默回落，不抛错）
 */
function scheduleTimeTableReady($DB)
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = false;
    try {
        $rs = @$DB->query("SELECT 1 FROM nteam_schedule_modes LIMIT 1");
        if ($rs) {
            $ready = true;
        }
    } catch (Exception $e) {
        $ready = false;
    }
    return $ready;
}

/**
 * 规范化节次数组：丢弃空行、校验时间格式（HH:MM）、统一字段名
 *
 * @param mixed $periods 数组或 JSON 字符串
 * @return array<int,array{name:string,start:string,end:string}>
 */
function scheduleTimeNormalizePeriods($periods)
{
    if (is_string($periods)) {
        $periods = json_decode($periods, true);
    }
    if (!is_array($periods)) {
        return [];
    }

    $result = [];
    $index  = 0;
    foreach ($periods as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name  = isset($item['name']) ? trim((string) $item['name']) : '';
        $start = isset($item['start']) ? trim((string) $item['start']) : '';
        $end   = isset($item['end']) ? trim((string) $item['end']) : '';
        if ($start === '' && $end === '') {
            continue; // 整行留空视为未填写
        }
        $index++;
        if ($name === '') {
            $name = '第' . $index . '节';
        }

        $s = scheduleTimeNormalizeHm($start);
        $e = scheduleTimeNormalizeHm($end);
        if ($s === null || $e === null) {
            return []; // 时间格式非法 → 交由调用方判定为失败
        }

        $result[] = ['name' => $name, 'start' => $s, 'end' => $e];
        if (count($result) >= scheduleTimeMaxPeriods()) {
            break;
        }
    }

    return $result;
}

/**
 * HH:MM / HH:MM:SS → HH:MM，非法返回 null
 */
function scheduleTimeNormalizeHm($time)
{
    $time = trim((string) $time);
    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $m)) {
        $h = intval($m[1]);
        $i = intval($m[2]);
        if ($h >= 0 && $h <= 23 && $i >= 0 && $i <= 59) {
            return sprintf('%02d:%02d', $h, $i);
        }
    }
    return null;
}

/**
 * 全部课程模式（默认模式排最前）
 */
function scheduleTimeListModes($DB)
{
    if (!scheduleTimeTableReady($DB)) {
        return [];
    }
    $rows = $DB->getAll("SELECT * FROM nteam_schedule_modes ORDER BY is_default DESC, sort_order ASC, id ASC");
    return $rows ? $rows : [];
}

/**
 * 取某个课程模式的原始记录
 */
function scheduleTimeGetModeRow($DB, $id)
{
    if (!scheduleTimeTableReady($DB)) {
        return null;
    }
    $id = intval($id);
    if ($id <= 0) {
        return null;
    }
    return $DB->getRow("SELECT * FROM nteam_schedule_modes WHERE id='$id'");
}

/**
 * 取默认模式记录
 */
function scheduleTimeGetDefaultModeRow($DB)
{
    if (!scheduleTimeTableReady($DB)) {
        return null;
    }
    return $DB->getRow("SELECT * FROM nteam_schedule_modes WHERE is_default=1 ORDER BY sort_order ASC, id ASC LIMIT 1");
}

/**
 * 取节次时间配置及所属模式信息
 *
 * @param string $modeId 课程模式 ID（教务 kbjcmsid），留空则使用默认模式
 * @return array{mode_id:string,mode_name:string,is_default:int,periods:array<int,array{name:string,start:string,end:string}>}
 */
function scheduleTimeGetConfig($DB, $modeId = '')
{
    $fallback = [
        'mode_id'    => '',
        'mode_name'  => '内置默认作息',
        'is_default' => 1,
        'periods'    => scheduleTimeDefaultPeriods(),
    ];

    if (!scheduleTimeTableReady($DB)) {
        return $fallback;
    }

    $row = null;
    $modeId = trim((string) $modeId);
    if ($modeId !== '') {
        $row = $DB->getRow("SELECT * FROM nteam_schedule_modes WHERE mode_id='" . addslashes($modeId) . "' ORDER BY is_default DESC, id ASC LIMIT 1");
    }
    // 未指定模式时，优先与系统默认课程模式（后台「学术推送配置」的默认课表类型）保持一致
    if (!$row) {
        try {
            $cfgRow = $DB->getRow("SELECT default_schedule_mode FROM nteam_academic_push_config WHERE id = 1");
            if ($cfgRow && !empty($cfgRow['default_schedule_mode'])) {
                $row = $DB->getRow("SELECT * FROM nteam_schedule_modes WHERE mode_id='" . addslashes($cfgRow['default_schedule_mode']) . "' ORDER BY id ASC LIMIT 1");
            }
        } catch (Exception $e) {
            $row = null;
        }
    }
    if (!$row) {
        $row = scheduleTimeGetDefaultModeRow($DB);
    }
    if (!$row) {
        return $fallback;
    }

    $periods = scheduleTimeNormalizePeriods(isset($row['periods']) ? $row['periods'] : '');
    if (empty($periods)) {
        $periods = scheduleTimeDefaultPeriods();
    }

    return [
        'mode_id'    => (string) $row['mode_id'],
        'mode_name'  => (string) $row['mode_name'],
        'is_default' => intval($row['is_default']) === 1 ? 1 : 0,
        'periods'    => $periods,
    ];
}

/**
 * 取节次时间配置
 *
 * @param string $modeId 课程模式 ID（教务 kbjcmsid），留空则使用默认模式
 * @return array<int,array{name:string,start:string,end:string}>
 */
function scheduleTimeGetPeriods($DB, $modeId = '')
{
    $cfg = scheduleTimeGetConfig($DB, $modeId);
    return $cfg['periods'];
}

/**
 * 转成课程表页面使用的列表：[['name'=>'第1节','time'=>'08:15-08:55'], ...]（0 基索引）
 *
 * @param int $padTo 不足该节数时补占位行（避免数据里的高节次课程没有行可渲染）
 */
function scheduleTimePeriodsToList($periods, $padTo = 0)
{
    $list = [];
    foreach ($periods as $p) {
        $list[] = [
            'name' => (string) $p['name'],
            'time' => $p['start'] . '-' . $p['end'],
        ];
    }
    $total = count($list);
    for ($i = $total; $i < intval($padTo); $i++) {
        $list[] = ['name' => '第' . ($i + 1) . '节', 'time' => ''];
    }
    return $list;
}

/**
 * 转成以节次编码为键的映射：['101'=>['name'=>'第1节','time'=>'08:15-08:55'], ...]
 * 编码规则与教务一致：1 + 两位节次（101 表示第 1 节）
 *
 * @param int $padTo 不足该节数时补占位行
 */
function scheduleTimePeriodsByCode($periods, $padTo = 11)
{
    $map = [];
    $index = 0;
    foreach ($periods as $p) {
        $index++;
        $code = '1' . sprintf('%02d', $index);
        $map[$code] = [
            'name' => (string) $p['name'],
            'time' => $p['start'] . '-' . $p['end'],
        ];
    }
    for ($i = $index; $i < intval($padTo); $i++) {
        $code = '1' . sprintf('%02d', $i + 1);
        $map[$code] = ['name' => '第' . ($i + 1) . '节', 'time' => ''];
    }
    return $map;
}

/**
 * 取节次数量（用于后台列表展示）
 */
function scheduleTimeCountPeriods($periodsJson)
{
    $periods = scheduleTimeNormalizePeriods($periodsJson);
    return count($periods);
}

/**
 * 保存课程模式（新增 / 修改）
 *
 * @param array $data id / mode_name / mode_id / is_default / periods(JSON) / sort_order
 * @return array{code:int,msg:string}
 */
function scheduleTimeSaveMode($DB, $data)
{
    if (!scheduleTimeTableReady($DB)) {
        return ['code' => -1, 'msg' => '课程时间配置表未导入，请先执行 php/schedule_time_update.sql'];
    }

    $id        = isset($data['id']) ? intval($data['id']) : 0;
    $modeName  = isset($data['mode_name']) ? trim((string) $data['mode_name']) : '';
    $modeId    = isset($data['mode_id']) ? trim((string) $data['mode_id']) : '';
    $isDefault = (isset($data['is_default']) && intval($data['is_default']) === 1) ? 1 : 0;
    $sortOrder = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

    if ($modeName === '') {
        return ['code' => -1, 'msg' => '请填写课程模式名称'];
    }
    if (mb_strlen($modeName, 'UTF-8') > 50) {
        return ['code' => -1, 'msg' => '课程模式名称过长（最多 50 字）'];
    }
    if ($modeId !== '' && mb_strlen($modeId, 'UTF-8') > 50) {
        return ['code' => -1, 'msg' => '课程模式 ID 过长（最多 50 字）'];
    }

    $periods = scheduleTimeNormalizePeriods(isset($data['periods']) ? $data['periods'] : '');
    if (empty($periods)) {
        return ['code' => -1, 'msg' => '请至少填写一节课的时间，且时间格式为 HH:MM'];
    }
    foreach ($periods as $p) {
        if (strtotime($p['end']) <= strtotime($p['start'])) {
            return ['code' => -1, 'msg' => '「' . $p['name'] . '」的结束时间必须晚于开始时间'];
        }
    }

    // 模式 ID 唯一（同一 kbjcmsid 只允许一条）
    if ($modeId !== '') {
        $exist = $DB->getRow("SELECT id FROM nteam_schedule_modes WHERE mode_id='" . addslashes($modeId) . "' AND id<>'$id' LIMIT 1");
        if ($exist) {
            return ['code' => -1, 'msg' => '该课程模式 ID 已存在，请勿重复添加'];
        }
    } else {
        $exist = $DB->getRow("SELECT id FROM nteam_schedule_modes WHERE mode_id='' AND id<>'$id' LIMIT 1");
        if ($id <= 0 && $exist) {
            return ['code' => -1, 'msg' => '已存在「默认模式」（模式 ID 留空），请直接编辑它'];
        }
    }

    $periodsJson = addslashes(json_encode(array_values($periods), JSON_UNESCAPED_UNICODE));
    $modeNameSql = addslashes($modeName);
    $modeIdSql   = addslashes($modeId);

    if ($id > 0) {
        $exists = scheduleTimeGetModeRow($DB, $id);
        if (!$exists) {
            return ['code' => -1, 'msg' => '课程模式不存在'];
        }
        $ok = $DB->exec("UPDATE nteam_schedule_modes SET
                mode_name='$modeNameSql',
                mode_id='$modeIdSql',
                periods='$periodsJson',
                is_default='$isDefault',
                sort_order='$sortOrder'
            WHERE id='$id'");
    } else {
        $ok = $DB->exec("INSERT INTO nteam_schedule_modes (mode_id, mode_name, periods, is_default, sort_order)
            VALUES ('$modeIdSql', '$modeNameSql', '$periodsJson', '$isDefault', '$sortOrder')");
    }

    if ($ok === false) {
        return ['code' => -1, 'msg' => '保存失败[' . $DB->error() . ']'];
    }

    // 默认模式全局唯一
    if ($isDefault === 1) {
        $newId = $id > 0 ? $id : intval($DB->getColumn("SELECT id FROM nteam_schedule_modes WHERE mode_id='$modeIdSql' ORDER BY id DESC LIMIT 1"));
        if ($newId > 0) {
            $DB->exec("UPDATE nteam_schedule_modes SET is_default=0 WHERE id<>'$newId'");
        }
    }

    return ['code' => 0, 'msg' => $id > 0 ? '课程时间保存成功' : '课程模式添加成功'];
}

/**
 * 删除课程模式（默认模式不可直接删除，需先指定其它默认模式）
 */
function scheduleTimeDeleteMode($DB, $id)
{
    if (!scheduleTimeTableReady($DB)) {
        return ['code' => -1, 'msg' => '课程时间配置表未导入'];
    }
    $row = scheduleTimeGetModeRow($DB, $id);
    if (!$row) {
        return ['code' => -1, 'msg' => '课程模式不存在'];
    }
    if (intval($row['is_default']) === 1) {
        $others = intval($DB->getColumn("SELECT COUNT(*) FROM nteam_schedule_modes WHERE id<>'" . intval($id) . "'"));
        if ($others > 0) {
            return ['code' => -1, 'msg' => '该模式是默认模式，请先将其它模式设为默认后再删除'];
        }
        return ['code' => -1, 'msg' => '至少保留一个课程模式（默认模式不可删除）'];
    }
    if ($DB->exec("DELETE FROM nteam_schedule_modes WHERE id='" . intval($id) . "'") === false) {
        return ['code' => -1, 'msg' => '删除失败[' . $DB->error() . ']'];
    }
    return ['code' => 0, 'msg' => '删除成功'];
}

/**
 * 设为默认模式
 */
function scheduleTimeSetDefaultMode($DB, $id)
{
    if (!scheduleTimeTableReady($DB)) {
        return ['code' => -1, 'msg' => '课程时间配置表未导入'];
    }
    $row = scheduleTimeGetModeRow($DB, $id);
    if (!$row) {
        return ['code' => -1, 'msg' => '课程模式不存在'];
    }
    $id = intval($id);
    if ($DB->exec("UPDATE nteam_schedule_modes SET is_default=0 WHERE id<>'$id'") === false) {
        return ['code' => -1, 'msg' => '操作失败[' . $DB->error() . ']'];
    }
    if ($DB->exec("UPDATE nteam_schedule_modes SET is_default=1 WHERE id='$id'") === false) {
        return ['code' => -1, 'msg' => '操作失败[' . $DB->error() . ']'];
    }
    return ['code' => 0, 'msg' => '已设为默认模式'];
}

/**
 * 恢复内置默认节次
 */
function scheduleTimeResetDefaults()
{
    return scheduleTimeDefaultPeriods();
}
