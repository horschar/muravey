<?php

    use OsT\Access;

    require_once  'layouts/header.php';

    $pageData['title'] = 'Структура';
    $pagesGroup = ['units', 'menu'];

    if (!Access::checkAccess('units_show')) {
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
            // Автоматическая отправка формы создания / изменения / перемещения подразделения при нажатии на клавишу Enter
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
         * Отобразить окно добавления подразделения
         */
        function unit_new_window_show (parent) {
            shadowNew(99, function () {
                unit_new_window_close();
            });
            $('#new_unit_parent').val(parent);
            $('.unit_new_window').show();
            $('#new_unit_title').focus();
        }

        /**
         * Скрыть окно добавления подразделения
         */
        function unit_new_window_close () {
            $('.unit_new_window').hide();
            $('#new_unit_title').val('');
            $('#new_unit_title').css('border-color', '#000');
            shadowRemove(99);
        }

        /**
         * Добавить подразделение
         */
        function unit_new_window_send () {
            $('#new_unit_title').css('border-color', '#000');
            parent = $('#new_unit_parent').val();
            title = $('#new_unit_title').val();
            title = $.trim(title);
            if (title)
                ajax(
                    'unit_new',
                    {
                        'title' :   title,
                        'parent' :  parent
                    },
                    function (key, data, respond) {
                        respond = JSON.parse(respond);
                        $('#unit_0').children('.unitTitleLine').children('.unitButtonsBox').children('.delete').removeClass('hidden');
                        $('#unit_' + data['parent']).children('.unitBody').append(respond['html']);
                        if (!$('#unit_' + data['parent']).children('.unitTitleLine').hasClass('opened'))
                            unit_show_body_open($('#unit_' + data['parent']).children('.unitTitleLine'));
                        unit_new_window_close();
                        highlight ('new', $('#unit_' + respond['id']));
                    }
                );
            else $('#new_unit_title').css('border-color', '#f00');
        }

        /**
         * Отобразить окно изменения подразделения
         */
        function unit_edit_window_show (unit) {
            title = $('#unit_' + unit).children('.unitTitleLine').children('.unitTitle').text();
            shadowNew(99, function () {
                unit_edit_window_close();
            });
            $('#edit_unit_id').val(unit);
            $('#edit_unit_title').val(title);
            $('.unit_edit_window').show();
            $('#edit_unit_title').focus();
        }

        /**
         * Скрыть окно изменения подразделения
         */
        function unit_edit_window_close () {
            $('.unit_edit_window').hide();
            $('#edit_unit_title').val('');
            $('#edit_unit_title').css('border-color', '#000');
            shadowRemove(99);
        }

        /**
         * Изменить данные подразделения
         */
        function unit_edit_window_send () {
            $('#edit_unit_title').css('border-color', '#000');
            unit = $('#edit_unit_id').val();
            title = $('#edit_unit_title').val();
            title = $.trim(title);
            if (title)
                ajax(
                    'unit_edit',
                    {
                        'title' :   title,
                        'id' :  unit
                    },
                    function (key, data, respond) {
                        $('#unit_' + data['id']).children('.unitTitleLine').children('.unitTitle').text(data['title']);
                        highlight ('edit', $('#unit_' + data['id']));
                        unit_edit_window_close();
                    }
                );
            else $('#edit_unit_title').css('border-color', '#f00');
        }

        /**
         * Отобразить окно перемещения подразделения
         */
        function unit_move_window_show (unit) {
            shadowNew(99, function () {
                unit_move_window_close();
            });
            $('#move_unit_id').val(unit);
            ajax(
                'unit_move_get_select_list',
                unit,
                function (key, data, respond) {
                    $('.unit_move_window .settingsInputBoxItem .settingsInputBoxItemSelect').html(respond);
                    $('.unit_move_window .settingsInputBoxItem .settingsInputBoxItemSelect select').change(function(e) {
                        unit_move_update_units_select_list(e);
                    });
                    $('.unit_move_window').show();
                }
            );
        }

        /**
         * Обновить списки подразделений в окне перемещения подразделения
         */
        function unit_move_update_units_select_list (e) {
            selected_unit = parseInt($(e.target).children('option:selected').val());
            if (selected_unit === -1) {
                update_units_select_list_delete_after($(e.target));
            } else {
                dont_show_unit = parseInt($('#move_unit_id').val());
                ajax(
                    'unit_move_update_select_list',
                    {
                        'unit': dont_show_unit,
                        'selected': selected_unit
                    },
                    function (key, data, respond) {
                        $('.unit_move_window .settingsInputBoxItem .settingsInputBoxItemSelect').html(respond);
                        $('.unit_move_window .settingsInputBoxItem .settingsInputBoxItemSelect select').change(function (e) {
                            unit_move_update_units_select_list(e);
                        });
                    }
                );
            }
        }

        /**
         * Удалить все дочерние списки выбора подразделения в окне перемещения подразделения
         */
        function update_units_select_list_delete_after (select) {
            select_delete_after_name_index (select, 'unit_move_');
        }

        /**
         * Скрыть окно перемещения подразделения
         */
        function unit_move_window_close () {
            $('.unit_move_window').hide();
            shadowRemove(99);
        }

        /**
         * Переместить подразделение
         */
        function unit_move_window_send () {
            unit = $('#move_unit_id').val();
            selected_units = form_get_data_all($('.unit_move_window .settingsInputBoxItemSelect'));
            ajax(
                'unit_move',
                {
                    'id' :  unit,
                    'parent' : selected_units
                },
                function (key, data, respond) {
                    parent = parseInt(respond);
                    $('#unit_' + data['id']).appendTo( $('#unit_' + parent).children('.unitBody') );
                    unit_show_body_to(data['id']);
                    highlight ('move', $('#unit_' + data['id']));
                    unit_move_window_close();
                }
            );
        }

        /**
         * Удалить подразделение
         */
        function unit_delete (unit) {
            text = 'Вы действительно хотите безвозвратно удалить данное подразделение вместе со всеми дочерними?';

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
                        'unit_delete',
                        unit,
                        function (key, data, respond) {
                            parent = get_unit_parent_box($('#unit_' + data));
                            $('#unit_' + data).remove();

                            if ($.trim($(parent).children('.unitBody').html()) === '')
                                unit_show_body_close($(parent).children('.unitTitleLine'));

                            if ($('.unitBox').length === 1) {
                                $('#unit_0').children('.unitTitleLine').children('.unitButtonsBox').children('.delete').addClass('hidden');
                                unit_show_body_close($('#unit_0').children('.unitTitleLine'));
                            }
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
         * Удалить все подразделения
         */
        function unit_delete_all () {
            text = 'Вы действительно хотите безвозвратно удалить все подразделения?';

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
                        'unit_delete_all',
                        null,
                        function (key, data, respond) {
                            $('#unit_0').children('.unitTitleLine').children('.unitButtonsBox').children('.delete').addClass('hidden');
                            $('#unit_0').children('.unitBody').html('');
                            unit_show_body_close($('#unit_0').children('.unitTitleLine'));
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
         * Подсветка подразделений после выполнения действий с ними
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

    </script>

    <style>
        .bodyBox {
            width: 80%;
            margin: 20px auto;
            display: inline-block;
            position: relative;
        }
        .pageTitleLine {
            font-family: RalewayB, sans-serif;
            font-size: 24px;
            text-align: left;
            color: #6f6f6f;
            padding: 0 0 15px 0;
            cursor: default;
        }

        .unitBox {
            display: inline-block;
            width: 100%;
            position: relative;
            float: left;
            box-sizing: border-box;
            margin-top: 3px;
        }
        .unitTitleLine {
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
        .unitBox:hover > .unitTitleLine,
        .unitTitleLine.opened {
            background-color: #581a1a2b;
        }
        .unitTitle {
            display: inline-block;
            float: left;
            font-size: 18px;
            color: #000000d1;
            line-height: 34px;
        }

        .unitTitleLine:hover > .unitTitle,
        .unitTitleLine.opened > .unitTitle {
            color: #000 !important;
        }
        .unitButtonsBox {
            float: right;
            display: inline-block;
            opacity: 0;
            transition: opacity .5s;
        }
        .unitTitleLine:hover .unitButtonsBox {
            opacity: 1;
        }
        .unitBody {
            display: none;
            float: right;
            width: calc(100% - 11px);
        }


        @keyframes highlight {
            from {}
            to {background-color: white;}
        }
        .highlight .unitTitleLine {
            animation: 0.8s highlight ease-in-out;
            animation-iteration-count: 1;
            animation-fill-mode: forwards;
        }
        .highlight.new .unitTitleLine {background-color: #00972b65;}
        .highlight.move .unitTitleLine {background-color: #00a9cb65;}
        .highlight.edit .unitTitleLine {background-color: #d89a0065;}
    </style>

    <div class="bodyBox no_select">
        <div class="pageTitleLine">
            Структура подразделений Росгвардии
        </div>

        <?php
            $units_html = '';
            $units = \OsT\Unit::getChildrenFromTree($STRUCT_TREE);
            $buttons = Access::checkAccess('units_edit');
            foreach ($units as $unit)
                $units_html .= \OsT\Unit::getHtml($unit, $STRUCT_DATA[$unit]['title'], $buttons);

            if (Access::checkAccess('units_edit')) {
                $buttons = [['class' => 'new', 'title' => 'Добавить', 'function' => 'unit_new_window_show(0)']];
                if (count($units))
                    $buttons[] = ['class' => 'delete', 'title' => 'Удалить все', 'function' => 'unit_delete_all()'];
                else $buttons[] = ['class' => 'delete hidden', 'title' => 'Удалить все', 'function' => 'unit_delete_all()'];
            } else $buttons = false;

            echo \OsT\Unit::getHtml(
                    0,
                    'Росгвардия',
                    $buttons,
                    $units_html
                );
        ?>
    </div>

    <div class="settingsInputBox unit_new_window">
        <div class="settingsInputBoxTitle">Добавить подразделение</div>
        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Наименование</div>
            <input id="new_unit_title" class="settingsInputBoxItemInput" tabindex="1">
        </div>
        <input id="new_unit_parent" class="hidden">
        <input class="settingsInputBoxItemSubmit" type="submit" onclick="unit_new_window_send()" value="Готово" tabindex="2">
    </div>

    <div class="settingsInputBox unit_edit_window">
        <div class="settingsInputBoxTitle">Изменить подразделение</div>
        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Наименование</div>
            <input id="edit_unit_title" class="settingsInputBoxItemInput" tabindex="1">
        </div>
        <input id="edit_unit_id" class="hidden">
        <input class="settingsInputBoxItemSubmit" type="submit" onclick="unit_edit_window_send()" value="Готово" tabindex="2">
    </div>

    <div class="settingsInputBox unit_move_window">
        <div class="settingsInputBoxTitle">Переместить подразделение</div>
        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Подразделение</div>
            <div class="settingsInputBoxItemSelect"></div>
        </div>
        <input id="move_unit_id" class="hidden">
        <input class="settingsInputBoxItemSubmit" type="submit" onclick="unit_move_window_send()" value="Готово" tabindex="2">
    </div>

<?php
    require_once 'layouts/footer.php';
?>
