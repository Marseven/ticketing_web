import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';

// Composants
import App from './App.vue';

// Pages
import Home from './pages/Home.vue';
import Events from './pages/Events.vue';
import EventDetail from './pages/EventDetail.vue';
import Checkout from './pages/Checkout.vue';
import Login from './pages/auth/Login.vue';
import Register from './pages/auth/Register.vue';
import TicketRetrieve from './pages/TicketRetrieve.vue';
import TicketDownload from './pages/TicketDownload.vue';

// Espace client
import Account from './pages/account/Account.vue';
import MyTickets from './pages/account/MyTickets.vue';
import MyOrders from './pages/account/MyOrders.vue';
import Profile from './pages/account/Profile.vue';

// Espace organisateur
import OrganizerDashboard from './pages/organizer/Dashboard.vue';
import OrganizerEvents from './pages/organizer/Events.vue';
import EventCreate from './pages/organizer/EventCreate.vue';

// Espace admin
import AdminDashboard from './pages/admin/Dashboard.vue';
import AdminUsers from './pages/admin/Users.vue';
import AdminEvents from './pages/admin/Events.vue';
import AdminTransactions from './pages/admin/Transactions.vue';
import AdminReports from './pages/admin/Reports.vue';
import AdminSettings from './pages/admin/Settings.vue';
import AdminProfile from './pages/admin/Profile.vue';

import ScannerApp from './pages/scanner/ScannerApp.vue';

// Configuration du routeur
const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/events', component: Events, name: 'events' },
    { path: '/events/:slug', component: EventDetail, name: 'event-detail' },
    { path: '/checkout/:eventSlug', component: Checkout, name: 'checkout' },
    { path: '/login', component: Login, name: 'login' },
    { path: '/register', component: Register, name: 'register' },
    { path: '/retrieve-ticket', component: TicketRetrieve, name: 'ticket-retrieve' },
    { path: '/ticket/:id/download', component: TicketDownload, name: 'ticket-download' },
    { path: '/ticket/:id', component: TicketDownload, name: 'ticket-view' },
    
    // Routes espace client
    { 
        path: '/account',
        component: Account,
        children: [
            { path: '', redirect: '/account/tickets' },
            { path: 'tickets', component: MyTickets, name: 'my-tickets' },
            { path: 'orders', component: MyOrders, name: 'my-orders' },
            { path: 'profile', component: Profile, name: 'profile' },
        ],
        meta: { requiresAuth: true }
    },
    
    // Routes organisateur
    { 
        path: '/organizer', 
        children: [
            { path: 'dashboard', component: OrganizerDashboard, name: 'organizer-dashboard' },
            { path: 'events', component: OrganizerEvents, name: 'organizer-events' },
            { path: 'events/create', component: EventCreate, name: 'organizer-event-create' },
        ]
    },
    
    // Routes admin
    { 
        path: '/admin', 
        children: [
            { path: 'dashboard', component: AdminDashboard, name: 'admin-dashboard' },
            { path: 'users', component: AdminUsers, name: 'admin-users' },
            { path: 'events', component: AdminEvents, name: 'admin-events' },
            { path: 'transactions', component: AdminTransactions, name: 'admin-transactions' },
            { path: 'reports', component: AdminReports, name: 'admin-reports' },
            { path: 'settings', component: AdminSettings, name: 'admin-settings' },
            { path: 'profile', component: AdminProfile, name: 'admin-profile' },
        ]
    },
    
    // Scanner
    { path: '/scanner', component: ScannerApp, name: 'scanner' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const pinia = createPinia();
const app = createApp(App);

app.use(router);
app.use(pinia);

// Initialiser le store d'authentification
import { useAuthStore } from './stores/auth.js';
app.mount('#app');

// Initialiser l'authentification après le montage
const authStore = useAuthStore();
authStore.initialize();
