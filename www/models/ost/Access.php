<?php
namespace OsT;

/**
 * Управление доступом к ресурсам системы
 * Class Access
 * @package OsT
 * @version 2022.03.10
 *
 * getDefaultAccessArray        Получить массив всех прав доступа по умолчанию
 * getAccessPackTypes           Получить список пакетов доступа
 * getDefaultAccessTypeArray    Получить определенный пакет доступа
 * checkAccess                  Проверить права на доступ
 *
 */
class Access
{

    /**
     * Получить массив всех прав доступа по умолчанию
     * @return array - массив прав доступа
     */
    public static function getDefaultAccessArray ()
    {
        return [
            /* просмотр страницы units
             * отображение значка "Структура" в меню
             */
            'units_show' => false,

            /*
             * создание, редактирование, удаление, перемещение подразделений на странице units
             */
            'units_edit' => false,

            /* просмотр страницы state
             * отображение значка "Штат" в меню
             */
            'state_show' => false,

            /*
             * создание, редактирование, удаление, перемещение штата на страницах state.php, state_edit
             */
            'state_edit' => false,

            /* просмотр данных военнослужащих
             * @param true -  всех военнослужащих
             * @param array - военнослужащих в подразделениях, указанных в массиве
             */
            'military_show' => [],

            /* возможность добавления новых военнослужащих
             */
            'military_new' => false,

            /* редактирование, удаление военнослужащих
             * @param true - редактирование всех военнослужащих
             * @param array - редактирование военнослужащих в подразделениях, указанных в массиве
             */
            'military_edit' => [],

            /* просмотр страницы users.php
             * отображение значка "Пользователи" в меню
             */
            'users_show' => false,

            /*
             * создание, редактирование, удаление пользователей на страницах users, user_edit
             */
            'users_edit' => false,

            /* Просмотр страницы schedule_select, schedule
             * Отображение значка "График нагрузки" в меню
             * @param true - просмотр всех графиков нагрузки
             * @param array - просмотр графиков нагрузки подразделений, указанных в массиве
             */
            'schedule_show' => [],

            /* Редактирование графиков нагрузки на странице schedule
             * @param true - редактирование всех графиков нагрузки
             * @param array - редактирование графиков нагрузки подразделений, указанных в массиве
             */
            'schedule_edit' => [],

            /* Формировать отчеты на странице reports
             * @param true - формирование отчетов по всем подразделениям
             * @param array - формирование отчетов по всем подразделениям, указаннын в массиве
             */
            'report_show' => [],

            /*
             * Доступ к настройкам системы
             */
            'settings' => false,

            /*
             * Просмотр страницы mask
             * @param true - просмотр собственных шаблонов
             */
            'masks_show' => false,

            /* Изменение / удаление шаблона
             * @param true - редактирование собственных шаблонов
             */
            'mask_edit' => false
        ];
    }

    /**
     * Получить список пакетов доступа
     * @return array
     */
    public static function getAccessPackTypes ()
    {
        return [
            'admin' => 'Администратор',
            'manager' => 'Сотрудник отдела кадров',
            'commander' => 'Командир подразделения',
            'clerk' => 'Писарь',
            'guest' => 'Гость',
        ];
    }

    /**
     * Получить определенный пакет доступа
     * @param $type - тип пакета доступа
     * @example 'admin'
     *
     * @param array $data - вспомогательные данные для формирования пакета
     *
     * @return array - пакет доступа
     * @todo допилить все типы
     */
    public static function getDefaultAccessTypeArray ($type, $data = [])
    {
        $access = Access::getDefaultAccessArray();
        switch ($type) {
            case 'admin':
                foreach ($access as $key => $value)
                    $access[$key] = true;
                break;

            case 'manager':
                // допилить
                break;

            // допилить .....
        }
        return $access;
    }

    /**
     * Проверить права на доступ
     * @param $key  - тип проверки
     * @param $vars - массив переменных
     * @return bool
     */
    public static function checkAccess ($key, $vars = [])
    {
        global $STRUCT_TREE;
        global $USER;
        $return = false;
        switch ($key) {
            /*
             * Просмотр страницы меню
             */
            case 'menu_show':
                $return = $USER instanceof User;
                break;

            /* просмотр страницы units
             * отображение значка "Структура" в меню
             */
            case 'units_show':
                if ($USER instanceof User)
                    $return = $USER->access['units_show'];
                break;

            /* создание, редактирование, удаление, перемещение подразделений на странице units
             */
            case 'units_edit':
                if ($USER instanceof User)
                    $return = $USER->access['units_edit'];
                break;

            /* просмотр страницы state
             * отображение значка "Штат" в меню
             */
            case 'state_show':
                if ($USER instanceof User)
                    $return = $USER->access['state_show'];
                break;

            /* создание, редактирование, удаление, перемещение штата (должностей) на странице state
             */
            case 'state_edit':
                if ($USER instanceof User)
                    $return = $USER->access['state_edit'];
                break;

            /* просмотр страницы militaries
             * отображение значка "Военнослужащие" в меню
             */
            case 'militaries_show':
                if ($USER instanceof User)
                    if ($USER->access['military_show'] === true || count($USER->access['military_show']))
                        $return = true;
                break;

            /* добавление новых военнослужащих
             */
            case 'military_new':
                return $USER->access['military_new'];

            /* редактирование, удаление военнослужащих
             */
            case 'military_edit':
                if ($USER instanceof User)
                    if ($USER->access['military_edit'] === true || count($USER->access['military_edit']))
                        $return = true;
                break;

            /* просмотр страницы users.php
             * отображение значка "Пользователи" в меню
             */
            case 'users_show':
                if ($USER instanceof User)
                    $return = $USER->access['users_show'];
                break;

            /* Просмотр страницы schedule_select
             * Отображение значка "График нагрузки" в меню
             */
            case 'schedules_show':
                if ($USER instanceof User)
                    if ($USER->access['schedule_show'] === true || count($USER->access['schedule_show']))
                        $return = true;
                break;

            /* Просмотр страницы reports
             * Отображение значка "Отчеты" в меню
             */
            case 'reports_show':
                if ($USER instanceof User)
                    if ($USER->access['report_show'] === true || count($USER->access['report_show']))
                        $return = true;
                break;

            /* Доступ к настройкам системы
             * просмотр страницы settings
             * отображение значка "Настройки" в меню
             */
            case 'settings_show':
                if ($USER instanceof User)
                    $return = $USER->access['settings'];
                break;

            /* Просмотр страницы mask
             */
            case 'masks_show':
                if ($USER instanceof User)
                   $return = $USER->access['masks_show'];
                break;

            /* Изменение / удаление шаблона
             * @param user - пользователь, который создал шаблон
             */
            case 'mask_edit':
                if ($USER instanceof User) {
                    if ($vars['user'] === $USER->id)
                        $return = $USER->access['mask_edit'];
                }
                break;

        }
        return $return;
    }

}