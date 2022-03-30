<?php

namespace OsT\Base;

use DateTime;
use FilesystemIterator;

/**
 * Базовый набор универсальных функций
 * Class System
 * @package OsT\Base
 * @version 2022.03.10
 *
 * * * * * * * * * * Дата и время * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *  getMonthTitleArr                -   Получить массив названий месяцев года
 *  getMonthTitle                   -   Получить название месяца по индексу
 *  getNumMonthByTitle              -   Получить индекс месяца по его названию
 *  i2d                             -   Преобразовать целое число в формат для дат
 *  getWeekDayTitle                 -   Получить название дня недели на русском по индексу
 *  parseDate                       -   Преобразование даты и времени
 *  plusDay                         -   Увеличить Unix время на 1 день
 *  minusDay                        -   Уменьшить Unix время на 1 день
 *  getYmdArray                     -   Получить массив дней [y][m][d], которые пересек Unix дапапазон времени от $from до $to
 *  convertMonthToTimeInterval      -   Получить начало и конец месяца в формате Unix
 *  gettimeBeginDayFromTime         -   Получить значение начала дня в формате Unix (первая секунда дня) из произвольного времени Unix
 *  gettimeBeginDayFromTimeSmart    -   Получить значение начала дня в формате Unix (первая секунда дня) из произвольного времени Unix умный
 *  gettimeBeginDay                 -   Получить значение начала дня в формате Unix (первая секунда дня)
 *  gettimeEndDay                   -   Получить значение конца дня в формате Unix (последняя секунда дня)
 *  nextMonth                       -   Получить значение месяца и года следующего месяца от исходного
 *  previousMonth                   -   Получить значение месяца и года предыдущего месяца от исходного
 *  timeToStr                       -   Преобразовать время (количество секунд) в строку типа 12 мин. 45 сек.
 *  time                            -   Получить время UNIX с учетом настройки сервера
 *  getMonthOutputDays              -   Получить массив выходных дней в месяце
 *  age                             -   Определить возраст по дате рождения
 *
 * * * * * * * * * * Работа с файлами и директориями * * * * * * * * * *
 *
 *  getFileNameFromPath             -   Получить имя файла из пути к нему
 *  uploadFile                      -   Загрузить файл на сервер
 *  deleteFile                      -   Удалить файл
 *  ext                             -   Получить тип файла в формате "exe" по названию
 *  return_bytes                    -   Преобразовать размер файла в биты
 *  sizetostr                       -   Преобразовать биты в размер файла
 *  deleteScandirTo4ki              -   Удалить точки из массива, который формурует scandir
 *  compareFilemtime                -   Сравнить какой из файлов более свежий
 *  recursiveRemoveDir              -   Рекурсивное удаление директории со всеми дочерними элементами
 *  copyFullDir                     -   Скопировать директорию вместе с дочерними файлами
 *  is_zip                          -   Проверить является ли файл ZIP архивом
 *
 * * * * * * * * * * Работа с текстом * * * * * * * * * *
 *
 *  shortFio                        -   Преобразовать ФИО в формат Иванов И.И.
 *  checkFio                        -   Проверить соответсвие фамилии, имени либо отчества шаблону
 *  checkEmail                      -   Проверить, является ли строка E-mail
 *  checkTelephone                  -   Проверить, является ли строка номером телефона
 *  getIdFromInputName              -   Получить идентификаторы объектов, встроенные в параметр name тега Input при отправке методом Post
 *  php2jsVarFormat                 -   Сформировать строку объявления переменной в JS из PHP в зависимости от ее дипа
 *  php2js                          -   Превратить переменную PHP в JS стоку для объявления как переменной
 *  php2jsArray                     -   Превратить массив PHP в JS стоку для объявления как переменной (массива)
 *
 * * * * * * * * * * * HTML * * * * * * * * * *
 *
 *  getSortButton                   -   Получить HTML представление кнопки сортировки
 *  calcSortURLArr                  -   Получить массив параметров для кнопки сортировки
 *  getHtmlSelect                   -   Сформировать HTML представление элемента select
 *  getHtmlSelectOptions            -   Сформировать HTML представление массива значений option для элемента select
 *
 * * * * * * * * * * * URL * * * * * * * * * *
 *
 *  location                        -   Выполнить переадресацию
 *  locationGoBack                  -   Получить ссылку для кнопки назад
 *  locationGoBackCreateURL         -   Добавить к ссылке постфикс для кнопки "назад"
 *  constructGETvarString           -   Сформировать строку переменных URL из массива данных
 *
 * * * * * * * * * * * Прочее * * * * * * * * * *
 *
 *  intervalCrossing                -   Проверить пересечение отрезков
 *  intervalMultiCrossing           -   Проверить пересечение значения либо диапазона var1 с одним из диапазонов массива var2
 *  value_crossing                  -   Функция предназначена для поиска совпадений значений
 *  parseCheckBox                   -   Преобразовать состояние чек бокса в целое число
 *  parseToCheckBox                 -   Преобразовать данные в  формат чек бокса
 *  search                          -   Выполнить поиск по массиву данных. В случае остутсвия совпадений элемент массива удаляется
 *  sort                            -   Отсортировать массив данных по атрибуту
 *  convertArrToSqlStr              -   Преобразовать массив данных в строку для SQL запроса
 *  replaceNumbers                  -   Поменять местами значения числовых переменных
 *  aroundArray                     -   Получить массив с элементами в обратном порядке
 *  chColum                         -   Правило проверки атрибутов для метода getData
 *  setCookie                       -   Добавить / изменить значения cookie
 *  calcPagesGroup                  -   Обработка массива pagesGroup при формировании меню
 *
 */
