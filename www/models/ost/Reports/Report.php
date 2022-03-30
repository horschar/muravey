<?php

namespace OsT\Reports;

use OsT\Base\System;
use OsT\Military\Military;
use OsT\Military\State;
use OsT\Serviceload\Type;
use OsT\Unit;

/**
 * Class Report
 * @package OsT\Reports
 * @version 2022.03.10
 *
 * __construct                  Сформировать из данных $_SESSION отчеты
 * generate                     Сформировать HTML отчетов
 * getReportClassArray          Получить массив наименований классов отчетов
 * getReportClass               Получить имя класса отчета по его ключу
 * constructClassName           Сформировать путь к классу отчета либо версии отчета
 * getHtmlMaskHead              Получить HTML представление шапки отчета
 * calcMaskSettings             Проверить и внести в массив $settings настройки получателя из report_settings.php
 * getHtmlMaskSender            Получить HTML представление отправителя отчета
 * calcSenderDataBySource       Сформировать массив данных для getHtmlMaskSender в зависимости от источника данных
 * calcSenderSettings           Проверить и внести в массив $settings настройки отправителя из report_settings.php
 * convertSpecialInStr          Преобразовать специальные выражения строки в текст
 * getHtmlSelectUnit            Получить HTML представление списка выбора подразделения для окна параметров создания отчета
 * getHtmlPrintSettingsBox      Сгенерировать блок настроек при добавлении отчета в лист печати
 * getHtmlPrintTableItem        Сформировать html представление отчета (либо набора одинаковых отчетов) для таблицы очереди печати
 * getHtmlPrintTableItemSingle  Сформировать HTML представление отчета для таблицы очереди печати
 * getPDF                       Сформировать PDF из отчетов
 * getHtml                      Получить HTML представление всех отчетов
 * calculate_filter_array       Вычисление массива данных о военнослужащем для фильтрации функцией calculate_filter
 * calculate_filter             Проверка соответствия набора данных военнослужащего $data перечню правил фильтрации $rules
 *
 */
class Report
{

    public $reports;

    /**
     * Сформировать из данных $_SESSION отчеты
     * Report constructor.
     * @param $reports
     */
    public function __construct($reports)
    {
        $package_settings = [];
        if (count($reports)) {
            foreach ($reports as $report) {
                $version_class = \OsT\Reports\Report::constructClassName($report['key'], $report['version']);
                if ($version_class !== null) {
                    if (isset($report['package'])) {
                        $package = intval($report['package']);
                        if (!isset($package_settings[$package])) {
                            $tmp = Package::getData([$package], ['settings']);
                            $package_settings[$package] = $tmp[$package]['settings'];
                        }
                        unset($report['package']);
                        $report['settings'] = $package_settings[$package][$version_class::REPORT_KEY][$version_class::REPORT_VERSION];
                    }

                    $this->reports[] = new $version_class($report);
                }
            }
        }
    }

    /**
     * Сформировать HTML отчетов
     */
    public function generate ()
    {
        foreach ($this->reports as $report)
            $report->generate();
    }

    /**
     * Получить массив наименований классов отчетов
     * @return string[]
     */
    public static function getReportClassArray ()
    {
        return [
            'rsn' => 'RSN',
            'rno' => 'RNO',
            'rnk' => 'RNK',
            'rsz' => 'RSZ',
            'sov' => 'SOV',
        ];
    }

    /**
     * Получить имя класса отчета по его ключу
     * @param $key - ключ отчета по типу 'rsn'
     * @return string|null
     */
    public static function getReportClass ($key)
    {
        $arr = self::getReportClassArray();
        if (isset($arr[$key]))
            return $arr[$key];
        return null;
    }

