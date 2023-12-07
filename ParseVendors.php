<?php
/**
 * @author Evgeniy Pedan (e@pedan.su)
 */

use GuzzleHttp\Client;

class ParseVendors
{
    private Client $client;
    private static string $baseUrl = 'https://vendors.bitrix24.ru';
    private static string $dailyPaymentsUrl = '/sale/payout.php';
    private static string $appListUrl = '/app/';
    private static string $appClientListUrl = '/sale/clients.php';
    private bool $debug = false;
    private string|null $sessionId = null;
//    private $cookieJar;
    private ?DateTime $dateTime;

    public function __construct(?DateTime $dateTime = null)
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->dateTime = $dateTime;
//        $this->cookieJar = new \GuzzleHttp\Cookie\FileCookieJar('vendors-cookie');
        $config = [
            'base_uri' => static::$baseUrl,
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36',
                'Accept-Language' => 'ru,en-US',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'X-Bitrix-Site-Id' => 'mv'
            ],
//            'cookies' => $this->cookieJar,
            'curl' => [
                CURLOPT_COOKIEJAR => __DIR__ . DIRECTORY_SEPARATOR . 'file.txt',
                CURLOPT_COOKIEFILE => __DIR__ . DIRECTORY_SEPARATOR . 'file.txt',
                CURLOPT_RETURNTRANSFER => 1
            ],
            'debug' => $this->debug,
        ];

        $this->client = new Client($config);
        $this->auth();
    }

    public function auth(): void
    {
        $loginResult = $this->client->request('POST', 'auth/', [
                'form_params' => [
                    'AUTH_FORM' => 'Y',
                    'TYPE' => 'AUTH',
                    'USER_LOGIN' => $_ENV['VENDORS_LOGIN'],
                    'USER_PASSWORD' => $_ENV['VENDORS_PASSWORD'],
                ],
            ]
        );

        $authContent = $loginResult->getBody()->getContents();

        if ($this->debug)
            file_put_contents(__DIR__ . '/log_auth.html', $authContent);

        preg_match_all("/'bitrix_sessid':'(.*)'/", $authContent, $sessIds);
        if (!empty($sessIds) && !empty($sessIds[1])) {
            $this->sessionId = current($sessIds[1]);
        } else {
            throw new \Exception('auth error');
        }
    }

    public function getPayments(): array
    {
        $this->setFilter('mp24_subscription_partner', 'mp24_subscription_partner');
        $params = [
            'query' => [
                'export' => 'Y',
                'sessid' => $this->sessionId,
                'type' => 'payouts',
            ],
        ];
        $queryPayments = $this->client->request('get', static::$dailyPaymentsUrl, $params);
        $queryPaymentsContent = $queryPayments->getBody()->getContents();

        if ($this->debug)
            file_put_contents(__DIR__ . '/queryPaymentsContent.csv', $queryPaymentsContent);

        $headerKey = [];
        $lines = explode(PHP_EOL, $queryPaymentsContent);
        $paymentsList = array();
        foreach ($lines as $k => $line) {
            $data = str_getcsv($line, ';');
            if ($k === 0) {
                $headerKey = $data;
                array_pop($headerKey);
            } elseif (count($data) > 1) {
                $payment = [];
                foreach ($headerKey as $key => $keyName) {
                    $value = $data[$key];

                    if ($keyName === 'AMOUNT' || $keyName === 'ALL_AMOUNT') {
                        $value = (float)str_replace([' ', ','], '.', $value);
                    }

                    if ($keyName === 'DATE_OF_USE') {
                        $value = (new \DateTime($value))->format('Y-m-d');
                    }

                    $payment[$keyName] = $value;
                }
                if (!empty($payment['ID']))
                    $paymentsList[] = $payment;
            }
        }
        return $paymentsList;
    }

    public function getPremiumPayments(): array
    {
        $this->setFilter('mp24_subscription_premium_for_partners', 'mp24_subscription_premium_for_partners');
        $params = [
            'query' => [
                'export' => 'Y',
                'sessid' => $this->sessionId,
                'type' => 'payouts_premium',
            ],
        ];
        $queryPaymentsPremium = $this->client->request('get', static::$dailyPaymentsUrl, $params);
        $queryPaymentsPremiumContent = $queryPaymentsPremium->getBody()->getContents();

        if ($this->debug)
            file_put_contents(__DIR__ . '/queryPaymentsPremiumContent.csv', $queryPaymentsPremiumContent);

        $headerKey = [];
        $lines = explode(PHP_EOL, $queryPaymentsPremiumContent);
        $paymentsPremiumList = array();
        foreach ($lines as $k => $line) {
            $data = str_getcsv($line, ';');
            if ($k === 0) {
                $headerKey = $data;
                array_pop($headerKey);
            } elseif (count($data) > 1) {
                $paymentPremium = [];
                foreach ($headerKey as $key => $keyName) {
                    $value = $data[$key];
                    if ($keyName === 'AMOUNT' || $keyName === 'ALL_AMOUNT') {
                        $value = (float)str_replace(' ', '', $value);
                    }
                    if ($keyName === 'SUBSCRIPTION_START' || $keyName === 'SUBSCRIPTION_END') {
                        $value = (new \DateTime($value))->format('Y-m-d');
                    }
                    $paymentPremium[$keyName] = $value;
                }
                if (!empty($paymentPremium['CLIENT_NAME'])) {
                    $paymentPremium['hash'] = md5(serialize($paymentPremium) . $this->dateTime->format('01-m-Y'));
                    $paymentPremium['DATE_PARSE'] = $this->dateTime->format('Y-m-01');
                    $paymentsPremiumList[] = $paymentPremium;
                }
            }
        }
        return $paymentsPremiumList;
    }

    private function getSessionId()
    {
//        $listQuery = $this->client->get(static::$dailyPaymentsUrl);
        $listQuery = $this->client->request('GET', static::$dailyPaymentsUrl, [
        ]);
        $listQueryHtml = $listQuery->getBody()->getContents();

        if ($this->debug)
            file_put_contents(__DIR__ . '/session.html', $listQueryHtml);

        preg_match_all("/'bitrix_sessid':'(.*)'/", $listQueryHtml, $sessIds);

        if (!empty($sessIds) && !empty($sessIds[1])) {
            return current($sessIds[1]);
        }
        return null;
    }

    private function setFilter($filterId, $gridId): void
    {
        $presetId = 'tmp_filter';
        $params = [
            'params' => [
                'FILTER_ID' => $filterId,
                'GRID_ID' => $gridId,
                'action' => 'setFilter',
                'forAll' => false,
                'commonPresetsId' => '',
                'apply_filter' => 'Y',
                'clear_filter' => 'N',
                'with_preset' => 'N',
                'save' => 'Y',
                'isSetOutside' => 'false',
            ],
            'data' => [
                'fields' => [
                    'FIND' => '',
                    'DATE_OF_USE_from' => '',
                    'DATE_OF_USE_to' => '',
                    'DATE_OF_USE_days' => '',
                    'DATE_OF_USE_quarter' => '',

                    'DATE_OF_USE_datesel' => 'MONTH',
                    'DATE_OF_USE_month' => $this->dateTime->format('m'),
                    'DATE_OF_USE_year' => $this->dateTime->format('Y')
                ],
                'rows' => 'DATE_OF_USE',
                'preset_id' => $presetId,
                'name' => 'Фильтр'
            ]
        ];

        $request = $this->client->request('POST', '/bitrix/services/main/ajax.php', [
                'query' => [
                    'mode' => 'ajax',
                    'c' => 'bitrix:main.ui.filter',
                    'action' => 'setFilter'
                ],
                'form_params' => $params,
                'headers' => [
                    'X-Bitrix-Csrf-Token' => $this->sessionId
                ],
            ]
        );

        if ($this->debug)
            file_put_contents(__DIR__ . '/log_set_filter.html', $request->getBody()->getContents());

        $params = [
            'apply_filter' => 'Y',
            'clear_nav' => 'Y'
        ];

        $request = $this->client->request('POST', '/sale/payout.php', [
                'query' => [
                    'sessid' => $this->sessionId,
                    'internal' => true,
                    'grid_id' => $gridId,
                    'apply_filter' => 'Y',
                    'clear_nav' => 'Y',
                    'grid_action' => 'showpage',
                    'bxajaxid' => '08121894e876869a8373dc61fb5e7f3e',
                ],
                'form_params' => $params,
            ]
        );

        if ($this->debug)
            file_put_contents(__DIR__ . '/log_set_filter2.html', $request->getBody()->getContents());
    }

    public function getAppList(): array
    {
        $result = [];
        $page = 0;
        while (++$page) {
            $params = [
                'query' => [
                    'internal' => 'true',
                    'sessid' => $this->sessionId,
                    'grid_id' => 'vendor_app_list',
                    'grid_action' => 'pagination',
                    'nav-moderator-app-list' => 'page-' . $page,
                    'bxajaxid' => '2eac8fa73aa98e2f7e412d509f9fe12b'
                ],
            ];
            $appListQuery = $this->client->request('get', static::$appListUrl, $params);
            $appListContent = $appListQuery->getBody()->getContents();

//            $appListContent = file_get_contents('./get_app_list_page1.html');
            if ($this->debug)
                file_put_contents(__DIR__ . '/get_app_list_page' . $page . '.html', $appListContent);

            $source = mb_convert_encoding($appListContent, 'HTML-ENTITIES', 'utf-8');

            $tableId = 'vendor_app_list_table';
            $dom = new DOMDocument;

            libxml_use_internal_errors(true);
            $dom->loadHTML($source);
            libxml_use_internal_errors(false);
            $xpath = new DOMXPath($dom);

            $headers = $xpath->query("//table[@id='$tableId']/thead//th");
            $headersData = [];
            foreach ($headers as $index => $header) {
                if (!empty($header->nodeValue)) {
                    $headersData[] = $header->nodeValue;
                }
            }

            $rowsData = $xpath->query("//table[@id='$tableId']/tbody//tr[@class='main-grid-row main-grid-row-body']");

            if ($this->debug) {
                echo 'page:' . $page;
                echo "\n";
                echo 'count:' . count($rowsData);
                echo "\n";
            }

            if (count($rowsData) === 0) {
                break;
            }

            foreach ($rowsData as $row) {
                $rowData = [];
                $cellsData = $xpath->query("td/div/span[@class='main-grid-cell-content']", $row);
                $cellsDataActions = $xpath->query("td/span/a[@class='main-grid-row-action-button']/@data-actions", $row);

                foreach ($cellsDataActions as $cellsDataAction) {
                    $pattern = "/'onclick':'bx24vendorClients\((\d+)\)'/";
                    preg_match($pattern, $cellsDataAction->nodeValue, $matches);
                    $id = null;
                    if (isset($matches[1])) {
                        $id = $matches[1];
                    }
                    $rowData['id'] = $id;
                }

                foreach ($cellsData as $index => $cell) {
                    $rowData[$headersData[$index]] = $cell->nodeValue;
                }
                $result[] = $rowData;
            }
        }
        return $result;
    }

    public function getClientList($appId): array
    {
        $result = [];
        $page = 0;
        while (++$page) {
            $params = [
                'query' => [
                    'ID' => $appId,
                    'nav-client' => 'page-' . $page,
                ],
            ];
            $appListQuery = $this->client->request('get', static::$appClientListUrl, $params);
            $appClientListContent = $appListQuery->getBody()->getContents();

//            $appClientListContent = file_get_contents('./get_app_client_list_page1.html');
            if ($this->debug)
                file_put_contents(__DIR__ . '/get_app_client_list_page' . $page . '.html', $appClientListContent);

            $source = mb_convert_encoding($appClientListContent, 'HTML-ENTITIES', 'utf-8');

            $tableId = 'mp24_client';
            $dom = new DOMDocument;

            libxml_use_internal_errors(true);
            $dom->loadHTML($source);
            libxml_use_internal_errors(false);
            $xpath = new DOMXPath($dom);

            $headersData = $xpath->query("//table[@id='$tableId']/tr[@class='bx-grid-head']//td");
            $headers = [];

            foreach ($headersData as $header) {
                $headerValue = trim($header->nodeValue);
                if (empty($headerValue))
                    $headerValue = 'actions';

                $headers[] = $headerValue;
            }

            $tableData = $xpath->query("//table[@id='$tableId']/tr[not(@class='bx-grid-head') and not(@class='bx-grid-footer')and not(@class='bx-grid-gutter')]");

            if ($this->debug) {
                echo 'page:' . $page;
                echo "\n";
                echo 'count:' . count($tableData);
                echo "\n";
            }

            //todo: [actions] => - Нет данных -, надо доп проверку. но сейчас не существенно
            if (count($tableData) === 1) {
                break;
            }

            foreach ($tableData as $row) {
                $rowData = [];
                $cellsData = $xpath->query("td", $row);
                foreach ($cellsData as $index => $cell) {
                    $rowData[$headers[$index]] = trim($cell->nodeValue);
                }
                $result[] = $rowData;
            }
        }
        return $result;
    }
}