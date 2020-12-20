'use strict';

// import $ from 'jquery';
// window.$ = $;
// window.jQuery = $;

import 'jquery-ui-sortable';

// import 'bootstrap';

import 'bootstrap-datetime-picker'
import 'bootstrap-datetime-picker/js/locales/bootstrap-datetimepicker.cs'

import 'bootstrap-select'
import 'bootstrap-select/dist/js/i18n/defaults-cs_CZ'

import 'bootstrap-fileinput'
import 'bootstrap-fileinput/themes/fa/theme'
import 'bootstrap-fileinput/js/locales/cs'
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

import naja from 'naja';
document.addEventListener('DOMContentLoaded', () => naja.initialize());
window.naja = naja;

import {LiveForm, Nette} from 'live-form-validation';
window.Nette = Nette;
window.LiveForm = LiveForm;

// import netteForms from 'nette-forms';
// window.Nette = netteForms;
// netteForms.initOnLoad();

// import 'ublaboo-datagrid'