class System
{
    const TIME_HOUR = 3600;
    const TIME_DAY = 86400;

    /**
     * Получить массив названий месяцев года
     * @return array типа [индекс_падежа] = название_месяца
     */
    public static function getMonthTitleArr()
    {
        return [
            ['Январь', 'Января'],
            ['Февраль', 'Февраля'],
            ['Март', 'Марта'],
            ['Апрель', 'Апреля'],
            ['Май', 'Мая'],
            ['Июнь', 'Июня'],
            ['Июль', 'Июля'],
            ['Август', 'Августа'],
            ['Сентябрь', 'Сентября'],
            ['Октябрь', 'Октября'],
            ['Ноябрь', 'Ноября'],
            ['Декабрь', 'Декабря']
        ];
    }

    /**
     * Получить название месяца по индексу
     * @param int $index - индекс месяца типа Январь - 1
     * @param int $padej - индекс падежа типа Именительный - 0
     * @return mixed - строка типа Январь
     */
    public static function getMonthTitle ($index, $padej = 0)
    {
        $arr = self::getMonthTitleArr();
        return $arr[$index-1][$padej];
    }

    /**
     * Получить индекс месяца по его названию
     * @param string $month - название
     * @param int $padej - индекс падежа, по которому проводится проверка
     * @return bool|string - номер месяца типа Январь - 01
     */
    public static function getNumMonthByTitle ($month, $padej)
    {
        $arr = self::getMonthTitleArr();
        foreach ($arr as $key => $val)
            if ($val[$padej] === $month)
                return self::i2d($key + 1);
        return false;
    }

    /**
     * Преобразовать целое число в формат для дат
     * @param int $int - типа 1
     * @return string - типа "01"
     */
    public static function i2d ($int)
    {
        return ($int < 10) ? '0' . $int : $int;
    }

    /**
     * Получить название дня недели на русском по индексу
     * @param int $index - индекс дня недели типа 0 - воскресенье
     * @return mixed - строка типа "Воскресенье"
     */
    public static function getWeekDayTitle($index)
    {
        $arr = [
            'Воскресенье',
            'Понедельник',
            'Вторник',
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота'
        ];
        return $arr[$index];
    }

    /**
     * Функция предназначена для поиска совпадений значений
     * @param $arr1 - значение либо массив значений
     * @param $arr2 - значение либо массив значений
     * @return bool
     */
    public static function value_crossing ($arr1, $arr2)
    {
        if (!is_array($arr1)) $arr1 = [$arr1];
        if (!is_array($arr2)) $arr2 = [$arr2];
        foreach ($arr1 as $value1)
            foreach ($arr2 as $value2)
                if ($value1 === $value2)
                    return true;
        return false;
    }

