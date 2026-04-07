import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../components/AdminLayout.vue';

const routes = [
    {
        path: '/admin/login',
        component: () => import('../views/Login.vue'),
        name: 'login',
    },
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: '', component: () => import('../views/Dashboard.vue'), name: 'dashboard' },
            { path: 'bookings', component: () => import('../views/Bookings.vue'), name: 'bookings' },
            { path: 'lockers', component: () => import('../views/Lockers.vue'), name: 'lockers' },
            { path: 'locations', component: () => import('../views/Locations.vue'), name: 'locations' },
            { path: 'pricing', component: () => import('../views/Pricing.vue'), name: 'pricing' },
            { path: 'blog', component: () => import('../views/Blog.vue'), name: 'blog' },
            { path: 'faqs', component: () => import('../views/Faqs.vue'), name: 'faqs' },
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
