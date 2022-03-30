    /**
     * Отложенное отображение содержимого страницы только по завершению ее загрузки
     */
    $(document).ready(function () {
        questionBoxKeyChecker();
        messageBoxKeyChecker();
    });

    /**
        Отображение окна с сообщением и единственной кнопкой "ОК"
        @param {string} text - текст сообщения
        @param {function} event - функция, выполняемая после нажатия на кнопку "ОК"
        @use
            centerMyBlock - для расположения в центре видимой области окна с сообщением
        @example - Создание окна с сообщением и тенью. Удаление окна и тени осуществляется как по клику на "ОК", так и по всей области тени

            shadowNew(490, function () {
                $('.messageBox').remove();
                shadowRemove(490);
            });
            showMessage('Внимание. После удаления файла необходимо обновить страницу', function () {
                $('.messageBox').remove();
                shadowRemove(490);
            });
    */
    function showMessage (text, event) {
        $('body').append('<div class="messageBox"><div class="text">' + text + '</div><div class="buttonsLine"><div class="bt ok">ОК</div></div></div>');
        $('.messageBox').children('.buttonsLine').children('.ok').on( "click",function (e) {event();});
        centerMyBlock($('.messageBox'));
    }

    /**
     * Подключить к messageBox реакцию на нажатие клавиш клавиатуры
     */
    function messageBoxKeyChecker() {
        $(window).keydown(function (event) {
            if ($('.messageBox').length) {
                switch (event.which) {
                    case 13:    // Enter
                    case 27:    // Esc
                        $('.messageBox').children('.buttonsLine').children('.ok').click();
                        break;
                }
            }

        });
    }

    /**
        Отображение окна с вопросом и двумя вариантами ответа "Да" и "Нет"
        @param {string} text - текст вопроса
        @param {function} event_yes - функция, выполняемая после нажатия на кнопку "Да"
        @param {function} event_no - функция, выполняемая после нажатия на кнопку "Нет"
        @use
            centerMyBlock - для расположения в центре видимой области окна с сообщением
        @example - Создание окна с вопросом и тенью. Удаление окна и тени осуществляется как по клику на "Да" и "Нет", так и по всей области тени

            shadowNew(490, function () {
                $('.questionBox').remove();
                shadowRemove(490);
            });
            showQuestion(
                'Внимание. После удаления файла необходимо обновить страницу',
                function () {
                    $('.questionBox').remove();
                    shadowRemove(490);
                },
                function () {
                    $('.questionBox').remove();
                    shadowRemove(490);
                }
            );
    */
    function showQuestion (text, event_yes, event_no) {
        $('body').append('<div class="questionBox"><div class="text">' + text + '</div><div class="buttonsLine"><div class="bt no">Нет</div><div class="bt yes">Да</div></div></div>');
        $('.questionBox').children('.buttonsLine').children('.yes').on( "click",function (e) {event_yes();});
        $('.questionBox').children('.buttonsLine').children('.no').on( "click",function (e) {event_no();});
        centerMyBlock($('.questionBox'));
    }

    /**
     * Подключить к questionBox реакцию на нажатие клавиш клавиатуры
     */
    function questionBoxKeyChecker() {
        $(window).keydown(function (event) {
            if ($('.questionBox').length) {
                switch (event.which) {
                    case 13:    // Enter
                        $('.questionBox').children('.buttonsLine').children('.yes').click();
                        break;
                    case 27:    // Esc
                        $('.questionBox').children('.buttonsLine').children('.no').click();
                        break;
                }
            }
        });
    }