<?php

namespace OsT\Serviceload;

use OsT\Base\System;
use PDO;

/**
 * Управление шаблонами службы
 * Class Mask
 * @package OsT\Serviceload
 * @version 2022.03.10
 *
 * __construct              Mask constructor
 * getData                  Получить массив данных из БД
 * getDataDecryption        Добавить в существующий массив выходных данных из getData расшифровку data (обязательное наличие)
 * getArrayByUser           Получить массив идентификаторов шаблонов пользователя
 * genHtmlItem              Сформировать HTML представление шаблона для таблицы шаблонов
 * genHtmlRageListItem      Сформировать HTML представление объекта древа области действия шаблона
 * genHtmlRageList          Сформировать HTML представление древа области действия шаблона
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * update                   Обновить запись в БД
 * _update                  Обновить запись в БД
 * delete                   Удалить запись из БД
 * _delete                  Удалить запись из БД
 * _deleteByUser            Удалить шаблоны пользователя
 * _deleteAll               Удалить все записи из БД
 *
 */
class Mask
{
    const TABLE_NAME = 'ant_serviceload_mask';

    public $id;                     // Идентфиикатор шаблона
    public $user;                   // Идентификатор пользователя, который создал шаблон
    public $title;                  // Наименование
    public $data;                   // Настройки шаблона в формате [атибут] = значение
    public $enabled;                // Включение (1) либо отключение (0) шаблона

    public $workability = false;    // Работоспособность объекта

