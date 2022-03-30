<?php
namespace OsT\Serviceload;

use OsT\Base\System;
use PDO;

/**
 * Типы служебной нагрузки
 * Class Type
 * @package OsT\Serviceload
 * @version 2022.03.11
 *
 * getData                  Получить массив данных из БД
 * getSubtypeIdByTitle      Получить идентификатор типа службы по его названию
 * getSubtypeDependencies   Поиск зависимостей подтипов службы путем проверки их использования в системе
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * _update                  Обновить запись в БД
 * _delete                  Удалить запись из БД
 * _deleteSubtype           Удалить подтип службы
 * _deleteUnusedSubtype     Удалить записи подтипов службы, которые нигде не используются
 * _deleteSubtypeByTitle    Удалить подтип службы по наименованию title
 * _deleteSubtypeAll        Удалить все подтипы службы
 * _deleteAll               Удалить все записи из БД
 *
 */
class Type
{
    const RABOCHUI = 1;
    const NARYAD = 2;
    const KOMANDIROVKA = 3;
    const OTPUSK = 4;
    const BOLNICHNUI = 5;
    const VOENNUIGOSPITAL = 6;
    const VUHODNOI = 7;

    const TABLE_NAME = 'ant_serviceload_type';

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
     *      id          -   Идентификатор типа
     *      title       -   Наименование
     *      title_short -   Наименование краткое
     *      color       -   Цвет
     *      position    -   Позиция
     *      absent      -   Закрепление типа отсутствия
     *      sub_types   -   Подтипы
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;

        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   ant_serviceload_type
            ' . $in_arr_sql
            . ' ORDER BY position');
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор типа */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Наименование */
                if (System::chColum('title', $colums))
                    $objectData['title'] = stripslashes($item['title']);

                /* Наименование краткое */
                if (System::chColum('title_short', $colums))
                    $objectData['title_short'] = stripslashes($item['title_short']);

                /* Цвет */
                if (System::chColum('color', $colums))
                    $objectData['color'] = $item['color'];

                /* Позиция */
                if (System::chColum('position', $colums))
                    $objectData['position'] = intval($item['position']);

                /* Закрепление типа отсутствия */
                if (System::chColum('absent', $colums))
                    $objectData['absent'] = intval($item['absent']);

                /* Подтипы */
                if (System::chColum('sub_types', $colums))
                    $objectData['sub_types'] = json_decode($item['sub_types'], true);

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
     * Получить идентификатор типа службы по его названию
     * @param $title - название
     * @param bool $insert - добавить в базу если не найден
     * @return bool|int - идентификатор типа
     */
    public static function getSubtypeIdByTitle ($title, $insert = false)
    {
        $serviceload_types = self::getData([self::NARYAD], ['sub_types']);
        $sub_types = $serviceload_types[self::NARYAD]['sub_types'];

        $id = null;
        if (count($sub_types)) {
            foreach ($sub_types as $key => $value)
                if ($value === $title)
                    $id = $key;
        }

        if ($id !== null) {
            return $id;

        } else if ($insert) {
            $sub_types[] = $title;
            self::_update(
                self::NARYAD,
                ['sub_types' => json_encode($sub_types)]);
            foreach ($sub_types as $key => $value)
                if ($value === $title)
                    return $key;
        }

        return false;
    }

    /**
     * Поиск зависимостей подтипов службы путем проверки их использования в системе
     * @param $subtypes - массив идентификаторов подтипов
     * @return array - массив количества использований
     *              count_schedule  - в графике нагрузки
     *              count_mask      - в шаблонах службы
     */
    public static function getSubtypeDependencies ($subtypes)
    {
        global $DB;

        $return = [];
        foreach ($subtypes as $subtype)
            $return[$subtype] = [
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
                                    if (isset($ddata['type']))
                                        if (isset($return[intval($ddata['type'])]))
                                            $return[intval($ddata['type'])]['count_schedule']++;
                    }
                }
            }
        }

        // Поиск по использованию в шаблонах
        $masks = Mask::getData(null, ['data']);
        if (count($masks)) {
            foreach ($masks as $mask) {
                if (isset($return[$mask['data']['type']]))
                    $return[$mask['data']['type']]['count_mask']++;
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
     * Применять только в крайнем случае
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
     * Удалить подтип службы
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что запись нигде не используется с помощью функции getSubtypeDependencies
     * В противном случае возникнут проблемы в работе системы
     *
     * @param $id
     */
    public static function _deleteSubtype ($id)
    {
        $serviceload_types = self::getData([self::NARYAD], ['sub_types']);
        $sub_types = $serviceload_types[self::NARYAD]['sub_types'];
        unset($sub_types[$id]);
        self::_update(
            self::NARYAD,
            ['sub_types' => json_encode($sub_types)]
        );
    }

    /**
     * Удалить записи подтипов службы, которые нигде не используются
     */
    public static function _deleteUnusedSubtype ()
    {
        $serviceload_types = self::getData([self::NARYAD], ['sub_types']);
        $sub_types = $serviceload_types[self::NARYAD]['sub_types'];
        if (count($sub_types)) {
            $arr = [];
            foreach ($sub_types as $key => $val)
                $arr[intval($key)] = intval($key);

            $arr = self::getSubtypeDependencies($arr);

            // Удаление
            foreach ($arr as $key => $subtype)
                if ($subtype['count_schedule'] === 0 && $subtype['count_mask'] === 0)
                    unset($sub_types[$key]);

            self::_update(
                self::NARYAD,
                ['sub_types' => json_encode($sub_types)]
            );
        }
    }

    /**
     * Удалить подтип службы по наименованию title
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что запись нигде не используется с помощью функции getSubtypeDependencies
     * В противном случае возникнут проблемы в работе системы
     *
     * @param $title
     */
    public static function _deleteSubtypeByTitle ($title)
    {
        $serviceload_types = self::getData([self::NARYAD], ['sub_types']);
        $sub_types = $serviceload_types[self::NARYAD]['sub_types'];
        foreach ($sub_types as $key => $val)
            if ($title === $val)
                unset($sub_types[$key]);
        self::_update(
            self::NARYAD,
            ['sub_types' => json_encode($sub_types)]
        );
    }

    /**
     * Удалить все подтипы службы
     *
     * ВНИМАНИЕ!
     * Перед удалением убедитесь, что записи нигде не используются с помощью функции getSubtypeDependencies
     * В противном случае возникнут проблемы в работе системы
     *
     */
    public static function _deleteSubtypeAll ()
    {
        self::_update(
            self::NARYAD,
            ['sub_types' => json_encode([])]
        );
    }

    /**
     * Удалить все записи из БД
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
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