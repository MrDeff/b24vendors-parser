--
-- Структура таблицы `app`
--

CREATE TABLE `app` (
  `id` int(11) NOT NULL,
  `region` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `active` char(1) NOT NULL,
  `version` int(18) NOT NULL,
  `install_cnt` int(18) NOT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `app_clients`
--

CREATE TABLE `app_clients` (
  `id` int(11) NOT NULL,
  `app_code` varchar(255) NOT NULL,
  `app_id` int(18) NOT NULL,
  `date_parse` date NOT NULL,
  `version` int(18) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `client_type` varchar(255) DEFAULT NULL,
  `portal_id` int(18) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `tarif` varchar(255) DEFAULT NULL,
  `partner_id` int(18) DEFAULT NULL,
  `type_sub` varchar(255) DEFAULT NULL,
  `sub_end_date` date DEFAULT NULL,
  `member_id` varchar(255) NOT NULL,
  `setup` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `app_install`
--

CREATE TABLE `app_install` (
  `code` varchar(255) NOT NULL,
  `date_parse` date NOT NULL,
  `install_cnt` int(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
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
  `CURRENCY` varchar(255) NOT NULL,
  `SUBSCRIPTION_START` date DEFAULT NULL,
  `SUBSCRIPTION_END` date DEFAULT NULL,
  `VERSION` int(18) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `payments_premium`
--

CREATE TABLE `payments_premium` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `app_clients`
--
ALTER TABLE `app_clients`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`ID`);

--
-- Индексы таблицы `payments_premium`
--
ALTER TABLE `payments_premium`
  ADD PRIMARY KEY (`hash`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `app_clients`
--
ALTER TABLE `app_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;