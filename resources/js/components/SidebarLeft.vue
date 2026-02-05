<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '../stores/AuthUserStore'
import { useSettingStore } from '../stores/SettingStore'

const authUserStore = useAuthUserStore()
const settingStore = useSettingStore()
const route = useRoute()

const { can, canAny } = authUserStore
const logout = () => authUserStore.logout()

/* ===============================
 * TREEVIEW STATE
 * =============================== */
const isUserMenuOpen = ref(false)
const isEventMenuOpen = ref(false)
const isPriceMenuOpen = ref(false)
const isPaymentVerificationMenuOpen = ref(false)

/* ===============================
 * ACTIVE HELPERS (DITAMBAHKAN)
 * =============================== */
const isNameActive = (name) => route.name === name

const isAnyNameActive = (names = []) => {
  const cur = route.name
  return !!cur && names.includes(cur)
}

/* ===============================
 * ROUTE GROUP
 * =============================== */
const userRoutes = [
  'admin.users',
  'admin.participant-categories',
  'admin.participants',
]

const eventRoutes = [
  'admin.events',
  'admin.event-days',
  'admin.rooms',
  'admin.activities',
]

const priceRoutes = [
  'admin.pricing',
  'admin.banks',
]

const paymentVerificationRoutes = [
  'admin.payment-verifications.queue',
  'admin.payment-verifications.history',
]

/* ===============================
 * AUTO OPEN TREEVIEW
 * =============================== */
watch(() => isAnyNameActive(userRoutes), v => v && (isUserMenuOpen.value = true), { immediate: true })
watch(() => isAnyNameActive(eventRoutes), v => v && (isEventMenuOpen.value = true), { immediate: true })
watch(() => isAnyNameActive(priceRoutes), v => v && (isPriceMenuOpen.value = true), { immediate: true })
watch(
  () => isAnyNameActive(paymentVerificationRoutes),
  v => v && (isPaymentVerificationMenuOpen.value = true),
  { immediate: true }
)
</script>