    /**
     * Сформировать путь к классу отчета либо версии отчета
     * @param $report - ключ отчета типа rsn
     * @param null $version - ключ версии типа 1
     * @return string|null
     */
    public static function constructClassName ($report, $version = null)
    {
        $path = '\\OsT\\Reports\\';
        $report_class = self::getReportClass($report);
        if ($report_class !== null) {
            $report_path = $path . $report_class . '\\' . $report_class;
            if ($version === null)
                return $report_path;
            else {
                $version_class = $report_path::getVersionClassName($version);
                if ($version_class !== null)
                    return $path . $report_class . '\\' . $version_class;
            }
        }

        return null;
    }

    /**
     * Получить HTML представление шапки отчета
     * @return string
     */
    public static function getHtmlMaskHead ($orientation, $type,  $data)
    {
        switch ($orientation) {
            case 'P' :
                switch ($type) {
                    case 0 :
                        return '';
                    case 1 :
                        return '<div align="right">' . nl2br($data['text']) . '</div>';
                }
                break;
            case 'L':
                switch ($type) {
                    case 0 :
                        return '';
                    case 1 :
                        return '<div align="right">' . nl2br($data['text']) . '</div>';
                }
                break;
        }
        return '';
    }

    /**
     * Проверить и внести в массив $settings настройки получателя из report_settings.php
     * @param $settings - массив настроек верссии V::getSettings
     * @param $data - массив данных формы $_POST
     */
    public static function calcMaskSettings (&$settings, $data)
    {
        $settings['head'] = intval($data['head']);
        $head = $settings['head'];
        if ($head === 1)
            $settings['head_data'][$head]['text'] = $data['head' . $head . '_text'];
    }

    /**
     * Получить HTML представление отправителя отчета
     * @return string
     */
    public static function getHtmlMaskSender ($orientation, $mask, $source,  $data)
    {
        $tmp = $data['sender_data'][$mask];
        if ($tmp !== null)
            $tmp = $tmp[$source];
        $tmp['date'] = $data['date'];

        $sender = self::calcSenderDataBySource($source, $tmp);

        switch ($orientation) {
            case 'P' :
                switch ($mask) {
                    case 0 :
                        return '';
                    case 1 :
                        if ($sender === null)
                            $sender = [
                                'state' => '',
                                'level' => '',
                                'fio' => '',
                             ];
                        $date = $data['show_datecreate'] ? $data['date_str'] . ' г.' : '';
                        $font = $data['font'];
                        return '
                            <table class="endPage" style="font-size: ' . $font . 'px;">
                                <tr>
                                    <td colspan="2">' . $date . '</td>
                                    <td colspan="2">' . $sender['state'] . '</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>' . $sender['level'] . '</td>
                                    <td style="text-align:right;">' . $sender['fio'] . '</td>
                                </tr>
                            </table>';
                }
                return '';

            case 'L':
                switch ($mask) {
                    case 0 :
                        return '';

                    case 1 :
                        if ($sender === null)
                            $sender = [
                                'state' => '',
                                'level' => '',
                                'fio' => '',
                            ];
                        $font = $data['font'];
                        return '<table class="endPage" style="font-size: ' . $font . 'px; width: 40%; float: left;">
                                    <tr>
                                        <td colspan="2">' . $sender['state'] . '</td>
                                    </tr>
                                    <tr>
                                        <td>' . $sender['level'] . '</td>
                                        <td style="text-align:right;">' . $sender['fio'] . '</td>
                                    </tr>
                                </table>';

                    case 2 :
                        if ($sender === null)
                            $sender = [
                                'state' => '',
                                'level' => '',
                                'fio' => '',
                            ];
                        $font = $data['font'];
                        $date = $data['show_datecreate'] ? $data['date_str'] . ' г.' : '';
                        return '<table class="endPage" style="font-size: ' . $font . 'px;">
                                    <tr>
                                        <td colspan="2">' . $date . '</td>
                                        <td colspan="2">' . $sender['state'] . '</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td>' . $sender['level'] . '</td>
                                        <td style="text-align:right;">' . $sender['fio'] . '</td>
                                    </tr>
                                </table>';
                }
                break;
        }
        return '';
    }

