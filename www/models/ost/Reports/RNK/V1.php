<?php

namespace OsT\Reports\RNK;

use OsT\Base\System;
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
class V1 extends RNK
{

    const REPORT_VERSION = '1';

    public $unit;           // Подразделение
    public $date;           // Дата, на которую формируется служебная нагрузка [unix|date|str]
    public $create;         // Дата создания отчета [unix|date|str]
    public $settings;       // Настройки отчета
    public $schedule;       // График нагрузки
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
        $this->create['unix'] = intval($data['create']);
        $this->create['date'] = getdate($this->create['unix']);
        $this->create['str'] = System::parseDate($this->create['unix']);
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
         * addnakotellist                   массив блоков данных о военнослужащих, которые будут добавлены на котловое довольствие (объявлены до вывода в таблице)
         *      N                               порядковый номер блока (порядок вывода от меньшего к большему), идентификатор
         *          title                           наименование блока
         *          data                            массив блоков фильтрации данных по типу служебной нагрузки
         *              N                               массив параметров фильтрации данных, если данные равны значениям в блоке - военнослужащий добавляется в данный блок
         *                  servicetype                     тип служебной нагрузки
         *                  usertype                        подтип служебной нагрузки (только для типа "Служба")
         *                  level                           массив идентификаторов допустимых званий, в которых военнослужащие могут попасть в данный блок
         * removenakotellist                массив блоков данных о военнослужащих, которые не будут добавлены на котловое довольствие (объявлены до вывода в таблице)
         *      N                               порядковый номер блока (порядок вывода от меньшего к большему), идентификатор
         *          title                           наименование блока
         *          data                            массив блоков фильтрации данных по типу служебной нагрузки
         *              N                               массив параметров фильтрации данных, если данные равны значениям в блоке - военнослужащий добавляется в данный блок
         *                  servicetype                     тип служебной нагрузки
         *                  usertype                        подтип служебной нагрузки (только для типа "Служба")
         *                  level                           массив идентификаторов допустимых званий, в которых военнослужащие могут попасть в данный блок
         * periods                          массив столбцов итоговой таблицы котлового довольствия
         *      N                               порядковый номер столбца (порядок вывода от меньшего к большему), идентификатор
         *          title                           наименование столбца
         *          from                            целое число, час от 0 до 23, начиная с которого военнослужащий должен находиться на рабочем месте
         *          to                              целое число, час от 0 до 23, до которого военнослужащий должен находиться на рабочем месте
         * data                             массив данных, которые попадут в итоговую таблицу
         *      N                               идентификатор периода periods -> N, указывает на столбец, в который попадут данные
         *          servicetype                     тип служебной нагрузки
         *          usertype                        подтип случебной нагрузки (только для типа "Служба")
         *          time_filter                     массив параметров для фильтрации по времени (зависит от time_filter в корне настроек)
         *              beforework                      указывает на то, учитывать ли при проверке время от прибытия на работу до начала работы
         *              work                            рабочее время
         *                                              для наряда - это момент от начала наряда до конца
         *                                              для рабочего дня - от глобального начала рабочего дня (8ч) до его конца (18ч)
         *          showplace                       true - отображать место несения службы
         *                                          false - не отображать
         */

