<?php

use OsT\Access;

require_once  'layouts/header.php';

    $pageData['title'] = 'Военнослужащие';
    $pagesGroup = ['military', 'menu'];

    if (!Access::checkAccess('militaries_show')) {
        \OsT\Base\System::location('menu.php');
        exit;
    }

    if (isset($_GET['delete'])) {
        $military = intval($_GET['delete']);
        \OsT\Military\Military::_deleteWithDependencies($military);
        \OsT\Base\System::location('militaries.php');
        exit;
    }

    require_once  'layouts/head.php';

?>
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/questionbox.css">
    <script src="js/questionBox.js"></script>

    <script>
        $(document).ready(function () {
            $(document).mousedown(function (e) {
                if (!is_or_has($('.button_list'), e.target)) {
                    button_more_close_list();
                }
            });
        });

        function button_more_show_list (button) {
            $(button).children('.button_list').show();
        }

        function button_more_close_list () {
            $('.button_list').hide();
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

        .tableBodyBox {
            background-color: #ffffffe8;
            padding: 20px;
            display: inline-block;
            position: relative;
            width: 100%;
        }
        .tableButtonsLine {
            padding: 0 0 10px 0;
            display: inline-block;
            width: 100%;
        }
        .tableButtonsLine .slideButton.filter {
            margin: 0;
            float: left;
        }
        .tableButtonsLine .slideButton.new {
             margin: 0;
             float: right;
             text-decoration: none;
         }
        .tableButtonsLine .slideButton.find {
            float: left;
            padding: 0;
            text-indent: 34px;
        }
        .tableButtonsLine .slideButton.find input{
            border: 0;
            width: 240px;
            font-size: 14px;
            font-family: 'Frank', "Franklin Gothic Medium", serif;
            color: #828282;
            height: 32px;
            padding-left: 8px;
        }

        .datatable {
            width: 100%;
            border-collapse: collapse;
        }
        .datatable .title td {color: #828282;}
        .datatable td {
            border: 1px solid #b1b1b1;
            padding: 4px;
            line-height: 26px;
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
            Военнослужащие
        </div>

        <div class="tableBodyBox">
            <div class="tableButtonsLine">
                <div class="slideButton filter" onclick="filter_box_show()">Фильтры</div>
                <div class="slideButton find">
                    <input placeholder="Поиск">
                </div>
                <a class="slideButton new" href="military_edit.php">Добавить</a>
            </div>

            <table class="datatable">
                <tr class="title">
                    <td>№</td>
                    <td>Звание</td>
                    <td>ФИО</td>
                    <td>Должность</td>
                    <td>Подразделение</td>
                    <td></td>
                </tr>
                <?php

                    $data = \OsT\Military\Military::getData(null, ['id', 'level_title', 'fio', 'state_title', 'unit_path_str']);
                    if (count($data)) {
                        $index = 1;
                        foreach ($data as $military) {
                            $military['index'] = $index;
                            echo \OsT\Military\Military::getTableItemHtml($military);
                            $index++;
                        }
                    } else echo '<tr><td colspan="6">Нет записей</td></tr>';

                ?>
            </table>
        </div>

    </div>

<?php
    require_once 'layouts/footer.php';
?>
