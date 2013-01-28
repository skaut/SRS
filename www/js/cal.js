/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 28.1.13
 * Time: 13:18
 * To change this template use File | Settings | File Templates.
 */

function CalendarCtrl($scope, $http) {

//    $scope.options = [
//    {'id': 1, "name": "Blok 1"},
//    {'id': 2, "name": "Blok 2"}
//    ]
    $scope.option = '';
    $scope.event = null;



    $http.post("./get", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
            bindCalendar($scope);
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });

    $http.post("./getoptions", {})
        .success(function(data, status, headers, config) {
            $scope.options = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });


    $scope.saveEvent = function(event) {
        seen = []
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if (seen.indexOf(val) >= 0)
                    return undefined
                seen.push(val)
            }
            return val
        });
        $http.post("./set?data="+json)
        .success(function(data, status, headers, config) {
           event.id = data['id'];
        });
    }

    $scope.update = function(option) {
        $('#blockModal').modal('hide');
        $scope.event.title = $scope.options[option].name;
        $scope.event.block = $scope.options[option].id;
        $scope.saveEvent($scope.event);
        $('#calendar').fullCalendar('updateEvent', $scope.event);
    };

    $scope.delete = function(event) {
        $http.post("./delete/"+event.id);
        $('#blockModal').modal('hide');
        $('#calendar').fullCalendar( 'removeEvents',[event.id] );
    }

    $scope.showupdateModal = function() {
        $('#blockModal').modal('show');

    }
}

function bindCalendar(scope) {

    var events = scope.events;
    var calendar = $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        selectable: true,
        selectHelper: true,
        select: function(start, end, allDay) {

            var title = 'Nepřiřazeno';
            var event = {
                title: title,
                start: start,
                end: end,
                allDay: allDay
            }
            scope.saveEvent(event);
            event.id = scope.newId;

            calendar.fullCalendar('renderEvent',
                event,
                true // make the event "stick"
            );
             calendar.fullCalendar('unselect');
        },

        eventClick: function(event, element) {
            scope.event = event;
            scope.showupdateModal();
        },

        eventDrop: function( event, jsEvent, ui, view ) {
            scope.event = event;
            scope.saveEvent(scope.event);
        },

        editable: true,
        events: events
    });
}