    /**
     * Преобразование даты и времени
     * @param $time - дата / время
     * @param string $to - ключ тита, в который нужно пребразовать
     * @param string $from - улюч типа, из которого нужно преобразовать
     * @return array|false|int|string
     */
    public static function parseDate ($time, $to = 'd', $from = 'unix')
    {
        switch ($from) {
            case 'unix' :   break;
            case 'd'    :
                $arr = explode('.', $time);
                $time = (count($arr ) === 3) ? strtotime($arr[2] . '-' . $arr[1] . '-' . $arr[0]) : 0;
                break;
            case 'm/d/y':
                $arr = explode('/', $time);
                $time = (count($arr ) === 3) ? strtotime($arr[2] . '-' . $arr[0] . '-' . $arr[1]) : 0;
                break;
            case 'y-m-d':
                $time = strtotime($time);
                break;
            case 'd/m/y':
                $arr = explode('/', $time);
                $time = (count($arr ) === 3) ? strtotime($arr[2] . '-' . $arr[1] . '-' . $arr[0]) : 0;
                break;
        }

        try {
            $temp =  @getdate($time);
        } catch (Exception $e) {
            return null;
        }

        switch ($to) {
            case 'unix' : return $time;
            case 'y'    : return $temp['year'];
            case 't'    : return self::i2d($temp['hours']) . ':' . self::i2d($temp['minutes']);
            case 'd'    : return self::i2d($temp['mday']) . '.' . self::i2d($temp['mon']) . '.' . $temp['year'];
            case 'dt'   : return self::i2d($temp['mday']) . '.' . self::i2d($temp['mon']) . '.' . $temp['year'] . ' ' . self::i2d($temp['hours']) . ':' . self::i2d($temp['minutes']);
            case 'dts'  : return self::i2d($temp['mday']) . '.' . self::i2d($temp['mon']) . '.' . $temp['year'] . ' ' . self::i2d($temp['hours']) . ':' . self::i2d($temp['minutes']) . ':' . self::i2d($temp['seconds']);
            case 'y-m-d'   : return $temp['year'] . '-' . self::i2d($temp['mon']) . '-' . self::i2d($temp['mday']);
            case 'm/d/y'   : return self::i2d($temp['mon']) . '/' . self::i2d($temp['mday'] . '/' . $temp['year']);
            case 'd/m/y'   : return self::i2d($temp['mday'] . '/' . self::i2d($temp['mon']) . '/' . $temp['year']);
            case 'y-m-dTh-m'   : return $temp['year'] . '-' . self::i2d($temp['mon']) . '-' . self::i2d($temp['mday']) . 'T' . self::i2d($temp['hours']) . ':' . self::i2d($temp['minutes']);
            case 'M y'  : return self::getMonthTitle($temp['mon']) . ' ' . $temp['year'];
            case 'dM'  : return ['day' => $temp['mday'], 'month' => self::getMonthTitle($temp['mon']), 'year'=>$temp['year']];
            case 'array':
            default:        $temp['month'] = self::getMonthTitle($temp['mon'], 0);
                $temp['month1'] = self::getMonthTitle($temp['mon'], 1);
                $temp['time'] = self::i2d($temp['hours']) . ':' . self::i2d($temp['minutes']);
                $temp['weekDay'] = self::getWeekDayTitle($temp['wday']);
                return $temp;
        }
    }

    /**
     * Увеличить Unix время на 1 день
     * @param $time
     * @return false|int
     */
    public static function plusDay ($time)
    {
        $date = self::parseDate($time, 'y-m-d');
        $date = new DateTime($date);
        $date->modify('+1 day');
        return strtotime($date->format('Y-m-d'));
    }

    /**
     * Уменьшить Unix время на 1 день
     * @param $time
     * @return false|int
     */
    public static function minusDay ($time)
    {
        $date = self::parseDate($time, 'y-m-d');
        $date = new DateTime($date);
        $date->modify('-1 day');
        return strtotime($date->format('Y-m-d'));
    }

    /**
     * Получить массив дней [y][m][d], которые пересек Unix дапапазон времени от $from до $to
     * @param $from - время От unix
     * @param $to - время До unix
     * @param array $arr - Ymd массив значений по умолчанию
     * @return array - Ymd массив
     */
    public static function getYmdArray ($from, $to, $arr = [])
    {
        for ($time = $from; $time <= $to; $time = System::plusDay($time)) {
            $date = getdate($time);
            $arr[$date['year']][$date['mon']][$date['mday']] = true;
        }
        return $arr;
    }

    /**
     * Получить начало и конец месяца в формате Unix
     * @param $year - год
     * @param $month - месяц
     * @return array
     *          from - первая секунда в месяце
     *          to - последняя секунда в месяце
     */
    public static function convertMonthToTimeInterval ($year, $month)
    {
        $month_days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        return [
            'from' =>   strtotime($year . '-' . System::i2d($month) . '-01'),
            'to' =>     System::plusDay(strtotime($year . '-' . System::i2d($month) . '-' . $month_days_count)) - 1
        ];
    }

    /**
     * Получить значение начала дня в формате Unix (первая секунда дня) из произвольного времени Unix
     * @param $time - произвольное время  вформате Unix
     * @return false|int
     */
    public static function gettimeBeginDayFromTime ($time)
    {
        $temp = getdate($time);
        return strtotime($temp['year'] . '-' . System::i2d($temp['mon']) . '-' . System::i2d($temp['mday']));
    }

