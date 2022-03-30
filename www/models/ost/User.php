<?php

namespace OsT;

use OsT\Base\System;
use PDO;

/**
 * Пользователь
 * Class User
 * @package OsT
 * @version 2022.03.12
 *
 * __construct              User constructor
 * getData                  Получить массив данных из БД
 * loginSearch              Поиск пользователя по логину
 * login                    Выполнить вход в систему пользователем
 * logout                   Выполнить выход из учетной записи пользователя
 * check                    Проверка авторизированного пользователя
 * getUserName              Получить имя (ФИО или логин) пользователя в системе
 *
 * last                     Получить идентификатор (id) последней запсии в таблице
 * count                    Получить количество записей в таблице
 * insert                   Добавить забись в БД
 * update                   Обновить запись в БД
 * _update                  Обновить запись в БД
 * updateSettings           Обновить настройки пользователя в БД, используя $this->settings
 * _clearSettings           Очистить персональные настройки пользователя
 * _clearSettingsAll        Очистить персональные настройки всех пользователей
 * delete                   Удалить запись из БД
 * _delete                  Удалить запись из БД
 * _deleteAll               Удалить всех пользователей
 *
 */
class User
{
    /**
     * $settings
     *      schedule                    нагройки графика служебной нагрузки
     *          unit                        идентификатор подразделения, на которое последний раз отображался график служебной нагрузки (schedule_edit)
     *      reports                     настройки отчетов
     *          unit_last_used              подразделение, выбранное в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *          rsn                         настройки отчета "Рапорт о служебной нагрузке"
     *              version_last_used           версия отчета, выбраная в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *              unit_last_used              подразделение, выбранное в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *          '1' => [
     *                  'font'              - шрифт
     *                  ...                 подробное описание в каждом классе V
     *                  ]
     *              ]
     *          rno                         настройки отчета "Рапорт универсальный"
     *              version_last_used           версия отчета, выбраная в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *          rnk                         настройки отчета "Рапорт на котел"
     *              version_last_used           версия отчета, выбраная в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *          rsz                         настройки отчета "Развернутая строевая записка"
     *              version_last_used           версия отчета, выбраная в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     *          sov                         настройки отчета "Список отсутстующих военнослужащих"
     *              version_last_used           версия отчета, выбраная в последний раз в окне параметров отчета при добавлении в очередь печати (reports)
     */

    const TABLE_NAME = 'ant_user';

    public $id;                         // Идентификатор
    public $login;                      // Логин
    public $pass;                       // Пароль
    public $military;                   // Идентификатор военнослужащего
    public $military_fio_short;         // ФИО военнослужащего
    public $access;                     // Массив прав доступа
    public $settings;                   // Массив личных настроек системы
    public $workability = false;        // Работоспособность объекта

    /**
     * User constructor.
     * @param $id - идентификатор пользователя
     */
    public function __construct ($id)
    {
        $data = self::getData([$id], [
            'id',
            'login',
            'pass',
            'military',
            'military_fio_short',
            'access',
            'settings'
        ]);
        if (count($data)) {
            $data = $data[$id];
            $this->id = $data['id'];
            $this->login = $data['login'];
            $this->pass = $data['pass'];
            $this->military = $data['military'];
            $this->military_fio_short = $data['military_fio_short'];
            $this->access = $data['access'];
            $this->settings = $data['settings'];

            $this->workability = true;
        }
    }

