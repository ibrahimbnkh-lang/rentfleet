<template>
  <div class="planning-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Planning & Calendrier de la Flotte</h1>
        <p class="page-subtitle">Vue chronologique interactive des disponibilités et affectations du parc automobile</p>
      </div>

      <div class="header-actions">
        <RouterLink to="/reservations" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M2 3h12M2 8h12M2 13h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Vue Tableau
        </RouterLink>
      </div>
    </div>

    <!-- Navigation & Controls Toolbar -->
    <div class="card toolbar-card">
      <div class="toolbar-left">
        <!-- Date Navigation -->
        <div class="date-nav-group">
          <button class="btn btn-secondary btn-icon" title="Période précédente" @click="shiftPeriod(-1)">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M10 12l-4-4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <button class="btn btn-secondary" @click="goToToday">
            Aujourd'hui
          </button>
          <button class="btn btn-secondary btn-icon" title="Période suivante" @click="shiftPeriod(1)">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <span class="period-title-display">
          {{ periodTitle }}
        </span>
      </div>

      <div class="toolbar-right">
        <!-- Window mode: 7, 14, 30 days -->
        <div class="view-mode-toggle">
          <button
            class="mode-btn"
            :class="{ active: viewDays === 7 }"
            @click="setViewDays(7)"
          >
            7 jours
          </button>
          <button
            class="mode-btn"
            :class="{ active: viewDays === 14 }"
            @click="setViewDays(14)"
          >
            14 jours
          </button>
          <button
            class="mode-btn"
            :class="{ active: viewDays === 30 }"
            @click="setViewDays(30)"
          >
            30 jours
          </button>
        </div>

        <!-- Branch filter -->
        <select v-model="selectedBranchId" class="input branch-select" @change="fetchTimeline">
          <option value="">Toutes les agences</option>
          <option v-for="b in branches" :key="b.id" :value="b.id">
            {{ b.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Legend Bar -->
    <div class="legend-bar">
      <div class="legend-item">
        <span class="legend-dot dot-in-progress"></span>
        <span>En cours (Loué)</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot dot-confirmed"></span>
        <span>Confirmée</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot dot-pending"></span>
        <span>En attente</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot dot-completed"></span>
        <span>Terminée</span>
      </div>
      <div class="legend-item ml-auto weekend-note">
        <span class="weekend-indicator"></span>
        <span>Week-end algérien (Ven / Sam)</span>
      </div>
    </div>

    <!-- Planning Grid Container -->
    <div class="card timeline-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Chargement du planning de la flotte…</p>
      </div>

      <div v-else-if="vehicles.length === 0" class="empty-state">
        <p class="empty-text">Aucun véhicule trouvé pour cette agence.</p>
      </div>

      <div v-else class="timeline-scroll-wrapper">
        <div class="timeline-table">
          <!-- Header Row -->
          <div class="timeline-row header-row">
            <!-- Left Header: Vehicle column -->
            <div class="col-vehicle header-cell sticky-col">
              Véhicules ({{ vehicles.length }})
            </div>

            <!-- Days columns -->
            <div class="days-columns-container">
              <div
                v-for="day in daysList"
                :key="day.dateStr"
                class="day-header-cell"
                :class="{
                  'is-weekend': day.isWeekend,
                  'is-today': day.isToday,
                }"
              >
                <span class="day-name">{{ day.dayName }}</span>
                <span class="day-number">{{ day.dayNumber }}</span>
                <span class="day-month">{{ day.monthName }}</span>
              </div>
            </div>
          </div>

          <!-- Body Rows (1 per vehicle) -->
          <div
            v-for="v in vehicles"
            :key="v.id"
            class="timeline-row vehicle-row"
          >
            <!-- Vehicle Info Left Sticky Cell -->
            <div class="col-vehicle vehicle-cell sticky-col">
              <div class="veh-info">
                <span class="veh-name">{{ v.brand }} {{ v.model }}</span>
                <div class="veh-meta">
                  <span class="plate font-mono">{{ v.registration_plate }}</span>
                  <span class="cat-pill">{{ v.category?.name }}</span>
                </div>
              </div>
            </div>

            <!-- Days Grid Track -->
            <div class="days-track-container">
              <!-- Background day columns -->
              <div class="day-slots-bg">
                <div
                  v-for="day in daysList"
                  :key="'bg-' + day.dateStr"
                  class="day-slot"
                  :class="{
                    'is-weekend': day.isWeekend,
                    'is-today': day.isToday,
                  }"
                ></div>
              </div>

              <!-- Reservation Blocks for this vehicle -->
              <div class="blocks-layer">
                <div
                  v-for="block in getVehicleBlocks(v.id)"
                  :key="block.res.id"
                  class="res-block"
                  :class="'block-' + block.res.status"
                  :style="{
                    left: block.leftPct + '%',
                    width: block.widthPct + '%',
                  }"
                  :title="block.tooltip"
                  @click="openDetails(block.res)"
                >
                  <div class="block-content">
                    <span class="block-ref font-mono">{{ block.res.reference_code }}</span>
                    <span class="block-client">{{ block.res.customer?.full_name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Reservation Detail Drawer ──────────────────────────────────────── -->
    <div v-if="selectedRes" class="drawer-backdrop" @click.self="selectedRes = null">
      <div class="drawer">
        <div class="drawer-header">
          <div>
            <div class="drawer-tag">
              <span class="font-mono ref-pill">{{ selectedRes.reference_code }}</span>
              <span class="status-badge" :class="'status-' + selectedRes.status">
                {{ getStatusLabel(selectedRes.status) }}
              </span>
            </div>
            <h2 class="drawer-title mt-2">Détails Réservation</h2>
          </div>
          <button class="modal-close" @click="selectedRes = null">&times;</button>
        </div>

        <div class="drawer-body">
          <div class="drawer-card">
            <div class="drawer-card-label">Client</div>
            <div class="drawer-card-val font-semibold">{{ selectedRes.customer?.full_name }}</div>
            <div class="drawer-card-sub">
              <span>📞 {{ selectedRes.customer?.phone }}</span>
              <span>📍 Wilaya {{ selectedRes.customer?.wilaya_code || '16' }}</span>
            </div>
          </div>

          <div class="drawer-card mt-3">
            <div class="drawer-card-label">Véhicule</div>
            <div class="drawer-card-val font-semibold">{{ getVehicleName(selectedRes.vehicle_id) }}</div>
            <div class="drawer-card-sub font-mono">{{ getVehiclePlate(selectedRes.vehicle_id) }}</div>
          </div>

          <div class="drawer-card mt-3">
            <div class="drawer-card-label">Dates & Durée</div>
            <div class="drawer-card-val">
              Du {{ formatDate(selectedRes.pickup_datetime) }} au {{ formatDate(selectedRes.return_datetime) }}
            </div>
            <div class="drawer-card-sub">
              Durée : <strong>{{ selectedRes.days_count }} jour(s)</strong>
            </div>
          </div>

          <div class="drawer-card mt-3">
            <div class="drawer-card-label">Financier</div>
            <div class="fin-line">
              <span>Total location :</span>
              <strong class="font-mono text-purple">{{ formatDzd(selectedRes.total_amount_dzd) }}</strong>
            </div>
            <div class="fin-line text-muted">
              <span>Tarif : {{ formatDzd(selectedRes.daily_rate_dzd) }}/j | Caution : {{ formatDzd(selectedRes.deposit_amount_dzd) }}</span>
            </div>
          </div>

          <div v-if="selectedRes.notes" class="drawer-card mt-3 notes-card">
            <div class="drawer-card-label">Notes</div>
            <p>{{ selectedRes.notes }}</p>
          </div>
        </div>

        <div class="drawer-footer">
          <RouterLink to="/reservations" class="btn btn-primary w-full text-center">
            Gérer dans la liste complète
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

interface BranchItem {
  id: number
  name: string
  code: string
}

interface VehicleCategory {
  id: number
  name: string
}

interface VehicleItem {
  id: number
  brand: string
  model: string
  registration_plate: string
  category?: VehicleCategory
  branch_id?: number
  daily_price_dzd?: number
}

interface ReservationItem {
  id: number
  reference_code: string
  vehicle_id: number
  customer_id: number
  pickup_datetime: string
  return_datetime: string
  status: string
  days_count: number
  daily_rate_dzd: number
  total_amount_dzd: number
  deposit_amount_dzd: number
  notes?: string
  customer?: {
    id: number
    full_name: string
    phone: string
    wilaya_code?: string
  }
}

const loading = ref(false)
const branches = ref<BranchItem[]>([])
const vehicles = ref<VehicleItem[]>([])
const reservations = ref<ReservationItem[]>([])
const selectedBranchId = ref<string | number>('')
const selectedRes = ref<ReservationItem | null>(null)

// Current view window
const viewDays = ref<number>(14) // 7, 14, 30
const windowStartDate = ref<Date>(new Date())

// Align start date to 00:00:00
const setDateStart = (d: Date) => {
  const c = new Date(d)
  c.setHours(0, 0, 0, 0)
  return c
}

// Generate the days list for the timeline header
const daysList = computed(() => {
  const list = []
  const current = new Date(windowStartDate.value)
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]

  for (let i = 0; i < viewDays.value; i++) {
    const d = new Date(current)
    d.setDate(current.getDate() + i)
    const dateStr = d.toISOString().split('T')[0]

    // Algerian weekend: Friday (5) and Saturday (6)
    const dayOfWeek = d.getDay()
    const isWeekend = (dayOfWeek === 5 || dayOfWeek === 6)
    const isToday = (dateStr === todayStr)

    const dayName = d.toLocaleDateString('fr-FR', { weekday: 'short' }).slice(0, 3)
    const dayNumber = d.getDate()
    const monthName = d.toLocaleDateString('fr-FR', { month: 'short' })

    list.push({
      date: d,
      dateStr,
      dayName,
      dayNumber,
      monthName,
      isWeekend,
      isToday,
    })
  }

  return list
})

const periodTitle = computed(() => {
  if (daysList.value.length === 0) return ''
  const first = daysList.value[0].date
  const last = daysList.value[daysList.value.length - 1].date

  const fMonth = first.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  const lMonth = last.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })

  if (fMonth === lMonth) {
    return `${first.getDate()} – ${last.getDate()} ${fMonth}`
  }
  return `${first.getDate()} ${first.toLocaleDateString('fr-FR', { month: 'short' })} – ${last.getDate()} ${last.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' })}`
})

