<?php

namespace OsT;

use OsT\Base\System;
use PDO;

/**
 * Управление штатом
 * Class State
 * @package OsT
 * @version 2022.03.12
 *
 * __construct                      State constructor
 * getData                          Получить массив данных из БД
 * getDataByUnit                    Получить массив данных о должностях в подразделении (если нет дочерних падразделений)
 * getHtmlUnit                      Получить HTML представление подразделения для списка всех подразделений
 * getSelectUnitStateHtml           Получить HTML представление списка выбора должности предвалительно выбрав ее подразделение
 * getHtml                          Получить HTML представление должности
 * getArrAffectedUnits              Сформировать массив идентификаторов подразделений, имеющих дачерние должности, как напрямую, так и косвенно
 *
 * last                             Получить идентификатор (id) последней запсии в таблице
 * count                            Получить количество записей в таблице
 * insert                           Добавить забись в БД
 * update                           Обновить запись в БД
 * _update                          Обновить запись в БД
 * delete                           Удалить запись из БД
 * _delete                          Удалить запись из БД
 * _deleteWithDependencies          Удалить запись из БД c последующим удалением зависимых от записи данных
 * _deleteByUnit                    Удалить все дочерние должности подразделения
 * _deleteByUnitWithDependencies    Удалить все дочерние должности подразделения с последующим удалением зависимых данных
 * _deleteAll                       Удалить все записи из БД
 * _deleteAllWithDependencies       Удалить все записи из БД c последующим удалением зависимых данных
 *
 */
class State
{
    const TABLE_NAME = 'unit_state';

    public $id;                         // Идентификатор
    public $unit;                       // Подразделение
    public $title;                      // Наименование полное
    public $title_short;                // Наименование сокращенно
    public $title_abbreviation;         // Аббревиатура
    public $vrio;                       // Возможность назначения временно исполняющего обязанности
    public $vrio_title;                 // Наименование полное для ВРИО
    public $vrio_title_short;           // Наименование сокращенно для ВРИО
    public $vrio_abbreviation;          // Аббревиатура для ВРИО

    public $workability = false;        // Работоспособность объекта

    /**
     * State constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id], [
            'id',
            'unit',
            'title',
            'title_short',
            'title_abbreviation',
            'vrio',
            'vrio_title',
            'vrio_title_short',
            'vrio_abbreviation'
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
     *      id                          -   Идентификатор должности
     *      unit                        -   Идентификатор подразделения
     *      title                       -   Наименование должности
     *      title_short                 -   Наименование должности краткое
     *      title_abbreviation          -   Наименование должности аббревиатура
     *      vrio                        -   Возможность временно исполнять обязанности (1 / 0)
     *      vrio_title                  -   Наименование должности ВРИО
     *      vrio_title_short            -   Наименование должности ВРИО краткое
     *      vrio_abbreviation           -   Наименование должности ВРИО аббревиатура
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT id, unit, title, title_short, title_abbreviation, vrio, vrio_title, vrio_title_short, vrio_abbreviation
            FROM   unit_state
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор должности */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Идентификатор подразделения */
                if (System::chColum('unit', $colums))
                    $objectData['unit'] = intval($item['unit']);

                /* Наименование должности */
                if (System::chColum('title', $colums))
                    $objectData['title'] = stripslashes($item['title']);

                /* Наименование должности краткое */
                if (System::chColum('title_short', $colums))
                    $objectData['title_short'] = stripslashes($item['title_short']);

                /* Наименование должности аббревиатура */
                if (System::chColum('title_abbreviation', $colums))
                    $objectData['title_abbreviation'] = stripslashes($item['title_abbreviation']);

                /* Возможность временно исполнять обязанности (1 / 0) */
                if (System::chColum('vrio', $colums))
                    $objectData['vrio'] = boolval($item['vrio']);

                /* Наименование должности ВРИО */
                if (System::chColum('vrio_title', $colums))
                    $objectData['vrio_title'] = stripslashes($item['vrio_title']);

                /* Наименование должности ВРИО краткое */
                if (System::chColum('vrio_title_short', $colums))
                    $objectData['vrio_title_short'] = stripslashes($item['vrio_title_short']);

                /* Наименование должности ВРИО аббревиатура */
                if (System::chColum('vrio_abbreviation', $colums))
                    $objectData['vrio_abbreviation'] = stripslashes($item['vrio_abbreviation']);

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
     * Получить массив данных о должностях в подразделении (если нет дочерних падразделений)
     * Либо массив данных о должностях во всех дочерних подразделениях
     * @param $unit - идентификатор подразделения
     * @param $colums - запрашивваемый набор данных
     * @return array
     */
    public static function getDataByUnit ($unit, $colums)
    {
        global $DB;
        $return = [];

        $children = Unit::getTree([$unit]);
        if (is_array($children[$unit])) {
            $where_units = Unit::convertUnitsTreeAllToList($children);
        } else $where_units = [$unit];

        $q = $DB->prepare('
            SELECT id
            FROM   unit_state
            WHERE  unit IN (' . System::convertArrToSqlStr($where_units) . ')');
        $q->execute();
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            $units = [];
            foreach ($data as $item)
                $units[] = intval($item['id']);

            return self::getData($units, $colums);
        }

        return $return;
    }

