

const REFRESH_INTERVAL = 10000

function AdminCalendarCtrl($scope, $http, $q, $timeout) {
    $scope.option = ''; // indexovane bloky - pro snadne vyhledavani a prirazovani
    $scope.event = null; // udalost se kterou prave pracuji
    $scope.config = null; // konfiguracni nastaveni pro kalendar
    $scope.blocks = []; // neindexovane bloky - v poli - pro filtrovani
    $scope.startup = function() {
        var promise, promisses = [];
        promise = $http.post("./getoptions", {})
            .success(function(data, status, headers, config) {
                $scope.options = data;
            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        promise = $http.post("./get", {})
            .success(function(data, status, headers, config) {
                $scope.events = data;
            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        promise = $http.post("./getcalendarconfig", {})
            .success(function(data, status, headers, config) {
                $scope.config = data;

            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        //pote co jsou vsechny inicializacni ajax requesty splneny
        $q.all(promisses).then(function() {
            angular.forEach($scope.events, function(event, key) {
                event.block = $scope.options[event.block];
                setColor(event);

            });
            angular.forEach($scope.options, function(block, key) {
                $scope.blocks.push(block);

            });
            if (!$scope.config.is_allowed_modify_schedule) {
                $scope.warning = 'Úprava harmonogramu semináře je zakázána. Povolit úpravy lze v modulu konfigurace.';
            }
            bindCalendar($scope);
        });
    }

    $scope.startup();

//    $scope.onTimeout = function(){
//        $scope.startup();
//        mytimeout = $timeout($scope.onTimeout, REFRESH_INTERVAL);
//    }
//    var mytimeout = $timeout($scope.onTimeout,REFRESH_INTERVAL);



    $scope.saveEvent = function(event) {
        $scope.event = event;
        event.startJSON = fixDate(event.start);
        event.endJSON = fixDate(event.end);
        seen = [];
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if (seen.indexOf(val) >= 0)
                    return undefined;
                seen.push(val);
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
        $scope.event.attendees_count = 0;
        $scope.event.block = $scope.options[option.id];
        var end = bindEndToBlockDuration($scope.event.start, $scope.event._end, $scope.event.block.duration, $scope.config.basic_block_duration);
        $scope.event.end = end;

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

    var local_config = {
        editable: scope.config.is_allowed_modify_schedule,
        droppable: scope.config.is_allowed_modify_schedule,
        events: scope.events,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        selectable: scope.config.is_allowed_modify_schedule,
        selectHelper: scope.config.is_allowed_modify_schedule,

        select: function(start, end, allDay) {
            end = bindEndToBasicBlockDuration(start, end, scope.config.basic_block_duration);
            var title = '(Nepřiřazeno)';
            var event = {
                title: title,
                start: start,
                end: end,
                allDay: allDay,
                mandatory: false
            }
            scope.event = event;
            setColor(scope.event);
            scope.saveEvent(event);
            calendar.fullCalendar('renderEvent',
                scope.event,
                true // make the event "stick"
            );
            calendar.fullCalendar('unselect');
        },

        eventClick: function(event, element) {
            if (scope.config.is_allowed_modify_schedule) {
                scope.event = event;
                scope.refreshForm();
                $('#blockModal').modal('show');
            }

        },

        eventDrop: function( event, jsEvent, ui, view ) {
            scope.event = event;
            scope.saveEvent(event);
        },

        eventResize: function( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view  ) {
            if (event.block == null || event.block == undefined) {
                var end = bindEndToBasicBlockDuration(event.start, event.end, scope.config.basic_block_duration);
                event.end = end;
                scope.event = event;
                scope.saveEvent(scope.event);
                $('#calendar').fullCalendar('updateEvent', event);
            }
            else {
                flashMessage('Položkám s přiřazeným programovým blokem nelze měnit délku', 'error');
                revertFunc();
            }
        },

        drop: function(date, allDay) {

            // retrieve the dropped element's stored Event Object
            var originalEventObject = $(this).data('eventObject');

            // we need to copy it, so that multiple events don't have a reference to the same object
            var event = $.extend({}, originalEventObject);

            // assign it the date that was reported
            event.start = date;
            event.attendees_count = 0;
            event.allDay = allDay;
            event.end = bindEndToBlockDuration(date, null, event.block.duration, scope.config.basic_block_duration);
            scope.event = event;
            setColor(scope.event);
            scope.saveEvent(event);

            // render the event on the calendar
            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
            $('#calendar').fullCalendar('renderEvent', event, true);
        },

        eventRender: function(event, element) {
            var options = {}
            options.html = true;
            options.trigger = 'hover';
            options.title = event.title;
            options.content = '';
            if (event.block != null && event.block != undefined) {
                options.content += "<ul class='no-margin block-properties'>";
                options.content += "<li><span>lektor:</span> "+ event.block.lector +"</li>";
                options.content += "<li><span>Kapacita:</span>"+event.attendees_count+"/"+ event.block.capacity +"</li>";
                options.content += "<li><span>Lokalita:</span> "+ event.block.location +"</li>";
                options.content += "<li><span>Pomůcky:</span> "+ event.block.tools +"</li>";
                options.content +="</ul>";
                options.content +="<p>"+event.block.perex+"</p>";
            }

            element.find('.fc-event-title').popover(options);
        }
    }

    var calendar = $('#calendar').fullCalendar(jQuery.extend(local_config, localization_config));
}




