<?php
/**
 * const
 * REPORT_KEY   - ключ отчета типа rsn
 * VERSION      - ключ версии типа 1
 * PACKAGE      - идентификатор пакета отчетов либо null если не изменяются настройки пакета
 */

use OsT\Access;
use OsT\Base\System;
use OsT\Reports\Report;

require_once  'layouts/header.php';

    $pageData['title'] = 'Настройки отчета';
    $pagesGroup = ['reports', 'menu'];

    if (!Access::checkAccess('reports_show')) {
        System::location('menu.php');
        exit;
    }

    // Проверка наличия ключевых параметров
    if (isset($_GET['report'])) {
        define('REPORT_KEY', $_GET['report']);
        // Проверка существования отчета по ключу
        $tmp = Report::getReportClass(REPORT_KEY);
        if ($tmp !== null) {
            $tmp = \OsT\Reports\Report::constructClassName(REPORT_KEY);
            $report_class_path = \OsT\Reports\Report::constructClassName(REPORT_KEY);
            // Обработка указателя версии
            if (isset($_GET['version'])) {
                $tmp = $report_class_path::getVersionClassName($_GET['version']);
                if ($tmp !== null) {
                    define('VERSION', $_GET['version']);
                    $version_class_path = \OsT\Reports\Report::constructClassName(REPORT_KEY, VERSION);
                    // Обработка указателя пакета
                    if (isset($_GET['package'])) {
                        //@todo Допилить проверку наличия данного пакета когда появятся пакеты
                        define('PACKAGE', intval($_GET['package']));
                        $R_SETTINGS = [];

                    } else {
                        define('PACKAGE', null);
                        $R_SETTINGS = $version_class_path::getSettings();
                    }

                } else {
                    System::location('menu.php');
                    exit;
                }

            } else {
                // Определение версии по умолчанию и последующее перенаправление
                $version = $report_class_path::getDefaultVersion();
                $package = '';
                if (isset($_GET['package']))
                    $package = '&package=' . $_GET['package'];
                System::location('report_settings.php?report=' . REPORT_KEY . '&version=' . $version . $package);
            }

        } else {
            System::location('menu.php');
            exit;
        }

    } else {
        System::location('menu.php');
        exit;
    }

    if (isset($_POST['submit'])) {
        $version_class_path::saveSettings($_POST);
        System::location($_SERVER [ 'REQUEST_URI' ]);
    }

    require_once  'layouts/head.php';

