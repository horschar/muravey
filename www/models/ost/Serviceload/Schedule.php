<?php

namespace OsT\Serviceload;

use OsT\Base\DB;
use OsT\Base\System;
use OsT\Military\State;

/**
 * Управление графиком нагрузки
 * Class Schedule
 * @package OsT\Serviceload
 * @version 2022.03.10
 *
 * __construct                          Schedule constructor
 * prepareMilitaryData                  Сформировать ключевые массивы данных о военнослужащих
 * uploadServiceload                    Загрузить данные из БД о служебной нагрузке военнослужащих $this->military
 * prepareServiceloadUnitArray          Сформировать массив служебной нагрузки $this->serviceload_unit
 * checkMilitaryScheduleHasData         Проверить наличие данных служебной нагрузки для запрашиваемого периода
 * genDefaultServiceLoad                Сформировать массив служебной нагрузки по умолчанию для военнослужащих
 * splitDefaultServiceLoadArray         Разделить массив служебной нагрузки по умолчанию из метода Serviceload/Military::genDefaultMonthRecords
 * genMilitaryUnitsArr                  Сформировать массив подразделений военного по дням типа [military][year][month][day] = $unit
 * genServiceloadCellHtml_NoData        Сформировать HTML представление пустой ячейки служебной нагрузки военнослужащего
 * genServiceloadCellHtml               Сформировать HTML представление для ячейки служебной нагрузки военнослужащего с выпадающим списком
 * genServiceloadCellValueHtml          Сформировать HTML представление значения ячейки служебной нагрузки военнослужащего
 * genAbsentWindowHtml                  Сформировать HTML представление окна управления периодом отсутствия
 * getNaryadDefaultData                 Получить массив значений по умолчанию для окна управления данными типа служба
 * getNaryadLengthArr                   Получить массив вариантов продолжительности службы
 * getNaryadWindowHtml                  Сформировать HTML представление окна управления службой
 * getDayArray                          Получить массив записей служебной нагрузки на день типа [военнослужащий] = тип нагрузки
 * getDayTypesArray                     Получить массив записей служебной нагрузки на день типа [servicetype][n] = military
 *
 */
class Schedule
{

    const TYPE_RABOCHUI =           Type::RABOCHUI;
    const TYPE_NARYAD =             Type::NARYAD;
    const TYPE_KOMANDIROVKA =       Type::KOMANDIROVKA;
    const TYPE_OTPUSK =             Type::OTPUSK;
    const TYPE_BOLNICHNUI =         Type::BOLNICHNUI;
    const TYPE_VOENNUIGOSPITAL =    Type::VOENNUIGOSPITAL;
    const TYPE_VUHODNOI =           Type::VUHODNOI;

    public $unit;                           // идентификатор подразделения
    public $from;                           // массив преобразований начала интервала времени [unix|date] = value;
    public $to;                             // массив преобразований конца интервала времени [unix|date] = value;
    public $military;                       // массив идентификаторов военнослужащих в подразделении
    public $military_data;                  // массив данных о военнослужащих
    public $military_state;                 // массив записей из послужного списка воеенослужащих
    public $used_days = [];                 // массив затронутых дней [y][m][d] = true
    public $serviceload = [];               // массив записей служебной нагрузки [military][y][m][d] = type
    public $serviceload_unit = [];          // массив записей служебной нагрузки [unit][military][y][m][d] = type
    public $serviceload_data = [];          // массив доп данных служебной нагрузки [military][y][m][d] = data[]
    public $military_units;                 // массив подразделений военных по днм [военнослужащий][y][m][d] = unit

    public $flag_correcttimeinterval = false;    // указывает на корректность указания периода времени
    public $flag_hasmilitary = false;            // указывает на наличие военнослужащих в выбранный период времени
    public $flag_workability = false;            // указывает на готовность к работе с объектом

