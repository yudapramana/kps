<script setup>
import { computed, onMounted } from "vue"
import { useAuthUserStore } from "../stores/AuthUserStore.js"
import { useScreenDisplayStore } from "../stores/ScreenDisplayStore.js"
import { useMasterDataStore } from "../stores/MasterDataStore.js"
import { ref } from "vue"
import axios from "axios"
import { Doughnut } from "vue-chartjs"
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from "chart.js"


const centerTextPlugin = {
  id: "centerText",
  afterDraw(chart) {

    const { ctx, chartArea } = chart

    if (!chartArea) return

    const centerX = (chartArea.left + chartArea.right) / 2
    const centerY = (chartArea.top + chartArea.bottom) / 2

    const dataset = chart.data.datasets[0]
    const total = dataset.data.reduce((a, b) => a + b, 0)

    ctx.save()

    /* TOTAL NUMBER */

    ctx.font = "bold 22px sans-serif"
    ctx.fillStyle = "#111827"
    ctx.textAlign = "center"
    ctx.textBaseline = "middle"

    ctx.fillText(total, centerX, centerY - 6)

    /* LABEL */

    ctx.font = "12px sans-serif"
    ctx.fillStyle = "#6b7280"

    ctx.fillText("Total", centerX, centerY + 12)

    ctx.restore()

  }
}

ChartJS.register(ArcElement, Tooltip, Legend, centerTextPlugin)



const refreshCooldown = ref(0)
let cooldownTimer = null

const startCooldown = () => {

  refreshCooldown.value = 60

  cooldownTimer = setInterval(() => {

    refreshCooldown.value--

    if (refreshCooldown.value <= 0) {
      clearInterval(cooldownTimer)
    }

  }, 1000)

}

const participantChartData = computed(() => {
  if (!statistics.value) return null

  return {
    labels: ["Paid Package", "Not Purchased Yet"],
    datasets: [
      {
        data: [
          statistics.value.paid_participants,
          statistics.value.unpaid_participants
        ],
        backgroundColor: ["#1fb6cf", "#f5a26f"],
        borderWidth: 0
      }
    ]
  }
})

