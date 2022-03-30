    var showConsoleLogs = true; // Отображать в консоли промежуточные данные выполнения функций


    /**
     * Отложенное отображение содержимого страницы только по завершению ее загрузки
     */
    $(document).ready(function () {
        $('.pace-background').delay(300).fadeOut('slow');
    });

    function showSysMessage(text, type) {
        var color;
        var autohide;
        var delay;
        switch (type) {
            case 'ok': color = '#28a745'; autohide = true; delay = 3000; break;
            case 'mess': color = '#d6d6d6'; autohide = true; delay = 5000; break;
            case 'warning': color = '#a2a96b'; autohide = true; delay = 5000; break;
            case 'access': color = '#0016DC'; autohide = true; delay = 8000; break;
            case 'error': color = '#dc0000'; autohide = false; delay = 0; break;
        }
        Toast.add({
            text: text,
            color: color,
            autohide: autohide,
            delay: delay
        });
    }

    /**
     * Выполнение ajax запроса
     * @param key - идентификатор отправляемого запроса
     * @param data - переменная, которая содержит передаваемый набор данных
     * @param responseHandler - функция обработки ответа сервета
     * @param serverScript - скрипт, обрабатывающий запрос на стороне сервера
     * @example
            ajaxSend ('hello', 'world', function (key, data, response) {
                console.log(response);
            });
     */
	function ajax (key, data, responseHandler, serverScript) {
        serverScript = serverScript || 'ajax.php';
        jQuery.ajax({
            type: 'POST',
            url: serverScript,
            data: {
                key: key,
                data: data
            },
            success: function( respond, status, jqXHR ){
                responseHandler(key, data, respond);
            },
            error: function( jqXHR, status, errorThrown ){
                if (showConsoleLogs)
                    console.log( 'ОШИБКА AJAX запроса: ' + status, jqXHR );
            }
        });
    }

    /**
     * Выравнивание блока с фиксированым позиционированием по центру видимой области
     * @todo Иногда косячит, если что
     *
     * @param ellLink - дескриптор объекта, который необходимо отцентрировать
     * @param maxHeight - ограничение максимальной высоты объекта (px)
     * @param maxWidth - ограничение максимальной ширины объекта (px)
     * @param reservedHeight - оставить свободное пространство сверху и снизу от объекта (px)
     * @param reservedWidth - оставить свободное пространство слева и справа от объекта (px)
     */
    function centerMyBlock(ellLink, maxHeight, maxWidth, reservedHeight, reservedWidth)
    {
        outerWidth = $(ellLink).outerWidth();
        outerHeight = $(ellLink).outerHeight();
        maxWidth = maxWidth || outerWidth;
        maxHeight = maxHeight || outerHeight;
        currentHeight = $(ellLink).height();
        currentWidth = $(ellLink).width();
        reservedHeight = reservedHeight || 0;
        reservedWidth = reservedWidth || 0;

        windowWidth = $(window).width();
        windowHeight = $(window).height();

        if (windowHeight > maxHeight + reservedHeight)
            NEWheight = currentHeight;
        else NEWheight = windowHeight - reservedHeight - (outerHeight - currentHeight);
        NEWtop = Math.round((NEWheight + reservedHeight)/2);

        if (windowWidth > maxWidth + reservedWidth)
            NEWwidth = currentWidth;
        else NEWwidth = windowHeight - reservedWidth - (outerWidth - currentWidth);
        NEWleft = Math.round((NEWwidth + reservedWidth)/2);

        $(ellLink)
            .css('height', NEWheight + 'px')
            .css('width', NEWwidth + 'px')
            .css('top', 'calc(50% - '+ NEWtop +'px)')
            .css('left', 'calc(50% - '+ NEWleft +'px)');

        if (showConsoleLogs)
            console.log('Был отцентрирован блок: \n' +
                ' макс. ширина = ' + maxWidth + 'px\n' +
                ' макс. высота = ' + maxHeight + 'px\n' +
                ' резерв высоты = ' + reservedHeight + 'px\n' +
                ' резерв ширины = ' + reservedWidth + 'px\n' +
                ' итоговая ширина = ' + NEWwidth + 'px\n' +
                ' итоговая высота = ' + NEWheight + 'px'
            );
    }

    /**
     * Отобразить полупрозрачное затемнение всей видимой области экрана
     * @param zindex - желаемое значение z-index тени (работает как идентификатор)
     * @param event - событие при нажании
     * @example - Создание тени, с последующим удалением по клику
            shadowNew(490, function () {
                shadowRemove(490);
            });
     */
    function shadowNew (zindex, event) {
        $('body').append('<div class="shadow shadow' + zindex +'" data-zindex="' + zindex + '" style="display:block; z-index: ' + zindex + ';" ></div>');
        $('.shadow' + zindex).on( "click",function (e) {event();});
    }

    /**
     * Удалить полупрозрачное затемнение всей видимой области экрана
     * @param zindex - значение z-index тени (работает как идентификатор)
     */
    function shadowRemove(zindex) {
        $('.shadow' + zindex).remove();
    }

    /**
     * Получить все данные с формы в виде массива
     * Поддерживаемые типы полей ввода:
     *      input,
     *      date,
     *      checkbox,
     *      select,
     *      textarea
     * @param ell - родительский элемент (jQ дескриптор)
     * @returns {{}|*}
     */
    function form_get_data_all (ell) {
        data = {};

        $(ell).find('input').each(function () {
            iname = $(this).attr('name');
            itype = $(this).attr('type');
            if (itype === 'checkbox')
                value = $('input[name=' + iname + ']').is(':checked') ? 1 : 0;
            else value = $('input[name=' + iname + ']').val();
            data[iname] = value;
        });

        $(ell).find('select').each(function () {
            iname = $(this).attr('name');
            value = $('select[name=' + iname + '] option:selected').val();
            data[iname] = value;
        });

        $(ell).find('textarea').each(function () {
            iname = $(this).attr('name');
            value = $('textarea[name=' + iname + ']').val();
            data[iname] = value;
        });

        return data;
    }

    /**
     * Получить индекс (целое число) после префикса из строки (имени элемента)
     *
     * @param sname - имя элемента
     * @example serial_number_64
     *
     * @param prefix - префикс
     * @example serial_number
     *
     * @returns {null|number}
     * @example 64
     */
    function get_index_after_prefix (sname, prefix) {
        if (sname.indexOf(prefix) === 0 && sname.length > prefix.length) {
            index = sname.substring(prefix.length);
            return parseInt(index);
        }
        return null;
    }

    /**
     *  Проверить является ли объект ell2 объектом ell1 либо его дочерним
     */
    function is_or_has(ell1, ell2) {
        if ($(ell1).is(ell2)) return 1; // тот же элемент
        if ($(ell1).has(ell2).length !== 0) return 2; // дочерний
        return 0; // не найден
    }


    /** Global
     * Удалить все варианты в select кроме варианта по умолчанию
     * @param select - дескриптор элемента форма типа select
     * @param default_value - значение по умолчанию, которое не будет удалено
     * @param integer - true - если значение value целое число, false - если текст
     */
    function select_delete_options (select, default_value = 0, integer = true) {
        $(select).children('option').each(function () {
            value = $(this).attr('value');
            if (integer)
                value = parseInt(value);
            if (value !== default_value)
                $(this).remove();
        });
    }

    /** Global
     * Удалить все соседние элементы типа select, data-index которых выше указанного значения
     * @param select - дескриптор элемента форма типа select
     * @param index - значение data-index, по которому будет выполнено сравнение
     */
    function select_delete_after_index (select, index) {
        parent = $(select).parent();
        $(parent).children('select').each(function () {
            tmp_index = parseInt($(this).data('index'));
            if (tmp_index > index)
                $(this).remove();
        });
    }

    /**
     * Удалить все соседние элементы типа select, в name которых index выше чем у заданного select
     * @param select - дескриптор элемента форма типа select
     * @param prefix - префикс в имени искомых select, по которому будет выполнен поиск и дальнейшее сравнение постфикса типа integer
     */
    function select_delete_after_name_index  (select, prefix = '') {
        sname = $(select).attr('name');
        if (prefix !== '')
            select_index = get_index_after_prefix(sname, prefix);
        else select_index = parseInt(sname);

        parent = $(select).parent();
        $(parent).find('select').each(function () {
            sname = $(this).attr('name');
            if (prefix !== '')
                index = get_index_after_prefix(sname, prefix);
            else index = parseInt(sname);

            if (index !== null) {
                if (index > select_index)
                    $(this).remove();
            }
        });
    }

    /** Global
     *  Получить массив данных из полей ввода внутри блока
     *  @todo Более не использовать, так как есть form_get_data_all тут же выше
     *  @todo Не обрабатывает textarea
     *  @todo Не работает с параметрами name по типу name[]
     */
    function getFormData (block) {
        data = {};
        $(block).find('input').each(function () {
            iname = $(this).attr('name');
            itype = $(this).attr('type');
            if (itype === 'checkbox')
                value = $('input[name=' + iname + ']').is(':checked') ? 1 : 0;
            else value = $('input[name=' + iname + ']').val();
            data[iname] = value;
        });
        $(block).find('select').each(function () {
            iname = $(this).attr('name');
            value = $('select[name=\'' + iname + '\'] option:selected').val();
            data[iname] = value;
        });
        return data;
    }

    /** Global
     * Вывести в консоль содержымое массива
     * @todo только для одномерных массивов
     * @param array
     */
    function console_var_dump_array (array) {
        $.each(data, function(index, value){
            console.log("INDEX: " + index + " VALUE: " + value);
        });
        console.log("--- END ARRAY ---");
    }