    /**
     * Сформировать массив данных для getHtmlMaskSender в зависимости от источника данных
     * @param $type - тип источника данных
     * @param $data - произвольный набор данных
     * @return array|null
     */
    public static function calcSenderDataBySource ($type, $data)
    {
        $return = null;
        switch ($type) {
            case 0 :        // Нет
                break;

            case 1 :        // Текст
                $return['state'] = $data['state'];
                $return['level'] = $data['level'];
                $return['fio'] = $data['fio'];
                break;

            case 2 :        // Военнослужащий
                $military = $data['military'];
                $military_data = Military::getData([$military],
                    [
                        'levels_data',
                        'fio_short'
                    ]);
                $military_data = $military_data[$data['military']];
                $current_level_data = Military::getLevel($military, $data['date'], $military_data['levels_data']);

                $state_title = '';
                $military_states_data = State::getCurrentlyMilitaryStates($military, $data['date']);
                if (isset($military_states_data['always'])) {
                    $unit_state_data = \OsT\State::getData([$military_states_data['always']['state']], [
                        'title',
                        'title_abbreviation',
                    ]);
                    $unit_state_data = $unit_state_data[$military_states_data['always']['state']];
                    $state_title = $unit_state_data['title_abbreviation'] !== '' ? $unit_state_data['title_abbreviation'] : $unit_state_data['title'];
                }

                $return['state'] = $state_title;
                $return['level'] = $current_level_data['level_short'];
                $return['fio'] = $military_data['fio_short'];
                break;

            case 3 :        // Военнослужащий на должности
                $states = State::getMilitaryByState($data['state'], $data['date']);
                if (count($states)) {
                    $arr = [];
                    foreach ($states as $state)
                        $arr[$state['vrio']] = $state['military'];

                    // определение ключа vrio
                    $vrio_index = null;
                    if (!$data['vrio']) {
                        if (isset($arr[0]))
                            $vrio_index = 0;
                    } else {
                        if (isset($arr[1]))
                            $vrio_index = 1;
                        else $vrio_index = 0;
                    }

                    if ($vrio_index !== null) {
                        $state_data = \OsT\State::getData([$data['state']], [
                            'title',
                            'title_abbreviation',
                            'vrio_title',
                            'vrio_abbreviation',
                        ]);

                        if ($vrio_index === 0)
                            $state_title = $state_data[$data['state']]['title_abbreviation'] !== '' ? $state_data[$data['state']]['title_abbreviation'] : $state_data[$data['state']]['title'];
                        else $state_title = $state_data[$data['state']]['vrio_abbreviation'] !== '' ? $state_data[$data['state']]['vrio_abbreviation'] : $state_data[$data['state']]['vrio_title'];

                        $military = $arr[$vrio_index];
                        $military_data = Military::getData([$military],
                            [
                                'levels_data',
                                'fio_short'
                            ]);
                        $military_data = $military_data[$military];
                        $current_level_data = Military::getLevel($military, $data['date'], $military_data['levels_data']);

                        $return['state'] = $state_title;
                        $return['level'] = $current_level_data['level_short'];
                        $return['fio'] = $military_data['fio_short'];
                    }
                }
                break;
        }

        return $return;
    }

