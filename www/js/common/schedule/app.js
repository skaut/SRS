var apiPath = basePath + '/api/schedule/';

var app = angular.module('scheduleApp', ['ui.calendar', 'ui.bootstrap']);

app.factory('myService', function($http) {
    var getData = function() {
        return $http.get(apiPath + "getcalendarconfig", {})
            .then(function(result){
                return result.data;
            });
    };

    return { getData: getData };
});


app.controller('AdminScheduleCtrl', AdminScheduleCtrl);

function AdminScheduleCtrl($scope, $http, uiCalendarConfig, myService) {
    var myDataPromise = myService.getData();
    myDataPromise.then(function (result) {
        $scope.uiConfig.calendar.defaultDate = $.fullCalendar.moment(result.seminar_from_date);
        $scope.uiConfig.calendar.views.seminar.duration.days = result.seminar_duration;
        $scope.uiConfig.calendar.editable = result.allowed_modify_schedule;
        $scope.uiConfig.calendar.droppable = result.allowed_modify_schedule;
    });


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
            eventResize: $scope.alertOnResize
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



}