    /**
     * Schedule constructor.
     * В экземпляр класса будут загружены данные служебной нагрузки военнослужащих, которые
     * находились на службе в подразделении $unit (дочерем) в период времени от $from до $to
     * @param $unit - подразделение
     * @param $from - начало интервала времени Unix
     * @param $to - конец интервала времени Unix
     */
    public function __construct($unit, $from, $to)
    {
        $this->from['unix'] = intval($from);
        $this->to['unix'] = intval($to);
        $this->unit = intval($unit);

        if ($this->from['unix'] && $this->to['unix']) {
            $this->flag_correcttimeinterval = true;
            $this->from['date'] = getdate($this->from['unix']);
            $this->to['date'] = getdate($this->to['unix']);
            $this->used_days = System::getYmdArray($this->from['unix'], $this->to['unix']);

            $military_states = \OsT\Military\Military::getByUnit(
                $unit,
                [
                    'from' => $this->from['unix'],
                    'to' => $this->to['unix']
                ],
                true,
                false
            );

            if ($military_states) {
                $this->flag_hasmilitary = true;
                $this->prepareMilitaryData($military_states);
                $this->uploadServiceload();

                // Заполнить данные о служебной нагрузке в случае их отсутствия
                $no_data = $this->checkMilitaryScheduleHasData();
                $this->genDefaultServiceLoad($no_data);

                // Создать массивы преобразования данных служебной нагрузки
                $this->prepareServiceloadUnitArray();
                $this->genMilitaryUnitsArr();

                $this->flag_workability = true;
            }
        }
    }

    /**
     * Сформировать ключевые массивы данных о военнослужащих
     *  Сформировать данные $this->military
     *  Сформировать данные $this->military_data
     *  Сформировать данные $this->military_state
     * @param $military_states - выходной массив \OsT\Military\Military::getByUnit
     */
    public function prepareMilitaryData ($military_states)
    {
        foreach ($military_states as $key => $value) {
            $military = intval($value['military']);
            unset($value['military']);
            $value['unit'] = intval($value['unit']);
            $value['date_from'] = intval($value['date_from']);
            $value['date_to'] = intval($value['date_to']);
            $value['vrio'] = 0;

            $this->military[$military] = $military;
            $this->military_state[$military][] = $value;
        }

        $this->military_data = \OsT\Military\Military::getData($this->military, [
            'fio_short',
            'level_short'
        ]);
    }

    /**
     * Загрузить данные из БД о служебной нагрузке военнослужащих $this->military
     * Сформировать данные $this->serviceload
     * Сформировать данные $this->serviceload_data
     */
    public function uploadServiceload ()
    {
        $serviceload = Military::getServiceloadMulti($this->military);
        foreach ($serviceload as $military => $data) {
            $this->serviceload[$military] = $data['schedule'];
            $this->serviceload_data[$military] = $data['schedule_data'];
        }
    }

    /**
     * Сформировать массив служебной нагрузки $this->serviceload_unit
     * Массив имеет вид [unit][military][y][m][d] = type
     * В данном массиве наличие данных указывает на нахождение военного в определенном подразделении
     */
    private function prepareServiceloadUnitArray ()
    {
        foreach ($this->military_state as $military => $works) {
            foreach ($works as $work) {
                $date_from = $work['date_from'];
                $date_to = $work['date_to'] ? $work['date_to'] : TIME_LAST_SECOND;
                foreach ($this->serviceload[$military] as $year => $yearData)
                    foreach ($yearData as $month => $monthData)
                        foreach ($monthData as $day => $typeload) {
                            $day_time = System::gettimeBeginDay($year, $month, $day);
                            if (System::intervalCrossing($date_from, $date_to, $day_time, $day_time))
                                $this->serviceload_unit[$work['unit']][$military][$year][$month][$day] = $typeload;
                        }
            }
        }
    }

    /**
     * Проверить наличие данных служебной нагрузки для запрашиваемого периода
     * Данные в массиве на выходе указывают на то, что военный находится на службе в данный месяц,
     * тем не менее служебная нагрузка по умолчанию для этого периода еще не рассщитывалась
     * @return array
     *      [] - все данные имеются
     *      [military][y][m] = true - массив месяцев, в которые военнослужащие не имеют данных служебной нагрузки
     */
    private function checkMilitaryScheduleHasData ()
    {
        $no_data = [];
        foreach ($this->used_days as $year => $months)
            foreach ($months as $month => $days)
                foreach ($this->serviceload as $military => $serviceload) {
                    if (!isset($serviceload[$year][$month])) {
                        $month_interval = System::convertMonthToTimeInterval($year, $month);
                        if (State::checkTimeIntervalCrossingState($this->military_state[$military], $month_interval['from'], $month_interval['to']))
                            $no_data[$military][$year][$month] = true;
                    }
                }
        return $no_data;
    }

