<template>
  <div class="bg-slate-50 text-slate-900 min-h-screen">
    <!-- NAVBAR -->
    <header class="sticky top-0 z-20 bg-white/95 border-b border-slate-200 backdrop-blur">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <!-- Brand -->
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-md bg-[#801428] flex items-center justify-center text-xs font-bold text-white">
            KPS
          </div>
          <div>
            <p class="text-sm font-semibold leading-tight">
              Kelakar Padang Symcard
            </p>
            <p class="text-[11px] text-slate-500">
              Admin Portal • Web Project PadangSymcard
            </p>
          </div>
        </div>

        <!-- Menu desktop -->
        <nav class="hidden md:flex items-center gap-6 text-xs text-slate-600">
          <a @click.prevent="scrollToSection('sistem')" class="hover:text-[#123A82]">Sistem</a>
          <a @click.prevent="scrollToSection('modul')" class="hover:text-[#123A82]">Modul</a>
          <a @click.prevent="scrollToSection('akses')" class="hover:text-[#123A82]">Hak Akses</a>
          <a @click.prevent="scrollToSection('kontak')" class="hover:text-[#123A82]">Kontak</a>
        </nav>

        <!-- Action -->
        <div class="hidden md:flex">
          <button
            class="px-4 py-1.5 text-xs rounded-md bg-[#123A82] hover:bg-[#0B1C3D] text-white font-semibold"
            @click="goToLogin"
          >
            Login Admin
          </button>
        </div>

        <!-- Mobile -->
        <button
          class="md:hidden p-2 rounded-md text-slate-600 hover:bg-slate-100"
          @click="mobileNavOpen = !mobileNavOpen"
        >
          <svg v-if="!mobileNavOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </header>

    <!-- HERO -->
    <section class="bg-gradient-to-b from-white via-slate-100 to-white">
      <div class="max-w-6xl mx-auto px-4 py-16 grid md:grid-cols-2 gap-10 items-center">
        <div>
          <p class="text-[11px] uppercase tracking-widest text-[#801428] mb-2">
            Official Admin Platform
          </p>
          <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
            Sistem Administrasi Terpadu
            <span class="text-[#123A82]">Padang Symcard</span>
          </h1>
          <p class="text-sm text-slate-700 mb-6">
            <strong>Kelakar Padang Symcard</strong> merupakan portal admin resmi
            untuk pengelolaan sistem, event, konten, peserta, dan data operasional
            dalam ekosistem Web Project PadangSymcard.
          </p>

          <ul class="text-sm text-slate-700 space-y-1.5 mb-6">
            <li>• Manajemen event, peserta, dan user.</li>
            <li>• Sistem role & hak akses terstruktur.</li>
            <li>• Monitoring data dan aktivitas sistem.</li>
            <li>• Terintegrasi dengan seluruh modul PadangSymcard.</li>
          </ul>

          <div class="flex gap-3">
            <button
              class="px-5 py-2 rounded-md bg-[#801428] hover:bg-[#651022] text-white text-sm font-semibold"
              @click="goToLogin"
            >
              Masuk ke Dashboard
            </button>
            <button
              class="px-5 py-2 rounded-md border border-slate-300 text-sm hover:bg-slate-100"
              @click="scrollToSection('modul')"
            >
              Lihat Modul Sistem
            </button>
          </div>
        </div>

        <!-- Preview -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
          <p class="text-xs font-semibold mb-3 text-slate-700">Modul Utama Sistem</p>
          <div class="grid grid-cols-3 gap-2 text-[11px] text-slate-700">
            <div class="bg-slate-100 rounded-lg p-2">Event</div>
            <div class="bg-slate-100 rounded-lg p-2">User</div>
            <div class="bg-slate-100 rounded-lg p-2">Konten</div>
            <div class="bg-slate-100 rounded-lg p-2">Penilaian</div>
            <div class="bg-slate-100 rounded-lg p-2">Laporan</div>
            <div class="bg-slate-100 rounded-lg p-2">Pengaturan</div>
          </div>
          <p class="mt-4 text-[11px] text-slate-500">
            Akses hanya untuk admin dan tim internal PadangSymcard.
          </p>
        </div>
      </div>
    </section>

    <!-- KONTAK -->
    <section id="kontak" class="border-t border-slate-200">
      <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
          <h2 class="text-xl font-semibold mb-2">Kontak Administrator Sistem</h2>
          <p class="text-sm text-slate-600 mb-4">
            Untuk keperluan teknis, pengelolaan akses, atau koordinasi sistem.
          </p>
          <p class="text-sm">Email: <strong>admin@padangsymcard.com</strong></p>
          <p class="text-sm">Domain: <strong>kelakar.padangsymcard.com</strong></p>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-slate-200 py-4 text-center text-[11px] text-slate-500">
      © {{ currentYear }} Kelakar Padang Symcard • Web Project PadangSymcard
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const mobileNavOpen = ref(false)
const currentYear = computed(() => new Date().getFullYear())

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const goToLogin = () => {
  window.location.href = '/login'
}
</script>
