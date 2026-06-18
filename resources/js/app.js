

import Alpine from 'alpinejs';
import sort from '@alpinejs/sort';
import { initFrontend } from './frontend';

Alpine.plugin(sort);
window.Alpine = Alpine;

Alpine.start();
initFrontend();
