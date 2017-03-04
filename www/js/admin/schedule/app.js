var apiPath = basePath + '/api/schedule/';

var COLOR_OPTIONAL = '#0275D8';
var COLOR_MANDATORY = '#D9534F';
var COLOR_AUTO_REGISTER = '#F0AD4E';


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

        filtered = [];
        angular.forEach(items, function (item) {
            if (item.programs_count == 0 || !item.auto_register) {
                filtered.push(item);
            }
        });
        items = filtered;

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


app.controller('AdminScheduleCtrl', function AdminScheduleCtrl($scope, $http, $q, uiCalendarConfig) {
    $scope.config = null;

    $scope.blocks = null;
    $scope.blocksMap = [];

    $scope.rooms = null;
    $scope.roomsMap = [];

    $scope.programs = null;

    $scope.events = [];

    $scope.event = null;

    $scope.message = {
        text: '',
        type: ''
    };


    $scope.startup = function () {
        var promisses = [];

        $scope.loading = 0;

        $scope.loading++;
        var configPromise = $http.get(apiPath + 'getcalendarconfig')
            .then(function (response) {
                $scope.config = response.data;
                calendarConfig = $scope.uiConfig.calendar;
                calendarConfig.defaultDate = $.fullCalendar.moment($scope.config.seminar_from_date);
                calendarConfig.views.seminar.duration.days = $scope.config.seminar_duration;
                calendarConfig.editable = $scope.config.allowed_modify_schedule;
                calendarConfig.droppable = $scope.config.allowed_modify_schedule;
            }, function (response) {
                $scope.flashMessage('Nepodařilo se načíst nastavení kalendáře.', 'danger');
            }).finally(function () {
                $scope.loading--;
            });
        promisses.push(configPromise);

        $scope.loading++;
        var blocksPromise = $http.get(apiPath + 'getblocks')
            .then(function (response) {
                $scope.blocks = response.data;
                angular.forEach($scope.blocks, function (block, key) {
                    $scope.blocksMap[block.id] = block;
                })
            }, function (response) {
                $scope.flashMessage('Nepodařilo se načíst programové bloky.', 'danger');
            }).finally(function () {
                $scope.loading--;
            });
        promisses.push(blocksPromise);

        $scope.loading++;
        var roomsPromise = $http.get(apiPath + 'getrooms')
            .then(function (response) {
                $scope.rooms = response.data;
                angular.forEach($scope.rooms, function (room, key) {
                    $scope.roomsMap[room.id] = room;
                })
            }, function (response) {
                $scope.flashMessage('Nepodařilo se načíst místnosti.', 'danger');
            }).finally(function () {
                $scope.loading--;
            });
        promisses.push(roomsPromise);

        $scope.loading++;
        var programsPromise = $http.get(apiPath + 'getprogramsadmin')
            .then(function (response) {
                $scope.programs = response.data;
            }, function (response) {
                $scope.flashMessage('Nepodařilo se načíst programy.', 'danger');
            }).finally(function () {
                $scope.loading--;
            });
        promisses.push(programsPromise);


        $q.all(promisses).then(function () {
            angular.forEach($scope.programs, function (program, key) {
                program.block = $scope.blocksMap[program.block_id];
                program.room = $scope.roomsMap[program.room_id];

                setTitle(program);
                setColor(program);

                $scope.events.push(program);
            });

            $('#calendar').css('visibility', 'visible');
        });
    };
    $scope.startup();


    /**
     * Pridani programu pretazenim ze seznamu bloku.
     *
     * @param event
     */
    $scope.addEvent = function (event) {
        $scope.event = event;

        var programSaveDTO = {
            block_id: event.block.id,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.id = response.data.program.id;
                    $scope.event.block.programs_count++;
                }
                else {
                    $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Presunuti programu na jiny cas.
     *
     * @param event
     * @param revertFunc
     */
    $scope.moveEvent = function (event, revertFunc) {
        $scope.event = event;

        event.start.stripZone();

        var programSaveDTO = {
            id: event.id,
            block_id: event.block.id,
            room_id: event.room ? event.room.id : null,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                }
                else {
                    revertFunc()
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Uprava programu - zmena mistnosti.
     *
     * @param event
     */
    $scope.updateEvent = function (event) {
        $('#program-modal').modal('hide');

        $scope.event = event;

        event.start.stripZone();

        var programSaveDTO = {
            id: event.id,
            block_id: event.block.id,
            room_id: event.room ? event.room.id : null,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                    setTitle($scope.event);
                    $('#calendar').fullCalendar('rerenderEvents');
                }
                else {
                    $scope.event.room = $scope.event._room;
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Odstraneni programu.
     *
     * @param event
     */
    $scope.removeEvent = function (event) {
        $('#program-modal').modal('hide');

        $scope.event = event;

        $scope.loading++;
        $http.post(apiPath + 'removeprogram/' + event.id)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.block.programs_count--;
                    $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo odstranit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    $scope.flashMessage = function (text, type) {
        $scope.message.text = text;
        $scope.message.type = type ? type : 'info';

        $('.notifications .alert').show().animate({
            opacity: 1.0
        }, ALERT_DURATION).slideUp(1000);
    };


    $scope.uiConfig = {
        calendar: {
            lang: 'cs',
            timezone: false,
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
                if ($scope.loading == 0 && $scope.config.allowed_modify_schedule) {
                    $scope.event = event;
                    $scope.event._room = $scope.event.room;
                    $('#program-modal').modal('show');
                }
            },

            eventDrop: function (event, delta, revertFunc) {
                if ($scope.loading == 0)
                    $scope.moveEvent(event, revertFunc);
                else
                    revertFunc();
            },

            eventReceive: function (event) {
                if ($scope.loading == 0)
                    $scope.addEvent(event);
                else
                    $('#calendar').fullCalendar('removeEvents', [event._id]);
            }
        }
    };

    $scope.eventSources = [$scope.events];
});

function setColor(event) {
    event.color = event.block.mandatory ? (event.block.auto_register ? COLOR_AUTO_REGISTER : COLOR_MANDATORY) : COLOR_OPTIONAL;
}

function setTitle(event) {
    var room = event.room;
    event.title = event.block.name + (room ? (' - ' + room.name) : '');
}