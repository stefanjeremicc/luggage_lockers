import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../components/AdminLayout.vue';

const routes = [
    {
        path: '/admin/login',
        component: () => import('../views/Login.vue'),
        name: 'login',
    },
    {
        path: '/admin/forgot-password',
        component: () => import('../views/ForgotPassword.vue'),
        name: 'forgot-password',
    },
    {
        path: '/admin/reset-password',
        component: () => import('../views/ResetPassword.vue'),
        name: 'reset-password',
    },
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: 'change-password', component: () => import('../views/ChangePassword.vue'), name: 'change-password' },
            { path: '', component: () => import('../views/Dashboard.vue'), name: 'dashboard' },
            { path: 'bookings', component: () => import('../views/Bookings.vue'), name: 'bookings' },
            { path: 'lockers', component: () => import('../views/Lockers.vue'), name: 'lockers' },
            { path: 'lockers/:id', component: () => import('../views/LockerDetail.vue'), name: 'locker-detail' },
            { path: 'locations', component: () => import('../views/Locations.vue'), name: 'locations' },
            { path: 'locations/new', component: () => import('../views/LocationEdit.vue'), name: 'location-new' },
            { path: 'locations/:id/edit', component: () => import('../views/LocationEdit.vue'), name: 'location-edit' },
            { path: 'pricing', component: () => import('../views/Pricing.vue'), name: 'pricing' },
            { path: 'blog', component: () => import('../views/Blog.vue'), name: 'blog' },
            { path: 'blog/new', component: () => import('../views/BlogEdit.vue'), name: 'blog-new' },
            { path: 'blog/:id/edit', component: () => import('../views/BlogEdit.vue'), name: 'blog-edit' },
            { path: 'faqs', component: () => import('../views/Faqs.vue'), name: 'faqs' },
            { path: 'faqs/new', component: () => import('../views/FaqEdit.vue'), name: 'faq-new' },
            { path: 'faqs/:id/edit', component: () => import('../views/FaqEdit.vue'), name: 'faq-edit' },
            { path: 'reviews', component: () => import('../views/Reviews.vue'), name: 'reviews' },
            { path: 'notification-templates', component: () => import('../views/NotificationTemplates.vue'), name: 'notification-templates' },
            { path: 'pages', component: () => import('../views/Pages.vue'), name: 'pages' },
            { path: 'pages/:slug/edit', component: () => import('../views/PageEdit.vue'), name: 'page-edit' },
            { path: 'users', component: () => import('../views/Users.vue'), name: 'users' },
            { path: 'settings', component: () => import('../views/Settings.vue'), name: 'settings' },
        ],
        beforeEnter: (to, from, next) => {
            const token = localStorage.getItem('admin_token');
            if (!token) next('/admin/login');
            else next();
        },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
