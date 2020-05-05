'use strict';

import Vue from 'vue';
import Calendar from './Calendar.vue';
import store from './store'

new Vue({
    el: '#calendar',
    store,
    render: h => h(Calendar),
});