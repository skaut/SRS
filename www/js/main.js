$(function () {
    $.nette.ext('onload', {
        complete: function () {
            animateAlerts();
            initMultiSelects();
            initFileInputs();
        }
    });

    $.nette.init();

    animateAlerts();
    initMultiSelects();
    initFileInputs();

    $('.alert:not(.alert-forever)').animate({
        opacity: 1.0
    }, 5000).slideUp(1000);

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

    $('[data-toggle="tooltip"]').tooltip();
});

function animateAlerts() {
    $('.alert:not(.alert-forever)').animate({
        opacity: 1.0
    }, 5000).slideUp(1000);
}

function initMultiSelects() {
    $('select[multiple]').selectpicker({
        iconBase: 'fa',
        tickIcon: 'fa-check',
        noneSelectedText: 'Nic není vybráno',
        noneResultsText: 'Žádné výsledky {0}',
        countSelectedText: 'Označeno {0} z {1}',
        maxOptionsText: ['Limit překročen ({n} {var} max)', 'Limit skupiny překročen ({n} {var} max)', ['položek', 'položka']],
        multipleSeparator: ', '
    });
}

function initFileInputs() {
    $('input[type="file"]').fileinput({
        language: "cz",
        theme: "fa",
        showPreview: false,
        showRemove: false,
        showUpload: false,
        showCancel: false,
        browseClass: "btn btn-default"
    });
}

