<?php
/**
 * @author Evgeniy Pedan (e@pedan.su)
 */

use YdbPlatform\Ydb\Auth\Implement\JwtWithJsonAuthentication;
use YdbPlatform\Ydb\Session;
use YdbPlatform\Ydb\Ydb;
use YdbPlatform\Ydb\YdbTable;

class YdbBase
{
    /**
     * @throws \YdbPlatform\Ydb\Exception
     */
    private Ydb $ydb;

    public function __construct()
    {
        $config = [
            'database' => '/ru-central1/b1...../etn3v.....',
            'endpoint' => 'ydb.serverless.yandexcloud.net:2135',
            'discovery' => false,
            // IAM config
            'iam_config' => [
                'temp_dir' => './tmp'
                // 'root_cert_file' => './CA.pem', // Root CA file (uncomment for dedicated server)
            ],

            'credentials' => new JwtWithJsonAuthentication('./auth.json')
        ];

        $this->ydb = new Ydb($config);
        $this->createTabels();
    }

    protected function createTabels()
    {
        $this->ydb->table()->retrySession(function (Session $session) {

            $session->createTable(
                'payments',
                YdbTable::make()
                    ->addColumn('ID', 'Int64')
                    ->addColumn('DATE_OF_USE', 'UTF8')
                    ->addColumn('APP_CODE', 'UTF8')
                    ->addColumn('CLIENT_NAME', 'UTF8')
                    ->addColumn('PARTNER', 'UTF8')
                    ->addColumn('SUBSCRIPTION_ID', 'UTF8')
                    ->addColumn('SUBSCRIPTION_TYPE', 'UTF8')
                    ->addColumn('MEMBER_ID', 'UTF8')
                    ->addColumn('MODE_OF_USE', 'UTF8')
                    ->addColumn('MODE_OF_USE_POINTS', 'UTF8')
                    ->addColumn('APP_TYPE', 'UTF8')
                    ->addColumn('APP_TYPE_POINTS', 'UTF8')
                    ->addColumn('POINTS', 'UTF8')
                    ->addColumn('ALL_POINTS', 'UTF8')
                    ->addColumn('AMOUNT', 'UTF8')
                    ->addColumn('ALL_AMOUNT', 'UTF8')
                    ->addColumn('CURRENCY', 'UTF8')
                    ->primaryKey('ID')
            );

        }, true);
        $this->ydb->table()->retrySession(function (Session $session) {
            $session->createTable(
                'payments_premium',
                YdbTable::make()
                    ->addColumn('DATE_PARSE', 'UTF8')
                    ->addColumn('CLIENT_NAME', 'UTF8')
                    ->addColumn('MEMBER_ID', 'UTF8')
                    ->addColumn('TARIFF', 'UTF8')
                    ->addColumn('PARTNER', 'UTF8')
                    ->addColumn('TYPE', 'UTF8')
                    ->addColumn('APP_CODE', 'UTF8')
                    ->addColumn('APP_REMOVED', 'UTF8')
                    ->addColumn('POINTS', 'UTF8')
                    ->addColumn('ALL_POINTS', 'UTF8')
                    ->addColumn('AMOUNT', 'UTF8')
                    ->addColumn('ALL_AMOUNT', 'UTF8')
                    ->addColumn('CURRENCY', 'UTF8')
                    ->addColumn('SUBSCRIPTION_START', 'UTF8')
                    ->addColumn('SUBSCRIPTION_END', 'UTF8')
                    ->addColumn('SUBSCRIPTION_ID', 'UTF8')
                    ->addColumn('hash', 'UTF8')
                    ->primaryKey('hash')
            );

        }, true);

        print('Table `series` has been created.');
    }

    public function upsert($table, $fields): void
    {
        foreach ($fields as $key => &$value) {
            if ($key === 'ID')
                $value = (int)$value;
            else {
                $value = '"' . str_replace('"', '', $value) . '"';
            }
        }
        $this->ydb->table()->retryTransaction(function (Session $session) use ($table, $fields) {
            $session->query('
            UPSERT INTO ' . $table . ' (' . implode(',', array_keys($fields)) . ')
            VALUES (' . implode(',', array_values($fields)) . ');');
        }, true);

//        print('Finished.');
    }
}