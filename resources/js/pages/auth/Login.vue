<script setup>
import axios from 'axios'
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthUserStore } from '../../stores/AuthUserStore'
import { useMasterDataStore } from '../../stores/MasterDataStore'

const router = useRouter()
const authUserStore = useAuthUserStore()
const masterDataStore = useMasterDataStore()

// -----------------------------
// CAPTCHA
// -----------------------------
const captchaUrl = ref('/captcha?' + Date.now())
const refreshCaptcha = () => {
  form.captcha = ''
  captchaUrl.value = '/captcha?' + Date.now()
}

const isCaptchaFilled = computed(() => form.captcha && form.captcha.length >= 4)

// -----------------------------
// FORM LOGIN
// -----------------------------
const form = reactive({
  username: '',
  password: '',
  captcha: '',
  remember: true,
})

const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

// -----------------------------
// HANDLE SUBMIT LOGIN
// -----------------------------
const handleSubmit = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    await axios.post('/login', {
      ...form,
    })

    await authUserStore.getAuthUser()

    authUserStore.isAuthenticated = true
    authUserStore.activeLayout = 'admin'

    router.push('/admin/dashboard')
  } catch (error) {
    let msg = error.response?.data?.message || 'Terjadi kesalahan saat login.'

    if (error.response?.status === 422) {
      const errs = error.response?.data?.errors || {}
      if (errs.username?.length) msg = errs.username[0]
      else if (errs.password?.length) msg = errs.password[0]
      else if (errs.captcha?.length) msg = errs.captcha[0]
    }

    errorMessage.value = msg
  } finally {
    setTimeout(() => (loading.value = false), 400)
  }
}
</script>



<template>
  <div class="mtq-login-page">
    <transition name="fade">
      <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>Memproses login...</p>
      </div>
    </transition>

    <div class="mtq-login-container">
      <div class="mtq-login-card justify-content-center">
        <!-- FORM LOGIN -->
        <div class="mtq-login-right w-100">
          <div class="mtq-login-header text-center mb-4">
            <h1 class="mtq-login-title">Kelakar Padang Symcard</h1>
            <p class="mtq-login-subtitle">Admin Portal</p>
            <p class="mtq-login-tagline">
              Silakan login untuk mengakses dashboard administrasi
            </p>
          </div>

          <div class="card mtq-login-form-card">
            <div class="card-body login-card-body">
              <p class="login-box-msg">Masuk ke sistem</p>

              <transition name="fade">
                <div v-if="errorMessage" class="alert alert-danger">
                  {{ errorMessage }}
                </div>
              </transition>

              <form @submit.prevent="handleSubmit" autocomplete="off">
                <!-- USERNAME -->
                <div class="input-group mb-3">
                  <input
                    v-model="form.username"
                    type="text"
                    class="form-control"
                    placeholder="Username"
                    required
                  />
                  <div class="input-group-append">
                    <div class="input-group-text mtq-input-icon">
                      <i class="fas fa-user"></i>
                    </div>
                  </div>
                </div>

                <!-- PASSWORD -->
                <div class="input-group mb-3">
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="form-control"
                    placeholder="Password"
                    required
                  />
                  <div class="input-group-append">
                    <button
                      class="input-group-text mtq-input-icon"
                      type="button"
                      @click="showPassword = !showPassword"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <!-- CAPTCHA -->
                <div class="input-group mb-3 align-items-center">
                  <input
                    v-model="form.captcha"
                    type="text"
                    class="form-control text-uppercase"
                    placeholder="Kode captcha"
                    required
                  />
                  <div class="input-group-append">
                    <span class="input-group-text p-0 bg-white">
                      <img
                        :src="captchaUrl"
                        class="captcha-img"
                        @click="refreshCaptcha"
                      />
                    </span>
                  </div>
                  <div class="input-group-append">
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="refreshCaptcha"
                    >
                      <i class="fas fa-sync-alt"></i>
                    </button>
                  </div>
                </div>

                <!-- ACTION -->
                <div class="row mb-3 align-items-center">
                  <div class="col-8">
                    <div class="icheck-primary">
                      <input type="checkbox" id="remember" v-model="form.remember" />
                      <label for="remember">&nbsp; Remember Me</label>
                    </div>
                  </div>
                  <div class="col-4 text-right">
                    <button
                      type="submit"
                      class="btn btn-success btn-block"
                      :disabled="loading || !isCaptchaFilled"
                    >
                      <span v-if="loading">Memuat...</span>
                      <span v-else>MASUK</span>
                    </button>
                  </div>
                </div>
              </form>

              <div class="mtq-login-footer-links">
                <p class="mb-1">
                  Lupa Password?
                  <a href="/password/wa/request" target="_blank">Reset di sini</a>
                </p>
                <p class="mb-0">
                  <a
                    class="text-sm text-muted"
                    href="https://wa.me/6282298476941"
                    target="_blank"
                  >
                    Hubungi admin
                  </a>
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
.mtq-login-page {
  min-height: 100vh;
  background: #f4f5f7;
  background-image: radial-gradient(circle at top left, #ffffff 0, #f4f5f7 55%, #e9ecf0 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 12px;
}

.mtq-login-container {
  width: 100%;
  max-width: 1000px;
}

.mtq-login-card {
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);
  display: flex;
  overflow: hidden;
  min-height: 460px;
}

