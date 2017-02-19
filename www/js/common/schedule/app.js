var apiPath = basePath + '/api/schedule/';

var app = angular.module('scheduleApp', ['ui.calendar', 'ui.bootstrap']);

app.filter("filterBlocks", function() {
    return function(items, search, unassignedOnly) {
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
            attrs.$observe('block', function(id) {
                var block = scope.blocksMap[id];

                var eventObject = {
                    title: block.name,
                    block: block,
                    duration: block.duration_hours + ":" + block.duration_minutes
                };
                element.data('event', eventObject);

                $(element).draggable({
                    scroll: false,
                    helper: 'clone',
                    zIndex:999,
                    revert:true,
                    revertDuration:0
                });
            });
        }
    };
});

app.factory('configService', function($http) {
    var getData = function() {
        return $http.get(apiPath + 'getcalendarconfig', {})
            .then(function(result){
                return result.data;
            });
    };
    return { getData: getData };
});

app.factory('blocksService', function($http) {
    var getData = function() {
        return $http.get(apiPath + 'getblocks', {})
            .then(function(result){
                return result.data;
            });
    };
    return { getData: getData };
});

app.factory('programsService', function($http) {
    var getData = function() {
        return $http.get(apiPath + 'getprogramsadmin', {})
            .then(function(result){
                return result.data;
            });
    };
    return { getData: getData };
});


app.controller('AdminScheduleCtrl', function AdminScheduleCtrl($scope, $http, uiCalendarConfig, configService, blocksService, programsService) {
    $scope.blocks = [];
    $scope.blocksMap = [];
    $scope.event = null;


    var configPromise = configService.getData();
    var blocksPromise = blocksService.getData();
    var programsPromise = programsService.getData();

    configPromise.then(function (result) {
        calendarConfig = $scope.uiConfig.calendar;
        calendarConfig.defaultDate = $.fullCalendar.moment(result.seminar_from_date);
        calendarConfig.views.seminar.duration.days = result.seminar_duration;
        calendarConfig.editable = result.allowed_modify_schedule;
        calendarConfig.droppable = result.allowed_modify_schedule;
    });

    blocksPromise.then(function (result) {
        $scope.blocks = result;
        angular.forEach($scope.blocks, function (block, key) {
            $scope.blocksMap[block.id] = block;
        })
    });

    $scope.programs = {
        url: apiPath + 'getprogramsadmin'
    };

    $scope.eventSources = [$scope.programs];

    $scope.addEvent = function (event) {

    };

    $scope.uiConfig = {
        calendar:{
            lang: 'cs',
            timezone: false,
            defaultView: 'seminar',
            aspectRatio: 1.6,
            header: false,
            eventDurationEditable: false,
            views: {
                seminar: {
                    type: 'agenda',
                    duration: { days: 3 },
                    buttonText: 'Seminář',
                    allDaySlot: false,
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00:00',
                    snapDuration: '00:05:00'
                }
            },
            eventClick: $scope.alertEventOnClick,
            eventDrop: $scope.alertOnDrop,
            drop: function (date) {
                var event = {};

                event.start = date;
                event.attendees_count = 0;
                event.block = $scope.blocksMap[$(this)[0].attributes['data-id'].value];
                event.block.programs_count++;
                $scope.event = event;
                $scope.addEvent(event);

                $('#calendar').fullCalendar('renderEvent', event, true);

            }
        }
    };


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

