function CalendarCtrl($scope, $http) {
    $scope.option = '';
    $scope.event = null;
    $scope.config = null;
    $scope.counter = 0

    $http.post("./get", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
            angular.forEach($scope.events, function(event, key) {
                setColor(event);
            });
            $http.post("./getcalendarconfig", {})
                .success(function(data, status, headers, config) {
                    $scope.config = data;
                    bindCalendar($scope);
                }).error(function(data, status, headers, config) {
                    $scope.status = status;
                });
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
        $scope.event = event;
        event.startJSON = fixDate(event.start);
        event.endJSON = fixDate(event.end);
        seen = [];
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if (seen.indexOf(val) >= 0)
                    return undefined
                seen.push(val)
            }
            if (key =='source') { // tyto data nepotřebujeme
                return undefined;
            }
            return val
        });
        $http.post("./set?data="+json)
        .success(function(data, status, headers, config) {
           $scope.event.id = data['id'];

        });
    }

    $scope.update = function(event, option) {
        $('#blockModal').modal('hide');
        $scope.event.mandatory = event.mandatory;
        if (option) {
        $scope.event.title = option.name;
        $scope.event.block = $scope.options[option.id];
        }
        else {
            $scope.event.title = '(Nepřiřazeno)';
            $scope.event.block = null;
        }
        setColor($scope.event);
        $scope.saveEvent($scope.event);
        $('#calendar').fullCalendar('updateEvent', [$scope.event]);
    };

    $scope.delete = function(event) {
        $http.post("./delete/"+event.id);
        $('#blockModal').modal('hide');
        $('#calendar').fullCalendar( 'removeEvents',[event._id] );
    }

    $scope.refreshForm = function() {
        this.event = $scope.event;
        if ($scope.event.block != undefined && $scope.event.block != null) {
            var id = $scope.event.block.id
            this.option = $scope.options[id];
        }
        else {
            this.option = null;
        }
        $scope.$apply();
    }
}

function bindCalendar(scope) {
    var calendar = $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        selectable: true,
        selectHelper: true,
        select: function(start, end, allDay) {
            end = bindEndToBlockDuration(start, end, scope.config.basic_block_duration);
            var title = 'Nepřiřazeno';
            var event = {
                title: title,
                start: start,
                end: end,
                allDay: allDay
            }
            scope.event = event;
            setColor(event);
            scope.saveEvent(event);
            calendar.fullCalendar('renderEvent',
                scope.event,
                true // make the event "stick"
            );
             calendar.fullCalendar('unselect');
        },

        eventClick: function(event, element) {
            scope.event = event;
            scope.refreshForm();
            $('#blockModal').modal('show');

        },

        eventDrop: function( event, jsEvent, ui, view ) {
            scope.event = event;
            scope.saveEvent(event);
        },

        editable: true,
        events: scope.events,
        firstDay: 1,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        defaultView: 'agendaWeek',
        ignoreTimezone: true,
        slotMinutes: 15
    });
}



//eventResize: function( event, jsEvent, ui, view ) {
//    console.log(event);
//    var end = bindEndToBlockDuration(event.start, event.end, scope.config.basic_block_duration);
//    event.end = end;
//    scope.event = event;
//    scope.saveEvent(scope.event);
//    $('#calendar').fullCalendar('updateEvent', event);
//    console.log(event);
//
//
//},
