<?php

    use OsT\Access;
    use OsT\Base\System;

    require_once('layouts/header.php');

    if (!isset($_POST['key']))
        exit;

	switch ($_POST['key']) {

	    /* Создать новое подразделение (проверка данных происходит на стороне пользователя units.php)
	     * @param parent - родительское подразделение
	     * @param title - наименование подразделения
	     * @return html представление подразделения
	     */
        case 'unit_new':
            $data = [
                'parent' => intval($_POST['data']['parent']),
                'title' => addslashes($_POST['data']['title'])
                ];
            \OsT\Unit::insert($data);
            $buttons = Access::checkAccess('units_edit');
            $return = [
                'id' => \OsT\Unit::last(),
                'html' => \OsT\Unit::getHtml(\OsT\Unit::last(), $data['title'], $buttons, '')
            ];
            echo json_encode($return);
            break;

        /* Изменить данные подразделения (проверка данных происходит на стороне пользователя units.php)
	     * @param id - идентификатор подразделения
	     * @param title - новое наименование подразделения
	     */
        case 'unit_edit':
            $id = intval($_POST['data']['id']);
            $data = [
                'title' => addslashes($_POST['data']['title'])
            ];
            \OsT\Unit::_update($id, $data);
            break;

        /* Удалить подразделение вместе с дочерними
	     * @param идентификатор подразделения
	     */
        case 'unit_delete':
            \OsT\Unit::_deleteWithDependencies(intval($_POST['data']));
            break;

        /* Удалить все подразделения
         */
        case 'unit_delete_all':
            \OsT\Unit::_deleteAllWithDependencies();
            break;

        /* Переместить подразделение
	     * @param id - идентификатор подразделения
	     * @param parent - массив выбранных подразделений для определения конечного
	     */
        case 'unit_move':
            $unit = intval($_POST['data']['id']);
            $units_list = $_POST['data']['parent'];
            $parent = \OsT\Unit::getSelectedUnit($units_list, 'unit_move_');
            $data = [
                'parent' => $parent
            ];
            \OsT\Unit::_update($unit, $data);
            echo $parent;
            break;

        /* Переместить подразделение
         * Сформировать элементы выбора подразделения
         * @param идентификатор подразделения
         */
        case 'unit_move_get_select_list':
            $unit = intval($_POST['data']);
            $parent = $STRUCT_DATA[$unit]['parent'];
            $tree = [0 => $STRUCT_TREE];
            $tree = \OsT\Unit::removeUnitIntoTree($tree, $unit);
            echo \OsT\Unit::getSelectUnitHtml($tree, $parent, 'unit_move_');
            break;

        /* Переместить подразделение
         * Обновить элементы выбора подразделения
         * @param unit - идентификатор перемещаемого подразделения
	     * @param selected - идентификатор выбранного из списка подразделения
         */
        case 'unit_move_update_select_list':
            $unit = intval($_POST['data']['unit']);
            $selected_unit = intval($_POST['data']['selected']);
            $tree = [0 => $STRUCT_TREE];
            $tree = \OsT\Unit::removeUnitIntoTree($tree, $unit);
            echo \OsT\Unit::getSelectUnitHtml($tree, $selected_unit, 'unit_move_');
            break;

        /* Создать ноыую должность (проверка данных происходит на стороне пользователя state.php)
         * @param unit - родительское подразделение
         * @param title - наименование должности
         * @param title_short - краткое наименование должности
         * @param title_abbreviation - аббревиатура должности
         * @param vrio - возможность указать временно исполняющего обязанности по данной должности
         * @param vrio_title - наименование Врио должности
         * @param vrio_title_short - ураткое наименование Врио должности
         * @param vrio_abbreviation - фббревиатура Врио должности
         * @return id идентификатор созданной должности
         * @return html представление должности
         */
        case 'state_new':
            $data = [
                'unit' => intval($_POST['data']['unit']),
                'title' => addslashes($_POST['data']['title']),
                'title_short' => addslashes($_POST['data']['title_short']),
                'title_abbreviation' => addslashes($_POST['data']['title_abbreviation']),
                'vrio' => intval($_POST['data']['vrio']),
                'vrio_title' => addslashes($_POST['data']['vrio_title']),
                'vrio_title_short' => addslashes($_POST['data']['vrio_title_short']),
                'vrio_abbreviation' => addslashes($_POST['data']['vrio_abbreviation']),
            ];
            \OsT\State::insert($data);
            $buttons = Access::checkAccess('state_edit');
            $return = [
                'id' => \OsT\State::last(),
                'html' => \OsT\State::getHtml(\OsT\State::last(), $data['title'], $data['unit'], $buttons)
            ];
            echo json_encode($return);
            break;

        /* Подгрузить данные о должности для окна редактирования должности
         * @param id - идентификатор объекта должности
         * @return array массив данных должности
         */
        case 'state_edit_get_data':
            $id = intval($_POST['data']);
            $return = \OsT\State::getData([$id], [
                'id',
                'unit',
                'title',
                'title_short',
                'title_abbreviation',
                'vrio',
                'vrio_title',
                'vrio_title_short',
                'vrio_abbreviation',
            ]);
            echo json_encode($return[$id]);
            break;

        /* Редактировать должность (проверка данных происходит на стороне пользователя state.php)
         * @param id - идентификатор объекта должности
         * @param title - наименование должности
         * @param title_short - краткое наименование должности
         * @param title_abbreviation - аббревиатура должности
         * @param vrio - возможность указать временно исполняющего обязанности по данной должности
         * @param vrio_title - наименование Врио должности
         * @param vrio_title_short - ураткое наименование Врио должности
         * @param vrio_abbreviation - аббревиатура Врио должности
         * @return html представление должности
         */
        case 'state_edit':
            $data = [
                'title' => addslashes($_POST['data']['title']),
                'title_short' => addslashes($_POST['data']['title_short']),
                'title_abbreviation' => addslashes($_POST['data']['title_abbreviation']),
                'vrio' => intval($_POST['data']['vrio']),
                'vrio_title' => addslashes($_POST['data']['vrio_title']),
                'vrio_title_short' => addslashes($_POST['data']['vrio_title_short']),
                'vrio_abbreviation' => addslashes($_POST['data']['vrio_abbreviation']),
            ];
            $return = \OsT\State::_update(intval($_POST['data']['id']), $data);
            echo intval($return);
            break;

        /* Удалить должность
	     * @param идентификатор дожности
	     */
        case 'state_delete':
            \OsT\State::_deleteWithDependencies(intval($_POST['data']));
            break;

        /* Удалить все дочерние должности подразделения
	     * @param идентификатор подразделения
	     */
        case 'state_delete_by_unit':
            \OsT\State::_deleteByUnitWithDependencies(intval($_POST['data']));
            break;

        /* Удалить все должности
	     */
        case 'state_delete_all':
            \OsT\State::_deleteAllWithDependencies();
            break;

        /* Сформировать военнослужащему военнослужащему запись о звании (не в бд, а лишь визуально)
	     */
        case 'military_level_getitemhtml':
            $return = ['error' => 0];
            $test = System::parseDate($_POST['data']['date'], 'unix', 'd/m/y');
            if ($test) {
                $data = [
                    'level' => intval($_POST['data']['level']),
                    'level_title' => $_POST['data']['level_title'],
                    'date_str' => System::parseDate($_POST['data']['date'], 'd', 'd/m/y'),
                    'date_datepicker' => $_POST['data']['date']
                ];
                $return['html'] = \OsT\Military\Level::getTableItemHtml($data);
            } else $return['error'] = 'ERR_DATA_FORMAT';
            echo json_encode($return);
            break;

        /* Сформировать html представления для полей выбора подразделения и должности при смене подразделения на странице military_edit
	     */
        case 'military_state_upload_unit_select':
            $unit_id = intval($_POST['data']['unit']);
            $index = intval($_POST['data']['index']);
            $window = $_POST['data']['window'];
            $units = \OsT\Unit::getChildrenFromTree($STRUCT_TREE, $unit_id);
            if (count($units)) {
                $unit_html = '<select class="' . $window . '_state_unit" onchange="state_select_unit_change(this, \'' . $window . '\')" data-index="' . ($index + 1) . '" name="unit' . ($index + 1) . '">
                <option value="0">- Не выбрано -</option>';
                $units_data = \OsT\Unit::getData($units, ['id', 'title']);
                foreach ($units_data as $unit)
                    $unit_html .= '<option value="' . $unit['id'] . '">' . $unit['title'] . '</option>';
                $unit_html .= '</select>';
                $return = [
                    'type' => 'unit',
                    'html' => $unit_html
                ];

            } else {
                $states = \OsT\State::getDataByUnit($unit_id, ['id', 'title']);
                if (count($states)) {
                    $html = '<select id="new_state_state" name="state">
                                <option value="0">- Не выбрано -</option>';
                    foreach ($states as $state)
                        $html .= '<option value="' . $state['id'] . '">' . $state['title'] . '</option>';
                    $html .= '</select>';
                    $return = [
                        'type' => 'state',
                        'html' => $html
                    ];

                } else {
                    $return = [
                        'type' => 'state',
                        'html' => ''
                    ];
                }
            }
            echo json_encode($return);
            break;

        /* Сформировать военнослужащему  запись о должности
         * Проверка данных на стороне сервера ajax.php
         * Страница military_edit
	     */
        case 'military_state_getitemhtml':
            $return = ['error' => 0];
            $data = \OsT\Military\State::checkFormData($_POST['data']);
            if (is_array($data)) {
                $data ['vrio_str'] = \OsT\Military\State::vrio_getString($data['vrio']);
                $data ['unit_path_str'] = \OsT\Unit::getPathStr($data['unit']);
                $data ['state_title'] = $_POST['data']['state_title'];
                $data ['date_from_str'] = \OsT\Military\State::dateToStr('from', $data['date_from']);
                $data ['date_to_str'] = \OsT\Military\State::dateToStr('to', $data['date_to']);
                $return['html'] = \OsT\Military\State::getTableItemHtml($data);
                //$return['html'] = '<tr><td>1</td></tr>';
            } else $return['error'] = $data;
            //var_dump($_POST['data']);
            echo json_encode($return);
            break;

        /* Сформировать элементы формы при редактировании военнослужащего
         * Страница military_edit
	     */
        case 'military_state_edit_uploadformhtml':
            $index = intval($_POST['data']['index']);
            $data = [
                'unit' => intval($_POST['data']['state_unit' . $index]),
                'state' => intval($_POST['data']['state_state' . $index]),
                'vrio' => intval($_POST['data']['state_vrio' . $index]),
                'date_from' => intval($_POST['data']['state_date_from' . $index]),
                'date_to' => intval($_POST['data']['state_date_to' . $index])
            ];
            $return = \OsT\Military\State::getFormDataFromTempTableData($data);
            echo json_encode($return);
            break;

        /* Обновить элементы выбора подразделения
         * Страница schedule_select
	     * @param selected - идентификатор выбранного из списка подразделения
         */
        case 'schedule_select_update_select_list':
            $selected_unit = intval($_POST['data']['selected']);
            $tree = [0 => $STRUCT_TREE];
            echo \OsT\Unit::getSelectUnitHtml($tree, $selected_unit, 'unit_');
            break;

        /* Проверить данные формы выбора расписания
         * Страница schedule_select
         * @param data - массив данных из формы выбора расписания
         *          year - год
         *          month - месяц
         *          unit_* - выбранные подразделения
         */
        case 'schedule_select':
            $year = intval($_POST['data']['data']['year']);
            $month = intval($_POST['data']['data']['month']);
            $time = System::convertMonthToTimeInterval($year, $month);
            $unit = \OsT\Unit::getSelectedUnit($_POST['data']['data'], 'unit_');
            $militaries = \OsT\Military\Military::getByUnit($unit, $time);
            if (count($militaries))
                echo $unit;
            else echo 'ERR_NO_MILITARY';
            break;

        /* Включение / отключение шаблона
         * Страница mask
         * @param data - массив данных из формы выбора расписания
         *          year - год
         *          month - месяц
         *          unit_* - выбранные подразделения
         */
        case 'mask_enable' :
            $id = intval($_POST['data']['id']);
            $enabled = intval($_POST['data']['enabled']);
            \OsT\Serviceload\Mask::_update($id, ['enabled' => $enabled]);
            break;

        /* Сгенерировать HTML представление блока настойки вывода отчета
         * @param report - ключ отчета
         */
        case 'report_settingsbox_generate':
            echo \OsT\Reports\Report::getHtmlPrintSettingsBox($_POST['data']['report']);
            break;

        /*
         * Сгенерировать HTML представление блока настойки вывода отчета при смене версии отчета
         * @param report - ключ отчета
         * @param version - ключ версии
         */
        case 'report_settingsbox_version_change':
            echo \OsT\Reports\Report::getHtmlPrintSettingsBox($_POST['data']['report'], $_POST['data']['version']);
            break;

        /* Обновить элементы выбора подразделения
         * Страница reports
         * @param key - ключ отчета
         * @param unit - идентификатор выбранного подразделения
         */
        case 'reports_reload_units_list':
            $key = $_POST['data']['key'];
            $unit = intval($_POST['data']['unit']);
            echo \OsT\Reports\Report::getHtmlSelectUnit ($key, $unit);
            break;

        /* Добавить отчет в очередь печати
         * Страница reports
         * @param report - ключ отчета
         * @param ... - атрибуты name элементов формы
         */
        case 'report_add_to_print_list':
            echo \OsT\Reports\Report::getHtmlPrintTableItem($_POST['data']);
            break;

        /* Визуализировать отчеты из очереди печати
         * Страница reports
         * @param ... - массив данных отчетов
         */
        case 'reports_show_pdf':
            $_SESSION['reports'] = $_POST['data'];
            echo  '<iframe name="pdf" class="objectPDF" src="report_show.php"></iframe>';
            break;

        /* Обновить элементы выбора военнослужащего-отправителя
         * Страница report_settings
         * @param selected - идентификатор выбранного из списка подразделения
         */
        case 'report_settings_sender2_unit_change':
            $selected_unit = intval($_POST['data']['selected']);
            $tree = [0 => $STRUCT_TREE];
            echo \OsT\Military\State::getSelectUnitMilitaryHtml (
                $tree,
                $selected_unit,
                null,
                'sender2_unit_',
                'sender2_military'
            );
            break;

        /* Обновить элементы выбора должности отправителя
         * Страница report_settings
         * @param selected - идентификатор выбранного из списка подразделения
         */
        case 'report_settings_sender3_unit_change':
            $selected_unit = intval($_POST['data']['selected']);
            $tree = [0 => $STRUCT_TREE];
            echo \OsT\State::getSelectUnitStateHtml (
                $tree,
                $selected_unit,
                null,
                'sender3_unit_',
                'sender3_state'
            );
            break;

        /* Создать пакет отчетов
         * Страница reports
         * @param title - название пакета
         * @param reports - массив параметров формирования отчетов
         */
        case 'package_create':
            $settings = [];
            foreach ($_POST['data']['reports'] as $report) {
                $key = $report['key'];
                $version = strval($report['version']);
                if (!isset($settings[$key][$version])) {
                    $version_class = \OsT\Reports\Report::constructClassName($key, $version);
                    $settings[$key][$version] = $version_class::getSettings();
                }
            }

            \OsT\Reports\Package::insert([
                'title' =>  addslashes($_POST['data']['title']),
                'user'  =>  $USER->id,
                'data'  =>  json_encode($_POST['data']['reports']),
                'settings'  =>  json_encode($settings)
            ]);
            $id = \OsT\Reports\Package::last();
            echo \OsT\Reports\Package::getHtmlTableItem($id, $_POST['data']['title']);
            break;

        /* Удалить пакет отчетов
         * Страница reports
         * @param id - идентификатор пакета
         */
        case 'package_delete':
            $id = intval($_POST['data']['id']);
            \OsT\Reports\Package::_delete($id);
            break;

        /* Добавить пакет отчетов в очередь печати
         * Страница reports
         * @param id - идентификатор пакета
         * @param date - дата младшего отчета
         */
        case 'package_add_to_print_list':
            $id = intval($_POST['data']['id']);
            $date = System::parseDate($_POST['data']['date'], 'unix', 'd/m/y');

            $package = new \OsT\Reports\Package($id);
            $package->calcData($date);
            echo $package->getHtmlPrintTableItems();

            break;

    }
