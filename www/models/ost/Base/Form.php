<?php

namespace OsT\Base;

/**
 * Формы
 * Class Form
 * @package OsT\Base
 * @version 2022.03.10
 *
 * getItemSelect                    Сформировать HTML выпадающего списка
 * getItemSelectOptions             Сформировать HTML значений для выпадающего списка
 * getInput                         Сформировать HTML элемента input
 * getItemText                      Сформировать HTML элемента типа текст
 * getItemTextSelect                Сформировать HTML элемента типа textSelect
 * getItemPassword                  Сформировать HTML элемента типа пароль
 * getItemDatetime                  Сформировать HTML элемента типа дата и время
 * getItemDate                      Сформировать HTML элемента типа дата
 * getItemTextarea                  Сформировать HTML элемента типа текстовое поле
 * getItemFile                      Сформировать HTML элемента типа текст
 * getItemLink                      Сформировать HTML элемента типа ссылка
 * getItemLabel                     Сформировать HTML элемента типа надпись
 * getItemBlock                     Сформировать HTML элемента типа блок
 * getItemFileButton                Сформировать HTML кнопки для элемента типа файл
 * getHint                          Сформировать HTML подказки
 * getCheckBox                      Сформировать HTML элемента типа чекбокс
 * getDefaultSelectVal              Получить массив для формирования значения по-умолчанию в элементе выпадающий список
 *
 */
class Form
{
    /**
     * Сформировать HTML выпадающего списка
     * @param $title - название
     * @param $name - имя select
     * @param $data - массив данных типа [№пп][id, title]
     * @param null $value - выбранное значение
     * @param null $hint - текст подсказки
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param bool $design - необходимость формирования дополнительной структуры для формы
     * @return string - HTML строка
     */
    public static function getItemSelect ($title, $name, $data, $value = null, $hint = null, $options = null, $design = true)
    {
        if ($hint) {
            $hint = self::getHint($hint);
        }

        $optionsStr = '';
        if ($design) $options['onfocus'] = 'formShowHint($(this).parent().parent())';
        if ($options !== null)
            foreach ($options as $key => $val)
                $optionsStr .= ' '.$key.'="'.$val.'"';

        $html = '<select name="'.$name.'" '.$optionsStr.'>'
            .self::getItemSelectOptions($data, $value).
            '</select>';

        if ($design)
            $html = '
            <div class="item type_select">
                <div class="title">'.$title.'</div>
                <div class="itemBody">
                    ' . $html . '
                </div>
                '.$hint.'
            </div>';

        return $html;
    }

    /**
     * Сформировать HTML значений для выпадающего списка
     * @param $data - массив данных типа [id,title]
     * @param null $value - выбранное значение
     * @return string
     */
    public static function getItemSelectOptions ($data, $value = null)
    {
        $html = '';
        if (is_array($data)) {
            foreach ($data as $item) {
                $selected = '';
                if ($item['id'] == $value)
                    $selected = 'selected';
                $html .= '<option value="'.$item['id'].'" '.$selected.'>'.$item['title'].'</option>';
            }
        } else $html .= '<option value="0">Нет данных</option>';
        return $html;
    }

    /**
     * Сформировать HTML элемента input
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @return string
     */
    public static function getInput ($options = null)
    {
        $input = '<input';
        foreach ($options as $key => $val)
            $input .= ' '.$key.'="'.$val.'"';
        $input .= '>';
        return $input;
    }