        return [
            'orientation' => 'P',
            'head' => 1,
            'head_data' => [
                0 => null,
                1 => [
                    'text' => 'Командиру войсковой части 6919
                        
                        '
                ]
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
            'addnakotellist' => [
                0 => [
                    'title' => 'В связи с несением службы (исполнением специальных обязанностей) в составе суточного наряда:',
                    'data' => [
                        [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 1
                        ]
                    ]
                ],
                1 => [
                    'title' => 'В связи с тем, что военнослужащий приступает к исполнению должностных обязанностей:',
                    'data' => [
                        [
                            'servicetype' => Type::RABOCHUI,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ]
                    ]
                ]
            ],
            'removenakotellist' => [
                0 => [
                    'title' => 'В связи с предоставлением выходного дня:',
                    'data' => [
                        [
                            'servicetype' => Type::VUHODNOI,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ],
                        [
                            'servicetype' => Type::BOLNICHNUI,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ],
                        [
                            'servicetype' => Type::KOMANDIROVKA,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ],
                        [
                            'servicetype' => Type::OTPUSK,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ],
                        [
                            'servicetype' => Type::VOENNUIGOSPITAL,
                            'level' => [0, 1, 2, 3, 4, 5]
                        ],
                    ]
                ]
            ],
            'periods' => [
                0 => [
                    'title' => 'Завтрак',
                    'from' => 6,
                    'to' => 7
                ],
                1 => [
                    'title' => 'Обед',
                    'from' => 13,
                    'to' => 14
                ],
                2 => [
                    'title' => 'Ужин',
                    'from' => 19,
                    'to' => 20
                ]
            ],
            'data' => [
                0 => [
                    'servicetype' => Type::NARYAD,
                    'usertype' => 1,
                    'time_filter' => [
                        'beforework' => true,
                        'work' => true
                    ],
                    'showplace' => true
                ],
                1 => [
                    'servicetype' => Type::RABOCHUI,
                    'level' => [0, 1, 2, 3, 4, 5],
                    'time_filter' => [
                        'work' => true
                    ]
                ],
                2 => [
                    'servicetype' => Type::NARYAD,
                    'usertype' => 2,
                    'time_filter' => [
                        'beforework' => true,
                        'work' => false
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
        return [0, 1];
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
     * Сформировать массив промежуточных данных для отчета типа [период питания][n][title|post]
     * @return mixed
     */
    public function calculate ()
    {
        $date['today'] = $this->date;
        $date['yesterday']['unix'] = System::minusDay($this->date['unix']);
        $date['yesterday']['date'] = getdate($date['yesterday']['unix']);
        $yesterday = $date['yesterday']['date'];
        $today = $date['today']['date'];
        $this->schedule = new Schedule($this->unit, $date['yesterday']['unix'], $date['today']['unix']);
        $records['yesterday'] = $this->schedule->getDayArray($yesterday['year'], $yesterday['mon'], $yesterday['mday']);
        $records['today'] = $this->schedule->getDayArray($today['year'], $today['mon'], $today['mday']);

        $militarys_data = Military::getData($this->schedule->military, [
            'id',
            'fname',
            'level',
            'fio_short',
            'level_short',
            'unit'
        ]);
        $places = Place::getData(null, [
            'title',
        ]);
        $return = [];

        foreach ($records as $day_key => $day_records) {
            $dayarr = $date[$day_key]['date'];
            foreach ($day_records as $military => $type) {
                $filter_array = Report::calculate_filter_array([
                    'day_key' => $day_key,
                    'servicetype' => $type,
                    'serviceload_data' => @$this->schedule->serviceload_data[$military][$dayarr['year']][$dayarr['mon']][$dayarr['mday']],
                    'military_data' => $militarys_data[$military]
                ]);

                // Вычисление addnakotellist и removenakotellist
                if ($day_key === 'today') {
                    foreach ($this->settings['addnakotellist'] as $index => $section)
                        foreach ($section['data'] as $dataIndex => $rules)
                            if (Report::calculate_filter($filter_array, $rules))
                                $return['addnakotellist'][$index][] = $militarys_data[$military]['level_short'] . ' ' . $militarys_data[$military]['fio_short'];

                    foreach ($this->settings['removenakotellist'] as $index => $section)
                        foreach ($section['data'] as $dataIndex => $rules)
                            if (Report::calculate_filter($filter_array, $rules))
                                $return['removenakotellist'][$index][] = $militarys_data[$military]['level_short'] . ' ' . $militarys_data[$military]['fio_short'];
                }

                // Вычисление данных для таблицы
                foreach ($this->settings['data'] as $sdata_key => $sdata) {
                    foreach ($this->settings['periods'] as $periodindex => $period) {
                        $filter_array['time_filter'] = $period;
                        if (Report::calculate_filter($filter_array, $sdata)) {
                            $military_data = [
                                'id' => $military,
                                'type' => $type,
                                'fio_short' => $militarys_data[$military]['fio_short'],
                                'level' => $militarys_data[$military]['level'],
                                'level_short' => $militarys_data[$military]['level_short'],
                                'day' => $day_key,
                                'str' => $militarys_data[$military]['level_short'] . ' ' . $militarys_data[$military]['fio_short'],
                                'position' => $sdata_key
                            ];
                            if (isset($sdata['showplace']) && $type === Type::NARYAD) {
                                $military_data['place'] = $this->schedule->serviceload_data[$military][$dayarr['year']][$dayarr['mon']][$dayarr['mday']]['place'];
                                $military_data['place_title'] = $places[$military_data['place']]['title'];
                                $military_data['str'] = $places[$military_data['place']]['title'] . ' &ndash; ' . $military_data['str'];
                            }
                            $return['table'][$periodindex][] = $military_data;
                        }
                    }
                }
            }
        }

        /* Позиционирование записей */
        if (isset($return['table'])) {
            foreach ($return['table'] as $periodindex => $periodData)
                $return['table'][$periodindex] = System::sort($periodData, 'position', 'asc');
        }

        return $return;
    }

    /**
     * Сформировать отчет версии
     * Результат формирования сохряняется в $this->html
     */
    public function generate ()
    {
        $arr = $this->calculate();

        /* Вычисление $table_max_data_count */
        $table_max_data_count = 0;  // максимальное количество записей в столбце периода питания военнослужащих
        if (isset($arr['table'])) {
            foreach ($arr['table'] as $arrdata) {
                $count = count($arrdata);
                if ($count > $table_max_data_count)
                    $table_max_data_count = $count;
            }
        }

        $parseArr = System::parseDate($this->date['unix'], 'array');
        $dateStr = System::i2d($parseArr['mday']) . ' ' . mb_strtolower($parseArr['month1'], 'UTF-8') . ' ' . $parseArr['year'];

        $html = Report::getHtmlMaskHead($this->settings['orientation'], $this->settings['head'], $this->settings['head_data'][$this->settings['head']]);

        $html .= '<div align="center">Рапорт</div><br>';

        // Зачислить на котловое довольствование
        if (isset($arr['addnakotellist'])) {
            $html .= '
                <div style="font-size:15px;">
                    <div style="text-align: justify; text-indent: 40px;">Согласно требованиям приказа Росгвардии от 15.11.2018 г № 499, прошу Вас зачислить на
                          ' . $dateStr . ' года на продовольственное обеспечение за счет средств федерального бюджета нижеуказанных военнослужащих в соответствии с вариантами организации питания, указанными в их рапортах:
                    </div>';

            foreach ($this->settings['addnakotellist'] as $index => $sdata) {
                if (isset($arr['addnakotellist'][$index])) {
                    $html .= '<div style="text-align: justify; text-indent: 40px; margin: 10px 0;">' . $sdata['title'] . '</div>
                        <div style="text-align: left; box-sizing: border-box; padding-left: 40px;">';
                    foreach ($arr['addnakotellist'][$index] as $military)
                        $html .= $military . '<br>';
                    $html .= '</div>';
                }
            }
        }

        // Снять с котлового довольствования
        if (isset($arr['removenakotellist'])) {
            $html .= '<div style="text-align: justify; text-indent: 40px; margin: 10px 0;">Согласно требованиям приказа Росгвардии от 15.11.2018 г № 499, прошу Вас снять на ' . $dateStr . ' года с продовольственного обеспечение за счет средств федерального бюджета нижеуказанных военнослужащих:</div>';

            foreach ($this->settings['removenakotellist'] as $index => $sdata) {
                if (isset($arr['removenakotellist'][$index])) {
                    $html .= '<div style="text-align: justify; text-indent: 40px; margin: 0 0 10px;">' . $sdata['title'] . '</div>
                        <div style="text-align: left; box-sizing: border-box; padding-left: 40px;">';
                    foreach ($arr['removenakotellist'][$index] as $military)
                        $html .= $military . '<br>';
                    $html .= '</div>';
                }
            }
        }

        if (isset($arr['addnakotellist']) || isset($arr['removenakotellist']))
            $html .= '<div style="text-align: justify; text-indent: 40px; margin: 10px 0;">Учитывая вышеизложенное, докладываю список военнослужащих, изъявивших желание <span style="text-decoration: underline;">' . $table_max_data_count . '</span> питаться в столовой воинской части, по приемам пищи:</div></div>';
        else $html .= '<div style="text-align: justify; text-indent: 40px; margin: 10px 0;">Настоящим докладываю список военнослужащих, изъявивших желание <span style="text-decoration: underline;">' . $table_max_data_count . '</span> питаться в столовой воинской части, по приемам пищи:</div></div>';

        /* Формирование таблицы питания */
        $html_table = '
            <table class = "kotel_table1">
                <tr><td style="text-align: center;" class="first_colum">№ п/п</td>';

        foreach ($this->settings['periods'] as $period_key => $period_data)
            $html_table .= '<td style="text-align: center;" class="second_colum">' . $period_data['title'] . '</td>';
        $html_table .= '</tr>';

        if (isset($arr['table'])) {
            for ($i = 0; $i < $table_max_data_count; $i++) {
                $html_table .= '<tr><td style="text-align: center;">' . ($i + 1) . '.</td>';
                foreach ($this->settings['periods'] as $period_key => $period_data)
                    @$html_table .= '<td>' . $arr['table'][$period_key][$i]['str'] . '</td>';
                $html_table .= '</tr>';
            }

        } else {
            $html_table .= '<tr><td style="text-align: center;">-</td>';
            foreach ($this->settings['periods'] as $period_key => $period_data)
                $html_table .= '<td>-</td>';
            $html_table .= '</tr>';
        }

        $html .= $html_table . '</table>';

        /* Вставка блока на подпись исполнителя */

        $data = $this->settings;
        $data['date'] = $this->create['unix'];
        $data['date_str'] = $this->create['str'];
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

        // Создан
        $create = System::time();
        $create = System::parseDate($create, 'd/m/y');
        $html .= '<div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">
                Дата создания (авто)
                <input class="pdf_settings_checkbox" type="checkbox" name="' . self::REPORT_KEY . '_create_auto" onclick="pdf_settings_chechbox_affect(this, $(\'.' . self::REPORT_KEY . '_create\'))">
            </div>
            <div class="pdf_settings_more_item_value">
                <input class="datepicker text ' . self::REPORT_KEY . '_create" name="' . self::REPORT_KEY . '_create" value="' . $create . '" disabled autocomplete="off">
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

        $from = System::parseDate($data[self::REPORT_KEY . '_from'], 'unix', 'd/m/y');
        if ($from) {
            $to = (boolval($data[self::REPORT_KEY . '_to_auto'])) ? System::parseDate($data[self::REPORT_KEY . '_to'], 'unix', 'd/m/y') : $from;
            $create = System::parseDate($data[self::REPORT_KEY . '_create'], 'unix', 'd/m/y');
            if ($to || $from <= $to) {
                $return['status'] = true;
                $cicle = intval(($to - $from + System::TIME_DAY + System::TIME_HOUR * 2) / System::TIME_DAY);
                for ($i = 0; $i < $cicle; $i++) {
                    $tmp = [];
                    $tmp['key'] = self::REPORT_KEY;
                    $tmp['unit'] = $unit;
                    $tmp['version'] = self::REPORT_VERSION;
                    $tmp['date'] = $from + System::TIME_HOUR * 2 + $i * System::TIME_DAY;
                    $tmp['create'] = (boolval($data[self::REPORT_KEY . '_create_auto'])) ? $create : System::minusDay($tmp['date']);
                    $return['data'][] = $tmp;
                }

            } else $return['errors']['to'] = true;
        } else $return['errors']['from'] = true;

        return $return;
    }

}