<?php

namespace OsT\Military;

use OsT\Base\System;
use OsT\Unit;
use PDO;

/**
 * Звания
 * Class State
 * @package OsT\Military
 * @version 2022.03.10
 *
 * __construct                          State constructor
 * getData                              Получить массив данных из БД
 * getCurrentlyMilitaryStates           Получить данные о занимаемых на момент времени $time должностях воленнослужащим $military (как временно, так и постоянно)
 * getMilitaryUnit                      Получить идентификатор подразделения военнослужащего
 * getSelectUnitMilitaryHtml            Получить HTML представление списка выбора военнослужащего предвалительно выбрав его подразделение
 * checkTimePointCrossing               Проверить, находится ли момент времени $time в диапазоне действия доложности
 * checkTimeIntervalCrossing            Проверить, пересекается ли интервал времени с диапазоном действия доложности
 * checkTimeIntervalCrossingState       Проверить наличие пересечения интевлала времени с диапазоном действия как минимум одной занимаемой должности
 * getTableItemHtml                     Получить html представление для таблицы управления послужным списком на странице military_edit
 * parseTempTableData                   Парсинг данных из таблицы временно добавленных записей в послужном списке на странице military_edit
 * getFormDataFromTempTableData         Сформировать данные для формы редактирования элемента послужного списка
 * checkTempCrossing                    Проверка пересечения временных диапазонов вводимых в форму данных с данными из временной таблицы послужного списка
 * checkDBCrossing                      Проверка пересечения временных диапазонов вводимых в форму данных с данными из базы данных послужного списка
 * checkFormData                        Проверка данных о должности (добавление и редактирование) посредством AJAX
 * getDataByState                       Запросить данные о всех военнослужащих на должности state
 * getMilitaryByState                   Получить идентификатор военнослужащего (а также date_from, date_to, vrio) который находится на должности $state в момент времени $time
 * getIdArrByMilitary                   Получить массив идентификаторов записей о должностях военнослужащего
 * getIdArrByState                      Получить массив идентификаторов записей о назначении на должность $state
 * parseFormData                        Подготовка данных из формы управления должностью военнослужащего
 * parseFormDate                        Преобразовать данные о дате из формы ввода данных о должности в обрабатываемый формат
 * dateToStr                            Преобразовать дату (начало либо конец периода) в текст
 * parseFormUnit                        Определить последний выбранный Unit из пути подразделений посредсвом элементов select
 * vrio_getString                       Преобразовать значение ВРИО в тест
 * vrio_getArr                          Получить массив вариантов типа постановки на должность
 * filterStatesArrByVrio                Отфильтровать массив занимаемых должностей, оставив лишь те, Врио которых соответствует запрашиваемому
 * checkTimeIntervalFullCrossing        Проверить работает ли военнослужащий с $from по $to
 * getWorkDays                          Сформировать массив дней, в которые военнослужащий пребывает на службе (назначен на должность)
 * getWorkDaysByMonth                   Сформировать массив дней месяца, в которые военнослужащий пребывает на службе (назначен на должность)
 *
 * last                                 Получить идентификатор (id) последней запсии в таблице
 * count                                Получить количество записей в таблице
 * insert                               Добавить забись в БД
 * insertArray                          Добавить массив забисей в БД
 * update                               Обновить запись в БД
 * _update                              Обновить запись в БД
 * delete                               Удалить запись из БД
 * _delete                              Удалить запись из БД
 * deleteWithDependencies               Удалить запись из БД с последующим изменением служебной нагрузки военнослужащего
 * _deleteByMilitary                    Удалить записи военнослужащего из БД
 * _deleteByMilitaryWithDependencies    Удалить записи военнослужащего из БД с последующим изменением служебной нагрузки и удалением периодов отсутствия
 * _deleteByState                       Удалить записи по атрибуту state
 * _deleteByStateWithDependencies       Удалить записи по атрибуту state с последующим удалением зависимостей
 * _deleteAll                           Удалить все записи из БД
 * _deleteAllWithDependencies           Удалить все записи из БД с последующим изменением служебной нагрузки и удалением периодов отсутствия
 *
 */
class State
{

    const TABLE_NAME = 'military_state';

    public $id;                         // Идентификатор
    public $military;                   // Военнослужащий
    public $state;                      // Должность
    public $date_from;                  // Дата назначения
    public $date_to;                    // Дата окончания
    public $vrio;                       // Тип назначения, временно либо постоянно
    public $contract;                   // Тип службы, по контракту либо срочная служба (раздел в разработке)

    public $workability = false;        // Работоспособность объекта

