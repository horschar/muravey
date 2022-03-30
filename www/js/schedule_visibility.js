/**
 *  Библиотека управления скрытием и отображением военнослужащих/подразделений/дней при нажании
 *
 */

var schedule_visibility_arr = {'military' : [], 'day' : []};
var schedule_military_unit_arr = [];

function schedule_visibility_getdata() {
    $('.military_title').each(function () {
        var id = parseInt($(this).data('id'));
        var unit = parseInt($(this).data('unit'));
        schedule_visibility_arr['military'][id] = 1;
        schedule_military_unit_arr[id] = unit;
    });

    $('.day_title').each(function () {
        var id = parseInt($(this).data('id'));
        schedule_visibility_arr['day'][id] = 1;
    });
}

function schedule_visibility(target, mode) {
    var type = $(target).data('type');
    var id = parseInt($(target).data('id'));

    if (mode === 1) {
        if (type === 'unit') {
            visible = 1;
            $.each(schedule_visibility_arr['military'], function(index, value){
                if (schedule_military_unit_arr[index] === id && schedule_visibility_arr['military'][index])
                    visible = 0;
            });
            $.each(schedule_visibility_arr['military'], function(index, value){
                if (schedule_military_unit_arr[index] === id)
                    schedule_visibility_arr['military'][index] = visible;
            });
        } else
            schedule_visibility_arr[type][id] = schedule_visibility_arr[type][id] ? 0: 1;

    } else if (mode === 2) {
        if (type === 'unit') {
            visible = 1;
            $.each(schedule_visibility_arr['military'], function(index, value){
                if (schedule_military_unit_arr[index] !== id && schedule_visibility_arr['military'][index])
                    visible = 0;
            });
            $.each(schedule_visibility_arr['military'], function(index, value){
                schedule_visibility_arr['military'][index] = visible;
                if (schedule_military_unit_arr[index] === id)
                    schedule_visibility_arr['military'][index] = 1;
            });
        } else {
            visible = 1;
            $.each(schedule_visibility_arr[type], function (index, value) {
                if (index !== id && value)
                    visible = 0;
            });
            $.each(schedule_visibility_arr[type], function (index, value) {
                schedule_visibility_arr[type][index] = visible;
            });
            schedule_visibility_arr[type][id] = 1;
        }
    }
    schedule_visibility_update();
}

function schedule_visibility_update() {
    $('.mySelect').each(function () {
        military = parseInt($(this).data('military'));
        day = parseInt($(this).data('day'));
        if (schedule_visibility_arr['military'][military] && schedule_visibility_arr['day'][day])
            $(this).children('.value').show();
        else $(this).children('.value').hide();
    });
}

$(document).ready(function () {
    schedule_visibility_getdata();
});