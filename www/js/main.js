$(function () {
    $.nette.ext('initScripts', {
        complete: function () {
            animateAlerts();
            initMultiSelects();
            initFileInputs();
            initConfirms();
        }
    });

    $.nette.init();

    animateAlerts();
    initMultiSelects();
    initFileInputs();
    initConfirms();

    $('input.date, input.datetime-local').each(function (i, el) {
        el = $(el);
        el.get(0).type = 'text';
        el.datetimepicker({
            language: 'cs',
            startDate: el.attr('min'),
            endDate: el.attr('max'),
            weekStart: 1,
            minView: el.is('.date') ? 'month' : 'hour',
            format: el.is('.date') ? 'd. m. yyyy' : 'd. m. yyyy hh:ii', // for seconds support use 'd. m. yyyy - hh:ii:ss'
            autoclose: true,
            fontAwesome: true,
            todayBtn: true,
            todayHighlight: true
        });
        el.attr('value') && el.datetimepicker('setValue');
    });

    $('[data-toggle="tooltip"]').tooltip();
});

function animateAlerts() {
    $('.alert:not(.alert-forever)').animate({
        opacity: 1.0
    }, 5000).slideUp(1000);
}

function initMultiSelects() {
    $('select[multiple]').selectpicker({
        noneSelectedText: 'Nic není vybráno',
        noneResultsText: 'Žádné výsledky {0}',
        countSelectedText: 'Označeno {0} z {1}',
        maxOptionsText: ['Limit překročen ({n} {var} max)', 'Limit skupiny překročen ({n} {var} max)', ['položek', 'položka']],
        multipleSeparator: ', ',
        iconBase: 'fa',
        tickIcon: 'fa-check'
    });
}

function initFileInputs() {
    $('input[type="file"]').fileinput({
        language: "cz",
        theme: "fa",
        browseLabel: "Vybrat",
        showPreview: false,
        showRemove: false,
        showUpload: false,
        showCancel: false,
        browseClass: "btn btn-default"
    });
}

function initConfirms() {
    $('[data-toggle=confirmation]').confirmation({
        rootSelector: '[data-toggle=confirmation]',
        title: '',
        singleton: 'true',
        popout: 'true',
        btnOkClass: 'btn btn-primary',
        btnOkIcon: 'fa fa-check',
        btnOkLabel: 'Ano',
        btnCancelClass: 'btn btn-default',
        btnCancelIcon: 'fa fa-times',
        btnCancelLabel: 'Ne'
    });
}

