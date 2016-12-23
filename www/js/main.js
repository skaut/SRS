$( document ).ready(function () {
    $('select[multiple]').multiSelect();
});

jQuery(function($) {
    $('input.date, input.datetime-local').each(function(i, el) {
        el = $(el);
        el.get(0).type = 'text';
        el.datetimepicker({
            language: 'cs',
            startDate: el.attr('min'),
            endDate: el.attr('max'),
            weekStart: 1,
            minView: el.is('.date') ? 'month' : 'hour',
            format: el.is('.date') ? 'd. m. yyyy' : 'd. m. yyyy - hh:ii', // for seconds support use 'd. m. yyyy - hh:ii:ss'
            autoclose: true
        });
        el.attr('value') && el.datetimepicker('setValue');
    });
});
