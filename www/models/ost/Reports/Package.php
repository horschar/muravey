<?php
namespace OsT\Reports;

use OsT\Base\System;
use PDO;

/**
 * Пакет отчетов
 * Class Package
 * @package OsT\Reports
 * @version 2022.03.10
 *
 * __construct              Package constructor
 * getData                  Получить массив данных из БД
 * getArrayByUser           Получить идентификаторы пакетов пользователя $user
 * getHtmlTableItem         Сформировать HTML представление пакета отчетов для таблицы пакетов отчетов
 * calcData                 Вычислить новые даты для $this->data исходя из здвига относительно даты $date
 * getHtmlPrintTableItems   Сформировать HTML представление отчетов пакета для таблицы очереди печати
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * update                   Обновить запись в БД
 * _update                  Обновить запись в БД
 * delete                   Удалить запись из БД
 * _delete                  Удалить запись из БД
 * _deleteByUser            Удалить пакеты пользователя
 * _deleteAll               Удалить все записи из БД
 *
 */
class Package
{
    const TABLE_NAME = 'ant_report_package';

    public $id;                         // Идентификатор
    public $title;                      // Наименование
    public $user;                       // Владелец
    public $data;                       // Набор параметров формирования отчетов
    public $settings;                   // Настройки отчетов

    public $workability = false;        // Работоспособность объекта

    /**
     * Package constructor.
     * @param $id
     */
    public function __construct ($id)
    {
        $data = self::getData([$id], [
            'id',
            'title',
            'user',
            'data',
            'settings',
        ]);
        if (count($data)) {
            $data = $data[$id];
            foreach ($data as $key => $value)
                $this->$key = $value;
            $this->workability = true;
        }
    }

    /**
     * Получить массив данных из БД
     *
     * @param array $records - массив идентификаторов запрашиваемых записей
     * @example [1, 4, 6, ...]  - определенные записи
     *          null            - все записи
     *
     * @param array $colums - массив атрибутов запрашиваемых данных
     * @example ['id', 'title', 'count', ...]   - определенные идентификаторы
     *          []                              - набор данных по умолчанию
     *
     * @return array массив данных запрашиваемых записей
     * @example [ 0 => ['id' => 1, 'title' => 'default', ...], ...]
     *          где 0 - идентификатор записи в БД
     *
     *      id          -   Идентификатор пакета
     *      title       -   Наименование пакета
     *      user        -   Идентификатор владельца
     *      data        -   Набор параметров формирования отчетов
     *      settings    -   Настройки отчетов
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   ant_report_package
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор пакета */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Наименование пакета */
                if (System::chColum('title', $colums))
                    $objectData['title'] = stripslashes($item['title']);

                /* Идентификатор владельца */
                if (System::chColum('user', $colums))
                    $objectData['user'] = intval($item['user']);

                /* Набор параметров формирования отчетов */
                if (System::chColum('data', $colums))
                    $objectData['data'] = json_decode($item['data'], true);

                /* Настройки отчетов */
                if (System::chColum('settings', $colums))
                    $objectData['settings'] = json_decode($item['settings'], true);

                // Удаление промежуточных данных
                foreach ($objectData as $okey => $oval)
                    if (!in_array($okey, $colums))
                        unset($objectData[$okey]);

