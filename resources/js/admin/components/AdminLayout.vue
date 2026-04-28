<template>
    <div class="min-h-screen bg-[#0A0A0A]">
        <!-- Mobile top bar (hidden on md+) -->
        <header class="md:hidden sticky top-0 z-40 bg-[#0F0F0F]/95 backdrop-blur border-b border-[#2A2A2A] flex items-center justify-between px-4 py-2.5">
            <button @click="drawerOpen = true" class="p-2 -ml-2 rounded-lg hover:bg-[#1A1A1A] text-white" aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <router-link to="/admin" class="flex items-center">
                <img src="/images/logo.png" alt="Belgrade Luggage Locker" class="h-9 w-auto">
            </router-link>
            <button @click="userMenuOpen = !userMenuOpen" ref="mobileUserBtn"
                class="w-9 h-9 rounded-full bg-gradient-to-br from-[#F59E0B] to-[#D97706] flex items-center justify-center text-black font-semibold text-sm">
                {{ initials }}
            </button>
        </header>

        <!-- Mobile drawer overlay -->
        <Transition name="fade">
            <div v-if="drawerOpen" @click="drawerOpen = false"
                class="md:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"></div>
        </Transition>

        <div class="flex">
            <!-- Sidebar (drawer on mobile, fixed on md+) -->
            <aside
                class="fixed top-0 left-0 bottom-0 w-72 md:w-64 bg-[#0F0F0F] border-r border-[#2A2A2A] flex flex-col z-50 transition-transform duration-300 md:translate-x-0"
                :class="drawerOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
                <div class="h-[65px] px-5 md:px-6 border-b border-[#2A2A2A] flex items-center justify-between">
                    <router-link to="/admin" class="flex items-center" @click="drawerOpen = false">
                        <img src="/images/logo.png" alt="Belgrade Luggage Locker" class="h-9 w-auto">
                    </router-link>
                    <button @click="drawerOpen = false" class="md:hidden p-1 text-[#A0A0A0] hover:text-white" aria-label="Close menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="flex-1 p-3 md:p-4 space-y-1 overflow-y-auto">
                    <router-link v-for="item in visibleNavItems" :key="item.path" :to="item.path"
                        @click="drawerOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
                        :class="isActive(item.path) ? 'bg-[#F59E0B]/10 text-[#F59E0B]' : 'text-[#A0A0A0] hover:text-white hover:bg-[#1A1A1A]'">
                        <span v-html="item.icon" class="w-5 h-5 shrink-0"></span>
                        {{ item.label }}
                    </router-link>
                </nav>
            </aside>

            <!-- Main content -->
            <main class="flex-1 md:ml-64 flex flex-col min-h-screen w-full min-w-0">
                <!-- Desktop top bar (hidden on mobile) -->
                <header class="hidden md:flex sticky top-0 z-30 bg-[#0A0A0A]/90 backdrop-blur border-b border-[#2A2A2A] h-[65px] px-8 items-center justify-end gap-4">
                    <div class="relative" ref="menuRef">
                        <button @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg hover:bg-[#1A1A1A] transition">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#F59E0B] to-[#D97706] flex items-center justify-center text-black font-semibold text-sm">
                                {{ initials }}
                            </div>
                            <div class="text-left">
                                <div class="text-sm text-white leading-tight">{{ user?.name || 'Admin' }}</div>
                            </div>
                            <svg class="w-4 h-4 text-[#A0A0A0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <UserMenu v-if="userMenuOpen" :user="user" @logout="handleLogout" />
                    </div>
                </header>

                <!-- Mobile user menu (anchored to mobile top bar) -->
                <div v-if="userMenuOpen" class="md:hidden fixed top-14 right-3 z-50" ref="mobileMenuRef">
                    <UserMenu :user="user" @logout="handleLogout" />
                </div>

                <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-x-hidden">
                    <router-view />
                </div>
            </main>
        </div>

        <Toaster />
        <ConfirmModal :model-value="confirm.open.value"
            :title="confirm.state.value.title"
            :message="confirm.state.value.message"
            :variant="confirm.state.value.variant"
            :confirm-text="confirm.state.value.confirmText"
            :cancel-text="confirm.state.value.cancelText"
            @confirm="confirm._confirm"
            @cancel="confirm._cancel" />
    </div>
</template>
<script setup>
import { ref, computed, onMounted, onUnmounted, watch, h } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useConfirm } from '../composables/useConfirm';
import Toaster from './Toaster.vue';
import ConfirmModal from './ConfirmModal.vue';

