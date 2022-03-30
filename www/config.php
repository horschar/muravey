<?php
    const TIME_ZONE = 2;
    const TIME_LAST_SECOND = 9999999999;

    const DB_HOST = 'localhost';
    const DB_NAME = 'rosgvard_ant_dev';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8';

    const NO_CATCH = true;                                      // Запретить кэширование файлов браузером

    const MYSQL_SERVER = 0;                                     // MySQL Server
    const MYSQL_XAMPP = 1;                                      // XAMPP
    const MYSQL = MYSQL_XAMPP;                                  // Указание инструмента для работы с БД
    const XAMPP_MYSQLDUMP = 'C:\xampp\mysql\bin\mysqldump.exe'; // Путь к инструменту создания бэкапов базы для XAMP
    const MYSQLSERVER_DUMP = 'mysqldump';                       // Путь к инструменту создания бэкапов базы для MYSQL SERVER
    const BACKUP_DB_MAX_FILES_COUNT = 30;                       // Максимальное количество хранимых последних бэкапов базы (0 - все)

    const BACKUP_DB_DIR = '../data/backup/database/';           // Папка для хранения бэкапов базы
    const UPDATES_DIR = '../data/updates/';                     // Папка для хранения обновлений
    const TEMP_DIR = '../data/temp/';                           // Папка для хранения временных файлов

/*
 * Суперпользователь имеет доступ ко всем функциям системы
 * login    admin
 * pass     ant_superuser
 */

/*
 * Стандарты:
 *      ST-0:   Имеются недоработки
 *      ST-1:   Используются актуальные функции, алгоритмы
 *      ST-2:   Имеется описание функций, методов, классов
 *      ST-3:   Выполнено базовое тестирование
 *      ST-4:   Выполнено развернутое тестирование
 *      ST-5:   Проведена оптимизация
 *
 */