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

function bindEndToBlockDuration(start, end, basic_block_duration) {

    var diff_milis = (start - end);
    var event_duration_minutes = Math.abs(Math.round(((diff_milis / 1000) / 60)));
    var ratio = event_duration_minutes / basic_block_duration;
    var event_basic_block_count = Math.round(ratio);
    if (event_basic_block_count == 0) {
        event_basic_block_count = 1; //vzdy vytvorime udalost o delce alespon jednoho bloku
    }
    var end = new Date(start.getTime() + basic_block_duration*event_basic_block_count*60000);
    return end;
}
