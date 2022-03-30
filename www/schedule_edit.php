<?php
    require_once  __DIR__ . '/layouts/header.php';

    $pageData['title'] = 'График нагрузки';
    $pagesGroup = ['schedule', 'menu'];

    use OsT\Access;
    use OsT\Base\Message;
    use OsT\Base\System;
    use OsT\Serviceload\Mask;
    use OsT\Serviceload\Schedule;
    use OsT\Unit;

    if (Access::checkAccess('schedules_show')) {
        $year = @intval($_GET['year']);
        $month = @intval($_GET['mon']);
        $unit = @intval($_GET['unit']);

        if ($year && $month && $unit) {

            if (isset($STRUCT_DATA[$unit])) {
                define('YEAR', $year);
                define('MONTH', $month);
                define('UNIT', $unit);


                $time = System::convertMonthToTimeInterval($year, $month);
                $schedule = new Schedule($unit, $time['from'], $time['to']);

                if ($schedule->flag_workability) {
                    $USER->settings['schedule']['unit'] = $unit;
                    $USER->update(['settings' => json_encode($USER->settings)]);

                } else {
                    new Message('Не удалось загрузить данные о графике нагрузки', Message::TYPE_ERROR);
                    System::location('schedule_select.php');
                    exit;
                }

            } else {
                System::location('schedule_select.php');
                exit();
            }

        } else {
            // Автоматически определить параметры графика нагрузки
            if (isset($USER->settings['schedule']['unit'])) {
                if (isset($STRUCT_DATA[$USER->settings['schedule']['unit']])) {
                    $now = getdate();
                    System::location('schedule_edit.php?year=' . $now['year'] . '&mon=' . $now['mon'] . '&unit=' . $USER->settings['schedule']['unit']);
                    exit();
                }
            }
            System::location('schedule_select.php');
            exit();
        }

    } else {
        new Message('Недостаточно прав для доступа данной информации', Message::TYPE_ACCESS);
        System::location('index.php');
        exit;
    }

    require_once  __DIR__ . '/layouts/head.php';
?>

<script src="js/textSelect.js"></script>
<script src="js/schedule_visibility.js"></script>
<script src="js/jquery-ui/jquery-ui.js"></script>
<script src="js/jquery-ui/datepicker-ru.js"></script>
<link rel="stylesheet" href="js/jquery-ui/jquery-ui.min.css">

