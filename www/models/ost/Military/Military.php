<?php

namespace OsT\Military;

use OsT\Base\System;
use OsT\Unit;
use PDO;

/**
 * Военнослужащий
 * Class Military
 * @package OsT\Military
 * @version 2022.03.10
 *
 * __construct                      Military constructor
 * getData                          Получить массив данных из БД
 * getUnit                          Получить идентификатор подразделения военнослужащего
 * getByUnit                        Получить массив идентификаторов военнослужащих, которые на момент времени time прикреплены к подразделению unit либо его дочернему
 * getTableItemHtml                 Получить html представление для таблицы управления военнослужащими
 * getLevelsData                    Получить массив данных о званиях военнослужащего
 * getLevel                         Получить данные о звании военнослужащего $military на момент времени $time
 *
 * last                             Получить идентификатор (id) последней запсии в таблице
 * count                            Получить количество записей в таблице
 * insert                           Добавить забись в БД
 * update                           Обновить запись в БД
 * _update                          Обновить запись в БД
 * delete                           Удалить запись из БД
 * _delete                          Удалить запись из БД
 * _deleteWithDependencies          Удалить запись из БД c последующим удалением зависимых от записи данных
 * _deleteAll                       Удалить все записи из БД
 * _deleteAllWithDependencies       Удалить все записи из БД c последующим удалением зависимых от данных
 *
 */
class Military
{

    const TABLE_NAME = 'military';

    public $id;                         // Идентификатор
    public $fname;                      // Фамилия
    public $iname;                      // Имя
    public $oname;                      // Отчество
    public $description;                // Примечание
    public $level;                      // Звание
    public $unit;                       // Подразделение
    public $state;                      // Текущая должность по штату

    public $workability = false;        // Работоспособность объекта

