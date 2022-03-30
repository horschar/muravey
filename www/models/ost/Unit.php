<?php

namespace OsT;

use OsT\Base\System;
use PDO;

/**
 * Управление структурой подразделений
 * Class Unit
 * @package OsT
 * @version 2022.03.12
 *
 * __construct                      Unit constructor
 * getData                          Получить массив данных из БД
 * getTreeLevel                     Получить количество уровней подразделений
 * getChildren                      Получить массив идентификаторов подразделений, у которых родитель равен $parent
 * getTree                          Сформировать дерево подразделений типа [id подразделения] = id подразделения || массив типа [id подразделения] = id подразделения
 * convertUnitsTreeAllToList        Преобразовать массив типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения в [№пп] = ид_подразделения
 * getChildrenUnitsTree             Получить дочернее дерево подразделений по ид_подразделения из массива дерева подразделений типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения
 * getChildrenFromTree              Получить одномерный массив первого уровня дочерних элементов из массива девера подразделений
 * removeUnitIntoTree               Удалить выбранное подразделение из массива структуры подразделений
 * getPath                          Получить путь к подразделению из массива дерева подразделений в виде массива [№пп] = подразделение
 * getPathStr                       Получить строку пути к подразделению типа "Родитель / Ребенок"
 * getSelectedUnit                  Определить выбранное подразделение из данных формы
 * getSelectUnitArray               Получить HTML элементы для выбора подразделения
 * getSelectUnitHtml                Получить HTML представление списка выбора подразделения
 * getHtml                          Получить HTML представление подразделения для списка всех подразделений
 *
 * last                             Получить идентификатор (id) последней запсии в таблице
 * count                            Получить количество записей в таблице
 * insert                           Добавить забись в БД
 * update                           Обновить запись в БД
 * _update                          Обновить запись в БД
 * delete                           Удалить запись из БД
 * _delete                          Удалить запись из БД
 * _deleteWithDependencies          Удалить запись из БД c последующим удалением зависимых данных
 * _deleteAll                       Удалить все записи из БД
 * _deleteAllWithDependencies       Удалить все записи из БД c последующим удалением зависимых данных
 *
 */
class Unit
{
    const TABLE_NAME = 'unit';

    public $id;                         // Идентификатор
    public $parent;                     // Родительское подразделение
    public $title;                      // Наименование
    public $workability = false;        // Работоспособность объекта

    /**
     * Unit constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id], [
            'id',
            'parent',
            'title'
        ]);
        if (count($data)) {
            $data = $data[$id];
            $this->id = $data['id'];
            $this->parent = $data['parent'];
            $this->title = $data['title'];

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
     *      id          -   Идентификатор подразделения
     *      parent      -   Идентификатор родительского подразделения
     *      title       -   Наименование подразделения
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT id,
                   parent,
                   title
            FROM   unit
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор подразделения */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Идентификатор родительского подразделения */
                if (System::chColum('parent', $colums))
                    $objectData['parent'] = intval($item['parent']);

                /* Наименование подразделения */
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
     * Получить количество уровней подразделений
     * [unit] - 1 уровень, нет дочерних
     * [unit][unit] - 2 уровня
     * @param $tree
     * @return int
     */
    public static function getTreeLevel ($tree)
    {
        $level = 0;
        $max = 0;
        if (is_array($tree)) {
            $level++;
            foreach ($tree as $item) {
                $tmp = self::getTreeLevel($item);
                if ($tmp > $max)
                    $max = $tmp;
            }
        }
        $level += $max;
        return $level;
    }

    /**
     * Получить массив идентификаторов подразделений, у которых родитель равен $parent
     * @param $parent
     * @return array
     */
    public static function getChildren ($parent)
    {
        global $STRUCT_DATA;
        $arr = [];
        foreach ($STRUCT_DATA as $key=>$data)
            if ($data['parent'] === $parent)
                $arr[$key] = $data['id'];
        return $arr;
    }

    /**
     * Сформировать дерево подразделений типа [id подразделения] = id подразделения || массив типа [id подразделения] = id подразделения
     * @param array $units
     * @return array
     */
    public static function getTree ($units = [0])
    {
        $arr = [];
        if (count($units)) {
            foreach ($units as $unit) {
                $data = self::getChildren($unit);
                if (count($data)) {
                    if ($unit)
                        $arr[$unit] = self::getTree($data);
                    else $arr = self::getTree($data);
                } else {
                    if ($unit)
                        $arr[$unit] = $unit;
                }
            }
        }
        return $arr;
    }

