'use strict';

import Vue from 'vue';
import Calendar from './Calendar.vue';
import BlocksList from './BlocksList.vue';
import store from './store'

new Vue({
    el: '#calendar',
    store,
    render: h => h(Calendar),
});

new Vue({
    el: '#blocks-list',
    store,
    render: h => h(BlocksList),
});