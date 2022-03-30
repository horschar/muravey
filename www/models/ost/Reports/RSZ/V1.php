<?php

namespace OsT\Reports\RSZ;

use OsT\Base\System;
use OsT\Military\Military;
use OsT\Reports\Report;
use OsT\Serviceload\Schedule;
use OsT\Serviceload\Type;
use OsT\State;
use OsT\Unit;

/**
 * Class V1
 * @package OsT\Reports
 * @version 2022.03.12
 *
 * __construct                      Обработать массив параметров отчета $data
 * getSettings                      Получить массив настроек отчета
 * getDefaultSettings               Получить массив настроек отчета по-умолчанию
 * savePrintSettings                Сохранить настройки отчета в базе при добавлении в очередь печати
 * saveSettings                     Сохранить настройки из report_settings.php
 * getHtmlSettingsForm              Получить HTML представление элементов формы настроек отчета
 * getHeadMaskIndexes               Получить массив индексов доступных для отчета шапок
 * getSenderMaskIndexes             Получить массив индексов доступных для отчета типов указания отправителя
 * getSenderSourceIndexes           Получить массив индексов доступных для отчета типов источников данных отправителя
 * calculate                        Сформировать массив промежуточных данных для отчета
 * generate                         Сформировать HTML отчет версии
 * getHtmlPrintSettingsBox          Сгенерировать блок настроек при добавлении отчета в лист печати
 * calcPrintTableItemData           Обработка данных из блока настроек отчета при добавлении в лист печати
 *
 */
class V1 extends RSZ
{

    const REPORT_VERSION = '1';

    public $unit;           // Подразделение
    public $date;           // Дата, на которую формируется служебная нагрузка [unix|date|str]
    public $settings;       // Настройки отчета
    public $html = null;    // HTML представление отчета

    /**
     * V1 constructor.
     * @param $data [
     *          unit - подразделение
     *          date - дата, на которую формируется отчет Unix
     *          create - дата создания отчета Unix
     *          settings - настройки версии (не обязательный параметр)
     */
    public function __construct($data)
    {
        $this->unit = intval($data['unit']);
        $this->date['unix'] = intval($data['date']);
        $this->date['date'] = getdate($this->date['unix']);
        $this->date['str'] = System::parseDate($this->date['unix']);
        if (isset($data['settings']))
            $this->settings = $data['settings'];
        else $this->settings = $this->getSettings();
    }

