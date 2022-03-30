<?php
namespace OsT\Serviceload;

use OsT\Base\System;
use OsT\Military\Absent;
use OsT\Military\State;
use PDO;

/**
 * Управление служебной нагрузкой военнослужащего
 * Class Military
 * @package OsT\Serviceload
 * @version 2022.03.11
 *
 * getServiceload               Получить массив данных о служебной нагрузке военнослужащего
 * getServiceloadMulti          Получить массив данных о служебной нагрузке военнослужащих
 * insertServiceload            Добавить в БД служебную нагрузку военнослужащего
 * updateServiceload            Обновить в БД служебную нагрузку военнослужащего
 * genDefaultRecords            Рассчитать значение служебной нагрузки по умолчанию для определенного военнослужащего на период с $from до $to
 * genDefaultMonthRecords       Сформировать массив служебной нагрузки по умолчанию для военнослужащено на месяц
 * genRecordsAfterStatesUpdate  Выполнить преобразование данных о служебной нагрузке военнослужащего после изменения его послужного списка (занимаемых должностей)
 * _clearByInterval             Удалить записи в служебной нагрузке $serviceload с $from по $to
 * _clear                       Сбросить (очистить) график служебной нагрузки военнослужащего
 * _clearAll                    Сбросить (очистить) график служебной нагрузки всех военнослужащих
 * _delete                      Удалить данные о служебной нагрузке военнослужащего
 * _deleteAll                   Удалить данные о служебной нагрузке всех военнослужащих
 *
 */
class Military
{

    const TABLE = 'ant_military_serviceload';

    /**
     * Получить массив данных о служебной нагрузке военнослужащего
     * @param $military
     * @return array
     *            schedule - массив с типами нагрузки по дням
     *            data - масасив с доп данными нагрузки по дням
     */
    public static function getServiceload ($military)
    {
        global $DB;
        $return = null;

        $q = $DB->prepare('
            SELECT schedule,
                   schedule_data
            FROM   ' . self::TABLE . '
            WHERE military = ?');
        $q->execute([$military]);

        if ($q->rowCount()) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            $return['schedule'] = json_decode($data['schedule'], true);
            $return['schedule_data'] = json_decode($data['schedule_data'], true);
        }

        return $return;
    }

    /**
     * Получить массив данных о служебной нагрузке военнослужащих
     * @param array $military - массив идентификаторов военнослужащих
     * @return array
     *          [идентификатор военнослужащего =>
     *            [
     *              schedule - массив с типами нагрузки по дням
     *              schedule_data - масасив с доп данными нагрузки по дням
     *              ]
     *          ]
     */
    public static function getServiceloadMulti ($military = [])
    {
        global $DB;
        $return = null;

        if (count($military)) {
            $sql_int_array = ' WHERE military IN (' . System::convertArrToSqlStr($military) . ')';
            $q = $DB->prepare('
                SELECT schedule,
                       schedule_data,
                       military
                FROM   ' . self::TABLE
                . $sql_int_array);
            $q->execute();

            if ($q->rowCount()) {
                $return = [];
                $data = $q->fetchAll(PDO::FETCH_ASSOC);
                foreach ($data as $key => $value) {
                    $military = intval($value['military']);
                    $return[$military] = [
                        'schedule' => json_decode($value['schedule'], true),
                        'schedule_data' => json_decode($value['schedule_data'], true)
                    ];
                }
            }
        }

        return $return;
    }

    /**
     * Добавить в БД служебную нагрузку военнослужащего
     * @param $military - идентификатор военнослужащего
     * @param $serviceload - массив служебной нагрузки
     *              schedule
     *              schedule_data
     */
    public static function insertServiceload ($military, $serviceload = null)
    {
        global $DB;

        if ($serviceload === null)
            $serviceload = [
                'schedule' => [],
                'schedule_data' => []
            ];

        $serviceload['military'] = intval($military);
        $serviceload['schedule'] = json_encode($serviceload['schedule']);
        $serviceload['schedule_data'] = json_encode($serviceload['schedule_data']);

        $DB->_insert(
            self::TABLE,
            $serviceload
        );
    }

