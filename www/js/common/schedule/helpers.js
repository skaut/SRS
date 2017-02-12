var localization_config = {
    header:{
        left:'prev,next today',
        center:'title',
        right:'month,agendaWeek,agendaDay'
    },
    allDaySlot:false,
    weekends:true,
    defaultView:'agendaWeek',
    ignoreTimezone:true,
    slotMinutes:15,
    snapMinutes:5,
    timeFormat:'H:mm{ - H:mm}',
    monthNames:['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
    monthNamesShort:['Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čvn', 'Čvc', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'],
    dayNames:['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
    dayNamesShort:['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
    buttonText:{
        prev:'&nbsp;&#9668;&nbsp;', // left triangle
        next:'&nbsp;&#9658;&nbsp;', // right triangle
        prevYear:'&nbsp;&lt;&lt;&nbsp;', // <<
        nextYear:'&nbsp;&gt;&gt;&nbsp;', // >>
        today:'Dnes',
        month:'Měsíc',
        week:'Seminář',
        day:'Den'
    },
    axisFormat:'H(:mm)',

    columnFormat:{
        month:'ddd', // Mon
        week:'ddd d.M.', // Mon 9/7
        day:'dddd d.M.'  // Monday 9/7
    },

    titleFormat:{
        month:'MMMM yyyy', // September 2009
        week:"", // Sep 7 - 13 2009
        day:'dddd, d. MMM yyyy'
    }
};


function fixDate(d) {
    var curr_date = d.getDate();
    var curr_month = d.getMonth() + 1; //Months are zero based
    var curr_year = d.getFullYear();
    var curr_hours = d.getHours();
    var curr_minutes = d.getMinutes();
    var curr_seconds = d.getSeconds();
    curr_minutes = ( curr_minutes < 10 ? "0" : "" ) + curr_minutes;
    curr_seconds = ( curr_seconds < 10 ? "0" : "" ) + curr_seconds;
    var dateString = curr_year + '-' + curr_month + '-' + curr_date + ' ' + curr_hours + ':' + curr_minutes + ':' + curr_seconds;
    return dateString;
}

var COLOR_MANDATORY = 'red';
var COLOR_EMPTY = 'gray';
var COLOR_EMPTY_MANDATORY = 'orange';
var COLOR_ATTEND = 'green';
var COLOR_FULL = COLOR_EMPTY;

function setColor(event) {
    if (event.block != null && event.mandatory == true) {
        event.color = COLOR_MANDATORY;
    }

    else if ((event.block == null || event.block == undefined) && event.mandatory == true) {
        event.color = COLOR_EMPTY_MANDATORY;
    }

    else if ((event.block == null || event.block == undefined) && event.mandatory == false) {
        event.color = COLOR_EMPTY;
    }
    else {
        event.color = null;
    }
}

function setColorFront(event) {
    if (event.attends == false && !userAllowedLogInPrograms) {
        event.color = COLOR_FULL;
    }

    else if (event.blocked == true && event.attends == false) {
        event.color = COLOR_FULL;
    }

    else if (event.block != undefined && (event.attendees_count >= event.block.capacity) && event.attends == false) {
        event.color = COLOR_FULL;
    }

    else if (event.mandatory == true && event.attends == false) {
        event.color = COLOR_MANDATORY;
    }

    else if (event.attends == true) {
        event.color = COLOR_ATTEND;
    }

    else if (event.block != undefined && (event.attendees_count >= event.block.capacity)) {
        event.color = COLOR_FULL;
    }

    else {
        event.color = null;
    }
}

function bindEndToBasicBlockDuration(start, end, basic_block_duration) {

    var diff_milis = (start - end);
    var event_duration_minutes = Math.abs(Math.round(((diff_milis / 1000) / 60)));
    var ratio = event_duration_minutes / basic_block_duration;

    if (ratio % 1 != 0) {
        flashMessage('Délka programu byla upravena, aby odpovídala násobku základní délky bloku', 'warning');
    }

    var event_basic_block_count = Math.round(ratio);
    if (event_basic_block_count == 0) {
        event_basic_block_count = 1; //vzdy vytvorime udalost o delce alespon jednoho bloku
    }
    var end = new Date(start.getTime() + basic_block_duration * event_basic_block_count * 60000);
    return end;
}

function bindEndToBlockDuration(start, end, block_duration, basic_block_duration) {
    var new_end = new Date(start.getTime() + basic_block_duration * 60000 * block_duration);
    if (end != null) {
        if (end.getTime() != new_end.getTime()) {
            flashMessage('Délka programu byla upravena s ohledem na délku přiřazeného bloku', 'warning');
        }
    }
    return new_end;
}

function flashMessage(text, type) {
    if (type == undefined) {
        type = 'info';
    }
//    <a class="close" data-dismiss="alert" href="#">&times;</a>
//    var messageEl = $('<div class="alert alert-'+type+'">'+message+'</div>');
//    $('#jsMessages').append(messageEl);
//    messageEl.alert().delay(5000).fadeOut();

    var fadeout = { enabled:true, delay:6000 }

    $('#jsMessages').notify({
        message:{
            text:text
        },
        type:type,
        fadeOut:fadeout,
        closable:true
    }).show();

}


function prepareExternalBlock(block, element) {
    var eventObject = {
        title:block.name,
        block:block
    };
    element.data('eventObject', eventObject);

    $(element).draggable({
        scroll:false,
        helper:'clone',
        zIndex:999,
        revert:true, // will cause the event to go back to its
        revertDuration:0  //  original position after the drag
    });
}