// Timeline Navigation
const shiftPeriod = (direction: number) => {
  const step = viewDays.value === 7 ? 7 : viewDays.value === 14 ? 7 : 14
  const next = new Date(windowStartDate.value)
  next.setDate(next.getDate() + (direction * step))
  windowStartDate.value = setDateStart(next)
  fetchTimeline()
}

const goToToday = () => {
  const today = new Date()
  // Show 1 day before today for context
  today.setDate(today.getDate() - 1)
  windowStartDate.value = setDateStart(today)
  fetchTimeline()
}

const setViewDays = (days: number) => {
  viewDays.value = days
  fetchTimeline()
}

// ─── Fetch Timeline Data ─────────────────────────────────────────────────────
const fetchTimeline = async () => {
  loading.value = true
  try {
    const startStr = daysList.value[0].dateStr
    const endStr = daysList.value[daysList.value.length - 1].dateStr

    const params: Record<string, any> = {
      start_date: startStr,
      end_date: endStr,
    }
    if (selectedBranchId.value) {
      params.branch_id = selectedBranchId.value
    }

    const res = await api.get('/reservations/timeline', { params })
    vehicles.value = res.data.data?.vehicles || []
    reservations.value = res.data.data?.reservations || []
  } catch (err) {
    console.error('Erreur chargement timeline:', err)
  } finally {
    loading.value = false
  }
}

