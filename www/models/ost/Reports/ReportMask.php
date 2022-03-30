<?php

namespace OsT\Reports;

/**
 * Служит шаблоном для создания отчетов
 * Abstract Class ReportMask
 * @package OsT\Reports
 * @version 2022.03.10
 *
 * getVersionsClassArray            Получить массив наименований классов версий отчета в формате [key_version] = 'class_name'
 * getVersionsKeys                  Получить массив версий отчета в формате [n] = int version
 * getVersionClassName              Получить имя класса версии отчета по ключу версии
 * getDefaultVersion                Определение версии отчета, которая будет по умолчанию предложена при формировании отчета
 *
 * __construct                      Обработать массив параметров отчета $data
 * getSettings                      Получить массив настроек отчета
 * getDefaultSettings               Получить массив настроек отчета по-умолчанию
 * savePrintSettings                Сохранить настройки отчета в базе при добавлении в очередь печати
 * saveSettings                     Сохранить настройки из report_settings.php
 * getHtmlSettingsForm              Получить HTML представление элементов формы настроек отчета
 * getHeadMaskIndexes               Получить массив индексов доступных для отчета шапок
 * getSenderMaskIndexes             Получить массив индексов доступных для отчета типов указания отправителя
 * getSenderSourceIndexes           Получить массив индексов доступных для отчета типов источников данных отправителя
 * generate                         Сформировать HTML отчет версии
 * getHtmlPrintSettingsBox          Сгенерировать блок настроек при добавлении отчета в лист печати
 * calcPrintTableItemData           Обработка данных из блока настроек отчета при добавлении в лист печати
 *
 */
abstract class ReportMask
{

    /**
     * Получить массив наименований классов версий отчета в формате [key_version] = 'class_name'
     * @return array [key_version] = 'class_name'
     */
    abstract public static function getVersionsClassArray ();

    /**
     * Получить массив версий отчета в формате [n] = int version
     * @return array [n] = int version
     */
    abstract public static function getVersionsKeys ();

    /**
     * Получить имя класса версии отчета по ключу версии
     * @param $version - ключ версии
     * @return string
     */
    abstract public static function getVersionClassName ($version);

    /**
     * Определение версии отчета, которая будет по умолчанию предложена при формировании отчета
     * @return string - ключ версии
     */
    abstract public static function getDefaultVersion ();

    /**
     * Обработать массив параметров отчета $data
     * RSN constructor.
     * @param $data
     */
    abstract public function __construct ($data);

    /**
     * Получить массив настроек отчета
     * @return array
     */
    abstract public static function getSettings ();

    /**
     * Получить массив настроек отчета по-умолчанию
     * @return array
     */
    abstract public static function getDefaultSettings ();

    /**
     * Сохранить настройки отчета в базе при добавлении в очередь печати
     * @param $data - POST данные из V::getHtmlPrintSettingsBox
     */
    abstract public static function savePrintSettings ($data);

    /**
     * Сохранить настройки из report_settings.php
     * @param $data - массив данных формы $_POST
     */
    abstract public static function saveSettings ($data);

    /**
     * Получить HTML представление элементов формы настроек отчета
     * Страница report_settings
     * @return string
     */
    abstract public static function getHtmlSettingsForm ();

    /**
     * Получить массив индексов доступных для отчета шапок
     * @return int[]
     */
    abstract public static function getHeadMaskIndexes ();

    /**
     * Получить массив индексов доступных для отчета шаблонов указания отправителя
     * @return int[]
     */
    abstract public static function getSenderMaskIndexes ();

    /**
     * Получить массив индексов доступных для отчета типов источников данных отправителя
     * 0 - без отправителя
     * 1 - произвольный текст
     * 2 - военнослужащий
     * 3 - текущий военнослужащий на выбранной должности
     * @return int[]
     */
    abstract public static function getSenderSourceIndexes ();

    /**
     * Сформировать HTML отчет версии
     */
    abstract public function generate ();

    /**
     * Сгенерировать блок настроек при добавлении отчета в лист печати
     * @return string - html представление
     */
    abstract public static function getHtmlPrintSettingsBox ();

    /**
     * Обработка данных из блока настроек отчета при добавлении в лист печати
     * @param $data - настройки отчета при добавлении в лист печати из блока настроек
     * @return array - обработанные данные
     */
    abstract public static function calcPrintTableItemData ($data);

}