    /**
     * Преобразовать массив типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения в [№пп] = ид_подразделения
     * @param $tree array дерево структуры типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения
     * @param bool $associative - конечный массив в виде [unit] = unit
     * @return array список всех подразделений типа [№пп] = ид_подразделения в дереве подразделений
     */
    public static function convertUnitsTreeAllToList ($tree, $associative = false)
    {
        $arr = [];
        if (is_array($tree) && count($tree)) {
            foreach ($tree as $unit=>$val) {
                if ($associative)
                    $arr[$unit] = $unit;
                else $arr[] = $unit;

                if (is_array($val))
                    $arr = array_merge($arr, self::convertUnitsTreeAllToList($val));
            }
        }
        return $arr;
    }

    /**
     * Получить дочернее дерево подразделений по ид_подразделения из массива дерева подразделений типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения
     * @param $tree array - дерево подразделений типа [ид_подразделения][ид_дочернего_подразделения][...] = ид_подразделения
     * @param $id int - идентификатор подразделения
     * @return bool|mixed
     *          array - подразделение имеет дочерниме подразделения
     *          false - подразделение не найдено либо не имеет дочернимх подразделений
     */
    public static function getChildrenUnitsTree ($tree, $id)
    {
        if ($id === null)
            return $tree;

        foreach ($tree as $key=>$val) {
            if ($key === $id)
                return $val;
            else {
                if (is_array($val)) {
                    $child = self::getChildrenUnitsTree($val, $id);
                    if ($child)
                        return $child;
                }
            }
        }
        return false;
    }

    /**
     * Получить одномерный массив первого уровня дочерних элементов из массива девера подразделений
     * @param $tree
     * @param null $id
     * @return array|bool|mixed
     */
    public static function getChildrenFromTree ($tree, $id = null)
    {
        $arr = self::getChildrenUnitsTree($tree, $id);
        if (is_array($arr)) {
            foreach ($arr as $key=>$val)
                $arr[$key] = $key;
            return $arr;
        } else return [];
    }

    /**
     * Удалить выбранное подразделение из массива структуры подразделений
     * @param $tree - дерево подразделений
     * @param $unit - идентификатор искомого подразделения
     * @return null - очищенное дерево подразделений
     */
    public static function removeUnitIntoTree ($tree, $unit)
    {
        foreach ($tree as $key => $item) {
            if ($key === $unit)
                unset($tree[$key]);
            else {
                if (is_array($item))
                    $tree[$key] = self::removeUnitIntoTree($item, $unit);
            }
        }

        if (!count($tree))
            return null;
        return $tree;
    }

    /**
     * Получить путь к подразделению из массива дерева подразделений в виде массива [№пп] = подразделение
     * @param $id - подразделение
     * @param $tree - массив дерева подразделений
     * @return array|bool
     */
    public static function getPath ($id, $tree = null)
    {
        global $STRUCT_TREE;
        $tree = ($tree === null) ? $STRUCT_TREE : $tree;
        foreach ($tree as $key=>$val) {
            if ($key === $id)
                return [$key];
            else {
                if (is_array($val)) {
                    $arr = self::getPath($id, $val);
                    if (count($arr))
                        return array_merge($arr, [$key]);
                }
            }
        }
        return [];
    }

    /**
     * Получить строку пути к подразделению типа "Родитель / Ребенок"
     * @param $id - идектификатор подразделения
     * @return false|string
     */
    public static function getPathStr ($id)
    {
        global $STRUCT_DATA;
        $str = '';
        if ($id) {
            $tree = self::getPath($id);
            foreach ($tree as $item)
                $str = $STRUCT_DATA[$item]['title'] . ' / ' . $str;
            return mb_substr($str, 0, (mb_strlen($str, 'UTF-8') - 3), 'UTF-8');
        }
        return 'Нет';
    }

    /**
     * Определить выбранное подразделение из данных формы
     * @param $post array - массив данных формы
     * @param $prefix - префикс в имени элемента перед его идетификатором (порядковым номером пидчиненности списка)
     * @param bool $onlylast - выдать как результат последнее (дочерний) выбранное подразделение (да, нет)
     * @return int - идентификатор подразделения
     */
    public static function getSelectedUnit ($post, $prefix, $onlylast = false)
    {
        $units = [];
        foreach ($post as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $index = intval(substr($key, strlen($prefix)));
                $units[$index] = intval($value);
            }
        }

