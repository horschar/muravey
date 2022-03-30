<?php

namespace OsT\Reports\RSN;

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
 * generate                         Сформировать HTML отчет версии
 * getHtmlPrintSettingsBox          Сгенерировать блок настроек при добавлении отчета в лист печати
 * calcPrintTableItemData           Обработка данных из блока настроек отчета при добавлении в лист печати
 *
 */
class V1 extends RSN
{

    const REPORT_VERSION = '1';

    public $unit;           // Подразделение
    public $date;           // Дата, на которую формируется служебная нагрузка [unix|date|str]
    public $create;         // Дата создания отчета [unix|date|str]
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
         * orientation              ориентация страницы отчета (P - вертикальная, L - горизонтальная)
         * head                     идентификатор шаблона HTML разметки блока получателя
         * head_data                набор данных для шаблона разметки блока получателя
         *      N                       идентификатор шаблона HTML разметки (head), для которого будет определен набор данных
         *                              принимает занчение null (данные не требуются) либо [] (массив данных для каждого шаблона)
         *      1   text                    получатель (текст)
         * sender_mask              идентификатор шаблона HTML разметки блока отправителя
         * sender_source            идентификатор типа источника данных отправителя
         * sender_data              пользовательский набор данных по типам источника отправителя
         *      N                       идентификатор шаблона HTML разметки (sender_mask), для которого будет определен набор данных
         *                              принимает занчение null (данные не требуются) либо [] (массив данных по каждому из типов источников)
         *          N                       идентификатор типа источника данных (sender_source), для которого указаны данные
         *                                  принимает значение null (данные не требуются) либо [] (массив данных для источника)
         *          1   state                   должность отправителя (текст)
         *              level                   звание отправителя (текст)
         *              fio                     ФИО отправителя (текст)
         *          2   military                идентификатор военнослужащего
         *          3   state                   идентификатор должности, по которой будет определен военнослужащий
         *              vrio                    true - если есть Врио на текущей должности - использовать его
         *                                      false - не использовать данные Врио даже если таков имеется
         * show_datecreate          отображать дату создания отчета
         * font                     размер шрифта для вычисления относительных величин элементов в отчете
         * text                     текст отчета, который будет помещен под надписью "Рапорт"
         * blocks                   массив блоков группировки военнослужащих
         *      N                       порядковый номер блока (порядок вывода от меньшего к большему), идентификатор блока
         *          title                   наименование блока (текст)
         *          data                    массив блоков фильтрации данных по типу служебной нагрузки
         *              N                       массив параметров фильтрации данных, если данные разны значениям в блоке - военнослужащий добавляется в данный блок
         *                  servicetype             тип служебной нагрузки
         *                  usertype                подтип служебной нагрузки (только для типа "Служба")
         *                  place                   true - отображать место несения службы
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
            'text' => 'Настоящим докладываю, что на оперативно-техническую службу @date_дд.мм.гггг_г. заступают:',
            'blocks' => [
                0 => [
                    'title' => 'Наряд',
                    'data' => [
                        [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 1,
                            'place' => true
                        ]
                    ]
                ],
                1 => [
                    'title' => 'Рабочий день',
                    'data' => [
                        [
                            'servicetype' => Type::RABOCHUI
                        ]
                    ]
                ],
                2 => [
                    'title' => 'Служебно-боевая задача',
                    'data' => [
                        [
                            'servicetype' => Type::NARYAD,
                            'usertype' => 2,
                            'place' => false
                        ]
                    ]
                ],
                3 => [
                    'title' => 'Отпуск',
                    'data' => [
                        [
                            'servicetype' => Type::OTPUSK
                        ]
                    ]
                ],
                4 => [
                    'title' => 'Командировка',
                    'data' => [
                        [
                            'servicetype' => Type::KOMANDIROVKA
                        ]
                    ]
                ],
                5 => [
                    'title' => 'Больничный',
                    'data' => [
                        [
                            'servicetype' => Type::BOLNICHNUI
                        ]
                    ]
                ],
                6 => [
                    'title' => 'Военный госпиталь',
                    'data' => [
                        [
                            'servicetype' => Type::VOENNUIGOSPITAL
                        ]
                    ]
                ],
                7 => [
                    'title' => 'Выходной',
                    'data' => [
                        [
                            'servicetype' => Type::VUHODNOI
                        ]
                    ]
                ]
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
     * Сформировать отчет версии
     * Результат формирования сохряняется в $this->html
     */
    public function generate ()
    {
        $year = $this->date['date']['year'];
        $month = $this->date['date']['mon'];
        $day = $this->date['date']['mday'];
        $schedule = new Schedule($this->unit, $this->date['unix'], $this->date['unix']);
        $records = $schedule->getDayTypesArray($year, $month, $day);
        $militarys_data = Military::getData($schedule->military, [
            'level_short',
            'fio_short'
        ]);
        $places = Place::getData(null, [
            'title'
        ]);

        $tmp['date'] = $this->date;

        $html = Report::getHtmlMaskHead($this->settings['orientation'], $this->settings['head'], $this->settings['head_data'][$this->settings['head']]);
        $html .= '
            <div align="center">Рапорт</div>
            <br>
            <div style="text-align: justify; text-indent: 40px;">' . Report::convertSpecialInStr($tmp, $this->settings['text']) . '</div>';

        foreach ($this->settings['blocks'] as $item) {
            $list = [];
            foreach ($item['data'] as $dataitem) {
                if (isset($records[$dataitem['servicetype']])) {
                    if ($dataitem['servicetype'] === Type::NARYAD) {
                        foreach ($records[$dataitem['servicetype']] as $military) {
                            $list_str = '';
                            if ($dataitem['place']) {
                                $place = $schedule->serviceload_data[$military][$year][$month][$day]['place'];
                                $list_str = $places[$place]['title'] . ' &ndash; ';
                            }
                            $list_str .= $militarys_data[$military]['level_short'] . ' ' . $militarys_data[$military]['fio_short'] . '<br>';
                            if (isset($dataitem['usertype'])) {
                                if ($schedule->serviceload_data[$military][$year][$month][$day]['type'] === $dataitem['usertype'])
                                    $list[] = $list_str;
                            } else $list[] = $list_str;
                        }

                    } else {
                        foreach ($records[$dataitem['servicetype']] as $military)
                            $list[] = $militarys_data[$military]['level_short'] . ' ' . $militarys_data[$military]['fio_short'] . '<br>';
                    }

                }
            }

            if (count($list)) {
                $html .= '<br><b>' . $item['title'] . ':</b><br>';
                foreach ($list as $list_item)
                    $html .= $list_item;
            }
        }

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

        // Шрифт
//        if (isset($USER->settings['report'][self::REPORT_KEY][self::REPORT_VERSION]['font']))
//            $default['font'] = intval($USER->settings['report'][self::REPORT_KEY][self::REPORT_VERSION]['font']);
//        else $default['font'] = $settings['font'];
//
//        $html .= '<div class="pdf_settings_more_item">
//            <div class="pdf_settings_more_item_title">Шрифт</div>
//            <div class="pdf_settings_more_item_value">
//                <select name="' . self::REPORT_KEY . '_font">';
//        for ($i = 6; $i < 36; $i++) {
//            $selected = ($default['font'] === $i) ? 'selected' : '';
//            $html .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
//        }
//        $html .= '</select>
//            </div>
//        </div>';

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