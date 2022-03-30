//========================================================== TextSelect ================================================//
/*
	TextSelect - гибрид элемента input text и select, тоесть к обычному input типа text добавляется выпадающее меню с вариантами значений
	Для создания объекта необходимо создать в желаемом месте любой элемент, будь-то div или input

	Добавить JS код при полной загрузке страницы

	TextSelect(ell, name, data, value, addclass, inarrayonly);
	Где:
		ell - дескриптор заранее подготовленного объекта, взамен которого будет создан TextSelect
		name - название поля на латинице (должно быть уникальным)
		data - массив вариантов для выпадающего списка
		value - значение по умолчанию
		addclass - классы, которые необходимо добавить в input
		inarrayonly - не очищать значение Input только при молном соответствии с одним из предложенных вариантов знаечения

	Пример:
		TextSelect($('#textSelect_floor'), 'floor', [1,2,3,4,5], 1, 'input text', true);

</script>


 */
	var TextSelect_Arr={};
	TextSelect_query='';		// Активная строка запроса
	TextSelect_LastVal='';		// Состояние Input перед нажатием клавиши

	$(document).ready(function(e) {
		$(document).mouseup(function (e){ 						// событие клика по веб-документу для закрытия TextSelect
			$('.TextSelect').each(function(index, element) {
				if (!$(this).is(e.target) && $(this).has(e.target).length === 0){
					$(this).children('.TextSelectItemsBl').hide();
				}
			});
		});
	});

	function TextSelect(ell, name, data, value, addclass, inarrayonly) {
		inarrayonly = inarrayonly || false;
		var inarrayonly_str = '';
		if (inarrayonly)
			inarrayonly_str = ' data-inarrayonly="1" ';
		TextSelect_Arr[name] = data;
		if (value !== 0)
			value = value || '';
		$(ell).before('<div class="TextSelect"><input id="TextSelect_' + name + '" class="TextSelect_input ' + name + ' ' + addclass +'" data-item="' + name + '" name="' + name + '" value="' + value + '" ' + inarrayonly_str + ' autocomplete="off"><div class="TextSelectItemsBl"></div></div>');
		$(ell).remove();
		TextSelect_setFunctions($('#TextSelect_' + name));
	}

	function TextSelect_setFunctions (ellement)
	{
		$(ellement).blur(function(e) {				TextSelect_Blur(this);});
		$(ellement).keydown(function(e) {			TextSelect_KeyDown(this);});
		$(ellement).keyup(function(eventObject){	TextSelect_KeyUP(this);});			// Обработчик для input	нажатие клавиш на клаве
		$(ellement).click(function(e) {				TextSelect_Generate($(this).parent());}); // Обработчик для input клик
	}

	/**
	 * Функция при потере фокуса элементом input
	 * @param input - дескриптор объекта input
	 * @constructor
	 */
	function TextSelect_Blur(input) {
		// Проверить наличие запрета на неполное совпадение введенного текста с массивом разрешенных значений
		if (typeof $(input).attr('data-inarrayonly') !== "undefined") {
			if (TextSelect_CheckInArray($(input).parent(), 1) === -1)
				$(input).val('');
		}
	}

	function TextSelect_CheckInArray(TextSelect, registr){
		registr = registr || 0;
		art=$(TextSelect).children('.TextSelect_input').data('item');
		arr=TextSelect_Arr[art];
		val = $(TextSelect).children('.TextSelect_input').val();
		var status = -1;
		$.each(arr, function(index, value){
			itemText = value.toString();
			if(registr === 0){
				itemText = itemText.toLowerCase();
				val = val.toLowerCase();
			}
			if(itemText === val){
				status = index;
			}
		});
		return status;
	}

	function TextSelect_Generate(TextSelect){
		art=$(TextSelect).children('.TextSelect_input').data('item');
		arr=TextSelect_Arr[art];
		filter=$(TextSelect).children('.TextSelect_input').val();
		itemBl=$(TextSelect).children('.TextSelectItemsBl');
		str='';
		TextSelect_query=filter;
		TextSelect_LastVal=filter;
		$.each(arr, function(index, value){
			itemText = value.toString();
			if(filter === '' || (itemText.toLowerCase().indexOf(filter.toLowerCase()) > -1 && itemText !== filter))
				str+='<div class="TextSelectItem">'+itemText+'</div>';
		});
		$(itemBl).html(str);
		$(itemBl).children('.TextSelectItem').each(function(index, element) {
			$(this).mouseenter(function(e) {	TextSelect_Mouse('enter',this); });	// Обработчик для items наведения
			$(this).click(function(e) { 		TextSelect_Mouse('click',this); });	// Обработчик для items клика
		});
		$(itemBl).show();
	}

	function TextSelect_Mouse(Event,ell){
		if(Event=='enter'){
			$(ell).parent().children('.TextSelectItem').filter('.active').removeClass('active');
			$(ell).addClass('active');
		} else if(Event=='click'){
			$(ell).parent().siblings('input').val($(ell).text());
			$(ell).parent().siblings('input').change();
			$(ell).parent().hide();
			//TextSelect_Generate($(ell).parent().parent());
		}
	}

	function TextSelect_KeyDown(ell){
		key=event.keyCode;
		if(key==38 || key==40 || key==13 || key==9 || key==37 || key==39){
			// проверка активного выделения
			count=-1;
			ActiveItem=-1;
			ItemsBl=$(ell).siblings('.TextSelectItemsBl');
			$(ItemsBl).children('.TextSelectItem').each(function(index, element) {
				if($(this).hasClass('active')) ActiveItem=index;
				count++;
			});

			if(key==38){ 			// клавиша Вверх
				if(ActiveItem>=0){
					$(ItemsBl).children('.TextSelectItem:eq('+ActiveItem+')').removeClass('active');
					if(ActiveItem>0){
						$(ItemsBl).children('.TextSelectItem:eq('+(ActiveItem-1)+')').addClass('active');
						$(ell).val($(ItemsBl).children('.TextSelectItem:eq('+(ActiveItem-1)+')').text());
					} else $(ell).val(TextSelect_query);
				}
			} else if(key==40){	// клавиша Вниз
				if(count>=0){
					if(ActiveItem>=0){
						if(ActiveItem<count){
							$(ItemsBl).children('.TextSelectItem:eq('+ActiveItem+')').removeClass('active');
							$(ItemsBl).children('.TextSelectItem:eq('+(ActiveItem+1)+')').addClass('active');
							$(ell).val($(ItemsBl).children('.TextSelectItem:eq('+(ActiveItem+1)+')').text());
						}
					} else {
						$(ItemsBl).children('.TextSelectItem:eq(0)').addClass('active');
						$(ell).val($(ItemsBl).children('.TextSelectItem:eq('+(ActiveItem+1)+')').text());
					}
				}
			} else if(key==13 || key==9){ 	// клавиша Enter, Tab
				$(ItemsBl).hide();
			} else if(key==37 || key==39){	// клавишы Влево и Вправо
				TextSelect_Generate($(ell).parent());
			}

			TextSelect_LastVal=$(ell).val();
		}
	}

	function TextSelect_KeyUP(ell){
		if(TextSelect_LastVal!=$(ell).val()){
			TextSelect_query=$(ell).val();
			TextSelect_LastVal=$(ell).val();
			TextSelect_Generate($(ell).parent());
		}
	}

	/*
		Баги...
		Каретку отбивать в конец при нажатии кнопки вверх
	*/