    /**
     * Сформировать массив служебной нагрузки по умолчанию для военнослужащих
     * @param $array [military][y][m] = true - массив месяцев, в которые военнослужащим необходимо установить служебную нагрузку по умолчанию
     */
    public function genDefaultServiceLoad ($array)
    {
        if (count($array)) {
            $update = [];
            foreach ($array as $military => $militaryData) {
                foreach ($militaryData as $year => $yearData) {
                    foreach ($yearData as $month => $monthData) {
                        $default = Military::genDefaultMonthRecords($military, $year, $month, $this->military_state[$military]);
                        if (count($default)) {
                            $default = self::splitDefaultServiceLoadArray($default);
                            $this->serviceload[$military][$year][$month] = $default['records'];
                            $this->serviceload_data[$military][$year][$month] = $default['records_data'];
                        } else {
                            unset($this->serviceload[$military][$year][$month]);
                            unset($this->serviceload_data[$military][$year][$month]);
                        }
                    }
                }
                $update['schedule'][$military] = json_encode($this->serviceload[$military]);
                $update['schedule_data'][$military] = json_encode($this->serviceload_data[$military]);
            }
            DB::updateArrById('ant_military_serviceload', 'schedule', $update['schedule'], 'military');
            DB::updateArrById('ant_military_serviceload', 'schedule_data', $update['schedule_data'], 'military');
        }
    }

    /**
     * Разделить массив служебной нагрузки по умолчанию из метода Serviceload/Military::genDefaultMonthRecords
     * @param $arr
     * @return array[]
     */
    public static function splitDefaultServiceLoadArray ($arr)
    {
        $records = [];
        $records_data = [];
        foreach ($arr as $day => $value) {
            $records[$day] = $value['type'];
            if (isset($value['data']))
                $records_data[$day] = $value['data'];
        }
        return ['records' => $records, 'records_data' => $records_data];
    }

    /**
     * Сформировать массив подразделений военного по дням типа [military][year][month][day] = $unit
     */
    public function genMilitaryUnitsArr ()
    {
        $arr = [];
        foreach ($this->serviceload_unit as $unit => $unitData)
            foreach ($unitData as $military => $militaryData)
                foreach ($militaryData as $year => $yearData)
                    foreach ($yearData as $month => $monthData)
                        foreach ($monthData as $day => $servicetype)
                            $arr[$military][$year][$month][$day] = $unit;

        $this->military_units = $arr;
    }

    /**
     * Сформировать HTML представление пустой ячейки служебной нагрузки военнослужащего
     * Для графика нагрузки schedule_edit.php
     * @param $military - идентификатор военнослужащего
     * @param $day - день месяца
     * @return string - HTML представление
     */
    public static function genServiceloadCellHtml_NoData ($military, $day)
    {
        return '
        <div class="mySelect mil' . $military . ' day' . $day . '" onmouseover="scheduleMove(this)" data-military="' . $military . '" data-day="' . $day . '">
            <div class="value"></div>
        </div>';
    }

    /**
     * Сформировать HTML представление для ячейки служебной нагрузки военнослужащего с выпадающим списком
     * Для графика нагрузки schedule_edit.php
     * @param $military - идентификатор военнослужащего
     * @param $day - день месяца
     * @param $servicetype - идентификатор типа служебной нагрузки
     * @param $servicetype_arr - массив данных о типах служебной нагрузки
     * @param $masks - массив шаблонов пользователя
     * @param null $additional_data - дополнительные данные о служебной нагрузке военнослужащего (schedule_data)
     * @return string - HTML представление
     */
    public function genServiceloadCellHtml ($military, $day, $servicetype, $servicetype_arr, $masks = [], $additional_data = null)
    {
        $html_list = '';
        // Формирование выпадающего списка
        $html_list = '<div class="items">';
        foreach ($servicetype_arr as $type => $item) {
            $mask_list = '';
            if ($type === Type::NARYAD) {
                if (count($masks)) {
                    foreach ($masks as $key => $mask) {
                        if ($mask['enabled']) {
                            if (in_array($military, $mask['data']['rage']))
                                $mask_list .= '<div class="naryad_maskItem" onclick="naryad_mask(' . $military . ', ' . $day . ', ' . $mask['id']  . ');">' . $mask['title'] . '</div>';
                        }
                    }
                    if ($mask_list !== '')
                        $mask_list = '<div class="naryad_maskBl" style="background-color: #' . $item['color'] . '">' . $mask_list . '</div>';
                }
            }
            $html_list .= '<div class="itemB bt_' . $item['id'] . '"><div style="background-color:#' . $item['color'] . ';" class="item t_' . $item['id'] . '" data-id="' . $item['id'] . '">' . $item['title'] . '</div>' . $mask_list . '</div>';
        }
        $html_list .= '</div>';

        // Формирование ячейки
        $html = '<div id="col_' . $military . '_' . $day . '" class="mySelect mil' . $military . ' day' . $day . '" data-military="' . $military . '" data-day="' . $day . '">
            ' . self::genServiceloadCellValueHtml($servicetype, $servicetype_arr, $additional_data) .
            $html_list .
            '</div>';

        return $html;
    }

