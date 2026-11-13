import { createApp, defineAsyncComponent } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import StaffAccess from './StaffAccess.vue';
import InviteActivation from './InviteActivation.vue';

const container = document.getElementById('app');

const pages = {
    menu: defineAsyncComponent(() => import('./App.vue')),
    staff: StaffAccess,
    invitation: InviteActivation,
};

createApp(pages[container.dataset.page] ?? pages.menu).mount(container);