    /**
     * Обновить в БД служебную нагрузку военнослужащего
     * @param $military - идентификатор военнослужащего
     * @param $serviceload - массив служебной нагрузки
     *              schedule
     *              schedule_data
     */
    public static function updateServiceload ($military, $serviceload)
    {
        global $DB;

        $serviceload['schedule'] = json_encode($serviceload['schedule']);
        $serviceload['schedule_data'] = json_encode($serviceload['schedule_data']);

        $DB->_update(
            self::TABLE,
            $serviceload,
            [['military = ', $military]]
        );
    }

    /**
     * Рассчитать значение служебной нагрузки по умолчанию для определенного военнослужащего на период с $from до $to
     *  Если период затрагивает месяц, данные для которого не определены в базе данных, конечный массив данных наполняется данными по умолчанию для этого месяца
     *  Если же данные на этот месяц уже были указаны в базе, выбранный диапазон времени наполняется данными по умолчанию
     *  На выходе функции массив на все периоды, но с уже внесенными исправлениями
     *
     * @param $military -идентификатор военнослужащего
     * @param $from - время с в формате Unix
     * @param $to - время до в формате Unix
     * @param array $absent - массив данных о периодах отсутствия
     * @param array $states - массив данных о должностях
     *                  [ 0 => [
     *                               'state'        идентификатор должности
     *                               'vrio'         временное исполнение обязанностей
     *                               'date_from'    начало в формате Unix
     *                               'date_to'      конец в формате Unix
     *                          ]
     *                  , ... ]
     * @param array $dontUseAbsent - массив идентификаторов периодов отсутствия, которые не стоит учитывать при формировании набора значений по умолчанию
     * @param bool $dontCreate - false указывает, что нужно создать записи о служебной нагрузке в месяце, если ранее о нем не было записей и наоборот если true
     * @param null $serviceload - указание вручную массива служебной нагрузки вместо self::getServiceload($military)
     * @return array
     */
    public static function genDefaultRecords ($military, $from, $to, $absent = null, $states = null, $dontUseAbsent = [], $dontCreate = true, $serviceload = null)
    {
        $default = [];

        if ($serviceload === null)
            $records = self::getServiceload($military);
        else $records = $serviceload;

        if ($records !== null) {

            // парсинг from , to к общему формату
            $from = System::gettimeBeginDayFromTime($from);
            $to = System::gettimeBeginDayFromTime($to);

            // Определение дней отпуска, командировки, больничного, воен. госпит.
            if ($absent === null) {
                $absent = Absent::getArrByMilitary($military);
                if (count($absent)) {
                    $absent = Absent::getData($absent, [
                        'date_from',
                        'date_to',
                        'type',
                        'id'
                    ]);
                }
            }

            // Определение дней, в которые военный служит (контракт)
            if ($states === null) {
                $states = State::getIdArrByMilitary($military);
                $states = State::getData(
                    $states,
                    [
                        'state',
                        'vrio',
                        'date_from',
                        'date_to'
                    ]);
            }

            $current_time = $from; // текущее время в цикле
            while (true) {
                $date = getdate($current_time);
                $year = $date['year'];
                $month = $date['mon'];
                $day = $date['mday'];

                if (!isset($default[$year][$month]))
                    $default[$year][$month] = self::genDefaultMonthRecords($military, $year, $month, $states, $dontUseAbsent, $absent);

                if (!isset($records['schedule'][$year][$month])) {
                    if (!$dontCreate)
                        $records['schedule'][$year][$month] = $default[$year][$month];
                    $nextMonth = System::nextMonth($year, $month);
                    $nextDay = System::gettimeBeginDay($nextMonth['year'], $nextMonth['month'], 1);
                    if ($nextDay <= $to)
                        $current_time = $nextDay;
                    else break;

                } else {
                    if (System::intervalCrossing($current_time, $current_time, $from, $to)) {
                        if (isset($default[$year][$month][$day]['type']))
                            $records['schedule'][$year][$month][$day] = $default[$year][$month][$day]['type'];
                        else unset($records['schedule'][$year][$month][$day]);
                        if (isset($default[$year][$month][$day]['data']))
                            $records['schedule_data'][$year][$month][$day] = $default[$year][$month][$day]['data'];
                        else unset($records['schedule_data'][$year][$month][$day]);

                        $current_time = System::plusDay($current_time);

                    } else break;
                }
            }
        }

        return $records;
    }

