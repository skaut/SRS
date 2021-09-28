'use strict';

import Vue from 'vue';
import Calendar from './Calendar.vue';
import BlocksList from './BlocksList.vue';
import store from './store'

if(document.getElementById("calendar")) {
    new Vue({
        el: '#calendar',
        store,
        render: h => h(Calendar),
    });
}

if(document.getElementById("blocks-list")) {
    new Vue({
        el: '#blocks-list',
        store,
        render: h => h(BlocksList),
    });
}