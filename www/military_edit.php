<?php

use OsT\Access;
use OsT\Military\Level;

require_once  'layouts/header.php';

    $pageData['title'] = 'Военнослужащий';
    $pagesGroup = ['military', 'menu'];

    $id = @intval($_GET['id']);

    if ($id) {
        if (!Access::checkAccess('military_edit')) {
            \OsT\Base\System::location('menu.php');
            exit;
        }
        $military = \OsT\Military\Military::getData([$id], [
                'fname',
                'iname',
                'oname',
                'description',
                'levels',
                'states'
        ]);
        $military = $military[$id];

        $military['levels_data'] = Level::getData(
                $military['levels'],
                [   'id',
                    'level',
                    'level_title',
                    'date',
                    'date_str',
                    'date_datepicker'
                ]);
        $military['levels_data'] = \OsT\Base\System::sort($military['levels_data'], 'date');

        $military['states_data'] = \OsT\Military\State::getData(
                $military['states'],
                [   'unit',
                    'unit_path_str',
                    'state',
                    'state_title',
                    'vrio',
                    'vrio_str',
                    'date_from',
                    'date_from_str',
                    'date_to',
                    'date_to_str'
                ]);
        $military['states_data'] = \OsT\Base\System::sort($military['states_data'], 'date_from');
        $state_index = count($military['states']);

        $mode = MODE_EDIT;

    } else {
        if (!Access::checkAccess('military_new')) {
            \OsT\Base\System::location('menu.php');
            exit;
        }
        $state_index = 0;
        $mode = MODE_INSERT;
    }

    if (isset($_POST['fname'])) {
        $data = [
            'fname' => addslashes(trim($_POST['fname'])),
            'iname' => addslashes(trim($_POST['iname'])),
            'oname' => addslashes(trim($_POST['oname'])),
            'description' => addslashes(trim($_POST['description']))
        ];

        if ($id) {
            $active_object = $id;
            \OsT\Military\Military::_update($id, $data);
            \OsT\Military\Level::_deleteByMilitary($active_object);
            \OsT\Military\State::_deleteByMilitary($active_object);

        } else {
            \OsT\Military\Military::insert($data);
            $active_object = \OsT\Military\Military::last();
            \OsT\Serviceload\Military::insertServiceload($active_object);
        }

        $levels = \OsT\Military\Level::parsePostData($active_object, $_POST);
        \OsT\Military\Level::insertArray($levels);

        $states = \OsT\Military\State::parseTempTableData($_POST);
        foreach ($states as $key => $item) {
            $states[$key]['military'] = $active_object;
            unset($states[$key]['unit']);
        }

        \OsT\Military\State::insertArray($states);
        $serviceload = \OsT\Serviceload\Military::genRecordsAfterStatesUpdate($active_object, $states);
        \OsT\Serviceload\Military::updateServiceload($active_object, $serviceload);

        \OsT\Base\System::location('military_edit.php?id=' . $active_object);
    }

    require_once  'layouts/head.php';

    ?>

    <script src="js/jquery-ui/jquery-ui.js"></script>
    <script src="js/jquery-ui/datepicker-ru.js"></script>
    <link rel="stylesheet" href="js/jquery-ui/jquery-ui.min.css">

    <script>

        today_formated = '<?php echo \OsT\Base\System::parseDate(\OsT\Base\System::time(), 'd/m/y')?>'; // Текущая дата в форимате 01/01/2001
        editing_level_element_hundler = null;           // указатель на объект в таблице званий, данные которого в текущий момент изменяются
        state_index = <?php echo $state_index;?>;                                // индекс (порядковый номер) последнего элемента должности
        state_edit_index = 0;                           // индекс (порядковый номер) редактируемого элемента должности
        military = <?php echo $id; ?>;                  // Военнослужащий, данные которого редактируются

        $(document).ready(function () {
            on_functions_datepicker();

            $('#new_levels_date').val(today_formated);
        });

        /**
         *  Активировать функцию отображения календаря
         */
        function on_functions_datepicker() {
            $( ".datepicker" ).datepicker({
                changeMonth: true,
                changeYear: true
            });
            $( ".datepicker" ).datepicker( "option", $.datepicker.regional[ "ru" ]);
        }

        /**
         * Отобразить окно добавления звания
         */
        function level_new_show()
        {
            shadowNew(99, function () {
                level_new_window_close();
            });
            $('.levels_new_window').show();
        }


        /**
         * Скрыть окно добавления звания
         */
        function level_new_window_close() {
            $('.levels_new_window').hide();
            $('#new_levels_level').prop('selectedIndex', 0);
            $('#new_levels_date').val(today_formated);
            $('#new_levels_date').css('border-color', '#000');
            shadowRemove(99);
        }

        /**
         * Добавить звание
         */
        function level_new_window_send () {
            $('#new_levels_date').css('border-color', '#000');
            date = $('#new_levels_date').val();

            level = $('#new_levels_level option:selected').val();
            level_title = $('#new_levels_level option:selected').text();

            if (date)
                ajax(
                    'military_level_getitemhtml',
                    {
                        'date' :   date,
                        'level' :  level,
                        'level_title' :  level_title
                    },
                    function (key, data, respond) {
                        respond = JSON.parse(respond);
                        if (!respond['error']) {
                            $('.level_new_button').before(respond['html']);
                            level_new_window_close();
                        } else {
                            switch (respond['error']) {
                                case 'ERR_DATA_FORMAT':
                                    $('#new_levels_date').css('border-color', '#f00');
                                    break;
                            }
                        }
                    }
                );
            else $('#new_levels_date').css('border-color', '#f00');
        }

        /**
         *  Открыть окно изменения звания
         */
        function level_edit_show (ell) {
            shadowNew(99, function () {
                level_edit_window_close();
            });

            editing_level_element_hundler = $(ell).parent().parent().parent();

            level = $(ell).parent().siblings('.dtlevel').val();
            date = $(ell).parent().siblings('.dtdate').val();

            $("#edit_levels_level option[value='" + level + "']").attr("selected", "selected");
            $('#edit_levels_date').val(date);

            $('.levels_edit_window').show();
        }

        /**
         * Изменить звание
         */
        function level_edit_window_send () {
            $('#edit_levels_date').css('border-color', '#000');
            date = $('#edit_levels_date').val();
            level = $('#edit_levels_level option:selected').val();
            level_title = $('#edit_levels_level option:selected').text();

            if (date)
                ajax(
                    'military_level_getitemhtml',
                    {
                        'date' :   date,
                        'level' :  level,
                        'level_title' :  level_title
                    },
                    function (key, data, respond) {
                        respond = JSON.parse(respond);
                        if (!respond['error']) {
                            $(editing_level_element_hundler).replaceWith(respond['html']);
                            editing_level_element_hundler = null;
                            level_edit_window_close();
                        } else {
                            switch (respond['error']) {
                                case 'ERR_DATA_FORMAT':
                                    $('#edit_levels_date').css('border-color', '#f00');
                                    break;
                            }
                        }
                    }
                );
            else $('#edit_levels_date').css('border-color', '#f00');
        }

        /**
         * Скрыть окно изменения звания
         */
        function level_edit_window_close() {
            $('.levels_edit_window').hide();

            $('#edit_levels_level').children('option').each(function () {
                $(this).removeAttr('selected');
            })

            shadowRemove(99);
        }

        /**
         * Удалить звание
         * @param ell
         */
        function level_delete (ell) {
            $(ell).parent().parent().parent().remove();
        }

        /**
         * Удалить должность
         * @param index
         */
        function state_delete (index) {
            $('.state_' + index).remove();
        }

        /**
         * Отобразить окно добавления должности
         */
        function state_new_show()
        {
            shadowNew(99, function () {
                state_new_window_close();
            });
            $('#state_new_mode').val('new');
            $('.state_new_window').show();
        }

        /**
         * Отобразить окно редактирования должности
         */
        function state_edit_show (index)
        {
            shadowNew(99, function () {
                state_new_window_close();
            });
            $('#state_new_mode').val('edit');
            state_edit_index = index;

            // загрузка данных объекта в форму редактирования
            data = getFormData('.state_' + state_edit_index);
            data['index'] = index;
            ajax(
                'military_state_edit_uploadformhtml',
                data,
                function (key, data, respond) {
                    //console.log(respond);
                    respond = JSON.parse(respond);

                    $('select[name="unit0"]').parent().html(respond['unit']);
                    $('#new_state_state').parent().html(respond['state']);
                    $('#new_state_vrio').prop('selectedIndex', parseInt(respond['vrio']));
                    $('#new_state_date_from').val(respond['date_from']);
                    $('#new_state_date_to').val(respond['date_to']);

                    $('.state_new_window').show();
                }
            );
        }

        /**
         * Добавить должность
         */
        function state_new_window_send () {
            $('#new_state_date_from').css('border-color', '#000');
            $('#new_state_date_to').css('border-color', '#000');
            $('#new_state_state').css('border-color', '#000');
            $('.new_state_unit').css('border-color', '#000');

            tmp_data = getFormData('table.state');
            data = getFormData('.state_new_window');
            data['state_title'] = $('#new_state_state option:selected').text();
            data['military'] = military;
            data['tmp_data'] = tmp_data;
            data['key'] = $('#state_new_mode').val();
            if (data['key'] === 'new')
                data['index'] = state_index;
            else data['index'] = state_edit_index;

            ajax(
                'military_state_getitemhtml',
                data,
                function (key, data, respond) {
                    //console.log(respond);
                    respond = JSON.parse(respond);
                    if (!respond['error']) {

                        if (data['key'] === 'new') {
                            state_index++;
                            $('.state_new_button').before(respond['html']);
                        } else {
                            $('.state_' + data['index']).replaceWith(respond['html']);
                        }
                        state_new_window_close();

                    } else {
                        alert(respond['error']);
                        /*
                        switch (respond['error']) {
                            case 'ERR_DATA_FORMAT':
                                $('#edit_levels_date').css('border-color', '#f00');
                                break;
                        } */
                    }
                }
            );
        }

        /**
         * Скрыть окно добавления должности
         */
        function state_new_window_close() {
            $('.state_new_window').hide();

            $('select[name="unit0"]').prop('selectedIndex', 0);
            select_delete_after_index($('select[name="unit0"]'), 0);

            $('#new_state_state').prop('selectedIndex', 0);
            select_delete_options($('#new_state_state'));

            $('#new_state_vrio').prop('selectedIndex', 0);
            $('#new_state_date_from').val('');
            $('#new_state_date_to').val('');

            $('#new_state_state').css('border-color', '#000');
            $('#new_state_vrio').css('border-color', '#000');
            $('#new_state_date_from').css('border-color', '#000');
            $('#new_state_date_to').css('border-color', '#000');

            shadowRemove(99);
        }

        /**
         * Действие при выборе подразделения в окне управления должностью
         * @param select - элемент, значение которого изменили
         * @param window - идентификатор активного окна
         */
        function state_select_unit_change (select, window) {
            unit = parseInt($(select).children('option:selected').val());
            index = parseInt($(select).data('index'));

            // Удаление всех дочерних объектов подразделений
            select_delete_after_index (select, index);

            // Очистка старых должностей
            select_delete_options('#' + window + '_state_state');

            if (unit) {
                ajax(
                    'military_state_upload_unit_select',
                    {
                        'unit' :   unit,
                        'index' :  index,
                        'window' :  window
                    },
                    function (key, data, respond) {
                        respond = JSON.parse(respond);
                        if (respond['type'] === 'unit')
                            $('.'+ data['window'] + '_state_unit[data-index=' + data['index'] + ']').after(respond['html']);
                        else {
                            if (respond['html'] !== '')
                                $('#' + data['window'] + '_state_state').replaceWith(respond['html']);
                        }
                    }
                );
            }
        }

    </script>

    <style>
        .bodyBox {
            width: 80%;
            margin: 20px auto;
            display: inline-block;
            position: relative;
        }
        .pageTitleLine {
            font-family: RalewayB;
            font-size: 24px;
            text-align: left;
            color: #6f6f6f;
            padding: 0 0 15px 0;
            cursor: default;
        }
        .dataBodyBox {
            background-color: #ffffffe8;
            display: inline-block;
            position: relative;
            width: 100%;
            padding: 0 0 20px;
        }

        .formBodyAs2 {
            padding: 0 0 0 20px;
            display: inline-block;
            width: calc(50% - 20px);
            float: left;
        }

        .formInputBoxItem {
            padding: 8px 0 0 0;
            display: inline-block;
            width: 100%;
            float: left;
        }
        .formInputBoxItemTitle {
            text-align: left;
            font-size: 15px;
            cursor: default;
            box-sizing: border-box;
            padding: 0 10px 0 0;
            line-height: 22px;
            color: #5e5e5e;
        }
        .formInputBoxItemInput {
            display: block;
            font-size: 15px;
            padding: 5px;
            box-sizing: border-box;
            height: 32px;
            width: 100%;
            font-family: 'Frank', "Franklin Gothic Medium", serif;
            border: 1px solid #636363;
        }
        textarea.formInputBoxItemInput {
            max-width: 100%;
            min-width: 100%;
        }
        .formFullBox {
            padding: 8px 20px 0;
            display: inline-block;
            width: 100%;
            box-sizing: border-box;
            float: left;
        }

        .formBoxTitle {
            text-align: left;
            font-size: 15px;
            cursor: default;
            box-sizing: border-box;
            padding: 0 10px 0 0;
            line-height: 22px;
            color: #5e5e5e;
        }
        .formBoxBody {
            width: 100%;
            box-sizing: border-box;
            display: inline-block;
            float: left;
            border: 1px solid #636363;
        }

        .formInputBoxItem.description textarea {
            max-height: 250px;
            min-height: 32px;
            height: 32px;
        }

        .form_table {
            width: 100%;
            border-collapse: collapse;
        }
        .form_table .title td {color: #828282;}
        .form_table td {
            border: 1px solid #b1b1b1;
            padding: 4px;
            line-height: 26px;
        }
        .table_td_buttons {
            width: 30px;
        }

        ::-webkit-input-placeholder {color: #bebebe; }
        ::-moz-placeholder {color: #bebebe; opacity: 1; text-overflow: ellipsis;}



        .buttonsBox {
            display: inline-block;
            float: right;
            min-width: 78px;
            padding: 0;
        }
        .buttonsBox .button {
            display: inline-block;
            width: 36px;
            height: 36px;
            background-size: 36px 108px;
            background-position: 0 -36px;
            background-repeat: no-repeat;
            margin: 1px 0 1px 6px;
            float: left;
        }
        .buttonsBox .button:first-child {margin-left: 0;}
        .buttonsBox .button:last-child {margin-right: 0;}
        .buttonsBox .button.edit {background-image: url("img/table_buttons/edit.png");}
        .buttonsBox .button.delete {background-image: url("img/table_buttons/delete.png");}

        .formSubmitButton {
            float: right;
            display: inline-block;
            margin: 20px 20px 0 0;
            background-color: white;
            padding: 5px 25px;
            border: 1px solid;
            font-family: 'Frank', "Franklin Gothic Medium", serif;
        }
    </style>

    <div class="bodyBox no_select">
        <div class="pageTitleLine">
            Военнослужащий
        </div>

        <div class="dataBodyBox">
            <form class="form" action="<?php echo $_SERVER [ 'REQUEST_URI' ] ?>" method="post" enctype="multipart/form-data">

                <input class="hidden" name="submit" type="submit" value="">

                <div class="formBodyAs2">

                    <div class="formInputBoxItem">
                        <div class="formInputBoxItemTitle">Фамилия *</div>
                        <input name="fname" class="formInputBoxItemInput" value="<?php echo @$military['fname']; ?>">
                    </div>

                    <div class="formInputBoxItem">
                        <div class="formInputBoxItemTitle">Имя *</div>
                        <input name="iname" class="formInputBoxItemInput" value="<?php echo @$military['iname']; ?>">
                    </div>

                    <div class="formInputBoxItem">
                        <div class="formInputBoxItemTitle">Отчество *</div>
                        <input name="oname" class="formInputBoxItemInput" value="<?php echo @$military['oname']; ?>">
                    </div>

                    <div class="formInputBoxItem description">
                        <div class="formInputBoxItemTitle">Примечание</div>
                        <textarea name="description" class="formInputBoxItemInput"><?php echo @$military['description']; ?></textarea>
                    </div>

                </div>

                <div class="formBodyAs2">

                    <div class="formFullBox levels">
                        <div class="formBoxTitle">Звания *</div>
                        <div class="formBoxBody">
                            <table class="form_table level">
                                <tr class="title">
                                    <td>Звание</td>
                                    <td>Дата присвоения</td>
                                    <td></td>
                                </tr>
                                <?php
                                    if ($mode === MODE_EDIT)
                                        foreach ($military['levels_data'] as $level)
                                            echo Level::getTableItemHtml($level);
                                ?>
                                <tr class="level_new_button">
                                    <td colspan="3">
                                        <div onclick="level_new_show()">Добавить звание</div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="formFullBox state">
                    <div class="formBoxTitle">Послужной список</div>
                    <div class="formBoxBody">
                        <table class="form_table state">
                            <tr class="title">
                                <td>Подразделение</td>
                                <td>Должность</td>
                                <td>Дата начала</td>
                                <td>Дата окончания</td>
                                <td>Тип</td>
                                <td></td>
                            </tr>
                            <tr class="state_new_button">
                                <?php
                                if ($mode === MODE_EDIT) {
                                    $index = 0;
                                    foreach ($military['states_data'] as $state) {
                                        $state['index'] = $index;
                                        echo \OsT\Military\State::getTableItemHtml($state);
                                        $index++;
                                    }
                                }
                                ?>
                                <td colspan="6">
                                    <div onclick="state_new_show()">Назначить на должность</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input name="submit" class="formSubmitButton" type="submit" value="Готово">

            </form>
        </div>

    </div>

    <div class="settingsInputBox levels_new_window">
        <div class="settingsInputBoxTitle">Добавить звание</div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Звание</div>
            <div class="settingsInputBoxItemSelect">
                <select id="new_levels_level">
                    <?php
                        $levels = \OsT\Level::getData(null, ['id', 'title']);
                        foreach ($levels as $level)
                            echo '<option value="' . $level['id'] . '">' . $level['title'] . '</option>';
                    ?>
                </select>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle">Дата</div>
            <input id="new_levels_date" class="settingsInputBoxItemInput datepicker" tabindex="1" autocomplete="off">
        </div>

        <input class="settingsInputBoxItemSubmit" type="submit" onclick="level_new_window_send()" value="Готово" tabindex="2">
    </div>

    <div class="settingsInputBox levels_edit_window">
        <div class="settingsInputBoxTitle">Изменить звание</div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Звание</div>
            <div class="settingsInputBoxItemSelect">
                <select id="edit_levels_level">
                    <?php
                    foreach ($levels as $level)
                        echo '<option value="' . $level['id'] . '">' . $level['title'] . '</option>';
                    ?>
                </select>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle">Дата</div>
            <input id="edit_levels_date" class="settingsInputBoxItemInput datepicker" tabindex="1" autocomplete="off">
        </div>

        <input class="settingsInputBoxItemSubmit" type="submit" onclick="level_edit_window_send()" value="Готово" tabindex="2">
    </div>

    <div class="settingsInputBox state_new_window">
        <div class="settingsInputBoxTitle">Добавить должность</div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Подразделение</div>
            <div class="settingsInputBoxItemSelect" >
                <select class="new_state_unit" onchange="state_select_unit_change(this, 'new')" data-index="0" name="unit0">
                    <option value="0">- Не выбрано -</option>
                    <?php
                    $units = \OsT\Unit::getChildrenFromTree($STRUCT_TREE);
                    $units_data = \OsT\Unit::getData($units, ['id' , 'title']);
                    foreach ($units_data as $unit)
                        echo '<option value="' . $unit['id'] . '">' . $unit['title'] . '</option>';
                    ?>
                </select>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Должность</div>
            <div class="settingsInputBoxItemSelect">
                <select id="new_state_state" name="state">
                    <option value="0">- Не выбрано -</option>
                </select>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Тип</div>
            <div class="settingsInputBoxItemSelect">
                <select id="new_state_vrio" name="vrio">
                    <option value="0">Постоянно</option>
                    <option value="1">Временно</option>
                </select>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle">Дата начала</div>
            <input id="new_state_date_from" name="date_from" class="settingsInputBoxItemInput datepicker" tabindex="1" autocomplete="off">
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle">Дата окончания</div>
            <input id="new_state_date_to" name="date_to" class="settingsInputBoxItemInput datepicker" tabindex="1" autocomplete="off">
        </div>

        <input id="state_new_mode" name="state_mode" type="hidden">
        <input class="settingsInputBoxItemSubmit" name="submit" type="submit" onclick="state_new_window_send()" value="Готово" tabindex="2">
    </div>

<?php
    require_once 'layouts/footer.php';
?>
