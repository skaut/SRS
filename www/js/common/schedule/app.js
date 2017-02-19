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

                var eventObject = {
                    block: block,
                    duration: block.duration_hours + ":" + block.duration_minutes
                };
                element.data('event', eventObject);

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
            calendarConfig = $scope.uiConfig.calendar;
            calendarConfig.defaultDate = $.fullCalendar.moment(result.seminar_from_date);
            calendarConfig.views.seminar.duration.days = result.seminar_duration;
            calendarConfig.editable = result.allowed_modify_schedule;
            calendarConfig.droppable = result.allowed_modify_schedule;
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
                $scope.setEventParams(program);
                $scope.events.push(program);
            })
        });
    };
    $scope.startup();

    $scope.setEventParams = function (event) {
        var room = event.block.room_id ? roomsMap[event.block.room_id] : null;
        event.title = event.block.name + (room ? ' - ' + room.name : '');
        event.color = event.block.mandatory ? '#D9534F' : '#0275D8';
    };

    $scope.addEvent = function (event) {
        var programSaveDTO = {
            block_id: event.block.id,
            start: event.start.utc().format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (result) {
                event.id = result;
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

            })
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
                    duration: {days: 3},
                    buttonText: 'Seminář',
                    allDaySlot: false,
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00:00',
                    snapDuration: '00:05:00'
                }
            },
            eventClick: $scope.alertEventOnClick,

            eventDrop: function (event) {
                $scope.saveEvent(event);
            },

            drop: function (date) {
                var event = angular.extend({}, $(this).data('event'));

                event.start = date;
                event.attendees_count = 0;
                event.block.programs_count++;

                $scope.setEventParams(event);
                $scope.addEvent(event);
            }
            // eventRender: function (event, element) {
            //     // var options = {
            //     //     html: true,
            //     //     trigger: 'hover',
            //     //     title: event.title,
            //     //     placement: 'bottom',
            //     //     content: ''
            //     // };
            //     //
            //     // options.content += "<ul class='no-margin block-properties'>";
            //     // options.content += "<li><span>Lektor:</span> " + event.block.lector + "</li>";
            //     // options.content += "<li><span>Obsazenost:</span>" + event.attendees_count + "/" + event.block.capacity + "</li>";
            //     // options.content += "<li><span>Lokalita:</span> " + event.block.location + "</li>";
            //     // options.content += "<li><span>Pomůcky:</span> " + event.block.tools + "</li>";
            //     // options.content += "</ul>";
            //     // options.content += "<p>" + event.block.perex + "</p>";
            //     //
            //     // element.popover(options);
            // }
        }
    };

    $scope.eventSources = [$scope.events];

    // $http.get(api_path + "getallprograms", {
    //     cache: true,
    //     params: {}
    // }).then(function (data) {
    //     $scope.events.slice(0, $scope.events.length);
    //     angular.forEach(data.data, function (value) {
    //         $scope.events.push({
    //             title: value.title,
    //             description: value.desctiption,
    //             start: new Date(parseInt(value.StartAt.substr(6))),
    //             end: new Date(parseInt(value.EndAt.substr(6))),
    //             allDay: false
    //         })
    //     })
    // })

    //config


});