    /**
     * Mask constructor.
     * @param $id
     */
    public function __construct( $id )
    {
        $data = self::getData([$id], [
            'id',
            'user',
            'title',
            'data',
            'enabled'
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
     *      id              -   Идентификатор шаблона
     *      user            -   Идентификатор пользователя, который создал шаблон
     *      title           -   Наименование
     *      data_json       -   Настройки шаблона в формате JSON
     *      data            -   Настройки шаблона в формате [атибут] = значение
     *      enabled         -   Включение (1) либо отключение (0) шаблона
     *      enabled_str     -   Включение (checked) либо отключение шаблона для checkbox
     *
     */
    public static function getData($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT *
            FROM   ant_serviceload_mask
            ' . $in_arr_sql);
        $q->execute();
        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор шаблона */
                $objectData['id'] = intval($item['id']);

                /* Идентификатор пользователя, который создал шаблон */
                if (System::chColum('user', $colums))
                    $objectData['user'] = intval($item['user']);

                /* Наименование */
                if (System::chColum('title', $colums))
                    $objectData['title'] = stripslashes($item['title']);

                /* Настройки шаблона в формате JSON */
                if (System::chColum('data_json', $colums))
                    $objectData['data_json'] = $item['data'];

                /* Настройки шаблона в формате [атибут] = значение */
                if (System::chColum('data', $colums))
                    $objectData['data'] = json_decode($item['data'], true);

                /* Включение (1) либо отключение (0) шаблона */
                if (System::chColum([
                    'enabled',
                    'enabled_str'
                ], $colums))
                    $objectData['enabled'] = intval($item['enabled']);

                /* Включение либо отключение шаблона для checkbox */
                if (System::chColum('enabled_str', $colums))
                    $objectData['enabled_str'] = $objectData['enabled'] ? 'checked' : '';

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
     * Добавить в существующий массив выходных данных из getData расшифровку data (обязательное наличие)
     *  Генерирует в data:  type_str
     *                      from_str
     *                      length_str
     *                      place_str
     *                      incoming_str
     * @param $masks - массив выходных данных из getData
     */
    public static function getDataDecryption (&$masks)
    {
        // Сформировать массив идентфикаторов мест службы
        $places_id = [];
        foreach ($masks as $mask)
            if (isset($mask['data']['place']))
                $places_id[$mask['data']['place']] = $mask['data']['place'];

        // Запросись данные о необходимых местах службы из базы
        $places = Place::getData($places_id, ['title']);

        // Сформировать массив подтипов службы
        $serviceload_subtypes = Type::getData([Schedule::TYPE_NARYAD], ['sub_types']);
        $serviceload_subtypes = $serviceload_subtypes[Schedule::TYPE_NARYAD]['sub_types'];

        foreach ($masks as $key => $mask) {
            $masks[$key]['data']['type_str'] =      @$serviceload_subtypes[$mask['data']['type']];
            $masks[$key]['data']['from_str'] =      @System::i2d($mask['data']['from']) . ':00';
            $masks[$key]['data']['length_str'] =    @System::i2d($mask['data']['len']) . 'ч.';
            $masks[$key]['data']['place_str'] =     @$places[$mask['data']['place']]['title'];
            $masks[$key]['data']['incoming_str'] =  @System::i2d($mask['data']['incoming']) . ':00';
        }
    }

    /**
     * Получить массив идентификаторов шаблонов пользователя
     * @param $user - идентфикатор пользователя
     * @return array
     */
    public static function getArrayByUser ($user)
    {
        global $DB;

        $arr = [];
        $q = $DB->prepare('
            SELECT  id
            FROM    ant_serviceload_mask
            WHERE   user = :user');
        $q->execute(['user' => $user]);
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item)
                $arr[] = intval($item['id']);
        }

        return $arr;
    }

    /**
     * Сформировать HTML представление шаблона для таблицы шаблонов
     * страница mask
     * @param $data - массив данных о шаблоне
     * @return string - HTML представление
     */
    public static function genHtmlItem ($data)
    {
        return '<tr>
            <td><input type="checkbox" onchange="maskEnable(this, ' . $data['id'] . ');" class="chb_enabled" ' . $data['enabled_str'] . '></td>
            <td>' . $data['title'] . '</td>
            <td>' . $data['data']['type_str'] . '</td>
            <td>' . $data['data']['incoming_str'] . '</td>
            <td>' . $data['data']['from_str'] . '</td>
            <td>' . $data['data']['length_str'] . '</td>
            <td>' . $data['data']['place_str'] . '</td>

            <td>
                <div class="buttonsBox">
                    <a class="button edit" href="mask_edit.php?id=' . $data['id'] . '" title="Изменить"></a>
                    <a class="button delete" href="mask.php?delete=' . $data['id'] . '" title="Удалить"></a>
                </div>
            </td>
        </tr>';
    }

    /**
     * Сформировать HTML представление объекта древа области действия шаблона
     * @param $id - имя объекта по типу m_1
     * @param $class - наименование класса
     * @param $title - текст по типу Иванов И.И. либо Мастерская
     * @param bool $enabled - включение / отключение элемента checkbox
     * @param null $content - содержимое верки
     * @param false $opened - указывает на то, развернут ли список
     * @return string
     */
    public static function genHtmlRageListItem ($id, $class, $title, $enabled = true, $content = null, $opened = false)
    {
        $enabled = $enabled ? 'checked' : '';
        $opened = $opened ? 'style="display:block;"' : '';
        $content = ($content !== null && trim($content) !== '') ? '<div class="structItemBody" ' . $opened . '>' . $content . '</div>' : '';
        return '<div class="structItemBox">
                    <input type="checkbox" class="' . $class . '" onclick="rageCheck(this);" name="' . $id . '" ' . $enabled . '>
                    <span onclick="structOpenList(this)">' . $title . '</span>
                    ' . $content . '
                </div>';
    }

    /**
     * Сформировать HTML представление древа области действия шаблона
     * @param $units - древо подразделений по типу $STRUCT_TREE
     * @param array $rage - выбранные военнослужащие
     * @return string
     */
    public static function genHtmlRageList ($units, $rage = [])
    {
        global $STRUCT_DATA;
        $html = '';
        foreach ($units as $id => $value) {
            if (is_array($value))
                $content = self::genHtmlRageList($value, $rage);
            else {
                $content = '';
                $military_arr = \OsT\Military\Military::getByUnit($id, System::time(), false, true);
                if (count($military_arr)) {
                    $military_data = \OsT\Military\Military::getData($military_arr, ['id', 'fio_short']);
                    foreach ($military_data as $key => $military) {
                        $enabled = in_array($military['id'], $rage);
                        $content .= self::genHtmlRageListItem('m_' . $military['id'], 'rage_m', $military['fio_short'], $enabled);
                    }
                }
            }
            $html .= self::genHtmlRageListItem('u_' . $id, 'rage_u',  $STRUCT_DATA[$id]['title'], false, $content);
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
     * Удалить шаблоны пользователя
     * @param $id
     * @return bool
     */
    public static function _deleteByUser ($id)
    {
        global $DB;
        return $DB->_delete(self::TABLE_NAME, [['user = ', $id]]);
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