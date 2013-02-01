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

const COLOR_MANDATORY = 'red';
const COLOR_EMPTY = 'gray';
const COLOR_EMPTY_MANDATORY = 'orange';

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

function bindEndToBasicBlockDuration(start, end, basic_block_duration) {

    var diff_milis = (start - end);
    var event_duration_minutes = Math.abs(Math.round(((diff_milis / 1000) / 60)));
    var ratio = event_duration_minutes / basic_block_duration;
    console.log(basic_block_duration);

    if (ratio % 1 != 0) {
        flashMessage('Délka programu byla upravena, aby odpovídala násobku základní délky bloku', 'warning');
    }

    var event_basic_block_count = Math.round(ratio);
    if (event_basic_block_count == 0) {
        event_basic_block_count = 1; //vzdy vytvorime udalost o delce alespon jednoho bloku
    }
    var end = new Date(start.getTime() + basic_block_duration*event_basic_block_count*60000);
    return end;
}

function bindEndToBlockDuration(start, end, block_duration, basic_block_duration) {
    var new_end = new Date(start.getTime() + basic_block_duration*60000*block_duration);
    if (end != null) {
        if (end.getTime() != new_end.getTime()) {
            flashMessage('Délka programu byla upravena s ohledem na délku přiřazeného bloku', 'warning');
        }
    }
    return new_end;
}

function bindTooltipContent(event) {
    var content =
        '<h4>' + event.title +  '</h4>';

    if (event.block) {
    content += "<ul>";
    content += "<li><span>Kapacita:</span>"+ event.block.capacity +"</li>";
    content += "<li><span>Lokalita:</span>"+ event.block.location +"</li>";
    content += "<li><span>Pomůcky:</span>"+ event.block.tools +"</li>";
    content +="</ul>";
    }
    return content;
};

function flashMessage(message, type) {
  if (type == undefined) {
      type = 'info';
  }
  var messageEl = $('<div class="alert alert-'+type+'">'+message+'<a class="close" data-dismiss="alert" href="#">&times;</a></div>');
  $('#jsMessages').append(messageEl);
  messageEl.alert().delay(5000).fadeOut();

}

function prepareExternalBlocks() {
    console.log('run');
    $('.calendar-blocks .block').each(function() {


    });
}

function prepareExternalBlock(block, element) {
    console.log(block);
        var eventObject = {
            title: block.name,
            block: block
        };

        element.data('eventObject', eventObject);
        $(element).draggable({
            zIndex: 999,
            revert: true,      // will cause the event to go back to its
            revertDuration: 0  //  original position after the drag
        });
}
