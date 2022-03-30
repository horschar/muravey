<?php
    require_once  __DIR__ . '/layouts/header.php';

    $pageData['title'] = 'Графики нагрузки';
    $pagesGroup = ['schedule', 'menu'];

    use OsT\Access;
    use OsT\Base\Message;
    use OsT\Base\System;

    if (!Access::checkAccess('schedules_show')) {
        new Message('Недостаточно прав для просмотра данной страницы', Message::TYPE_ACCESS);
        System::location('index.php');
        exit;
    }

    require_once  __DIR__ . '/layouts/head.php';
?>

<link rel="stylesheet" href="css/questionbox.css">
<script src="js/questionBox.js"></script>

<script>

    $(document).ready(function () {
        $('.unit_select_box .settingsInputBoxItemSelect select').change(function (e) {
            update_units_select_list(e);
        });
    });

    /**
     * Обновить списки подразделений
     */
    function update_units_select_list (e) {
        selected_unit = parseInt($(e.target).children('option:selected').val());
        if (selected_unit === -1) {
            select_delete_after_name_index ($(e.target), 'unit_');
        } else {
            ajax(
                'schedule_select_update_select_list',
                {
                    'selected': selected_unit
                },
                function (key, data, respond) {
                    $('.unit_select_box .settingsInputBoxItemSelect').html(respond);
                    $('.unit_select_box .settingsInputBoxItemSelect select').change(function (e) {
                        update_units_select_list(e);
                    });
                }
            );
        }
    }

    /**
     * Проверить корректность данных в форме вибора графика нагрузки
     */
    function checkFormData () {
        form_data= form_get_data_all($('.form'));
        ajax(
            'schedule_select',
            {
                'data' : form_data
            },
            function (key, data, respond) {
                if (respond === 'ERR_NO_MILITARY') {
                    text = 'В выбранном вами подразделении не найдено ни одного работника на данный период времени';

                    shadowNew(99, function () {
                        $('.messageBox').remove();
                        shadowRemove(99);
                    });
                    showMessage(
                        text,
                        function () {
                            $('.messageBox').remove();
                            shadowRemove(99);
                        }
                    );

                } else {
                    unit = parseInt(respond);
                    year = form_data['year'];
                    mon = form_data['month'];
                    window.location = "schedule_edit.php?year=" + year + "&mon=" + mon + '&unit=' + unit;
                }
            }
        );
    }

</script>

<style>
    .block{
        background-color: #ffffffe8;
        padding: 20px;
        display: inline-block;
        margin: 20px;
        max-width: 800px;
        width: 80%;
    }

    .pageTitle {
        display: inline-block;
        border-bottom: 2px solid #000;
        font-family: RalewayB, sans-serif;
        font-size: 22px;
        margin: 0 0 3px;
        padding: 0 0 6px;
        width: 100%;
        cursor: default;
        position: relative;
    }

    .form {
        width: 90%;
        display: inline-block;
        margin: 10px 0 0 0;
    }

    .submitButton {
        background-color: #991314;
        color: #e4e4e4;
        padding: 10px 25px;
        border-radius: 10px;
        display: inline-block;
        float: right;
        margin: 10px 0 0;
        cursor: pointer;
    }
</style>

<div class="block">

    <div class="pageTitle">
        <?php echo $pageData['title'];?>
    </div>

    <form class="form" action="<?php echo $_SERVER [ 'REQUEST_URI' ]; ?>" method="post" enctype="multipart/form-data">

        <?php

        $now = getdate();

        $data = [];
        $year_from = $now['year'] - 5;
        $year_to = $now['year'] + 5;

        for ($i = $year_from; $i < $year_to; $i++) {
            $tmp = ['id' => $i, 'title' => $i];
            if ($i === $now['year'])
                $tmp['selected'] = 'selected';
            $data[] = $tmp;
        }

        $year_select_html =  System::getHtmlSelect($data, 'year');



        $data = [];
        $months = System::getMonthTitleArr();
        foreach ($months as $key=>$value) {
            $tmp = ['id' => $key + 1, 'title' => $value[0]];
            if ($key === $now['mon'] - 1)
                $tmp['selected'] = 'selected';
            $data[] = $tmp;
        }
        $month_select_html =  System::getHtmlSelect($data, 'month');



        /** @var $STRUCT_TREE
            @global layouts/header.php
         */
        $tree = [0 => $STRUCT_TREE];
        $unit_select_html = \OsT\Unit::getSelectUnitHtml($tree, 0, 'unit_');

        ?>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Год</div>
            <div class="settingsInputBoxItemSelect">
                <?php echo $year_select_html;?>
            </div>
        </div>

        <div class="settingsInputBoxItem">
            <div class="settingsInputBoxItemTitle" >Месяц</div>
            <div class="settingsInputBoxItemSelect">
                <?php echo $month_select_html;?>
            </div>
        </div>

        <div class="settingsInputBoxItem unit_select_box">
            <div class="settingsInputBoxItemTitle" >Подразделение</div>
            <div class="settingsInputBoxItemSelect">
                <?php echo $unit_select_html;?>
            </div>
        </div>

        <div class="submitButton" onclick="checkFormData()">
            Подтвердить
        </div>

    </form>

</div>


<?php
    require_once __DIR__ . '/layouts/footer.php';
?>
