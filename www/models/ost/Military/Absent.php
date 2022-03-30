<?php

namespace OsT\Military;

use OsT\Base\System;
use OsT\Base\DB;
use PDO;

/**
 * Class Absent
 * @package OsT\Military
 * @version 2022.03.10
 *
 * __construct                          Absent constructor
 * getData                              Получить массив данных из БД
 * checkData                            Проверить корректность пользовательских данных при добавлении / редактировании периода отсутствия
 * getItemByTime                        Получить период отстутствия военнослужащего в момент времени
 * getItemsByInterval                   Получить периоды отстутствия военнослужащего, которые пересекают интервал времени
 * getArrByMilitary                     Получить идентификаторы периодов отсутствия военнослужащего
 *
 * last                                 Получить идентификатор (id) последней запсии в таблице
 * count                                Получить количество записей в таблице
 * insert                               Добавить забись в БД
 * insertWithDependencies               Добавить запись в БД с последующим обновлением зависимых ячеек графика нагрузки
 * updateWithDependencies               Обновить данные периода отсутствия с последующим обновлением зависимых ячеек графика нагрузки
 * update                               Обновить запись в БД
 * _update                              Обновить запись в БД
 * delete                               Удалить запись из БД
 * _delete                              Удалить запись из БД
 * deleteWithDependencies               Удалить запись из БД с последующим обновлением служебной нагрузки
 * _deleteByMilitary                    Удалить все периоды отсутствия военнослужащего без обновления зависимостей
 * _deleteByMilitaryWithDependencies    Удалить все периоды отсутствия военнослужащего с последующим обновлением служебной нагрузки
 * _deleteAll                           Удалить все периоды отсутствия без обновления зависимостей
 * _deleteAllWithDependencies           Удалить все периоды отсутствия с последующим обновлением служебной нагрузки
 *
 */
class Absent
{
    const TABLE_NAME = 'military_absent';

    public $id;
    public $military;
    public $type;
    public $type_title;
    public $date_from;
    public $date_to;

    public $workability = false;

