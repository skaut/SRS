'use strict';

import Vue from "vue";
import Vuex from "vuex";
import axios from "axios";
import VueAxios from "vue-axios"

const COLOR_VOLUNTARY = '#0077F7';
const COLOR_MANDATORY = '#D53343';
const COLOR_AUTO_REGISTERED = '#F7BB07';

Vue.use(Vuex);
Vue.use(VueAxios, axios);
Vue.axios.defaults.baseURL = "http://srs.loc/api/schedule/";

export default new Vuex.Store({
    state: {
        config: {
            seminar_from_date: "2000-01-01",
            seminar_to_date: "2000-01-01",
            allowed_modify_schedule: false
        },
        blocks: [],
        blocksMap: [],
        resources: [],
        events: [],
        loading: 0
    },
    getters: {
        getBlockById: state => id => {
            return state.blocksMap[id];
        }
    },
    mutations: {
        setConfig(state, config) {
            state.config = config;
        },
        setBlocks(state, blocks) {
            state.blocks = blocks;
        },
        setBlocksMap(state, blocksMap) {
            state.blocksMap = blocksMap;
        },
        setResources(state, resources) {
            state.resources = resources;
        },
        setEvents(state, events) {
            state.events = events;
        },
        incrementLoading(state) {
            state.loading++;
        },
        decrementLoading(state) {
            state.loading--;
        }
    },
    actions: {
        loadData({commit}) {
            commit('incrementLoading');
            Vue.axios.get('get-calendar-config')
                .then(res => {
                    const config = JSON.parse(res.data);
                    commit('setConfig', config);
                    commit('decrementLoading');
                }).catch(error => {
                    throw new Error(`API ${error}`); // todo
                });

            commit('incrementLoading');
            axios.all([
                Vue.axios.get('get-blocks'),
                Vue.axios.get('get-rooms'),
                Vue.axios.get('get-programs-admin')
            ]).then(axios.spread((blocksRes, roomsRes, programsRes) => {
                const blocks = Array.prototype.slice.call(JSON.parse(blocksRes.data))
                    .map(function(block) {
                        return {
                            id: block.id,
                            name: block.name,
                            category: block.category,
                            lectorsNames: block.lectors_names,
                            capacity: block.capacity,
                            duration: block.duration,
                            mandatory: block.mandatory,
                            autoRegistered: block.auto_registered,
                            programsCount: block.programs_count,
                            color: block.mandatory ? (block.auto_registered ? COLOR_AUTO_REGISTERED : COLOR_MANDATORY) : COLOR_VOLUNTARY
                        }
                    });
                commit('setBlocks', blocks);

                const blocksMap = blocks
                    .reduce(function (map, obj) {
                        map[obj.id] = obj;
                        return map;
                        }, {});
                commit('setBlocksMap', blocksMap);

                const resources = Array.prototype.slice.call(JSON.parse(roomsRes.data))
                    .map(function(room) {
                        return {
                            id: room.id,
                            title: room.name + (room.capacity ? (' (' + room.capacity + ')') : ''),
                            extendedProps: {
                                capacity: room.capacity
                            }
                        }
                    });
                resources.push({id: 0, title: 'nezarazeno'});
                commit('setResources', resources);

                const resourcesMap = resources
                     .reduce(function (map, obj) {
                         map[obj.id] = obj;
                         return map;
                         }, {});

                const events = Array.prototype.slice.call(JSON.parse(programsRes.data))
                    .map(function(program) {
                        const block = blocksMap[program.block_id];
                        return {
                            id: program.id,
                            resourceId: program.room_id || 0,
                            title: block.name,
                            start: program.start,
                            end: program.end,
                            color: block.color,
                            extendedProps: {
                                block: block
                            }
                        }
                    });
                commit('setEvents', events);

                commit('decrementLoading');
            })).catch(error => {
                throw new Error(`API ${error}`); // todo
            });
        },
        addProgram(context, event) {
            const data = {
                block_id: event.extendedProps.block.id,
                room_id: event.resourceId > 0 ? event.resourceId : null,
                start: event.start.toISOString()
            };
            Vue.axios.post('save-program', data)
                .then(res => {
                    const program = JSON.parse(res.data);
                    event.id = program.id;
                }).catch(error => {
                    throw new Error(`API ${error}`); //todo
                });
        },
        updateProgram(context, event) {

        },
        removeProgram(context, event) {

        }
    }
});