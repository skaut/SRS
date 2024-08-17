<template>
    <div class='calendar-app'>
        <div id="program-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content" v-if="selectedEventInfo">
                    <div class="modal-header">
                        <h5 class="modal-title">Nastavení programu</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-3">Název</div>
                            <div class="col-9">
                                <a :href="'../blocks/detail/' + selectedEventInfo.event.extendedProps.block.id" target="_blank">
                                    {{ selectedEventInfo.event.extendedProps.block.name }}
                                </a>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-3">Kategorie</div>
                            <div class="col-9">{{ selectedEventInfo.event.extendedProps.block.category }}</div>
                        </div>
                        <div class="form-group row">
                            <div class="col-3">Lektoři</div>
                            <div class="col-9">{{ selectedEventInfo.event.extendedProps.block.lectorsNames }}</div>
                        </div>

                        <form class="form-horizontal">
                            <div class="form-group row mb-1">
                                <div class="col-3 col-form-label">
                                    <label for="select-room">Místnost</label>
                                </div>
                                <div class="col-9">
                                    <select id="select-room" class="form-control" v-model="selectedEventInfo.resourceId">
                                        <option v-for="resource in resources" :value="resource.id">{{ resource.title }}</option>
                                    </select>
                                    <span v-if="(getResourceById(selectedEventInfo.resourceId).extendedProps.capacity || Number.MAX_VALUE) < (selectedEventInfo.event.extendedProps.block.capacity || 0)" class="text-warning">
                                        <span class="fa fa-triangle-exclamation"></span>
                                        Kapacita místnosti je menší než kapacita bloku.
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="form-group row">
                            <div class="col-3">Kapacita</div>
                            <div class="col-9">{{ selectedEventInfo.event.extendedProps.block.capacity }}</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="handleEventRemove()" class="btn btn-danger" :disabled="!config.allowed_modify_schedule">Odstranit</button>
                        <button @click="handleEventUpdateRoom()" class="btn btn-primary" :disabled="!config.allowed_modify_schedule">Uložit</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="notifications">
            <div v-if="message" class="alert" :class="'alert-' + message.type">
                {{ message.text }}
            </div>
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
            </div>
            <div class="col-auto">
                <div class="spinner" v-show="loading > 0">
                    <span class="fa fa-spinner fa-pulse fa-2x" style=""></span>
                </div>
            </div>
        </div>

        <FullCalendar id="calendar" v-show="config" ref="fullCalendar" :options="calendarOptions"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
    import csLocale from '@fullcalendar/core/locales/cs';
    import timeGridPlugin from '@fullcalendar/timegrid'
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
    import interactionPlugin from '@fullcalendar/interaction'
    import bootstrapPlugin from '@fullcalendar/bootstrap'
    import { mapState, mapGetters, mapActions } from 'vuex'

    export default {
        components: {
            FullCalendar
        },
        data: function () {
            return {
                initialView: localStorage.getItem('fcAdminInitialView') || 'timeGridSeminar',
                selectedEventInfo: null
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading', 'message']),
            ...mapGetters(['getResourceById']),
            calendarOptions() {
                return {
                    themeSystem: 'bootstrap',
                    locales: csLocale,
                    locale: 'cs',
                    timeZone: 'none',
                    scrollTime: '07:00:00',
                    aspectRatio: 1.85,
                    headerToolbar: false,
                    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                    plugins: [
                        timeGridPlugin,
                        resourceTimelinePlugin,
                        interactionPlugin,
                        bootstrapPlugin
                    ],
                    views: this.calendarViews,
                    initialView: this.initialView,
                    validRange: this.validRange,
                    eventDidMount: this.eventDidMount,
                    viewDidMount: this.viewDidMount,
                    datesSet: this.datesSet,
                    events: this.events,
                    resources: this.resources,
                    droppable: this.config.allowed_modify_schedule,
                    eventStartEditable: this.config.allowed_modify_schedule,
                    eventResourceEditable: this.config.allowed_modify_schedule,
                    eventOverlap: this.eventOverlap,
                    eventAllow: this.eventAllow,
                    eventReceive: this.handleEventReceive,
                    eventDrop: this.handleEventDrop,
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
                };
            },
            validRange() {
                return {
                    start: this.config.seminar_from_date,
                    end: this.config.seminar_to_date
                }
            }
        },
        watch: {
            message: function () {
                $('.notifications').show().animate({
                    opacity: 1.0
                }, ALERT_DURATION).slideUp(ALERT_ANIMATION);
            },
            config: function () {
                $('#calendar').css('visibility', 'visible');
            }
        },
        methods: {
            ...mapActions(['loadData', 'addProgram', 'updateProgram', 'updateProgramRoom', 'removeProgram']),

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
                }
            },

            /**
             * Nedovolí kolize v místnosti a u automaticky přihlašovaných programů.
             */
            eventOverlap(stillEvent, movingEvent) {
                return (!stillEvent.extendedProps.block.autoRegistered && !movingEvent.extendedProps.block.autoRegistered)
                    && movingEvent.getResources()[0].id === '0';
            },

            /**
             * Zabrání přidání nového programu při zablokované editaci.
             * Workaround - droppable=false by mělo stačit.
             */
            eventAllow(info, draggedEvent) {
                return this.config.allowed_modify_schedule;
            },

            /**
             * Zpracuje přepnutí view a uloží zvolené.
             */
            handleChangeView(view) {
                this.initialView = view;
                this.$refs.fullCalendar.getApi().changeView(view);
                localStorage.setItem('fcAdminInitialView', view);
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
             * Zpracuje přidání programu.
             */
            handleEventReceive(info) {
                this.addProgram(info);
            },

            /**
             * Zpracuje přesunutí programu.
             */
            handleEventDrop(info) {
                this.updateProgram(info);
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
             * Zpracuje změnu místnosti z modal okna.
             */
            handleEventUpdateRoom() {
                $('#program-modal').modal('hide');
                this.updateProgramRoom(this.selectedEventInfo);
            },

            /**
             * Zpracuje odstranění programu z modal okna.
             */
            handleEventRemove() {
                $('#program-modal').modal('hide');
                this.removeProgram(this.selectedEventInfo);
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
</style>