<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- BRAND -->
    <a href="#" class="brand-link">
      <img src="/symcardfavicon.ico" class="brand-image img-circle elevation-3" />
      <span class="brand-text font-weight-light">
        {{ settingStore.setting.app_name }}
      </span>
    </a>

    <div class="sidebar">
      <!-- USER PANEL -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img :src="authUserStore.user.avatar" class="img-circle elevation-2" />
        </div>
        <div class="info">
          <a href="#" class="d-block text-sm">{{ authUserStore.user.name }}</a>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">

          <!-- DASHBOARD -->
          <li class="nav-item">
            <router-link
              :to="{ name: 'admin.dashboard' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.dashboard') }"
            >
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </router-link>
          </li>

          <!-- ================= MASTER DATA ================= -->
          <li class="nav-header" v-if="canAny([
            'manage.master.permissions',
            'manage.master.users',
            'manage.master.participant-categories',
            'manage.master.participants'
          ])">
            MASTER DATA
          </li>

          <li
            class="nav-item"
            v-if="can('manage.master')"
            :class="{ 'menu-open': isUserMenuOpen }"
          >
            <a
              href="#"
              class="nav-link"
              :class="{ active: isAnyNameActive(userRoutes) }"
              @click.prevent="isUserMenuOpen = !isUserMenuOpen"
            >
              <i class="nav-icon fas fa-users"></i>
              <p>Pengguna <i class="right fas fa-angle-left"></i></p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item" v-if="can('manage.master.permissions')">
                <router-link
                  :to="{ name: 'admin.permissions' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.permissions') }"
                >
                  <i class="nav-icon fas fa-user-shield"></i>
                  <p>Permissions & Roles</p>
                </router-link>
              </li>



              <li class="nav-item" v-if="can('manage.master.users')">
                <router-link
                  :to="{ name: 'admin.users' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.users') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>User</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.master.participant-categories')">
                <router-link
                  :to="{ name: 'admin.participant-categories' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.participant-categories') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Participant Categories</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.master.participants')">
                <router-link
                  :to="{ name: 'admin.participants' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.participants') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Participants</p>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- ================= EVENT ================= -->
          <li
            class="nav-item"
            v-if="can('manage.event')"
            :class="{ 'menu-open': isEventMenuOpen }"
          >
            <a
              href="#"
              class="nav-link"
              :class="{ active: isAnyNameActive(eventRoutes) }"
              @click.prevent="isEventMenuOpen = !isEventMenuOpen"
            >
              <i class="nav-icon fas fa-calendar-alt"></i>
              <p>Event & Struktur <i class="right fas fa-angle-left"></i></p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item" v-if="can('manage.event.events')">
                <router-link
                  :to="{ name: 'admin.events' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.events') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Events</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.event.event-days')">
                <router-link
                  :to="{ name: 'admin.event-days' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.event-days') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Event Days</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.event.rooms')">
                <router-link
                  :to="{ name: 'admin.rooms' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.rooms') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Rooms</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.event.activities')">
                <router-link
                  :to="{ name: 'admin.activities' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.activities') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Activities</p>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- ================= PRICING ================= -->
          <li
            class="nav-item"
            v-if="can('manage.pricing')"
            :class="{ 'menu-open': isPriceMenuOpen }"
          >
            <a
              href="#"
              class="nav-link"
              :class="{ active: isAnyNameActive(priceRoutes) }"
              @click.prevent="isPriceMenuOpen = !isPriceMenuOpen"
            >
              <i class="nav-icon fas fa-tags"></i>
              <p>Pembayaran <i class="right fas fa-angle-left"></i></p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item" v-if="can('manage.pricing.items')">
                <router-link
                  :to="{ name: 'admin.pricing' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.pricing') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Pricing</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.pricing.banks')">
                <router-link
                  :to="{ name: 'admin.banks' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.banks') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Banks</p>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- ================= OPERASIONAL ================= -->
          <li class="nav-header" v-if="can('manage.sessions')">OPERASIONAL EVENT</li>
          <li class="nav-item" v-if="can('manage.sessions')">
            <router-link
              :to="{ name: 'admin.sessions' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.sessions') }"
            >
              <i class="nav-icon fas fa-clock"></i><p>Sessions</p>
            </router-link>
          </li>

          <!-- ================= TRANSAKSIONAL ================= -->
          <li class="nav-header" v-if="canAny(['manage.registrations','manage.payments'])">
            TRANSAKSIONAL
          </li>

          <li class="nav-item" v-if="can('manage.registrations')">
            <router-link
              :to="{ name: 'admin.registrations' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.registrations') }"
            >
              <i class="nav-icon fas fa-file-invoice"></i><p>Registrations</p>
            </router-link>
          </li>

          <li class="nav-item" v-if="can('manage.payments')">
            <router-link
              :to="{ name: 'admin.payments' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.payments') }"
            >
              <i class="nav-icon fas fa-money-bill-wave"></i><p>Payments</p>
            </router-link>
          </li>

          <!-- PAYMENT VERIFICATION -->
          <li
            class="nav-item"
            v-if="can('manage.payment-verifications')"
            :class="{ 'menu-open': isPaymentVerificationMenuOpen }"
          >
            <a
              href="#"
              class="nav-link"
              :class="{ active: isAnyNameActive(paymentVerificationRoutes) }"
              @click.prevent="isPaymentVerificationMenuOpen = !isPaymentVerificationMenuOpen"
            >
              <i class="nav-icon fas fa-check-double"></i>
              <p>Payment Verifications <i class="right fas fa-angle-left"></i></p>
            </a>

            <ul class="nav nav-treeview">
              <li class="nav-item" v-if="can('manage.payment-verifications.queue')">
                <router-link
                  :to="{ name: 'admin.payment-verifications.queue' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.payment-verifications.queue') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>Queue</p>
                </router-link>
              </li>

              <li class="nav-item" v-if="can('manage.payment-verifications.history')">
                <router-link
                  :to="{ name: 'admin.payment-verifications.history' }"
                  class="nav-link"
                  :class="{ active: isNameActive('admin.payment-verifications.history') }"
                >
                  <i class="far fa-circle nav-icon"></i><p>History</p>
                </router-link>
              </li>
            </ul>
          </li>

          <!-- FINANCE -->
          <li class="nav-item" v-if="can('view.finance-dashboard')">
            <router-link
              :to="{ name: 'admin.finance.dashboard' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.finance.dashboard') }"
            >
              <i class="nav-icon fas fa-chart-line"></i><p>Finance Dashboard</p>
            </router-link>
          </li>

          
          <li
            class="nav-header"
            v-if="canAny([
              'manage.paper-types',
              'manage.papers.review',
              'manage.papers.final'
            ])"
          >
            SCIENTIFIC
          </li>


          <li class="nav-item" v-if="can('manage.paper-types')">
            <router-link
              :to="{ name: 'admin.paper-types' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.paper-types') }"
            >
              <i class="nav-icon fas fa-layer-group"></i>
              <p>Paper Types</p>
            </router-link>
          </li>

          <li class="nav-item" v-if="can('manage.papers.review')">
            <router-link
              :to="{ name: 'admin.papers.review' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.papers.review') }"
            >
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Paper Review</p>
            </router-link>
          </li>

          <li class="nav-item" v-if="can('manage.papers.final')">
            <router-link
              :to="{ name: 'admin.papers.final' }"
              class="nav-link"
              :class="{ active: isNameActive('admin.papers.final') }"
            >
              <i class="nav-icon fas fa-flag-checkered"></i>
              <p>Paper Final</p>
            </router-link>
          </li>


          <!-- SETTINGS -->
          <li class="nav-header" v-if="can('manage.settings')">KELOLA</li>
          <li class="nav-item" v-if="can('manage.settings')">
            <router-link
              to="/admin/settings"
              class="nav-link"
              :class="{ active: route.path.startsWith('/admin/settings') }"
            >
              <i class="nav-icon fas fa-cog"></i><p>Pengaturan</p>
            </router-link>
          </li>

          <!-- LOGOUT -->
          <li class="nav-item">
            <a href="#" class="nav-link" @click.prevent="logout">
              <i class="nav-icon fas fa-sign-out-alt"></i><p>Keluar</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>
</template>
