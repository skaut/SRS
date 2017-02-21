var apiPath = basePath + '/api/schedule/';

var COLOR_OPTIONAL = '#0275D8';
var COLOR_MANDATORY = '#D9534F';
var COLOR_ATTEND = '#5CB85C';
var COLOR_BLOCKED = '#F7F7F7';


var app = angular.module('scheduleApp', ['ui.calendar', 'ui.bootstrap']);


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
            })
        });
    };
    $scope.startup();

    //
    // $scope.attend = function (event) {
    //     $http.post(api_path + "attend/" + event.id)
    //         .success(function (data, status, headers, config) {
    //             flashMessage(data['message'], data['status']);
    //             if (data['status'] == 'success') {
    //                 event.attends = true;
    //
    //                 if (event.mandatory == true) {
    //                     $scope.mandatory_unsigned_programs_count--;
    //                 }
    //
    //                 event.attendees_count = data.event.attendees_count;
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     $scope.events[i].blocked = false;
    //                 }
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     for (j = $scope.events.length - 1; j >= 0; j--) {
    //                         if (i == j) continue;
    //                         if ($scope.events[i].attends == true && $scope.events[i].blocks.indexOf($scope.events[j].id) != -1)
    //                             $scope.events[j].blocked = true;
    //                     }
    //                 }
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     setColorFront($scope.events[i]);
    //                 }
    //             }
    //             $('#calendar').fullCalendar('updateEvent', event);
    //         }).error(function (data, status, headers, config) {
    //         $scope.status = status;
    //     });
    //     $('#blockModal').modal('hide');
    // };
    //
    //
    // $scope.unattend = function (event) {
    //     $http.post(api_path + "unattend/" + event.id)
    //         .success(function (data, status, headers, config) {
    //             flashMessage(data['message'], data['status']);
    //             if (data['status'] == 'success') {
    //                 event.attends = false;
    //                 event.attendees_count = data.event.attendees_count;
    //                 if (event.mandatory == true) {
    //                     $scope.mandatory_unsigned_programs_count++;
    //                 }
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     $scope.events[i].blocked = false;
    //                 }
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     for (j = $scope.events.length - 1; j >= 0; j--) {
    //                         if (i == j) continue;
    //                         if ($scope.events[i].attends == true && $scope.events[i].blocks.indexOf($scope.events[j].id) != -1)
    //                             $scope.events[j].blocked = true;
    //                     }
    //                 }
    //
    //                 for (i = $scope.events.length - 1; i >= 0; i--) {
    //                     setColorFront($scope.events[i]);
    //                 }
    //             }
    //             $('#calendar').fullCalendar('updateEvent', event);
    //         }).error(function (data, status, headers, config) {
    //         $scope.status = status;
    //     });
    //     $('#blockModal').modal('hide');
    // };


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
                    slotLabelInterval: '01:00:00',
                }
            },

            eventClick: function (event, element) {
                $scope.event = event;
                $('#program-modal').modal('show');
            },

            eventMouseout:function (event, jsEvent, view) {
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

                options.content += "<ul class='no-margin block-properties'>";
                options.content += "<li><span>Kategorie:</span> " + event.block.lector + "</li>";
                options.content += "<li><span>Lektor:</span> " + event.block.lector + "</li>";
                options.content += "<li><span>Místnost:</span> " + event.block.lector + "</li>";
                options.content += "<li><span>Obsazenost:</span>" + event.attendees_count + "/" + event.block.capacity + "</li>";
                options.content += "</ul>";
                if (event.block.perex) {
                    options.content += "<p>" + event.block.perex + "</p>";
                }
                element.popover(options);
            }
        }
    };

    $scope.eventSources = [$scope.events];
});

function setColor(event) {
    event.color = event.block.mandatory ? COLOR_MANDATORY : COLOR_OPTIONAL;
}

function setTitle(event) {
    var room = event.room;
    event.title = event.block.name + (room ? (' - ' + room.name) : '');
}