                // Добавление данных в конечный массив
                $arr[intval($item['id'])] = $objectData;
            }
        }
        return $arr;
    }

    /**
     * Получить идентификаторы пакетов пользователя $user
     * @param $user - идентификатор пользователя
     * @return array - массив идентификаторов пакетов
     */
    public static function getArrayByUser ($user)
    {
        global $DB;
        $arr = [];
        $q = $DB->prepare('
                SELECT     id
                FROM       ant_report_package
                WHERE      user = ?
        ');
        $q->execute([$user]);
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $item)
                $arr[intval($item['id'])] = intval($item['id']);
        }

        return $arr;
    }

    /**
     * Сформировать HTML представление пакета отчетов для таблицы пакетов отчетов
     * страница reports
     * @param $id - идентификатор пакета
     * @param $title - название пакета
     * @return string - HTML представление пакета отчетов
     */
    public static function getHtmlTableItem ($id, $title)
    {
        return '<tr class="package_table_item" id="package_table_item_' . $id . '">
                    <td class="reportTitle">' . $title . '</td>
                    <td class="reportButtons">
                        <div class="button delete" title="Настройки отчета" onclick="packageDelete(' . $id . ')"></div>
                        <div class="button print" title="Добавить в очередь печати" onclick="packageShowPrintWindow(' . $id . ')"></div>
                    </td>
                </tr>';
    }

    /**
     * Вычислить новые даты для $this->data исходя из здвига относительно даты $date
     * @param $date - дата в формате Unix
     */
    public function calcData ($date)
    {
        $reports_data = $this->data;

        // Определение минимальной date
        $min_date = TIME_LAST_SECOND;
        foreach ($reports_data as $index => $report) {
            if (isset($report['date'])) {
                $reports_data[$index]['date'] = intval($report['date']);
                if ($reports_data[$index]['date'] < $min_date)
                    $min_date = $reports_data[$index]['date'];
            }
            if (isset($report['create']))
                $reports_data[$index]['create'] = intval($report['create']);
        }
        if ($min_date === TIME_LAST_SECOND)
            $min_date = $date;

        // Вычисление сдвига дат
        foreach ($reports_data as $index => $report) {
            if (isset($report['date'])) {
                $tmp = $report['date'] - $min_date;
                if ($tmp !== 0)
                    $tmp = intval($tmp / System::TIME_DAY);
                $reports_data[$index]['date_shift'] = $tmp;
            }

            if (isset($report['create'])) {
                $tmp = $report['create'] - $min_date;
                if ($tmp !== 0)
                    $tmp = intval($tmp / System::TIME_DAY);
                $reports_data[$index]['create_shift'] = $tmp;
            }
        }

        // Преобразование дат
        foreach ($reports_data as $index => $report) {
            if (isset($report['date'])) {
                $reports_data[$index]['date_new'] = $date + ($report['date_shift'] * System::TIME_DAY);
                if (System::gettimeBeginDayFromTime($reports_data[$index]['date_new']) !== $reports_data[$index]['date_new'])
                    $reports_data[$index]['date_new'] = System::gettimeBeginDayFromTimeSmart($reports_data[$index]['date_new']);
            }

            if (isset($report['create'])) {
                $reports_data[$index]['create_new'] = $date + ($report['create_shift'] * System::TIME_DAY);
                if (System::gettimeBeginDayFromTime($reports_data[$index]['create_new']) !== $reports_data[$index]['create_new'])
                    $reports_data[$index]['create_new'] = System::gettimeBeginDayFromTimeSmart($reports_data[$index]['create_new']);
            }
        }

        // Присвоение данных экземпляру объекта
        foreach ($reports_data as $index => $report) {
            if (isset($report['date']))
                $this->data[$index]['date'] = $report['date_new'];
            if (isset($report['create']))
                $this->data[$index]['create'] = $report['create_new'];
        }

    }

    /**
     * Сформировать HTML представление отчетов пакета для таблицы очереди печати
     * @return string
     */
    public function getHtmlPrintTableItems ()
    {
        $html = '';
        foreach ($this->data as $report) {
            $report['package'] = $this->id;
            $version_class = Report::constructClassName($report['key'], $report['version']);
            $title = $version_class::REPORT_TITLE;
            $html .= Report::getHtmlPrintTableItemSingle($title, $report);
        }
        return $html;
    }

    /**
     * Получить идентификатор (id) последней запсии в таблице
     * @return int
     */
    public static function last ()
    {
        global $DB;
        $q = $DB->prepare('
                SELECT     MAX(id)
                FROM       ' . self::TABLE_NAME);
        $q->execute();
        $id = $q->fetch(PDO::FETCH_NUM);
        return intval(end($id));
    }

    /**
     * Получить количество записей в таблице
     * @return int
     */
    public static function count ()
    {
        global $DB;
        $q = $DB->prepare('
                SELECT     COUNT(id)
                FROM       ' . self::TABLE_NAME);
        $q->execute();
        $id = $q->fetch(PDO::FETCH_NUM);
        return intval(end($id));
    }

    /**
     * Добавить забись в БД
     * @param $data
     * @return bool
     */
    public static function insert ($data)
    {
        global $DB;
        return $DB->_insert(self::TABLE_NAME, $data);
    }

    /**
     * Обновить запись в БД
     * @param $data
     * @return bool
     */
    public function update ($data)
    {
        return self::_update($this->id, $data);
    }

    /**
     * Обновить запись в БД
     * @param $id
     * @param $data
     * @return bool
     */
    public static function _update ($id, $data)
    {
        global $DB;
        return $DB->_update(
            self::TABLE_NAME,
            $data,
            [
                ['id = ', $id]
            ]);
    }

    /**
     * Удалить запись из БД
     * @return bool
     */
    public function delete ()
    {
        return self::_delete($this->id);
    }

    /**
     * Удалить запись из БД
     * @param $id
     * @return bool
     */
    public static function _delete ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['id = ', $id]]);
        return true;
    }

    /**
     * Удалить пакеты пользователя
     * @param $id - идентификатор пользователя
     * @return bool
     */
    public static function _deleteByUser ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['user = ', $id]]);
        return true;
    }

    /**
     * Удалить все записи из БД
     * @return bool
     */
    public static function _deleteAll ()
    {
        global $DB;
        $q = $DB->prepare('DELETE FROM ' . self::TABLE_NAME);
        $q->execute();
        return true;
    }

}