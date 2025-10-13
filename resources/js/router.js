import { createRouter, createWebHistory } from 'vue-router';
import authUtils from './utils/auth';

// Composants chargés immédiatement (essentiels)
import Home from './pages/Home.vue';
import Login from './pages/auth/Login.vue';

// Lazy loading pour toutes les autres pages
const Events = () => import('./pages/Events.vue');
const EventDetail = () => import('./pages/EventDetail.vue');
const Checkout = () => import('./pages/Checkout.vue');
const Register = () => import('./pages/auth/Register.vue');
const EmailVerification = () => import('./pages/auth/EmailVerification.vue');
const EmailVerificationResult = () => import('./pages/EmailVerificationResult.vue');
const ForgotPassword = () => import('./pages/auth/ForgotPassword.vue');
const ResetPassword = () => import('./pages/auth/ResetPassword.vue');
const TicketRetrieve = () => import('./pages/TicketRetrieve.vue');
const TicketDownload = () => import('./pages/TicketDownload.vue');
const TicketSuccess = () => import('./pages/TicketSuccess.vue');
const PaymentSuccess = () => import('./pages/PaymentSuccess.vue');

// Espace client
const Account = () => import('./pages/account/Account.vue');
const MyTickets = () => import('./pages/account/MyTickets.vue');
const MyOrders = () => import('./pages/account/MyOrders.vue');
const Profile = () => import('./pages/account/Profile.vue');

// Espace organisateur
const OrganizerLayout = () => import('./layouts/OrganizerLayout.vue');
const OrganizerDashboard = () => import('./pages/organizer/OrganizerDashboard.vue');
const OrganizerEvents = () => import('./pages/organizer/Events.vue');
const OrganizerEventDetail = () => import('./pages/organizer/EventDetail.vue');
const OrganizerEventEdit = () => import('./pages/organizer/EventEdit.vue');
const EventCreate = () => import('./pages/organizer/EventCreate.vue');
const BalanceManagement = () => import('./pages/organizer/BalanceManagement.vue');
const OrganizerProfile = () => import('./pages/organizer/OrganizerProfile.vue');
// const PhysicalSales = () => import('./pages/organizer/PhysicalSales.vue');
// const PaymentHistory = () => import('./pages/organizer/PaymentHistory.vue');

// Layout et pages Admin avec chunk grouping
const AdminLayout = () => import(/* webpackChunkName: "admin-layout" */ './layouts/AdminLayout.vue');
const AdminDashboard = () => import(/* webpackChunkName: "admin-core" */ './pages/admin/AdminDashboard.vue');
const UserManagement = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/UserManagement.vue');
const OrganizerManagement = () => import(/* webpackChunkName: "admin-organizers" */ './pages/admin/OrganizerManagement.vue');
const EventManagement = () => import(/* webpackChunkName: "admin-events" */ './pages/admin/EventManagement.vue');
const OrderManagement = () => import(/* webpackChunkName: "admin-orders" */ './pages/admin/OrderManagement.vue');
const PaymentTracking = () => import(/* webpackChunkName: "admin-payments" */ './pages/admin/PaymentTracking.vue');
const PayoutDashboard = () => import(/* webpackChunkName: "admin-payouts" */ './pages/admin/PayoutDashboard.vue');
const OrganizerBalanceConfig = () => import(/* webpackChunkName: "admin-payouts" */ './pages/admin/OrganizerBalanceConfig.vue');
const AdminReports = () => import(/* webpackChunkName: "admin-reports" */ './pages/admin/Reports.vue');
const AdminSettings = () => import(/* webpackChunkName: "admin-settings" */ './pages/admin/Settings.vue');
const AdminProfile = () => import(/* webpackChunkName: "admin-settings" */ './pages/admin/Profile.vue');
const CategoryManagement = () => import(/* webpackChunkName: "admin-categories" */ './pages/admin/CategoryManagement.vue');
const VenueManagement = () => import(/* webpackChunkName: "admin-venues" */ './pages/admin/VenueManagement.vue');
const UserTypes = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/UserTypes.vue');
const Privileges = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/Privileges.vue');
const Roles = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/Roles.vue');
const Admins = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/Admins.vue');
const Clients = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/Clients.vue');
const OrganizersUsers = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/OrganizersUsers.vue');
const TrashedUsers = () => import(/* webpackChunkName: "admin-users" */ './pages/admin/TrashedUsers.vue');

// Scanner
const ScannerApp = () => import('./pages/scanner/ScannerApp.vue');

