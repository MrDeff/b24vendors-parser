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

    public function upsert($table, $fields): void
    {
        $db = $this->db;
        if ($table == 'payments') {
            $db->where('ID', $fields['ID']);
        }

        if ($table == 'payments_premium') {
            $db->where('hash', $fields['hash']);
        }
        $data = $this->db->get($table);
        if (!$data) {
            $this->db->insert($table, $fields);
        }
    }
}


/*
--
-- Table structure for table `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
`ID` int(11) NOT NULL,
  `DATE_OF_USE` date NOT NULL,
  `APP_CODE` varchar(255) NOT NULL,
  `CLIENT_NAME` varchar(255) NOT NULL,
  `PARTNER` int(11) NOT NULL,
  `SUBSCRIPTION_ID` int(11) NOT NULL,
  `SUBSCRIPTION_TYPE` varchar(255) NOT NULL,
  `MEMBER_ID` varchar(255) NOT NULL,
  `MODE_OF_USE` varchar(255) NOT NULL,
  `MODE_OF_USE_POINTS` int(11) NOT NULL,
  `APP_TYPE` int(11) NOT NULL,
  `APP_TYPE_POINTS` int(11) NOT NULL,
  `POINTS` int(11) NOT NULL,
  `ALL_POINTS` int(11) NOT NULL,
  `AMOUNT` double NOT NULL,
  `ALL_AMOUNT` double NOT NULL,
  `CURRENCY` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `payments_premium`
--

CREATE TABLE IF NOT EXISTS `payments_premium` (
`DATE_PARSE` date NOT NULL,
  `CLIENT_NAME` varchar(255) NOT NULL,
  `MEMBER_ID` varchar(255) NOT NULL,
  `TARIFF` varchar(255) NOT NULL,
  `PARTNER` int(11) NOT NULL,
  `TYPE` varchar(255) NOT NULL,
  `APP_CODE` varchar(255) NOT NULL,
  `APP_REMOVED` char(1) NOT NULL,
  `POINTS` int(11) NOT NULL,
  `ALL_POINTS` int(11) NOT NULL,
  `AMOUNT` double NOT NULL,
  `ALL_AMOUNT` double NOT NULL,
  `CURRENCY` int(11) NOT NULL,
  `SUBSCRIPTION_START` date NOT NULL,
  `SUBSCRIPTION_END` date NOT NULL,
  `SUBSCRIPTION_ID` int(11) NOT NULL,
  `hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `payments` ADD PRIMARY KEY (`ID`);

ALTER TABLE `payments_premium` ADD PRIMARY KEY (`hash`);

*/