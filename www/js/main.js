$(function () {
    $.nette.ext('flashes', {
        complete: function () {
            $('.alert').animate({
                opacity: 1.0
            }, 4000).fadeOut(2000);
        }
    });

    $.nette.init();

    $('.alert').animate({
        opacity: 1.0
    }, 4000).fadeOut(2000);

    $('select[multiple]').selectpicker({
        iconBase: 'fa',
        tickIcon: 'fa-check'
    });

    $('.selectpicker').selectpicker({
        iconBase: 'fa',
        tickIcon: 'fa-check'
    });

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
            autoclose: true,
            fontAwesome: true,
            todayBtn: true,
            todayHighlight: true
        });
        el.attr('value') && el.datetimepicker('setValue');
    });

    $.confirm.options = {
        title: "",
        confirmButton: "Ano",
        cancelButton: "Ne",
        post: false,
        submitForm: false,
        confirmButtonClass: "btn-primary",
        cancelButtonClass: "btn-default",
        dialogClass: "modal-dialog"
    }
});