const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/events', component: Events, name: 'events' },
    { path: '/events/:slug', component: EventDetail, name: 'event-detail' },
    { path: '/checkout/:eventSlug', component: Checkout, name: 'checkout' },
    { path: '/login', component: Login, name: 'login' },
    { path: '/register', component: Register, name: 'register' },
    { path: '/email/verify/:id?/:hash?', component: EmailVerification, name: 'email-verification' },
    { path: '/email-verification-result', component: EmailVerificationResult, name: 'email-verification-result' },
    { path: '/forgot-password', component: ForgotPassword, name: 'forgot-password' },
    { path: '/reset-password', component: ResetPassword, name: 'reset-password' },
    { path: '/retrieve-ticket', component: TicketRetrieve, name: 'ticket-retrieve' },
    { path: '/ticket/:id/download', component: TicketDownload, name: 'ticket-download' },
    { path: '/ticket/:id', component: TicketDownload, name: 'ticket-view' },
    { path: '/ticket-success', component: TicketSuccess, name: 'ticket-success' },
    { path: '/payment-success', component: PaymentSuccess, name: 'payment-success' },
    
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
    
    // Routes organisateur avec layout
    { 
        path: '/organizer', 
        component: OrganizerLayout,
        meta: { requiresAuth: true, role: 'organizer' },
        children: [
            { path: '', redirect: '/organizer/dashboard' },
            { path: 'dashboard', component: OrganizerDashboard, name: 'organizer-dashboard' },
            { path: 'events', component: OrganizerEvents, name: 'organizer-events' },
            { path: 'events/create', component: EventCreate, name: 'organizer-event-create' },
            { path: 'events/:slug', component: OrganizerEventDetail, name: 'organizer-event-detail' },
            { path: 'events/:slug/edit', component: OrganizerEventEdit, name: 'organizer-event-edit' },
            { path: 'balance', component: BalanceManagement, name: 'organizer-balance' },
            { path: 'profile', component: OrganizerProfile, name: 'organizer-profile' },
            // { path: 'physical-sales', component: PhysicalSales, name: 'organizer-physical-sales' },
            // { path: 'payments', component: PaymentHistory, name: 'organizer-payments' },
        ]
    },
    
    // Routes admin avec layout
    { 
        path: '/admin', 
        component: AdminLayout,
        meta: { requiresAuth: true, role: 'admin' },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            { path: 'dashboard', component: AdminDashboard, name: 'admin-dashboard' },
            { path: 'users', component: UserManagement, name: 'admin-users' },
            { path: 'user-types', component: UserTypes, name: 'admin-user-types' },
            { path: 'privileges', component: Privileges, name: 'admin-privileges' },
            { path: 'roles', component: Roles, name: 'admin-roles' },
            { path: 'admins', component: Admins, name: 'admin-admins' },
            { path: 'clients', component: Clients, name: 'admin-clients' },
            { path: 'organizers-users', component: OrganizersUsers, name: 'admin-organizers-users' },
            { path: 'trashed-users', component: TrashedUsers, name: 'admin-trashed-users' },
            { path: 'organizers', component: OrganizerManagement, name: 'admin-organizers' },
            { path: 'events', component: EventManagement, name: 'admin-events' },
            { path: 'orders', component: OrderManagement, name: 'admin-orders' },
            { path: 'payments', component: PaymentTracking, name: 'admin-payments' },
            { path: 'payouts', component: PayoutDashboard, name: 'admin-payouts' },
            { path: 'balance-config', component: OrganizerBalanceConfig, name: 'admin-balance-config' },
            { path: 'categories', component: CategoryManagement, name: 'admin-categories' },
            { path: 'venues', component: VenueManagement, name: 'admin-venues' },
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

// Navigation guards
router.beforeEach((to, from, next) => {
    // Vérifier et migrer les données d'authentification si nécessaire
    const userRole = authUtils.checkAndMigrateAuth();
    const token = authUtils.getToken();
    
    console.log('Navigation Guard - To:', to.path, 'Role:', userRole, 'Token:', !!token);
    
    // Vérifier l'authentification
    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (!token) {
            console.log('No token, redirecting to login');
            next({ name: 'login', query: { redirect: to.fullPath } });
            return;
        }
    }
    
    // Vérifier le rôle pour les routes qui en nécessitent un
    if (to.matched.some(record => record.meta.role)) {
        const requiredRole = to.matched.find(record => record.meta.role)?.meta.role;
        console.log('Required role:', requiredRole, 'User role:', userRole);
        
        if (userRole !== requiredRole) {
            console.log('Role mismatch, redirecting based on role');
            // Si l'utilisateur essaie d'accéder à une page admin sans être admin
            if (requiredRole === 'admin' && userRole !== 'admin') {
                next({ name: 'login', query: { redirect: to.fullPath } });
                return;
            }
            next('/');
            return;
        }
    }
    
    next();
});

export default router;