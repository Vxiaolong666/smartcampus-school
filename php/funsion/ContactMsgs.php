<?php
/**
 * 联系我们留言 / 用户反馈（nteam_contact_msgs）
 * ------------------------------------------------------------------
 * 数据来源：
 *   - source=site：官网「联系我们」表单提交的访客留言（user_id=0）
 *   - source=user：网页用户端「联系我们」提交的反馈（user_id 为登录用户）
 *
 * 处理状态（handle_status）：
 *   0 待处理、1 处理中、2 已解决、3 已驳回、4 已忽略
 *
 * 表结构升级（新增 user_id / source / handle_status / remark / update_time 及 user_id 索引）
 * 由 contactMsgEnsureSchema() 幂等补齐，也可手动导入 php/contact_msgs_update.sql。
 * 未升级的环境下 related 方法会自动退化为旧字段，保证官网留言提交不受影响。
 */

if (!function_exists('contactMsgStatusMap')) {
    /**
     * 处理状态字典（值 => 名称）
     */
    function contactMsgStatusMap() {
        return array(
            0 => '待处理',
            1 => '处理中',
            2 => '已解决',
            3 => '已驳回',
            4 => '已忽略',
        );
    }
}

if (!function_exists('contactMsgStatusLabel')) {
    /**
     * 状态名称（非法值按「待处理」处理）
     */
    function contactMsgStatusLabel($status) {
        $map = contactMsgStatusMap();
        $status = intval($status);
        return isset($map[$status]) ? $map[$status] : $map[0];
    }
}

if (!function_exists('contactMsgStatusClass')) {
    /**
     * 状态对应的 Bootstrap badge 配色（后台与用户端共用）
     */
    function contactMsgStatusClass($status) {
        switch (intval($status)) {
            case 1:  return 'bg-info';        // 处理中
            case 2:  return 'bg-success';     // 已解决
            case 3:  return 'bg-danger';      // 已驳回
            case 4:  return 'bg-secondary';   // 已忽略
            default: return 'bg-warning';     // 待处理
        }
    }
}

if (!function_exists('contactMsgStatusValid')) {
    /**
     * 是否为合法的处理状态
     */
    function contactMsgStatusValid($status) {
        return array_key_exists(intval($status), contactMsgStatusMap());
    }
}

if (!function_exists('contactMsgColumns')) {
    /**
     * 留言表字段（键为字段名，请求内缓存；表不存在时返回空数组）
     */
    function contactMsgColumns($DB) {
        static $cols = null;
        if ($cols !== null) {
            return $cols;
        }
        $cols = array();
        $rows = @$DB->getAll("SHOW COLUMNS FROM nteam_contact_msgs");
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['Field'])) {
                    $cols[$row['Field']] = true;
                }
            }
        }
        return $cols;
    }
}

if (!function_exists('contactMsgTableReady')) {
    /**
     * 留言表是否已创建
     */
    function contactMsgTableReady($DB) {
        return count(contactMsgColumns($DB)) > 0;
    }
}

