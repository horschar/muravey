<?php

namespace OsT\Reports\RNK;

use OsT\Reports\ReportMask;

/**
 * Служит шаблоном для создания отчетов
 * Abstract Class RNK
 * @package OsT\Reports
 * @version 2022.03.10
 *
 * getVersionsClassArray            Получить массив наименований классов версий отчета в формате [key_version] = 'class_name'
 * getVersionsKeys                  Получить массив версий отчета в формате [n] = int version
 * getVersionClassName              Получить имя класса версии отчета по ключу версии
 * getDefaultVersion                Определение версии отчета, которая будет по умолчанию предложена при формировании отчета
 *
 */
abstract class RNK extends ReportMask
{

    const REPORT_TITLE = 'Рапорт на котел';
    const REPORT_KEY = 'rnk';
    const REPORT_CLASS = 'RNK';

    /**
     * Получить массив наименований классов версий отчета в формате [key_version] = 'class_name'
     * @return array [key_version] = 'class_name'
     */
    public static function getVersionsClassArray ()
    {
        return [
            '1' => 'V1',
            '2.0' => 'V2_0',
            '2.1' => 'V2_1',
        ];
    }

    /**
     * Получить массив версий отчета в формате [n] = int version
     * @return array [n] = int version
     */
    public static function getVersionsKeys ()
    {
        $return = [];
        $arr = self::getVersionsClassArray();
        foreach ($arr as $key => $class)
            $return[] = $key;
        return $return;
    }

    /**
     * Получить имя класса версии отчета по ключу версии
     * @param $version - ключ версии
     * @return string
     */
    public static function getVersionClassName ($version)
    {
        $arr = self::getVersionsClassArray();
        if (isset($arr[$version]))
            return $arr[$version];
        return null;
    }

    /**
     * Определение версии отчета, которая будет по умолчанию предложена при формировании отчета
     * @return string - ключ версии
     */
    public static function getDefaultVersion ()
    {
        global $USER;

        if (isset($USER->settings['report'][self::REPORT_KEY]['version']))
            return $USER->settings['report'][self::REPORT_KEY]['version'];
        else {
            $versions = self::getVersionsKeys();
            return end($versions);
        }
    }

}