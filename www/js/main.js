var ALERT_DURATION = 5000;

$(function () {
    $.nette.ext('initScripts', {
        init: function () {
            init();
        },
        complete: function () {
            init();
        }
    });

    $.nette.init();
});

function init() {
    animateAlerts();
    initSelects();
    initFileInputs();
    initConfirms();
    initDateTimePicker();

    $('[data-toggle="tooltip"]').tooltip();
}

function animateAlerts() {
    $('.alert:not(.alert-forever)').animate({
        opacity: 1.0
    }, ALERT_DURATION).slideUp(1000);
}

function initSelects() {
    $('select')
        .not('.datagrid .row-group-actions select')
        .not('.datagrid .col-per-page select')
        .not('.modal-body select')
        .add('select[multiple]')
        .selectpicker({
            noneSelectedText: 'Nic není vybráno',
            noneResultsText: 'Žádné výsledky {0}',
            countSelectedText: 'Označeno {0} z {1}',
            maxOptionsText: [
                'Limit překročen ({n} {var} max)',
                'Limit skupiny překročen ({n} {var} max)',
                ['položek', 'položka']
            ],
            selectAllText: 'Vše',
            deselectAllText: 'Nic',
            multipleSeparator: ', ',
            selectedTextFormat: 'count > 3',
            actionsBox: true,
            iconBase: 'fa',
            tickIcon: 'fa-check',
            style: 'btn-light'
        });
}

function initFileInputs() {
    $('input[type="file"]').fileinput({
        language: 'cs',
        theme: 'fa',
        browseLabel: 'Vybrat',
        msgPlaceholder: 'Vybrat soubor...',
        showPreview: false,
        showRemove: false,
        showUpload: false,
        showCancel: false,
        browseClass: 'btn btn-secondary'
    });
}

function initConfirms() {
    $('[data-toggle="confirmation"]').confirmation({
        rootSelector: '[data-toggle=confirmation]',
        title: '',
        singleton: true,
        popout: true,
        btnOkClass: 'btn btn-sm btn-primary',
        btnOkIcon: 'fa fa-check',
        btnOkLabel: 'Ano',
        btnCancelClass: 'btn btn-sm btn-secondary',
        btnCancelIcon: 'fa fa-times',
        btnCancelLabel: 'Ne'
    });
}

function initDateTimePicker() {
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
}
