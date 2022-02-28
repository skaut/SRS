'use strict';

// jquery
import 'jquery'
import 'jquery-ui-sortable';
window.$ = $;
// jquery mousewheel

// bootstrap
import 'bootstrap';

// nette-forms + live-form-validation
import {LiveForm, Nette} from 'live-form-validation';
window.Nette = Nette;
window.LiveForm = LiveForm;
Nette.initOnLoad();
LiveForm.setOptions({
    messageErrorPrefix: '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>&nbsp;'
});

// naja
import naja from 'naja';
naja.addEventListener('init', () => {
    init();
});
naja.addEventListener('complete', () => {
    init();
});
document.addEventListener('DOMContentLoaded', () => naja.initialize());

// bootstrap inputs
import 'bootstrap-datetime-picker'
import 'bootstrap-datetime-picker/js/locales/bootstrap-datetimepicker.cs'
import 'bootstrap-select'
import 'bootstrap-select/dist/js/i18n/defaults-cs_CZ'
import 'bootstrap-confirmation2'
import 'bootstrap-fileinput'
import 'bootstrap-fileinput/themes/fa/theme'
import 'bootstrap-fileinput/js/locales/cs'

// datagrid
import Happy from "happy-inputs";
window.happy = new Happy;
window.happy.init();

import 'ublaboo-datagrid'

// init funkce
function init() {
    animateAlerts();
    initSelects();
    initFileInputs();
    initConfirms();
    initDateTimePickers();
    initTooltips();
    // $(".datagrid").floatingScroll();
}

function animateAlerts() {
    $('.alert:not(.alert-forever)').animate({
        opacity: 1.0
    }, ALERT_DURATION).slideUp(ALERT_ANIMATION);
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
        showPreview: false,
        showRemove: false,
        showUpload: false,
        showCancel: false,
        showClose: false,
        showDrag: false, // nefunguje, opraveno v CSS
        initialPreviewShowDelete: false,
        initialPreviewAsData: true,
        browseClass: 'btn btn-secondary',
        browseLabel: 'Vybrat',
        msgPlaceholder: 'Vybrat soubor...',
        dropZoneTitle: 'Přetáhněte soubory sem &hellip;',
        fileSingle: 'souborů',
        filePlural: 'souborů'
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

function initDateTimePickers() {
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

function initTooltips() {
    $('[data-toggle="tooltip"]').tooltip();
}