if (!function_exists('contactMsgSchemaReady')) {
    /**
     * 留言表是否已包含反馈功能所需的全部字段
     */
    function contactMsgSchemaReady($DB) {
        $cols = contactMsgColumns($DB);
        foreach (array('user_id', 'source', 'handle_status', 'remark', 'update_time') as $need) {
            if (!isset($cols[$need])) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('contactMsgEnsureSchema')) {
    /**
     * 幂等补齐反馈功能所需字段（仅新增列/索引，不修改已有数据）
     *
     * @return bool 补齐后是否满足新的表结构
     */
    function contactMsgEnsureSchema($DB) {
        if (!contactMsgTableReady($DB)) {
            return false;
        }
        if (contactMsgSchemaReady($DB)) {
            return true;
        }

        $cols = contactMsgColumns($DB);
        $alters = array();
        if (!isset($cols['user_id'])) {
            $alters[] = "ADD COLUMN `user_id` INT(11) NOT NULL DEFAULT 0 COMMENT '关联用户ID（0=官网访客）'";
        }
        if (!isset($cols['source'])) {
            $alters[] = "ADD COLUMN `source` VARCHAR(16) NOT NULL DEFAULT 'site' COMMENT '来源：site=官网，user=用户端'";
        }
        if (!isset($cols['handle_status'])) {
            $alters[] = "ADD COLUMN `handle_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '处理状态：0待处理 1处理中 2已解决 3已驳回 4已忽略'";
        }
        if (!isset($cols['remark'])) {
            $alters[] = "ADD COLUMN `remark` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '处理备注（用户端可见）'";
        }
        if (!isset($cols['update_time'])) {
            $alters[] = "ADD COLUMN `update_time` DATETIME NULL DEFAULT NULL COMMENT '状态最后更新时间'";
        }
        if ($alters) {
            @$DB->exec("ALTER TABLE nteam_contact_msgs " . implode(', ', $alters));
        }

        // 用户端按 user_id 查自己的反馈记录，补一个索引
        $hasIndex = false;
        $indexRows = @$DB->getAll("SHOW INDEX FROM nteam_contact_msgs");
        if (is_array($indexRows)) {
            foreach ($indexRows as $idx) {
                if (isset($idx['Key_name']) && $idx['Key_name'] === 'user_id') {
                    $hasIndex = true;
                    break;
                }
            }
        }
        if (!$hasIndex) {
            @$DB->exec("ALTER TABLE nteam_contact_msgs ADD INDEX `user_id` (`user_id`)");
        }

        // 重新读取一次字段列表，避免请求内缓存导致误判
        $fresh = array();
        $rows = @$DB->getAll("SHOW COLUMNS FROM nteam_contact_msgs");
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['Field'])) {
                    $fresh[$row['Field']] = true;
                }
            }
        }
        return isset($fresh['handle_status']) && isset($fresh['user_id']);
    }
}

if (!function_exists('contactMsgAdd')) {
    /**
     * 写入一条留言 / 反馈
     *
     * @param array $data name,phone,email,subject,content,ip,ua,user_id,source
     * @return int|false 新记录 id，失败返回 false
     */
    function contactMsgAdd($DB, $data) {
        $name    = isset($data['name']) ? (string)$data['name'] : '';
        $phone   = isset($data['phone']) ? (string)$data['phone'] : '';
        $email   = isset($data['email']) ? (string)$data['email'] : '';
        $subject = isset($data['subject']) ? (string)$data['subject'] : '';
        $content = isset($data['content']) ? (string)$data['content'] : '';
        $ip      = isset($data['ip']) ? (string)$data['ip'] : '';
        $ua      = isset($data['ua']) ? (string)$data['ua'] : '';
        $userId  = isset($data['user_id']) ? intval($data['user_id']) : 0;
        $source  = ($userId > 0) ? 'user' : 'site';

        // 旧结构环境：只写原有字段，保证提交不失败
        if (!contactMsgSchemaReady($DB)) {
            $ok = $DB->exec("INSERT INTO nteam_contact_msgs (`name`,`phone`,`email`,`subject`,`content`,`ip`,`ua`,`status`)
                VALUES ('" . addslashes($name) . "','" . addslashes($phone) . "','" . addslashes($email) . "','"
                . addslashes($subject) . "','" . addslashes($content) . "','" . addslashes($ip) . "','"
                . addslashes($ua) . "',0)");
            return $ok === false ? false : intval($DB->lastInsertId());
        }

        $ok = $DB->exec("INSERT INTO nteam_contact_msgs
            (`name`,`phone`,`email`,`subject`,`content`,`ip`,`ua`,`user_id`,`source`,`handle_status`,`remark`,`status`)
            VALUES ('" . addslashes($name) . "','" . addslashes($phone) . "','" . addslashes($email) . "','"
            . addslashes($subject) . "','" . addslashes($content) . "','" . addslashes($ip) . "','"
            . addslashes($ua) . "','" . $userId . "','" . addslashes($source) . "',0,'',0)");
        return $ok === false ? false : intval($DB->lastInsertId());
    }
}

if (!function_exists('contactMsgSetStatus')) {
    /**
     * 更新处理状态（可同时写入处理备注）；非「待处理」时自动标记为已读
     *
     * @return bool
     */
    function contactMsgSetStatus($DB, $id, $status, $remark = '') {
        $id = intval($id);
        if ($id <= 0 || !contactMsgStatusValid($status)) {
            return false;
        }
        $status = intval($status);
        $remarkSql = addslashes(trim((string)$remark));
        $readSql = $status === 0 ? '' : ", `status`=1";
        $ok = $DB->exec("UPDATE nteam_contact_msgs SET `handle_status`='$status', `remark`='$remarkSql',
            `update_time`=NOW()$readSql WHERE `id`='$id'");
        return $ok !== false;
    }
}