/* KIRI */
.mtq-login-left {
  flex: 0 0 40%;
  background: linear-gradient(135deg, #f7f9fb 0%, #ffffff 50%, #f2f4f7 100%);
  padding: 24px;
  align-items: center;
  justify-content: center;
}

.mtq-login-poster-wrapper {
  border-radius: 12px;
  background: #ffffff;
  padding: 12px;
  box-shadow: 0 12px 26px rgba(0, 0, 0, 0.18);
}

.mtq-login-poster {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

/* KANAN */
.mtq-login-right {
  flex: 1;
  padding: 24px 24px 28px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.mtq-login-header {
  margin-bottom: 16px;
}

.mtq-login-logos {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
}

.mtq-login-logo-event {
  max-height: 56px;
  width: auto;
}

/* Logo kecil (jika diperlukan) */
.mtq-login-logo-small {
  max-height: 42px;
  width: auto;
}

.mtq-login-title {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 4px;
  color: #2f3a4a;
}

.mtq-login-subtitle {
  font-size: 1.05rem;
  font-weight: 600;
  margin-bottom: 4px;
  color: #2f3a4a;
}

.mtq-login-tagline {
  font-size: 0.9rem;
  color: #6b7280;
  margin-bottom: 0;
}

.mtq-login-form-card {
  /* border-radius: 12px;
  border: 1px solid #e5e7eb; */
  border: 0px !important;
}

.card {
  box-shadow: none !important;
}

/* Input & button styling */
.mtq-input-icon {
  background-color: #198754;
  color: #fff;
  border-color: #198754;
}

.mtq-input-icon i {
  font-size: 0.9rem;
}

.btn-success {
  background-color: #198754;
  border-color: #198754;
}

.btn-success:hover {
  background-color: #157347;
  border-color: #146c43;
}

.mtq-login-footer-links {
  margin-top: 8px;
  font-size: 0.85rem;
}

.mtq-event-warning {
  margin-top: 8px;
  font-size: 0.8rem;
  color: #b91c1c;
}

/* LOADING OVERLAY & TRANSISI */
.loading-overlay {
  position: fixed;
  z-index: 999;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.85);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 16px;
  color: #555;
  transition: opacity 0.3s;
}

.spinner {
  border: 4px solid #ccc;
  border-top: 4px solid #28a745;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 0.8s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active { transition: opacity 0.3s ease; }

.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* RESPONSIVE */
@media (max-width: 991.98px) {
  .mtq-login-card {
    flex-direction: column;
  }

  .mtq-login-right {
    padding: 20px 18px 24px;
  }

  .mtq-login-title {
    font-size: 1.2rem;
  }
}



.captcha-img {
  height: 38px;          /* sejajar input */
  width: auto;
  cursor: pointer;
  user-select: none;
}

.input-group-text {
  border-left: 0;
}

.btn-outline-secondary {
  border-left: 0;
}


</style>
