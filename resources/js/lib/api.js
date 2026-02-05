import axios from 'axios'

const api = axios.create({
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// CSRF hanya untuk API admin
const token = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content')

if (token) {
  api.defaults.headers.common['X-CSRF-TOKEN'] = token
}

// Interceptor khusus API admin
api.interceptors.response.use(
  response => response,
  error => {
    const status = error.response?.status

    if (status === 419 || status === 401) {
      alert('Session kedaluwarsa. Silakan login ulang.')
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)

export default api