    /**
     * Получить массив данных из БД
     *
     * @param array $records - массив идентификаторов запрашиваемых записей
     * @example [1, 4, 6, ...]  - определенные записи
     *          null            - все записи
     *
     * @param array $colums - массив атрибутов запрашиваемых данных
     * @example ['id', 'title', 'count', ...]   - определенные идентификаторы
     *          []                              - набор данных по умолчанию
     *
     * @return array массив данных запрашиваемых записей
     * @example [ 0 => ['id' => 1, 'title' => 'default', ...], ...]
     *          где 0 - идентификатор записи в БД
     *
     * @todo Допилить получение прав доступа. В конечном итоге в базе будет храниться только массив доступа, а не название пакета как сейчас
     *
     *      id                  -   Идентификатор пользователя
     *      login               -   Логин
     *      pass                -   Пароль
     *      military            -   Идентификатор военнослужащего (связь аккаунта пользователя с военнослужащим)
     *      military_fio_short  -   ФИО краткое военнослужащего в формате Иванов И.И.
     *      access              -   Права доступа
     *      settings            -   Персональные настройки
     *
     */
    public static function getData ($records = null, $colums = [])
    {
        global $DB;
        $arr = [];
        $in_arr_sql = ($records === null) ? '' : ' WHERE u.id IN (' . System::convertArrToSqlStr($records) . ')';
        $q = $DB->prepare('
            SELECT u.id,
                   login,
                   pass,
                   u.military,
                   access,
                   settings,
                   m.fname,
                   m.iname,
                   m.oname
            FROM   ant_user u
            LEFT JOIN military m ON m.id = u.military
            ' . $in_arr_sql);
        $q->execute();

        if ($q->rowCount()) {
            $objectData = [];
            $data = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $key => $item) {

                /* Идентификатор пользователя */
                if (System::chColum('id', $colums))
                    $objectData['id'] = intval($item['id']);

                /* Логин */
                if (System::chColum('login', $colums))
                    $objectData['login'] = stripslashes($item['login']);

                /* Пароль */
                if (System::chColum('pass', $colums))
                    $objectData['pass'] = $item['pass'];

                /* Идентификатор военнослужащего (связь аккаунта пользователя с военнослужащим) */
                if (System::chColum('military', $colums))
                    $objectData['military'] = intval($item['military']);

                /* ФИО краткое военнослужащего в формате Иванов И.И. */
                if (System::chColum('military_fio_short', $colums))
                    $objectData['military_fio_short'] = System::shortFio(stripslashes($item['fname']) . ' ' . stripslashes($item['iname']) . ' ' .stripslashes($item['oname']));

                /* Права доступа */
                if (System::chColum('access', $colums)) {
                    if ($item['access'] === null)
                        $objectData['access'] = [];
                    else {
                        $packs_arr = Access::getAccessPackTypes();
                        if (isset($packs_arr[$item['access']]))
                            $objectData['access'] = Access::getDefaultAccessTypeArray($item['access']);
                        else $objectData['access'] = json_decode($item['access'], true);
                    }
                }

                /* Персональные настройки */
                if (System::chColum('settings', $colums))
                    $objectData['settings'] = ($item['settings'] === null) ? [] : json_decode($item['settings'], true);

                // Удаление промежуточных данных
                foreach ($objectData as $okey => $oval)
                    if (!in_array($okey, $colums))
                        unset($objectData[$okey]);

                // Добавление данных в конечный массив
                $arr[intval($item['id'])] = $objectData;
            }
        }
        return $arr;
    }