    /**
     * Получить массив настроек отчета
     * @return array
     */
    public static function getSettings ()
    {
        global $USER;
        if (isset($USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION]))
            return $USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION];
        else return self::getDefaultSettings();
    }

    /**
     * Получить массив настроек отчета по-умолчанию
     * @return array
     */
    public static function getDefaultSettings()
    {
        /**
         * orientation                      ориентация страницы отчета (P - вертикальная, L - горизонтальная)
         * head                             идентификатор шаблона HTML разметки блока получателя
         * head_data                        набор данных для шаблона разметки блока получателя
         *      N                               идентификатор шаблона HTML разметки (head), для которого будет определен набор данных
         *                                      принимает занчение null (данные не требуются) либо [] (массив данных для каждого шаблона)
         *      1   text                            получатель (текст)
         * sender_mask                      идентификатор шаблона HTML разметки блока отправителя
         * sender_source                    идентификатор типа источника данных отправителя
         * sender_data                      пользовательский набор данных по типам источника отправителя
         *      N                               идентификатор шаблона HTML разметки (sender_mask), для которого будет определен набор данных
         *                                      принимает занчение null (данные не требуются) либо [] (массив данных по каждому из типов источников)
         *          N                               идентификатор типа источника данных (sender_source), для которого указаны данные
         *                                          принимает значение null (данные не требуются) либо [] (массив данных для источника)
         *          1   state                           должность отправителя (текст)
         *              level                           звание отправителя (текст)
         *              fio                             ФИО отправителя (текст)
         *          2   military                        идентификатор военнослужащего
         *          3   state                           идентификатор должности, по которой будет определен военнослужащий
         *              vrio                            true - если есть Врио на текущей должности - использовать его
         *                                              false - не использовать данные Врио даже если таков имеется
         * show_datecreate                  отображать дату создания отчета
         * font                             размер шрифта для вычисления относительных величин элементов в отчете
         * time_filter                      в контексте данного отчета указывает на время его формирования (час) и применяется для фильтра записей по времени
         *                                  Принимает значения:
         *                                      число - час от 0 до 23, в который военнослужащий должен находиться на рабочем месте
         *                                      массив типа ['from', 'to'] - диапазон вемени
         *                                  Данный фильтр применяется в сочетании с массивом colums -> N -> data -> N -> timefilter
         * data_in_empty_cells              заполнитель для пустых ячеек (символ)
         * show_row_unit                    отображать строку с названием запрашиваемого подразделения в таблице
         * show_row_summary                 отображать последнюю строку "итого"
         * show_col_number                  отображать столбец № п/п
         * show_col_actualquantity          отображать столбец По списку
         * show_col_data_actualquantity     отображать данные в столбце По списку
         * show_col_countbystate            отображать столбец По штату
         * show_col_data_countbystate       отображать данные в столбце По штату
         * colums                           массив столбцов таблицы
         *      N                               порядковый номер столбца (порядок вывода от меньшего к большему), идентификатор
         *          title                           наименование столбца
         *          data                            массив блоков фильтрации данных по типу служебной нагрузки
         *              N                               массив параметров фильтрации данных, если данные равны значениям в блоке - военнослужащий добавляется в данный столбец
         *                  servicetype                     тип служебной нагрузки
         *                  usertype                        подтип служебной нагрузки (только для типа "Служба")
         *                  time_filter                     массив параметров для фильтрации по времени (зависит от time_filter в корне настроек)
         *                      beforework                      указывает на то, учитывать ли при проверке время от прибытия на работу до начала работы
         *                      work                            рабочее время
         *                                                      для наряда - это момент от начала наряда до конца
         *                                                      для рабочего дня - от глобального начала рабочего дня (8ч) до его конца (18ч)
         */

        global $SETTINGS;
        return [
            'orientation' => 'L',
            'head' => 0,
            'head_data' => [
                0 => null
            ],
            'sender_mask' => 1,
            'sender_source' => 1,
            'sender_data' => [
                0 => null,
                1 => [
                    0 => null,
                    1 => [
                        'state' => 'Начальник',
                        'level' => 'звание',
                        'fio' => 'Иванов И.И.'
                    ],
                    2 => [
                        'military' => null
                    ],
                    3 => [
                        'state' => null,
                        'vrio' => true
                    ]
                ]
            ],
            'show_datecreate' => true,
            'font' => 16,
            'time_filter' => $SETTINGS['TIME_RECIEVE_DOC'],
            'data_in_empty_cells' => '',
            'show_row_unit' => true,
            'show_row_summary' => true,
            'show_col_number' => true,
            'show_col_actualquantity' => true,
            'show_col_data_actualquantity' => true,
            'show_col_countbystate' => false,
            'show_col_data_countbystate' => true,
            'colums' => [
                0 => [
                    'title' => 'На лицо',
                    'data' => [
                        0 => [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 1,
                            'time_filter' => [
                                'beforework' => true,
                                'work' => false
                            ]
                        ],
                        1 => [
                            'servicetype' => Type::RABOCHUI,
                            'time_filter' => [
                                'beforework' => true,
                                'work' => true
                            ]
                        ]
                    ]
                ],
                1 => [
                    'title' => 'Наряд',
                    'data' => [
                        0 => [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 1,
                            'time_filter' => [
                                'beforework' => false,
                                'work' => true
                            ]
                        ]
                    ]
                ],
                2 => [
                    'title' => 'Командировка',
                    'data' => [
                        0 => [
                            'servicetype' => Type::KOMANDIROVKA,
                        ]
                    ]
                ],
                3 => [
                    'title' => 'Отпуск',
                    'data' => [
                        0 => [
                            'servicetype' => Type::OTPUSK,
                        ]
                    ]
                ],
                4 => [
                    'title' => 'Больничный',
                    'data' => [
                        0 => [
                            'servicetype' => Type::BOLNICHNUI,
                        ]
                    ]
                ],
                5 => [
                    'title' => 'Военный госпиталь',
                    'data' => [
                        0 => [
                            'servicetype' => Type::VOENNUIGOSPITAL,
                        ]
                    ]
                ],
                6 => [
                    'title' => 'Прочее',
                    'data' => [
                        0 => [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 2
                        ],
                        1 => [
                            'servicetype' => Type::VUHODNOI
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Сохранить настройки отчета в базе при добавлении в очередь печати
     * @param $data - POST данные из V::getHtmlPrintSettingsBox
     */
    public static function savePrintSettings ($data)
    {
        global $USER;
        $USER->settings['reports'][self::REPORT_KEY]['version_last_used'] = $data['version'];
        $USER->settings['reports'][self::REPORT_KEY]['unit_last_used'] = $data['unit'];
        $USER->settings['reports']['unit_last_used'] = $data['unit'];
        $USER->updateSettings();
    }

    /**
     * Сохранить настройки из report_settings.php
     * @param $data - массив данных формы $_POST
     */
    public static function saveSettings ($data)
    {
        global $USER;
        $settings = self::getSettings();

        Report::calcMaskSettings($settings, $data);
        Report::calcSenderSettings($settings, $data);

        $USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION] = $settings;
        $USER->updateSettings();
    }

    /**
     * Получить HTML представление элементов формы настроек отчета
     * Страница report_settings
     * @return string
     */
    public static function getHtmlSettingsForm ()
    {
        return '';
    }

    /**
     * Получить массив индексов доступных для отчета шапок
     * @return int[]
     */
    public static function getHeadMaskIndexes ()
    {
        return [0];
    }

    /**
     * Получить массив индексов доступных для отчета шаблонов указания отправителя
     * @return int[]
     */
    public static function getSenderMaskIndexes ()
    {
        return [0, 1];
    }

    /**
     * Получить массив индексов доступных для отчета типов источников данных отправителя
     * 0 - без отправителя
     * 1 - произвольный текст
     * 2 - военнослужащий
     * 3 - текущий военнослужащий на выбранной должности
     * @return int[]
     */
    public static function getSenderSourceIndexes ()
    {
        return [
            0 => null,
            1 => [0, 1, 2, 3]
        ];
    }

    /**
     * Сформировать массив промежуточных данных для отчета
     * @return mixed
     */
    public function calculate ()
    {
        $date['today'] = $this->date;
        $date['yesterday']['unix'] = System::minusDay($this->date['unix']);
        $date['yesterday']['date'] = getdate($date['yesterday']['unix']);
        $yesterday = $date['yesterday']['date'];
        $today = $date['today']['date'];
        $schedule = new Schedule($this->unit, $date['yesterday']['unix'], $date['today']['unix']);
        $records['yesterday'] = $schedule->getDayArray($yesterday['year'], $yesterday['mon'], $yesterday['mday']);
        $records['today'] = $schedule->getDayArray($today['year'], $today['mon'], $today['mday']);
        $return = [];
        $using_militaries_in_list = []; // массив содержит идентификаторы военнослужащих, которые уже добавлены в конечный массив

        foreach ($records as $day_key => $day_records) {
            $dayarr = $date[$day_key]['date'];
            foreach ($day_records as $military => $type) {
                $filter_array = Report::calculate_filter_array([
                    'day_key' => $day_key,
                    'servicetype' => $type,
                    'serviceload_data' => @$schedule->serviceload_data[$military][$dayarr['year']][$dayarr['mon']][$dayarr['mday']],
                    'military_data' => [
                        'id' => $military,
                        'unit' => Military::getUnit($military, $date[$day_key]['unix'], $schedule->military_state[$military])
                    ],
                    'time_filter' => $this->settings['time_filter']
                ]);

                // Блок управления необходимостью обработки текущей записи в зависимости от дня и типа нагрузки
                $check_this_record = false;
                if ($day_key === 'yesterday') {
                    if (isset($filter_array['timeinterval']))
                        if ($filter_array['timeinterval']['work']['to'] >= $this->settings['time_filter'])
                            $check_this_record = true;
                } elseif ($day_key === 'today') {
                    $check_this_record = true;
                    if (isset($filter_array['timeinterval'])) {
                        $check_this_record = true;

                        // Обработка типов службы, которые начинаются после подачи строевой записки
                        $start_time = $filter_array['timeinterval']['work']['from'];
                        if (isset($filter_array['timeinterval']['beforework']))
                            $start_time = $filter_array['timeinterval']['beforework']['from'];
                        if ($start_time > $this->settings['time_filter'])
                            $filter_array['servicetype'] = Type::VUHODNOI;
                    }
                }

                // Формирование выходного массива данных
                if ($check_this_record) {
                    // Вычисление данных для таблицы
                    foreach ($this->settings['colums'] as $colum_position => $colum_info) {
                        foreach ($colum_info['data'] as $colum_data_index => $colum_data_rules) {
                            if (Report::calculate_filter($filter_array, $colum_data_rules)) {
                                if (!in_array($military, $using_militaries_in_list)) {
                                    $using_militaries_in_list[] = $military;
                                    $return[$filter_array['unit']][$colum_position][] = $military;
                                }
                                //echo 'В процессе формированя отчета произошел сбой (дублирование данных о военнослужащем с идентификатором ' . $military . '). Проверьте корректность ввода данных о служебной нагрузке на предмет пересечения временных диапазонов. В противном случае обратитесь к системному администратору.';
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * Сформировать отчет
     * Результат формирования сохряняется в $this->html
     */
    public function generate ()
    {
        global $STRUCT_DATA;
        $records = $this->calculate();

        $table_colums_count = 1;
        $summary_row = [
            'po_shtatu' => 0,
            'po_spisky' => 0
        ];

        $html = '<div style="width: 100%;">';
        $html .= Report::getHtmlMaskHead($this->settings['orientation'], $this->settings['head'], $this->settings['head_data'][$this->settings['head']]);
        $html .= '<div style="margin: 0 auto 20px auto; text-align: center; position: relative; width: 90%; border-bottom: 1px solid;">
                    <div style="text-align: center"> РАЗВЕРНУТАЯ СТРОЕВАЯ ЗАПИСКА</div>
                    <div style="float: left; display: inline-block; width: 20%;"></div>
                    <div style="float: right; display: inline-block; width: 20%; font-family: verbena; font-size: 18px;">на ' . $this->date['str'] . ' года</div>
                    <div style="float: right; display: inline-block; width: 60%; font-family: verbena; font-size: 18px;">' . mb_strtolower($STRUCT_DATA[$this->unit]['title'], 'UTF-8') . '</div>
         
                </div>';
        // войсковой части 6919

        $html .= '<table class="drillnote_toptable"><tr>';

        if ($this->settings['show_col_number']) {
            $html .= '<td style="text-align: center;">№ п/п</td>';
            $table_colums_count++;
        }

        $html .= '<td style="text-align: center;">Подразделение</td>';

        if ($this->settings['show_col_countbystate']) {
            $html .= '<td style="text-align: center;">По штату</td>';
            $table_colums_count++;
        }

        if ($this->settings['show_col_actualquantity']) {
            $html .= '<td style="text-align: center;">По списку</td>';
            $table_colums_count++;
        }

        foreach ($this->settings['colums'] as $colum_index => $colum_settings) {
            $html .= '<td>' . $colum_settings['title'] . '</td>';
            $table_colums_count++;
            $summary_row[$colum_index] = 0;
        }
        $html .= '</tr>';

        if ($this->settings['show_row_unit'])
            $html .= '<tr><td style="text-align: center;" colspan="' . $table_colums_count . '" >' . $STRUCT_DATA[$this->unit]['title'] . '</td></tr>';

        // Формирование списка подразделений
        $countUnit = 1;     // Счетчик подразделений в таблице
        $children_unit = Unit::getTree ([$this->unit]);
        $children_unit = $children_unit[$this->unit];
        $tree_level = Unit::getTreeLevel($children_unit);
        if ($tree_level === 0) {
            $units = [$this->unit => $this->unit];
        } elseif ($tree_level === 1) {
            $units = Unit::convertUnitsTreeAllToList($children_unit, true);
        } else {
            $units = Unit::getChildrenFromTree($children_unit);
            foreach ($units as $key => $val)
                if (is_array($children_unit[$key]))
                    $units[$key] = Unit::convertUnitsTreeAllToList($children_unit[$key], true);
                else $units[$key] = [$key => $key];
        }

        // Формирование массива для подсчета количества людей по списку
        $militaries_count_in_units = [];    // Фактическое количество военнослужащих в каждом из подразделений
        foreach ($units as $unit => $subunits)
            $militaries_count_in_units[$unit] = 0;

        // Подсчет суммарных показателей
        foreach ($records as $unit => $unit_records)
            foreach ($unit_records as $colum_index => $militaries)
                foreach ($militaries as $military) {
                    $summary_row['po_spisky']++;
                    $summary_row[$colum_index]++;
                    if ($tree_level > 1) {
                        foreach ($units as $u => $subunits)
                            if (in_array($unit, $subunits))
                                $militaries_count_in_units[$u]++;
                    } else $militaries_count_in_units[$unit]++;
                }

        // По штату запрос данных
        if ($this->settings['show_col_countbystate'] && $this->settings['show_col_data_countbystate']) {
            $summary_row['po_shtatu'] = 0;
            $units_state_count = State::getArrAffectedUnits();
        }

        foreach ($units as $unit => $subunits) {

            $html .= '<tr>';

            if ($this->settings['show_col_number']) {
                $html .= '<td style="text-align: center;">' . $countUnit . '</td>';
                $countUnit++;
            }

            $html .= '<td style="text-align: left;">' . $STRUCT_DATA[$unit]['title'] . '</td>';

            if ($this->settings['show_col_countbystate']) {
                if ($this->settings['show_col_data_countbystate']) {
                    $tmp = 0;
                    if (isset($units_state_count[$unit]))
                        $tmp = $units_state_count[$unit];
                    $html .= '<td style="text-align: center;">' . $tmp . '</td>';
                    $summary_row['po_shtatu'] += $tmp;
                } else $html .= '<td></td>';
            }

            if ($this->settings['show_col_actualquantity']) {
                if ($this->settings['show_col_data_actualquantity'])
                    $html .= '<td style="font-family: verbena; font-size: 18px; text-align: center;">' . $militaries_count_in_units[$unit] . '</td>';
                else $html .= '<td></td>';
            }

            foreach ($this->settings['colums'] as $colum_index => $colum_settings) {
                $colum_value = 0;
                if (is_array($subunits)) {
                    foreach ($records as $u => $colums)
                        if (in_array($u, $subunits))
                            if (isset($colums[$colum_index]))
                                $colum_value += count($colums[$colum_index]);
                } else {
                    if (isset($records[$unit][$colum_index]))
                        $colum_value = count($records[$unit][$colum_index]);
                }

                if ($colum_value)
                    $html .= '<td style="font-family: verbena; font-size: 18px; text-align: center;">' . $colum_value . '</td>';
                else $html .= '<td>' . $this->settings['data_in_empty_cells'] . '</td>';
            }

            $html .= '</tr>';
        }

        if ($this->settings['show_row_summary']) {
            $colspan = 1;
            if ($this->settings['show_col_number']) $colspan++;
            $html .= '<tr><td colspan="' . $colspan . '" style="text-align: right;">ИТОГО</td>';

            if ($this->settings['show_col_countbystate']) {
                if ($this->settings['show_col_data_countbystate'])
                    $html .= '<td style="text-align: center;">' . $summary_row['po_shtatu'] . '</td>';
                else $html .= '<td></td>';
            }

            if ($this->settings['show_col_actualquantity']) {
                if ($this->settings['show_col_data_actualquantity'])
                    $html .= '<td style="font-family: verbena; font-size: 18px; text-align: center;">' . $summary_row['po_spisky'] . '</td>';
                else $html .= '<td></td>';
            }

            foreach ($this->settings['colums'] as $colum_index => $colum_settings) {
                if ($summary_row[$colum_index])
                    $html .= '<td style="font-family: verbena; font-size: 18px; text-align: center;">' . $summary_row[$colum_index] . '</td>';
                else $html .= '<td>' . $this->settings['data_in_empty_cells'] . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        $data = $this->settings;
        $data['date'] = $this->date['unix'];
        $data['date_str'] = $this->date['str'];
        $html .= Report::getHtmlMaskSender(
            $this->settings['orientation'],
            $this->settings['sender_mask'],
            $this->settings['sender_source'],
            $data
        );

        $this->html = '<div class="page ' . self::REPORT_KEY . '" style="font-size: ' . $this->settings['font'] . 'px;">' . $html . '</div>';
    }

    /**
     * Сгенерировать блок настроек при добавлении отчета в лист печати
     * @return string - html представление
     */
    public static function getHtmlPrintSettingsBox ()
    {
        global $USER;
        $settings = self::getSettings();

        //*****************************      Визуализация

        $html = '
        <div class="headLinkButtonsWindow shadow_window ' . self::REPORT_KEY . '">
            <div class="pdf_settings_title">Параметры</div>';

        //  Версия отчета
        $html .= '
        <div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">Версия</div>
            <div class="pdf_settings_more_item_value">
                <select name="' . self::REPORT_KEY . '_version" onchange="report_settingsbox_version_change(this, \'' . self::REPORT_KEY . '\')">';
        $data = self::getVersionsKeys();
        foreach ($data as $item) {
            $checked = ($item === self::REPORT_VERSION) ? 'selected' : '';
            $html .= '<option value="' . $item . '" ' . $checked . '>' . $item . '</option>';
        }
        $html .= '                
                </select>
            </div>
        </div>';

        // Подразделение
        if (isset($USER->settings['reports'][self::REPORT_KEY]['unit_last_used']))
            $default['unit'] = $USER->settings['reports'][self::REPORT_KEY]['unit_last_used'];
        elseif (isset($USER->settings['reports']['unit_last_used']))
            $default['unit'] = $USER->settings['reports']['unit_last_used'];
        elseif (isset($USER->settings['schedule']['unit']))
            $default['unit'] = $USER->settings['schedule']['unit'];
        else
            $default['unit'] = 0;

        // Проверка существования подразделения
        if ($default['unit'] !== 0)
            if (!isset($STRUCT_DATA[$default['unit']]))
                $default['unit'] = 0;

//@todo В дальнейшем доделать ссылку на прикрепленный к user military
//            $default['unit'] = $USER->unit;

        $html .= '<div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">Подразделение</div>
            <div class="pdf_settings_more_item_value">'
            . Report::getHtmlSelectUnit(self::REPORT_KEY, $default['unit']) .
            '</div>
        </div>';


        // Дата
        $from = System::parseDate(System::plusDay(System::time()), 'd/m/y');
        $html .= '<div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">Дата</div>
            <div class="pdf_settings_more_item_value">
                <input class="datepicker text date_to_val" name="' . self::REPORT_KEY . '_from" value="' . $from . '" autocomplete="off">
            </div>
        </div>';

        $html .= '
            <span class="pdf_settings_show_more_button" onclick="pdf_settings_show_more();">Больше...</span>
            <div class="hidden pdf_settings_show_more_body">';

        // По
        $to = $from;
        $html .= '<div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">
                По
                <input class="pdf_settings_checkbox" type="checkbox" name="' . self::REPORT_KEY . '_to_auto" onclick="pdf_settings_chechbox_affect(this, $(\'.' . self::REPORT_KEY . '_to\'))">
            </div>
            <div class="pdf_settings_more_item_value">
                <input class="datepicker text date_to_val ' . self::REPORT_KEY . '_to" name="' . self::REPORT_KEY . '_to" value="' . $to . '" disabled autocomplete="off">
            </div>
        </div>';

        $html .= '</div>';
        $html .= '<div class="button" onclick="printQueue_addReport_ok(\'' . self::REPORT_KEY . '\')">Готово</div>
        </div>';

        return $html;
    }

    /**
     * Обработка данных из блока настроек отчета при добавлении в лист печати
     * @param $data - настройки отчета при добавлении в лист печати из блока настроек
     * @return array - обработанные данные
     */
    public static function calcPrintTableItemData ($data)
    {
        $return = ['status' => false];

        // определение unit
        $unit = \OsT\Unit::getSelectedUnit($data, self::REPORT_KEY . '_unit');

        if ($unit) {
            $from = System::parseDate($data[self::REPORT_KEY . '_from'], 'unix', 'd/m/y');
            if ($from) {
                $to = (boolval($data[self::REPORT_KEY . '_to_auto'])) ? System::parseDate($data[self::REPORT_KEY . '_to'], 'unix', 'd/m/y') : $from;

                if ($to || $from <= $to) {
                    $return['status'] = true;
                    $cicle = intval(($to - $from + System::TIME_DAY + System::TIME_HOUR * 2) / System::TIME_DAY);
                    for ($i = 0; $i < $cicle; $i++) {
                        $tmp = [];
                        $tmp['key'] = self::REPORT_KEY;
                        $tmp['unit'] = $unit;
                        $tmp['version'] = self::REPORT_VERSION;
                        $tmp['date'] = $from + System::TIME_HOUR * 2 + $i * System::TIME_DAY;
                        $tmp['create'] = $tmp['date'];
                        $return['data'][] = $tmp;
                    }

                } else $return['errors']['to'] = true;
            } else $return['errors']['from'] = true;
        } else $return['errors']['unit'] = true;

        return $return;
    }

}