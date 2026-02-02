<script setup>
import { computed } from 'vue'
import { useSettingStore } from '../stores/SettingStore'
import { useAuthUserStore } from '../stores/AuthUserStore'

const settingStore = useSettingStore()
const authUserStore = useAuthUserStore()

// normalisasi maintenance
settingStore.setting.maintenance =
  ['1', 1, true, 'true', 'on'].includes(settingStore.setting.maintenance)

// ================================
// EVENT AKTIF (AuthUserStore)
// ================================
const eventData = computed(() => authUserStore.eventData || null)

// ================================
// SWITCH LAYOUT
// ================================
const handleSwitchLayout = () => {
  if (authUserStore.user?.can_multiple_role) {
    authUserStore.switchLayout()
  }
}

// ================================
// ENVIRONMENT LABEL
// ================================
const environmentLabel = computed(() =>
  settingStore.isDevelopment ? 'DEV' : 'PROD'
)

const environmentClass = computed(() =>
  settingStore.isDevelopment
    ? 'badge badge-warning'
    : 'badge badge-success'
)
</script>

<template>
  <nav
    class="main-header navbar navbar-expand"
    :class="settingStore.theme === 'dark' ? 'navbar-dark' : 'navbar-light'"
  >
    <!-- LEFT -->
    <ul class="navbar-nav">
      <li class="nav-item" id="toggleMenuIcon">
        <a
          class="nav-link"
          href="#"
          role="button"
          @click.prevent="settingStore.toggleMenuIcon"
        >
          <i class="fas fa-bars"></i>
        </a>
      </li>

      <li class="nav-item">
        <a
          class="nav-link"
          href="#"
          role="button"
          @click.prevent="settingStore.changeTheme"
        >
          <i
            class="far"
            :class="settingStore.theme === 'dark' ? 'fa-moon' : 'fa-sun'"
          ></i>
        </a>
      </li>
    </ul>

    <!-- RIGHT -->
    <ul class="navbar-nav ml-auto align-items-center">
      <li class="nav-item d-none d-md-block text-right">
        <div class="d-flex align-items-center justify-content-end">
          <!-- ENV LABEL -->
          <span
            :class="environmentClass"
            class="mr-2"
            style="font-size: 10px; letter-spacing: 0.5px;"
            title="Application Environment"
          >
            {{ environmentLabel }}
          </span>

          <!-- EVENT NAME -->
          <strong class="text-sm">
            {{ eventData?.slug || 'Event belum dipilih' }}
          </strong>
        </div>
      </li>
    </ul>
  </nav>
</template>