    /**
     * Absent constructor.
     * @param $id
     */
    public function __construct( $id )
    {
        $data = self::getData([$id], [
            'id',
            'military',
            'type',
            'type_title',
            'date_from',
            'date_to'
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
     *          []              - все записи
     *
     * @param array $colums - массив атрибутов запрашиваемых данных
     * @example ['id', 'title', 'count', ...]   - определенные идентификаторы
     *          []                              - набор данных по умолчанию
     *
     * @return array массив данных запрашиваемых записей
     * @example [ 0 => ['id' => 1, 'title' => 'default', ...], ...]
     *          где 0 - идентификатор записи в БД
     *
     *      id                  -   Идентификатор записи об отсутствии
     *      military            -   Идентификатор военнослужащего
     *      fname               -   Фамилия
     *      iname               -   Имя
     *      oname               -   Отчество
     *      fio                 -   ФИО в полной форме
     *      fio_short           -   ФИО в краткой ворме, Иванов И.И.
     *      type                -   Идентификатор типа отсутствия
     *      type_title          -   Наименование типа отсутствия
     *      date_from           -   Дата начала периода в формате Unix
     *      date_from_string    -   Дата начала периода в формате 01.01.2022
     *      date_to             -   Дата окончания периода в формате Unix
     *      date_to_string      -   Дата окончания периода в формате 01.01.2022
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE ma.id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
                SELECT  ma.id,
                        military,
                        m.fname,
                        m.iname,
                        m.oname,
                        ma.absent_type as type,
                        t.title as type_title,
                        date_from,
                        date_to
                FROM    military_absent ma
                LEFT JOIN military m ON ma.military = m.id
                LEFT JOIN ant_serviceload_type t ON t.id = ma.absent_type
                ' . $in_arr_sql);
        $q->execute();
        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор записи об отсутствии */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Идентификатор военнослужащего */
                if (System::chColum('military', $colums))
                    $objectData['military'] = intval($item['military']);

                /* Фамилия */
                if (System::chColum([
                    'fname',
                    'fio',
                    'fio_short'
                ], $colums))
                    $objectData['fname'] = stripslashes($item['fname']);

                /* Имя */
                if (System::chColum([
                    'iname',
                    'fio',
                    'fio_short'
                ], $colums))
                    $objectData['iname'] = stripslashes($item['iname']);

                /* Отчество */
                if (System::chColum([
                    'oname',
                    'fio',
                    'fio_short'
                ], $colums))
                    $objectData['oname'] = stripslashes($item['oname']);

                /* ФИО в полной форме */
                if (System::chColum([
                    'fio',
                    'fio_short'
                ], $colums))
                    $objectData['fio'] = $objectData['fname'] . ' ' . $objectData['iname'] . ' ' . $objectData['oname'];

                /* ФИО в краткой ворме, Иванов И.И. */
                if (System::chColum('fio_short', $colums))
                    $objectData['fio_short'] = System::shortFio($objectData['fio']);

                /* Идентификатор типа отсутствия */
                if (System::chColum('type', $colums))
                    $objectData['type'] = intval($item['type']);

                /* Наименование типа отсутствия */
                if (System::chColum('type_title', $colums))
                    $objectData['type_title'] = stripslashes($item['type_title']);

                /* Дата начала периода в формате Unix */
                if (System::chColum([
                    'date_from',
                    'date_from_string',
                ], $colums))
                    $objectData['date_from'] = intval($item['date_from']);

                /* Дата начала периода в формате 01.01.2022 */
                if (System::chColum('date_from_string', $colums))
                    $objectData['date_from_string'] = System::parseDate($objectData['date_from']);

                /* Дата окончания периода в формате Unix */
                if (System::chColum([
                    'date_to',
                    'date_to_string',
                ], $colums))
                    $objectData['date_to'] = intval($item['date_to']);

                /* Дата окончания периода в формате 01.01.2022 */
                if (System::chColum('date_to_string', $colums))
                    $objectData['date_to_string'] = System::parseDate($objectData['date_to']);

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
     * Проверить корректность пользовательских данных при добавлении / редактировании периода отсутствия
     * Используется в ajax_schedule.php
     * @param $data
     * @param bool $edit
     * @return mixed
     */
    public static function checkData ($data, $edit = false)
    {
        $errors = [
            'no_data' => 'Были заполнены не все поля',
            'incorrect_interval' => 'Значение поля "С" должно быть меньше или равно значению в поле "По"',
            'no_military' => 'Не надено данных о военнослужащем, график отсутствия которого вы пытаетесь изменить',
            'military_work_interval' => 'Интервал отсутствия военнослужащего не должен выходить за пределы интервала его службы',
            'interval_crossing' => 'Данный период отсутсвия пересекается с уже имеющимися у военнослужащего',
        ];
        if ($data [ 'date_from' ] && $data [ 'date_to' ] && $data ['military']) {
            if ($data [ 'date_from' ] <= $data [ 'date_to' ]) {
                $military = Military::getData(
                    [$data['military']],
                    ['states_data']
                );

                // Проверка существования военнослужащего
                if (isset($military[$data['military']])) {
                    // Проверка назначен ли он на должность в выбранном диапазоне времени
                    $states = $military[$data['military']]['states_data'];
                    $workalltime = State::checkTimeIntervalFullCrossing($states, $data ['date_from'], $data ['date_to']);
                    if ($workalltime) {
                        // Проверка на пересечение с уже имеющимися периодами отсутствия
                        $crossing_dontcheck = $edit ? [$edit] : [];
                        $crossing = Absent::getItemsByInterval($data ['military'], $data ['date_from'], $data ['date_to'], $crossing_dontcheck);
                        if (!count($crossing))
                            return true;
                        else return $errors['interval_crossing'];
                    } else return $errors['military_work_interval'];
                } else return $errors['no_military'];
            } else return $errors['incorrect_interval'];
        } else return $errors['nodata'];
    }

    /**
     * Получить период отстутствия военнослужащего в момент времени
     * @param $military - идентификатор военнослужащего
     * @param $date - время в формате Unix
     * @return int|null
     *          int - идентификатор периода отсутствия
     *          null - нет
     */
    public static function getItemByTime ($military, $date)
    {
        $q = DB::connect()->prepare('
            SELECT  id
            FROM    military_absent
            WHERE   military = :military AND
                    date_from < :date_from AND
                    date_to > :date_to');
        $q->execute([
            'military' => $military,
            'date_from' => ($date + 1),
            'date_to' => ($date - 1)
            ]);
        if ($q->rowCount()) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            return intval($data['id']);

        } else return null;
    }

    /**
     * Получить периоды отстутствия военнослужащего, которые пересекают интервал времени
     * @param $military - идентификатор военнослужащего
     * @param $date_from - начало интервала в формате Unix
     * @param $date_to - начало интервала в формате Unix
     * @param array $black_list - массив идентификаторов периодов отсутствия, которые не учитываются при проверке
     * @return array
     *          [] - нет пересечений
     *              либо
     *          [0 => [
     *                  id,
     *                  date_from,
     *                  date_to
     *              ],
     *          ...
     *          ] -
     */
    public static function getItemsByInterval ($military, $date_from, $date_to, $black_list = [])
    {
        global $DB;
        $q = $DB->prepare('
            SELECT  id,
                    date_from,
                    date_to
            FROM    military_absent
            WHERE   military = :military');
        $q->execute(['military' => $military]);
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key=>$item) {
                $data[$key]['id'] = intval($item['id']);
                if (!in_array($data[$key]['id'], $black_list)) {
                    $data[$key]['date_from'] = intval($item['date_from']);
                    $data[$key]['date_to'] = intval($item['date_to']);
                    if (!System::intervalCrossing($data[$key]['date_from'], $data[$key]['date_to'], $date_from, $date_to))
                        unset($data[$key]);
                } else unset($data[$key]);
            }
            return $data;
        } else return [];
    }

    /**
     * Получить идентификаторы периодов отсутствия военнослужащего
     * @param $military - идентификатор военнослужащего
     * @return array - масиив идентификаторов периодов отсутствия военнослужащего
     */
    public static function getArrByMilitary ($military)
    {
        global $DB;
        $return = [];

        $q = $DB->prepare('
            SELECT  id
            FROM    military_absent
            WHERE military = :military');
        $q->execute(['military' => $military]);

        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $val)
                $return[] = intval($val['id']);
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
     * Добавить запись в БД с последующим обновлением зависимых ячеек графика нагрузки
     * @param $data
     * @return bool
     */
    public static function insertWithDependencies ($data)
    {
        self::insert($data);
        $serviceload = \OsT\Serviceload\Military::genDefaultRecords(
            $data['military'],
            $data['date_from'],
            $data['date_to']
        );
        \OsT\Serviceload\Military::updateServiceload($data['military'], $serviceload);
        return true;
    }

    /**
     * Обновить данные периода отсутствия с последующим обновлением зависимых ячеек графика нагрузки
     * @param $data
     * @return bool
     */
    public function updateWithDependencies ($data)
    {
        $this->update($data);
        $from = $this->date_from < $data['date_from'] ? $this->date_from : $data['date_from'];
        $to = $this->date_to > $data['date_to'] ? $this->date_to : $data['date_to'];
        $serviceload = \OsT\Serviceload\Military::genDefaultRecords(
            $this->military,
            $from,
            $to
        );
        \OsT\Serviceload\Military::updateServiceload($this->military, $serviceload);
        return true;
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
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки
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
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
     * @param $id - идентификатор записи absent
     * @return bool
     */
    public static function _delete ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['id = ', $id]]);
        return true;
    }

    /**
     * Удалить запись из БД с последующим обновлением служебной нагрузки
     * @return bool
     */
    public function deleteWithDependencies ()
    {
        $this->delete();
        $serviceload = \OsT\Serviceload\Military::genDefaultRecords(
            $this->military,
            $this->date_from,
            $this->date_to
        );
        \OsT\Serviceload\Military::updateServiceload($this->military, $serviceload);
        return true;
    }

    /**
     * Удалить все периоды отсутствия военнослужащего без обновления зависимостей
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
     * @param $id - идентификатор военнослужащего
     */
    public static function _deleteByMilitary ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['military = ', $id]]);
    }

    /**
     * Удалить все периоды отсутствия военнослужащего с последующим обновлением служебной нагрузки
     * @param $military
     */
    public static function _deleteByMilitaryWithDependencies ($military)
    {
        $absents = self::getArrByMilitary($military);
        if (count($absents)) {
            $serviceload = \OsT\Serviceload\Military::getServiceload($military);

            $states = State::getIdArrByMilitary($military);
            $states = State::getData(
                $states,
                [
                    'state',
                    'vrio',
                    'date_from',
                    'date_to'
                ]);

            $absents = self::getData($absents, [
                'date_from',
                'date_to',
                'type',
                'id',
            ]);

            foreach ($absents as $key => $absent) {
                self::_delete($key);
                unset($absents[$key]);
                $serviceload = \OsT\Serviceload\Military::genDefaultRecords(
                    $military,
                    $absent['date_from'],
                    $absent['date_to'],
                    $absents,
                    $states,
                    [],
                    true,
                    $serviceload
                );
            }

            \OsT\Serviceload\Military::updateServiceload($military, $serviceload);
        }
    }

    /**
     * Удалить все периоды отсутствия без обновления зависимостей
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
     */
    public static function _deleteAll ()
    {
        global $DB;
        $DB->query('DELETE FROM ' . self::TABLE_NAME);
    }

    /**
     * Удалить все периоды отсутствия с последующим обновлением служебной нагрузки
     */
    public static function _deleteAllWithDependencies ()
    {
        $absents = self::getData(null, [
            'military',
            'date_from',
            'date_to',
            'type',
            'id',
        ]);
        if (count($absents)) {
            $military_absents = [];
            foreach ($absents as $key => $absent)
                $military_absents[$absent['military']][$key] = $absent;

            foreach ($absents as $key => $absent) {
                self::_delete($key);
                unset($military_absents[$absent['military']][$key]);
                $serviceload = \OsT\Serviceload\Military::genDefaultRecords(
                    $absent['military'],
                    $absent['date_from'],
                    $absent['date_to'],
                    $military_absents[$absent['military']]
                );
                \OsT\Serviceload\Military::updateServiceload($absent['military'], $serviceload);
            }
        }
    }

}