    /**
     * Получить значение начала дня в формате Unix (первая секунда дня) из произвольного времени Unix
     * приставка Smart указывает на сравнение в какую сторону (к началу или концу дня) происходит сдвиг по времени
     * исходя из направления сдвига может увеличивать или уменьшать время на день
     * @param $time - произвольное время  вформате Unix
     * @return false|int - значение начала дня в формате Unix
     */
    public static function gettimeBeginDayFromTimeSmart ($time)
    {
        $tmp = self::gettimeBeginDayFromTime($time);
        if ($time !== $tmp) {
            $digit = $time - $tmp;
            if ($digit > (self::TIME_DAY / 2))
                $tmp = self::plusDay($tmp);
        }
        return $tmp;
    }

    /**
     * Получить значение начала дня в формате Unix (первая секунда дня)
     * @param $year -год
     * @param $month - месяц
     * @param $day - день
     * @return false|int
     */
    public static function gettimeBeginDay ($year, $month, $day)
    {
        return strtotime($year . '-' . System::i2d($month) . '-' . System::i2d($day));
    }

    /**
     * Получить значение конца дня в формате Unix (последняя секунда дня)
     * @param $year -год
     * @param $month - месяц
     * @param $day - день
     * @return false|int
     */
    public static function gettimeEndDay ($year, $month, $day)
    {
        $time = self::gettimeBeginDay($year, $month, $day);
        return self::plusDay($time) - 1;
    }

    /**
     * Получить значение месяца и года следующего месяца от исходного
     * @param $year - исходный год
     * @param $month - исходный месяц
     * @return array - массив данных о следующем месяце
     *            year - год в следующем месяце
     *            month - номер (порядковый 1-12) следующего месяца
     */
    public static function nextMonth ($year, $month) {
        $return = [
            'year' => $year,
            'month' => $month
        ];

        if ($month === 12) {
            $return['month'] = 1;
            $return['year']++;
        } else $return['month']++;

        return $return;
    }

    /**
     * Получить значение месяца и года предыдущего месяца от исходного
     * @param $year - исходный год
     * @param $month - исходный месяц
     * @return array - массив данных о следующем месяце
     *            year - год в предыдущем месяце
     *            month - номер (порядковый 1-12) предыдущего месяца
     */
    public static function previousMonth ($year, $month) {
        $return = [
            'year' => $year,
            'month' => $month
        ];

        if ($month === 1) {
            $return['month'] = 12;
            $return['year']--;
        } else $return['month']--;

        return $return;
    }

    /**
     * Преобразовать время (количество секунд) в строку типа 12 мин. 45 сек.
     * @param $time - кол-во секунд
     * @param string $type - тип возвращаемого значения
     * @return string - строка типа "2д. 34 мин."
     */
    public static function timeToStr ($time, $type = 'auto-d-h-m-s')
    {
        $title = [
            'day' => 'д.',
            'hour' => 'ч.',
            'min' => 'мин.',
            'sec' => 'сек.'
        ];

        $val['day'] = intval($time / (60 * 60 * 24));
        $time %= 60 * 60 * 24;
        $val['hour'] = intval($time / (60 * 60));
        $time %= 60 * 60;
        $val['min'] = intval($time / (60));
        $val['sec'] = $time % 60;

        $str = '';
        foreach ($val as $key => $value)
            if ($value)
                $str .= $value . ' ' . $title[$key] . ' ';
        return trim($str);
    }

    /**
     * Получить время UNIX с учетом настройки сервера
     * @return float|int
     */
    public static function time()
    {
        return time() + TIME_ZONE * self::TIME_HOUR;
    }

    /**
     * Определить возраст по дате рождения
     * @param $birthday - '1990-11-05'
     * @return int
     * @throws \Exception
     */
    public static function age ($birthday)
    {
        $born = new DateTime($birthday);
        return intval($born->diff(new DateTime)->format('%y'));
    }

    /**
     * Получить HTML представление кнопки сортировки
     * @param $key - атрибут для сортировки
     * @param $script - URL скрипта, который обрабатывает сортировку
     * @param $get - массив данных о текущей сортировке данных
     * @return string
     */
    public static function getSortButton ($key, $script ,$get)
    {
        $active = '';
        $sorttype = 'asc';
        if (isset($get['sort'])) {
            if ($get['sort'] === $key) {
                $active = 'active';
                if (isset($get['sorttype']))
                    $sorttype = $get['sorttype'];
            }
        }
        return '<a class="sort ' . $active . ' ' . $sorttype . '" href="' . $script . self::constructGETvarString(self::calcSortURLArr($key, $get)) . '"></a>';
    }

