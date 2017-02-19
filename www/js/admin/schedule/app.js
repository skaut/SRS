var apiPath = basePath + '/api/schedule/';

var app = angular.module('scheduleApp', ['ui.calendar', 'ui.bootstrap']);

app.filter("filterBlocks", function () {
    return function (items, search, unassignedOnly) {
        var filtered;

        if (search) {
            filtered = [];
            var pattern = new RegExp(search.toLowerCase());
            angular.forEach(items, function (item) {
                if (pattern.test(item.name.toLowerCase())) {
                    filtered.push(item);
                }
            });
            items = filtered;
        }

        if (unassignedOnly) {
            filtered = [];
            angular.forEach(items, function (item) {
                if (item.programs_count == 0) {
                    filtered.push(item);
                }
            });
            items = filtered;
        }

        return items;
    };
});

app.directive("block", function ($parse) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            attrs.$observe('block', function (id) {
                var block = scope.blocksMap[id];

                var event = {
                    block: block,
                    duration: block.duration_hours + ":" + block.duration_minutes
                };

                setColor(event);
                setTitle(event);

                element.data('event', event);

                $(element).draggable({
                    scroll: false,
                    helper: 'clone',
                    zIndex: 999,
                    revert: true,
                    revertDuration: 0
                });
            });
        }
    };
});

app.factory('apiService', function ($http) {
    var getData = function (action) {
        return $http.get(apiPath + action, {})
            .then(function (result) {
                return result.data;
            });
    };
    return {getData: getData};
});

app.controller('AdminScheduleCtrl', function AdminScheduleCtrl($scope, $http, $q, uiCalendarConfig, apiService) {
    $scope.config = null;

    $scope.blocks = null;
    $scope.blocksMap = [];

    $scope.rooms = null;
    $scope.roomsMap = [];

    $scope.programs = null;

    $scope.events = [];

    $scope.event = null;

    $scope.startup = function () {
        var promisses = [];

        var configPromise = apiService.getData('getcalendarconfig');
        configPromise.then(function (result) {
            $scope.config = result;
            calendarConfig = $scope.uiConfig.calendar;
            calendarConfig.defaultDate = $.fullCalendar.moment($scope.config.seminar_from_date);
            calendarConfig.views.seminar.duration.days = $scope.config.seminar_duration;
            calendarConfig.editable = $scope.config.allowed_modify_schedule;
            calendarConfig.droppable = $scope.config.allowed_modify_schedule;
        });
        promisses.push(configPromise);

        var blocksPromise = apiService.getData('getblocks');
        blocksPromise.then(function (result) {
            $scope.blocks = result;
            angular.forEach($scope.blocks, function (block, key) {
                $scope.blocksMap[block.id] = block;
            })
        });
        promisses.push(blocksPromise);

        var roomsPromise = apiService.getData('getrooms');
        roomsPromise.then(function (result) {
            $scope.rooms = result;
            angular.forEach($scope.rooms, function (room, key) {
                $scope.roomsMap[room.id] = room;
            })
        });
        promisses.push(roomsPromise);

        var programsPromise = apiService.getData('getprogramsadmin');
        programsPromise.then(function (result) {
            $scope.programs = result;
        });
        promisses.push(programsPromise);


        $q.all(promisses).then(function () {
            angular.forEach($scope.programs, function (program, key) {
                program.block = $scope.blocksMap[program.block_id];
                program.room = $scope.roomsMap[program.room_id];
                setTitle(program);
                setColor(program);
                $scope.events.push(program);
            })
        });
    };
    $scope.startup();


    $scope.addEvent = function (event) {
        $scope.event = event;

        var programSaveDTO = {
            block_id: event.block.id,
            start: event.start.utc().format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (result) {
                $scope.event.id = result.data.id;
                flashMessage('Pridano', 'success');
            })
    };


    $scope.saveEvent = function (event) {
        var programSaveDTO = {
            id: event.id,
            block_id: event.block.id,
            room_id: event.room ? event.room.id : null,
            start: event.start.utc().format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (result) {
                flashMessage('Upraveno', 'success');
            })
    };


    $scope.removeEvent = function (event) {
        $('#program-modal').modal('hide');

        event.block.programs_count--;

        $http.post(apiPath + 'removeprogram/' + event.id)
            .then(function (result) {
                flashMessage('Smazano', 'success');
            });

        $('#calendar').fullCalendar('removeEvents', [event._id]);
    };


    $scope.updateEvent = function (event, room) {
        $('#program-modal').modal('hide');

        event.room = room ? room : null;

        setTitle(event);

        $scope.saveEvent(event);

        $('#calendar').fullCalendar('updateEvent', [event]);
    };


    $scope.refreshForm = function () {
        this.event = $scope.event;
        this.room = $scope.event.room;
    };

    $scope.uiConfig = {
        calendar: {
            lang: 'cs',
            timezone: 'utc',
            defaultView: 'seminar',
            aspectRatio: 1.6,
            header: false,
            eventDurationEditable: false,
            views: {
                seminar: {
                    type: 'agenda',
                    buttonText: 'Seminář',
                    allDaySlot: false,
                    duration: {days: 7},
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00:00',
                    snapDuration: '00:05:00'
                }
            },

            eventClick: function (event, element) {
                if ($scope.config.allowed_modify_schedule) {
                    $scope.event = event;
                    $scope.refreshForm();
                    $('#program-modal').modal('show');
                }
            },

            eventDrop: function (event) {
                $scope.saveEvent(event);
            },

            drop: function (date) {
                var event = angular.extend({}, $(this).data('event'));

                event.start = date;
                event.attendees_count = 0;
                event.block.programs_count++;

                $scope.addEvent(event);
            }
        }
    };

    $scope.eventSources = [$scope.events];
});

function flashMessage(text, type) {
    // if (type == undefined) {
    //     type = 'info';
    // }
    //
    // var fadeout = { enabled:true, delay:6000 }
    //
    // $('#jsMessages').notify({
    //     message:{
    //         text: text
    //     },
    //     type: type,
    //     fadeOut: fadeout,
    //     closable: true
    // }).show();
}

function setColor(event) {
    event.color = event.block.mandatory ? '#D9534F' : '#0275D8';
}

function setTitle(event) {
    var room = event.room;
    event.title = event.block.name + (room ? (' - ' + room.name) : '');
}