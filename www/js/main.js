$(function () {
    $.datetimepicker.setLocale('cs');

    $(".datepicker").datetimepicker({
        format: 'd.m.Y',
        timepicker: false
    });

    $('.datetimepicker').datetimepicker({
        format: 'd.m.Y H:i'
    });
});