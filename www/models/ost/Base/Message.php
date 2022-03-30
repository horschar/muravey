<?php

namespace OsT\Base;

/**
 * Работа с сообщениями системы
 * Class Message
 * @package OsT\Base
 * @version 2022.03.10
 *
 * __construct      Message constructor
 * push             Добавить сообщение в сессию
 * toJS             Сформировать код JS для отображения сообщения
 * getAll           Сформировать HTML код для отображения всех сообщений
 * genNewIndex      Определить индекс нового элемента в массиве сессии
 * deleteAll        Удалить все сообщения из массива сессии
 *
 */
class Message
{
    public $index;
    public $text;
    public $type;

    const TYPE_OK = 'ok';
    const TYPE_MESSAGE = 'mess';
    const TYPE_WARNING = 'warning';
    const TYPE_ACCESS = 'access';
    const TYPE_ERROR = 'error';
    const SESSION_NAME = 'messages';

    /**
     * Message constructor.
     * @param $text
     * @param string $type
     */
    public function __construct ($text, $type = self::TYPE_OK)
    {
        $this->text = $text;
        $this->type = $type;
        $this->index = self::genNewIndex();
        $this->push();
    }

    /**
     * Добавить сообщение в сессию
     */
    public function push ()
    {
        global $_SESSION;
        $_SESSION[self::SESSION_NAME][$this->index] = ['text' => $this->text, 'type' => $this->type];
    }

    /**
     * Сформировать код JS для отображения сообщения
     * @param $text - текст сообщения
     * @param $type - тип
     * @return string - JS код
     */
    public static function toJS ($text, $type)
    {
        return ' showSysMessage (\'' . $text . '\', \'' . $type . '\');';
    }

    /**
     * Сформировать HTML код для отображения всех сообщений
     * @return string
     */
    public static function getAll ()
    {
        $str = '<script>$(function () {';
        global $_SESSION;
        if (isset($_SESSION[self::SESSION_NAME]) && is_array($_SESSION[self::SESSION_NAME]))
            foreach ($_SESSION[self::SESSION_NAME] as $message)
                $str .= self::toJS($message['text'], $message['type']);
        $str .= '});</script>';
        self::deleteAll();
        return $str;
    }

    /**
     * Определить индекс нового элемента в массиве сессии
     * @return int
     */
    public static function genNewIndex ()
    {
        global $_SESSION;
        $index = (isset($_SESSION[self::SESSION_NAME]) && is_array($_SESSION[self::SESSION_NAME])) ? count($_SESSION[self::SESSION_NAME]) : 0;
        return $index;
    }

    /**
     * Удалить все сообщения из массива сессии
     */
    public static function deleteAll ()
    {
        global $_SESSION;
        unset($_SESSION[self::SESSION_NAME]);
    }

}