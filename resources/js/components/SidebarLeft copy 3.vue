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
const isUserMenuOpen = ref(false)
const isPriceMenuOpen = ref(false)
const isPaymentVerificationMenuOpen = ref(false)



/* ===============================
 * ROUTE MATCH
 * =============================== */
const isEventRoute = computed(() =>
  [
    'admin.events',
    'admin.event-days',
    'admin.rooms',
    'admin.activities',
  ].includes(route.name)
)

const isUserRoute = computed(() =>
  [
    'admin.users',
    'admin.participant-categories',
    'admin.participants',
  ].includes(route.name)
)

const isPriceRoute = computed(() =>
  [
    'admin.pricing',
    'admin.banks',
  ].includes(route.name)
)

const isPaymentVerificationRoute = computed(() =>
  [
    'admin.payment-verifications.queue',
    'admin.payment-verifications.history',
  ].includes(route.name)
)

const isOperationalRoute = computed(() =>
  ['admin.sessions'].includes(route.name)
)

const isTransactionalRoute = computed(() =>
  ['admin.registrations', 'admin.payments'].includes(route.name)
)


/* auto open jika route termasuk event */
watch(isEventRoute, (val) => {
    if (val) isEventMenuOpen.value = true
}, { immediate: true })

watch(isUserRoute, val => {
  if (val) isUserMenuOpen.value = true
}, { immediate: true })

watch(isPriceRoute, val => {
  if (val) isPriceMenuOpen.value = true
}, { immediate: true })

watch(isPaymentVerificationRoute, (val) => {
  if (val) isPaymentVerificationMenuOpen.value = true
}, { immediate: true })



const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

const togglePriceMenu = () => {
  isPriceMenuOpen.value = !isPriceMenuOpen.value
}

const toggleEventMenu = () => {
  isEventMenuOpen.value = !isEventMenuOpen.value
}

const togglePaymentVerificationMenu = () => {
  isPaymentVerificationMenuOpen.value = !isPaymentVerificationMenuOpen.value
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

          <li class="nav-header">MASTER DATA</li>
          
          <li class="nav-item" :class="{ 'menu-open': isUserMenuOpen }">
            <a
              href="#"
              class="nav-link"
              :class="{ active: isUserRoute }"
              @click.prevent="toggleUserMenu"
            >
              <i class="nav-icon fas fa-users"></i>
              <p>
                Pengguna
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul class="nav nav-treeview" v-show="isUserMenuOpen">
              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.users' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>User</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.participant-categories' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Participant Categories</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.participants' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Participants</p>
                </router-link>
              </li>
            </ul>
          </li>


          <!-- ===========================
           MANAGEMEN EVENT (TREEVIEW)
          ============================ -->
          <li class="nav-item" :class="{ 'menu-open': isEventMenuOpen }">
            <a
              href="#"
              class="nav-link"
              :class="{ active: isEventRoute }"
              @click.prevent="toggleEventMenu"
            >
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>
                Event & Struktur
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul class="nav nav-treeview" v-show="isEventMenuOpen">
              <li class="nav-item">
                <router-link :to="{ name: 'admin.events' }" class="nav-link" active-class="active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Events</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link :to="{ name: 'admin.event-days' }" class="nav-link" active-class="active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Event Days</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link :to="{ name: 'admin.rooms' }" class="nav-link" active-class="active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Rooms</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link :to="{ name: 'admin.activities' }" class="nav-link" active-class="active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Activities</p>
                </router-link>
              </li>
            </ul>
          </li>

          <li class="nav-item" :class="{ 'menu-open': isPriceMenuOpen }">
            <a
              href="#"
              class="nav-link"
              :class="{ active: isPriceRoute }"
              @click.prevent="togglePriceMenu"
            >
              <i class="nav-icon fas fa-tags"></i>
              <p>
                Pembayaran
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul class="nav nav-treeview" v-show="isPriceMenuOpen">
              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.pricing' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Pricing</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.banks' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Banks</p>
                </router-link>
              </li>
            </ul>
          </li>



          <li class="nav-header">OPERASIONAL EVENT</li>

          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.sessions' }"
              class="nav-link"
              :class="{ active: isOperationalRoute }"
            >
              <i class="nav-icon fas fa-clock"></i>
              <p>Sessions</p>
            </router-link>
          </li>

          <li class="nav-header">TRANSAKSIONAL</li>

          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.registrations' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-file-invoice"></i>
              <p>Registrations</p>
            </router-link>
          </li>


          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.payments' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-money-bill-wave"></i>
              <p>Payments</p>
            </router-link>
          </li>

          <li class="nav-item" :class="{ 'menu-open': isPaymentVerificationMenuOpen }">
            <a
              href="#"
              class="nav-link"
              :class="{ active: isPaymentVerificationRoute }"
              @click.prevent="togglePaymentVerificationMenu"
            >
              <i class="nav-icon fas fa-check-double"></i>
              <p>
                Payment Verifications
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul class="nav nav-treeview" v-show="isPaymentVerificationMenuOpen">
              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.payment-verifications.queue' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>Queue</p>
                </router-link>
              </li>

              <li class="nav-item">
                <router-link
                  :to="{ name: 'admin.payment-verifications.history' }"
                  class="nav-link"
                  active-class="active"
                >
                  <i class="far fa-circle nav-icon"></i>
                  <p>History</p>
                </router-link>
              </li>
            </ul>
          </li>

          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.finance.dashboard' }"
              class="nav-link"
              active-class="active"
            >
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Finance Dashboard</p>
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