    /**
     * Получить массив параметров для кнопки сортировки
     * @param $key - атрибут для сортировки
     * @param $get -  массив данных о текущей сортировке данных
     * @return mixed - преобразованный массив данных для сортировки
     */
    public static function calcSortURLArr ($key, $get)
    {
        if (isset($get['sort'])) {
            if ($get['sort'] === $key) {
                if (isset($get['sorttype']))
                    $get['sorttype'] = ($get['sorttype'] === 'asc') ? 'desc' : 'asc';
                else $get['sorttype'] = 'asc';
            } else $get['sorttype'] = 'asc';
        } else $get['sorttype'] = 'asc';
        $get['sort'] = $key;
        return $get;
    }

    /**
     * Проверить пересечение отрезков a и b
     * @param $a_start - начало отрезка a
     * @param $a_end - конец отрезка a
     * @param $b_start - начало отрезка b
     * @param $b_end - конец отрезка b
     * @return bool
     */
    public static function intervalCrossing ($a_start, $a_end, $b_start, $b_end)
    {
        return $a_start <= $b_end && $a_end >= $b_start;
    }

    /**
     * Проверить пересечение значения либо диапазона var1 с одним из диапазонов массива var2
     * @param $var1 - массив типа ['from' => 0, 'to' => 2324242] либо значение Integer
     * @param $var2 - массив интервалов [ ['from', 'to'], ['from', 'to'], ... ]
     * @return bool
     */
    public static function intervalMultiCrossing ($var1, $var2)
    {
        $return = false;
        if (!is_array($var1))
            $var1 = [
                'from' => $var1,
                'to' => $var1
            ];
        foreach ($var2 as $interval)
            if (self::intervalCrossing($var1['from'], $var1['to'], $interval['from'], $interval['to']))
                $return = true;

        return $return;
    }

    /**
     * Выполнить переадресацию
     * @param $link - ссылка
     */
    public static function location ($link) {
        header("Location: " . $link);
    }

    /**
     * Получить ссылку для кнопки назад
     * @param $default - ссылка по умолчанию
     * @return string - ссылка для кнопки назад
     */
    public static function locationGoBack ($default) {
        global $_GET;
        if (isset($_GET['backto']))
            return urldecode($_GET['backto']);
        else return $default;
    }

    /**
     * Добавить к ссылке постфикс для кнопки "назад"
     * @param $link - сырая ссылка
     * @return string -  ссылка с постфиксом
     */
    public static function locationGoBackCreateURL ($link)
    {
        global $_SERVER;
        $link .= strpos($link, '?') ? '&' : '?';
        return $link . 'backto=' . urlencode($_SERVER['REQUEST_URI']);
    }

