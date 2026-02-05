import axios from 'axios'

const publicApi = axios.create({
  withCredentials: false,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export default publicApi