?>

    <script>

        <?php echo System::php2js('REPORT_KEY', REPORT_KEY);?>
        <?php echo System::php2js('VERSION', VERSION);?>

        $(document).ready(function () {
            $('#masksender2_optionsbox select').change(function (e) {
                masksender2_update_select_list(e);
            });

            $('#masksender3_optionsbox select').change(function (e) {
                masksender3_update_select_list(e);
            });
        });

        /**
         * Обновить выпадающий список masksender2 при изменении
         */
        function masksender2_update_select_list (e) {
            index = get_index_after_prefix($(e.target).attr('name'), 'sender2_unit_');
            if (index !== null) {
                selected_unit = parseInt($(e.target).children('option:selected').val());
                if (selected_unit === -1) {
                    select_delete_after_name_index($(e.target), 'sender2_unit_');
                    $('select[name=sender2_military]').remove();

                } else {
                    ajax(
                        'report_settings_sender2_unit_change',
                        {
                            'selected': selected_unit
                        },
                        function (key, data, respond) {
                            $('#masksender2_optionsbox').html(respond);
                            $('#masksender2_optionsbox select').change(function (e) {
                                masksender2_update_select_list(e);
                            });
                        }
                    );
                }
            }
        }

        /**
         * Обновить выпадающий список masksender3 при изменении
         */
        function masksender3_update_select_list (e) {
            index = get_index_after_prefix($(e.target).attr('name'), 'sender3_unit_');
            if (index !== null) {
                selected_unit = parseInt($(e.target).children('option:selected').val());
                if (selected_unit === -1) {
                    select_delete_after_name_index($(e.target), 'sender3_unit_');
                    $('select[name=sender3_state]').remove();

                } else {
                    ajax(
                        'report_settings_sender3_unit_change',
                        {
                            'selected': selected_unit
                        },
                        function (key, data, respond) {
                            $('#masksender3_optionsbox .masksender3_state_box').html(respond);
                            $('#masksender3_optionsbox select').change(function (e) {
                                masksender3_update_select_list(e);
                            });
                        }
                    );
                }
            }
        }

        /**
         * Управление отображением параметров шапки при смене типа шапки
         * @param radio
         */
        function mask_head_change(radio) {
            if ($(radio).is(':checked')) {
                $('.maskhead_optionsbox').hide();
                $(radio).siblings('.maskhead_optionsbox').show();
            }
        }

        /**
         * Управление отображением параметров шапки при смене типа шапки
         * @param radio
         */
        function mask_sender_change(radio) {
            if ($(radio).is(':checked')) {
                $('.masksender_optionsbox').hide();
                $(radio).siblings('.masksender_optionsbox').show();
            }
        }

        /**
         * Сменить версию отчета при нажатии на кнопку "Сменить"
         */
        function select_version_change () {
            v = $('select[name=select_version_change]').children('option:selected').val();
            if (v !== VERSION)
                document.location.href = 'report_settings.php?report=' + REPORT_KEY + '&version=' + v;
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

        .formSubmitButton {
            float: right;
            display: inline-block;
            margin: 20px 20px 0 0;
            background-color: white;
            padding: 5px 25px;
            border: 1px solid;
            font-family: 'Frank', "Franklin Gothic Medium", serif;
        }

        .maskhead_radiobox,
        .masksender_radiobox {
            display: block;
            float: left;
            padding: 5px;
            width: 100%;
            text-align: left;
            box-sizing: border-box;
            position: relative;
        }
        .maskhead_optionsbox,
        .masksender_optionsbox {
            display: none;
            padding: 5px 5px 5px 15px;
            position: relative;
        }
        .masksender_input_text_box {
            width: 100%;
            display: inline-block;
            float: left;
            margin: 3px 0;
        }
        .masksender_input_text_title {
            display: inline-block;
            width: 10%;
            min-width: 100px;
            float: left;
            line-height: 28px;
            font-size: 14px;
        }
        .masksender_input_text {
            border: 1px solid;
            padding: 5px;
            width: 90%;
            box-sizing: border-box;
            float: left;
        }
        .maskhead_optionsbox .maskhead1_text {
            width: 100%;
            border: 1px solid;
            max-width: 100%;
            min-width: 100%;
            min-height: 30px;
            max-height: 100px;
            padding: 5px;
            box-sizing: border-box;
        }
        /*.masksender_unit_select_box {*/
        /*    display: inline-block;*/
        /*    width: 100%;*/
        /*}*/
        /*.masksender_unit_select_box_title {*/
        /*    display: inline-block;*/
        /*    width: 15%;*/
        /*    min-width: 100px;*/
        /*    float: left;*/
        /*    line-height: 28px;*/
        /*    font-size: 14px;*/
        /*}*/
        /*.masksender_unit_select_box_body {*/
        /*    display: inline-block;*/
        /*    width: 85%;*/
        /*}*/
        #masksender2_optionsbox select,
        #masksender3_optionsbox select {
            width: 100%;
            border: 1px solid;
            padding: 5px;
            box-sizing: border-box;
            margin-bottom: 10px;
            cursor: pointer;
        }

        .select_version_box {
            padding: 15px 20px 0;
            display: inline-block;
            width: 100%;
            box-sizing: border-box;
            float: left;
            position: relative;
        }
        .select_version_box select {
            border: 1px solid;
            padding: 5px;
            width: calc(100% - 100px);
            box-sizing: border-box;
            float: left;
            cursor: pointer;
        }
        .select_change_button {
            width: 90px;
            float: right;
            display: inline-block;
            background-color: white;
            padding: 5px 0;
            border: 1px solid;
            font-family: 'Frank', "Franklin Gothic Medium", serif;
            box-sizing: border-box;
            cursor: pointer;
            font-size: 14px;
        }

        .full_textarea {
            width: 100%;
            max-width: 100%;
            min-width: 100%;
            max-height: 300px;
            min-height: 40px;
            height: 40px;
            padding: 5px;
            border: 1px solid;
            box-sizing: border-box;
            display: inline-block;
            float: left;
        }

    </style>

    <div class="bodyBox">
        <div class="pageTitleLine">
            <?php echo $pageData['title']?>
        </div>

        <div class="dataBodyBox">

            <div class="select_version_box">
                <?php

                // Select выбора версии
                $available_versions = $report_class_path::getVersionsKeys();
                $arr = [];
                foreach ($available_versions as $item) {
                    $item = strval($item);
                    $tmp = [
                        'id' => $item,
                        'title' => $report_class_path::REPORT_TITLE . ', версия ' . $item
                    ];
                    if ($item === VERSION) {
                        $tmp['title'] .= ' (текущая)';
                        $tmp['selected'] = true;
                    }
                    $arr[] = $tmp;
                }
                echo System::getHtmlSelect($arr, 'select_version_change');

                ?>
                <div class="select_change_button" onclick="select_version_change()">
                    Сменить
                </div>
            </div>

            <form class="form" action="<?php echo $_SERVER [ 'REQUEST_URI' ] ?>" method="post" enctype="multipart/form-data">

                <div class="formFullBox maskhead">
                    <div class="formBoxTitle">Шапка</div>
                    <div class="formBoxBody">
                        <?php
                        $available_maskhead = $version_class_path::getHeadMaskIndexes();

                        $index = 0; //maskheadindex
                        if (in_array($index, $available_maskhead)) {
                            $checked = $R_SETTINGS['head'] === $index ? 'checked' : '';
                            echo '<div class="maskhead_radiobox">
                                <input type="radio" id="maskhead' . $index . '" name="head" value="' . $index . '" ' . $checked . ' onchange="mask_head_change(this)">
                                <label for="maskhead' . $index . '">Нет</label>
                            </div>';
                        }

                        $index = 1;
                        if (in_array($index, $available_maskhead)) {
                            $text = $R_SETTINGS['head_data'][$index]['text'];
                            $text = str_replace('&', '&amp;', $text);
                            $checked = $R_SETTINGS['head'] === $index ? 'checked' : '';
                            $opened = $R_SETTINGS['head'] === $index ? 'style="display:block"' : '';
                            echo '<div class="maskhead_radiobox">
                                <input type="radio" id="maskhead' . $index . '" name="head" value="' . $index . '" ' . $checked . ' onchange="mask_head_change(this)">
                                <label for="maskhead' . $index . '">Текст</label>
                                <div class="maskhead_optionsbox" id="maskhead' . $index . '_optionsbox" ' . $opened . '>
                                    <textarea name="head' . $index . '_text" class="maskhead' . $index . '_text">' . $text . '</textarea>
                                </div>
                            </div>';
                        }
                        ?>

                    </div>
                </div>

                <?php

                echo $version_class_path::getHtmlSettingsForm();

                ?>


                <div class="formFullBox sender">
                    <div class="formBoxTitle">Отправитель</div>
                    <div class="formBoxBody">
                        <?php
                        $masksender_current_index = $R_SETTINGS['sender_mask'];     // Индекс текущего шаблона разметки отправителя, в дальнейшем поправить, проверить

                        $available_masksender = $version_class_path::getSenderSourceIndexes();
                        $available_masksender = $available_masksender[$masksender_current_index];

                        if ($available_masksender !== null) {

                            $index = 0; //sendersourceindex
                            if (in_array($index, $available_masksender)) {
                                $checked = $R_SETTINGS['sender_source'] === $index ? 'checked' : '';
                                echo '<div class="masksender_radiobox">
                                    <input type="radio" id="masksender' . $index . '" name="sender" value="' . $index . '" ' . $checked . ' onchange="mask_sender_change(this)">
                                    <label for="masksender' . $index . '">Нет</label>
                                </div>';
                            }

                            $index = 1;
                            if (in_array($index, $available_masksender)) {
                                $checked = $R_SETTINGS['sender_source'] === $index ? 'checked' : '';
                                $opened = $R_SETTINGS['sender_source'] === $index ? 'style="display:block"' : '';
                                echo '<div class="masksender_radiobox">
                                    <input type="radio" id="masksender' . $index . '" name="sender" value="' . $index . '" ' . $checked . ' onchange="mask_sender_change(this)">
                                    <label for="masksender' . $index . '">Текст</label>
                                    <div class="masksender_optionsbox" id="masksender' . $index . '_optionsbox" ' . $opened . '>
                                        <div class="masksender_input_text_box">
                                            <div class="masksender_input_text_title">Должность</div>
                                            <input name="sender' . $index . '_state" class="masksender_input_text" value="' . $R_SETTINGS['sender_data'][$masksender_current_index][$index]['state'] . '">
                                        </div>
                                        <div class="masksender_input_text_box">
                                            <div class="masksender_input_text_title">Звание</div>
                                            <input name="sender' . $index . '_level" class="masksender_input_text" value="' . $R_SETTINGS['sender_data'][$masksender_current_index][$index]['level'] . '">
                                        </div>
                                        <div class="masksender_input_text_box">
                                            <div class="masksender_input_text_title">ФИО</div>
                                            <input name="sender' . $index . '_fio" class="masksender_input_text" value="' . $R_SETTINGS['sender_data'][$masksender_current_index][$index]['fio'] . '">
                                        </div>
                                    </div>
                                </div>';
                            }

                            $index = 2;
                            if (in_array($index, $available_masksender)) {
                                $checked = $R_SETTINGS['sender_source'] === $index ? 'checked' : '';
                                $opened = $R_SETTINGS['sender_source'] === $index ? 'style="display:block"' : '';

                                if (isset($R_SETTINGS['sender_data'][$masksender_current_index][$index]['military'])) {
                                    $military = $R_SETTINGS['sender_data'][$masksender_current_index][$index]['military'];
                                    $tmp = \OsT\Military\Military::getData([$military], ['id']);
                                    if (!count($tmp))
                                        $military = null;
                                } else $military = null;

                                $tree = [0 => $STRUCT_TREE];
                                $html = \OsT\Military\State::getSelectUnitMilitaryHtml(
                                    $tree,
                                    0,
                                    $military,
                                    'sender' . $index . '_unit_',
                                    'sender' . $index . '_military'
                                );

                                echo '<div class="masksender_radiobox">
                                    <input type="radio" id="masksender' . $index . '" name="sender" value="' . $index . '" ' . $checked . ' onchange="mask_sender_change(this)">
                                    <label for="masksender' . $index . '">Военнослужащий</label>
                                    <div class="masksender_optionsbox" id="masksender' . $index . '_optionsbox" ' . $opened . '>
                                         ' . $html . '
                                    </div>
                                </div>';
                            }

                            $index = 3;
                            if (in_array($index, $available_masksender)) {
                                $checked = $R_SETTINGS['sender_source'] === $index ? 'checked' : '';
                                $opened = $R_SETTINGS['sender_source'] === $index ? 'style="display:block"' : '';

                                $tree = [0 => $STRUCT_TREE];
                                $html = \OsT\State::getSelectUnitStateHtml(
                                    $tree,
                                    0,
                                    $R_SETTINGS['sender_data'][$masksender_current_index][$index]['state'],
                                    'sender' . $index . '_unit_',
                                    'sender' . $index . '_state'
                                );

                                $vrio_checked = $R_SETTINGS['sender_data'][$masksender_current_index][$index]['vrio'] ? 'checked' : '';

                                echo '<div class="masksender_radiobox">
                                    <input type="radio" id="masksender' . $index . '" name="sender" value="' . $index . '" ' . $checked . ' onchange="mask_sender_change(this)">
                                    <label for="masksender' . $index . '">Военнослужащий на должности</label>
                                    <div class="masksender_optionsbox" id="masksender' . $index . '_optionsbox" ' . $opened . '>
                                         <div class="masksender' . $index . '_state_box">
                                            ' . $html . '
                                         </div>
                                         <input type="checkbox" name="sender' . $index . '_vrio" id="sender' . $index . '_vrio" ' . $vrio_checked . '>
                                         <label for="sender' . $index . '_vrio">Учитывать временно исполняющего обязанности</label>
                                    </div>
                                </div>';
                            }

                        }

                        ?>

                    </div>
                </div>

                <input name="submit" class="formSubmitButton" type="submit" value="Готово">

            </form>

        </div>

    </div>

<?php
    require_once 'layouts/footer.php';
?>