const fetchBranches = async () => {
  try {
    const res = await api.get('/core/branches')
    branches.value = res.data.data || []
  } catch (err) {
    console.error('Erreur chargement agences:', err)
  }
}

// ─── Block Position Calculations ────────────────────────────────────────────
const getVehicleBlocks = (vehicleId: number) => {
  const vehRes = reservations.value.filter(r => r.vehicle_id === vehicleId)
  if (vehRes.length === 0 || daysList.value.length === 0) return []

  const windowStartMs = daysList.value[0].date.getTime()
  const windowEndMs = new Date(daysList.value[daysList.value.length - 1].date).setHours(23, 59, 59, 999)
  const windowDurationMs = windowEndMs - windowStartMs

  const blocks: Array<{
    res: ReservationItem
    leftPct: number
    widthPct: number
    tooltip: string
  }> = []

  vehRes.forEach(r => {
    const rStartMs = new Date(r.pickup_datetime).getTime()
    const rEndMs = new Date(r.return_datetime).getTime()

    // Skip if completely out of window
    if (rEndMs < windowStartMs || rStartMs > windowEndMs) return

    // Clamp to window bounds
    const clampedStart = Math.max(rStartMs, windowStartMs)
    const clampedEnd = Math.min(rEndMs, windowEndMs)

    const leftPct = ((clampedStart - windowStartMs) / windowDurationMs) * 100
    const widthPct = Math.max(1.8, ((clampedEnd - clampedStart) / windowDurationMs) * 100)

    const client = r.customer?.full_name || 'Client'
    const tooltip = `${r.reference_code} · ${client} (${r.status})`

    blocks.push({
      res: r,
      leftPct,
      widthPct,
      tooltip,
    })
  })

  return blocks
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
const openDetails = (res: ReservationItem) => {
  selectedRes.value = res
}

const getVehicleName = (vehId: number) => {
  const v = vehicles.value.find(item => item.id === vehId)
  return v ? `${v.brand} ${v.model}` : 'Véhicule'
}

const getVehiclePlate = (vehId: number) => {
  const v = vehicles.value.find(item => item.id === vehId)
  return v ? v.registration_plate : ''
}

const formatDzd = (val: number | string | undefined) => {
  const num = Number(val || 0)
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    maximumFractionDigits: 0,
  }).format(num).replace('DZD', 'DA')
}

