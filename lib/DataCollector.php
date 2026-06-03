<?php
/**
 * Read-only FreePBX/Asterisk data collector for Shikem syncs.
 */

namespace Shikem;

class DataCollector {

    private $db;
    private $cdrDb;
    private $recordingDir;
    private $voicemailDir;

    public function __construct($db = null, $cdrDb = null, $recordingDir = '/var/spool/asterisk/monitor', $voicemailDir = '/var/spool/asterisk/voicemail') {
        $this->db = $db;
        $this->cdrDb = $cdrDb ?: $db;
        $this->recordingDir = $recordingDir;
        $this->voicemailDir = $voicemailDir;
    }

    public function collectAll() {
        return [
            'extensions' => $this->collectExtensions(),
            'cdr' => $this->collectCdr(),
            'voicemail' => $this->collectVoicemail(),
            'recordings' => $this->collectRecordings(),
            'inventory' => $this->collectInventory(),
            'collectedAt' => gmdate('c'),
        ];
    }

    public function collectExtensions() {
        return [
            'users' => $this->selectTable('users', ['extension', 'name', 'voicemail', 'ringtimer', 'noanswer', 'recording']),
            'devices' => $this->selectTable('devices', ['id', 'tech', 'dial', 'devicetype', 'user', 'description', 'emergency_cid']),
            'sipSettings' => $this->selectKvTable('sip'),
            'pjsipSettings' => $this->selectKvTable('pjsip'),
            'iaxSettings' => $this->selectKvTable('iax'),
        ];
    }

    public function collectCdr($limit = 500) {
        $limit = max(1, min((int) $limit, 2000));
        if (!$this->cdrDb || !$this->tableExists($this->cdrDb, 'cdr')) {
            return [];
        }

        $available = $this->tableColumns($this->cdrDb, 'cdr');
        $wanted = ['calldate', 'clid', 'src', 'dst', 'dcontext', 'channel', 'dstchannel', 'lastapp', 'lastdata', 'duration', 'billsec', 'disposition', 'uniqueid', 'linkedid', 'recordingfile'];
        $selected = array_values(array_intersect($wanted, $available));
        if (!$selected) {
            return [];
        }

        $order = in_array('calldate', $available, true) ? ' ORDER BY `calldate` DESC' : '';
        return $this->queryRows(
            $this->cdrDb,
            'SELECT ' . implode(', ', array_map([$this, 'quoteIdentifier'], $selected)) . ' FROM `cdr`' . $order . ' LIMIT ' . $limit
        );
    }

    public function collectVoicemail() {
        return [
            'mailboxes' => $this->selectTable('voicemail_users', ['context', 'mailbox', 'fullname', 'email', 'pager', 'options']),
            'files' => $this->scanVoicemailFiles(),
        ];
    }

    public function collectRecordings($limit = 500) {
        return $this->scanFiles($this->recordingDir, $limit, [
            'wav',
            'WAV',
            'mp3',
            'gsm',
        ]);
    }

    public function collectInventory() {
        return [
            'trunks' => $this->selectTable('trunks', ['trunkid', 'name', 'tech', 'channelid', 'outcid', 'keepcid', 'maxchans', 'disabled']),
            'inboundRoutes' => $this->selectTable('incoming', ['cidnum', 'extension', 'destination', 'privacyman', 'alertinfo', 'ringing']),
            'outboundRoutes' => $this->selectTable('outbound_routes', ['route_id', 'name', 'outcid', 'mohsilence', 'time_group_id']),
            'outboundRoutePatterns' => $this->selectTable('outbound_route_patterns', ['route_id', 'match_pattern_prefix', 'match_pattern_pass', 'match_cid']),
            'queues' => $this->selectTable('queues_config', ['extension', 'descr', 'grppre', 'alertinfo', 'strategy', 'timeout', 'retry', 'wrapuptime', 'maxlen', 'joinempty', 'leavewhenempty']),
            'queueMembers' => $this->selectTable('queue_members', ['queue_name', 'interface', 'penalty', 'paused']),
            'ringGroups' => $this->selectTable('ringgroups', ['grpnum', 'description', 'grplist', 'strategy', 'grptime', 'postdest']),
            'ivrs' => $this->selectTable('ivr_details', ['id', 'name', 'description', 'announcement', 'directdial', 'timeout_time', 'timeout_append_announce', 'timeout_ivr_ret']),
            'ivrEntries' => $this->selectTable('ivr_entries', ['ivr_id', 'selection', 'dest']),
            'timeConditions' => $this->selectTable('timeconditions', ['timeconditions_id', 'displayname', 'time', 'truegoto', 'falsegoto']),
            'timeGroups' => $this->selectTable('timegroups_groups', ['id', 'description']),
            'conferences' => $this->selectTable('meetme', ['exten', 'options', 'userpin', 'adminpin', 'description']),
        ];
    }

