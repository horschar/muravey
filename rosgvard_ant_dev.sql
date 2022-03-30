-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Мар 30 2022 г., 12:10
-- Версия сервера: 10.4.22-MariaDB
-- Версия PHP: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `rosgvard_ant_dev`
--

-- --------------------------------------------------------

--
-- Структура таблицы `absent_type`
--

CREATE TABLE `absent_type` (
  `id` int(11) NOT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Варианты длительного освобождения военнослужащих';

--
-- Дамп данных таблицы `absent_type`
--

INSERT INTO `absent_type` (`id`, `title`) VALUES
(1, 'Отпуск'),
(2, 'Больничный'),
(3, 'Военный госпиталь'),
(4, 'Командировка');

-- --------------------------------------------------------

--
-- Структура таблицы `ant_military_serviceload`
--

CREATE TABLE `ant_military_serviceload` (
  `military` int(11) NOT NULL,
  `schedule` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `schedule_data` text COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Служебная нагрузка военнослужащего';

-- --------------------------------------------------------

--
-- Структура таблицы `ant_report_package`
--

CREATE TABLE `ant_report_package` (
  `id` int(11) NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `settings` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Пакеты отчетов';

-- --------------------------------------------------------

--
-- Структура таблицы `ant_serviceload_mask`
--

CREATE TABLE `ant_serviceload_mask` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL COMMENT 'Идентификатор пользователя',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование',
  `data` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'JSON данные о шаблоне',
  `enabled` smallint(1) NOT NULL DEFAULT 1 COMMENT 'Вкл / Выкл шаблон	'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Шаблоны службы';

-- --------------------------------------------------------

--
-- Структура таблицы `ant_serviceload_place`
--

CREATE TABLE `ant_serviceload_place` (
  `id` int(11) NOT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Место несения службы';

-- --------------------------------------------------------

--
-- Структура таблицы `ant_serviceload_type`
--

CREATE TABLE `ant_serviceload_type` (
  `id` int(11) NOT NULL,
  `title` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование',
  `title_short` varchar(8) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование сокращенно',
  `color` varchar(16) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Цвет ячейки',
  `position` tinyint(4) DEFAULT 0 COMMENT 'Позиция по вертикали (0 - первый)',
  `absent` int(11) DEFAULT NULL COMMENT 'Закрепление типа отсутствия',
  `sub_types` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Подтипы'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Тип служебной нагрузки';

--
-- Дамп данных таблицы `ant_serviceload_type`
--

INSERT INTO `ant_serviceload_type` (`id`, `title`, `title_short`, `color`, `position`, `absent`, `sub_types`) VALUES
(1, 'Рабочий день', 'Р', '98d8a0', 0, NULL, NULL),
(2, 'Служба', 'С', '00ad2d', 1, NULL, '{\"1\": \"Наряд\", \"2\": \"Специальная боевая задача\"}'),
(3, 'Командировка', 'К', '777777', 3, 4, NULL),
(4, 'Отпуск', 'О', '777777', 4, 1, NULL),
(5, 'Больничный', 'Б', '777777', 5, 2, NULL),
(6, 'Военный госпиталь', 'Г', '777777', 6, 3, NULL),
(7, 'Выходной', 'В', 'cccccc', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `ant_settings`
--

CREATE TABLE `ant_settings` (
  `id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Глобальные настройки муравья';

--
-- Дамп данных таблицы `ant_settings`
--

INSERT INTO `ant_settings` (`id`, `data`) VALUES
('TIME_OBED', '13'),
('TIME_RABOCHIY_END', '18'),
('TIME_RABOCHIY_START', '8'),
('TIME_RECIEVE_DOC', '9'),
('TIME_UGYN', '19'),
('TIME_ZAVTRAK', '7');

-- --------------------------------------------------------

--
-- Структура таблицы `ant_user`
--

CREATE TABLE `ant_user` (
  `id` int(11) NOT NULL,
  `login` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Логин',
  `pass` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Пароль',
  `military` int(11) DEFAULT NULL COMMENT 'Военнослужащий',
  `access` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Права доступа',
  `settings` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Личные настойки'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Пользователь';

--
-- Дамп данных таблицы `ant_user`
--

INSERT INTO `ant_user` (`id`, `login`, `pass`, `military`, `access`, `settings`) VALUES
(1, 'admin', '6cc42f96742531e7fe92e3a8d4405bd1e20f53837071eb1b34f42613d9b72661', NULL, 'admin', '[]');

-- --------------------------------------------------------

--
-- Структура таблицы `level`
--

CREATE TABLE `level` (
  `id` int(11) NOT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование',
  `title_short` varchar(16) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование сокращенно',
  `position` smallint(6) NOT NULL DEFAULT 0 COMMENT 'Позиционирование по значимости, чем выше показатель - тем выше значимость'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Звание';

--
-- Дамп данных таблицы `level`
--

INSERT INTO `level` (`id`, `title`, `title_short`, `position`) VALUES
(1, 'рядовой', 'ряд.', 0),
(2, 'ефрейтор', 'ефр.', 1),
(3, 'младший сержант', 'мл. с-т.', 2),
(4, 'сержант', 'сер.', 3),
(5, 'старший сержант', 'ст. с-т.', 4),
(6, 'старшина', 'ст-на', 5),
(7, 'прапорщик', 'пр-к', 6),
(8, 'старший прапорщик', 'ст. пр-к', 7),
(9, 'младший лейтенант', 'мл. л-т', 8),
(10, 'лейтенант', 'л-т', 9),
(11, 'старший лейтенант', 'ст. л-т', 10),
(12, 'капитан', 'к-н', 11),
(13, 'майор', 'м-р', 12),
(14, 'подполковник', 'п/п-к', 13),
(15, 'полковник', 'п-к', 14),
(16, 'генерал-майор', 'ген. м-р', 17),
(17, 'генерал-лейтенант', 'ген. л-т', 18),
(18, 'генерал-полковник', 'ген. п-к', 19),
(19, 'генерал армии', 'ген.А', 20);

-- --------------------------------------------------------

--
-- Структура таблицы `military`
--

CREATE TABLE `military` (
  `id` int(11) NOT NULL,
  `fname` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Фамилия',
  `iname` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Имя',
  `oname` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Отчество',
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Описание'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Военнослужащий';

-- --------------------------------------------------------

--
-- Структура таблицы `military_absent`
--

CREATE TABLE `military_absent` (
  `id` int(11) NOT NULL,
  `military` int(11) NOT NULL COMMENT 'Военнослужащий',
  `absent_type` int(11) NOT NULL COMMENT 'Тип отсутствия',
  `date_from` int(11) NOT NULL COMMENT 'Дата начала',
  `date_to` int(11) DEFAULT 0 COMMENT 'Дата окончания'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Длительное освобождение военнослужащих';

-- --------------------------------------------------------

--
-- Структура таблицы `military_level`
--

CREATE TABLE `military_level` (
  `id` int(11) NOT NULL,
  `military` int(11) NOT NULL COMMENT 'Военнослужащий',
  `level` int(11) NOT NULL COMMENT 'Звание',
  `date` int(11) NOT NULL COMMENT 'Дата присвоения'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Звание военнослужащего';

-- --------------------------------------------------------

--
-- Структура таблицы `military_state`
--

CREATE TABLE `military_state` (
  `id` int(11) NOT NULL,
  `military` int(11) NOT NULL COMMENT 'Военнослужащий',
  `state` int(11) NOT NULL COMMENT 'Должность',
  `date_from` int(11) DEFAULT 0 COMMENT 'Занимает с (unix) либо не известно (0)',
  `date_to` int(11) DEFAULT 0 COMMENT 'Занимает по  (unix) либо по настоящий момент (0)',
  `vrio` tinyint(1) DEFAULT 0 COMMENT 'Временно исполняет обязанности (1) либо постоянно (0)',
  `contract` tinyint(1) DEFAULT 1 COMMENT 'Служба по контракту (1) либо строчная (0)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Назначение военнослужащего на должность';

-- --------------------------------------------------------

--
-- Структура таблицы `unit`
--

CREATE TABLE `unit` (
  `id` int(11) NOT NULL,
  `parent` int(11) DEFAULT NULL COMMENT 'Родительское подразделение',
  `title` varchar(264) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Нименование'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Подразделение';

-- --------------------------------------------------------

--
-- Структура таблицы `unit_state`
--

CREATE TABLE `unit_state` (
  `id` int(11) NOT NULL,
  `unit` int(11) NOT NULL COMMENT 'Подразделение',
  `title` varchar(256) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Наименование полное',
  `title_short` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Наименование сокращенно',
  `title_abbreviation` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Наименование аббревиатура',
  `vrio` tinyint(1) DEFAULT 0 COMMENT 'Возможность назначения временно исполняющего обязанности',
  `vrio_title` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Наименование полное для ВРИО',
  `vrio_title_short` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Наименование сокращенно для ВРИО',
  `vrio_abbreviation` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ВРИО аббревиатура'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Штат';

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `absent_type`
--
ALTER TABLE `absent_type`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_military_serviceload`
--
ALTER TABLE `ant_military_serviceload`
  ADD PRIMARY KEY (`military`);

--
-- Индексы таблицы `ant_report_package`
--
ALTER TABLE `ant_report_package`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_serviceload_mask`
--
ALTER TABLE `ant_serviceload_mask`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_serviceload_place`
--
ALTER TABLE `ant_serviceload_place`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_serviceload_type`
--
ALTER TABLE `ant_serviceload_type`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_settings`
--
ALTER TABLE `ant_settings`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ant_user`
--
ALTER TABLE `ant_user`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `level`
--
ALTER TABLE `level`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `military`
--
ALTER TABLE `military`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `military_absent`
--
ALTER TABLE `military_absent`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `military_level`
--
ALTER TABLE `military_level`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `military_state`
--
ALTER TABLE `military_state`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `unit_state`
--
ALTER TABLE `unit_state`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `absent_type`
--
ALTER TABLE `absent_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `ant_report_package`
--
ALTER TABLE `ant_report_package`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `ant_serviceload_mask`
--
ALTER TABLE `ant_serviceload_mask`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `ant_serviceload_place`
--
ALTER TABLE `ant_serviceload_place`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `ant_serviceload_type`
--
ALTER TABLE `ant_serviceload_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `ant_user`
--
ALTER TABLE `ant_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `level`
--
ALTER TABLE `level`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `military`
--
ALTER TABLE `military`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `military_absent`
--
ALTER TABLE `military_absent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `military_level`
--
ALTER TABLE `military_level`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT для таблицы `military_state`
--
ALTER TABLE `military_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT для таблицы `unit`
--
ALTER TABLE `unit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `unit_state`
--
ALTER TABLE `unit_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
