
function formResetError(form) {
    form = form || '.form';
    $(form).find('.title').css('background-color', '');
    $(form).find('.hint').removeClass('error');
}

function formError(ell) {
    $(ell).children('.title').css('background-color', '#F00');
    formShowHint(ell, true);
}

function formBlockOpen(id) {
    $('#'+id).children('.itemBody').slideToggle();
}

/*=============================== Hint ===========================*/
function formShowHint(ell, error) {
    hint = $(ell).children('.hint');
    formHideHints(hint);
    if (error)
        $(hint).addClass('error');
    $(hint).slideDown();
}

function formHideHints(hint){
    $('.hint').each(function () {
        if($(this).css('display') === 'block')
            if(!$(this).is($(hint)))
                $(this).slideUp();
    });
}
