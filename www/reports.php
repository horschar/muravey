<?php
    require_once  __DIR__ . '/layouts/header.php';

$pageData['title'] = 'Отчеты';
$pagesGroup = ['reports', 'menu'];

use OsT\Access;
use OsT\Base\Message;
use OsT\Base\System;
use OsT\Reports\Package;

if (!Access::checkAccess('reports_show')) {
        new Message('Недостаточно прав для просмотра данной страницы', Message::TYPE_ACCESS);
        System::location('index.php');
        exit;
    }

    require_once  __DIR__ . '/layouts/head.php';
?>

<script src="js/jquery-ui/jquery-ui.js"></script>
<script src="js/jquery-ui/datepicker-ru.js"></script>
<link rel="stylesheet" href="js/jquery-ui/jquery-ui.min.css">

<script>
    <?php echo System::php2js('user', $USER->id);?>

    $(document).ready(function () {
        on_functions_datepicker();
    });

    /**
     * Активировать функции обьетов всех datepicker
     */
    function on_functions_datepicker() {
        $( ".datepicker" ).datepicker({
            changeMonth: true,
            changeYear: true
        });
        $( ".datepicker" ).datepicker( "option", $.datepicker.regional[ "ru" ]);
    }

    /**
     * Сформировать окно выбора параметров отчета
     */
    function printQueue_addReport(report) {
        ajax(
            'report_settingsbox_generate',
            {
                'report': report
            },
            function (key, data, respond) {
                $('body').append(respond);
                on_functions_datepicker();
                centerMyBlock($('.headLinkButtonsWindow.' + data['report']));
                $('.headLinkButtonsWindow.' + data['report']).show();
                shadowNew(99, function () {
                    window_close();
                });
            }
        );
    }

    /**
     * Закрыть окно выбора параметров отчета
     */
    function window_close() {
        $('.shadow_window').fadeOut();
        shadowRemove(99);
        $('.headLinkButtonsWindow').remove();
    }

    /**
     * Управлять активностью элементов (enabled / disabled) при помощи checkbox
     * @param ell - дескриптор объекта checkbox
     * @param affectedEll - дескриптор зависимого объекта (input, select, ...)
     */
    function pdf_settings_chechbox_affect(ell, affectedEll) {
        if ($(ell).is(':checked'))
            $(affectedEll).removeAttr('disabled');
        else $(affectedEll).attr('disabled', 'disabled');
    }

    /**
     * Действие при нажатии на кнопку 'больше' в параметрах отчета
     */
    function pdf_settings_show_more() {
        $('.pdf_settings_show_more_button').remove('');
        $('.pdf_settings_show_more_body').removeClass('hidden');
        $('.headLinkButtonsWindow').css('height', '');
        $('.headLinkButtonsWindow').css('width', '');
        centerMyBlock($('.headLinkButtonsWindow'), 20, 20);
    }

    /**
     * Обновить списки подразделений
     */
    function update_units_select(select, key) {
        $(".headLinkButtonsWindow").css({ 'height' : '', 'width' : '' });
        unit = parseInt($(select).children('option:selected').val());
        name = $(select).attr('name');
        if (unit === -1) {
            select_delete_after_name_index (select, key + '_unit');
            centerMyBlock($('.headLinkButtonsWindow'));
        } else {
            ajax(
                'reports_reload_units_list',
                {
                    'name': name,
                    'unit': unit,
                    'key': key
                },
                function (key, data, respond) {
                    parent = $('select[name=' + data['name'] + ']').parent();
                    $(parent).html(respond);
                    centerMyBlock($('.headLinkButtonsWindow'));
                }
            );
        }
    }

    /**
     * Сгенерировать HTML представление блока настойки вывода отчета при смене версии отчета
     */
    function report_settingsbox_version_change (select, report_key) {
        version = $(select).children('option:selected').val();
        ajax(
            'report_settingsbox_version_change',
            {
                'report': report_key,
                'version': version,
            },
            function (key, data, respond) {
                window_close();
                $('body').append(respond);
                on_functions_datepicker();
                centerMyBlock($('.headLinkButtonsWindow.' + data['report']));
                $('.headLinkButtonsWindow.' + data['report']).show();
                shadowNew(99, function () {
                    window_close();
                });
            }
        );
    }

    /**
     * Добавить отчет в очередь печати
     * @param report - ключ отчета по типу 'rsn'
     */
    function printQueue_addReport_ok (report) {
        data = form_get_data_all('.headLinkButtonsWindow');
        data['report'] = report;
        ajax(
            'report_add_to_print_list',
            data,
            function (key, data, respond) {
                if ($.trim(respond) !== '') {
                    $('.printTable').append(respond);
                    window_close();
                    $('.prePrint').show();
                }
            }
        );
    }

    /**
     * Удалить отчет из очереди печати
     * @param ell - дескриптор кнопки удаления
     */
    function printQueue_deleteReport(ell) {
        $(ell).parent().parent().remove();
        if (printQueue_count() === 0)
            $('.prePrint').hide();
    }

    /**
     * Удалить все отчеты из очереди печати
     */
    function printQueue_deleteAll () {
        $('.printTable').find('tr').each(function () {
            if(!(typeof $(this).attr('data-key') === "undefined")){
                $(this).remove();
            }
        });
        $('.prePrint').hide();
    }

    /**
     * Узнать количество отчетов в очереди печати
     */
    function printQueue_count () {
        count = 0;
        $('.printTable').find('tr').each(function () {
            if(!(typeof $(this).attr('data-key') === "undefined")){
                count++;
            }
        });
        return count;
    }

    var pdf_list = [];

    /**
     *  Собрать данные из очереди печати в массив pdf_list
     */
    function calc_pdf_list () {
        pdf_list = [];
        $('.printTable tr').each(function () {
            if ($(this).data('key') !== undefined)
                pdf_list.push($(this).data());
        });
    }

    /**
     * Визуализация очереди печати
     */
    function show_pdf() {
        calc_pdf_list();
        ajax(
            'reports_show_pdf',
            pdf_list,
            function (key, data, respond) {
                $('.printPDF').children('.boxBody').html(respond);
                $('.printPDF').show();
            }
        );
    }

    /**
     * Скрыть PDF
     */
    function pdf_close() {
        $('.printPDF').hide();
        if ($('.printPDF').hasClass('fullscreen'))
            $('.printPDF').removeClass('fullscreen');
    }

    /**
     * Сделать блок PDF во весь экран
     */
    function pdf_fullscreen() {
        if ($('.printPDF').hasClass('fullscreen'))
            $('.printPDF').removeClass('fullscreen');
        else {
            used_height = $('.printPDF .boxTitle').outerHeight();
            window_height = $(window).height();
            new_height = window_height - used_height;
            $('.printPDF .boxBody iframe').css('height', new_height + 'px');
            $('.printPDF').addClass('fullscreen');
        }
    }

    /**
     *  Закрыть окно управления пакетом
     */
    function packageWindowClose () {
        $('.shadow_window').fadeOut();
        shadowRemove(99);
        $('.packageWindow').hide();
    }

    /**
     * Отобразить окно параметров для создания пакета
     */
    function packageShowCreateWindow () {
        centerMyBlock($('.packageWindow.packageCreate'));
        $('.packageWindow.packageCreate').show();
        shadowNew(99, function () {
            packageWindowClose();
            $('input[name=package_title]').val('');
        });
    }

    /**
     * Создать пакет отчетов
     */
    function packageCreate () {
        title = $('input[name=package_title]').val();
        title = $.trim(title);
        if (title !== '') {
            calc_pdf_list();
            ajax(
                'package_create',
                {
                    'reports': pdf_list,
                    'title' : title
                },
                function (key, data, respond) {
                    $('.packages').append(respond);
                    packageCheckNoData();
                    packageWindowClose();
                    $('input[name=package_title]').val('');
                }
            );
        }
    }

    /**
     * Отобразить окно параметров для добавления пакета в очередь печати
     */
    function packageShowPrintWindow (id) {
        $('input[name=package_print_id]').val(id);
        centerMyBlock($('.packageWindow.packagePrint'));
        $('.packageWindow.packagePrint').show();
        shadowNew(99, function () {
            packageWindowClose();
            $('input[name=package_date]').val('');
        });
    }

    /**
     * Добавить пакет в очередь печати
     */
    function printQueue_addPackage () {
        id = $('input[name=package_print_id]').val();
        date = $('input[name=package_date]').val();
        date = $.trim(date);
        if (date !== '') {
            ajax(
                'package_add_to_print_list',
                {
                    'id': id,
                    'date': date,
                },
                function (key, data, respond) {
                    $('.printTable').append(respond);
                    packageWindowClose();
                    $('input[name=package_date]').val('');
                    $('.prePrint').show();
                }
            );
        }
    }

    /**
     * Удалить пакет отчетов
     */
    function packageDelete (id) {
        ajax(
            'package_delete',
            {
                'id': id
            },
            function (key, data, respond) {
                $('#package_table_item_' + id).remove();
                packageCheckNoData();
            }
        );
    }

    /**
     * Проверить наличие данных в блоке пакетов отчетов
     * Скрыть / отобразить заглушку при необходимости
     */
    function packageCheckNoData () {
        count = $('.package_table_item').length;
        if (count === 0)
            $('.packages_no_data').show();
        else $('.packages_no_data').hide();
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

    .dataBodyBox {
        background-color: #ffffffe8;
        display: inline-block;
        position: relative;
        width: 100%;
        padding: 20px;
        box-sizing: border-box;
    }

    .box {
        width: 100%;
        float: left;
        margin: 0 0 10px;
    }
    .box.printPDF {
        display: none;
    }
    .box2 {
        width: calc(50% - 5px);
        float: left;
        margin-left: 10px;
    }
    .box2:first-child {
        margin-left: 0;
    }

    .box .boxTitle,
    .box2 .boxTitle {
        height: 44px;
        color: #0f0f0f;
        background-color: #f3f3f3;
        font-size: 16px;
        font-family: Frank, "Franklin Gothic Medium", sans-serif;
        text-align: center;
        cursor: default;
        line-height: 44px;
    }
    .boxBody {
        border: 1px solid #f3f3f3;
        padding: 5px;
    }
    .reports,
    .packages {
        border-collapse: collapse;
        width: 100%;
    }
    .reports td,
    .packages td {
        border: 1px solid #b1b1b1;
        padding: 4px;
        line-height: 26px;
    }
    .reportTitle {
        text-align: left;
        padding-left: 10px !important;
        border-right: 0 !important;
        color: #606060;
        font-family: system-ui;
        font-size: 14px;
    }
    .reportButtons {border-left: 0 !important;}
    .reportButtons .button {
        display: inline-block;
        width: 36px;
        height: 36px;
        background-size: 36px 108px;
        background-position: 0 -36px;
        background-repeat: no-repeat;
        margin: 1px 0 1px 6px;
        cursor: pointer;
        float: right;
    }
    .reportButtons .button:hover {background-position: 0 0;}
    .reportButtons .button.active {background-position: 0 -72px;}
    .reportButtons .button.print {background-image: url("img/table_buttons/pring.png");}
    .reportButtons .button.settings {background-image: url("img/table_buttons/settings.png");}
    .reportButtons .button.delete {background-image: url("img/table_buttons/delete.png");}
    .reportButtons .button.save {background-image: url("img/table_buttons/save.png");}
    .reportButtons .button.resize {background-image: url("img/table_buttons/resize.png");}

    .printPDF iframe {
        width: 100%;
        height: 400px;
        margin: 0 auto; padding: 0; border: 0;
    }
    .printPDF .boxBody {
        padding: 0;
    }
    .printPDF.fullscreen{
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
    }
    .pdf_settings_show_more_button {
        cursor: pointer;
        color: #8c8c8c;
        line-height: 26px;
    }

    .prePrint {
        display: none;
    }

    .prePrintButtonsBox {
        display: inline-block;
        float: right;
        padding: 4px 4px 0 0;
    }

    .printTable td {
        color: #606060;
        font-family: system-ui;
        font-size: 14px;
    }
</style>

<div class="bodyBox">
    <div class="pageTitleLine">
        <?php echo $pageData['title']?>
    </div>

    <div class="dataBodyBox">

        <div class="box">
            <div class="box2">
                <div class="boxTitle">Все отчеты</div>
                <div class="boxBody">
                    <table class="reports">
                        <tr>
                            <td class="reportTitle"><?php echo \OsT\Reports\RSN\RSN::REPORT_TITLE;?></td>
                            <td class="reportButtons">
                                <a class="button settings" title="Настройки отчета" target="_blank" href="report_settings.php?report=<?php echo \OsT\Reports\RSN\RSN::REPORT_KEY;?>"></a>
                                <div class="button print" title="Добавить в очередь печати" onclick="printQueue_addReport('<?php echo \OsT\Reports\RSN\RSN::REPORT_KEY;?>')"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="reportTitle"><?php echo \OsT\Reports\RNO\RNO::REPORT_TITLE;?></td>
                            <td class="reportButtons">
                                <a class="button settings" title="Настройки отчета" target="_blank" href="report_settings.php?report=<?php echo \OsT\Reports\RNO\RNO::REPORT_KEY;?>"></a>
                                <div class="button print" title="Добавить в очередь печати" onclick="printQueue_addReport('<?php echo \OsT\Reports\RNO\RNO::REPORT_KEY;?>')"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="reportTitle"><?php echo \OsT\Reports\RNK\RNK::REPORT_TITLE;?></td>
                            <td class="reportButtons">
                                <a class="button settings" title="Настройки отчета" target="_blank" href="report_settings.php?report=<?php echo \OsT\Reports\RNK\RNK::REPORT_KEY;?>"></a>
                                <div class="button print" title="Добавить в очередь печати" onclick="printQueue_addReport('<?php echo \OsT\Reports\RNK\RNK::REPORT_KEY;?>')"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="reportTitle"><?php echo \OsT\Reports\RSZ\RSZ::REPORT_TITLE;?></td>
                            <td class="reportButtons">
                                <a class="button settings" title="Настройки отчета" target="_blank" href="report_settings.php?report=<?php echo \OsT\Reports\RSZ\RSZ::REPORT_KEY;?>"></a>
                                <div class="button print" title="Добавить в очередь печати" onclick="printQueue_addReport('<?php echo \OsT\Reports\RSZ\RSZ::REPORT_KEY;?>')"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="reportTitle"><?php echo \OsT\Reports\SOV\SOV::REPORT_TITLE;?></td>
                            <td class="reportButtons">
                                <a class="button settings" title="Настройки отчета" target="_blank" href="report_settings.php?report=<?php echo \OsT\Reports\SOV\SOV::REPORT_KEY;?>"></a>
                                <div class="button print" title="Добавить в очередь печати" onclick="printQueue_addReport('<?php echo \OsT\Reports\SOV\SOV::REPORT_KEY;?>')"></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="box2">
                <div class="boxTitle">Пакеты отчетов</div>
                <div class="boxBody">
                    <table class="packages">
                        <?php
                            $packages_no_data_style = 'style="display:none;"';
                            $packages = Package::getArrayByUser($USER->id);
                            if (count($packages)) {
                                $packages = Package::getData($packages, ['id', 'title']);
                                foreach ($packages as $package)
                                    echo Package::getHtmlTableItem($package['id'], $package['title']);
                            } else $packages_no_data_style = '';
                        ?>
                        <tr class="packages_no_data" <?php echo $packages_no_data_style;?>>
                            <td class="reportTitle" style="height: 39px; border-right: 1px solid #b1b1b1 !important;">Экономьте время. Объединяйте отчеты из очереди печати в пакеты отчетов</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="box prePrint">
            <div class="boxTitle">
                Очередь печати
                <div class="reportButtons prePrintButtonsBox">
                    <div class="button delete" title="Очистить очередь печати" onclick="printQueue_deleteAll()"></div>
                    <div class="button save" title="Сохранить в пакет отчетов" onclick="packageShowCreateWindow()"></div>
                    <div class="button print" title="Сформировать PDF" onclick="show_pdf();"></div>
                </div>
            </div>
            <div class="boxBody">
                <table class="reports printTable">
                    <tr>
                        <td>Название</td>
                        <td>Версия</td>
                        <td>Подразделение</td>
                        <td>Дата</td>
                        <td>Создан</td>
                        <td></td>
                    </tr>
                </table>
            </div>
        </div>


        <div class="box printPDF">
            <div class="boxTitle">
                Версия для печати
                <div class="reportButtons prePrintButtonsBox">
                    <div class="button delete" title="Закрыть" onclick="pdf_close()"></div>
                    <div class="button resize" title="Развернуть / Свернуть" onclick="pdf_fullscreen();"></div>
                </div>
            </div>
            <div class="boxBody"></div>
        </div>
    </div>
</div>


<div class="packageWindow shadow_window packageCreate">
    <div class="pdf_settings_title">Параметры</div>
    <div class="pdf_settings_more_item">
        <div class="pdf_settings_more_item_title">Название</div>
        <div class="pdf_settings_more_item_value">
            <input class="text" name="package_title" value="">
        </div>
    </div>

    <div class="button" onclick="packageCreate()">Готово</div>
</div>

<div class="packageWindow shadow_window packagePrint">
    <div class="pdf_settings_title">Параметры</div>
    <div class="pdf_settings_more_item">
        <div class="pdf_settings_more_item_title">Дата</div>
        <div class="pdf_settings_more_item_value">
            <input class="datepicker text" name="package_date" value="" autocomplete="off">
        </div>
    </div>

    <input name="package_print_id" value="" type="hidden">

    <div class="button" onclick="printQueue_addPackage()">Готово</div>
</div>

<style>
    .headLinkButtonsWindow,
    .packageWindow {
        position: fixed;
        display: none;
        left: 0;
        top: 0;
        z-index: 100;
        background-color: #fff;
        padding: 10px 20px;
        width: 400px;
    }
    .headLinkButtonsWindow.middle {
        width: 700px;
    }
    .headLinkButtonsWindow select,
    .headLinkButtonsWindow .text,
    .packageWindow .text
    {
        display: block;
        width: 100%;
        font-family: 'Frank', "Franklin Gothic Medium", serif;;
        font-size: 16px;
        border: 1px solid;
        padding: 5px;
        box-sizing: border-box;
    }
    .headLinkButtonsWindow select {
        cursor: pointer;
        margin-bottom: 10px;
    }
    .headLinkButtonsWindow select:last-child {
        margin: 0;
    }
    .headLinkButtonsWindow .button,
    .packageWindow .button
    {
        border: 2px solid;
        padding: 5px 20px;
        font-size: 16px;
        font-family: 'Frank', "Franklin Gothic Medium", serif;
        display: inline-block;
        cursor: pointer;
        width: 100%;
        box-sizing: border-box;
        margin: 5px auto 0;
    }

    .pdf_settings_title {
        padding: 6px 0 10px 0;
        font-size: 18px;
        color: #6d6d6d;
        cursor: default;
    }
    .pdf_settings_more_item {
        padding: 8px 0 0;
        display: inline-block;
        width: 100%;
    }
    .pdf_settings_more_item_title {
        float: left;
        width: 50%;
        text-align: left;
        font-size: 15px;
        line-height: 32px;
        cursor: default;
    }
    .headLinkButtonsWindow.middle .pdf_settings_more_item_title {
        width: 30%;
    }
    .pdf_settings_more_item_value {
        float: left;
        width: 50%;
    }
    .headLinkButtonsWindow.middle .pdf_settings_more_item_value {
        width: 70%;
    }
    .pdf_settings_checkbox {
        float: right;
        margin: 6px;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .pdf_settings_more_item_value textarea {
        width: 100%;
        border: 1px solid;
        box-sizing: border-box;
        padding: 5px;
        max-width: 100%;
        min-width: 100%;
        min-height: 120px;
        max-height: 120px;
    }
</style>

<?php
//echo \OsT\Reports\RSN\RSN::getHtmlReportAddPrintSettingsBox();
/*
    $USER->settings['report_rsn_defaultunit'] = 21;
    $USER->settings['report_rno_defaultunit'] = 21;
    $USER->settings['report_rnk_defaultunit'] = 21;
    echo \OsT\Reports\RSN\RSN::getHtmlReportAddPrintSettingsBox();
    echo \OsT\Reports\RNO\RNO::getHtmlReportAddPrintSettingsBox();
    echo \OsT\Reports\RNK\RNK::getHtmlReportAddPrintSettingsBox();
    echo \OsT\Reports\RSZ\RSZ::getHtmlReportAddPrintSettingsBox();
    echo \OsT\Reports\SOV\SOV::getHtmlReportAddPrintSettingsBox();
*/
?>

<div class="shadow" onclick="window_close()"></div>

<?php
    require_once __DIR__ . '/layouts/footer.php';
?>
