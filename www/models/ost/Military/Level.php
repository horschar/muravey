<?php

namespace OsT\Military;

use OsT\Base\System;
use PDO;

/**
 * Звания
 * Class Level
 * @package OsT\Military
 * @version 2022.03.10
 *
 * __construct                      Level constructor
 * getData                          Получить массив данных из БД
 * getIdArrByMilitary               Получить массив идентификаторов записей о званиях военнослужащего
 * getTableItemHtml                 Получить html представление для таблицы управления званиями на странице military_edit
 * parsePostData                    Преобразовать данные из формы редактирования в общий формат
 *
 * last                             Получить идентификатор (id) последней запсии в таблице
 * count                            Получить количество записей в таблице
 * insert                           Добавить забись в БД
 * insertArray                      Добавить массив забисей в БД
 * update                           Обновить запись в БД
 * _update                          Обновить запись в БД
 * delete                           Удалить запись из БД
 * _delete                          Удалить запись из БД
 * _deleteByMilitary                Удалить записи военнослужащего из БД
 * _deleteAll                       Удалить все записи из БД
 *
 */
class Level
{
    const TABLE_NAME = 'military_level';

    public $id;                         // Идентификатор
    public $military;                   // Военнослужащий
    public $level;                      // Звание
    public $date;                       // Дата присвоения

    public $workability = false;        // Работоспособность объекта

    /**
     * Level constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id], [
            'id',
            'military',
            'level',
            'date'
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
     *      id              -   Идентификатор
     *      military        -   Идентификатор военнослужащего
     *      level           -   Идентификатор звания
     *      level_title     -   Наименование звания
     *      level_short     -   Краткое наименование звания
     *      date            -   Дата присвоения звания в формате Unix
     *      date_str        -   Дата присвоения звания в формате 01.01.2022
     *      date_datepicker -   Дата присвоения звания в формате 01/01/2022
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   military_level
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            if (System::chColum('level_title', $colums))
                $levels_data = \OsT\Level::getData(null , [
                    'title',
                    'title_short'
                ]);

            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Идентификатор военнослужащего */
                if (System::chColum('military', $colums))
                    $objectData['military'] = intval($item['military']);

                /* Идентификатор звания */
                if (System::chColum([
                    'level',
                    'level_title',
                    'level_short',
                ], $colums))
                    $objectData['level'] = intval($item['level']);

                /* Наименование звания */
                if (System::chColum('level_title', $colums))
                    $objectData['level_title'] = $levels_data[$objectData['level']]['title'];

                /* Краткое наименование звания */
                if (System::chColum('level_short', $colums))
                    $objectData['level_short'] = $levels_data[$objectData['level']]['title_short'];

                /* Дата присвоения звания в формате Unix */
                if (System::chColum([
                    'date',
                    'date_str',
                    'date_datepicker',
                ], $colums))
                    $objectData['date'] = intval($item['date']);

                /* Дата присвоения звания в формате 01.01.2022 */
                if (System::chColum('date_str', $colums))
                    $objectData['date_str'] = System::parseDate($objectData['date']);

                /* Дата присвоения звания в формате 01/01/2022 */
                if (System::chColum('date_datepicker', $colums))
                    $objectData['date_datepicker'] = System::parseDate($objectData['date'], 'd/m/y');

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
     * Получить массив идентификаторов записей о званиях военнослужащего
     * @param $military
     * @return array
     */
    public static function getIdArrByMilitary ($military)
    {
        global $DB;
        $arr = [];
        $q = $DB->prepare('
            SELECT id
            FROM   military_level
            WHERE  military = ?');
        $q->execute([$military]);

        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {
                $arr[] = intval($item['id']);
            }
        }

        return $arr;
    }

    /**
     * Получить html представление для таблицы управления званиями на странице military_edit
     *
     * @param $data [                           - массив данных о звании
     *                  'level',                - идентификатор звания
     *                  'level_title',          - полное наименование звания
     *                  'date_str',             - дата присвоения в формате 01.01.2001
     *                  'date_datepicker'       - дата присвоения в формате 01/01/2001
     *
     * @return string - html представление для таблицы управления званиями на странице military_edit
     */
    public static function getTableItemHtml ($data)
    {
        return '<tr>
                    <td>' . $data['level_title'] . '</td>
                    <td>' . $data['date_str'] . '</td>
                    <td class="table_td_buttons">
                        <div class="buttonsBox">
                            <div class="button edit" onclick="level_edit_show(this);" title="Изменить"></div>
                            <div class="button delete" onclick="level_delete(this);" title="Удалить"></div>
                        </div>
                        <input class="dtlevel" type="hidden" name="levels_level[]" value="' . $data['level'] . '">
                        <input class="dtdate" type="hidden" name="levels_date[]" value="' . $data['date_datepicker'] . '">
                    </td>
                </tr>';
    }

    /**
     * Преобразовать данные из формы редактирования в общий формат
     * @param $military
     * @param $POST
     * @return array
     */
    public static function parsePostData ($military, $POST) {
        $levels = [];
        if (isset($POST['levels_level'])) {
            foreach ($POST['levels_level'] as $key => $value) {
                if (isset($POST['levels_date'][$key])) {
                    $levels [] = [
                        'level' => intval($value),
                        'date' => System::parseDate($POST['levels_date'][$key], 'unix', 'd/m/y'),
                        'military' => $military
                    ];
                }
            }
        }
        return $levels;
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
     * Добавить массив забисей в БД
     * @param $data
     */
    public static function insertArray ($data) {
        if (count($data))
            foreach ($data as $level)
                self::insert($level);
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
     * Удалить записи военнослужащего из БД
     * @param $military
     * @return bool
     */
    public static function _deleteByMilitary ($military)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['military = ', $military]]);
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