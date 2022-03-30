<?php

namespace OsT\Reports\SOV;

use OsT\Base\System;
use OsT\Military\Absent;
use OsT\Military\Military;
use OsT\Reports\Report;
use OsT\Serviceload\Place;
use OsT\Serviceload\Schedule;
use OsT\Serviceload\Type;

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
class V1 extends SOV
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
         *                                  Данный фильтр применяется в сочетании с массивом blocks -> N -> data -> N -> timefilter
         * blocks                           массив блоков (таблиц) по типам отсутствия
         *      N                               порядковый номер блока (порядок вывода от меньшего к большему), идентификатор
         *          title                           наименование блока
         *          table_title                     HTML разметка наименований столбцов
         *          table_data                      массив атрибутов данных военнослужащего, которые будут отображаться в таблице
         *          data                            массив блоков фильтрации данных по типу служебной нагрузки
         *              N                               массив параметров фильтрации данных, если данные равны значениям в блоке - военнослужащий добавляется в данный блок
         *                                              порядковый номер блока (порядок вывода от меньшего к большему), идентификатор
         *                  servicetype                     тип служебной нагрузки
         *                  usertype                        подтип служебной нагрузки (только для типа "Служба")
         *                  time_filter                     массив параметров для фильтрации по времени (зависит от time_filter в корне настроек)
         *                      beforework                      указывает на то, учитывать ли при проверке время от прибытия на работу до начала работы
         *                      work                            рабочее время
         *                                                      для наряда - это момент от начала наряда до конца
         *                                                      для рабочего дня - от глобального начала рабочего дня (8ч) до его конца (18ч)
         *          show_without_data               true - отображать блок, даже если данные отсутствуют
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
            'blocks' => [
                0 => [
                    'title' => 'Отсутствующие',
                    'table_title' => '
                        <td>№ п/п</td>
                        <td>Воинское звание</td>
                        <td>Фамилия, инициалы</td>
                        <td>Подразделение</td>
                        <td>Место проведения</td>
                        <td colspan="2">Время отсутствия</td>
                        <td></td>',
                    'table_data' => [
                        'count',
                        'level_title',
                        'fio_short',
                        'unit_title',
                        'place',
                        'from',
                        'to',
                        'serviceload_title'
                    ],
                    'data' => [
                        0 => [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 1,
                            'time_filter' => [
                                'beforework' => false,
                                'work' => true
                            ]
                        ],
                        1 => [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 2,
                            'time_filter' => [
                                'work' => true
                            ]
                        ],
                        2 => [
                            'servicetype' => Type::VUHODNOI
                        ],
                        3 => [
                            'servicetype' => Type::OTPUSK,
                        ]
                    ],
                    'show_without_data' => true,
                ],
                1 => [
                    'title' => 'Командировка',
                    'table_title' => '
                        <td>№ п/п</td>
                        <td>Воинское звание</td>
                        <td>Фамилия, инициалы</td>
                        <td>Подразделение</td>
                        <td>Место командировки</td>
                        <td>с какого</td>
                        <td>по какое</td>
                        <td>цель</td>',
                    'table_data' => [
                        'count',
                        'level_title',
                        'fio_short',
                        'unit_title',
                        'place',
                        'from',
                        'to',
                        null
                    ],
                    'data' => [
                        0 => [
                            'servicetype' => Type::KOMANDIROVKA,
                        ]
                    ],
                    'show_without_data' => false,
                ],
                2 => [
                    'title' => 'Больничный',
                    'table_title' => '
                        <td>№ п/п</td>
                        <td>Воинское звание</td>
                        <td>Фамилия, инициалы</td>
                        <td>Подразделение</td>
                        <td>Место лечения</td>
                        <td colspan="2">с какого</td>
                        <td></td>',
                    'table_data' => [
                        'count',
                        'level_title',
                        'fio_short',
                        'unit_title',
                        'place',
                        'from',
                        null,
                        null
                    ],
                    'data' => [
                        0 => [
                            'servicetype' => Type::BOLNICHNUI,
                        ]
                    ],
                    'show_without_data' => false,
                ],
                3 => [
                    'title' => 'Военный госпиталь',
                    'table_title' => '
                        <td>№ п/п</td>
                        <td>Воинское звание</td>
                        <td>Фамилия, инициалы</td>
                        <td>Подразделение</td>
                        <td>Место лечения</td>
                        <td colspan="2">с какого</td>
                        <td></td>',
                    'table_data' => [
                        'count',
                        'level_title',
                        'fio_short',
                        'unit_title',
                        'place',
                        'from',
                        null,
                        null
                    ],
                    'data' => [
                        0 => [
                            'servicetype' => Type::VOENNUIGOSPITAL,
                        ]
                    ],
                    'show_without_data' => false,
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

                // Формирование массива для фильтрации записей
                $filter_array = Report::calculate_filter_array([
                    'day_key' => $day_key,
                    'servicetype' => $type,
                    'serviceload_data' => @$schedule->serviceload_data[$military][$dayarr['year']][$dayarr['mon']][$dayarr['mday']],
                    'time_filter' => $this->settings['time_filter']
                ]);

                // Блок управления необходимостью обработки текущей записи в зависимости от дня и типа нагрузки
                $check_this_record = false;
                if ($day_key === 'yesterday') {
                    if (isset($filter_array['timeinterval']))
                        if ($filter_array['timeinterval']['work']['to'] >= $this->settings['time_filter'])
                            $check_this_record = true;
                } elseif ($day_key === 'today') {
                    if (!in_array($military, $using_militaries_in_list)) {
                        $check_this_record = true;
                        if (isset($filter_array['timeinterval'])) {
                            // Обработка типов службы, которые начинаются после подачи строевой записки
                            $start_time = $filter_array['timeinterval']['work']['from'];
                            if (isset($filter_array['timeinterval']['beforework']))
                                $start_time = $filter_array['timeinterval']['beforework']['from'];
                            if ($start_time > $this->settings['time_filter'])
                                $filter_array['servicetype'] = Type::VUHODNOI;
                        }
                    }
                }

                // Формирование выходного массива данных
                if ($check_this_record) {
                    // Вычисление данных для таблицы
                    foreach ($this->settings['blocks'] as $block_position => $colum_info) {
                        foreach ($colum_info['data'] as $block_data_index => $colum_data_rules) {
                            if (Report::calculate_filter($filter_array, $colum_data_rules)) {
                                $using_militaries_in_list[] = $military;
                                $return[$block_position][$block_data_index][] = [
                                    'id' => $military,
                                    'serviceload_data' => @$schedule->serviceload_data[$military][$dayarr['year']][$dayarr['mon']][$dayarr['mday']],
                                    'servicetype' => $type
                                ];
                                //echo 'В процессе формированя отчета произошел сбой (дублирование данных о военнослужащем с идентификатором ' . $military . '). Проверьте корректность ввода данных о служебной нагрузке на предмет пересечения временных диапазонов. В противном случае обратитесь к системному администратору.';
                            }
                        }
                    }
                }
            }
        }

        // Сбор дополнительных данных
        if (count($using_militaries_in_list)) {
            global $STRUCT_DATA;

            $serviceload_types = Type::getData(
                null ,
                [
                    'title',
                ]);
            $serviceload_subtypes = Type::getData(
                [Type::NARYAD] ,
                [
                    'sub_types',
                ]);
            $serviceload_subtypes = $serviceload_subtypes[Type::NARYAD]['sub_types'];

            $places = Place::getData(null,
            [
                'title'
            ]);
            $militarys = Military::getData($using_militaries_in_list, [
                'fio_short',
                'level_title',
                'unit',
            ]);

            foreach ($return as $block_position => $block_array) {
                foreach ($block_array as $block_data_index => $military_arr) {
                    foreach ($military_arr as $military_index => $military) {
                        if ($military['servicetype'] === Type::NARYAD) {
                            $militarys[$military['id']]['serviceload_title'] = $serviceload_subtypes[intval($military['serviceload_data']['type'])];
                            $militarys[$military['id']]['place'] = $places[intval($military['serviceload_data']['place'])]['title'];
                            $militarys[$military['id']]['from'] = null;
                            $militarys[$military['id']]['to'] = null;

                        } else {
                            $militarys[$military['id']]['serviceload_title'] = $serviceload_types[$military['servicetype']]['title'];
                            $militarys[$military['id']]['place'] = null;
                            if ($military['servicetype'] === Type::VUHODNOI) {
                                $militarys[$military['id']]['from'] = null;
                                $militarys[$military['id']]['to'] = null;

                            } else {
                                $absent_data = Absent::getData([intval($military['serviceload_data']['id'])], [
                                    'date_from_string',
                                    'date_to_string'
                                ]);
                                $absent_data = $absent_data[intval($military['serviceload_data']['id'])];
                                $militarys[$military['id']]['from'] = $absent_data['date_from_string'];
                                $militarys[$military['id']]['to'] = $absent_data['date_to_string'];
                            }
                        }

                        $return[$block_position][$block_data_index][$military_index] = [
                            'level_title' => $militarys[$military['id']]['level_title'],
                            'fio_short' => $militarys[$military['id']]['fio_short'],
                            'serviceload_title' => $militarys[$military['id']]['serviceload_title'],
                            'place' => $militarys[$military['id']]['place'],
                            'from' => $militarys[$military['id']]['from'],
                            'to' => $militarys[$military['id']]['to'],
                            'unit_title' => $STRUCT_DATA[$militarys[$military['id']]['unit']]['title']
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Сформировать отчет версии
     * Результат формирования сохряняется в $this->html
     */
    public function generate ()
    {
        $records = $this->calculate();

        $html = '<div style="width: 100%;">';
        $html .= Report::getHtmlMaskHead($this->settings['orientation'], $this->settings['head'], $this->settings['head_data'][$this->settings['head']]);
        $html .= '<table class="drillnote_bottable">
            <tr><td colspan="8">Список военнослужащих, возвращающихся из командировок, отпусков, лечебных учреждений и находящихся в отпуске</td></tr>';

        foreach ($this->settings['blocks'] as $block_index => $block_data) {
            if (isset($records[$block_index])) {
                $count = 1;
                $html .= '<tr><td colspan="8"><b>' . $block_data['title'] . '</b></td></tr>
                    <tr style="font-style: italic;">' . $block_data['table_title'] . '</tr>';
                foreach ($block_data['data'] as $block_data_index => $block_data_rules) {
                    if (isset($records[$block_index][$block_data_index])) {
                        foreach ($records[$block_index][$block_data_index] as $record) {
                            $record['count'] = $count;
                            $html .= '<tr>';
                            foreach ($block_data['table_data'] as $key) {
                                $value = ($key === null) ? '' : $record[$key];
                                $html .= '<td>' . $value . '</td>';
                            }
                            $html .= '</tr>';
                            $count++;
                        }
                    }
                }

            } elseif ($block_data['show_without_data']) {
                $html .= '<tr><td colspan="8"><b>' . $block_data['title'] . '</b></td></tr>
                    <tr style="font-style: italic;">' . $block_data['table_title'] . '</tr>
                    <tr>';
                foreach ($block_data['table_data'] as $key)
                    $html .= '<td>-</td>';
                $html .= '</tr>';

            }
        }

        if (false) {
            $html .= '<tr><td colspan="8"><b>Прикомандированные</b></td></tr>
            <tr style="font-style: italic;">
                <td>№ п/п</td>
                <td>Воинское звание</td>
                <td>Фамилия, инициалы</td>
                <td>К какому подразделению прикомандирован (до отделения)</td>
                <td>Примечание</td>
                <td colspan="2"></td>
                <td></td>
            </tr>
            <tr><td>1</td><td></td><td></td><td></td><td></td><td colspan="2"></td><td></td></tr>';
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

        $html .= '</div>';

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