    /**
     * Проверить и внести в массив $settings настройки отправителя из report_settings.php
     * @param $settings - массив настроек верссии V::getSettings
     * @param $data - массив данных формы $_POST
     */
    public static function calcSenderSettings (&$settings, $data)
    {
        $settings['sender_source'] = intval($data['sender']);
        $source = $settings['sender_source'];
        $mask = $settings['sender_mask'];
        switch ($mask) {
            case 1 :
                switch ($source) {
                    case 1 :
                        $settings['sender_data'][$mask][$source]['state'] = $data['sender' . $source . '_state'];
                        $settings['sender_data'][$mask][$source]['level'] = $data['sender' . $source . '_level'];
                        $settings['sender_data'][$mask][$source]['fio'] = $data['sender' . $source . '_fio'];
                        break;

                    case 2 :
                        if (isset($data['sender' . $source . '_military'])) {
                            $military = intval($data['sender' . $source . '_military']);
                            if ($military !== -1)
                                $settings['sender_data'][$mask][$source]['military'] = $military;
                        }
                        break;

                    case 3 :
                        if (isset($data['sender' . $source . '_state'])) {
                            $state = intval($data['sender' . $source . '_state']);
                            if ($state !== -1) {
                                $settings['sender_data'][$mask][$source]['state'] = $state;
                                if (isset($data['sender' . $source . '_vrio']))
                                    $settings['sender_data'][$mask][$source]['vrio'] = true;
                                else $settings['sender_data'][$mask][$source]['vrio'] = false;
                            }
                        }
                        break;
                }

                break;
        }
    }

    /**
     * Преобразовать специальные выражения строки в текст
     * @param $str - исходная строка
     * @return string - результат
     */
    public static function convertSpecialInStr ($data, $str)
    {
        $specials = [
            '@date_дд.мм.гггг_г.' => '<span style=" white-space: nowrap;">' . $data['date']['str'] . ' г.</span>'
        ];
        foreach ($specials as $key => $value)
            $str = implode($value, explode($key, $str));

        return $str;
    }

    /**
     * Получить HTML представление списка выбора подразделения для окна параметров создания отчета
     * @param $key
     * @param null $default
     * @return string
     */
    public static function getHtmlSelectUnit ($key, $default = null)
    {
        global $STRUCT_TREE;
        $tree = [0 => $STRUCT_TREE];
        return \OsT\Unit::getSelectUnitHtml($tree, $default, $key . '_unit', null, [
            'onchange' => 'update_units_select(this, \'' . $key . '\')'
        ]);
    }

    /**
     * Сгенерировать блок настроек при добавлении отчета в лист печати
     * @param $report - ключ отчета
     * @param $version - ключ версии
     * @return mixed
     */
    public static function getHtmlPrintSettingsBox ($report, $version = null)
    {
        global $USER;

        // Определение версии
        if ($version !== null) {
            $version_class = \OsT\Reports\Report::constructClassName($report, $version);
            if ($version_class === null)
                $version = null;
        }

        if ($version === null) {
            $report_class = \OsT\Reports\Report::constructClassName($report);
            if (isset($USER->settings['reports'][$report_class::REPORT_KEY]['version_last_used'])) {
                $version = $USER->settings['reports'][$report_class::REPORT_KEY]['version_last_used'];
                $avaible_versions = $report_class::getVersionsKeys();
                if (!in_array($version, $avaible_versions))
                    $version = null;
            }
            if ($version === null)
                $version = $report_class::getDefaultVersion();

            $version_class = \OsT\Reports\Report::constructClassName($report, $version);
        }

        return $version_class::getHtmlPrintSettingsBox();
    }

    /**
     * Сформировать html представление отчета (либо набора одинаковых отчетов) для таблицы очереди печати
     * Выполнить проверку и преобразование пользовательских параметров формирования
     * @param $data
     * @return string
     */
    public static function getHtmlPrintTableItem ($data) {
        $version_class = \OsT\Reports\Report::constructClassName($data['report'], $data[$data['report'] . '_version']);
        $data = $version_class::calcPrintTableItemData($data);
        $html = '';

        if ($data['status']) {
            // Сохранение в настройки пользователя параметров формирования отчета
            $version_class::savePrintSettings($data['data'][0]);

            // Формирование HTML записей в таблицу очереди печати
            $title = $version_class::REPORT_TITLE;
            $data = $data['data'];
            foreach ($data as $rows)
                $html .= self::getHtmlPrintTableItemSingle ($title, $rows);
        }

        return $html;
    }

