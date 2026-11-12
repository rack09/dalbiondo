import { createApp } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import App from './App.vue';
import StaffAccess from './StaffAccess.vue';
import InviteActivation from './InviteActivation.vue';

const container = document.getElementById('app');
const pages = {
    staff: StaffAccess,
    invitation: InviteActivation,
};

createApp(pages[container.dataset.page] ?? App).mount(container);