    /**
     * Military constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id], [
            'id',
            'fname',
            'iname',
            'oname',
            'description',
            'level',
            'unit',
            'state'
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
     *      id                      -   Идентификатор военнослужащего
     *      fname                   -   Фамилия
     *      iname                   -   Имя
     *      oname                   -   Отчество
     *      fio                     -   ФИО в полной форме
     *      fio_short               -   ФИО в краткой форме, Иванов И.И.
     *      description             -   Примечание
     *      levels                  -   Массив идентификаторов записей о присвоении званий военнослужащему
     *      levels_data             -   Данные о званиях военнослужащего
     *      current_level_data      -   Данные текущего звания военнослужащего
     *      level                   -   Идентификатор текущего звания военнослужащего
     *      level_title             -   Наименование текущего звания военнослужащего
     *      level_short             -   Краткое наименование текущего звания военнослужащего
     *      states                  -   Массив идентификаторов записей о должностях военнослужащего
     *      states_data             -   Данные о должностях военнослужащего
     *      currently_states_data   -   Данные о текущей должности военнослужащего (постоянной и, при на личии временной)
     *      state                   -   Идентификатор текущей должности военнослужащего
     *      state_title             -   Наименование текущей должности военнослужащего
     *      unit                    -   Текущее подразделение военнослужащего
     *      unit_path_str           -   Путь (иерархия) к текущему подразделению военнослужащего
     */
    public static function getData($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   military
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор военнослужащего */
                $objectData['id'] = intval($item['id']);

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

                /* ФИО в краткой форме, Иванов И.И. */
                if (System::chColum('fio_short', $colums))
                    $objectData['fio_short'] = System::shortFio($objectData['fio']);

                /* Примечание */
                if (System::chColum('description', $colums))
                    $objectData['description'] = stripslashes($item['description']);

                /* Массив идентификаторов записей о присвоении званий военнослужащему */
                if (System::chColum([
                    'level',
                    'level_title',
                    'level_short',
                    'levels',
                    'levels_data',
                    'current_level_data'
                ], $colums))
                    $objectData['levels'] = Level::getIdArrByMilitary($objectData['id']);

                /* Данные о званиях военнослужащего */
                if (System::chColum([
                    'level',
                    'level_title',
                    'level_short',
                    'levels_data',
                    'current_level_data'
                ], $colums))
                    $objectData['levels_data'] = self::getLevelsData($objectData['id'], ['level', 'level_title', 'level_short', 'date'], $objectData['levels']);

                /* Данные текущего звания военнослужащего */
                if (System::chColum([
                    'level',
                    'level_title',
                    'level_short',
                    'current_level_data'
                ], $colums))
                    $objectData['current_level_data'] = self::getLevel($objectData['id'], null, $objectData['levels_data']);

                /* Идентификатор текущего звания военнослужащего */
                if (System::chColum('level', $colums))
                    $objectData['level'] = $objectData['current_level_data']['level'];

                /* Наименование текущего звания военнослужащего */
                if (System::chColum('level_title', $colums))
                    $objectData['level_title'] = $objectData['current_level_data']['level_title'];

                /* Краткое наименование текущего звания военнослужащего */
                if (System::chColum('level_short', $colums))
                    $objectData['level_short'] = $objectData['current_level_data']['level_short'];

                /* Массив идентификаторов записей о должностях военнослужащего */
                if (System::chColum([
                    'states',
                    'states_data',
                    'currently_states_data',
                    'state',
                    'state_title',
                    'unit',
                    'unit_path_str'
                ], $colums))
                    $objectData['states'] = State::getIdArrByMilitary($objectData['id']);

                /* Данные о должностях военнослужащего */
                if (System::chColum([
                    'states_data',
                    'currently_states_data',
                    'state',
                    'state_title',
                    'unit',
                    'unit_path_str'
                ], $colums))
                    $objectData['states_data'] = State::getData(
                        $objectData['states'],
                        [
                            'unit',
                            'unit_path_str',
                            'state',
                            'state_title',
                            'state_title_abbreviation',
                            'vrio',
                            'date_from',
                            'date_to'
                        ]);

                /* Данные о текущей должности военнослужащего (постоянной и, при на личии временной) */
                if (System::chColum([
                    'currently_states_data',
                    'state',
                    'state_title',
                    'unit',
                    'unit_path_str'
                ], $colums))
                    $objectData['currently_states_data'] = State::getCurrentlyMilitaryStates($objectData['id'], null, $objectData['states_data']);

                /* Идентификатор текущей должности военнослужащего */
                if (System::chColum('state', $colums))
                    $objectData['state'] = isset($objectData['currently_states_data']['always']) ? $objectData['currently_states_data']['always']['state'] : null;

                /* Наименование текущей должности военнослужащего */
                if (System::chColum('state_title', $colums))
                    $objectData['state_title'] = isset($objectData['currently_states_data']['always']) ? $objectData['currently_states_data']['always']['state_title'] : 'Нет';

                /* Аббревиатура текущей должности военнослужащего */
                if (System::chColum('state_title_abbreviation', $colums))
                    $objectData['state_title_abbreviation'] = isset($objectData['currently_states_data']['always']) ? $objectData['currently_states_data']['always']['state_title_abbreviation'] : 'Нет';

                /* Текущее подразделение военнослужащего */
                if (System::chColum('unit', $colums))
                    $objectData['unit'] = isset($objectData['currently_states_data']['always']) ? $objectData['currently_states_data']['always']['unit'] : null;

                /* Путь (иерархия) к текущему подразделению военнослужащего */
                if (System::chColum('unit_path_str', $colums))
                    $objectData['unit_path_str'] = isset($objectData['currently_states_data']['always']) ? $objectData['currently_states_data']['always']['unit_path_str'] : 'Нет';

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
     * Получить идентификатор подразделения военнослужащего
     * @param $military - идентификатор военнослужащего
     * @param null $time - время в формате Unix. Если null - подразделение на текущий момент
     * @param null $states_data - массив данных из послужного списка военнослужащего с минимальным набором данных
     *              [ 0 =>
     *                  [   'unit',
     *                      'vrio',
     *                      'date_from',
     *                      'date_to'
     *                  ]]
     * @return mixed|null - Идентификатор подразделения либо null
     */
    public static function getUnit ($military, $time = null, $states_data = null)
    {
        return State::getMilitaryUnit($military, $time, $states_data);
    }

    /**
     * Получить массив идентификаторов военнослужащих, которые на момент времени time
     * прикреплены к подразделению unit либо его дочернему
     * @param $unit - идентификатор подразделения
     * @param null $time - время в формате Unix, на момент которого военнослужащий был на должности
     *                  null - текущее время
     *                  integer - время в формате Unix
     *                  ['from', 'to'] - диапазон времени
     * @param bool $childred - учитывать дочерние подразделения
     *                  true - да
     *                  false - нет
     * @param bool $id_only - только идентификаторы военнослужащих
     *                  true - выходной массив в формате [идентификатор_военнослужащего_1, ...]
     *                  false - выходной массив в формате [ 0 => ['military', 'unit', ...]]
     * @return array
     *          [идентификатор_военнослужащего_1, ...]
     *          либо если $id_only = false
     *          [
     *              military    - идентификатор военнослужащего
     *              unit        - идентификатор подразделения
     *              date_from   - время в формате Unix назначения на должность
     *              date_to     - время в формате Unix снятия с должности
     *          ]
     */
    public static function getByUnit ($unit, $time = null, $childred = true, $id_only = true)
    {
        global $DB;

        $data = [];

        if ($time === null)
            $time = System::time();
        if (!is_array($time))
            $time = [
                'from' => $time,
                'to' => $time
            ];

        if ($childred) {
            $units = Unit::getTree ([$unit]);
            $units = Unit::convertUnitsTreeAllToList($units);
            $where_sql = !count($units) ? '' : ' AND us.unit IN (' . System::convertArrToSqlStr($units) . ')';
        } else {
            $where_sql = ' AND us.unit = ' . $unit;
        }

        $q = $DB->prepare('
            SELECT  ms.military,
                    us.unit,
                    ms.date_from,
                    ms.date_to,
                    us.title as state_title
            FROM    military_state ms
            LEFT JOIN unit_state us on us.id = ms.state
            WHERE   ms.vrio = 0 '
            . $where_sql);
        $q->execute();
        if ($q->rowCount()) {
            $tmp = $q->fetchAll(PDO::FETCH_ASSOC);

            // Удаление данных, которые не попадают в диапазон времени
            foreach ($tmp as $key => $value) {
                $from = intval($value['date_from']);
                $to = intval($value['date_to']);
                if ($to === 0)
                    $to = TIME_LAST_SECOND;
                if (!System::intervalCrossing($time['from'], $time['to'], $from, $to))
                    unset($tmp[$key]);
            }

            if (!$id_only) {
                $data = $tmp;
            } else {
                foreach ($tmp as $key => $value) {
                    $id = intval($value['military']);
                    $data[$id] = $id;
                }
            }
        }

        return $data;
    }

    /**
     * Получить html представление для таблицы управления военнослужащими
     *  страница military
     *
     * @param $data [                           - массив данных о военнослужащем
     *                  'index',                - порядковый номер записи в таблице
     *                  'id',                   - идентификатор военнослувжащего
     *                  'unit_path_str',        - полный путь к подразделению, к которому на данный момент относится военнослужащий
     *                  'state_title'           - наименование текущей должности на постоянной основе
     *                  'level_title'           - наименование текущего звания
     *                  'fio'                   - полное ФИО
     *
     * @return string - html представление для таблицы управления военнослужащими
     */
    public static function getTableItemHtml ($data)
    {
        return '<tr class="military_' . $data['id'] . '">
                    <td>' . $data['index'] . '</td>
                    <td>' . $data['level_title'] . '</td>
                    <td>' . $data['fio'] . '</td>
                    <td>' . $data['state_title'] . '</td>
                    <td>' . $data['unit_path_str'] . '</td>
                    <td class="table_td_buttons">
                        <div class="buttonsBox">
                            <a class="button edit" href="military_edit.php?id=' . $data['id'] . '" title="Изменить"></a>
                            <div class="button more" onclick="button_more_show_list(this)" title="Больше">
                                <div class="button_list">
                                    <ul>
                                        <li>Периоды отсутствия</li>
                                        <li><a href="militaries.php?delete=' . $data['id'] . '">Удалить</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>';
    }

    /**
     * Получить массив данных о званиях военнослужащего
     * @param $military
     * @param array $colums
     * @param null $levels_id
     * @return array
     */
    public static function getLevelsData ($military, $colums = [], $levels_id = null)
    {
        if ($levels_id === null)
            $levels_id = Level::getIdArrByMilitary($military);
        if (count($levels_id))
            return Level::getData($levels_id, $colums);
        else return [];
    }

    /**
     * Получить данные о звании военнослужащего $military на момент времени $time
     * @param $military -идентификатор военнослужащего
     * @param null $time - время в формате UNIX || null - текущее время
     * @param null $levels - массив данных о звыаниях военнослужащего
     * @return array|mixed
     */
    public static function getLevel ($military, $time = null, $levels = null)
    {
        if ($levels === null)
            $levels = self::getLevelsData($military, ['level', 'level_title', 'date']);
        if (count($levels)) {
            $levels = System::sort($levels, 'date', 'desc');
            if ($time === null)
                $time = System::time();
            foreach ($levels as $key => $level) {
                $needle = $key;
                if ($level['date'] <= $time)
                    break;
            }
            return $levels[$needle];

        } else return [
            'level' => 0,
            'level_title' => 'Не присвоено',
            'date' => 0
        ];
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
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
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
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
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
     * Удалить запись из БД c последующим удалением зависимых от записи данных
     * @param $id
     */
    public static function _deleteWithDependencies ($id)
    {
        \OsT\Serviceload\Military::_delete($id);
        Level::_deleteByMilitary($id);
        State::_deleteByMilitary($id);
        Absent::_deleteByMilitary($id);
        self::_delete($id);
    }

    /**
     * Удалить все записи из БД
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     *
     * @return bool
     */
    public static function _deleteAll ()
    {
        global $DB;
        $DB->query('DELETE FROM ' . self::TABLE_NAME);
        return true;
    }

    /**
     * Удалить все записи из БД c последующим удалением зависимых от данных
     */
    public static function _deleteAllWithDependencies ()
    {
        self::_deleteAll();
        \OsT\Serviceload\Military::_deleteAll();
        Level::_deleteAll();
        State::_deleteAll();
        Absent::_deleteAll();
    }

}