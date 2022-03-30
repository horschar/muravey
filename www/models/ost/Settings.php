<?php

namespace OsT;

use OsT\Base\System;
use PDO;

/**
 * Настройки системы
 * Class Level
 * @package OsT
 * @version 2022.03.10
 *
 * __construct              Settings constructor
 * getData                  Получить массив данных из БД
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * update                   Обновить запись в БД
 * _update                  Обновить запись в БД
 * delete                   Удалить запись из БД
 * _delete                  Удалить запись из БД
 * _deleteAll               Удалить все записи из БД
 *
 */
class Settings
{
    const TABLE_NAME = 'ant_settings';

    public $id;                         // Ключ
    public $data;                       // Значение

    public $workability = false;        // Работоспособность объекта

    /**
     * Settings constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id]);
        if (count($data)) {
            $this->id = $id;
            $this->data = $data[$id];

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
     */
    public static function getData ($records = null)
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   ant_settings
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item)
                $arr[$item['id']] = $item['data'];
        }
        return $arr;
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