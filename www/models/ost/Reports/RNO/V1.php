<?php

namespace OsT\Reports\RNO;

use OsT\Base\System;
use OsT\Reports\Report;

/**
 * Class V1
 * @package OsT\Reports
 * @version 2022.03.10
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
class V1 extends RNO
{

    const REPORT_VERSION = '1';

    public $date;           // Дата, на которую формируется служебная нагрузка [unix|date|str]
    public $text;           // Текст рапорта
    public $settings;       // Настройки отчета
    public $html = null;    // HTML представление отчета

    /**
     * V1 constructor.
     * @param $data [
     *          unit - подразделение
     *          text - текст рапорта
     *          date - дата, на которую формируется отчет Unix
     *          settings - настройки версии (не обязательный параметр)
     */
    public function __construct($data)
    {
        $this->date['unix'] = intval($data['date']);
        $this->date['date'] = getdate($this->date['unix']);
        $this->date['str'] = System::parseDate($this->date['unix']);
        $this->text = trim($data['text']);
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
         * show_datecreate                      отображать дату создания отчета
         * font                                 размер шрифта для вычисления относительных величин элементов в отчете
         * text                                 текст отчета, который будет помещен под надписью "Рапорт"
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
            'text' => 'Настоящим докладываю, что во взводе связи и ИТ личный состав на лицо. Лиц незаконно отсутствующих нет.',
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
        if (!isset($USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION]))
            $USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION] = self::getDefaultSettings();
        $USER->settings['reports'][self::REPORT_KEY][self::REPORT_VERSION]['text'] = htmlspecialchars_decode($data['text']);
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
        $html = Report::getHtmlMaskHead($this->settings['orientation'], $this->settings['head'], $this->settings['head_data'][$this->settings['head']]);
        $html .= '
            <div align="center">Рапорт</div>
            <br>
            <div style="text-align: justify; text-indent: 40px;">' . htmlspecialchars_decode($this->text) . '</div>';

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
        $settings = self::getSettings();

        //*****************************      Визуализация

        $html = '
        <div class="headLinkButtonsWindow middle shadow_window ' . self::REPORT_KEY . '">
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

        //  Текст
        $html .= '
        <div class="pdf_settings_more_item">
            <div class="pdf_settings_more_item_title">Текст</div>
            <div class="pdf_settings_more_item_value">
                <textarea name="' . self::REPORT_KEY . '_text">'
                . $settings['text'] .
                '</textarea>
            </div>
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

        $from = System::parseDate($data[self::REPORT_KEY . '_from'], 'unix', 'd/m/y');
        if ($from) {
            $to = (boolval($data[self::REPORT_KEY . '_to_auto'])) ? System::parseDate($data[self::REPORT_KEY . '_to'], 'unix', 'd/m/y') : $from;

            if ($to || $from <= $to) {
                $return['status'] = true;
                $cicle = intval(($to - $from + System::TIME_DAY + System::TIME_HOUR * 2) / System::TIME_DAY);
                for ($i = 0; $i < $cicle; $i++) {
                    $tmp = [];
                    $tmp['key'] = self::REPORT_KEY;
                    $tmp['version'] = self::REPORT_VERSION;
                    $tmp['date'] = $from + System::TIME_HOUR * 2 + $i * System::TIME_DAY;
                    $tmp['create'] = $tmp['date'];
                    $tmp['text'] = htmlspecialchars($data[self::REPORT_KEY . '_text']);
                    $return['data'][] = $tmp;
                }

            } else $return['errors']['to'] = true;
        } else $return['errors']['from'] = true;

        return $return;
    }

}