    public function summarize($payload) {
        $summary = [];
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $summary[$key] = $this->countNested($value);
            }
        }
        return $summary;
    }

    private function selectTable($table, $columns = ['*']) {
        if (!$this->db) {
            return [];
        }
        if (!$this->tableExists($this->db, $table)) {
            return [];
        }

        $available = $this->tableColumns($this->db, $table);
        if ($columns === ['*']) {
            $select = '*';
        } else {
            $selected = array_values(array_intersect($columns, $available));
            if (!$selected) {
                return [];
            }
            $select = implode(', ', array_map([$this, 'quoteIdentifier'], $selected));
        }

        return $this->queryRows($this->db, 'SELECT ' . $select . ' FROM ' . $this->quoteIdentifier($table));
    }

    private function selectKvTable($table) {
        $rows = $this->selectTable($table, ['id', 'keyword', 'data', 'flags']);
        $grouped = [];
        foreach ($rows as $row) {
            $id = isset($row['id']) ? (string) $row['id'] : 'unknown';
            if (!isset($grouped[$id])) {
                $grouped[$id] = [];
            }
            if (isset($row['keyword'])) {
                if ($this->isSensitiveKey($row['keyword'])) {
                    continue;
                }
                $grouped[$id][$row['keyword']] = $row['data'] ?? null;
            } else {
                $grouped[$id][] = $row;
            }
        }
        return $grouped;
    }

    private function tableExists($db, $table) {
        $rows = $this->queryRows($db, "SHOW TABLES LIKE '" . $this->escapeSql($table) . "'");
        return !empty($rows);
    }

    private function tableColumns($db, $table) {
        $rows = $this->queryRows($db, 'SHOW COLUMNS FROM ' . $this->quoteIdentifier($table));
        $columns = [];
        foreach ($rows as $row) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }
        return $columns;
    }

    private function queryRows($db, $sql) {
        if (!$db) {
            return [];
        }

        try {
            if (method_exists($db, 'getAll')) {
                $fetchMode = defined('DB_FETCHMODE_ASSOC') ? constant('DB_FETCHMODE_ASSOC') : null;
                $rows = $db->getAll($sql, null, $fetchMode);
                return is_array($rows) ? $rows : [];
            }

            if (method_exists($db, 'query')) {
                $result = $db->query($sql);
                if (is_object($result) && method_exists($result, 'fetchAll')) {
                    return $result->fetchAll(\PDO::FETCH_ASSOC);
                }
                if (is_object($result) && method_exists($result, 'fetchRow')) {
                    $rows = [];
                    $fetchMode = defined('DB_FETCHMODE_ASSOC') ? constant('DB_FETCHMODE_ASSOC') : null;
                    while ($row = $result->fetchRow($fetchMode)) {
                        $rows[] = $row;
                    }
                    return $rows;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    private function scanVoicemailFiles() {
        $files = [];
        if (!is_dir($this->voicemailDir)) {
            return $files;
        }

        foreach (glob($this->voicemailDir . '/*/*/INBOX/msg*.txt') ?: [] as $path) {
            $files[] = [
                'path' => $path,
                'mailbox' => basename(dirname(dirname($path))),
                'context' => basename(dirname(dirname(dirname($path)))),
                'mtime' => filemtime($path) ?: null,
                'size' => filesize($path) ?: 0,
                'metadata' => $this->parseKeyValueFile($path),
            ];
            if (count($files) >= 500) {
                break;
            }
        }

        return $files;
    }

    private function scanFiles($root, $limit, $extensions) {
        $files = [];
        if (!is_dir($root)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (!in_array($file->getExtension(), $extensions, true)) {
                continue;
            }

            $files[] = [
                'path' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'mtime' => $file->getMTime(),
            ];
        }

        usort($files, function ($a, $b) {
            return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
        });

        return array_slice($files, 0, $limit);
    }

    private function parseKeyValueFile($path) {
        $data = [];
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return $data;
        }

        foreach ($lines as $line) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $data[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $data;
    }

    private function countNested($value) {
        if (!is_array($value)) {
            return 0;
        }
        if ($this->isList($value)) {
            return count($value);
        }

        $counts = [];
        foreach ($value as $key => $child) {
            $counts[$key] = is_array($child) ? count($child) : 1;
        }
        return $counts;
    }

    private function isSensitiveKey($key) {
        return (bool) preg_match('/(secret|password|passwd|token|apikey|api_key|auth|oauth|private|cert|key)$/i', (string) $key);
    }

    private function isList($array) {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function quoteIdentifier($identifier) {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function escapeSql($value) {
        return str_replace("'", "''", $value);
    }
}