    /**
     * Сформировать массив служебной нагрузки по умолчанию для военнослужащено на месяц
     * @param $military     - идентификатор военнослужащего
     * @param $year         - год
     * @param $month        - месяц
     * @param array $states - массив данных о должностях
     *                  [ 0 => [
     *                               'state'        идентификатор должности
     *                               'vrio'         временное исполнение обязанностей
     *                               'date_from'    начало в формате Unix
     *                               'date_to'      конец в формате Unix
     *                          ]
     *                  , ... ]
     * @param array $absent_black_list - массив идентификаторов периодов отсутствия, которые не нужно учитывать
     * @param array $absent - массив данных о периодах отсутствия
     * @return array    - массив служебной нагрузки
     */
    public static function genDefaultMonthRecords ($military, $year, $month, $states = null, $absent_black_list = [], $absent = null)
    {
        $arr = [];
        $default = [];
        $month_days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $output_arr = System::getMonthOutputDays($year, $month);

        // Определение дней отпуска, командировки, больничного, воен. госпит.
        if ($absent === null) {
            $data = Absent::getArrByMilitary($military);
            if (count($data)) {
                $data = Absent::getData($data, [
                    'date_from',
                    'date_to',
                    'type',
                    'id'
                ]);
            }
        } else $data = $absent;

        if (count($data)) {
            $time_start = strtotime($year . '-' . System::i2d($month) . '-01');
            $time_finish = strtotime($year . '-' . System::i2d($month) . '-' . $month_days_count);
            $time_finish = System::plusDay($time_finish) - 1;

            foreach ($data as $absent) {
                if (!in_array(intval($absent['id']), $absent_black_list)) {
                    if (System::intervalCrossing($absent['date_from'], $absent['date_to'], $time_start, $time_finish)) {
                        for ($day = 1; $day <= $month_days_count; $day++) {
                            $day_from = strtotime($year . '-' . System::i2d($month) . '-' . System::i2d($day));
                            if (System::intervalCrossing(
                                $absent['date_from'],
                                $absent['date_to'],
                                $day_from,
                                $day_from)) {
                                $default[$day] = [
                                    'type' => intval($absent['type']),
                                    'data' => ['id' => $absent['id']],
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Определение дней, в которые военный служит (контракт)
        if ($states === null) {
            $states = State::getIdArrByMilitary($military);
            $states = State::getData(
                $states,
                [
                    'state',
                    'vrio',
                    'date_from',
                    'date_to'
                ]);
        }

        $service = State::getWorkDaysByMonth ($states, $year, $month, $month_days_count);

        // Финальная сборка массива рабочих дней
        for ($day = 1; $day <= $month_days_count; $day++) {
            if (isset($service[$day])) {
                if (isset($default[$day])) {
                    $arr[$day] = $default[$day];
                } elseif ($output_arr[$day]) {
                    $arr[$day] = ['type' => Schedule::TYPE_VUHODNOI];
                } else {
                    $arr[$day] = ['type' => Schedule::TYPE_RABOCHUI];
                }
            }
        }
        return $arr;
    }

    /**
     * Выполнить преобразование данных о служебной нагрузке военнослужащего после изменения его послужного списка (занимаемых должностей)
     * @param $military - идентификатор военнослужащего
     * @param $states - массив информации о занимаемых должностях
     * @return array|array[] - измененный массив служебной нагрузки
     */
    public static function genRecordsAfterStatesUpdate ($military, $states)
    {
        $default = [];
        $states_all = $states;
        $records = self::getServiceload($military);
        if ($records['schedule'] !== null) {
            if (count($records['schedule'])) {
                $states = State::filterStatesArrByVrio($states, 0);

                if (count($states)) {
                    $statesIntervals = [];
                    foreach ($states as $state) {
                        if ($state['date_to'] === 0)
                            $state['date_to'] = TIME_LAST_SECOND;
                        $statesIntervals[] = [
                            'from' => $state['date_from'],
                            'to' => $state['date_to'],
                        ];
                    }

                    foreach ($records['schedule'] as $year => $yRecords) {
                        foreach ($yRecords as $month => $mRecords) {
                            $monthStart = strtotime($year . '-' . System::i2d($month) . '-01');
                            $tmp = System::nextMonth($year,$month);
                            $monthEnd = strtotime($tmp['year'] . '-' . System::i2d($tmp['month']) . '-01') - 1;

                            if (System::intervalMultiCrossing(
                                [
                                    'from' => $monthStart,
                                    'to' => $monthEnd,
                                ],
                                $statesIntervals
                            )) {
                                $month_days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                for ($day = 1; $day <= $month_days_count; $day++) {
                                    $dayStart = strtotime($year . '-' . System::i2d($month) . '-' . System::i2d($day));
                                    if (System::intervalMultiCrossing($dayStart, $statesIntervals)) {
                                        if (!isset($records['schedule'][$year][$month][$day])) {
                                            if (!isset($default[$year][$month]))
                                                $default[$year][$month] = self::genDefaultMonthRecords($military, $year, $month, $states_all);

                                            $records['schedule'][$year][$month][$day] = $default[$year][$month][$day]['type'];
                                            $records['schedule_data'][$year][$month][$day] = $default[$year][$month][$day]['data'];
                                        }

                                    } else {
                                        unset($records['schedule'][$year][$month][$day]);
                                        unset($records['schedule_data'][$year][$month][$day]);
                                    }
                                }

                            } else {
                                unset($records['schedule'][$year][$month]);
                                unset($records['schedule_data'][$year][$month]);
                            }

                        }
                    }
                } else $records = [
                    'schedule' => [],
                    'schedule_data' => []
                ];
            }
        }

        return $records;
    }

    /**
     * Удалить записи в служебной нагрузке $serviceload с $from по $to
     * @param $serviceload - массив служебной нагрузки военнослужащего
     *              [
     *                  schedule
     *                  schedule_data
     *              ]
     * @param $from - начало интервала очистки в формате Unix
     * @param $to - конец интервала очистки в формате Unix
     * @return mixed - отредактированный массив служебной нагрузки военнослужащего
     */
    public static function _clearByInterval ($serviceload, $from, $to)
    {
        if (isset($serviceload['schedule'])) {
            if (is_array($serviceload['schedule'])) {
                foreach ($serviceload['schedule'] as $year => $ydata)
                    foreach ($ydata as $month =>$mdata)
                        foreach ($mdata as $day =>$type) {
                            $time = System::gettimeBeginDay($year, $month, $day);
                            if (System::intervalCrossing($time, $time, $from, $to)) {
                                unset($serviceload['schedule'][$year][$month][$day]);
                                unset($serviceload['schedule_data'][$year][$month][$day]);
                            }
                        }
            }
        }
        return $serviceload;
    }

    /**
     * Сбросить (очистить) график служебной нагрузки военнослужащего
     * @param $id - идентификатор военнослужащего
     */
    public static function _clear ($id)
    {
        $records = [
            'schedule' => [],
            'schedule_data' => []
        ];
        self::updateServiceload($id, $records);
    }

    /**
     * Сбросить (очистить) график служебной нагрузки всех военнослужащих
     */
    public static function _clearAll ()
    {
        global $DB;

        $serviceload = [
            'schedule' => json_encode([]),
            'schedule_data' => json_encode([]),
        ];

        $DB->_update(
            self::TABLE,
            $serviceload
        );
    }

    /**
     * Удалить данные о служебной нагрузке военнослужащего
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки военнослужащего
     *
     * @param $id
     * @return bool
     */
    public static function _delete ($id)
    {
        global $DB;
        $DB->_delete(self::TABLE , [['military = ', $id]]);
        return true;
    }

    /**
     * Удалить данные о служебной нагрузке всех военнослужащих
     *
     * ВНИМАНИЕ!
     * Применять только в крайнем случае
     * В противном случае возникнут проблемы с отображением графика нагрузки
     *
     */
    public static function _deleteAll ()
    {
        global $DB;
        $DB->query('DELETE FROM ' . self::TABLE);
    }

}