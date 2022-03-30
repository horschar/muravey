<?php

use OsT\Access;

require_once  'layouts/header.php';

$pageData['title'] = 'Меню';
$pagesGroup = ['menu'];

if (!Access::checkAccess('menu_show')) {
    \OsT\Base\System::location('login.php');
    exit;
}

require_once  'layouts/head.php';

?>

    <style>
        .menuItemsBox {
            width: 80%;
            margin: 10px auto;
            display: inline-block;
        }
        .menuItemBox {
            width: 200px;
            height: 220px;
            display: inline-block;
            margin: 10px;
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center;
            font-size: 18px;
            padding: 170px 0 0 0;
            text-decoration: none;
            color: #777;
            border: 1px solid #989898;
            box-sizing: border-box;
            background-color: #ffffff7d;
            font-family: RalewayB, sans-serif;
        }
        .menuItemBox:hover {
            color: #0a0000;
        }

    </style>

    <div class="menuItemsBox">
        <?php
            $menu = [
                'units' =>          '<a class="menuItemBox" href="units.php" style="background-image: url(\'img/menu/unit.svg\'); background-size: 150px; background-position: center 1px;">Структура</a>',
                'state' =>          '<a class="menuItemBox" href="state.php" style="background-image: url(\'img/menu/state.svg\'); background-size: 134px; background-position: center 8px;">Штат</a>',
                'militaries' =>     '<a class="menuItemBox" href="militaries.php" style="background-image: url(\'img/menu/military.svg\'); background-size: 134px; background-position: center 8px;">Военнослужащие</a>',
                'users' =>          '<a class="menuItemBox" href="#" style="background-image: url(\'img/menu/user.svg\'); background-size: 134px; background-position: center 8px;">Пользователи</a>',
                'schedules' =>      '<a class="menuItemBox" href="schedule_edit.php" style="background-image: url(\'img/menu/schedule.svg\'); background-size: 134px; background-position: center 8px;">График нагрузки</a>',
                'reports' =>        '<a class="menuItemBox" href="reports.php" style="background-image: url(\'img/menu/reports.svg\'); background-size: 134px; background-position: center 8px;">Отчеты</a>',
                'settings' =>       '<a class="menuItemBox" href="#" style="background-image: url(\'img/menu/settings.svg\'); background-size: 116px; background-position: center 21px;">Настройки</a>'
            ];

            if (Access::checkAccess('units_show'))      echo $menu['units'];
            if (Access::checkAccess('state_show'))      echo $menu['state'];
            if (Access::checkAccess('militaries_show')) echo $menu['militaries'];
            if (Access::checkAccess('users_show'))      echo $menu['users'];
            if (Access::checkAccess('schedules_show'))  echo $menu['schedules'];
            if (Access::checkAccess('reports_show'))    echo $menu['reports'];
            if (Access::checkAccess('settings_show'))   echo $menu['settings'];

        ?>
    </div>

<?php
    require_once 'layouts/footer.php';
?>