        if ($onlylast)
            return $units[count($units) - 1];
        else {
            $unit = 0;
            for ($i = (count($units) - 1); $i > -1; $i--) {
                if ($units[$i] !== -1) {
                    $unit = $units[$i];
                    break;
                }
            }
            return $unit;
        }
    }

    /**
     * Получить HTML элементы для выбора подразделения
     * @param $struct - дерево подразделений
     * @param $id - выбранное подразделение
     * @return array
     */
    public static function getSelectUnitArray ($struct, $id = 0) {
        global $STRUCT_DATA;

        $struct_data = $STRUCT_DATA;
        $struct_data[0] =  ['id' => 0, 'title' => 'Росгвардия'];

        $return = [
            0 => [['id' => 0, 'title' => 'Росгвардия', 'selected' => true]]
        ];

        if ($id === 0) {
            $path = [0];
        } else {
            $path = self::getPath($id, $struct);
            $path = System::aroundArray($path);
        }

        $struct_list = self::convertUnitsTreeAllToList($struct);
        array_unshift($struct_list, 0);

        foreach ($path as $unit) {
            if (in_array($unit, $struct_list)) {
                $children = self::getChildrenFromTree($struct, $unit);
                if (count($children)) {
                    $data = [['id' => -1, 'title' => '', 'selected' => false]];
                    foreach ($children as $child) {
                        $selected = in_array($child, $path);
                        $data[] = ['id' => $child, 'title' => $STRUCT_DATA[$child]['title'], 'selected' => $selected];
                    }
                    $return[] = $data;
                }
            }
        }

        return $return;
    }

    /**
     * Получить HTML представление списка выбора подразделения
     * @param $struct - дерево подразделений
     * @param int $id - выбранное подразделение
     * @param null $prefix
     * @param null $class
     * @param array $attr - массив произвольных атрибутов по типу ['onclick' => 'form_submit()' , ...]
     * @return string - html
     */
    public static function getSelectUnitHtml ($struct, $id = 0, $prefix = null, $class = null, $attr = [])
    {
        $html = '';
        $list = \OsT\Unit::getSelectUnitArray($struct, $id);
        foreach ($list as $key => $item)
            $html .= System::getHtmlSelect($item, $prefix . $key, $class, $attr);
        return $html;
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
     * @param null $class - атрибут class для родительскогоо блока
     * @example null - класс по-умолчанию
     *          'class_name' - произвольный класс + класс по-умолчанию
     *
     * @return string - html разметка подразделения
     */
    public static function getHtml ($id, $title, $buttons = true, $body = null, $class = null)
    {
        global $STRUCT_DATA;

        $class_str = $class === null ? 'unitBox' : 'unitBox ' . $class;

        // Обработка кнопок
        $buttons_html = '';
        if ($buttons === true) {
            $buttons_array = [
                ['class' => 'new', 'title' => 'Добавить', 'function' => 'unit_new_window_show(' . $id . ')'],
                ['class' => 'move', 'title' => 'Переместить', 'function' => 'unit_move_window_show(' . $id . ')'],
                ['class' => 'edit', 'title' => 'Изменить', 'function' => 'unit_edit_window_show(' . $id . ')'],
                ['class' => 'delete', 'title' => 'Удалить', 'function' => 'unit_delete(' . $id . ')'],
            ];
        } elseif ($buttons === false)
            $buttons_array = [];
        else $buttons_array = $buttons;

        if (count($buttons_array)) {
            foreach ($buttons_array as $button)
                $buttons_html .= '<div class="slideButton ' . $button['class'] . '" onclick="' . $button['function'] . '">' . $button['title'] . '</div>';
        }

        $body_html = '';
        if ($body === null) {
            $struct = self::getChildren($id);
            if (count($struct))
                foreach ($struct as $item)
                    $body_html .= self::getHtml($STRUCT_DATA[$item]['id'], $STRUCT_DATA[$item]['title'], $buttons, null, $class);
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
     * @param $id - идентификатор подразделения
     * @return bool
     */
    public static function _delete ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['id = ', $id]]);
        return true;
    }

    /**
     * Удалить запись из БД c последующим удалением зависимых данных
     * @param $id - идентификатор подразделения
     */
    public static function _deleteWithDependencies ($id)
    {
        global $STRUCT_TREE;
        global $DB;
        $units = self::getChildrenUnitsTree ($STRUCT_TREE, $id);
        $units = self::convertUnitsTreeAllToList($units);
        $units[] = $id;

        foreach ($units as $unit)
            State::_deleteByUnitWithDependencies($unit);

        $DB->_deleteArray(self::TABLE_NAME, $units);
    }

    /**
     * Удалить все записи из БД
     */
    public static function _deleteAll ()
    {
        global $DB;
        $DB->query('DELETE FROM ' . self::TABLE_NAME);
    }

    /**
     * Удалить все записи из БД c последующим удалением зависимых данных
     */
    public static function _deleteAllWithDependencies ()
    {
        self::_deleteAll();
        State::_deleteAllWithDependencies();
        User::_clearSettingsAll();
    }

}