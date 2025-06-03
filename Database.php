<?php

class Database
{
    /**
     * @throws \YdbPlatform\Ydb\Exception
     */
    private MysqliDb $db;

    public function __construct()
    {
        $this->db = new MysqliDb ($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->db->disconnectAll();
    }

    public function clearPayments($date): void
    {
        $db = $this->db;
        $db->where('DATE_OF_USE', $date, '>=');
        $db->delete('payments');
    }

    public function clearPremiumPayments($date): void
    {
        $db = $this->db;
        $db->where('DATE_PARSE', $date, '>=');
        $db->delete('payments_premium');
    }

    public function clearAppClients($appCode, $date): void
    {
        $db = $this->db;
        $db->where('app_code', $appCode);
        $db->where('date_parse', $date, '>=');
        $db->delete('app_clients');
    }

    public function upsert($table, $fields): void
    {
        $db = $this->db;
        if ($table == 'payments') {
            $db->where('ID', $fields['ID']);
        }

        if ($table == 'payments_premium') {
            $db->where('hash', $fields['hash']);
        }

        if ($table == 'app') {
            $db->where('code', $fields['code']);
        }

        $data = $this->db->get($table);
        if (!$data) {
            $this->db->insert($table, $fields);
        }
    }

    public function upsertApp($fields): void
    {
        $db = $this->db;
        $db->where('code', $fields['code']);
        $db->where('region', $fields['region']);

        $data = $db->get('app');

        if (!$data) {
            $db->insert('app', $fields);
        } else {
            $db->where('code', $fields['code']);
            $db->where('region', $fields['region']);
            $db->update('app', $fields);
        }

        $dbSearch = $dbInsert = $this->db;
        $db->where('code', $fields['code']);
        $data = $dbSearch->get('app_install');

        $installData = ['code' => $fields['code'], 'install_cnt' => $fields['install_cnt'] ?? 0, 'date_parse' => (new DateTime(date('d.m.Y')))->format('Y-m-d')];
        if (!$data) {
            $dbInsert->insert('app_install', $installData);
        } else {
            $dbInsert->where('code', $fields['code']);
            $dbInsert->update('app_install', $installData);
        }
    }

    public function upsertAppClient($appCode, $appId, $fields): void
    {
        if (!isset($fields['member_id']) || empty($fields['member_id'])) {
            if (!isset($fields['portal_id'])) {
                $fields['member_id'] = $fields['domain'];
            } else {
                $fields['member_id'] = $fields['portal_id'];
            }
        }

        $db = $this->db;
        $db->where('app_code', $appCode);
        $db->where('app_id', $appId);
        $db->where('date_parse', $fields['date_parse']);
        $db->where('member_id', $fields['member_id']);

        $data = $db->get('app_clients');

        if (!$data) {
            $db->insert('app_clients', $fields);
        } else {
            $db->where('app_code', $appCode);
            $db->where('app_id', $appId);
            $db->where('member_id', $fields['member_id']);
            $db->update('app_clients', $fields);
        }
    }
}