    /**
     * Сформировать HTML представление значения ячейки служебной нагрузки военнослужащего
     * Для графика нагрузки schedule_edit.php
     * @param $servicetype - идентификатор типа служебной нагрузки
     * @param $servicetype_arr - массив данных о типах служебной нагрузки
     * @param null $additional_data - дополнительные данные о служебной нагрузке военнослужащего (schedule_data)
     * @return string - HTML представление
     */
    public static function genServiceloadCellValueHtml ($servicetype, $servicetype_arr, $additional_data = [])
    {
        $additional_data_html = 'data-type="' . $servicetype . '" ';
        if (is_array($additional_data))
            foreach ($additional_data as $key => $value)
                $additional_data_html .= 'data-' . $key . '="' . $value . '" ';

        return '<div class="value" onmousedown="mySelectMouseDown(event)"  onmouseup="mySelectMouseUp(event)" style="background-color: #' . $servicetype_arr[$servicetype]['color'] . ';" ' . $additional_data_html . '><div>' . $servicetype_arr[$servicetype]['title_short'] . '</div></div>';
    }

    /**
     * Сформировать HTML представление окна управления периодом отсутствия
     * @param $from - начало диапазона времени в формате Unix
     * @param $to - конец диапазона времени в формате Unix
     * @param $type - идентификатор типа отсутствия
     * @param $type_title - наименование типа отсутсвия
     * @param $fio_short - ФИО военнослужащего в формате Иванов И.И.
     * @param $edit - указывает на режим работы с периодом отсутствия
     *          0 - создание,
     *          1 - изменение
     * @param int $absent_id - идентификатор периода отсутствия (необходим в случае, когда $edit = 1)
     * @return string - HTML представление
     */
    public static function genAbsentWindowHtml ($from, $to, $type, $type_title, $fio_short, $edit, $absent_id = 0)
    {
        $delete_button = $edit ? '<div class="naryadButton left" onclick="otpuskDelete();">Удалить</div>' : '';

        $html = '<div class="naryadTitle">' . $type_title . ' ' . $fio_short . '</div>';

        $html .= '<div class="naryadDataBl">
                    Дата начала
                    <div class="inputTextBox">
                        <input class="datepicker" name="otpusk_from" autocomplete="off" value="' . System::parseDate($from, 'd/m/y') . '">
                    </div>
                </div>
                
                <div class="naryadDataBl">
                    Дата окончания
                    <div class="inputTextBox">
                        <input class="datepicker" name="otpusk_to" autocomplete="off" value="' . System::parseDate($to, 'd/m/y') . '">
                    </div>   
                </div>
                
                <input type="hidden" name="otpusk_type" value="' . $type . '">
                <input type="hidden" name="otpusk_mode" value="' . $edit . '">
                <input type="hidden" name="otpusk_id" value="' . $absent_id . '">
                ' . $delete_button . '
                <div class="naryadButton" onclick="otpuskOk();">Сохранить</div>
            ';

        return $html;
    }

    /**
     * Получить массив значений по умолчанию для окна управления данными типа служба
     * @return array
     */
    public static function getNaryadDefaultData ()
    {
        global $SETTINGS;
        return [
            'type' => 0,
            'from' => 10,
            'len' => 24,
            'place' => 0,
            'incoming' => $SETTINGS['TIME_RABOCHIY_START']
        ];
    }