<script>
    <?php
        echo System::php2js('type_naryad',Schedule::TYPE_NARYAD);
        echo System::php2js('type_vuhodnoi',Schedule::TYPE_VUHODNOI);
        echo System::php2js('type_rabochui',Schedule::TYPE_RABOCHUI);

        echo System::php2js('schedule_year', YEAR);
        echo System::php2js('schedule_month', MONTH);
        echo System::php2js('schedule_unit', UNIT);
    ?>
    var types_no_interval = new Array(type_rabochui, type_vuhodnoi, type_naryad);   // Массив типов слуебной нагрузки, которая указывается лишь на день и не является интервальной величиной
    var activeMilitary = null; // Идентификатор военнослужащего, для которого в данный момент открыто окно параметров наряда
    var activeDay = null;      // Идентификатор дня месяца, для которого в данный момент открыто окно параметров наряда
    var schedulePreactive = true;               // Разрешить применять preactive для ячеек
    var schedulePreactiveInterval = false;      // preactive применен к интевалу (отпуск, больничный и т.п.)
    var schedulePreactiveIntervalBegin = null;  // preactive начало итервала
    var schedulePreactiveIntervalEnd = null;    // preactive конец итервала
    var scheduleWorkListOpened = false;     // Открыто ли меню выбора нагрузки
    var scheduleContextListOpened = false;  // Открыто контектное меню
    var scheduleWorkBoxOpened = false;      // Открыто окно доп параметров нагрузки (служба, отпуск, и тд.)
    var scheduleListClosing = false;        // Выполняется переход от события закрытия выпадающего списка (mouseDown) до события разворачивания нового списка (mouseUp)
    var multiSelect = false;                // применяется активное выделение ячеек (multiSelect)
    var multiSelectExtension = false;       // режим растягивания диапазона multiSelect
    var multiSelectMilitary = null;         // Военнослужащий, для которого применяется выделение ячеек (multiSelect)
    var multiSelectDayBegin = null;         // День месяца, с которого начинается выделение ячеек (multiSelect)
    var multiSelectDayEnd = null;           // День месяца, на котором заканчивается выделение ячеек (multiSelect)

    $(document).ready(function () {
        $('.no_context').attr('oncontextmenu', 'return false;'); // отключить контекстное меню для таблицы графика

        $('.mySelect').each(function () {
            $(this).find('.item').on('click', function () {
                schedule_select_item(this);
            });
        });

        $('.mySelect').parent().mouseleave(function (e) {
            if (!multiSelectMilitary)
                scheduleMoveClean();
            $('.value.preactive').removeClass('preactive');
            schedulePreactiveInterval = false;
        });

        $('.mySelect').parent().mouseenter(function (e) {
            // multiSelect
            if (multiSelectExtension) {
                multiSelectDayEnd = parseInt($(e.currentTarget).children('.mySelect').data('day'));
                if (multiSelectDayBegin !== multiSelectDayEnd) {
                    if (multiSelectDayBegin < multiSelectDayEnd) {
                        for (day = multiSelectDayBegin; day <= multiSelectDayEnd; day++) {
                            type = $('#col_' + multiSelectMilitary + '_' + day).children('.value').data('type');
                            if (types_no_interval.indexOf(type) === -1) {
                                multiSelectDayEnd = day - 1;
                                break;
                            }
                        }
                    } else if (multiSelectDayBegin > multiSelectDayEnd) {
                        for (day = multiSelectDayBegin; day >= multiSelectDayEnd; day--) {
                            type = $('#col_' + multiSelectMilitary + '_' + day).children('.value').data('type');
                            if (types_no_interval.indexOf(type) === -1) {
                                multiSelectDayEnd = day + 1;
                                break;
                            }
                        }
                    }
                }
                mySelectMultiPaint();
            }

            // preactive
            if (schedulePreactive) {
                data = mySelect_getData($(e.currentTarget).children('.mySelect'));
                if (types_no_interval.indexOf(data['id']) === -1) { // подсветить интервал (отпуск и т.п.)
                    id = $(e.currentTarget).children('.mySelect').children('.value').data('id');
                    interval = getAbsentDaysInterval(id);
                    schedulePreactiveIntervalBegin = interval['begin'];
                    schedulePreactiveIntervalEnd = interval['end'];
                    $('[data-id=' + id + ']').addClass('preactive');
                    schedulePreactiveInterval = true;
                } else { // подсветить один день
                    $(e.currentTarget).children('.mySelect').children('.value').addClass('preactive');
                }
            }

            scheduleMove($(e.currentTarget).children('.mySelect'));
        });

        $(document).mousedown(function (e) {
            if (!is_or_has($('.value.active').parent().parent(), e.target) &&     // Если клик не по ячейке mySelect
                !scheduleWorkBoxOpened) {     // И окно доп опций закрыто
                select_close_list_all();
                mySelectContextMenuClose();
                scheduleMoveClean();
                mySelectMultiClear();
                schedulePreactive = true;
            }
        });

        $(document).mouseup(function (e) { // событие клика по веб-документу
            multiSelectExtension = false;
            if (multiSelectMilitary && !scheduleWorkListOpened && !scheduleContextListOpened && !scheduleWorkBoxOpened)
                select_show_list($(getMySelectEll(multiSelectMilitary, multiSelectDayEnd)).children('.value'));
        });

    });

    /**
     * Определить первый и последний день цельного интервала по его идентификатору
     */
    function getAbsentDaysInterval(id) {
        begin = 32;
        end = 0;
        $('[data-id=' + id + ']').each(function () {
            day = $(this).parent().data('day');
            if (day < begin)
                begin = day;
            if (day > end)
                end = day;
        });
        return {'begin': begin, 'end': end};
    }

    function mySelectMouseDown(e) {
        if (scheduleWorkListOpened || scheduleContextListOpened) {
            data = mySelect_getData($(e.currentTarget).parent());
            if (!data['active']) { // Клик по неактивной ячейке
                select_close_list_all();
                mySelectContextMenuClose();
                scheduleListClosing = true;
                scheduleMoveClean();
                mySelectMultiClear();
                scheduleMove($(e.currentTarget).parent());
                schedulePreactive = true;
            } else { // Клик по активной ячейке
                if (scheduleWorkListOpened && e.button !== 0)
                    select_close_list_all();
                if (scheduleContextListOpened && e.button !== 2)
                    mySelectContextMenuClose();
            }
        } else {
            data = mySelect_getData($(e.currentTarget).parent());
            multiSelect = true;
            multiSelectMilitary = data['military'];
            if (types_no_interval.indexOf(data['id']) === -1) {
                interval = getAbsentDaysInterval($(e.currentTarget).data('id'));
                multiSelectDayBegin = interval['begin'];
                multiSelectDayEnd = interval['end'];
            } else {
                multiSelectExtension = true;
                multiSelectDayBegin = data['day'];
                multiSelectDayEnd = multiSelectDayBegin;
            }
            mySelectMultiPaint();
            scheduleListClosing = false;
            schedulePreactive = false;
        }
    }

    /**
     * Событие клика по дочернему элементу MySelect с классом value
     */
    function mySelectMouseUp(e) {
        if (!scheduleListClosing) {
            switch (e.button) {
                case 0: // левая кнопка мыши
                    select_show_list(e.currentTarget);
                    break;
                case 1: // средняя кнопка мыши
                    break;
                case 2: // правая кнопка мыши
                    mySelectContextMenu(e.currentTarget);
                    break;
            }
        }
    }

    function mySelectMultiPaint() {
        $('.value.active').removeClass('active');
        if (multiSelectDayBegin < multiSelectDayEnd) {
            from = multiSelectDayBegin;
            end = multiSelectDayEnd;
        } else {
            from = multiSelectDayEnd;
            end = multiSelectDayBegin;
        }
        for (var day=from; day<=end; day++)
            $('#col_'+multiSelectMilitary+'_'+day).children('.value').addClass('active');
    }

    function mySelectMultiClear() {
        multiSelect = false;
        multiSelectMilitary = null;
        multiSelectDayBegin = null;
        multiSelectDayEnd = null;
        $('.value.active').removeClass('active');
    }

    /**
     *  Отобразить контекстное меню ячейки графика
     */
    function mySelectContextMenu(ell) {
        if (!scheduleContextListOpened) {
            if (multiSelect)
                ell = $(getMySelectEll(multiSelectMilitary, multiSelectDayEnd)).children('.value');
            $(ell).parent().append($('.context'));
            mySelectContextCalcList();
            scheduleListPosition(ell);
            $('.context').fadeIn();
            $('.context').addClass('opened');
            scheduleContextListOpened = true;
        }
    }

    function mySelectContextCalcList() {
        full_list = ['edit', 'delete', 'continue', 'copy', 'paste', 'default', 'later'];
        type = $(getMySelectEll(multiSelectMilitary, multiSelectDayEnd)).children('.value').data('type');
        if (types_no_interval.indexOf(data['id']) === -1) { // отпуск, больичный и т.п.
            list = ['edit', 'delete'];
        } else { // рабочий, служба, выходной
            if (multiSelectDayBegin === multiSelectDayEnd) {   // выделена одна ячейка
                if (type === type_naryad) { // выделена ячейка типа "Служба"
                    list = ['edit']; // 'paste'
                } else {    // выделеа ячейка типа "Рабочий" или "Выходой"
                    list = ['later']; // 'paste'
                }
            } else {    // выделено много ячеек
                list = ['continue', 'default']; // , 'copy', 'paste'
            }
        }
        $.each(full_list, function(index, value){
            if (list.indexOf(value) === -1)
                $('.context').children('.c_' + value).hide();
            else
                $('.context').children('.c_' + value).show();
        });
    }

    function mySelectContextMenuAction(action) {
        mySelectContextMenuClose();
        type = $(getMySelectEll(multiSelectMilitary, multiSelectDayEnd)).children('.value').data('type');
        switch (action) {
            case 'edit':
                data = {
                    'id': type,
                    'day': multiSelectDayEnd,
                    'military': multiSelectMilitary
                };
                if (types_no_interval.indexOf(data['id']) === -1)
                    otpuskOpen(data);
                else naryadOpen(data);
                break;

            case 'delete':
                activeMilitary = multiSelectMilitary;
                activeDay = multiSelectDayEnd;
                otpuskDelete();
                break;

            case 'default':
                activeMilitary = multiSelectMilitary;
                ajax(
                    'set_default_values',
                    {
                        'year': schedule_year,
                        'month': schedule_month,
                        'day_begin': multiSelectDayBegin,
                        'day_end': multiSelectDayEnd,
                        'military': multiSelectMilitary
                    },
                    function (key, data, respond) {
                        scheduleSetData(respond);
                    },
                    'ajax_schedule.php'
                );
                break;

            case 'continue':
                activeMilitary = multiSelectMilitary;
                ajax(
                    'continue_schedule_values',
                    {
                        'unit': schedule_unit,
                        'year': schedule_year,
                        'month': schedule_month,
                        'day_begin': multiSelectDayBegin,
                        'day_end': multiSelectDayEnd,
                        'military': multiSelectMilitary
                    },
                    function (key, data, respond) {
                        scheduleSetData(respond);
                    },
                    'ajax_schedule.php'
                );
                break;
        }
    }

    /**
     * Скрыть контекстное меню ячейки графика
     */
    function mySelectContextMenuClose() {
        $('.context').fadeOut();
        $('.context').removeClass('opened');
        scheduleContextListOpened = false;
    }

    /**
     *  Получить дескриптор ячейки по известным параметрам military и day
     *  military - идентификатор военнослужащего
     *  day - идентификатор дня месяца
     */
    function getMySelectEll(military, day) {
        return $('#col_' + military + '_' + day);
    }

    /**
     *  Отобразить выпадающий список типов службы при нажатии на ячейку
     *  ell - дексриптор ячейки
     */
    function select_show_list(ell) {
        if (!scheduleWorkListOpened) {
            if (multiSelect)
                ell = $(getMySelectEll(multiSelectMilitary, multiSelectDayEnd)).children('.value');
            type = parseInt($(ell).data('type'));
            if (types_no_interval.indexOf(type) === -1) {
                data = {
                    'id': type,
                    'day': parseInt($(ell).parent().data('day')),
                    'military': parseInt($(ell).parent().data('military'))
                };
                otpuskOpen(data);
            } else {
                scheduleListPosition(ell);
                $(ell).siblings('.items').fadeIn();
                $(ell).siblings('.items').addClass('select_items_opened');
                scheduleWorkListOpened = true;
            }
        }
    }

    function scheduleListPosition(ell) {
        var iconHeight = $(ell).outerHeight();  // высота ячейки mySelect
        var windowHeight = $(window).height();  // высота видимой области страницы
        var windowWidth = $(window).width();    // ширина видимой области страницы
        var scrollTop = $(window).scrollTop();  // расстояие от верха страницы до видимой области
        var listOffsetTop = $(ell).offset().top;    // расстояние от верха страницы до ячейки mySelect
        var listOffsetLeft = $(ell).offset().left;  // расстояние от левой стенки страницы до ячейки mySelect
        var listHeight = $(ell).siblings('.items').height();    // высота списка items
        var listWidth = $(ell).siblings('.items').width();      // ширина списка items
        var contextHeight = $(ell).siblings('.context').height();    // высота списка context
        var contextWidth = $(ell).siblings('.context').width();      // ширина списка context
        $(ell).siblings('.items').removeClass('t');
        $(ell).siblings('.items').removeClass('l');
        $(ell).siblings('.context').removeClass('t');
        $(ell).siblings('.context').removeClass('l');

        // Выставить items
        if (scrollTop + windowHeight < listOffsetTop + iconHeight + listHeight) {
            $(ell).siblings('.items').css('top', '-' + listHeight + 'px');
            $(ell).siblings('.items').addClass('t');
        } else $(ell).siblings('.items').css('top', iconHeight + 'px');
        if (windowWidth - listOffsetLeft < listWidth)
            $(ell).siblings('.items').addClass('l');

        // выставить шаблоны
        $(ell).siblings('.items').show();
        var ellMask = $(ell).siblings('.items').children('.itemB').children('.naryad_maskBl');
        var maskWidth = $(ellMask).width();
        developmentBar('maskWidth', maskWidth);
        $(ellMask).removeClass('left');
        if (windowWidth - listOffsetLeft < listWidth + maskWidth)
            $(ellMask).addClass('left');
        $(ell).siblings('.items').hide();

        // Выставить context
        if (scrollTop + windowHeight < listOffsetTop + iconHeight + contextHeight) {
            $(ell).siblings('.context').css('top', '-' + contextHeight + 'px');
            $(ell).siblings('.context').addClass('t');
        } else $(ell).siblings('.context').css('top', iconHeight + 'px');
        if (windowWidth - listOffsetLeft < contextWidth)
            $(ell).siblings('.context').addClass('l');
    }

    /**
     *  Скрыть выпадающий список типов службы определенной ячейки
     *  ell - дескриптор элемента ячейки с классом value
     */
    function select_close_list(ell) {
        $(ell).siblings('.items').fadeOut(150);
        $(ell).siblings('.items').removeClass('select_items_opened');
        scheduleWorkListOpened = false;
    }

    /**
     *  Скрыть все открытые выпадающие списки типов службы
     */
    function select_close_list_all() {
        $('.select_items_opened').each(function () {
            $(this).fadeOut(150);
            $(this).removeClass('select_items_opened');
        });
        scheduleWorkListOpened = false;
    }

    /**
     * Дествие при выборе элемента из випадающего списка (только из первичного)
     * @param ell - дексриптор элемента списка с классом item
     */
    function schedule_select_item(ell) {
        var value_ell = $(ell).parent().parent().siblings('.value');
        var data = select_item_getdata(ell);
        var simpleData = new Array(type_rabochui, type_vuhodnoi);
        if (simpleData.indexOf(data['id']) !== -1)
            schedule_set_data(data['military'], data['day'], data['id'], null);
        else if (data['id'] === type_naryad)
            naryadOpen(data);
        else otpuskOpen(data);
        select_close_list(value_ell);
    }

    /**
     * Получить данные выбранного элемента из выпадающего списка типов службы (только из первичного)
     * @param ell - дексриптор элемента списка с классом item
     * @returns {{military: *, id: *, day: *}}
     */
    function select_item_getdata(ell) {
        return {
            'id': parseInt($(ell).data('id')),
            'day': parseInt($(ell).parent().parent().parent().data('day')),
            'military': parseInt($(ell).parent().parent().parent().data('military'))
        };
    }

    /**
     * Получить данные ячейки mySelect
     * @param ell - дексриптор элемента списка с классом mySelect
     * @returns {{military: *, id: *, day: *}}
     */
    function mySelect_getData(ell) {
        return {
            'id': parseInt($(ell).children('.value').data('type')),
            'day': parseInt($(ell).data('day')),
            'military': parseInt($(ell).data('military')),
            'active': $(ell).children('.value').hasClass('active')
        };
    }

    /**
     * Установить новое значение ячейки
     * @param military - идентфикатор военноеслуащего
     * @param day - значение дня месяца, из меню которого производится вызов функции
     * @param type - тип нагрузки
     * @param data - набор данных
     * @param solid_interval - применяется для указания цельных интервалов типа отпуск, больничный, командирока и оенный госпиталь
     */
    function schedule_set_data (military, day, type, data, solid_interval = false) {
        if ((activeMilitary === null && activeDay === null) || (activeMilitary === military && activeDay === day)) {
            activeMilitary = military;
            activeDay = day;
            var action = 'schedule_set_data';
            if (solid_interval)
                action = 'schedule_set_data_interval';
            ajax(
                action,
                {
                    'military': activeMilitary,
                    'year': schedule_year,
                    'month': schedule_month,
                    'day_begin': multiSelectDayBegin,
                    'day_end': multiSelectDayEnd,
                    'servicetype': type,
                    'additional_data': data
                },
                function (key, data, respond) {
                    if (key === 'schedule_set_data')
                        scheduleSetData(respond);
                    else {
                        data = JSON.parse(respond);
                        if (data.errors === false) {
                            if (data.html !== false) {
                                $.each(data.html, function(index, value){
                                    ell = getMySelectEll(activeMilitary, index);
                                    $(ell).children('.value').remove();
                                    $(ell).children('.items').before(value);
                                });
                            }
                        } else alert(data.errors);
                        window_close();
                    }
                },
                'ajax_schedule.php'
            );
        }
    }

    /**
     * Открыть окно выбора параметров отпуска/больничного/командировки/госпиталя
     */
    function otpuskOpen(data) {
        $(".naryadBl").css({ 'height' : '', 'width' : '' });
        activeMilitary =  data['military'];
        activeDay = data['day'];
        ajax(
            'otpusk_change',
            {
                'year': schedule_year,
                'month': schedule_month,
                'unit': schedule_unit,
                'day_begin': multiSelectDayBegin,
                'day_end': multiSelectDayEnd,
                'military': data['military'],
                'type': data['id']
            },
            function (key, data, respond) {
                $('.naryadBl').html(respond);
                window_open();
            },
            'ajax_schedule.php'
        );
    }

    /**
     * Отправить данные из окна отпуска на сервер
     */
    function otpuskOk() {
        var data = {
            'from': $('input[name=otpusk_from]').val(),
            'to': $('input[name=otpusk_to]').val(),
            'mode': $('input[name=otpusk_mode]').val(),
            'id': $('input[name=otpusk_id]').val(),
        };
        schedule_set_data(activeMilitary, activeDay, parseInt($('input[name=otpusk_type]').val()), data, true);
    }

    /**
     *  Удалить отпуск
     */
    function otpuskDelete() {
        ajax(
            'otpusk_delete',
            {
                'year': schedule_year,
                'month': schedule_month,
                'day': activeDay,
                'military': activeMilitary
            },
            function (key, data, respond) {
                data = JSON.parse(respond);
                if (data.errors === false) {
                    if (data.html !== false) {
                        $.each(data.html, function(index, value){
                            ell = getMySelectEll(activeMilitary, index);
                            $(ell).children('.value').remove();
                            $(ell).children('.items').before(value);
                        });
                    }
                } else data.errors;
                window_close();
            },
            'ajax_schedule.php'
        );
    }
    
    /**
     * Открыть окно выбора параметров наряда / прочей службы
    */
    function naryadOpen(data) {
        $(".naryadBl").css({ 'height' : '', 'width' : '' });
        activeMilitary =  data['military'];
        activeDay = data['day'];
        ajax(
            'naryad_change',
            {
                'year': schedule_year,
                'month': schedule_month,
                'unit': schedule_unit,
                'day': data['day'],
                'military': data['military'],
                'type': data['id']
            },
            function (key, data, respond) {
                $('.naryadBl').html(respond);
                window_open();
            },
            'ajax_schedule.php'
        );
    }

    /**
     * Указать параметры наряда / службы через диалоговое окно
     */
    function naryadOk() {
        var data = {
            'type': $('input[name=naryad_type]').val(),
            'from': $('select[name=naryad_from] option:selected').val(),
            'len': $('select[name=naryad_length] option:selected').val(),
            'place': $('input[name=naryad_place]').val(),
            'incoming': $('select[name=naryad_incoming] option:selected').val(),
        };
        schedule_set_data(activeMilitary, activeDay, type_naryad, data);
    }

    /**
     * Открыть окно параметров службы
     */
    function window_open() {
        centerMyBlock($('.naryadBl'));
        shadowNew(99, function () {
            window_close();
        });
        $('.naryadBl').fadeIn();
        $( ".datepicker" ).datepicker({
            changeMonth: true,
            changeYear: true
        });
        $( ".datepicker" ).datepicker( "option", $.datepicker.regional[ "ru" ]);
        scheduleWorkBoxOpened = true;
    }

    /**
     * Закрыть окно параметров службы
     */
    function window_close() {
        shadowRemove(99);
        if (scheduleWorkBoxOpened) {
            $('.naryadBl').fadeOut();
            scheduleWorkBoxOpened = false;
        }
        activeMilitary = null;
        activeDay = null;
        mySelectMultiClear();
        schedulePreactive = true;
    }

    /**
     * Выбор маски наряда из выпадающего списка
     */
    function naryad_mask(military, day, mask) {
        activeMilitary = military;
        activeDay = day;
        ajax(
            'schedule_set_mask',
            {
                'military': military,
                'year': schedule_year,
                'month': schedule_month,
                'day_begin': multiSelectDayBegin,
                'day_end': multiSelectDayEnd,
                'servicetype': type_naryad,
                'mask': mask
            },
            function (key, data, respond) {
                scheduleSetData(respond);
            },
            'ajax_schedule.php'
        );
        select_close_list_all();
    }

    /**
     *  Выделение ячеек по вертикали и горизонтали при наведении на одну из них
     *  ell - дескриптор элемента с классом mySelect
     */
    function scheduleMove(ell) {
        if (!scheduleWorkListOpened && !scheduleContextListOpened) {
            if (schedulePreactiveInterval) {
                var militarys = [$(ell).data('military')];
                var days = [schedulePreactiveIntervalBegin, schedulePreactiveIntervalEnd];
            } else if (multiSelectMilitary) {
                var militarys = [multiSelectMilitary];
                var days = [multiSelectDayBegin, multiSelectDayEnd];
            } else {
                var militarys = [$(ell).data('military')];
                var days = [$(ell).data('day')];
            }
            scheduleMoveClean();
            scheduleMovePaint(militarys, days);
        }
    }

    function scheduleMovePaint(militaries, days) {
        $.each(militaries, function(index, military) {
            $('#military_' + military).addClass('active');
            $('.mil' + military).children('.value').addClass('subHover');
        });
        $.each(days, function(index, day) {
            $('#day_' + day).addClass('active');
            $('.day' + day).children('.value').addClass('subHover');
        });
    }

    /**
     * Убрать выбеделение ячеек графика по вертикали и горизонтали
     */
    function scheduleMoveClean() {
        $('.mySelect').children('.subHover').removeClass('subHover');
        $('.day_title').removeClass('active');
        $('.military_title').removeClass('active');
    }

    /** Вставить ответ сервера в ячейки
     *  data - массив данных от ajax
     */
    function scheduleSetData (data) {
        data = JSON.parse(data);
        $.each(data, function(index, value){
            ell = getMySelectEll(activeMilitary, index);
            $(ell).children('.value').remove();
            $(ell).children('.items').before(value);
        });
        activeMilitary = null;
        activeDay = null;
        mySelectMultiClear();
        schedulePreactive = true;
        window_close();
    }

