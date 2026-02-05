<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useSettingStore } from '../stores/SettingStore'

const authUserStore = useAuthUserStore()
const settingStore = useSettingStore()
const route = useRoute()

const logout = () => authUserStore.logout()

/* ===============================
 * TREEVIEW STATE (VUE CONTROLLED)
 * =============================== */
const isEventMenuOpen = ref(false)

/* ===============================
 * ROUTE MATCH
 * =============================== */
const isEventRoute = computed(() =>
  [
    'admin.events',
    'admin.event-days',
    'admin.rooms',
    'admin.activities',
    'admin.sessions',
  ].includes(route.name)
)

/* auto open jika route termasuk event */
watch(
  isEventRoute,
  (val) => {
    if (val) isEventMenuOpen.value = true
  },
  { immediate: true }
)

/* toggle manual */
const toggleEventMenu = () => {
  isEventMenuOpen.value = !isEventMenuOpen.value
}
</script>

<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- BRAND -->
    <a href="#" class="brand-link">
      <img
        src="/app_logo.png"
        alt="App Logo"
        class="brand-image img-circle elevation-3"
        style="opacity:.8"
      >
      <span class="brand-text font-weight-light">
        {{ settingStore.setting.app_name }}
      </span>
    </a>

    <div class="sidebar">
      <!-- USER PANEL -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img
            :src="authUserStore.user.avatar"
            class="img-circle elevation-2"
            alt="User Image"
          >
        </div>
        <div class="info">
          <a href="#" class="d-block" style="font-size: small;">{{ authUserStore.user.name }}</a>
        </div>
      </div>

      <!-- MENU -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

          <!-- DASHBOARD -->
          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.dashboard' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </router-link>
          </li>

          <!-- USERS -->
          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.users' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-users"></i>
              <p>User</p>
            </router-link>
          </li>

          <!-- ===========================
           MANAGEMEN EVENT (TREEVIEW)
          ============================ -->
          <li
            class="nav-item"
            :class="{ 'menu-open': isEventMenuOpen }"
          >
            <a
              href="#"
              class="nav-link"
              :class="{ active: isEventRoute }"
              @click.prevent="toggleEventMenu"
            >
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>
                Manajemen Event
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul
              class="nav nav-treeview"
              v-show="isEventMenuOpen"
            >
              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.events' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Events</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.event-days' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Event Days</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.rooms' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Rooms</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.activities' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Activities</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.sessions' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Sessions</p>
                </router-link>
              </li>
            </ul>
          </li>


          <!-- PARTICIPANT CATEGORIES (MENU UTAMA) -->
          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.participant-categories' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-id-badge"></i>
              <p>Participant Categories</p>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.participants' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-user-check"></i>
              <p>Participants</p>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.pricing' }"
              class="nav-link"
              active-class="active"
            >
              <i class="fas fa-tags nav-icon"></i>
              <p>Pricing</p>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.banks' }"
              class="nav-link"
              active-class="active"
            >
              <i class="fas fa-university nav-icon"></i>
              <p>Banks</p>
            </router-link>
          </li>








          <li class="nav-header">KELOLA</li>

          <!-- SETTINGS -->
          <li class="nav-item">
            <router-link
              to="/admin/settings"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-cog"></i>
              <p>Pengaturan</p>
            </router-link>
          </li>

          <!-- LOGOUT -->
          <li class="nav-item">
            <a href="#" class="nav-link" @click.prevent="logout">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Keluar</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>
</template>
