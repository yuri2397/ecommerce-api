<template>
    <div class="admin-layout">
        <AdminHeader />

        <div class="admin-container">
            <AdminSidebar />

            <div class="admin-content">
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script>
import AdminHeader from './AdminHeader.vue';
import AdminSidebar from './AdminSidebar.vue';

export default {
    name: 'AdminLayout',
    components: {
        AdminHeader,
        AdminSidebar
    }
}
</script>

<style scoped>
.admin-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.admin-container {
    display: flex;
    flex: 1;
}

.admin-content {
    flex: 1;
    padding: 1.5rem;
    background-color: #f5f7f9;
    overflow-y: auto;
}

@media (max-width: 992px) {
    .admin-sidebar {
        position: fixed;
        left: -250px;
        top: 64px;
        bottom: 0;
        z-index: 1000;
        transition: left 0.3s ease;
    }

    .admin-sidebar.open {
        left: 0;
    }

    .admin-content {
        width: 100%;
    }
}
</style>
