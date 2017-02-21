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
