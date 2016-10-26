$(function () {
    $.datetimepicker.setLocale('cs');

    $(".datepicker").datetimepicker({
        format: 'd.m.Y',
        timepicker: false
    });

    $('.datepicker-birthdate').datetimepicker({
        format: 'd.m.Y'
    });

    $('.datetimepicker').datetimepicker({
        format: 'd.m.Y H:i'
    });
});