    /**
     * Получить HTML представление подразделения для списка всех подразделений
     *
     * @param int $id - иентификатор подразделения
     * @param $title - наименование подразделения
     *
     * @param bool $buttons отображение кнопок управления подразделением
     * @example true - все кнопки
     *          false - без кнопок
     *          [['class' => 'new', 'title' => 'Добавить', 'function' => 'f()'], ...]  - массив кнопок
     *
     * @param null $body - тело (дочерние подразделения)
     * @example null - рекурсивное автоформирование
     *          '<tad></tad>' - произвольный html
     *
     * @param null $class - атрибут class для родительского блока
     * @example null - класс по-умолчанию
     *          'class_name' - произвольный класс + класс по-умолчанию
     *
     * @return string - html разметка подразделения
     */
    public static function getHtmlUnit ($id, $title, $buttons = true, $body = null, $class = null, $count_by_units = [])
    {
        global $STRUCT_DATA;
        $children = Unit::getChildren($id);
        $class_str = $class === null ? 'unitBox' : 'unitBox ' . $class;

        // Обработка кнопок
        $buttons_html = '';
        $buttons_array = [];
        if ($buttons === true) {
            $visibility = isset($count_by_units[$id]) ? '' : 'hidden';
            if (count($children)) {
                $buttons_array = [
                    ['class' => 'delete ' . $visibility, 'title' => 'Удалить все', 'function' => 'unit_delete_all(' . $id . ')']
                ];
            } else {
                $buttons_array = [
                    ['class' => 'new', 'title' => 'Добавить', 'function' => 'state_new_window_show(' . $id . ')'],
                    ['class' => 'move object_in_development ' . $visibility, 'title' => 'Переместить все', 'function' => 'unit_move_window_show(' . $id . ')'],
                    ['class' => 'delete ' . $visibility, 'title' => 'Удалить все', 'function' => 'unit_delete_all(' . $id . ')'],
                ];
            }
        } elseif ($buttons === false)
            $buttons_array = [];
        else $buttons_array = $buttons;

        if (count($buttons_array)) {
            foreach ($buttons_array as $button)
                $buttons_html .= '<div class="slideButton ' . $button['class'] . '" onclick="' . $button['function'] . '">' . $button['title'] . '</div>';
        }

        $body_html = '';
        if ($body === null) {
            if (count($children)) {
                foreach ($children as $item)
                    $body_html .= self::getHtmlUnit($STRUCT_DATA[$item]['id'], $STRUCT_DATA[$item]['title'], $buttons, null, $class, $count_by_units);
            } else {
                $state = self::getDataByUnit($id, ['id', 'unit', 'title']);
                foreach ($state as $item)
                    $body_html .= self::getHtml($item['id'], $item['title'], $item['unit'], $buttons);
            }
        } else $body_html = $body;

        return '<div class="' . $class_str . '" id="unit_' . $id . '" data-unit="' . $id . '">
                    <div class="unitTitleLine" onclick="unit_show_body_toggle(this)">
                        <div class="unitTitle">' . $title . '</div>
                        <div class="unitButtonsBox">
                            ' . $buttons_html . '
                        </div>
                    </div>
                    <div class="unitBody">
                        ' . $body_html . '
                    </div>
                </div>';
    }

    /**
     * Получить HTML представление списка выбора должности предвалительно выбрав ее подразделение
     * по типу unit -> unit -> military
     * @param $tree - дерево подразделений
     * @param int $unit - идентификатор выбранного подразделения (не используется, если указан $state)
     * @param null $state - идентификатор выбранной должности
     * @param string $unit_prefix - префикс имени для элементов select выбора подразделения
     * @param string $state_name - имя элемента select выбора должности
     * @param string $state_title - ключ данных из self::getData, которые будут отображаться в option
     * @return string - HTML представление
     */
    public static function getSelectUnitStateHtml ($tree, $unit = 0, $state = null, $unit_prefix = 'unit_', $state_name = 'state', $state_title = 'title')
    {
        if ($state !== null) {
            $tmp = self::getData([$state], ['unit']);
            if (isset($tmp[$state]['unit']))
                $unit = $tmp[$state]['unit'];
            else $state = null;
        }

        $html =  Unit::getSelectUnitHtml($tree, $unit, $unit_prefix);
        $children = \OsT\Unit::getChildren($unit);
        if (!count($children)) {
            $states = self::getDataByUnit($unit, [$state_title]);
            if (count($states)) {
                $item = [];
                foreach ($states as $id => $data) {
                    $tmp = [
                        'id' => $id,
                        'title' => $data[$state_title]
                    ];
                    if ($state === $id)
                        $tmp['selected'] = 'selected';
                    $item[] = $tmp;
                }

            } else $item = [[
                'id' => -1,
                'title' => ''
            ]];
            $html .= System::getHtmlSelect($item, $state_name);
        }

        return $html;
    }