const formatDate = (iso: string) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'confirmed': return 'Confirmée'
    case 'in_progress': return 'En cours'
    case 'pending': return 'En attente'
    case 'completed': return 'Terminée'
    case 'cancelled': return 'Annulée'
    default: return st
  }
}

onMounted(async () => {
  goToToday()
  await fetchBranches()
})
</script>

<style scoped>
.planning-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  font-size: 1.625rem;
  font-weight: 700;
  color: #0f172a;
}

.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

/* Toolbar */
.toolbar-card {
  padding: 0.875rem 1.25rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.toolbar-left, .toolbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-nav-group {
  display: flex;
  gap: 0.375rem;
  align-items: center;
}

.period-title-display {
  font-size: 1.0625rem;
  font-weight: 700;
  color: #1e293b;
  min-width: 200px;
}

.view-mode-toggle {
  display: flex;
  background: #f1f5f9;
  padding: 0.25rem;
  border-radius: 0.5rem;
}

.mode-btn {
  border: none;
  background: transparent;
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.mode-btn.active {
  background: #ffffff;
  color: #0f172a;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.branch-select {
  min-width: 180px;
}

/* Legend Bar */
.legend-bar {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  color: #475569;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.legend-dot {
  width: 0.625rem;
  height: 0.625rem;
  border-radius: 50%;
}

.dot-in-progress { background: #3b82f6; }
.dot-confirmed { background: #10b981; }
.dot-pending { background: #f59e0b; }
.dot-completed { background: #94a3b8; }

.weekend-note {
  font-size: 0.75rem;
  color: #64748b;
}

.weekend-indicator {
  display: inline-block;
  width: 0.75rem;
  height: 0.75rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 2px;
}

.ml-auto {
  margin-left: auto;
}

/* Timeline Matrix */
.timeline-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}

.timeline-scroll-wrapper {
  overflow-x: auto;
  position: relative;
}

.timeline-table {
  min-width: 900px;
  display: flex;
  flex-direction: column;
}

.timeline-row {
  display: flex;
  border-bottom: 1px solid #f1f5f9;
}

.header-row {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  position: sticky;
  top: 0;
  z-index: 20;
}

/* Sticky Vehicle Column */
.col-vehicle {
  width: 240px;
  min-width: 240px;
  max-width: 240px;
  padding: 0.75rem 1rem;
  flex-shrink: 0;
  background: #ffffff;
}

.header-cell.sticky-col {
  background: #f8fafc;
  font-weight: 700;
  color: #475569;
  font-size: 0.8125rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
}

.sticky-col {
  position: sticky;
  left: 0;
  z-index: 10;
  box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.06);
  border-right: 1px solid #e2e8f0;
}

.veh-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.veh-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.veh-meta {
  display: flex;
  gap: 0.375rem;
  align-items: center;
}

.plate {
  font-size: 0.75rem;
  background: #f1f5f9;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
  color: #334155;
}

.cat-pill {
  font-size: 0.6875rem;
  background: #ede9fe;
  color: #6d28d9;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
}

/* Days container & Header */
.days-columns-container {
  display: flex;
  flex: 1;
}

.day-header-cell {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 0.25rem;
  border-right: 1px solid #f1f5f9;
  font-size: 0.75rem;
}

.day-header-cell.is-weekend {
  background: #f1f5f9;
  color: #64748b;
}

.day-header-cell.is-today {
  background: #ede9fe;
  color: #6d28d9;
  font-weight: 700;
}

.day-name {
  text-transform: uppercase;
  font-size: 0.6875rem;
}

.day-number {
  font-size: 0.9375rem;
  font-weight: 700;
  line-height: 1.2;
}

.day-month {
  font-size: 0.6875rem;
  color: #94a3b8;
}

/* Track & Blocks Layer */
.days-track-container {
  position: relative;
  display: flex;
  flex: 1;
  min-height: 52px;
}

.day-slots-bg {
  position: absolute;
  inset: 0;
  display: flex;
  pointer-events: none;
}

.day-slot {
  flex: 1;
  border-right: 1px solid #f1f5f9;
  height: 100%;
}

.day-slot.is-weekend {
  background: rgba(241, 245, 249, 0.6);
}

.day-slot.is-today {
  background: rgba(237, 233, 254, 0.3);
  border-left: 2px solid #a855f7;
}

.blocks-layer {
  position: absolute;
  inset: 0;
  padding: 6px 0;
}

.res-block {
  position: absolute;
  height: 40px;
  border-radius: 0.375rem;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  z-index: 5;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.res-block:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.12);
  z-index: 10;
}

.block-in_progress {
  background: #3b82f6;
  color: #ffffff;
  border: 1px solid #2563eb;
}

.block-confirmed {
  background: #10b981;
  color: #ffffff;
  border: 1px solid #059669;
}

.block-pending {
  background: #f59e0b;
  color: #ffffff;
  border: 1px solid #d97706;
}

.block-completed {
  background: #94a3b8;
  color: #ffffff;
  border: 1px solid #64748b;
}

.block-content {
  display: flex;
  gap: 0.375rem;
  align-items: center;
  font-size: 0.75rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.block-ref {
  font-weight: 700;
  opacity: 0.9;
}

.block-client {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Detail Drawer */
.drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
}

.drawer {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 15px rgba(0, 0, 0, 0.1);
  animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.drawer-header {
  padding: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.drawer-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.ref-pill {
  padding: 0.25rem 0.5rem;
  background: #f1f5f9;
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 0.8125rem;
}

.drawer-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.drawer-card {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.drawer-card-label {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.drawer-card-val {
  color: #0f172a;
}

.drawer-card-sub {
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: #64748b;
  display: flex;
  gap: 0.75rem;
}

.fin-line {
  display: flex;
  justify-content: space-between;
  margin-top: 0.25rem;
}

.text-purple {
  color: #6d28d9;
}

.notes-card {
  background: #fffbeb;
  border-color: #fef3c7;
  color: #92400e;
}

.drawer-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.w-full {
  width: 100%;
}

.loading-state, .empty-state {
  padding: 3rem;
  text-align: center;
}
</style>

