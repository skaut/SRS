<template>
    <div class='blocks-list-app'>
        <div class="card card-body bg-light mb-3">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><span class="fa fa-magnifying-glass fa-fw"></span></span>
                </div>
                <input id="textbox-search" class="form-control" type="text" v-model="search">
            </div>

            <div class="custom-control custom-checkbox text-center mb-3">
                <input type="checkbox" id="checkbox-unassigned" class="custom-control-input" v-model="unassignedOnly">
                <label class="custom-control-label" for="checkbox-unassigned">jen nezařazené</label>
            </div>

            <div class="pre-scrollable">
                <ul id="blocks-container" class="list-group text-white">
                    <li class="list-group-item" v-for="block in filterBlocks()" :class="blockClass(block)" :data-block="block.id">
                        <div class="row justify-content-between">
                            <div class="col">
                                <b>{{ block.name }}</b>
                            </div>
                            <div class="col-auto text-right">
                                <span class="badge badge-dark badge-pill">{{ block.programsCount }} ×</span>
                            </div>
                        </div>
                        <table>
                            <tr>
                                <td class="pr-3 align-top">Kategorie</td>
                                <td>{{ block.category }}</td>
                            </tr>
                            <tr>
                                <td class="pr-3 align-top">Lektoři</td>
                                <td>{{ block.lectorsNames }}</td>
                            </tr>
                            <tr>
                                <td class="pr-3">Kapacita</td>
                                <td>{{ block.capacity }}</td>
                            </tr>
                        </table>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    'use strict';

    import { mapState, mapGetters } from 'vuex'
    import { Draggable } from "@fullcalendar/interaction";

    export default {
        data: function () {
            return {
                search: "",
                unassignedOnly: false
            }
        },
        mounted: function () {
            const self = this;
            new Draggable(document.getElementById('blocks-container'), {
                itemSelector: '.list-group-item',
                eventData: function (eventEl) {
                    const blockId = eventEl.getAttribute('data-block');
                    const block = self.getBlockById(blockId);
                    return {
                        title: block.name,
                        duration: {minutes: block.duration},
                        color: block.color,
                        resourceId: 0,
                        extendedProps: {
                            block: block
                        }
                    };
                }
            });
        },
        computed: {
            ...mapState(['blocks']),
            ...mapGetters(['getBlockById'])
        },
        methods: {
            /**
             * Vyfiltruje bloky podle podmínky (název, nepřiřazené)
             */
            filterBlocks() {
                const search = this.search.toLowerCase();
                const unassignedOnly = this.unassignedOnly;
                return this.blocks.filter(block =>
                    ((!unassignedOnly && !block.autoRegistered) || block.programsCount === 0)
                    && (search === "" || block.name.toLowerCase().includes(search))
                );
            },

            /**
             * Nastaví barvu bloku.
             */
            blockClass(block) {
                return block.mandatory ? (block.autoRegistered ? 'bg-warning' : 'bg-danger') : 'bg-primary';
            }
        }
    }
</script>