    /**
     * Получить HTML представление должности
     *
     * @param int $id - иентификатор должности
     * @param $title - наименование полное
     *
     * @param $unit - родительское подразделение
     *
     * @param bool $buttons отображение кнопок управления
     * @return string - html разметка
     * @example true - все кнопки
     *          false - без кнопок
     *          [['class' => 'new', 'title' => 'Добавить', 'function' => 'f()'], ...]  - массив кнопок
     *
     */
    public static function getHtml ($id, $title, $unit, $buttons = true)
    {
        // Добавление информации о родительских подразделениях
        $path = Unit::getPath($unit);
        $path[] = 0;
        $class = '';
        foreach ($path as $item)
            $class .= ' unit_' . $item;

        // Обработка кнопок
        $buttons_html = '';
        if ($buttons === true) {
            $buttons_array = [
                ['class' => 'move object_in_development ', 'title' => 'Переместить', 'function' => 'state_move_window_show(' . $id . ')'],
                ['class' => 'edit', 'title' => 'Изменить', 'function' => 'state_edit_window_show(' . $id . ')'],
                ['class' => 'delete', 'title' => 'Удалить', 'function' => 'state_delete(' . $id . ')'],
            ];
        } elseif ($buttons === false)
            $buttons_array = [];
        else $buttons_array = $buttons;

        if (count($buttons_array)) {
            foreach ($buttons_array as $button)
                $buttons_html .= '<div class="slideButton ' . $button['class'] . '" onclick="' . $button['function'] . '">' . $button['title'] . '</div>';
        }

        return '<div class="stateBox ' . $class . '" id="state_' . $id . '" data-state="' . $id . '">
                    <div class="stateTitleLine">
                        <div class="stateTitle">' . $title . '</div>
                        <div class="stateButtonsBox">
                            ' . $buttons_html . '
                        </div>
                    </div>
                </div>';
    }

    /**
     * Сформировать массив идентификаторов подразделений, имеющих дачерние должности, как напрямую, так и косвенно
     * @return array - массив типа [ID подразделения] = кол-во дочерних должностей
     */
    public static function getArrAffectedUnits ()
    {
        global $STRUCT_TREE;
        global $DB;

        $q = $DB->prepare('
            SELECT  count(id) as num,
                    unit
            FROM    unit_state
            GROUP BY unit');
        $q->execute();
        if ($q->rowCount()) {
            $units_state_count = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {
                $unit = intval($item['unit']);
                $count = intval($item['num']);
                $path = Unit::getPath($unit);
                foreach ($path as $punit) {
                    if (isset($units_state_count[$punit]))
                        $units_state_count[$punit] += $count;
                    else $units_state_count[$punit] = $count;
                }
            }
            return $units_state_count;

        } else return [];
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
     * @param $id - идентификатор должности
     */
    public static function _deleteWithDependencies ($id)
    {
        self::_delete($id);
        \OsT\Military\State::_deleteByStateWithDependencies($id);
    }

    /**
     * Удалить все дочерние должности подразделения
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     *
     * @param $unit - идентификатор подразделения
     */
    public static function _deleteByUnit ($unit)
    {
        global $DB;
        $arr = self::getDataByUnit ($unit, ['id']);
        $states = [];
        foreach ($arr as $state)
            $states[] = $state['id'];

        $DB->_deleteArray(self::TABLE_NAME, $states);
    }

    /**
     * Удалить все дочерние должности подразделения с последующим удалением зависимых данных
     * @param $unit - идентификатор подразделения
     */
    public static function _deleteByUnitWithDependencies ($unit)
    {
        global $DB;
        $arr = self::getDataByUnit ($unit, ['id']);
        $states = [];
        foreach ($arr as $state) {
            $states[] = $state['id'];
            \OsT\Military\State::_deleteByStateWithDependencies($state['id']);
        }

        $DB->_deleteArray(self::TABLE_NAME, $states);
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
     * Удалить все записи из БД c последующим удалением зависимых данных
     */
    public static function _deleteAllWithDependencies ()
    {
        self::_deleteAll();
        \OsT\Military\State::_deleteAllWithDependencies();
    }

}