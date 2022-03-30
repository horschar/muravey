<?php

use OsT\Access;
use OsT\User;

?>
<style>
    .headLine{
        width: 100%;
        display: inline-block;
        float: left;
    }
    .titleLine {
        width: 100%;
        background-image: url("../img/header-bg2.jpg");
        background-position: center;
        background-size: cover;
        display: inline-block;
        cursor: default;
        float: left;
    }
    .titleLineLeft,
    .titleLineRight,
    .navLineLeft,
    .navLineRight{
        display: inline-block;
        width: 10%;
        float: left;
        height: 1px;
    }
    .titleLineLeft {
        background-image: url("../img/rosicon.png");
        background-position: center;
        background-size: contain;
        background-repeat: no-repeat;
        height: 60px;
        margin: 8px 0;
    }
    .titleLineCenter,
    .navLineCenter{
        width: 80%;
        float: left;
    }
    .navLineCenter {
        box-sizing: border-box;
        border-left: 2px solid #891212;
        border-right: 2px solid #891212;
    }
    .mainTitle{
        font-size: 18px;
        color: #fde7e7;
        padding: 16px 0 6px;
        text-align: left;
    }
    .subTitle {
        font-size: 14px;
        text-align: left;
        color: #ceab03;
    }
    .navLine {
        width: 100%;
        background-image: linear-gradient(to bottom,#9C1112 0,#611313 100%);
        display: inline-block;
        float: left;
        position: relative;
    }
    .navLineButton {
        color: #d4d4d4;
        padding: 10px 15px;
        line-height: 20px;
        float: left;
        text-decoration: none;
        transition: all .2s linear; -o-transition: all .2s linear; -moz-transition: all .2s linear; -webkit-transition: all .2s linear;
        border-right: 2px solid #891212;
    }
    .navLineButton:hover,
    .navLineButton.active {
        background-image: linear-gradient(to bottom,#9C1112 0,#8A191A 100%);
        box-shadow: 0px 1px 6px 0px rgba(0,0,0,0.12), 0px 1px 6px 0px rgba(39, 11, 11, 0.03);
        color: #fff;
    }
    .navLineButton.right {
        float: right;
        border-right: 0;
        border-left: 2px solid #891212;
    }
    .version_link {
        color: #9e8301;
        cursor: pointer;
        text-decoration: none;
    }
    .version_link:hover {
        text-decoration: underline;
        color: #ceab03;
    }


    /*---------------------------------  settingsInputBox  ------------------------------ */
    .settingsInputBox {
        display: none;
        z-index: 100;
        background-color: #fff;
        padding: 10px 20px;
        top: 50%;
        left: 50%;
        -webkit-transform: translate(-50%, -50%);
        -moz-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        position: fixed;
        width: 400px;
    }
    .settingsInputBoxTitle {
        padding: 6px 0 10px 0;
        font-size: 18px;
        color: #6d6d6d;
        cursor: default;
    }
    .settingsInputBoxItem {
        padding: 8px 0 0;
        display: inline-block;
        width: 100%;
    }
    .settingsInputBoxItemTitle,
    .settingsInputBoxItemTitleCheckbox {
        float: left;
        width: 40%;
        text-align: left;
        font-size: 15px;
        line-height: 32px;
        cursor: default;
    }
    .settingsInputBoxItemTitleCheckbox {
        width: auto;
        display: flex;
    }
    .settingsInputBoxItemInput {
        display: block;
        width: 60%;
        font-family: 'Frank', "Franklin Gothic Medium", serif;
        font-size: 16px;
        border: 1px solid;
        padding: 5px;
        box-sizing: border-box;
    }
    textarea.settingsInputBoxItemInput {
        max-width: 60%;
        min-width: 60%;
        max-height: 200px;
        min-height: 30px;
    }
    .settingsInputBoxItemSelect {
        display: inline-block;
        width: 60%;
    }
    .settingsInputBoxItemSelect select {
        width: 100%;
        font-family: 'Frank', "Franklin Gothic Medium", serif;
        font-size: 16px;
        border: 1px solid;
        padding: 5px;
        box-sizing: border-box;
        margin-bottom: 10px;
        cursor: pointer;
    }
    .settingsInputBoxItemSelect select:last-child {
        margin-bottom: 0;
    }
    .settingsInputBoxItemSubmit {
        border: 2px solid;
        padding: 5px 20px;
        font-size: 16px;
        font-family: 'Frank', "Franklin Gothic Medium", serif;
        display: inline-block;
        cursor: pointer;
        width: 100%;
        box-sizing: border-box;
        margin: 12px auto 0;
        background-color: white;
    }
    .settingsInputBoxItemCheckbox {
        float: right;
        margin: 6px;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .settingsShowMoreButton {
        cursor: pointer;
        color: #8c8c8c;
        line-height: 26px;
    }
    .settingsShowMoreBody {
        display: none;
    }

    /***************************************** slideButton ***********************************************/
    .slideButton {
        display: inline-block;
        float: left;
        max-width: 34px;
        height: 34px;
        padding: 0 10px 0 0;
        box-sizing: border-box;
        background-size: 35px;
        background-position: -1px -1px;
        overflow: hidden;
        background-repeat: no-repeat;
        border-radius: 6px;
        line-height: 30px;
        text-indent: 42px;
        transition: max-width .4s;
        cursor: pointer;
        background-color: #ffffffe3;
        color: #404040;
        margin: 0 0 0 4px;
        font-family: 'Frank';
        /*transition: all .2s linear; -o-transition: all .2s linear; -moz-transition: all .2s linear; -webkit-transition: all .2s linear;*/
    }
    .slideButton.new {
        background-image: url(img/table_buttons/new.png);
        border: 1px solid #00972b;
    }
    .slideButton.move {
        background-image: url(img/table_buttons/move.png);
        border: 1px solid #00a9cb;
    }
    .slideButton.filter {
        background-image: url(img/table_buttons/filters.png);
        border: 1px solid #00a9cb;
    }
    .slideButton.edit {
        background-image: url(img/table_buttons/edit_yellow.png);
        border: 1px solid #d89a00;
    }
    .slideButton.find {
        background-image: url(img/table_buttons/find.png);
        border: 1px solid #d89a00;
    }
    .slideButton.delete {
        background-image: url(img/table_buttons/delete.png);
        border: 1px solid #ea1112;
    }
    .slideButton:hover,
    .slideButton.active {
        max-width: 300px;
    }

    /***********************************  buttonsBox ********************************/

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
        position: relative;
    }
    .buttonsBox .button:first-child {margin: 1px 0 1px;}
    .buttonsBox .button_list {
        display: none;
        position: absolute;
        right: 0;
        top: 38px;
        background-color: #f2f2f2;
        z-index: 1;
        border: 1px solid #b7090a;
        width: auto;
    }
    .buttonsBox .button_list li {
        list-style-type: none;
        white-space: nowrap;
        text-align: left;
        padding: 0 5px;
        cursor: pointer;
    }
    .buttonsBox .button.edit {background-image: url("img/table_buttons/edit.png");}
    .buttonsBox .button.delete {background-image: url("img/table_buttons/delete.png");}
    .buttonsBox .button.more {background-image: url("img/table_buttons/more.png");}

    /*------------------------------ TextSelect ----------------------------*/
    .TextSelect{
        position:relative;
        display:inline-block;
        width: 100%;
    }
    .TextSelectItemsBl{
        width:100%;
        position:absolute;
        display:none;
        z-index:1;
    }
    .TextSelectItem{
        background-color: rgb(232, 232, 232);
        border-bottom: #868686 solid 1px;
        min-width: 100%;
        padding: 5px 13px;
        box-sizing: border-box;
        cursor: pointer;
        text-align: left;
        display: table;
        white-space: nowrap;
        border-left: #868686 solid 1px;
        border-right: #868686 solid 1px;
        color: #5e6063;
    }
    .TextSelectItem.active{
        background-color: #f5c2c2;
        color: #1c1c1c;
    }


    /***********************************  DEV MODE ********************************/

    .object_in_development {
        display: none !important;
    }
</style>

<script>
    function settingsShowMore (ell) {
        $(ell).siblings('.settingsShowMoreBody').show();
        $(ell).hide();
    }
</script>

<div class="headLine no_select">
    <div class="titleLine">
        <div class="titleLineLeft"></div>
        <div class="titleLineCenter">
            <div class="mainTitle">НАЦИОНАЛЬНАЯ ГВАРДИЯ РОССИЙСКОЙ ФЕДЕРАЦИИ</div>
            <div class="subTitle">АВТОМАТИЗИРОВАННАЯ ИНФОРМАЦИОННАЯ СИСТЕМА УПРАВЛЕНИЯ СЛУЖЕБНОЙ НАГРУЗКОЙ "МУРАВЕЙ" <a href="version.php" class="version_link" title="Подробнее о версии"><?php echo $VERSION_SETTINGS['system'] . ' ' . $VERSION_SETTINGS['development_stage'];?></a></div>
        </div>
        <div class="titleLineRight"></div>
    </div>
    <div class="navLine">
        <div class="navLineLeft"></div>
        <div class="navLineCenter">
            <?php
            $arr['index'] = in_array('index', $pagesGroup) ? 'active' : '';
            $arr['schedule'] = in_array('schedule', $pagesGroup) ? 'active' : '';
            $arr['reports'] = in_array('reports', $pagesGroup) ? 'active' : '';
            $arr['menu'] = in_array('menu', $pagesGroup) ? 'active' : '';
            $arr['login'] = in_array('login', $pagesGroup) ? 'active' : '';

            if ($USER !== null) {
                echo '
                <a class="navLineButton right ' . $arr['login'] . '" href="login.php?logout=1">' . $USER->getUserName() . ' (выйти)</a>
                <a class="navLineButton ' . $arr['index'] . '" href="index.php">Главная</a>
                <a class="navLineButton ' . $arr['menu'] . '" href="menu.php">Меню</a>';

                if (Access::checkAccess('schedules_show'))
                    echo '<a class="navLineButton ' . $arr['schedule'] . '" href="schedule_edit.php">График нагрузки</a>';

                if (Access::checkAccess('reports_show'))
                    echo '<a class="navLineButton ' . $arr['reports'] . '" href="reports.php">Отчеты</a>';

            } else {
                echo '
                <a class="navLineButton ' . $arr['index'] . '" href="index.php">Главная</a>
                <a class="navLineButton right ' . $arr['login'] . '" href="login.php">Войти</a>';
            }
            ?>
        </div>
        <div class="navLineRight"></div>
    </div>
</div>