</script>

<style>
    .bodyBox {
        width: calc(100% - 20px);
        min-width: 1300px;
        margin: 20px auto;
        display: inline-block;
        position: relative;
    }
    .pageTitleLine {
        font-family: RalewayB, sans-serif;
        font-size: 24px;
        text-align: left;
        color: #6f6f6f;
        padding: 0 0 15px 15px;
        cursor: default;
    }
    .tableBodyBox {
        background-color: #ffffffe8;
        display: inline-block;
        position: relative;
        width: 100%;
        padding: 15px;
        box-sizing: border-box;
    }

    .scheduleTable {
        width: 100%;
        margin: 0 auto 20px auto;
        border-collapse: collapse;
    }
    .scheduleTable td {
        border: 1px solid;
    }
    .scheduleTable td.output{
        background-color: #f99790;
    }
    .scheduleTable .title {
        background-color: #e8e8e8;
    }
    .scheduleTable .title.top td{
        padding: 5px;
    }
    .military_title{
        padding: 5px;
        text-align: right;
        cursor: pointer;
        line-height: 19px;
    }
    .day_title{line-height: 30px; cursor: pointer;}
    .scheduleTable .days {
        width: 30px;
        height: 30px;
    }
    .day_title.active,
    .military_title.active {background-color: rgba(0, 0, 0, 0.26) !important;}
    .unit_title{cursor: pointer;}
    /* -------------------- MySelect ---------------------- */
    .mySelect {
        display: inline-block;
        float: left;
        text-align: center;
        position: relative;
        width: 100%;
    }
    .value {
        display: inline-block;
        width: 100%;
        box-sizing: border-box;
        cursor: default;
    }
    .mySelect .value {
        cursor: pointer;
        font-family: monospace;
    }
    .value div {
        padding: 5px;
    }

    .mySelect .value.active {

    }
    .mySelect .value.subHover div {
        background-color: rgba(0, 0, 0, 0.26);
    }

    .mySelect .value.active div,
    .mySelect .value.preactive div {
        background-color: #f99790 !important;
    }

    .mySelect .items,
    .mySelect .context {
        display: none;
        position: absolute;
        background-color: #bbbbbb;
        border-top: 1px solid;
        z-index: 1;
        left: -1px;
        box-shadow: 1px 3px 6px 2px #000000b8;
    }
    .mySelect .items.t,
    .mySelect .context.t {box-shadow: 1px -3px 7px 2px #000000b8;}
    .mySelect .items.l,
    .mySelect .context.l {right: -1px; left: auto;}

    .mySelect .items .itemB,
    .mySelect .context .itemB {
        position: relative;
        display: inline-block;
        width: 100%;
        float: left;
    }
    .mySelect .items .item,
    .mySelect .context .item {
        border: 1px solid #2b2b2b;
        border-top: 0;
        float: left;
        padding: 5px;
        width: 100%;
        box-sizing: border-box;
        text-align: left;
        margin: 0;
        cursor: pointer;
        white-space: nowrap;
        font-family: system-ui;
    }
    .mySelect .items .itemB:hover .item,
    .mySelect .context .item:hover,
    .mySelect .items .naryad_maskItem:hover {
        background-color: #f99790 !important;
    }

    .scheduleTable .button {
        display: inline-block;
        width: 40px;
        height: 22px;
        text-decoration: none;
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
    }
    .scheduleTable .button.next {
        background-image: url("img/next.png");
        float: right;
    }
    .scheduleTable .button.previous {
        background-image: url("img/previous.png");
        float: left;
    }


    .naryadBl {
        position: fixed;
        display: none;
        z-index: 100;
        background-color: #fff;
        padding: 20px;
        min-width: 380px;
    }
    .naryadTitle{
        margin: 0 auto 12px;
        display: inline-block;
        width: 100%;
        color: #525252;
        font-size: 16px;
        cursor: default;
    }
    .naryadDataBl {
        width: 100%;
        text-align: left;
        margin: 6px 0;
        line-height: 31px;
        cursor: default;
        color: #525252;
    }
    .naryadDataBl select {
        width: 50%;
        float: right;
        font-size: 16px;
        padding: 5px;
        cursor: pointer;
    }
    .naryadDataBl .inputTextBox {
         width: 50%;
         float: right;
     }
    .naryadDataBl .inputTextBox input {
        font-size: 16px;
        padding: 5px;
        width: 100%;
        box-sizing: border-box;
    }
    .naryadDataBl .chb {
        margin: 6px 0;
        width: 20px;
        height: 20px;
        cursor: pointer;
        float: right;
    }
    .naryadButton {
        display: inline-block;
        padding: 0 20px 0 30px;
        line-height: 30px;
        border: 1px solid #8a8a8a;
        border-left: 0;
        margin: 10px 0 0;
        float: right;
        cursor: pointer;
    }
    .naryadButton.left {
        float: left;
        border: 1px solid #8a8a8a;
        border-right: 0;
    }
    .naryadBl .hint {
        width: 100%;
        display: inline-block;
        color: #969696;
        font-size: 12px;
    }
    .naryadBl .hint a {
        text-decoration: underline;
        color: #5a5a5a;
    }

    .naryad_maskBl {
        display: none;
        position: absolute;
        left: 100%;
        border: 1px solid #2b2b2b;
        border-bottom: 0;
        z-index: 2;
        top: -1px;
        box-shadow: 2px 5px 5px 0px #000000b8;
    }
    .naryad_maskBl.left {
        left: unset !important;
        right: 100% !important;
        box-shadow: -2px 3px 6px 2px #000000b8 !important;
    }
    .naryad_maskBl .naryad_maskItem {
        display: block;
        border-bottom: 1px solid #2b2b2b;
        cursor: pointer;
        white-space: nowrap;
        text-align: left;
        font-family: system-ui;
        padding: 5px;
    }
    .mySelect .items .itemB:hover .naryad_maskBl {display: block;}

    .scheduleTable_unit_line td {height: 10px;}


    /* ------------------------- DevelopmentBar -----------------------------*/
    .developmentBar {
        position: fixed;
        left: 0;
        z-index: 9999;
        display: none;
        width: 100%;
        bottom: 0;
    }
    .developmentBarOpener {
        margin: 0 auto;
        padding: 5px 20px;
        background-color: #000000f0;
        color: #CCCCCC;
        display: inline-block;
        cursor: pointer;
        border-bottom: 1px solid #fff;
    }
    .developmentBarBody {
        width: 100%;
        padding: 10px 0;
        background-color: #000000db;
        color: #CCCCCC;
        display: none;
        text-align: left;
        float: left;
        position: relative;
    }
    .developmentBarBodyVariables {
        border-right: 2px solid;
        float: left !important;
    }
    .developmentBarBodyVariables,
    .developmentBarBodySettings {
        float: right;
        width: calc(50% - 1px);
        position: relative;
        box-sizing: border-box;
        padding: 0 5px;
    }
    .developmentBarBodyVariables > div,
    .developmentBarBodySettings > div {
        width: calc(50% - 16px);
        display: block;
        float: left;
        background-color: #000000ab;
        margin: 4px;
        border-bottom: 1px solid;
        padding: 4px;
    }
    .developmentBarBody .DBIKey {
        display: inline-block;
        float: left;
        color: #909090;
    }
    .developmentBarBody .DBIValue {
        display: inline-block;
        float: right;
        color: #ffffff;
    }
    .developmentBarBottomBox {
        padding: 5px 5px 0 5px;
        float: left;
        width: 100%;
        display: inline-block;
        box-sizing: border-box;
    }
    .developmentBarBottomBoxButton {
        float: right;
        padding: 5px 20px;
        background-color: #CCCCCC;
        color: #2b2b2b;
        cursor: pointer;
        margin: 2px;
        text-decoration: none;
    }
    .right {float: right;}
    .left{float: left;}
    .changeUnitButton{
        font-size: 14px;
        color: #612f90;
    }
</style>

<div class="bodyBox no_select">
    <div class="pageTitleLine">
        <?php echo $pageData['title'];?>
    </div>

    <div class="tableBodyBox">

        <?php

        $month_days_count = cal_days_in_month(CAL_GREGORIAN, MONTH, YEAR);
        $output_days = System::getMonthOutputDays(YEAR, MONTH);
        $next_month = System::nextMonth(YEAR, MONTH);
        $previous_month = System::previousMonth(YEAR, MONTH);

        // Определение дочених подразделений
        /**
         * @todo Переделать под бесконечное вложение подразделений
         *       Пока что работает только если 1 уровень дорерних либо их вообще нет
         */
        $children_units = Unit::getTree ([UNIT]);
        $children_units = $children_units[UNIT];
        $children_units_list = Unit::convertUnitsTreeAllToList($children_units);
        if (count($children_units_list)) {
            $units = $children_units_list;
            $colspan = 3;
        } else {
            $units = [UNIT];
            $colspan = 2;
        }

        // Загрузка наименований подразделений
        $units_title = [];
        /** @var $STRUCT_DATA - массив данных о подразделениях объявлен в layouts/header.php */
        foreach ($children_units_list as $unit)
            $units_title[$unit] = $STRUCT_DATA[$unit]['title'];

        // Формирование шапки таблицы
        $html = '
                <table class="scheduleTable no_select no_context ">
                    <tr class="title top">
                        <td rowspan="2" colspan="' . $colspan . '">' . Unit::getPathStr(UNIT) . '<br>(<a class="changeUnitButton" href="schedule_select.php?unit=' . UNIT . '">Изменить</a>)</td>
                        <td colspan="' . $month_days_count . '" style="line-height: 22px;">
                            <a class="button previous" href="schedule_edit.php?year=' . $previous_month['year'] . '&mon=' . $previous_month['month'] . '&unit=' . UNIT . '"></a>
                            ' . System::parseDate($schedule->from['unix'], 'M y') . '
                            <a class="button next" href="schedule_edit.php?year=' . $next_month['year'] . '&mon=' . $next_month['month'] . '&unit=' . UNIT . '"></a>
                        </td>
                    </tr>
                    <tr>';

        // Формирование строки дней месяца
        for ($i = 1; $i <= $month_days_count; $i++) {
            $temp = $output_days[$i] ? 'output' : '';
            $html .= '<td class="days ' . $temp . '"><div class="day_title" id="day_' . $i . '" onclick="schedule_visibility(this, 1);" ondblclick="schedule_visibility(this, 2);" data-id="' . $i . '" data-type="day">' . $i . '</div></td>';
        }
        $html .= '</tr>';

        // Формирование служебной нагрузки военнослужащих
        $military_number = 1;   // Счетчик выведенных сток данных (военнослужащих)
        $first_unit = true;     // Переменная для определения места разрыва таблицы между подразделениями
        $serviceload_types = \OsT\Serviceload\Type::getData(
            null ,
            [
                'id',
                'title',
                'color',
                'title_short'
            ]);
        $masks = Mask::getData(
            Mask::getArrayByUser($USER->id),
            [
                'id',
                'title',
                'data',
                'enabled'
            ]);

        foreach ($units as $unit) {
            if (isset($schedule->serviceload_unit[$unit])) {
                // Добавить разрыв таблицы между Подразделениями
                if (!$first_unit)
                    $html .= '<tr class="scheduleTable_unit_line"><td colspan="' . ($month_days_count + 3) . '"></td></tr>';
                else $first_unit = false;

                $html .= '<tr>';
                $html .= (count($children_units_list)) ? '<td class="unit_title" rowspan="' . count($schedule->serviceload_unit[$unit]) . '" onclick="schedule_visibility(this, 1);" ondblclick="schedule_visibility(this, 2);" data-id="' . $unit . '" data-type="unit">' . $units_title[$unit] . '</td>' : '';

                foreach ($schedule->serviceload_unit[$unit] as $military => $serviceload) {
                    $html .= '
                            <td>' . $military_number . '</td>
                            <td class="title">
                                <div class="military_title" id="military_' . $military . '" onclick="schedule_visibility(this, 1);" ondblclick="schedule_visibility(this, 2);" data-id="' . $military . '" data-unit="' . $unit . '" data-type="military">
                                ' . $schedule->military_data[$military]['level_short'] . ' 
                                ' . $schedule->military_data[$military]['fio_short'] . '
                                </div>
                            </td>';

                    for ($day = 1; $day <= $month_days_count; $day++) {
                        if (!isset($serviceload[YEAR][MONTH][$day]))
                            $html .= '<td>' . Schedule::genServiceloadCellHtml_NoData($military, $day) . '</td>';

                        else {
                            $additional_data = @$schedule->serviceload_data[$military][YEAR][MONTH][$day];
                            $html .=  '<td>' . $schedule->genServiceloadCellHtml(
                                    $military,
                                    $day,
                                    $serviceload[YEAR][MONTH][$day],
                                    $serviceload_types,
                                    $masks,
                                    $additional_data) . '</td>';
                        }
                    }

                    $html .= '</tr>';
                    $military_number++;
                }
            }
        }
        $html .= '</table>';

        echo $html;

        ?>

    </div>

    <script>

        <?php
            // Типы нагрузки
//            foreach ($types_arr as $key => $value)
//                if (!in_array($key, [Schedule::TYPE_KOMANDIROVKA, Schedule::TYPE_BOLNICHNUI, Schedule::TYPE_OTPUSK, Schedule::TYPE_VOENNUIGOSPITAL]))
//                    foreach ($value as $attribute => $variable)
//                        if (in_array($attribute, ['short', 'color']))
//                            $types_arr_js[$key][$attribute] = $variable;
//
//            echo System::php2js('types_arr', $types_arr_js);
        ?>

        <?php echo System::php2js('month_days', $month_days_count);?>
    </script>

</div>

<div class="naryadBl"></div>

<div class="shadow" onclick="window_close()"></div>

<div class="context" style="display: none;">
    <div class="item c_edit" onclick="mySelectContextMenuAction('edit');">Изменить</div>
    <div class="item c_continue" onclick="mySelectContextMenuAction('continue');">Продублировать</div>
    <div class="item c_copy" onclick="mySelectContextMenuAction('copy');">Копировать</div>
    <div class="item c_paste" onclick="mySelectContextMenuAction('paste');">Вставить</div>
    <div class="item c_default" onclick="mySelectContextMenuAction('default');">Сбросить</div>
    <div class="item c_delete" onclick="mySelectContextMenuAction('delete');">Удалить</div>
    <div class="item c_later">Скоро...</div>
</div>

<div class="developmentBar" <?php if(isset($_GET['development'])) echo 'style="display: block;"';?>>
    <div class="developmentBarOpener" onclick="$('.developmentBarBody').slideToggle();">Режим разработчика</div>
    <div class="developmentBarBody">
        <div class="developmentBarBodyVariables"></div>
        <div class="developmentBarBodySettings">
            <div><div class="DBIKey">Контекстное меню</div> <div class="DBIValue"><input id="DBSe_show_schedule_context" type="checkbox"></div></div>
            <div><div class="DBIKey">Автообновление переменных</div> <div class="DBIValue"><input id="DBSe_auto_update_variables" type="checkbox"></div></div>
        </div>
        <div class="developmentBarBottomBox">
            <div class="developmentBarBottomBoxButton left" onclick="$('.developmentBarBodyVariables').html('');">Очистить окно переменных</div>
            <div class="developmentBarBottomBoxButton right" onclick="developmentBarApplySettings()">Применить</div>
            <a class="developmentBarBottomBoxButton left" href="<?php echo 'schedule_edit.php?year=' . YEAR . '&mon=' . MONTH . '&unit=' . UNIT . '&remove=1';?>">Обнулить график нагрузки</a>
        </div>
    </div>
    <script>
        var DBInterval = null;
        function developmentBar(key, value) {
            if ($('.developmentBarBodyVariables').has($('.DBI_' + key)).length)
                $('.DBI_' + key).children('.DBIValue').text(value);
            else $('.developmentBarBodyVariables').append('<div class="DBI_' + key + '"><div class="DBIKey">' + key + '</div> <div class="DBIValue">' + value + '</div></div>');
        }
        function developmentBarApplySettings() {
            if ($('#DBSe_show_schedule_context').is(':checked'))
                $('.no_context').removeAttr("oncontextmenu");
            else $('.no_context').attr('oncontextmenu', 'return false;');

            if ($('#DBSe_auto_update_variables').is(':checked')) {
                DBInterval = setInterval(function () {
                    developmentBar('activeMilitary', activeMilitary);
                    developmentBar('activeDay', activeDay);
                    developmentBar('multiSelect', multiSelect);
                    developmentBar('multiSelectMilitary', multiSelectMilitary);
                    developmentBar('multiSelectDayBegin', multiSelectDayBegin);
                    developmentBar('multiSelectDayEnd', multiSelectDayEnd);
                    developmentBar('scheduleWorkListOpened', scheduleWorkListOpened);
                    developmentBar('scheduleContextListOpened', scheduleContextListOpened);
                }, 500);
            } else {
                if (DBInterval !== null)
                    clearInterval(DBInterval);
            }
        }
    </script>
</div>

<?php
    require_once __DIR__ . '/layouts/footer.php';
?>
