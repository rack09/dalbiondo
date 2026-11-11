import { createApp } from 'vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import App from './App.vue';
import StaffAccess from './StaffAccess.vue';

const container = document.getElementById('app');
const page = container.dataset.page === 'staff' ? StaffAccess : App;

createApp(page).mount(container);