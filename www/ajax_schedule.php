<?php

use OsT\Base\System;
use OsT\Military\Absent;
use OsT\Serviceload\Mask;
use OsT\Serviceload\Military;
use OsT\Serviceload\Overworking;
use OsT\Serviceload\Place;
use OsT\Serviceload\Schedule;
use OsT\Serviceload\Type;

require_once('layouts/header.php');

    if (!isset($_POST['key']))
        exit;

	switch ($_POST['key']) {

	    /* Установить в ячейки значения по умолчанию (сбросить)
	     * @param year - год
	     * @param month - месяц
	     * @param day_begin - день начала диапазона
	     * @param day_end - день конца диапазона
	     * @param military - идентификатор военнослужащего
	     *
	     * @return массив html представленией затронутых ячеек
	     */
        case 'set_default_values':
            $return = [];
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day_begin = intval($_POST['data']['day_begin']);
            $day_end = intval($_POST['data']['day_end']);
            $military = intval($_POST['data']['military']);

            System::replaceNumbers($day_begin, $day_end, 'asc');
            $time_from = System::gettimeBeginDay($year, $month, $day_begin) + System::TIME_HOUR * 2;
            $time_end = System::gettimeBeginDay($year, $month, $day_end) + System::TIME_HOUR * 2;

            $records = Military::genDefaultRecords($military, $time_from, $time_end);
            Military::updateServiceload($military, $records);

            $serviceload_types = Type::getData(
                null ,
                [
                    'color',
                    'title_short'
                ]);

            for ($day = $day_begin; $day <= $day_end; $day++) {
                $additional_data = [];
                if (isset($records['schedule_data'][$year][$month][$day]))
                    $additional_data = $records['schedule_data'][$year][$month][$day];
                $return[$day] = Schedule::genServiceloadCellValueHtml(
                    $records['schedule'][$year][$month][$day],
                    $serviceload_types,
                    $additional_data
                );
            }

            echo json_encode($return);
            break;

        /* Продублировать значение ячеек на период
         * @param unit - идентификатор подразделения
         * @param year - год
         * @param month - месяц
         * @param day_begin - день начала диапазона
         * @param day_end - день конца диапазона
         * @param military - идентификатор военнослужащего
         *
         * @return массив html представленией затронутых ячеек
         */
        case 'continue_schedule_values':
            $return = [];
            $unit = intval($_POST['data']['unit']);
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day_begin = intval($_POST['data']['day_begin']);
            $day_end = intval($_POST['data']['day_end']);
            $military = intval($_POST['data']['military']);

            System::replaceNumbers($day_begin, $day_end, 'asc');
            $time_day_end = System::gettimeBeginDay($year, $month, $day_end) + System::TIME_HOUR * 2;

            // Определеие количества дубликатов
            $month_days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $copy_days_count = $day_end - $day_begin + 1;
            $cicles = intval($month_days_count / $copy_days_count);

            $time_dublicate_begin = System::gettimeBeginDay($year, $month, $day_end) + System::TIME_DAY + System::TIME_HOUR * 2;
            $time_dublicate_end = $time_dublicate_begin + ($cicles * $copy_days_count - 1) * System::TIME_DAY;
            $serviceload = Military::genDefaultRecords(
                $military,
                $time_dublicate_begin,
                $time_dublicate_end,
                null,
                null,
                [],
                false);

            $serviceload_types = Type::getData(
                null ,
                [
                    'color',
                    'title_short'
                ]);
            $time = $time_day_end;
            for ($i = 1; $i <= $cicles; $i++) {
                for ($day = $day_begin; $day <= $day_end; $day++) {
                    $time += System::TIME_DAY;
                    $date = getdate($time);
                    if (isset($serviceload['schedule'][$date['year']][$date['mon']][$date['mday']]))
                        if (in_array($serviceload['schedule'][$date['year']][$date['mon']][$date['mday']], [Schedule::TYPE_RABOCHUI, Schedule::TYPE_VUHODNOI])) {
                            $serviceload['schedule'][$date['year']][$date['mon']][$date['mday']] = $serviceload['schedule'][$year][$month][$day];
                            $additional_data = [];
                            if (isset($serviceload['schedule_data'][$year][$month][$day])) {
                                $additional_data = $serviceload['schedule_data'][$year][$month][$day];
                                $serviceload['schedule_data'][$date['year']][$date['mon']][$date['mday']] = $additional_data;
                            }
                            if ($month === $date['mon'])
                                $return[$date['mday']] = Schedule::genServiceloadCellValueHtml(
                                    $serviceload['schedule'][$date['year']][$date['mon']][$date['mday']],
                                    $serviceload_types,
                                    $additional_data);
                            continue;
                        }
                    break 2;
                }
            }

            Military::updateServiceload($military, $serviceload);

            echo json_encode($return);
            break;

        /* Установить значение шаблона в ячейку
         * Использует для работы schedule_set_data
         *
         * @param year - год
         * @param month - месяц
         * @param day_begin - день начала диапазона
         * @param day_end - день конца диапазона
         * @param military - идентификатор военнослужащего
         * @param servicetype - идентификатор типа служебной нагрузки
         * @param mask - идентификатор шаблона
         *
         * @return массив html представленией затронутых ячеек
         */
        case 'schedule_set_mask':
            $mask = intval($_POST['data']['mask']);
            $mask = Mask::getData([$mask], ['data']);
            Mask::getDataDecryption($mask);
            $mask = end($mask);
            $_POST['data']['additional_data'] = [
                'type' => $mask['data']['type_str'],
                'from' => $mask['data']['from'],
                'len' => $mask['data']['len'],
                'place' => $mask['data']['place_str'],
                'incoming' => $mask['data']['incoming']
            ];

        /* Установить значение в ячейку (или на период)
         * @param year - год
         * @param month - месяц
         * @param day_begin - день начала диапазона
         * @param day_end - день конца диапазона
         * @param military - идентификатор военнослужащего
         * @param servicetype - идентификатор типа служебной нагрузки
         * @param additional_data - массив дополнительных данных о служебной нагрузке
         *
         * @return массив html представленией затронутых ячеек
         */
        case 'schedule_set_data' :
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day_begin = intval($_POST['data']['day_begin']);
            $day_end = intval($_POST['data']['day_end']);
            $military = intval($_POST['data']['military']);
            $servicetype = intval($_POST['data']['servicetype']);
            $return = [];
            System::replaceNumbers($day_begin, $day_end, 'asc');

            if (!isset($_POST['data']['additional_data']) || !is_array($_POST['data']['additional_data']))
                $additional_data = [];
            else $additional_data = $_POST['data']['additional_data'];

            $serviceload = Military::getServiceload($military);

            if (isset($additional_data['place'])) {
                $additional_data['place'] = trim($additional_data['place']);
                $additional_data['place'] = Place::getIdByTitle($additional_data['place'], true);
            }

            if (isset($additional_data['type'])) {
                $additional_data['type'] = trim($additional_data['type']);
                $additional_data['type'] = Type::getSubtypeIdByTitle($additional_data['type'], true);
            }

            $serviceload_types = Type::getData(
                null ,
                [
                    'color',
                    'title_short'
                ]);

            for ($day = $day_begin; $day <= $day_end; $day++) {
                $serviceload['schedule'][$year][$month][$day] = $servicetype;
                $serviceload['schedule_data'][$year][$month][$day] = $additional_data;
                $return[$day] = Schedule::genServiceloadCellValueHtml($servicetype, $serviceload_types, $additional_data);
            }

            // Автоопределение выходных за переработку
            if ($day_begin === $day_end) {
                $overworking_days = Overworking::calcOutputDays(
                    $serviceload,
                    $year,
                    $month,
                    $day_begin);
                if ($overworking_days) {
                    $time = System::gettimeBeginDay($year, $month, $day_begin) + System::TIME_HOUR * 3;
                    for ($i = 1; $i <= $overworking_days; $i++) {
                        $time += System::TIME_DAY;
                        $date = getdate($time);
                        if (in_array($serviceload['schedule'][$year][$month][$date['mday']], [Type::VUHODNOI, Type::RABOCHUI, Type::NARYAD])) {
                            $serviceload['schedule'][$year][$month][$date['mday']] = Type::VUHODNOI;
                            unset($serviceload['schedule_data'][$year][$month][$date['mday']]);

                            if (intval($date['mon']) === $month)
                                $return[$date['mday']] = Schedule::genServiceloadCellValueHtml(Type::VUHODNOI, $serviceload_types);
                        }
                    }
                }
            }

            Military::updateServiceload($military, $serviceload);
            echo json_encode($return);
            break;

        /* Добавить новый период отсутсвия через график служебной нагрузки
         * @param year - год
         * @param month - месяц
         * @param military - идентификатор военнослужащего
         * @param servicetype - идентификатор типа служебной нагрузки
         * @param additional_data - массив дополнительных данных о служебной нагрузке
         *
         * @return
         */
        case 'schedule_set_data_interval' :
            $return['errors'] = false;
            $return['html'] = false;
            $useges_interval = [];

            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $military = intval($_POST['data']['military']);
            $servicetype = intval($_POST['data']['servicetype']);

            if (!isset($_POST['data']['additional_data']) || !is_array($_POST['data']['additional_data']))
                $additional_data = [];
            else $additional_data = $_POST['data']['additional_data'];

            if (in_array($servicetype, [Schedule::TYPE_OTPUSK, Schedule::TYPE_KOMANDIROVKA, Schedule::TYPE_VOENNUIGOSPITAL, Schedule::TYPE_BOLNICHNUI])) {
                $edit = boolval($additional_data['mode']);  // true - изменить имеющийся период отсутствия, false - создать новый
                $id = intval($additional_data['id']);       // Идентификатор изменяемого периода отсутствия при mode = true
                $data ['date_from'] = System::parseDate($additional_data['from'], 'unix', 'd/m/y');
                $data ['date_to'] = System::parseDate($additional_data['to'], 'unix', 'd/m/y');
                $data ['military'] = $military;
                $data ['absent_type'] = $servicetype;
                $check_data = Absent::checkData($data, $id);
                if ($check_data === true) {
                    if ($id) {
                        $absent = new Absent($id);
                        $useges_interval['from'] = ($data['date_from'] < $absent->date_from) ? $data ['date_from'] : $absent->date_from;
                        $useges_interval['to'] = ($data['date_to'] > $absent->date_to) ? $data ['date_to'] : $absent->date_to;
                        $absent->updateWithDependencies($data);
                    } else {
                        $useges_interval['from'] = $data ['date_from'];
                        $useges_interval['to'] = $data ['date_to'];
                        Absent::insertWithDependencies($data);
                    }
                    // Подготовка выходных данных
                    // Определение диапазона затронутых дней
                    $days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $serviceload = Military::getServiceload($military);
                    $serviceload_types = Type::getData(
                        null ,
                        [
                            'color',
                            'title_short'
                        ]);

                    /**
                     * @todo выделить этот участок в отдельную функцию множественного формирования ячеек по диапазону времени
                     */

                    for ($i = 1; $i <= $days_count; $i++) {
                        $time = System::gettimeBeginDay($year, $month, $i);
                        if (System::intervalCrossing($time, $time, $useges_interval['from'], $useges_interval['to'])) {
                            $serviceload_day_type = $serviceload['schedule'][$year][$month][$i];
                            $additional_data = [];
                            if (isset($serviceload['schedule_data'][$year][$month][$i]))
                                $additional_data = $serviceload['schedule_data'][$year][$month][$i];
                            $return['html'][$i] = Schedule::genServiceloadCellValueHtml($serviceload_day_type, $serviceload_types, $additional_data);
                        }
                    }

                } else $return['errors'] = $check_data;

            } else $return['errors'] = 'servicetype';

            echo json_encode($return);
            break;

        /* Сформировать окно управления периодом отсутствия
         * @param year - год
         * @param month - месяц
         * @param day_begin - день начала диапазона
         * @param day_end - день конца диапазона
         * @param military - идентификатор военнослужащего
         * @param type - идентификатор типа служебной нагрузки
         *
         * @return html представление окна
         */
        case 'otpusk_change' :
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day_begin = intval($_POST['data']['day_begin']);
            $day_end = intval($_POST['data']['day_end']);
            System::replaceNumbers($day_begin, $day_end, 'asc');
            $time_begin = System::gettimeBeginDay($year, $month, $day_begin);
            $time_end = System::gettimeBeginDay($year, $month, $day_end);
            $military = intval($_POST['data']['military']);
            $type = intval($_POST['data']['type']);

            $edit = 0;
            $tmp = Type::getData([$type] , ['title']);
            $type_title = $tmp[$type]['title'];

            $military_data = \OsT\Military\Military::getData([$military], ['fio_short']);
            $military_data = end($military_data);
            $serviceload = Military::getServiceload($military);

            $previous_servicetype = $serviceload['schedule'][$year][$month][$day_begin];
            $absent_id = 0;

            $default_data = [
                'from' => $time_begin,
                'to' => $time_end
            ];

            if ($previous_servicetype === $type) {
                $absent = Absent::getItemByTime($military, $time_begin);
                if ($absent !== null) {
                    $absent = Absent::getData(
                        [$absent],
                        [
                            'id',
                            'date_from',
                            'date_to'
                        ]
                    );
                    $absent = end($absent);
                    $default_data = [
                        'from' => $absent['date_from'],
                        'to' => $absent['date_to'],
                    ];
                    $edit = 1;
                    $absent_id = $absent['id'];
                }
            }

            echo Schedule::genAbsentWindowHtml(
                $default_data['from'],
                $default_data['to'],
                $type,
                $type_title,
                $military_data['fio_short'],
                $edit,
                $absent_id
            );
            break;

        /* Сформировать окно управления службой
         * @param year - год
         * @param month - месяц
         * @param day - день
         * @param military - идентификатор военнослужащего
         * @param type - идентификатор типа служебной нагрузки
         *
         * @return html представление окна
         */
        case 'naryad_change' :
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day = intval($_POST['data']['day']);
            $military = intval($_POST['data']['military']);
            $type = intval($_POST['data']['type']);

            $military_data = \OsT\Military\Military::getData([$military], ['fio_short']);
            $military_data = end($military_data);
            $serviceload = Military::getServiceload($military);

            $previous_servicetype = $serviceload['schedule'][$year][$month][$day];
            if ($previous_servicetype === $type)
                $default_data = $serviceload['schedule_data'][$year][$month][$day];
            else $default_data = Schedule::getNaryadDefaultData();

            $time = System::gettimeBeginDay($year, $month, $day);
            $date = System::parseDate($time);

            $serviceload_types = Type::getData([Type::NARYAD], ['sub_types']);
            $subtypes = $serviceload_types[Type::NARYAD]['sub_types'];
            $subtype_value = '';
            if (count($subtypes)) {
                foreach ($subtypes as $key => $title) {
                    if ($key === intval($default_data['type']))
                        $subtype_value = $title;
                }
            }

            $places = Place::getData(null, [
                'id',
                'title'
            ]);
            $place_value = '';
            if (count($places)) {
                foreach ($places as $key => $item) {
                    $places[$key] = $item['title'];
                    if ($item['id'] === $default_data['place'])
                        $place_value = $item['title'];
                }
            }

            echo Schedule::getNaryadWindowHtml(
                $military_data['fio_short'],
                $date,
                $default_data['incoming'],
                $default_data['from'],
                $default_data['len'],
                $subtypes,
                $subtype_value,
                $places,
                $place_value
            );
            break;

        /* Удалить период отсутствия
         * @param year - год
         * @param month - месяц
         * @param day - день
         * @param military - идентификатор военнослужащего
         *
         * @return массив html представленией затронутых ячеек
         */
        case 'otpusk_delete' :
            $return['errors'] = false;
            $return['html'] = false;
            $year = intval($_POST['data']['year']);
            $month = intval($_POST['data']['month']);
            $day = intval($_POST['data']['day']);
            $military = intval($_POST['data']['military']);

            $absent = Absent::getItemByTime($military, System::gettimeBeginDay($year, $month, $day));
            if ($absent !== null) {
                $absent = new Absent($absent);
                $useges_interval['from'] = $absent->date_from;
                $useges_interval['to'] = $absent->date_to;
                $absent->deleteWithDependencies();
                $days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);

                $serviceload = Military::getServiceload($military);
                $serviceload_types = Type::getData(
                    null ,
                    [
                        'color',
                        'title_short'
                    ]);

                /**
                 * @todo выделить этот участок в отдельную функцию множественного формирования ячеек по диапазону времени
                 */

                for ($i = 1; $i <= $days_count; $i++) {
                    $time = System::gettimeBeginDay($year, $month, $i);
                    if (System::intervalCrossing($time, $time, $useges_interval['from'], $useges_interval['to'])) {
                        $serviceload_day_type = $serviceload['schedule'][$year][$month][$i];
                        $additional_data = [];
                        if (isset($serviceload['schedule_data'][$year][$month][$i]))
                            $additional_data = $serviceload['schedule_data'][$year][$month][$i];
                        $return['html'][$i] = Schedule::genServiceloadCellValueHtml($serviceload_day_type, $serviceload_types, $additional_data);
                    }
                }

                echo json_encode($return);

            } else $return['errors'] = 'cant_find_absent_by_time';

            break;



    }