    /**
     * Сформировать HTML элемента типа текст
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemText ($title, $options = null, $hint = null)
    {
        $options['type'] = 'text';
        @$options['class'] = 'input '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }
        $options['onfocus'] = 'formShowHint($(this).parent().parent())';

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody">
                '.self::getInput($options).'
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа textSelect
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemTextSelect ($title, $options = null, $hint = null)
    {
        $options['type'] = 'text';
        @$options['class'] = 'input '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }
        $options['onfocus'] = 'formShowHint($(this).parent().parent())';

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody">
                '.self::getInput($options).'
                <script>
                    TextSelect($("input[name=' . $options['name'] . ']"), "' . $options['name'] . '", ' . System::php2js('', $options['haystack'], false, true) . ', ' . System::php2js('', $options['value'], false, true) . ', "input", false);
                </script>
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа пароль
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemPassword ($title, $options = null, $hint = null)
    {
        $options['type'] = 'password';
        @$options['class'] = 'input '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }
        $options['onfocus'] = 'formShowHint($(this).parent().parent())';

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody">
                '.self::getInput($options).'
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа дата и время
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemDatetime ($title, $options = null, $hint = null)
    {
        $options['type'] = 'datetime-local';
        @$options['class'] = 'input '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }
        $options['onfocus'] = 'formShowHint($(this).parent().parent())';

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody">
                '.self::getInput($options).'
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа дата
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemDate ($title, $options = null, $hint = null)
    {
        $options['type'] = 'date';
        @$options['class'] = 'input '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }
        $options['onfocus'] = 'formShowHint($(this).parent().parent())';

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody">
                '.self::getInput($options).'
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа текстовое поле
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemTextarea ($title, $options = null, $hint = null)
    {
        @$options['class'] = 'textarea '.$options['class'];
        if ($hint) {
            $hint = self::getHint($hint);
        }

        $content = '<textarea class="textarea" name="'.$options['name'].'" >'.$options['value'].'</textarea>';
        return self::getItemBlock(
            $title,
            $options['name'],
            $content,
            $hint
        );
    }

    /**
     * Сформировать HTML элемента типа текст
     * @param $title - название
     * @param $buttons - HTML управляющих кнопок
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemFile ($title, $buttons, $options = null, $hint = null)
    {
        $options['type'] = 'file';
        $value = $options['value'];
        unset($options['value']);
        if ($hint) {
            $hint = self::getHint($hint);
        }

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody" onclick="formShowHint($(this).parent())">
                <span>'.$value.'</span>
                '.self::getInput($options).'
                <div class="buttonsBl">
                    '.$buttons.'
                </div>
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа ссылка
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemLink ($title, $options = null, $hint = null)
    {
        $options['type'] = 'link';
        if ($hint)
            $hint = self::getHint($hint);

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody" onclick="formShowHint($(this).parent())">
                <a href="'.$options['href'].'" target="_blank">'.$options['text'].'</a>
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа надпись
     * @param $title - название
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemLabel ($title, $options = null, $hint = null)
    {
        $options['type'] = 'label';
        if ($hint)
            $hint = self::getHint($hint);

        @$html = '
        <div class="item type_'.$options['type'].'" id="'.$options['name'].'">
            <div class="title">'.$title.'</div>
            <div class="itemBody" onclick="formShowHint($(this).parent())">
               ' .$options['text'].'
            </div>
            '.$hint.'
        </div>';
        return $html;
    }

    /**
     * Сформировать HTML элемента типа блок
     * @param $title - название
     * @param $id - желаемый идентификатор элемента
     * @param $content - HTML содержимое блока
     * @param null $hint - текст подсказки
     * @return string
     */
    public static function getItemBlock ($title, $id, $content, $hint = null, $options = null)
    {
        if ($hint) {
            $hint = self::getHint($hint);
        }
        if (!isset($options['openOnClick'])) $options['openOnClick'] = true;
        if (!isset($options['opened'])) $options['opened'] = false;

        if ($options['openOnClick']) $optionsStr['openOnClick'] = 'formBlockOpen(\''.$id.'\');';
        if (!$options['opened']) $optionsStr['opened'] = 'style="display:none;"';

        @$html = '
            <div class="item type_block" id="'.$id.'">
                <div class="title" onclick="'.$optionsStr['openOnClick'].' formShowHint($(this).parent())">'.$title.'</div>
                <div class="itemBody" '.$optionsStr['opened'].'>
                   '.$content.'
                </div>
                '.$hint.'
            </div>';
        return $html;
    }

    /**
     * Сформировать HTML кнопки для элемента типа файл
     * @param $type - тип кнопки
     * @param $event - событие по клику
     * @return string
     */
    public static function getItemFileButton ($type, $event)
    {
        return '<div class="button '.$type.'" onClick="'.$event.'"></div>';
    }

    /**
     * Сформировать HTML подказки
     * @param $text - текст подсказки
     * @return string
     */
    public static function getHint ($text)
    {
        return '<div class="hint">'.$text.'</div>';
    }

    /**
     * Сформировать HTML элемента типа чекбокс
     * @param $title - название
     * @param $name - имя input
     * @param null $options - массив дополнитьельных опций типа [название] = значение
     * @param null $value - значение по умолчанию
     * @return string
     */
    public static function getCheckBox ($title, $name, $options = null, $value = null)
    {
        $value = (boolval($value)) ? 'checked' : '';
        $optionsStr = '';
        if ($options !== null)
            foreach ($options as $key => $val)
                $optionsStr .= ' '.$key.'="'.$val.'"';

        @$html = '<div class="checkBoxBl">
                        <input class="checkBox" type="checkbox" name="'.$name.'" '.$value.' ' . $optionsStr . '>
                        <div class="checkBoxText">'.$title.'</div>
                    </div>';

        return $html;
    }

    /**
     * Получить массив для формирования значения по-умолчанию в элементе выпадающий список
     * @return array
     */
    public static function getDefaultSelectVal ()
    {
        return ['id' => 0, 'title' => '-- Не выбрано --'];
    }

}