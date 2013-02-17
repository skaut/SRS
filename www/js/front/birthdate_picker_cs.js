/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 17.2.13
 * Time: 16:22
 * To change this template use File | Settings | File Templates.
 */
$(function() {
    $.datepicker.regional['cs'] = {
        renderer: $.ui.datepicker.defaultRenderer,
        monthNames: ['leden','únor','březen','duben','květen','červen',
            'červenec','srpen','září','říjen','listopad','prosinec'],
        monthNamesShort: ['led','úno','bře','dub','kvě','čer',
            'čvc','srp','zář','říj','lis','pro'],
        dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
        dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
        dayNamesMin: ['ne','po','út','st','čt','pá','so'],
        dateFormat: 'yy-mm-dd',
        firstDay: 1,
        prevText: '&#x3c;Dříve', prevStatus: '',
        prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: '',
        nextText: 'Později&#x3e;', nextStatus: '',
        nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: '',
        currentText: 'Nyní', currentStatus: '',
        todayText: 'Nyní', todayStatus: '',
        clearText: '-', clearStatus: '',
        closeText: 'Zavřít', closeStatus: '',
        yearStatus: '', monthStatus: '',
        weekText: 'Týd', weekStatus: '',
        dayStatus: 'DD d MM',
        defaultStatus: '',
        isRTL: false
    };
    //$.extend($.datepicker.defaults, $.datepicker.regional['cs']);
    $.datepicker.setDefaults( $.datepicker.regional[ "cs" ] )
    $(".datepicker").datepicker({
        yearRange: '-100y:c+nn',
        maxDate: '-1d',
        changeMonth: true,
        changeYear: true

    });

});