    /**
     * Получить массив вариантов продолжительности службы
     * @return int[]
     */
    public static function getNaryadLengthArr ()
    {
        return [4, 5, 6, 7,  8, 9, 10, 11, 12, 24];
    }

    /**
     * Сформировать HTML представление окна управления службой
     * @param $fio_short - ФИО военнослужащего в формате Иванов И.И.
     * @param $date - дата на которую указывается служба в формате 31.12.2022
     * @param $incoming - час прибытия в формате 23
     * @param $from - час заступления в формате 23
     * @param $length - продолжительность службы в формате 8
     * @param $subtypes - массив всех подтипов службы
     *              [ id => title ], [...]
     * @param $subtype - наименования подтипа службы
     * @param $places - массив мест несения службы
     *              [ id => title ], [...]
     * @param $place - наименование места несения службы
     *
     * @return string - HTML представление
     */
    public static function getNaryadWindowHtml ($fio_short, $date, $incoming, $from, $length, $subtypes, $subtype, $places, $place)
    {
        $html = '<div class="naryadTitle">' . $fio_short . ' на ' . $date . '</div>';

        $html .= '<div class="naryadDataBl">
                    Тип службы
                    <div class="inputTextBox">
                        <input name="naryad_type">
                        <script>
                            TextSelect($("input[name=naryad_type]"), "naryad_type", ' . System::php2js('', $subtypes, false, true) . ', ' . System::php2js('', $subtype, false, true) . ', "input", false);
                        </script>
                    </div>
                </div>
                
                <div class="naryadDataBl">
                    Время прибытия
                    <select name="naryad_incoming">';

        for ($i=0; $i <= 24; $i++) {
            $selected = ($i === intval($incoming)) ? 'selected' : '';
            $html .= '<option value="' . $i . '" ' . $selected . '>' . System::i2d($i) . ':00</option>';
        }

        $html .= '    </select>
                </div>
                
                <div class="naryadDataBl">
                    Время заступления
                    <select name="naryad_from">';

        for ($i=0; $i < 24; $i++) {
            $selected = ($i === intval($from)) ? 'selected' : '';
            $html .= '<option value="' . $i . '" ' . $selected . '>' . System::i2d($i) . ':00</option>';
        }

        $html .= '    </select>
                </div>
                <div class="naryadDataBl">
                    Продолжительность
                    <select name="naryad_length">';

        $length_arr = self::getNaryadLengthArr();
        foreach ($length_arr as $item) {
            $selected = ($item === intval($length)) ? 'selected' : '';
            $html .= '<option value="' . $item . '" ' . $selected . '>' . $item . ' ч.</option>';
        }

        $html .= '    </select>
                </div>
                <div class="naryadDataBl">
                    Место
                    <div class="inputTextBox">
                        <input name="naryad_place">
                        <script>
                            TextSelect($("input[name=naryad_place]"), "naryad_place", ' . System::php2js('', $places, false, true) . ', ' . System::php2js('', $place, false, true) . ', "input", false);
                        </script>
                    </div>
                </div>';

        $html .= '
                <div class="hint">Для удобства заполнения можете воспользоваться <a href="mask.php" target="_blank">шаблонами</a></div>
                <div class="naryadButton" onclick="naryadOk();">Готово</div>
            ';

        return $html;
    }

    /**
     * Получить массив записей служебной нагрузки на день типа [военнослужащий] = тип нагрузки
     * @param $year - год
     * @param $month - месяц
     * @param $day - день
     * @return array [военнослужащий] = тип нагрузки
     */
    public function getDayArray ($year, $month, $day)
    {
        $arr = [];
        foreach ($this->serviceload_unit as $unit => $unitData)
            foreach ($unitData as $military => $militaryData)
                if (isset($militaryData[$year][$month][$day]))
                    $arr[$military] = $militaryData[$year][$month][$day];
        return $arr;
    }

    /**
     * Получить массив записей служебной нагрузки на день типа [servicetype][n] = military
     * @param $year - год
     * @param $month - месяц
     * @param $day - день
     * @return array [servicetype][n] = military
     */
    public function getDayTypesArray ($year, $month, $day)
    {
        $arr = [];
        $data = $this->getDayArray($year, $month, $day);
        foreach ($data as $military => $type)
            $arr[$type][] = $military;
        return $arr;
    }

}