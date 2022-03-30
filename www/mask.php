<?php
require_once  'layouts/header.php';

$pageData['title'] = 'Шаблоны';
$pagesGroup = ['settings', 'menu'];

use OsT\Access;
use OsT\Base\Message;
use OsT\Base\System;
use OsT\Serviceload\Mask;

if (!Access::checkAccess('masks_show')) {
    new Message('Недостаточно прав для просмотра данной страницы', Message::TYPE_ACCESS);
    System::location('index.php');
    exit;
}

if (isset($_GET['delete'])) {
    $mask = new Mask(intval($_GET['delete']));
    if ($mask->workability) {
        if (Access::checkAccess('mask_edit', ['user' => $mask->user])) {
            if ($mask->delete()) {
                new Message('Шаблон успешно удален', Message::TYPE_OK);
                System::location('mask.php');
                exit;

            } else {
                new Message('В процессе удаления данных произошла ошибка', Message::TYPE_ERROR);
                System::location('mask.php');
                exit;
            }

        } else {
            new Message('Недостаточно прав для удаления данного шаблона', Message::TYPE_ACCESS);
            System::location('index.php');
            exit;
        }

    } else {
        new Message('Шаблона, который вы пытаетесь удалить, не существует', Message::TYPE_WARNING);
        System::location('mask.php');
        exit;
    }
}

require_once  'layouts/head.php';



?>

    <script>
        function maskEnable (ell, id) {
            ajax(
                'mask_enable',
                {
                    'enabled': $(ell).is(':checked') ? 1 : 0,
                    'id': id
                },
                function () {}
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
        .tableButtonsLine .slideButton.new {
            margin: 0;
            float: right;
            text-decoration: none;
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
        .datatable .chb_enabled {
            margin: 6px 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .buttonsBox {
            display: inline-block;
            float: right;
            min-width: 82px;
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
        .buttonsBox .button:first-child {margin: 1px 0 1px;}
        .buttonsBox .button.edit {background-image: url("img/table_buttons/edit.png");}
        .buttonsBox .button.delete {background-image: url("img/table_buttons/delete.png");}
    </style>

    <div class="bodyBox no_select">
        <div class="pageTitleLine">
            Шаблоны
        </div>

        <div class="tableBodyBox">
            <div class="tableButtonsLine">
                <a class="slideButton new" href="mask_edit.php">Добавить</a>
            </div>

            <table class="datatable">
                <tr class="title">
                    <td rowspan="2"></td>
                    <td rowspan="2">Название</td>
                    <td colspan="5">Данные</td>
                    <td rowspan="2"></td>
                </tr>
                <tr class="title">
                    <td>Тип службы</td>
                    <td>Прибытие</td>
                    <td>Заступление</td>
                    <td>Продолжительность</td>
                    <td>Место</td>
                </tr>
                <?php
                    $data = Mask::getArrayByUser($USER->id);
                    if (count($data)) {
                        $data = Mask::getData($data, [
                            'id',
                            'title',
                            'data',
                            'enabled',
                            'enabled_str'
                        ]);
                        Mask::getDataDecryption($data);
                        foreach ($data as $item)
                            echo Mask::genHtmlItem($item);
                    } else echo '<tr><td colspan="8">Нет данных</td></tr>';
                ?>
            </table>
        </div>
    </div>

<?php
    require_once 'layouts/footer.php';
?>
