<template>
    <div class='calendar-app'>
        <div id="program-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content" v-if="selectedEventInfo">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ selectedEventInfo.event.extendedProps.block.name }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-unstyled">
                            <li v-if="selectedEventInfo.event.extendedProps.block.category">
                                <strong>Kategorie: </strong>
                                {{ selectedEventInfo.event.extendedProps.block.category }}
                            </li>
                            <li v-if="selectedEventInfo.event.getResources().length > 0">
                                <strong>Místnost: </strong>
                                {{ selectedEventInfo.event.getResources()[0].title }}
                            </li>
                            <li>
                                <strong>Obsazenost: </strong>
                                {{ selectedEventInfo.event.extendedProps.block.capacity
                                ? selectedEventInfo.event.extendedProps.attendeesCount + '/' + selectedEventInfo.event.extendedProps.block.capacity
                                : selectedEventInfo.event.extendedProps.attendeesCount
                                }}
                            </li>
                        </ul>

                        <div v-if="selectedEventInfo.event.extendedProps.block.lectors.length > 0">
                            <h6>Lektoři</h6>
                            <div v-for="lector in selectedEventInfo.event.extendedProps.block.lectors">
                                <b>{{ lector.name }}</b>
                                <p>
                                    <img :src="basePath + '/files/user_photos/' + lector.photo" v-if="lector.photo" class="pull-left mr-3 mb-3"/>
                                    {{ lector.about }}
                                </p>
                                <div class="clearfix"></div>
                            </div>
                        </div>

                        <h6>Popis</h6>
                        <p>{{ selectedEventInfo.event.extendedProps.block.perex }}</p>
                        <div v-html="selectedEventInfo.event.extendedProps.block.description"></div>
                    </div>

                    <div class="modal-footer" v-show="userAllowedRegisterPrograms">
                        <button @click="handleAttendEvent()" v-show="!selectedEventInfo.event.extendedProps.userAttends"
                                :disabled="selectedEventInfo.event.extendedProps.blocked || selectedEventInfo.event.extendedProps.occupied || !selectedEventInfo.event.extendedProps.paid"
                                class="btn btn-sm btn-success pull-left">
                            Přihlásit se na program
                        </button>
                        <button @click="handleUnattendEvent()" v-show="selectedEventInfo.event.extendedProps.userAttends"
                                :disabled="selectedEventInfo.event.extendedProps.block.autoRegistered"
                                class="btn btn-sm btn-danger pull-left">
                            Odhlásit se z programu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="help-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nápověda</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Kliknutím na program se zobrazí možnost přihlášení/odhlášení a další informace o programu.</p>

                        <h6>Typy programů</h6>
                        <ul class="list-group text-white">
                            <li class="list-group-item bg-success">Programy, které máte přihlášené.</li>
                            <li class="list-group-item bg-primary">Programy, na které se lze přihlásit.</li>
                            <li class="list-group-item bg-danger">Programy, které jsou povinné.</li>
                            <li class="list-group-item bg-secondary">Programy, na které se nelze přihlásit (máte přihlášený stejný program, je obsazený nebo jej nemáte zaplacený).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning alert-forever" v-show="notRegisteredMandatoryPrograms > 0">
            Máte nepřihlášené povinné programy (v kalendáři červeně). Prosíme, zapište si je.
        </div>

        <div class="row mb-3">
            <div class="col-auto mr-auto">
                <div class="btn-group">
                    <button @click="handleChangeView('timeGridSeminar')" class="btn btn-sm btn-secondary" :class="{ active: initialView === 'timeGridSeminar' }">
                        Na výšku
                    </button>
                    <button @click="handleChangeView('resourceTimelineSeminar')" class="btn btn-sm btn-secondary" :class="{ active: initialView === 'resourceTimelineSeminar' }">
                        Na šířku
                    </button>
                    <button @click="handleChangeView('listSeminar')" class="btn btn-sm btn-secondary" :class="{ active: initialView === 'listSeminar' }">
                        Seznam
                    </button>
                </div>
                <div class="btn-group" v-show="initialView === 'resourceTimelineSeminar'">
                    <button @click="handlePrev()" id="btnPrev" class="btn btn-sm btn-secondary">
                        <span class="fc-icon fc-icon-chevron-left"></span>
                    </button>
                    <button id="btnTitle" class="btn btn-sm btn-secondary" disabled></button>
                    <button @click="handleNext()" id="btnNext" class="btn btn-sm btn-secondary">
                        <span class="fc-icon fc-icon-chevron-right"></span>
                    </button>
                </div>
                <button data-toggle="modal" data-target="#help-modal" class="btn btn-sm btn-secondary">
                    <i class="fa fa-question-circle"></i> Nápověda
                </button>
            </div>
            <div class="col-auto">
                <div class="spinner float-left" v-show="loading > 0">
                    <span class="fa fa-spinner fa-pulse fa-2x" style=""></span>
                </div>
                <div class="notifications float-left ml-2 mt-2 mt-sm-0">
                    <div v-if="message" class="alert" :class="'alert-' + message.type" style="padding: 4px 8px; margin: 0">
                        {{ message.text }}
                    </div>
                </div>
            </div>
        </div>

        <FullCalendar id="calendar" style="visibility: hidden" ref="fullCalendar" :options="calendarOptions"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
    import csLocale from '@fullcalendar/core/locales/cs';
    import timeGridPlugin from '@fullcalendar/timegrid'
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
    import listPlugin from '@fullcalendar/list'
    import bootstrapPlugin from '@fullcalendar/bootstrap'
    import { mapState, mapActions } from 'vuex'
    import interactionPlugin from '@fullcalendar/interaction';

    export default {
        components: {
            FullCalendar
        },
        data: function () {
            return {
                initialView: localStorage.getItem('fcInitialView') || 'timeGridSeminar',
                selectedEventInfo: null,
                userAllowedRegisterPrograms: userAllowedRegisterPrograms,
                basePath: basePath
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading', 'message', 'notRegisteredMandatoryPrograms']),
            calendarOptions() {
                return {
                    themeSystem: 'bootstrap',
                    locales: csLocale,
                    locale: 'cs',
                    timeZone: 'none',
                    aspectRatio: 1.6,
                    headerToolbar: false,
                    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                    plugins: [
                        timeGridPlugin,
                        resourceTimelinePlugin,
                        listPlugin,
                        interactionPlugin,
                        bootstrapPlugin
                    ],
                    views: this.calendarViews,
                    initialView: this.initialView,
                    validRange: this.validRange,
                    slotMinTime: this.slotMinTime,
                    slotMaxTime: this.slotMaxTime,
                    eventDidMount: this.eventDidMount,
                    viewDidMount: this.viewDidMount,
                    datesSet: this.datesSet,
                    events: this.events,
                    resources: this.resources,
                    eventClick: this.handleEventClick
                }
            },
            calendarViews() {
                return {
                    timeGridSeminar: {
                        type: 'timeGrid',
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        allDaySlot: false,
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1},
                        dayHeaderFormat: {weekday: 'short', day: 'numeric', month: 'numeric'}
                    },
                    resourceTimelineSeminar: {
                        type: 'resourceTimeline',
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1},
                        resourceAreaHeaderContent: 'Místnosti'
                    },
                    listSeminar: {
                        type: 'listYear',
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        noEventsContent: 'Nejsou k dispozici žádné programy'
                    },
                };
            },
            validRange() {
                return {
                    start: this.config.seminar_from_date,
                    end: this.config.seminar_to_date
                }
            },
            slotMinTime() {
                return {
                    minutes: this.config.min_time * 60
                };
            },
            slotMaxTime() {
                return {
                    minutes: this.config.max_time * 60
                };
            }
        },
        watch: {
            message: function () {
                $('.notifications').show().animate({
                    opacity: 1.0
                }, ALERT_DURATION).slideUp(1000);
            },
            config: function () {
                $('#calendar').css('visibility', 'visible');
            }
        },
        methods: {
            ...mapActions(['loadData', 'attendProgram', 'unattendProgram']),

            /**
             * Vykreslí název místnosti v timeGridSeminar zobrazení.
             */
            eventDidMount(info) {
                if (info.view.type === 'timeGridSeminar') {
                    const eventResources = info.event.getResources();
                    if (eventResources.length > 0 && eventResources[0].id > 0) {
                        const titleEl = info.el.getElementsByClassName('fc-event-title')[0];
                        const roomEl = document.createElement('div');
                        roomEl.setAttribute('class', 'fc-event-room');
                        roomEl.innerText = eventResources[0].title;
                        titleEl.parentNode.insertBefore(roomEl, titleEl.nextSibling);
                    }
                } else if (info.view.type === 'listSeminar') {
                    const eventResources = info.event.getResources();
                    if (eventResources.length > 0 && eventResources[0].id > 0) {
                        const titleEl = info.el.getElementsByClassName('fc-list-event-title')[0].firstElementChild;
                        const roomEl = document.createElement('a');
                        roomEl.setAttribute('class', 'fc-list-event-room');
                        roomEl.innerText = eventResources[0].title;
                        titleEl.parentNode.insertBefore(roomEl, titleEl.nextSibling);
                    }
                }
                if (info.event.extendedProps.block.perex) {
                    $(info.el).tooltip({
                        title: info.event.extendedProps.block.perex,
                        placement: 'bottom',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },

            /**
             * Zpracuje přepnutí view.
             */
            handleChangeView(view) {
                this.initialView = view;
                this.$refs.fullCalendar.getApi().changeView(view);
            },

            /**
             * Uloží zvolené view.
             */
            viewDidMount(info) {
                localStorage.setItem('fcInitialView', info.view.type);
            },

            /**
             * Zpracuje přechod na přechozí den.
             */
            handlePrev(view) {
                this.$refs.fullCalendar.getApi().prev();
            },

            /**
             * Zpracuje přechod na následující den.
             */
            handleNext(view) {
                this.$refs.fullCalendar.getApi().next();
            },

            /**
             * Překreslí tlačítka pro volbu dne.
             */
            datesSet(info) {
                $('#btnPrev').prop('disabled', info.view.currentStart.toISOString().split('T')[0] <= this.validRange.start);
                $('#btnNext').prop('disabled', info.view.currentEnd.toISOString().split('T')[0] >= this.validRange.end);
                $('#btnTitle').html(info.view.title);
            },

            /**
             * Zpracuje kliknutí na událost (otevře modal okno).
             */
            handleEventClick(info) {
                this.selectedEventInfo = {
                    event: info.event,
                    resourceId: info.event.getResources()[0].id
                };
                $('#program-modal').modal('show');
            },

            /**
             * Zpracuje přihlášení na program.
             */
            handleAttendEvent() {
                $('#program-modal').modal('hide');
                $('.fc-event').tooltip('dispose');
                this.attendProgram(this.selectedEventInfo);
            },

            /**
             * Zpracuje odhlášení z programu.
             */
            handleUnattendEvent() {
                $('#program-modal').modal('hide');
                $('.fc-event').tooltip('dispose');
                this.unattendProgram(this.selectedEventInfo);
            }
        },
        created: function () {
            this.loadData();
        }
    }
</script>

<style lang='css'>
    .fc-day-today {
        background-color: inherit !important;
    }
    .fc-event-title {
        font-weight: bold;
    }
    .fc-event-room {
        padding: 0 1px;
    }
    .fc-list-event-title {
        font-weight: bold;
    }
    .fc-list-event-room {
        font-weight: normal;
        padding-left: 14px;
    }
</style>