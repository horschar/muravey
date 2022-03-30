<?php

use OsT\Access;

require_once  'layouts/header.php';

    $pageData['title'] = 'Штат';
    $pagesGroup = ['state', 'menu'];

    if (!Access::checkAccess('state_show')) {
        \OsT\Base\System::location('menu.php');
        exit;
    }

    require_once  'layouts/head.php';

?>
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/questionbox.css">
    <script src="js/questionBox.js"></script>

    <script>

        $(document).ready(function () {
            // Автоматическая отправка формы создания / изменения должности при нажатии на клавишу Enter
            $(window).keydown(function (event) {
                if (event.which === 13) {
                    $('.settingsInputBox').each(function () {
                        if ($(this).css('display') !== 'none')
                            $(this).children('.settingsInputBoxItemSubmit').click();
                    });
                }
            });
        });

        /**
         *  Действие отображения содержимого при нажатии на подразделение
         */
        function unit_show_body_toggle (unit) {
            // Если клик по свободной от кноаок области области
            if ($(event.target).hasClass('unitTitleLine') || $(event.target).hasClass('unitTitle')) {
                body = $(unit).siblings('.unitBody');

                // Если тело содержит подразделения
                if ($.trim(body.html()) !== '') {
                    display = $(body).css('display');
                    if (display === 'none')
                        unit_show_body_open (unit);
                    else unit_show_body_close (unit);
                }
            }
        }

        /**
         * Отобразить содержимое подразделения
         */
        function unit_show_body_open (unit) {
            $(unit).siblings('.unitBody').slideDown();
            $(unit).addClass('opened');
        }

        /**
         * Скрыть содержимое подразделения
         */
        function unit_show_body_close (unit) {
            $(unit).parent().find('.unitBody').each(function () {
                $(this).slideUp();
                $(this).siblings('.unitTitleLine').removeClass('opened');
            })
        }

        /**
         * Раскрыть все дерево списков по пути к подразделению
         * @param unit
         */
        function unit_show_body_to (unit) {
            parent = get_unit_parent_box($('#unit_' + unit));
            while (parent !== null) {
                if ($(parent).children('.unitBody').css('display') === 'none') {
                    $(parent).children('.unitBody').slideDown();
                    $(parent).children('.unitTitleLine').addClass('opened');
                }
                parent = get_unit_parent_box(parent);
            }
        }

        /**
         * Подсветка должностей после выполнения действий с ними
         * @param type
         * @param ell
         */
        function highlight (type, ell) {
            aclass = 'highlight animated fadeIn fast ' + type;
            delay = 800;
            if (ell) {
                if (!$(ell).hasClass('.highlight'))
                    $(ell).addClass(aclass);
                setTimeout(function () {
                    $(ell).removeClass(aclass);
                }, delay);
            } else setTimeout(function () {
                $('.highlight').removeClass(aclass);
            }, delay);
        }

        /**
         * Получить дескриптор родительского подразделения
         */
        function get_unit_parent_box (ell) {
            parent = $(ell).parent().parent();
            if ($(parent).hasClass('unitBox'))
                return parent;
            return null;
        }

        /**
         * Отобразить окно добавления должности
         */
        function state_new_window_show (unit) {
            shadowNew(99, function () {
                state_new_window_close();
            });
            $('#new_state_parent').val(unit);
            $('.state_new_window').show();
            $('#new_state_title').focus();
        }

        function state_vrio_checkbox_affect(checkbox, affect) {
            if (affect === undefined) {
                if ($(checkbox).is(':checked'))
                    affect = 'show';
                else affect = 'hide';
            }
            if (affect === 'show') {
                $(checkbox).parent().parent().find('input[name=vrio_title]').parent().removeClass('hidden');
                $(checkbox).parent().parent().find('input[name=vrio_title_short]').parent().removeClass('hidden');
                $(checkbox).parent().parent().find('input[name=vrio_abbreviation]').parent().removeClass('hidden');
            } else {
                $(checkbox).parent().parent().find('input[name=vrio_title]').parent().addClass('hidden');
                $(checkbox).parent().parent().find('input[name=vrio_title_short]').parent().addClass('hidden');
                $(checkbox).parent().parent().find('input[name=vrio_abbreviation]').parent().addClass('hidden');
            }
        }

        /**
         * Скрыть окно добавления должности
         * и очистистить все ранее введенные данные
         */
        function state_new_window_close () {
            $('.state_new_window').hide();
            shadowRemove(99);

            $('#new_state_title').val('');
            $('#new_state_title').css('border-color', '#000');

            $('#new_state_title_short').val('');
            $('#new_state_title_short').css('border-color', '#000');

            $('#new_state_title_abbreviation').val('');
            $('#new_state_title_abbreviation').css('border-color', '#000');

            $('#new_state_vrio_title').val('');
            $('#new_state_vrio_title').css('border-color', '#000');

            $('#new_state_vrio_title_short').val('');
            $('#new_state_vrio_title_short').css('border-color', '#000');

            $('#new_state_vrio_abbreviation').val('');
            $('#new_state_vrio_abbreviation').css('border-color', '#000');

            $('#new_state_parent').val('');

            checkbox = $('#new_state_vrio');
            state_vrio_checkbox_affect(checkbox, 'hide');
            $(checkbox).prop('checked', false);

            show_more = $('.state_new_window').children('.settingsShowMoreButton');
            $(show_more).show();
            $(show_more).siblings('.settingsShowMoreBody').hide();
        }

        /**
         * Добавить должность
         */
        function state_new_window_send () {
            errors = false;

            // Очистка выделения ошибок
            $('#new_state_title').css('border-color', '#000');
            $('#new_state_title_short').css('border-color', '#000');
            $('#new_state_title_abbreviation').css('border-color', '#000');
            $('#new_state_vrio_title').css('border-color', '#000');
            $('#new_state_vrio_title_short').css('border-color', '#000');
            $('#new_state_vrio_abbreviation').css('border-color', '#000');

            // Сбор и обработка данных формы
            unit =  $('#new_state_parent').val();
            unit =  parseInt(unit);
            title = $('#new_state_title').val();
            title = $.trim(title);
            title_short = $('#new_state_title_short').val();
            title_short = $.trim(title_short);
            title_abbreviation = $('#new_state_title_abbreviation').val();
            title_abbreviation = $.trim(title_abbreviation);
            vrio = $('#new_state_vrio').is(':checked');
            vrio = vrio ? 1 : 0;
            vrio_title = $('#new_state_vrio_title').val();
            vrio_title = $.trim(vrio_title);
            vrio_title_short = $('#new_state_vrio_title_short').val();
            vrio_title_short = $.trim(vrio_title_short);
            vrio_abbreviation = $('#new_state_vrio_abbreviation').val();
            vrio_abbreviation = $.trim(vrio_abbreviation);

            // Проверка корректности данных формы
            if (!title) {
                $('#new_state_title').css('border-color', '#f00');
                errors = true;
            }

            /*
            if (!title_short) {
                $('#new_state_title_short').css('border-color', '#f00');
                errors = true;
            }

            if (!title_abbreviation) {
                $('#new_state_title_abbreviation').css('border-color', '#f00');
                errors = true;
            }*/

            if (vrio) {
                if (!vrio_title) {
                    $('#new_state_vrio_title').css('border-color', '#f00');
                    errors = true;
                }

                /*
                if (!vrio_title_short) {
                    $('#new_state_vrio_title_short').css('border-color', '#f00');
                    errors = true;
                }

                if (!vrio_abbreviation) {
                    $('#new_state_vrio_abbreviation').css('border-color', '#f00');
                    errors = true;
                }*/
            }

            // Отправка запроса создания на сервер
            if (!errors) {
                vars = {
                    'unit' : unit,
                    'title': title,
                    'title_short': title_short,
                    'title_abbreviation': title_abbreviation,
                    'vrio': vrio,
                    'vrio_title': vrio_title,
                    'vrio_title_short': vrio_title_short,
                    'vrio_abbreviation': vrio_abbreviation
                };

                ajax(
                    'state_new',
                    vars,
                    function (key, data, respond) {
                        respond = JSON.parse(respond);
                        $('#unit_0').children('.unitTitleLine').children('.unitButtonsBox').children('.delete').removeClass('hidden');
                        parent_unit = $('#unit_' + data['unit']);
                        $(parent_unit).children('.unitTitleLine').children('.unitButtonsBox').children('.delete').removeClass('hidden');
                        $(parent_unit).children('.unitTitleLine').children('.unitButtonsBox').children('.move').removeClass('hidden');

                        parent = get_unit_parent_box(parent_unit);
                        while (parent !== null) {
                            $(parent).children('.unitTitleLine').children('.unitButtonsBox').children('.delete').removeClass('hidden');
                            parent = get_unit_parent_box(parent);
                        }

                        $(parent_unit).children('.unitBody').append(respond['html']);
                        if (!$(parent_unit).children('.unitTitleLine').hasClass('opened'))
                            unit_show_body_open($('#unit_' + data['unit']).children('.unitTitleLine'));
                        state_new_window_close();
                        highlight('new', $('#state_' + respond['id']));
                    }
                );
            }
        }

        /**
         * Отобразить окно редактирования должности
         */
        function state_edit_window_show (id) {
            ajax(
                'state_edit_get_data',
                id,
                function (key, data, respond) {
                    respond = JSON.parse(respond);
                    vrio = respond['vrio'];

                    show_more = $('.state_edit_window').children('.settingsShowMoreButton');
                    $(show_more).hide();
                    $(show_more).siblings('.settingsShowMoreBody').show();

                    $('#edit_state_id').val(id);
                    $('#edit_state_title').val(respond['title']);
                    $('#edit_state_title_short').val(respond['title_short']);
                    $('#edit_state_title_abbreviation').val(respond['title_abbreviation']);

                    if (vrio) {
                        $('#edit_state_vrio_title').val(respond['vrio_title']);
                        $('#edit_state_vrio_title_short').val(respond['vrio_title_short']);
                        $('#edit_state_vrio_abbreviation').val(respond['vrio_abbreviation']);

                        checkbox = $('#edit_state_vrio');
                        state_vrio_checkbox_affect(checkbox, 'show');
                        $(checkbox).prop('checked', true);
                    }

                    shadowNew(99, function () {
                        state_edit_window_close();
                    });
                    $('.state_edit_window').show();
                    $('#edit_state_title').focus();
                }
            );
        }

        /**
         * Скрыть окно редактирования должности
         * и очистистить все ранее введенные данные
         */
        function state_edit_window_close () {
            $('.state_edit_window').hide();
            shadowRemove(99);

            $('#edit_state_title').val('');
            $('#edit_state_title').css('border-color', '#000');

            $('#edit_state_title_short').val('');
            $('#edit_state_title_short').css('border-color', '#000');

            $('#edit_state_title_abbreviation').val('');
            $('#edit_state_title_abbreviation').css('border-color', '#000');

            $('#edit_state_vrio_title').val('');
            $('#edit_state_vrio_title').css('border-color', '#000');

            $('#edit_state_vrio_title_short').val('');
            $('#edit_state_vrio_title_short').css('border-color', '#000');

            $('#edit_state_vrio_abbreviation').val('');
            $('#edit_state_vrio_abbreviation').css('border-color', '#000');

            $('#edit_state_id').val('');

            checkbox = $('#edit_state_vrio');
            state_vrio_checkbox_affect(checkbox, 'hide');
            $(checkbox).prop('checked', false);

            show_more = $('.state_edit_window').children('.settingsShowMoreButton');
            $(show_more).show();
            $(show_more).siblings('.settingsShowMoreBody').hide();
        }

        /**
         * Редактировать должность
         */
        function state_edit_window_send () {
            errors = false;

            // Очистка выделения ошибок
            $('#edit_state_title').css('border-color', '#000');
            $('#edit_state_title_short').css('border-color', '#000');
            $('#edit_state_title_abbreviation').css('border-color', '#000');
            $('#edit_state_vrio_title').css('border-color', '#000');
            $('#edit_state_vrio_title_short').css('border-color', '#000');
            $('#edit_state_vrio_abbreviation').css('border-color', '#000');

            // Сбор и обработка данных формы
            id =  $('#edit_state_id').val();
            id =  parseInt(id);
            title = $('#edit_state_title').val();
            title = $.trim(title);
            title_short = $('#edit_state_title_short').val();
            title_short = $.trim(title_short);
            title_abbreviation = $('#edit_state_title_abbreviation').val();
            title_abbreviation = $.trim(title_abbreviation);
            vrio = $('#edit_state_vrio').is(':checked');
            vrio = vrio ? 1 : 0;
            vrio_title = $('#edit_state_vrio_title').val();
            vrio_title = $.trim(vrio_title);
            vrio_title_short = $('#edit_state_vrio_title_short').val();
            vrio_title_short = $.trim(vrio_title_short);
            vrio_abbreviation = $('#edit_state_vrio_abbreviation').val();
            vrio_abbreviation = $.trim(vrio_abbreviation);

            // Проверка корректности данных формы
            if (!title) {
                $('#edit_state_title').css('border-color', '#f00');
                errors = true;
            }

            if (vrio) {
                if (!vrio_title) {
                    $('#edit_state_vrio_title').css('border-color', '#f00');
                    errors = true;
                }
            }

            // Отправка запроса создания на сервер
            if (!errors) {
                vars = {
                    'id' : id,
                    'title': title,
                    'title_short': title_short,
                    'title_abbreviation': title_abbreviation,
                    'vrio': vrio,
                    'vrio_title': vrio_title,
                    'vrio_title_short': vrio_title_short,
                    'vrio_abbreviation': vrio_abbreviation
                };

                ajax(
                    'state_edit',
                    vars,
                    function (key, data, respond) {
                        state_edit_window_close();
                        if (parseInt(respond)) {
                            $('#state_' + id).children('.stateTitleLine').children('.stateTitle').text(title);
                            highlight('edit', $('#state_' + id));

                        } else alert('В процессе обработки данных произошла непредвиденная ошибка');
                    }
                );
            }

        }

        /**
         * Удалить должность
         */
        function state_delete (state) {
            text = 'Вы действительно хотите безвозвратно удалить данную должность?<br>Военнослужащий, занимавший эту должность получит отметку "Не назначена" в параметре должность, что равнозначно исключению его из подразделения, к которому относится данная должность';

            shadowNew(99, function () {
                $('.questionBox').remove();
                shadowRemove(99);
            });
            showQuestion(
                text,
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                    ajax(
                        'state_delete',
                        state,
                        function (key, data, respond) {
                            parent = $('#state_' + data).parent().parent();
                            $('#state_' + data).remove();

                            if ($.trim($(parent).children('.unitBody').html()) === '')
                                unit_show_body_close($(parent).children('.unitTitleLine'));

                            buttons_visibility_update();
                        }
                    );
                },
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                }
            );
        }

        /**
         * Обновить отображение кнопок массового управления должностями
         */
        function buttons_visibility_update ()
        {
            $('.unitBox').each(function () {
                states_count = $(this).find('.stateBox').length;
                if (states_count) {
                    $(this).children('.unitTitleLine').children('.unitButtonsBox').children('.delete').removeClass('hidden');
                    $(this).children('.unitTitleLine').children('.unitButtonsBox').children('.move').removeClass('hidden');
                } else {
                    $(this).children('.unitTitleLine').children('.unitButtonsBox').children('.delete').addClass('hidden');
                    $(this).children('.unitTitleLine').children('.unitButtonsBox').children('.move').addClass('hidden');
                }
            });
        }

        /**
         * Удалить все дочерние должности подразделения
         */
        function unit_delete_all (unit) {
            text = 'Вы действительно хотите безвозвратно удалить все должности в выбранном подразделении?';
            unit = parseInt(unit);

            shadowNew(99, function () {
                $('.questionBox').remove();
                shadowRemove(99);
            });
            showQuestion(
                text,
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                    ajax(
                        'state_delete_by_unit',
                        unit,
                        function (key, data, respond) {
                            $('.unit_' + data).remove();
                            buttons_visibility_update();
                        }
                    );
                },
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                }
            );
        }

        /**
         * Удалить все должности
         */
        function delete_all () {
            text = 'Вы действительно хотите безвозвратно удалить все должности?';

            shadowNew(99, function () {
                $('.questionBox').remove();
                shadowRemove(99);
            });
            showQuestion(
                text,
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                    ajax(
                        'state_delete_all',
                        null,
                        function (key, data, respond) {
                            $('.stateBox').remove();
                            buttons_visibility_update();
                        }
                    );
                },
                function () {
                    $('.questionBox').remove();
                    shadowRemove(99);
                }
            );
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

        .unitBox,
        .stateBox {
            display: inline-block;
            width: 100%;
            position: relative;
            float: left;
            box-sizing: border-box;
            margin-top: 3px;
        }
        .unitTitleLine,
        .stateTitleLine {
            width: 100%;
            background-color: #ffffff9e;
            display: inline-block;
            padding: 4px 4px 4px 20px;
            box-sizing: border-box;
            float: left;
            border-left: 0;
            cursor: pointer;
            border-left: 2px solid #bda1a1;
            transition: background-color 0.5s;
        }
        .stateTitleLine {
            border-left: 2px solid #3a3030;
        }
        .unitBox:hover > .unitTitleLine,
        .unitTitleLine.opened,
        .stateBox:hover > .stateTitleLine {
            background-color: #581a1a2b;
        }
        .unitTitle,
        .stateTitle {
            display: inline-block;
            float: left;
            font-size: 18px;
            color: #000000d1;
            line-height: 34px;
            max-width: calc(100% - 230px);
            text-align: left;
        }

        .unitTitleLine:hover > .unitTitle,
        .unitTitleLine.opened > .unitTitle,
        .stateTitleLine:hover > .stateTitle {
            color: #000 !important;
        }
        .unitButtonsBox,
        .stateButtonsBox {
            float: right;
            display: inline-block;
            opacity: 0;
            transition: opacity .5s;
        }
        .unitTitleLine:hover .unitButtonsBox,
        .stateTitleLine:hover .stateButtonsBox {
            opacity: 1;
        }
        .unitBody,
        .stateBody {
            display: none;
            float: right;
            width: calc(100% - 11px);
        }


        @keyframes highlight {
            from {}
            to {background-color: white;}
        }

        .highlight .stateTitleLine {
            animation: 0.8s highlight ease-in-out;
            animation-iteration-count: 1;
            animation-fill-mode: forwards;
        }
        .highlight.new .stateTitleLine {background-color: #00972b65;}
        .highlight.move .stateTitleLine {background-color: #00a9cb65;}
        .highlight.edit .stateTitleLine {background-color: #d89a0065;}

        ::-webkit-input-placeholder {color: #bebebe; }
        ::-moz-placeholder {color: #bebebe; opacity: 1; text-overflow: ellipsis;}
    </style>

    <div class="bodyBox no_select">
        <div class="pageTitleLine">
            Список должностей по штату
        </div>

        <?php
            $count_by_units = \OsT\State::getArrAffectedUnits();

            $units_html = '';
            $units = \OsT\Unit::getChildrenFromTree($STRUCT_TREE);
            $buttons = Access::checkAccess('state_edit');
            foreach ($units as $unit)
                $units_html .= \OsT\State::getHtmlUnit($unit, $STRUCT_DATA[$unit]['title'], $buttons, null, null, $count_by_units);

            if (Access::checkAccess('state_edit')) {
                if (\OsT\State::count())
                    $buttons = [['class' => 'delete', 'title' => 'Удалить все', 'function' => 'delete_all()']];
                else $buttons = [['class' => 'delete hidden', 'title' => 'Удалить все', 'function' => 'delete_all()']];
            } else $buttons = false;

            echo \OsT\State::getHtmlUnit(
                    0,
                    'Росгвардия',
                    $buttons,
                    $units_html
                );
        ?>
    </div>

    <div class="settingsInputBox state_new_window">
        <div class="settingsInputBoxTitle">Добавить должность</div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Наименование</div>
            <input name="title" id="new_state_title" class="settingsInputBoxItemInput" placeholder="Начальник мастерской средств связи" tabindex="1">
        </div>

        <span class="settingsShowMoreButton" onclick="settingsShowMore(this)">Больше</span>
        <div class="settingsShowMoreBody">
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitle">Краткое</div>
                <input name="title_short" id="new_state_title_short" class="settingsInputBoxItemInput" placeholder="Начальник мастерской" tabindex="2">
            </div>
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitle">Аббревиатура</div>
                <input name="title_abbreviation" id="new_state_title_abbreviation" class="settingsInputBoxItemInput" placeholder="НМ" tabindex="3">
            </div>
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitleCheckbox">Временно исполняющий обязанности</div>
                <input name="vrio" id="new_state_vrio" onchange="state_vrio_checkbox_affect(this)" type="checkbox" class="settingsInputBoxItemCheckbox" tabindex="4">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио наименование</div>
                <input name="vrio_title" id="new_state_vrio_title" class="settingsInputBoxItemInput" placeholder="Врио начальника мастерской средств связи" tabindex="5">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио краткое</div>
                <input name="vrio_title_short" id="new_state_vrio_title_short" class="settingsInputBoxItemInput" placeholder="Врио начальника мастерской" tabindex="6">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио аббревиатура</div>
                <input name="vrio_abbreviation" id="new_state_vrio_abbreviation" class="settingsInputBoxItemInput" placeholder="Врио НМ" tabindex="7">
            </div>
        </div>

        <input id="new_state_parent" class="hidden" name="unit">
        <input class="settingsInputBoxItemSubmit" name="submit" type="submit" onclick="state_new_window_send()" value="Готово" tabindex="2">
    </div>

    <div class="settingsInputBox state_edit_window">
        <div class="settingsInputBoxTitle">Редактировать должность</div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Наименование</div>
            <input name="title" id="edit_state_title" class="settingsInputBoxItemInput" placeholder="Начальник мастерской средств связи" tabindex="1">
        </div>

        <span class="settingsShowMoreButton" onclick="settingsShowMore(this)">Больше</span>
        <div class="settingsShowMoreBody">
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitle">Краткое</div>
                <input name="title_short" id="edit_state_title_short" class="settingsInputBoxItemInput" placeholder="Начальник мастерской" tabindex="2">
            </div>
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitle">Аббревиатура</div>
                <input name="title_abbreviation" id="edit_state_title_abbreviation" class="settingsInputBoxItemInput" placeholder="НМ" tabindex="3">
            </div>
            <div class="settingsInputBoxItem">
                <div class="settingsInputBoxItemTitleCheckbox">Временно исполняющий обязанности</div>
                <input name="vrio" id="edit_state_vrio" onchange="state_vrio_checkbox_affect(this)" type="checkbox" class="settingsInputBoxItemCheckbox" tabindex="4">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио наименование</div>
                <input name="vrio_title" id="edit_state_vrio_title" class="settingsInputBoxItemInput" placeholder="Врио начальника мастерской средств связи" tabindex="5">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио краткое</div>
                <input name="vrio_title_short" id="edit_state_vrio_title_short" class="settingsInputBoxItemInput" placeholder="Врио начальника мастерской" tabindex="6">
            </div>
            <div class="settingsInputBoxItem hidden">
                <div class="settingsInputBoxItemTitle">Врио аббревиатура</div>
                <input name="vrio_abbreviation" id="edit_state_vrio_abbreviation" class="settingsInputBoxItemInput" placeholder="Врио НМ" tabindex="7">
            </div>
        </div>

        <input id="edit_state_id" class="hidden" name="id">
        <input class="settingsInputBoxItemSubmit" name="submit" type="submit" onclick="state_edit_window_send()" value="Готово" tabindex="2">
    </div>

<?php
    require_once 'layouts/footer.php';
?>