    /**
     * Поиск пользователя по логину
     * @param $login - логин
     * @param array $blackList - массив идентификаторов пользователей, которых не нужно учитывать при поиске
     * @return int
     *      0 - пользователей с данным логином не найдено
     *      N > 0 - идентификатор пользователя с данным логином
     */
    public static function loginSearch ($login, $blackList = [])
    {
        global $DB;
        $blackListStr = '';
        if (count($blackList)) {
            $blackListStr = 'AND id NOT IN (' . System::convertArrToSqlStr($blackList) . ')';
        }
        $q = $DB->prepare('
            SELECT  id
            FROM    ant_user
            WHERE   login = :login 
        ' . $blackListStr);
        $q->execute(['login'=>$login]);
        if ($q->rowCount()) {
            $data = $q->fetch(PDO::FETCH_ASSOC);
            return intval($data['id']);
        }
        return 0;
    }

    /**
     * Выполнить вход в систему пользователем
     * @param $login - логин
     * @param $pass - hash пароля
     * @return bool
     *          true - вход выполнен
     *          false - вход не выполнен
     */
    public static function login ($login, $pass)
    {
        global $DB;
        $login = trim($login);
        $login = addslashes($login);
        $pass = hash('sha256', $pass);

        $q = $DB->prepare('
            SELECT  id
            FROM    ant_user
            WHERE   login = :login AND 
                    pass = :pass
        ');
        $q->execute(['login'=>$login, 'pass'=>$pass]);
        if ($q->rowCount()) {
            $data = $q->fetch(PDO::FETCH_NUM);
            $cookie = [
                'user' =>  intval($data[0]),
                'sc_user' => $pass
            ];
            System::setCookie($cookie);

            return true;
        }

        return false;
    }

    /**
     * Выполнить выход из учетной записи пользователя
     */
    public static function logout ()
    {
        $cookie = [
            'user' =>  null,
            'sc_user' => null
        ];
        System::setCookie($cookie);
    }

    /**
     * Проверка авторизированного пользователя
     * @return User|null
     *       User - экземпляр класса авторизированного пользователя
     *       null - ни один из пользователей не авторизирован
     */
    public static function check ()
    {
        global $_COOKIE;
        if (isset($_COOKIE['user']) && isset($_COOKIE['sc_user'])) {
            $user = new User(intval($_COOKIE['user']));
            if ($user->workability && $user->pass === $_COOKIE['sc_user'])
                return $user;
        }
        return null;
    }

    /**
     * Получить имя (ФИО или логин) пользователя в системе
     * @return mixed
     */
    public function getUserName ()
    {
        if ($this->military)
            return $this->military_fio_short;
        else return $this->login;
    }

    /**
     * Получить идентификатор (id) последней запсии в таблице
     * @return int
     */
    public static function last ()
    {
        global $DB;
        $q = $DB->prepare('
                SELECT     MAX(id)
                FROM       ' . self::TABLE_NAME);
        $q->execute();
        $id = $q->fetch(PDO::FETCH_NUM);
        return intval(end($id));
    }

    /**
     * Добавить забись в БД
     * @param $data
     * @return bool
     */
    public static function insert ($data)
    {
        global $DB;
        return $DB->_insert(self::TABLE_NAME, $data);
    }

    /**
     * Обновить запись в БД
     * @param $data
     * @return bool
     */
    public function update ($data)
    {
        return self::_update($this->id, $data);
    }

    /**
     * Обновить запись в БД
     * @param $id
     * @param $data
     * @return bool
     */
    public static function _update ($id, $data)
    {
        global $DB;
        return $DB->_update(
            self::TABLE_NAME,
            $data,
            [
                ['id = ', $id]
            ]);
    }

    /**
     * Обновить настройки пользователя в БД, используя $this->settings
     */
    public function updateSettings ()
    {
        $this->update(['settings' => json_encode($this->settings)]);
    }

    /**
     * Очистить персональные настройки пользователя
     * @param $id
     */
    public static function _clearSettings ($id)
    {
        self::_update($id, ['settings' => json_encode([])]);
    }

    /**
     * Очистить персональные настройки всех пользователей
     */
    public static function _clearSettingsAll ()
    {
        global $DB;
        $DB->_update(self::TABLE_NAME, ['settings' => json_encode([])]);
    }

    /**
     * Удалить запись из БД
     * @return bool
     */
    public function delete ()
    {
        return self::_delete($this->id);
    }

    /**
     * Удалить запись из БД
     * @param $id - идентификатор пользователя
     * @return bool
     */
    public static function _delete ($id)
    {
        global $DB;

        \OsT\Serviceload\Mask::_deleteByUser($id);
        \OsT\Reports\Package::_deleteByUser($id);

        $DB->_delete(self::TABLE_NAME, [['id = ', $id]]);
        return true;
    }

    /**
     * Удалить всех пользователей
     */
    public static function _deleteAll ()
    {
        global $DB;

        \OsT\Serviceload\Mask::_deleteAll();
        \OsT\Reports\Package::_deleteAll();

        $DB->query('DELETE FROM ' . self::TABLE_NAME);
    }

}