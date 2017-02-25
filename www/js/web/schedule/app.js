var apiPath = basePath + '/api/schedule/';

var COLOR_OPTIONAL = '#0275D8';
var COLOR_MANDATORY = '#D9534F';
var COLOR_ATTENDS = '#5CB85C';
var COLOR_BLOCKED = '#A6A6A6';


var app = angular.module('scheduleApp', ['ui.calendar', 'ui.bootstrap', 'ngSanitize']);


app.controller('WebScheduleCtrl', function WebScheduleCtrl($scope, $http, $q, uiCalendarConfig) {
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

    $scope.mandatory_nonregistered_programs_count = 0;


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

                    if (block.mandatory && block.user_allowed && !block.user_attends)
                        $scope.mandatory_nonregistered_programs_count++;
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
        var programsPromise = $http.get(apiPath + 'getprogramsweb')
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


    $scope.attend = function (event) {
        $('#program-modal').modal('hide');

        $scope.loading++;
        $http.post(apiPath + 'attendprogram/' + event.id)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.user_attends = true;

                    if ($scope.event.block.mandatory == true) {
                        $scope.mandatory_nonregistered_programs_count--;
                    }

                    $scope.event.attendees_count = response.data.int_data;

                    angular.forEach($scope.events, function (event, key) {
                        if ($scope.event.id != event.id && $scope.event.user_attends && $scope.event.blocks.indexOf(event.id) != -1)
                            event.blocked = true;
                    });

                    angular.forEach($scope.events, function (event, key) {
                        setColor(event);
                    });

                    $('#calendar').fullCalendar('removeEvents');
                    $('#calendar').fullCalendar('addEventSource', $scope.events);
                }

                $scope.flashMessage(response.data.message, response.data.status);
            }).finally(function () {
            $scope.loading--;
        });
    };


    $scope.unattend = function (event) {
        $('#program-modal').modal('hide');

        $scope.loading++;
        $http.post(apiPath + 'unattendprogram/' + event.id)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.user_attends = false;

                    if ($scope.event.block.mandatory) {
                        $scope.mandatory_nonregistered_programs_count++;
                    }

                    $scope.event.attendees_count = response.data.int_data;

                    angular.forEach($scope.events, function (event, key) {
                        event.blocked = false;
                    });

                    angular.forEach($scope.events, function (event, key) {
                        angular.forEach($scope.events, function (event, key) {
                            if (this.id != event.id && this.user_attends && this.blocks.indexOf(event.id) != -1)
                                event.blocked = true;
                        }, event)
                    });

                    angular.forEach($scope.events, function (event, key) {
                        setColor(event);
                    });

                    $('#calendar').fullCalendar('removeEvents');
                    $('#calendar').fullCalendar('addEventSource', $scope.events);
                }

                $scope.flashMessage(response.data.message, response.data.status);
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
            editable: false,
            views: {
                seminar: {
                    type: 'agenda',
                    buttonText: 'Seminář',
                    allDaySlot: false,
                    duration: {days: 7},
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00:00'
                }
            },

            eventClick: function (event, element) {
                if ($scope.loading == 0) {
                    $scope.event = $scope.events[event._id - 1];
                    if (event.block.capacity !== undefined && event.block.capacity <= event.attendees_count)
                        $scope.event.occupied = true;

                    $('#program-modal').modal('show');
                }
            },

            eventMouseout: function (event, jsEvent, view) {
                $('.popover').fadeOut();
            },

            eventRender: function (event, element) {
                var options = {
                    html: true,
                    trigger: 'hover',
                    title: event.block.name,
                    placement: 'bottom',
                    content: ''
                };

                options.content += "<strong>Kategorie:</strong> " + event.block.category + "<br>";
                options.content += "<strong>Lektor:</strong> " + event.block.lector + "<br>";
                options.content += "<strong>Místnost:</strong> " + (event.room ? event.room.name : '') + "<br>";
                options.content += "<strong>Obsazenost:</strong> " + (event.block.capacity !== undefined ? event.attendees_count + "/" + event.block.capacity : event.attendees_count) + "</br>";
                options.content += event.block.perex;

                element.popover(options);
            }
        }
    };

    $scope.eventSources = [$scope.events];
});

function setColor(event) {
    if (event.user_attends) {
        event.color = COLOR_ATTENDS;
    }
    else if (!userAllowedRegisterPrograms || (event.block.capacity !== null && event.attendees_count >= event.block.capacity) || event.blocked) {
        event.color = COLOR_BLOCKED;
    }
    else if (event.block.mandatory) {
        event.color = COLOR_MANDATORY;
    }
    else {
        event.color = COLOR_OPTIONAL;
    }
}

function setTitle(event) {
    var room = event.room;
    event.title = event.block.name + (room ? (' - ' + room.name) : '');
}