const registrationChartData = computed(() => {
  if (!statistics.value) return null

  return {
    labels: ["Already Registration", "Not Yet Registration"],
    datasets: [
      {
        data: [
          statistics.value.already_registered,
          statistics.value.not_yet_registered
        ],
        backgroundColor: ["#1fb6cf", "#f5a26f"],
        borderWidth: 0
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: "70%",
  plugins: {
    legend: {
      position: "bottom",
      labels: {
        boxWidth: 10,
        padding: 15
      }
    }
  }
}

const statistics = ref(null)
const loading = ref(false)

const loadStatistics = async () => {

  loading.value = true

  try {
    const res = await axios.get("/api/v1/statistics")
    statistics.value = res.data
  } finally {
    loading.value = false
  }

}

const refreshStatistics = async () => {

  if (refreshCooldown.value > 0) return

  loading.value = true

  try {

    await axios.post("/api/v1/statistics/refresh")

    await loadStatistics()

    startCooldown()

  } finally {

    loading.value = false

  }

}

const authUserStore = useAuthUserStore()
const screenDisplayStore = useScreenDisplayStore()
const masterDataStore = useMasterDataStore()



/* ================= EVENT ACTIVE ================= */
const activeEvent = computed(() => authUserStore.eventData || {})

/* ================= AGENDA ================= */
const today = new Date().toISOString().slice(0, 10)

/* ================= HELPERS ================= */
const formatDate = (date) => {
  if (!date) return "-"
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  })
}

const formatEventDate = (start, end) => {
  if (!start) return "-"
  const s = formatDate(start)
  if (!end) return s
  return `${s} – ${formatDate(end)}`
}

const isEarlyBirdActive = computed(() => {
  if (!activeEvent.value?.early_bird_end_date) return false
  return activeEvent.value.early_bird_end_date >= today
})

onMounted(() => {
  loadStatistics()
})
</script>

<template>
  <!-- ================= HEADER ================= -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Beranda</h1>
        </div>
        <div class="col-sm-6" v-if="!screenDisplayStore.isMobile">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Beranda</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= CONTENT ================= -->
  <div class="content">
    <div class="container-fluid">

      <!-- HEADER SUMMARY -->
      <div class="dashboard-summary-header" v-if="statistics">

        <h5 class="summary-title">
          Dashboard Summary
        </h5>

        <button
          class="btn-refresh"
          @click="refreshStatistics"
          :disabled="loading || refreshCooldown > 0"
        >

          <i
            class="fas"
            :class="loading ? 'fa-spinner fa-spin' : 'fa-sync'"
          ></i>

          <span v-if="loading">
            Loading...
          </span>

          <span v-else-if="refreshCooldown > 0">
            Refresh ({{ refreshCooldown }}s)
          </span>

          <span v-else>
            Refresh
          </span>

        </button>

      </div>

      <div class="row">

        <div class="col-md-12 col-sm-12">
          <div class="card event-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h5 class="font-weight-bold text-primary mb-1">
                    {{ activeEvent.name || 'Event Aktif' }}
                  </h5>

                  <p class="text-muted text-sm mb-2" v-if="activeEvent.theme">
                    {{ activeEvent.theme }}
                  </p>
                </div>

                <span class="badge badge-success">
                  Event Aktif
                </span>
              </div>

              <hr class="my-2">

              <div class="text-sm mb-1">
                <i class="far fa-calendar-alt mr-1"></i>
                {{ formatEventDate(activeEvent.start_date, activeEvent.end_date) }}
              </div>

              <div class="text-sm mb-1">
                <i class="fas fa-map-marker-alt mr-1"></i>
                {{ activeEvent.location }} – {{ activeEvent.venue }}
              </div>

              <div class="mt-2" v-if="activeEvent.early_bird_end_date">
                <span
                  class="badge"
                  :class="isEarlyBirdActive ? 'badge-warning' : 'badge-secondary'"
                >
                  Early Bird {{ isEarlyBirdActive ? 'Aktif' : 'Berakhir' }}
                </span>
                <small class="text-muted ml-2">
                  sampai {{ formatDate(activeEvent.early_bird_end_date) }}
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- PARTICIPANT SUMMARY -->
        <div class="col-lg-6 col-md-12" v-if="statistics">

          <div class="card dashboard-card">

            <div class="card-header">
              <h6 class="mb-0">Participant Summary</h6>
            </div>

            <div class="card-body">

              <div v-if="loading" class="chart-loading">
                <div class="spinner"></div>
              </div>

              <div class="row">

                <!-- CHART -->
                <div class="col-5 chart-box">
                  <Doughnut
                    v-if="participantChartData"
                    :data="participantChartData"
                    :options="chartOptions"
                  />
                </div>

                <!-- TABLE -->
                <div class="col-7">

                  <div class="table-title">
                    Participant Details
                  </div>

                  <table class="summary-table">
                    <tbody>

                      <tr>
                        <td>Paid Participant</td>
                        <td class="value success">
                          {{ statistics.paid_participants }}
                        </td>
                      </tr>

                      <tr>
                        <td>Unpaid Participant</td>
                        <td class="value danger">
                          {{ statistics.unpaid_participants }}
                        </td>
                      </tr>

                      <tr class="total-row">
                        <td>Total Participant</td>
                        <td class="value">
                          {{ statistics.total_participants }}
                        </td>
                      </tr>

                    </tbody>
                  </table>

                </div>

              </div>

            </div>

          </div>

        </div>
        


        <!-- REGISTRATION SUMMARY -->
        <div class="col-lg-6 col-md-12" v-if="statistics">

          <div class="card dashboard-card">

            <div class="card-header">
              <h6 class="mb-0">Registration Summary</h6>
            </div>

            <div class="card-body">

              <div v-if="loading" class="chart-loading">
                <div class="spinner"></div>
              </div>

              <div class="row">

                <!-- CHART -->
                <div class="col-5 chart-box">
                  <Doughnut
                    v-if="registrationChartData"
                    :data="registrationChartData"
                    :options="chartOptions"
                  />
                </div>

                <!-- TABLE -->
                <div class="col-7">

                  <div class="table-title">
                    Registration Details
                  </div>

                  <table class="summary-table">
                    <tbody>

                      <tr>
                        <td>Already Registration</td>
                        <td class="value success">
                          {{ statistics.already_registered }}
                        </td>
                      </tr>

                      <tr>
                        <td>Not Yet Registration</td>
                        <td class="value warning">
                          {{ statistics.not_yet_registered }}
                        </td>
                      </tr>

                      <tr class="total-row">
                        <td>Total Registration</td>
                        <td class="value">
                          {{ statistics.total_registrations }}
                        </td>
                      </tr>

                    </tbody>
                  </table>

                </div>

              </div>

            </div>

          </div>

        </div>
        

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ================= DASHBOARD SUMMARY HEADER ================= */

.dashboard-summary-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:12px;
}

.summary-title{
  font-size:15px;
  font-weight:600;
  margin:0;
}

/* refresh button */

.btn-refresh{
  border:1px solid #e5e7eb;
  background:#fafafa;
  padding:5px 12px;
  border-radius:6px;
  font-size:12px;
  cursor:pointer;
  transition:all .15s ease;
}

.btn-refresh:hover{
  background:#f0f2f5;
}

.btn-refresh i{
  margin-right:5px;
}

.dashboard-card{
  border-radius:12px;
}

/* chart container */

.chart-box{
  height:180px;
}

/* total row */

.table-total{
  background:#f8f9fa;
}

/* mobile */

@media (max-width:768px){

  .chart-box{
    height:160px;
    margin-bottom:10px;
  }

}

/* ================= LOADING OVERLAY ================= */

.chart-loading{
  position:absolute;
  inset:0;
  background:rgba(255,255,255,0.75);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:10;
}

/* spinner */

.spinner{
  width:32px;
  height:32px;
  border:3px solid #e5e7eb;
  border-top:3px solid #1fb6cf;
  border-radius:50%;
  animation:spin 0.8s linear infinite;
}

@keyframes spin{
  to{
    transform:rotate(360deg);
  }
}

/* ================= TABLE TITLE ================= */

.table-title{
  font-size:13px;
  font-weight:600;
  margin-bottom:8px;
  color:#374151;
}

/* ================= SUMMARY TABLE ================= */

.summary-table{
  width:100%;
  border-collapse:collapse;
  font-size:13px;
}

.summary-table td{
  padding:6px 4px;
  border-bottom:1px solid #f1f3f5;
}

/* VALUE COLUMN */

.summary-table .value{
  text-align:right;
  font-weight:600;
}

/* COLORS */

.value.success{
  color:#28a745;
}

.value.danger{
  color:#dc3545;
}

.value.warning{
  color:#f39c12;
}

/* TOTAL ROW */

.total-row td{
  border-top:2px solid #e9ecef;
  font-weight:700;
  padding-top:8px;
}

/* CHART AREA */

.chart-box{
  height:180px;
  display:flex;
  align-items:center;
  justify-content:center;
}

/* MOBILE */

@media (max-width:768px){

  .chart-box{
    height:160px;
  }

  .summary-table{
    font-size:12.5px;
  }

}
</style>
