<?php

namespace OsT\Serviceload;

/**
 * Переработка
 * Class Overworking
 * @package OsT\Serviceload
 * @version 2022.03.10
 *
 *  calcOutputDays      Рассчитать количество дней отдыха при указании службы
 *
 */
class Overworking
{
    const NORM_WORKING_HOURS_IN_DAY = 8;

    /**
     * Рассчитать количество дней отдыха при указании службы
     * @param $serviceload
     * @param $year
     * @param $month
     * @param $day
     * @return int
     */
    public static function calcOutputDays ($serviceload, $year, $month, $day)
    {
        $schedule_line = [];
        $stop = false;
        foreach ($serviceload['schedule'] as $s_year => $year_data)
            foreach ($year_data as $s_month => $month_data)
                foreach ($month_data as $s_day => $day_data) {
                    if (!$stop) {
                        $temp_data = null;
                        if (isset($serviceload['schedule_data'][$s_year][$s_month][$s_day]['len']))
                            $temp_data = intval($serviceload['schedule_data'][$s_year][$s_month][$s_day]['len']);
                        $arr = [
                            'type' => $day_data,
                            'len' => $temp_data,
                            'day' => $s_day
                        ];
                        array_unshift($schedule_line, $arr);
                        if ($s_year === $year && $s_month === $month && $s_day === $day) {
                            $stop = true;
                        }
                    }
                }
        $work_hours = 0;
        foreach ($schedule_line as $item) {
            if ($item['type'] === Schedule::TYPE_NARYAD)
                $work_hours += $item['len'] - self::NORM_WORKING_HOURS_IN_DAY;
            else break;
        }
        if ($work_hours < 1)
            return 0;
        else
            return @intval($work_hours / self::NORM_WORKING_HOURS_IN_DAY);
    }

}