$(function () {
    $.datetimepicker.setLocale('cs');

    $(".datepicker").datetimepicker({
        format: 'd.m.Y',
        timepicker: false
    });

    $('.datepicker-birthdate').datetimepicker({
        format: 'd.m.Y',
        maxDate: new Date(),
        yearStart: 1920,
        yearEnd: new Date().getFullYear()
    });

    $('.datetimepicker').datetimepicker({
        format: 'd.m.Y H:i'
    });
});