    /**
     * Сформировать HTML представление отчета для таблицы очереди печати
     * @param $title - название отчета
     * @param $data - набор атрибутов
     * @return string
     */
    public static function getHtmlPrintTableItemSingle ($title, $data)
    {
        $data_attr = '';
        foreach ($data as $key => $value)
            $data_attr .= ' data-' . $key . '="' . $value . '"';

        if (isset($data['unit'])) {
            $data['unit'] = intval($data['unit']);
            $data['unit_path'] = Unit::getPathStr($data['unit']);
        } else $data['unit_path'] = 'Нет данных';

        return '<tr' . $data_attr . '>
                    <td>' . $title . '</td>
                    <td>' . $data['version'] . '</td>
                    <td>' . $data['unit_path'] . '</td>
                    <td>' . System::parseDate($data['date']) . '</td>
                    <td>' . System::parseDate($data['create']) . '</td>
                    <td class="reportButtons">
                        <div class="button delete" title="Удалить" onclick="printQueue_deleteReport(this)"></div>
                    </td>
                </tr>';
    }

    /**
     * Сформировать PDF из отчетов
     * @throws \Mpdf\MpdfException
     */
    public function getPDF ()
    {

        $mpdf = new \Mpdf\Mpdf([
            'fontDir' => [ __DIR__ . '/../../../font'],
            'fontdata' => [
                    'verbena' => ['R' => 'VerbenaC.ttf'],
                    'freeserif' => [
                        'R' => 'FreeSerif.ttf',
                        'B' => 'FreeSerifBold.ttf',
                        'I' => 'FreeSerifItalic.ttf',
                        'BI' => 'FreeSerifBoldItalic.ttf',
                    ],
                ],
            'default_font' => 'freeserif',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 9,
            'margin_footer' => 9
        ]);
        $title = 'Муравей production output file ' . System::parseDate(time(), 'y-m-dTh-m');
        $mpdf->SetTitle($title);
        $stylesheet = file_get_contents(__DIR__ . '/../../../css/reports.css');
        $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
        foreach ($this->reports as $report) {
            $mpdf->AddPage($report->settings['orientation']);
            $mpdf->WriteHTML($report->html);
        }
        $mpdf->Output($title . '.pdf', 'I');
    }

    /**
     * Получить HTML представление всех отчетов
     * @return string
     */
    public function getHtml ()
    {
        $html = '';
        foreach ($this->reports as $report)
            $html .= $report->html;
        return $html;
    }

