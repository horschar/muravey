<?php

namespace OsT\Serviceload;

use OsT\Base\System;
use PDO;

/**
 * Место несения службы
 * Class Place
 * @package OsT\Serviceload
 * @version 2022.03.11
 *
 * __construct              Place constructor
 * getData                  Получить массив данных из БД
 * getIdByTitle             Получить идентификатор места по его названию
 * getDependencies          Поиск зависимостей мест службы путем проверки их использования в системе
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * update                   Обновить запись в БД
 * _update                  Обновить запись в БД
 * delete                   Удалить запись из БД
 * _delete                  Удалить запись из БД
 * _deleteByTitle           Удалить запись с наименованием title
 * _deleteUnused            Удалить записи мест службы, которые нигде не используются
 * _deleteAll               Удалить все записи из БД
 *
 */
class Place
{
    const TABLE_NAME = 'ant_serviceload_place';

    public $id;
    public $title;

    public $workability = false;

    /**
     * Place constructor.
     * @param $id - идентификатор места
     */
    public function __construct ( $id )
    {
        $data = self::getData([$id], [
            'id',
            'title'
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
     * @param array $colums - массив атрибутов запрашиваемых данных
     * @return array массив данных запрашиваемых записей
     * @example ['id', 'title', 'count', ...]   - определенные идентификаторы
     *          []                              - набор данных по умолчанию
     *
     * @example [1, 4, 6, ...]  - определенные записи
     *          null            - все записи
     *
     * @example [ 0 => ['id' => 1, 'title' => 'default', ...], ...]
     *          где 0 - идентификатор записи в БД
     *
     *      id          -   Идентификатор места
     *      title       -   Наименование
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   ant_serviceload_place
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор места */
                $objectData['id'] = intval($item['id']);

                /* Наименование */
                if (System::chColum('title', $colums))
                    $objectData['title'] = stripslashes($item['title']);

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
     * Получить идентификатор места по его названию
     * @param $title - название
     * @param bool $insert - добавить в базу если не найден
     * @return bool|int - идентификатор места
     */
    public static function getIdByTitle ($title, $insert = false)
    {
        global $DB;
        $q = $DB->prepare('
            SELECT  id
            FROM    ant_serviceload_place
            WHERE   title = ?'
        );
        $q->execute([$title]);
        if ($q->rowCount()) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            return intval($data['id']);

        } else if ($insert) {
            self::insert(['title' => $title]);
            return self::last();

        } else return false;
    }

    /**
     * Поиск зависимостей мест службы путем проверки их использования в системе
     * @param $places - массив идентификаторов мест службы
     * @return array - массив количества использований
     *              count_schedule  - в графике нагрузки
     *              count_mask      - в шаблонах службы
     */
    public static function getDependencies ($places)
    {
        global $DB;

        $return = [];
        foreach ($places as $place)
            $return[$place] = [
                'count_schedule' => 0,
                'count_mask' => 0,
            ];

        // Поиск по использованым данным в графике
        $q = $DB->query('
                SELECT      schedule_data
                FROM        ant_military_serviceload');
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $item) {
                $schedule_data = json_decode($item['schedule_data'], true);
                if (is_array($schedule_data)) {
                    if (count($schedule_data)) {
                        foreach ($schedule_data as $year => $ydata)
                            foreach ($ydata as $month => $mdata)
                                foreach ($mdata as $day => $ddata)
                                    if (isset($ddata['place']))
                                        if (isset($return[intval($ddata['place'])]))
                                            $return[intval($ddata['place'])]['count_schedule']++;
                    }
                }
            }
        }

        // Поиск по использованию в шаблонах
        $masks = Mask::getData(null, ['data']);
        if (count($masks)) {
            foreach ($masks as $mask) {
                if (isset($return[$mask['data']['place']]))
                    $return[$mask['data']['place']]['count_mask']++;
            }
        }

        return $return;
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
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что запись нигде не используется с помощью функции getDependencies
     * В противном случае возникнут проблемы в работе системы
     *
     * @return bool
     */
    public function delete ()
    {
        return self::_delete($this->id);
    }

    /**
     * Удалить запись из БД
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что запись нигде не используется с помощью функции getDependencies
     * В противном случае возникнут проблемы в работе системы
     *
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
     * Удалить запись с наименованием title
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что запись нигде не используется с помощью функции getDependencies
     * В противном случае возникнут проблемы в работе системы
     *
     * @param $title
     * @return bool
     */
    public static function _deleteByTitle ($title)
    {
        global $DB;
        $title = addslashes($title);
        return $DB->_delete(self::TABLE_NAME, [['title = ', $title]]);
    }

    /**
     * Удалить записи мест службы, которые нигде не используются
     */
    public static function _deleteUnused ()
    {
        global $DB;
        $places = self::getData(null, ['id']);
        if (count($places)) {
            foreach ($places as $key => $place)
                $places[$key] = $key;

            $places = self::getDependencies($places);

            // Удаление
            foreach ($places as $key => $place)
                if ($place['count_schedule'] === 0 && $place['count_mask'] === 0)
                    self::_delete($key);

        }
    }

    /**
     * Удалить все записи из БД
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
     * Перед удалением убедитесь, что места несения службы нигде не используются
     * В противном случае возникнут проблемы в работе системы
     *
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