    /**
     * State constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $data = self::getData([$id], [
            'id',
            'military',
            'state',
            'date_from',
            'date_to',
            'vrio'
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
     *      id                          -   Идентификатор
     *      military                    -   Идентификатор военнослужащего
     *      unit                        -   Идентификатор подразделения
     *      unit_path_str               -   Путь к подразделению
     *      state                       -   Идентификатор должности
     *      state_title                 -   Наименование должности
     *      state_title_abbreviation    -   Аббревиатура должности
     *      date_from                   -   Дата заступления на должность в формате Unix
     *      date_from_str               -   Дата заступления на должность в формате текста (Не указано / 01.01.2022)
     *      date_to                     -   Дата снятия с должности в формате Unix
     *      date_to_str                 -   Дата снятия с должности в формате текста (Не указано / 01.01.2022)
     *      vrio                        -   Индекс ВРИО (0 / 1)
     *      vrio_str                    -   Индекс ВРИО в виде текста (Постоянно / Временно)
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE ms.id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT ms.id,
                   ms.military,
                   ms.state,
                   us.title as state_title,
                   us.title_abbreviation as state_title_abbreviation,
                   us.unit,
                   ms.date_from,
                   ms.date_to,
                   ms.vrio
            FROM   military_state ms
            LEFT JOIN unit_state us ON us.id = ms.state
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Идентификатор военнослужащего */
                if (System::chColum('military', $colums))
                    $objectData['military'] = intval($item['military']);

                /* Идентификатор подразделения */
                if (System::chColum([
                    'unit',
                    'unit_path_str',
                ], $colums))
                    $objectData['unit'] = intval($item['unit']);

                /* Путь к подразделению */
                if (System::chColum('unit_path_str', $colums))
                    $objectData['unit_path_str'] = Unit::getPathStr($objectData['unit']);

                /* Идентификатор должности */
                if (System::chColum('state', $colums))
                    $objectData['state'] = intval($item['state']);

                /* Наименование должности */
                if (System::chColum('state_title', $colums))
                    $objectData['state_title'] = stripslashes($item['state_title']);

                /* Аббревиатура должности */
                if (System::chColum('state_title_abbreviation', $colums))
                    $objectData['state_title_abbreviation'] = stripslashes($item['state_title_abbreviation']);

                /* Дата заступления на должность в формате Unix */
                if (System::chColum([
                    'date_from',
                    'date_from_str',
                ], $colums))
                    $objectData['date_from'] = intval($item['date_from']);

                /* Дата заступления на должность в формате текста (Не указано / 01.01.2022) */
                if (System::chColum('date_from_str', $colums))
                    $objectData['date_from_str'] = self::dateToStr('from', $objectData['date_from']);

                /* Дата снятия с должности в формате Unix */
                if (System::chColum([
                    'date_to',
                    'date_to_str',
                ], $colums))
                    $objectData['date_to'] = intval($item['date_to']);

                /* Дата снятия с должности в формате текста (Не указано / 01.01.2022) */
                if (System::chColum('date_to_str', $colums))
                    $objectData['date_to_str'] = self::dateToStr('to', $objectData['date_to']);

                /* Индекс ВРИО (0 / 1) */
                if (System::chColum([
                    'vrio',
                    'vrio_str',
                ], $colums))
                    $objectData['vrio'] = intval($item['vrio']);