    /**
     * Получить идентификаторы объектов, встроенные в параметр name тега Input при отправке методом Post
     * @param $arr - массив Post
     * @param $prefix - префикс к идентификатору, например person_1, где person_ - префикс
     * @return array - массив идентификаторов
     */
    public static function getIdFromInputName ($arr, $prefix)
    {
        $return = [];
        $prefixlen = strlen($prefix);
        foreach ($arr as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $return[] = intval(substr($key, $prefixlen));
            }
        }
        return $return;
    }

    /**
     * Преобразовать состояние чек бокса в целое число
     * @param string $val - строка типа "on"
     * @return int - целое число 1 или 0
     */
    public static function parseCheckBox ($val)
    {
        return $val === 'on' ? 1 : 0;
    }

    /**
     * Преобразовать данные в  формат чек бокса
     * @param int - целое число 1 или 0
     * @return string $val - строка типа "on"
     */
    public static function parseToCheckBox ($val)
    {
        return $val ? 'on' : 'off';
    }

    /**
     * Получить имя файла из пути к нему
     * @param $path - путь
     * @return array|mixed - имя файла
     */
    public static function getFileNameFromPath ($path)
    {
        $pathArr = explode('/', $path);
        $pathArr = end($pathArr);
        return $pathArr;
    }

    /**
     * Загрузить файл на сервер
     * @param $file - массив данных о загруженном файле
     * @param $dir - путь к месту хранения файла
     * @param null $name - желаемое имя файла
     * @return bool|mixed|string|null - название загруженного файла в случае успеха
     */
    public static function uploadFile ($file, $dir, $name = null)
    {
        global $sysInfo;

        if ($file['size'] <= $sysInfo['upload_max_filesize'] && $file['size'] <= $sysInfo['post_max_size']) {

            if ($name !== null) {
                $ext = self::ext($file['name']);
                $name .= '.' . $ext;
            } else {
                $name = $file['name'];
            }

            if (file_exists($dir . $name))
                return null;

            $res = @move_uploaded_file($file['tmp_name'], $dir . $name);
            if ($res) {
                return $name;
            } else return false;

        } else return false;
    }

    /**
     * Удалить файл
     * @param $path - строка путь к файлу
     * @return bool|null
     *          true - файл удален
     *          false - файл не удален
     *          null - файла не существует
     */
    public static function deleteFile ($path)
    {
        if (file_exists($path))
            return unlink($path);
        return null;
    }

    /**
     * Получить тип файла в формате "exe" по названию
     * @param $filename - имя файла
     * @return array|bool|mixed
     *          string - тип
     *          false - тип не указан
     */
    public static function ext ($filename)
    {
        $ext = explode('.', strtolower($filename));
        if (count($ext) > 1) {
            $ext = end($ext);
            return $ext;
        } else return false;
    }

    /**
     * Преобразовать размер файла в биты
     * @param $val - строка типа "2056mb"
     * @return int - количество битов
     */
    public static function return_bytes ($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = intval($val);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

    /**
     * Преобразовать биты в размер файла
     * @param $size - кол-во бит
     * @return string - строка типа "2 Gb"
     */
    public static function sizetostr ($size)
    {
        $kb = 1024;
        $mb = 1024 * $kb;
        $gb = 1024 * $mb;
        if ($size >= $kb) {
            if ($size >= $mb) {
                if ($size >= $gb) {
                    return intval($size / $gb).' Gb';
                } else return intval($size / $mb).' Mb';
            } else return intval($size / $kb).' Kb';
        } else return $size.' bytes';
    }

    /**
     * Выполнить поиск по массиву данных. В случае остутсвия совпадений элемент массива удаляется
     * @param $data - массив данных типа [п-ый номер][атрибут]
     * @param $text - искомый текст
     * @param $attr_list - список атибутов, по которым производится поиск
     * @return mixed - массив данных
     */
    public static function search ($data, $text, $attr_list, $register = false)
    {
        $text = trim(strval($text));
        if ($text !== '') {
            if (!$register)
                $text = mb_strtolower($text, 'UTF-8');
            foreach ($data as $key => $item) {
                $unset = true;
                foreach ($item as $item_key => $item_val) {
                    if (in_array($item_key, $attr_list)) {
                        $item_val = strval($item_val);
                        if (!$register)
                            $item_val = mb_strtolower($item_val, 'UTF-8');
                        if (mb_strpos($item_val, $text, 0, 'UTF-8') !== false)
                            $unset = false;
                    }
                }
                if ($unset)
                    unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Сформировать строку переменных URL из массива данных
     * @param $get - массив данных типа ключ => значение
     * @return string - строка типа ?ключ=значение&ключ2=значение2
     */
    public static function constructGETvarString ($get)
    {
        $get_str = '?';
        foreach ($get as $key=>$value)
            $get_str .= $key . '=' .urlencode($value) .'&';
        return substr($get_str, 0, strlen($get_str) - 1);
    }

    /**
     * Отсортировать массив данных по атрибуту
     * @param array $data - массив данных типа [№пп][атрибут] = значение
     * @param $key - атрибут для сортировки
     * @param string $type - правило сортировки
     * @return array - отсортировынный массив
     */
    public static function sort ($data, $key, $type = 'asc')
    {
        if (count($data)) {
            foreach ($data as $attr => $item) {
                foreach ($item as $item_key => $item_val) {
                    $itemReverse[$item_key][$attr] = $item_val;
                }
            }

            $arr_s = ['asc' => SORT_ASC, 'desc' => SORT_DESC];
            array_multisort($itemReverse[$key], $arr_s[$type], $data);
        }
        return $data;
    }

    /**
     * Преобразовать массив данных в строку для SQL запроса
     * @param $arr - массив типа [№пп] = значение
     * @return string|null
     *          string - строка типа "1,2,4"
     *          null - массив не содержит данных
     */
    public static function convertArrToSqlStr ($arr)
    {
        $sqlIN = '';
        if (count($arr)) {
            foreach ($arr as $item)
                $sqlIN .= $item . ',';
            return substr($sqlIN, 0, strlen($sqlIN) - 1);
        } return null;
    }

    /**
     * Получить массив выходных дней в месяце
     * @param $year - год
     * @param $month - месяц
     * @return array - массив типа [день] = 1|0
     */
    public static function getMonthOutputDays($year, $month)
    {
        $month_days_count = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $output = [];
        $time_start = strtotime($year . '-' . System::i2d($month) . '-01');
        for ($day = 1; $day <= $month_days_count; $day++) {
            $date = getdate($time_start + ($day - 1) * System::TIME_DAY);
            $output[$day] = ($date['weekday'] === 'Saturday' || $date['weekday'] === 'Sunday') ? 1 : 0;
        }
        return $output;
    }

    /**
     * Сформировать строку объявления переменной в JS из PHP в зависимости от ее дипа
     * @param $var
     * @return bool|string
     */
    public static function php2jsVarFormat ($var) {
        $type = gettype($var);
        if ($type === 'string') return '"' . $var . '"';
        if (in_array($type, ['double', 'integer'])) return $var;
        if ($type === 'boolean') return ($var) ? 'true' : 'false';
        return false;
    }

    /**
     * Превратить переменную PHP в JS стоку для объявления как переменной
     * @param $name - название переменной в JS
     * @param $value - значение
     * @return string - строка типа "var example = 2;"
     */
    public static function php2js ($name, $value, $var = true, $arrayonly = false)
    {
        $var = ($var && !$arrayonly) ? 'var ' : '';
        $arrayonly_start = (!$arrayonly) ? $name . ' = ' : '';
        $arrayonly_end = (!$arrayonly) ? ';' : '';
        return (is_array($value)) ? $var . $arrayonly_start . self::php2jsArray($value) . $arrayonly_end : $var . $arrayonly_start . self::php2jsVarFormat($value) . $arrayonly_end;
    }

    /**
     * Превратить массив PHP в JS стоку для объявления как переменной (массива)
     * @param $arr
     * @return bool|string
     */
    public static function php2jsArray ($arr)
    {
        if (is_array($arr)) {
            $str = '{';
            if (count($arr)) {
                foreach ($arr as $key => $value)
                    $str .= self::php2jsVarFormat($key) . ':' . self::php2jsArray($value) . ',';
                $str = substr($str, 0, strlen($str) - 1);
            }
            return $str . '}';
        } else return self::php2jsVarFormat($arr);
    }

    /**
     * Удалить точки из массива, который формурует scandir
     * @param $files - массив названий файлов или директорий
     * @return mixed - очищенный массив
     */
    public static function deleteScandirTo4ki (&$files)
    {
        foreach ($files as $key => $file)
            if ($file === '.' || $file === '..')
                unset($files[$key]);
        return $files;
    }

    /**
     * Сравнить какой из файлов более свежий
     * @param $file1
     * @param $file2
     * @return bool|null
     *          true - первый новее
     *          false - второй новее
     *          null - одинаковые
     */
    public static function compareFilemtime ($file1, $file2)
    {
        $version1 = filemtime($file1);
        $version2 = filemtime($file2);
        if ($version1 > $version2)
            return true;
        else if ($version1 < $version2)
            return false;
        else return null;
    }

    /**
     * Рекурсивное удаление директории со всеми дочерними элементами
     * @param $dir
     */
    public static function recursiveRemoveDir($dir) {
        $includes = new FilesystemIterator($dir);
        foreach ($includes as $include) {
            if(is_dir($include) && !is_link($include)) {
                self::recursiveRemoveDir($include);
            } else {
                unlink($include);
            }
        }
        rmdir($dir);
    }

    /**
     * Скопировать директорию вместе с дочерними файлами
     * @param $from
     * @param $to
     */
    public static function copyFullDir ($from, $to)
    {
        if (is_dir($from)) {
            @mkdir($to);
            $d = dir($from);
            while (false !== ($entry = $d->read())) {
                if ($entry == "." || $entry == "..") continue;
                self::copyFullDir("$from/$entry", "$to/$entry");
            }
            $d->close();
        }
        else copy($from, $to);
    }

    /**
     * Проверить является ли файл ZIP архивом
     * @param $dir
     * @param $file
     * @return bool
     */
    public static function is_zip ($dir, $file)
    {
        if (is_file($dir . $file)) {
            $file = strtolower($file);
            $exp = explode('.', $file);
            return end($exp) === 'zip';
        }
        return false;
    }

    /**
     * Преобразовать ФИО в формат Иванов И.И.
     * @param $fio
     * @return string
     */
    public static function shortFio ($fio)
    {
        $arr = explode(' ', $fio);
        $res = mb_strtoupper(mb_substr($arr[0], 0 ,1, 'UTF-8'), 'UTF-8') . mb_substr($arr[0], 1, null, 'UTF-8');
        $res .= (isset($arr[1])) ? ' ' . mb_strtoupper(mb_substr($arr[1], 0,1, 'UTF-8'), 'UTF-8') . '.' : '';
        $res .= (isset($arr[2])) ? mb_strtoupper(mb_substr($arr[2], 0,1, 'UTF-8'), 'UTF-8') . '.' : '';
        return $res;
    }

    /**
     * Проверить соответсвие фамилии, имени либо отчества шаблону
     * только русские либо укр символы
     * поддержка двойного имени через - , но не через пробел
     * @param $fio - Иванов
     * @return false|int
     */
    public static function checkFio ($fio)
    {
        //return preg_match("/^[a-zA-Zа-яА-Я]+$/ui", $fio); - упрощенный вариант, не поддерживает двойной фамилии
        return preg_match("/^(([a-zA-Z-]{1,20})|([а-яА-ЯЁёІіЇїҐґЄє-]{1,20}))$/u", $fio);
    }

    /**
     * Проверить, является ли строка E-mail
     * @param $email
     * @return false|int
     */
    public static function checkEmail ($email)
    {
        return preg_match('/^((([0-9A-Za-z]{1}[-0-9A-z\.]{1,}[0-9A-Za-z]{1})|([0-9А-Яа-я]{1}[-0-9А-я\.]{1,}[0-9А-Яа-я]{1}))@([-A-Za-z]{1,}\.){1,2}[-A-Za-z]{2,})$/u', $email);
    }

    /**
     * Проверить, является ли строка номером телефона
     * @param $telephone
     * @return false|int
     */
    public static function checkTelephone ($telephone)
    {
        return preg_match('/^(\+?\d+)?\s*(\(\d+\))?[\s-]*([\d-]*)$/', $telephone);
    }

    /**
     * Поменять местами значения числовых переменных
     * @param $number1 - число 1
     * @param $number2 - число 2
     * @param null $rule - правило
     */
    public static function replaceNumbers (&$number1, &$number2, $rule = null)
    {
        $number1_clone = $number1;
        $number2_clone = $number2;
        switch ($rule) {
            case 'asc': // в порядке возрастания (число 1 меньше числа 2)
                $number1 = ($number1 < $number2) ? $number1_clone : $number2_clone;
                $number2 = ($number1 < $number2) ? $number2_clone : $number1_clone;
                break;
            case 'desc': // в порядке убывания (число 1 больше числа 2)
                $number1 = ($number1 > $number2) ? $number1_clone : $number2_clone;
                $number2 = ($number1 > $number2) ? $number2_clone : $number1_clone;
                break;
            case null: // поменять местами
                $number1 = $number2_clone;
                $number2 = $number1_clone;
                break;
        }
    }

    /**
     * Получить массив с элементами в обратном порядке
     * @param $arr
     * @return array
     */
    public static function aroundArray($arr)
    {
        $return = [];
        foreach ($arr as $value)
            array_unshift($return, $value);
        return $return;
    }

    /**
     * Правило проверки атрибутов для метода getData
     * @param $key
     * @param $colums
     * @return bool
     */
    public static function chColum ($key, $colums)
    {
        if (count($colums))
            return System::value_crossing($key, $colums);
        else return true;
    }

    /**
     * Добавить / изменить значения cookie
     * @param $cookie - массив данных
     * @example ["id" => 1, "key" => "user"]
     */
    public static function setCookie ($cookie)
    {
        foreach ($cookie as $key => $value)
            setcookie ($key, $value);
    }

    /**
     * Сформировать HTML представление элемента select
     * @param $list - массив для списка
     * @example [
     *      [
     *          'id' => 1,
     *          'title' => 'Кот',
     *          'selected' => true,
     *      ],
     *      [...]
     * ]
     *
     * @param null $name - артибут name для select
     * @param null $class - атрибут class для select
     * @param array $attr - массив произвольных атрибутов по типу ['onclick' => 'form_submit()' , ...]
     * @return string - html
     */
    public static function getHtmlSelect ($list, $name = null, $class = null, $attr = [])
    {
        $name = $name === null ? '' : 'name="' . $name . '"';
        $class = $class === null ? '' : 'class="' . $class . '"';
        $any_attributes = '';
        if (count($attr)) {
            foreach ($attr as $key => $value)
                $any_attributes .= $key . '="' . $value . '" ';
        }

        return '<select ' . $name . ' ' . $class . ' ' . $any_attributes . '>' . self::getHtmlSelectOptions($list) . '</select>';
    }

    /**
     * Сформировать HTML представление массива значений option для элемента select
     * @param $list - массив для списка
     * @example [
     *      [
     *          'id' => 1,
     *          'title' => 'Кот',
     *          'selected' => true,
     *      ],
     *      [...]
     * ]
     * @return string - html
     */
    public static function getHtmlSelectOptions ($list)
    {
        $html = '';
        foreach ($list as $item) {
            if (isset($item['selected']))
                $selected = $item['selected'] ? 'selected' : '';
            else $selected = '';
            $html .= '<option value="' . $item['id'] . '" ' . $selected . '>' . $item['title'] . '</option>';
        }
        return $html;
    }

    /**
     * Обработка массива pagesGroup при формировании меню
     * @param $keys
     * @return string
     */
    public static function calcPagesGroup ($keys)
    {
        global $pagesGroup;
        foreach ($keys as $key) {
            if (in_array($key, $pagesGroup)) {
                $pagesGroup = [];
                return 'active';
            }
        }
        return '';
    }

}