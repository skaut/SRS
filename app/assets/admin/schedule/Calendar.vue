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
                                        <span class="fa fa-exclamation-triangle"></span>
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
                        <button @click="handleEventUpdateRoom()" class="btn btn-primary pull-left" :disabled="!config.allowed_modify_schedule">Uložit</button>
                        <button @click="handleEventRemove()" class="btn btn-danger pull-right" :disabled="!config.allowed_modify_schedule">Odstranit</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="notifications">
            <div v-if="message" class="alert" :class="'alert-' + message.type">
                {{ message.text }}
            </div>
        </div>

        <div class="spinner pull-right mt-1" v-show="loading > 0">
            <span class="fa fa-spinner fa-pulse fa-2x"></span>
        </div>

        <FullCalendar id="calendar"
                      style="visibility: hidden"
                      ref="fullCalendar"
                      theme-system="bootstrap"
                      locale="cs"
                      timeZone="none"
                      aspect-ratio="1.85"
                      scheduler-license-key="GPL-My-Project-Is-Open-Source"
                      :plugins="calendarPlugins"
                      :views="calendarViews"
                      :default-view="defaultView"
                      :event-render="eventRender"
                      :view-skeleton-render="storeView"
                      :header="{left: 'timeGridSeminar,resourceTimelineSeminar', center: '', right: ''}"
                      :events="events"
                      :resources="resources"
                      :droppable="config.allowed_modify_schedule"
                      :event-start-editable="config.allowed_modify_schedule"
                      :event-resource-editable="config.allowed_modify_schedule"
                      :event-overlap="eventOverlap"
                      :event-allow="eventAllow"
                      @eventReceive="handleEventReceive"
                      @eventDrop="handleEventDrop"
                      @eventClick="handleEventClick"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
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
                calendarPlugins: [
                    timeGridPlugin,
                    resourceTimelinePlugin,
                    interactionPlugin,
                    bootstrapPlugin
                ],
                defaultView: localStorage.getItem("fcDefaultView") || "timeGridSeminar",
                selectedEventInfo: null
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading', 'message']),
            ...mapGetters(['getResourceById']),
            calendarViews() {
                return {
                    timeGridSeminar: {
                        type: 'timeGrid',
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        buttonText: "Na výšku",
                        allDaySlot: false,
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1}
                    },
                    resourceTimelineSeminar: {
                        type: 'resourceTimeline',
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        buttonText: "Na šířku",
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1},
                        resourceLabelText: "Místnosti"
                    },
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
            ...mapActions(['loadData', 'addProgram', 'updateProgram', 'updateProgramRoom', 'removeProgram']),

            /**
             * Vykreslí název místnosti v timeGridSeminar zobrazení.
             */
            eventRender(info) {
                if (info.view.type === 'timeGridSeminar') {
                    const eventResources = info.event.getResources();
                    if (eventResources.length > 0 && eventResources[0].id > 0) {
                        const titleEl = info.el.getElementsByClassName('fc-title')[0];
                        const roomEl = document.createElement('div');
                        roomEl.setAttribute('class', 'fc-room');
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
                    && movingEvent.getResources()[0].id === "0";
            },

            /**
             * Zabrání přidání nového programu při zablokované editaci.
             * Workaround - droppable=false by mělo stačit.
             */
            eventAllow(info, draggedEvent) {
                return this.config.allowed_modify_schedule;
            },

            /**
             * Uloží zvolené view.
             */
            storeView(info) {
                localStorage.setItem("fcDefaultView", info.view.type);
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

<style lang='scss'>
    @import '~@fullcalendar/core/main.css';
    @import '~@fullcalendar/daygrid/main.css';
    @import '~@fullcalendar/timegrid/main.css';
    @import '~@fullcalendar/timeline/main.css';
    @import '~@fullcalendar/resource-timeline/main.css';
    @import '~@fullcalendar/bootstrap/main.css';

    .fc-today {
        background-color: inherit !important;
    }
    .fc-title {
        font-weight: bold;
    }
    .fc-room {
        padding: 0 1px;
    }
</style>