const confirm = useConfirm();

const { user, logout } = useAuth();
const route = useRoute();
const drawerOpen = ref(false);
const userMenuOpen = ref(false);
const menuRef = ref(null);
const mobileMenuRef = ref(null);
const mobileUserBtn = ref(null);

const initials = computed(() => {
    const name = user.value?.name || 'A';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const isActive = (path) => {
    if (path === '/admin') return route.path === '/admin' || route.path === '/admin/';
    return route.path === path || route.path.startsWith(path + '/');
};

// Close user menu when route changes
watch(() => route.path, () => { userMenuOpen.value = false; drawerOpen.value = false; });

const closeOnOutside = (e) => {
    if (!userMenuOpen.value) return;
    const inDesktop = menuRef.value && menuRef.value.contains(e.target);
    const inMobile = (mobileMenuRef.value && mobileMenuRef.value.contains(e.target)) ||
                     (mobileUserBtn.value && mobileUserBtn.value.contains(e.target));
    if (!inDesktop && !inMobile) userMenuOpen.value = false;
};

// Close drawer on ESC
const onKey = (e) => {
    if (e.key === 'Escape') { drawerOpen.value = false; userMenuOpen.value = false; }
};

onMounted(() => {
    document.addEventListener('click', closeOnOutside);
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    document.removeEventListener('click', closeOnOutside);
    document.removeEventListener('keydown', onKey);
});

const handleLogout = () => {
    userMenuOpen.value = false;
    logout();
};

// Dropdown panel as an inline component so both mobile + desktop reuse it
const UserMenu = {
    props: ['user'],
    emits: ['logout'],
    setup(props, { emit }) {
        return () => h('div', { class: 'w-56 bg-[#111] border border-[#2A2A2A] rounded-lg shadow-2xl overflow-hidden' }, [
            h('div', { class: 'px-4 py-3 border-b border-[#2A2A2A]' }, [
                h('div', { class: 'text-sm text-white font-medium truncate' }, props.user?.name || 'Admin'),
            ]),
            h(RouterLink, {
                to: '/admin/change-password',
                class: 'w-full text-left px-4 py-2.5 text-sm text-white hover:bg-[#1A1A1A] flex items-center gap-2 border-b border-[#2A2A2A]',
            }, () => [
                h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
                    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' })]),
                'Change password',
            ]),
            h('button', {
                class: 'w-full text-left px-4 py-2.5 text-sm text-[#EF4444] hover:bg-[#1A1A1A] flex items-center gap-2',
                onClick: () => emit('logout'),
            }, [
                h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' },
                    [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' })]),
                'Log out',
            ]),
        ]);
    },
};

const navItems = [
    { path: '/admin', label: 'Dashboard', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>' },
    { path: '/admin/bookings', label: 'Bookings', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' },
    { path: '/admin/lockers', label: 'Lockers', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>' },
    { path: '/admin/locations', label: 'Locations', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
    { path: '/admin/pricing', label: 'Pricing', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
    { path: '/admin/blog', label: 'Blog', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>' },
    { path: '/admin/faqs', label: 'FAQs', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
    { path: '/admin/reviews', label: 'Reviews', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>' },
    { path: '/admin/notification-templates', label: 'Notifications', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
    { path: '/admin/pages', label: 'Pages & SEO', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>' },
    { path: '/admin/users', label: 'Users', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 01-4 4"/></svg>', superOnly: true },
    { path: '/admin/settings', label: 'Settings', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
];

const visibleNavItems = computed(() =>
    navItems.filter(i => !i.superOnly || user.value?.role === 'super_admin')
);
</script>
<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
