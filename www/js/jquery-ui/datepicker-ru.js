/* French initialisation for the jQuery UI date picker plugin. */
/* Written by Keith Wood (kbwood{at}iinet.com.au),
			  Stéphane Nahmani (sholby@sholby.net),
			  Stéphane Raimbault <stephane.raimbault@gmail.com> */
( function( factory ) {
	if ( typeof define === "function" && define.amd ) {

		// AMD. Register as an anonymous module.
		define( [ "../widgets/datepicker" ], factory );
	} else {

		// Browser globals
		factory( jQuery.datepicker );
	}
}( function( datepicker ) {

datepicker.regional.ru = {
	closeText: "Закрыть",
	prevText: "Précédent",
	nextText: "Предыдущий",
	currentText: "Сегодня",
	monthNames: [ "Январь", "Февраль", "Март", "Апрель", "Май", " Июнь",
		"Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", " Декабрь" ],
	monthNamesShort: [ "Январь", "Февраль", "Март", "Апрель", "Май", " Июнь",
		"Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", " Декабрь" ], //"янв.", "февр.", "март", " апр.", "май", " июнь", "июль.", "август", " сент.", "окт.", "нояб.", "дек."
	dayNames: [ "Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", " Суббота" ],
	dayNamesShort: [ "вс.", "пн.", "вт.", "ср.", "чт.", "пт.", "сб." ],
	dayNamesMin: [ "Вс","Пн","Вт","Ср","Чт","Пт","Сб" ],
	weekHeader: "Sem.",
	dateFormat: "dd/mm/yy",
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: "" };
datepicker.setDefaults( datepicker.regional.ru );

return datepicker.regional.ru;

} ) );