                /* Индекс ВРИО в виде текста (Постоянно / Временно) */
                if (System::chColum('vrio_str', $colums))
                    $objectData['vrio_str'] = self::vrio_getString($objectData['vrio']);

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
     * Получить данные о занимаемых на момент времени $time должностях воленнослужащим $military (как временно, так и постоянно)
     * @param $military - идентификатор военнослужащего
     * @param null $time - момент времени в формате Unix (null - текущее время)
     * @param null $states_data - массив данных о всех должностях военнослужащего
     * @return array
     *          [] - не занимает ни одной должности
     *
     *          ['always'       - данные о должности, которую военнослужащий занимает на постоянной основе
     *              [ ... ]
     *           'temp'         - данные о должности, которую военнослужащий занимает на временной основе
     *              [ ... ]
     *          ]
     */
    public static function getCurrentlyMilitaryStates ($military, $time = null, $states_data = null)
    {
        $return = [];
        if ($states_data === null) {
            $states = State::getIdArrByMilitary($military);
            $states_data = State::getData( $states,
                [   'unit',
                    'unit_path_str',
                    'state',
                    'state_title',
                    'vrio',
                    'date_from',
                    'date_to'
                ]);
        }
        if (count($states_data)) {
            if ($time === null)
                $time = System::time();
            foreach ($states_data as $state) {
                if (self::checkTimePointCrossing($time, $state['date_from'], $state['date_to'])) {
                    if ($state['vrio'])
                        $return['temp'] = $state;
                    else $return['always'] = $state;
                }
            }
        }

        return $return;
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
    public static function getMilitaryUnit ($military, $time = null, $states_data = null)
    {
        if ($time === null)
            $time = System::time();

        if ($states_data === null) {
            $states_data = State::getIdArrByMilitary($military);
            $states_data = State::getData( $states_data,
                [   'unit',
                    'vrio',
                    'date_from',
                    'date_to'
                ]);
        }

        if (count($states_data)) {
            foreach ($states_data as $state) {
                if (self::checkTimePointCrossing($time, $state['date_from'], $state['date_to'])) {
                    if (!$state['vrio'])
                        return $state['unit'];
                }
            }
        }

        return null;
    }

    /**
     * Получить HTML представление списка выбора военнослужащего предвалительно выбрав его подразделение
     * по типу unit -> unit -> military
     * @param $tree - дерево подразделений
     * @param int $unit - идентификатор выбранного подразделения (не используется, если указан $military)
     * @param null $military - идентификатор выбранного военнослужащего
     * @param string $unit_prefix - префикс имени для элементов select выбора подразделения
     * @param string $military_name - имя элемента select выбора военнослужащего
     * @param string $military_title - ключ данных из Military::getData, которые будут отображаться в option
     * @return string - HTML представление
     */
    public static function getSelectUnitMilitaryHtml ($tree, $unit = 0, $military = null, $unit_prefix = 'unit_', $military_name = 'military', $military_title = 'fio')
    {
        if ($military !== null)
            $unit = self::getMilitaryUnit($military);

        $html =  Unit::getSelectUnitHtml($tree, $unit, $unit_prefix);
        $children = \OsT\Unit::getChildren($unit);
        if (!count($children)) {
            $militaries = Military::getByUnit($unit);
            if (count($militaries)) {
                $militaries_data = Military::getData($militaries, [
                        $military_title
                    ]);
                $item = [];
                foreach ($militaries_data as $id => $data) {
                    $tmp = [
                        'id' => $id,
                        'title' => $data[$military_title]
                    ];
                    if ($military === $id)
                        $tmp['selected'] = 'selected';
                    $item[] = $tmp;
                }

            } else $item = [[
                'id' => -1,
                'title' => ''
            ]];
            $html .= System::getHtmlSelect($item, $military_name);
        }

        return $html;
    }

    /**
     * Проверить, находится ли момент времени $time в диапазоне действия доложности
     * @param $time - мемент времени в формате Unix
     * @param $from - начало диапазона действия должностив формате Unix (из базы)
     * @param $to   - конец диапазона действия должностив формате Unix (из базы)
     * @return bool
     */
    public static function checkTimePointCrossing ($time, $from, $to)
    {
        if ($to === 0)
            $to = TIME_LAST_SECOND;
        else $to += System::TIME_DAY - 1;
        return System::intervalCrossing($time, $time, $from, $to);
    }

    /**
     * Проверить, пересекается ли интервал времени с диапазоном действия доложности
     * @param $time_from - начало интервала врмени в формате Unix
     * @param $time_to - конец интервала врмени в формате Unix
     * @param $from - начало диапазона действия должностив формате Unix (из базы)
     * @param $to   - конец диапазона действия должностив формате Unix (из базы)
     * @return bool
     */
    public static function checkTimeIntervalCrossing ($time_from, $time_to, $from, $to)
    {
        if ($to === 0)
            $to = TIME_LAST_SECOND;
        else $to += System::TIME_DAY - 1;
        return System::intervalCrossing($time_from, $time_to, $from, $to);
    }

    /**
     * Проверить наличие пересечения интевлала времени с диапазоном действия как минимум одной занимаемой должности
     * @param $states - массив данных о должности с минимальным набором данных ['date_from', 'date_to'] при значении $multi = false
     *                  либо массив таких массивов данных при значении $multi = true
     * @param $from - начало интервала врмени в формате Unix
     * @param $to - конец интервала врмени в формате Unix
     * @param bool $multi - указывает на тип массива $states
     *              true - массив $states содержит множество должностей
     *              false - массив $states содержит одну запись
     * @return bool - наличие пересечения интевлала времени с диапазоном действия как минимум одной занимаемой доложности
     */
    public static function checkTimeIntervalCrossingState ($states, $from, $to, $multi = true)
    {
        if (!$multi)
            $states = [$states];
        foreach ($states as $state) {
            $state['date_from'] = intval($state['date_from']);
            $state['date_to'] = intval($state['date_to']);
            if (self::checkTimeIntervalCrossing($from, $to, $state['date_from'], $state['date_to']))
                return true;
        }
        return false;
    }

    /**
     * Получить html представление для таблицы управления послужным списком на странице military_edit
     *
     * @param $data [                           - массив данных о звании
     *                  'index',                - порядковый номер должности
     *                  'unit_path_str',        - полный путь к подразделению
     *                  'unit',                 - идентификатор подразделения
     *                  'state_title'           - наименование должности
     *                  'state'                 - идентификатор должности
     *                  'vrio_str'              - тип постановки в текстовом представлении
     *                  'vrio'                  - тип постановки, где 0 - постоянная, а 1 - временная
     *                  'date_from_str'         - дата начала службы в формате 01.01.2001
     *                  'date_from'             - дата начала службы в формате Unix
     *                  'date_to_str'           - дата окончания службы в формате 01.01.2001
     *                  'date_to'               - дата окончания службы в формате Unix
     *
     * @return string - html представление для таблицы управления послужным списком на странице military_edit
     */
    public static function getTableItemHtml ($data)
    {
        return '<tr data-index="' . $data['index'] . '" class="state_' . $data['index'] . '">
                    <td>' . $data['unit_path_str'] . '</td>
                    <td>' . $data['state_title'] . '</td>
                    <td>' . $data['date_from_str'] . '</td>
                    <td>' . $data['date_to_str'] . '</td>
                    <td>' . $data['vrio_str'] . '</td>
                    <td class="table_td_buttons">
                        <div class="buttonsBox">
                            <div class="button edit" onclick="state_edit_show(' . $data['index'] . ');" title="Изменить"></div>
                            <div class="button delete" onclick="state_delete(' . $data['index'] . ');" title="Удалить"></div>
                        </div>
                        <input class="dtunit" type="hidden" name="state_unit' . $data['index'] . '" value="' . $data['unit'] . '">
                        <input class="dtstate" type="hidden" name="state_state' . $data['index'] . '" value="' . $data['state'] . '">
                        <input class="dtvrio" type="hidden" name="state_vrio' . $data['index'] . '" value="' . $data['vrio'] . '">
                        <input class="dtdate_from" type="hidden" name="state_date_from' . $data['index'] . '" value="' . $data['date_from'] . '">
                        <input class="dtdate_to" type="hidden" name="state_date_to' . $data['index'] . '" value="' . $data['date_to'] . '">
                    </td>
                </tr>';
    }

    /**
     * Парсинг данных из таблицы временно добавленных записей в послужном списке на странице military_edit
     * @param $data - массив данных из таблицы временных записей
     * @return array - сгрупированные данные о каждом из объектов таблицы
     *              по типу [ 1 .. N ] [key => val],
     *              где [ 1 .. N ] - первый уровень массива с целочисленным индексом (порядковым номером) объекта таблицы
     *              [key => val]   - второй уровень массива, где key - ключ типа "unit", а val - значение типа 15
     */
    public static function parseTempTableData ($data) {
        $return = [];
        $indexes = System::getIdFromInputName($data, 'state_unit');
        if (count($indexes)) {
            foreach ($indexes as $index) {
                $return [$index] = [
                    'unit' => intval($data['state_unit' . $index]),
                    'state' => intval($data['state_state' . $index]),
                    'vrio' => intval($data['state_vrio' . $index]),
                    'date_from' => intval($data['state_date_from' . $index]),
                    'date_to' => intval($data['state_date_to' . $index])
                ];
            }
        }
        return $return;
    }

    /**
     * Сформировать данные для формы редактирования элемента послужного списка
     * Страница military_edit
     * @param $data - данные о назначении из временной таблицы послежного списка
     * @return string[]     - массив данных для формы
     *          'unit'      - html представление всех элементов select unit
     *          'state'     - html представление select state
     *          'vrio'      - целочисельное значение типа постановки на должность
     *          'date_from' - дата начала в формате 01/01/2001
     *          'date_to'   - дата окончания в формате 01/01/2001
     */
    public static function getFormDataFromTempTableData ($data) {
        // Подразделение
        $return = [
            'unit' => ''
        ];
        $children = [];
        $path = Unit::getPath($data['unit']);
        $path = System::aroundArray($path);

        $children[] = Unit::getChildren(0);
        foreach ($path as $unit) {
            $tmp = Unit::getChildren($unit);
            if (count($tmp))
                $children[] = $tmp;
        }

        $children_list  = [];
        foreach ($children as $item)
            $children_list = array_merge($children_list, $item);
        $children_data = Unit::getData($children_list, ['id', 'title']);

        $unit_index = 0;
        foreach ($children as $child) {
            $tmp = [];
            foreach ($child as $unit) {
                if (in_array($unit, $path))
                    $children_data[$unit]['selected'] = true;
                $tmp[] = $children_data[$unit];
            }
            $return['unit'] .=
                '<select class="new_state_unit" onchange="state_select_unit_change(this, \'' . 'new' . '\')" data-index="' . $unit_index . '" name="unit' . $unit_index . '">' .
                    '<option value="0">- Не выбрано -</option>' .
                    System::getHtmlSelectOptions($tmp) .
                '</select>';

            $unit_index++;
        }

        // Должность
        $state_list = \OsT\State::getDataByUnit($data['unit'], ['id', 'title']);
        $state_list[$data['state']]['selected'] = true;
        $return['state'] =
            '<select id="new_state_state" name="state">' .
                '<option value="0">- Не выбрано -</option>' .
                System::getHtmlSelectOptions($state_list) .
            '</select>';

        // Прочие данные
        $return['vrio'] = $data['vrio'];
        $return['date_from'] = $data['date_from'] ? System::parseDate($data['date_from'], 'd/m/y') : '';
        $return['date_to'] = $data['date_to'] ? System::parseDate($data['date_to'], 'd/m/y') : '';

        return $return;
    }

    /**
     * Проверка пересечения временных диапазонов вводимых в форму данных с данными из временной таблицы послужного списка
     * на странице military_edit
     * @param $tmp_data - массив временных данных из таблицы после форматирования функцией parseTempTableData
     * @param $form_data - массив вводимых пользователем данных о должности после форматирования функцией parseFormData
     * @return false|string
     *          false - все GooD
     *          string - код ошибки
     *              ERR_DATE_TMP_STATE_USED - военнослужащий уже занимает выбранную должность
     *              ERR_DATE_TMP_VRIO       - военнослужащий в указанном диапазоне времени занимает иную должность, но тип службы (постоянно / временно) дублируется
     */
    public static function checkTempCrossing ($tmp_data, $form_data) {
        if ($form_data['date_to'] === 0)
            $form_data['date_to'] = TIME_LAST_SECOND;

        foreach ($tmp_data as $tmp_item) {
            if ($tmp_item['date_to'] === 0)
                $tmp_item['date_to'] = TIME_LAST_SECOND;

            if (System::intervalCrossing($tmp_item['date_from'], $tmp_item['date_to'], $form_data['date_from'], $form_data['date_to'])) {
                if ($form_data['state'] === $tmp_item['state'])
                    return 'ERR_DATE_TMP_STATE_USED';
                else {
                    if ($form_data['vrio'] === $tmp_item['vrio'])
                        return 'ERR_DATE_TMP_VRIO';
                }
            }
        }

        return false;
    }

    /**
     * Проверка пересечения временных диапазонов вводимых в форму данных с данными из базы данных послужного списка
     * на странице military_edit
     * @param $db_data - массив данных из базы (таблица military_state)
     * @param $form_data - массив вводимых пользователем данных о должности после форматирования функцией parseFormData
     * @return false|string
     *          false - все GooD
     *          string - код ошибки
     *              ERR_DATE_DB_VRIO       - выбранная должность с данным типом службы (постоянно / временно) уже занята
     */
    public static function checkDBCrossing ($db_data, $form_data) {
        if ($form_data['date_to'] === 0)
            $form_data['date_to'] = TIME_LAST_SECOND;

        foreach ($db_data as $db_item) {
            if ($db_item['date_to'] === 0)
                $db_item['date_to'] = TIME_LAST_SECOND;

            if (System::intervalCrossing($db_item['date_from'], $db_item['date_to'], $form_data['date_from'], $form_data['date_to'])) {
                if ($form_data['vrio'] === $db_item['vrio'])
                    return 'ERR_DATE_DB_VRIO';
            }
        }

        return false;
    }

    /**
     * Проверка данных о должности (добавление и редактирование) посредством AJAX
     *  из формы добавления новой записи в послужной список на странице military_edit
     * @param $data - исходные данные из функции state_new_window_send или state_edit_window_send
     * @return array|string
     *      array - массив преобразованных данных
     *          date_from   - дата в формате UNIX начала службы на должности (0 - не определена)
     *          date_to     - дата в формате UNIX окончания службы на должности (0 - по настоящий момент)
     *          unit        - подразделение
     *          state       - должность
     *          vrio        - тип постановки на должность (0 - на постоянку, 1 - временно)
     *          index       - порядковый номер создаваемой / редактируемой записи
     *      string - код ошибки
     *          ERR_DATE_FROM               - неверный формат даты в поле date_from
     *          ERR_DATE_TO                 - неверный формат даты в поле date_to
     *          ERR_DATE_POS                - в поле date_to указано значение меньше чем в поле date_from
     *          ERR_UNIT                    - подразделение не выбрано либо содержит дочерние
     *          ERR_STATE                   - должность не выбрана
     *          ERR_DATE_TMP_STATE_USED     - военнослужащий уже занимает выбранную должность
     *          ERR_DATE_TMP_VRIO           - военнослужащий в указанном диапазоне времени занимает иную должность, но тип службы (постоянно / временно) дублируется
     *          ERR_DATE_DB_VRIO            - выбранная должность с данным типом службы (постоянно / временно) уже занята
     */
    public static function checkFormData ($data) {
        $index = intval($data['index']);
        $military = intval($data['military']);
        if ($data['key'] === 'new')
            $index++;
        $form_data = self::parseFormData($data);
        if (is_array($form_data)) {
            $form_data['index'] = $index;
            $tmp_data = [];
            $crossing_tmp = false;
            if (isset($data['tmp_data']))
                $tmp_data = self::parseTempTableData($data['tmp_data']);
            if (count($tmp_data)) {
                if ($data['key'] === 'edit')
                    unset($tmp_data[$index]);
                if (count($tmp_data))
                    $crossing_tmp = self::checkTempCrossing($tmp_data, $form_data);
            }
            if (!$crossing_tmp) {
                $states_list = self::getDataByState($form_data['state']);
                if (count($states_list)) {
                    if ($military) {
                        foreach ($states_list as $key => $item)
                            if ($item['military'] === $military)
                                unset($states_list[$key]);
                    }
                    if (count($states_list)) {
                        $crossing_db = self::checkDBCrossing($states_list, $form_data);
                        if ($crossing_db !== false)
                            return $crossing_db;
                    }
                }
            } else return $crossing_tmp;

            return $form_data;

        } else return $form_data;
    }

    /**
     * Запросить данные о всех военнослужащих на должности state
     * @param $state - идентификатор должности
     * @return array - массив данных о назначениях на должность
     */
    public static function getDataByState ($state) {
        $data = self::getData();
        if (count($data)) {
            foreach ($data as $key => $item) {
                if ($item['state'] !== $state)
                    unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Получить идентификатор военнослужащего (а также date_from, date_to, vrio) который находится на должности $state в момент времени $time
     * @param $state - идентификатор должности
     * @param null $time - момент времени в формате Unix, если null - текущее время
     * @param null $vrio - отфильтровать результаты по vrio (0, 1), если null - не фильтровать
     * @return array - массив данных о назначении на должность либо [], если нет данных
     */
    public static function getMilitaryByState ($state, $time = null, $vrio = null)
    {
        global $DB;

        if ($time === null)
            $time = System::time();

        $q = $DB->prepare('
            SELECT  military,
                    date_from,
                    date_to,
                    vrio
            FROM    military_state
            WHERE   state = ?
            ');
        $q->execute([$state]);
        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {
                $data[$key] = [
                    'military' => intval($item['military']),
                    'date_from' => intval($item['date_from']),
                    'date_to' => intval($item['date_to']),
                    'vrio' => intval($item['vrio'])
                ];
            }
            foreach ($data as $key => $item) {
                if (!self::checkTimePointCrossing($time, $item['date_from'], $item['date_to']))
                    unset($data[$key]);
                else {
                    if ($vrio !== null)
                        if ($vrio !== $item['vrio'])
                            unset($data[$key]);
                }
            }
            return $data;
        }
        return [];
    }

    /**
     * Получить массив идентификаторов записей о должностях военнослужащего
     * @param $military
     * @return array
     */
    public static function getIdArrByMilitary ($military)
    {
        global $DB;
        $arr = [];
        $q = $DB->prepare('
            SELECT id
            FROM   military_state
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
     * Получить массив идентификаторов записей о назначении на должность $state
     * @param $state - идентификатор должности state (unit_state->id)
     * @return array
     */
    public static function getIdArrByState ($state)
    {
        global $DB;
        $arr = [];
        $q = $DB->prepare('
            SELECT id
            FROM   military_state
            WHERE  state = ?');
        $q->execute([$state]);

        if ($q->rowCount()) {
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {
                $arr[] = intval($item['id']);
            }
        }

        return $arr;
    }

    /**
     * Подготовка данных из формы управления должностью военнослужащего,
     * проверка корректности заполнения полей, преобразование данных
     * к подходящему типу для последующих операций
     * @param $data - набор всех данных формы типа POST
     * @return array|string
     *      array - массив преобразованных данных
     *          date_from   - дата в формате UNIX начала службы на должности (0 - не определена)
     *          date_to     - дата в формате UNIX окончания службы на должности (0 - по настоящий момент)
     *          unit        - подразделение
     *          state       - должность
     *          vrio        - тип постановки на должность (0 - на постоянку, 1 - временно)
     *      string - код ошибки
     *          ERR_DATE_FROM   - неверный формат даты в поле date_from
     *          ERR_DATE_TO     - неверный формат даты в поле date_to
     *          ERR_DATE_POS    - в поле date_to указано значение меньше чем в поле date_from
     *          ERR_UNIT        - подразделение не выбрано либо содержит дочерние
     *          ERR_STATE       - должность не выбрана
     */
    public static function parseFormData ($data) {
        $return['date_from'] = self::parseFormDate($data['date_from']);
        if ($return['date_from'] !== false) {
            $return['date_to'] = self::parseFormDate($data['date_to']);
            if ($return['date_to'] !== false) {
                if ($return['date_to'] >= $return['date_from'] || $return['date_to'] === 0) {
                    $return['unit'] = self::parseFormUnit($data);
                    if ($return['unit']) {
                        $return['state'] = intval($data['state']);
                        if ($return['state']) {
                            $return['vrio'] = intval($data['vrio']);
                            return $return;
                        } else return 'ERR_STATE';
                    } else return 'ERR_UNIT';
                } else return 'ERR_DATE_POS';
            } else return 'ERR_DATE_TO';
        } else return 'ERR_DATE_FROM';
    }

    /**
     * Преобразовать данные о дате из формы ввода данных о должности в обрабатываемый формат
     * @param $date - дата в формате 01/01/2001
     * @return false|int
     *      false   - данные введены с ошибкой
     *      0       - граница периода не определена пользователем
     *      int     - дата в формате времени Unix
     */
    public static function parseFormDate ($date) {
        $date = trim($date);
        if ($date === '')
            return 0;
        else {
            $test = System::parseDate($date, 'unix', 'd/m/y');
            if ($test)
                return intval($test);
            else return false;
        }
    }

    /**
     * Преобразовать дату (начало либо конец периода) в текст
     * @param $type - тип даты, где from - начало, а to - конец
     * @param $time - дата в формате Unix
     * @return array|false|int|string|null
     */
    public static function dateToStr ($type, $time) {
        if (!$time)
            return ($type === 'from') ? 'Не указано' : 'Не указано';
        else return System::parseDate($time, 'd');
    }

    /**
     * Определить последний выбранный Unit из пути подразделений посредсвом элементов select
     * @param $data - данные POST, где искомые значения имеют имена unit0, 0 - родитель, далее +1
     * @return int - идентификатор последнего (дочернего) подразделения
     * @todo Сравнить с функцией в классе Unit :: getSelectedUnit
     * @todo Может есть смысл выбрать ту
     */
    public static function parseFormUnit ($data) {
        $index = System::getIdFromInputName ($data, 'unit');
        $last = max($index);
        return intval($data['unit' . $last]);
    }

    /**
     * Преобразовать значение ВРИО в тест
     * @param $value
     * @return string
     */
    public static function vrio_getString ($value) {
        $arr = self::vrio_getArr();
        return $arr[$value];
    }

    /**
     * Получить массив вариантов типа постановки на должность
     * @return string[]
     */
    public static function vrio_getArr () {
        return [
            0 => 'Постоянно',
            1 => 'Временно',
        ];
    }

    /**
     * Отфильтровать массив занимаемых должностей, оставив лишь те, Врио которых соответствует запрашиваемому
     * @param $states
     * @param $vrio
     * @return mixed
     */
    public static function filterStatesArrByVrio ($states, $vrio)
    {
        foreach ($states as $key => $item)
            if ($item['vrio'] !== $vrio)
                unset($states[$key]);
        return $states;
    }

    /**
     * Проверить работает ли военнослужащий с $from по $to
     * @param $states - массив данных о должностях
     *                  [ 0 => [
     *                               'state'        идентификатор должности
     *                               'vrio'         временное исполнение обязанностей
     *                               'date_from'    начало в формате Unix
     *                               'date_to'      конец в формате Unix
     *                          ]
     *                  , ... ]
     * @param $from - начало интервала времени в формате Unix
     * @param $to - конец интервала времени в формате Unix
     * @return boolean
     *              true - военнослужащий работает во все дни интервала времени
     *              false - военнослужащий не работает как минимум в один из дней
     */
    public static function checkTimeIntervalFullCrossing ($states, $from, $to)
    {
        $from = System::gettimeBeginDayFromTime($from);
        $to = System::gettimeBeginDayFromTime($to);
        $tmp_time = $from;
        $workdays = self::getWorkDays($states, $from, $to);
        while ($tmp_time <= $to) {
            $date = getdate($tmp_time);
            if (!isset($workdays[$date['year']][$date['mon']][$date['mday']]))
                return false;
            $tmp_time = System::plusDay($tmp_time);
        }
        return true;
    }

    /**
     * Сформировать массив дней, в которые военнослужащий пребывает на службе (назначен на должность)
     * @param $states - массив данных о должностях
     *                  [ 0 => [
     *                               'state'        идентификатор должности
     *                               'vrio'         временное исполнение обязанностей
     *                               'date_from'    начало в формате Unix
     *                               'date_to'      конец в формате Unix
     *                          ]
     *                  , ... ]
     * @param $from - начало интервала времени в формате Unix
     * @param $to - конец интервала времени в формате Unix
     * @return array - массив дней, в которые военнослужащий пребывает на службе
     *          [year => [
     *                  mon => [
     *                      1 => state_id,
     *                      5 => state_id,
     *                      6 => state_id,
     *                      ...
     *                  ]
     *              ]
     *          ]
     */
    public static function getWorkDays ($states, $from, $to)
    {
        $return = [];
        if (count($states)) {
            $states = self::filterStatesArrByVrio($states, 0);
            $from = System::gettimeBeginDayFromTime($from);
            $to = System::gettimeBeginDayFromTime($to);
            $tmp_time = $from;
            while ($tmp_time <= $to) {
                $date = getdate($tmp_time);
                foreach ($states as $key => $item) {
                    $state_from = $item['date_from'];
                    $state_to = $item['date_to'] ? $item['date_to'] : TIME_LAST_SECOND;
                    if (System::intervalCrossing($tmp_time, $tmp_time, $state_from, $state_to))
                        $return[$date['year']][$date['mon']][$date['mday']] = $item['state'];
                }
                $tmp_time = System::plusDay($tmp_time);
            }
        }
        return $return;
    }

    /**
     * Сформировать массив дней месяца, в которые военнослужащий пребывает на службе (назначен на должность)
     * @param $states - массив данных о должностях
     *                  [ 0 => [
     *                               'state'        идентификатор должности
     *                               'vrio'         временное исполнение обязанностей
     *                               'date_from'    начало в формате Unix
     *                               'date_to'      конец в формате Unix
     *                          ]
     *                  , ... ]
     * @param $year - год
     * @param $month - месяц
     * @param $days_count - количество дней в месяце
     * @return array - массив дней месяца, в которые военнослужащий назначен на должность
     *              [
     *                  1 => true,
     *                  5 => true,
     *                  6 => true,
     *                  ...
     *              ]
     */
    public static function getWorkDaysByMonth ($states, $year, $month, $days_count)
    {
        $days = [];
        if (count($states)) {
            $states = self::filterStatesArrByVrio($states, 0);
            foreach ($states as $key => $item) {
                $date_from = $item['date_from'];
                $date_to = $item['date_to'] ? $item['date_to'] : TIME_LAST_SECOND;
                for ($i = 1; $i <= $days_count; $i++) {
                    $time = strtotime($year . '-' . System::i2d($month) . '-' . System::i2d($i));
                    if (System::intervalCrossing($time, $time, $date_from, $date_to))
                        $days[$i] = true;
                }
            }
        }
        return $days;
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
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
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
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     * В противном случае возникнут проблемы с отображением графика нагрузки
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
     * Удалить запись из БД с последующим изменением служебной нагрузки военнослужащего
     */
    public function deleteWithDependencies ()
    {
        $this->delete();
        if ($this->vrio === 0) {
            $to = $this->date_to;
            if ($this->date_to === 0)
                $to = TIME_LAST_SECOND;

            $serviceload = \OsT\Serviceload\Military::getServiceload($this->military);
            $serviceload = \OsT\Serviceload\Military::_clearByInterval($serviceload, $this->date_from, $to);
            \OsT\Serviceload\Military::updateServiceload($this->military, $serviceload);
        }
    }

    /**
     * Удалить записи военнослужащего из БД
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
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
     * Удалить записи военнослужащего из БД с последующим изменением служебной нагрузки и удалением периодов отсутствия
     * @param $military
     * @return bool
     */
    public static function _deleteByMilitaryWithDependencies ($military)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['military = ', $military]]);
        \OsT\Serviceload\Military::_clear($military);
        Absent::_deleteByMilitary($military);
        return true;
    }

    /**
     * Удалить записи по атрибуту state
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
     * @param $state - идентификатор должности
     */
    public static function _deleteByState ($state)
    {
        global $DB;
        $DB->_delete(self::TABLE_NAME, [['state = ', $state]]);
    }

    /**
     * Удалить записи по атрибуту state с последующим удалением зависимостей
     * @param $state - идентификатор должности
     */
    public static function _deleteByStateWithDependencies ($state)
    {
        $states = self::getIdArrByState($state);
        if (count($states)) {
            foreach ($states as $item) {
                $tmp = new self($item);
                $tmp->deleteWithDependencies();
            }
        }
    }

    /**
     * Удалить все записи из БД
     *
     * ВНИМАНИЕ!
     * После удаления остается мусор в базе данных
     * Используйте только в сочитании с прочими функциями глобальной чистки
     * В противном случае возникнут проблемы с отображением графика нагрузки
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
     * Удалить все записи из БД с последующим изменением служебной нагрузки и удалением периодов отсутствия
     */
    public static function _deleteAllWithDependencies ()
    {
        self::_deleteAll();
        \OsT\Serviceload\Military::_clearAll();
        Absent::_deleteAll();
    }

}