    /**
     * Вычисление массива данных о военнослужащем для фильтрации функцией calculate_filter
     * @param $data - массив, содержащий сырые данные
     *          [
     *              day_key -           относительный идентификатор выбранного дня
     *              servicetype -       тип служебной нагрузки военнослужащего в выбранный день
     *              serviceload_data -  массив дополнительных данных о служебной нагрузке военнослужащего в выбранный день
     *              military_data -     массив данных о военнослужащем
     *          ]
     * @example [
     *              "today" | "yesterday"
     *              1 | 2 | ...
     *              ["type":1,"from":12,"len":24,"place":5,"incoming":8]
     *              ["id":1,"level":12, "fname": ...]
     *          ]
     * @return array|null
     */
    public static function calculate_filter_array ($data)
    {
        global $SETTINGS;

        $return = @[
            '__id' => $data['military_data']['id'],
            '__day' => $data['day_key'],
            '__fio' => $data['military_data']['fname']
        ];
        $daytimecorrector = [
            'yesterday' => -24,
            'today' => 0
        ];
        if (isset($data['servicetype'])) {
            $return['servicetype'] = intval($data['servicetype']);
            if ($return['servicetype']) {

                // коррекция времени с помощью $daytimecorrector
                if (isset($data['serviceload_data']['from'])) {
                    $data['serviceload_data']['from'] += $daytimecorrector[$data['day_key']];
                    $data['serviceload_data']['incoming'] += $daytimecorrector[$data['day_key']];
                }

                // вычисление timeinterval
                if ($return['servicetype'] === Type::RABOCHUI) {
                    $return['timeinterval']['work'] = [
                        'from' => $SETTINGS['TIME_RABOCHIY_START'] + $daytimecorrector[$data['day_key']],
                        'to' => $SETTINGS['TIME_RABOCHIY_END'] + $daytimecorrector[$data['day_key']]
                    ];
                } elseif ($return['servicetype'] === Type::NARYAD) {

                    // вычисление usertype
                    $return['usertype'] = intval($data['serviceload_data']['type']);

                    if ($data['serviceload_data']['incoming'] !== $data['serviceload_data']['from']) {
                        if ($data['serviceload_data']['from'] < $data['serviceload_data']['incoming'])
                            $data['serviceload_data']['from'] += 24;

                        $return['timeinterval']['beforework'] = [
                            'from' => $data['serviceload_data']['incoming'],
                            'to' => $data['serviceload_data']['from']
                        ];
                    }

                    $return['timeinterval']['work'] = [
                        'from' => $data['serviceload_data']['from'],
                        'to' => $data['serviceload_data']['from'] + $data['serviceload_data']['len']
                    ];

                    // afterwork
                    /* Нет надобности, так как нигде подобное не применяется
                    $naryad_end_corrected = ($data['serviceload_data']['from'] + $data['serviceload_data']['len']) % 24;
                    $full_days = intval(($data['serviceload_data']['from'] + $data['serviceload_data']['len']) / 24);
                    if ($naryad_end_corrected < TIME_RABOCHIY_END)
                        $return['timeinterval']['afterwork'] = [
                            'from' => $data['serviceload_data']['from'] + $data['serviceload_data']['len'],
                            'to' => $full_days * 24 + TIME_RABOCHIY_END
                        ];
                    */
                }

                // вычисление military_data
                if (isset($data['military_data'])) {
                    if (isset($data['military_data']['level']))
                        $return['level'] = intval($data['military_data']['level']);

                    if (isset($data['military_data']['unit']))
                        $return['unit'] = intval($data['military_data']['unit']);
                }

                // вычисление time_filter
                if (isset($data['time_filter']))
                    $return['time_filter'] = $data['time_filter'];

                return $return;
            }
        }
        return null;
    }

    /**
     * Проверка соответствия набора данных военнослужащего $data перечню правил фильтрации $rules
     * @param $data - набор данных военнослужащего
     * @param $rules - правила проверки
     * @return bool
     */
    public static function calculate_filter ($data, $rules)
    {
        // По типу
        if (isset($rules['servicetype'])) {
            if ($rules['servicetype'] !== $data['servicetype'])
                return false;
        }

        // Фильтр по подтипу
        if (isset($rules['usertype'])) {
            if ($rules['usertype'] !== intval($data['usertype']))
                return false;
        }

        // Фильтр по званию
        if (isset($rules['level'])) {
            if (!in_array($data['level'] , $rules['level']))
                return false;
        }

        // Фильтр по времени
        if (isset($data['timeinterval']) && isset($rules['time_filter'])) {
            $crossing = false;
            foreach ($data['timeinterval'] as $interval_key => $time) {
                if ($rules['time_filter'][$interval_key]) {
                    if (!is_array($data['time_filter'])) {
                        if (System::intervalCrossing(
                            $time['from'],
                            $time['to'],
                            $data['time_filter'],
                            $data['time_filter']
                        )) {
                            $crossing = true;
                        }
                    } else {
                        if (System::intervalCrossing(
                            $time['from'],
                            $time['to'],
                            $data['time_filter']['from'],
                            $data['time_filter']['to']
                        )) {
                            $crossing = true;
                        }
                    }
                }
            }
            if (!$crossing)
                return false;
        }

        return true;
    }

}