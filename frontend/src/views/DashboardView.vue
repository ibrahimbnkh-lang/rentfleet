<template>
  <div class="dashboard">
    <!-- Welcome Banner -->
    <div class="welcome-card card-elevated">
      <div class="welcome-content">
        <div class="welcome-text">
          <h2 class="welcome-title">Bienvenue, {{ auth.user?.name ?? 'Utilisateur' }} 👋</h2>
          <p class="welcome-sub">Voici un aperçu en temps réel de votre plateforme RentFleet.</p>
        </div>
        <div class="welcome-actions">
          <button
            class="btn-refresh"
            :class="{ 'is-loading': loading }"
            title="Actualiser les données"
            :disabled="loading"
            @click="fetchStats"
          >
            <span class="refresh-icon">🔄</span>
            <span class="refresh-text">{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
          </button>
          <div class="welcome-badge badge badge-primary">
            {{ new Date().toLocaleDateString('fr-DZ', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Africa/Algiers' }) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Dynamic Stats Grid -->
    <div class="stats-grid">
      <RouterLink
        v-for="stat in stats"
        :key="stat.key"
        :to="stat.link"
        class="stat-card card"
      >
        <div class="stat-icon-wrap" :style="{ background: stat.color + '15' }">
          <img :src="stat.icon" :alt="stat.label" class="stat-icon-img" />
        </div>

        <div class="stat-body">
          <div class="stat-value-row">
            <span v-if="loading && stat.displayValue === null" class="stat-skeleton"></span>
            <span v-else class="stat-value">{{ formatNumber(stat.displayValue) }}</span>
            <span class="stat-pulse-dot" :style="{ backgroundColor: stat.color }"></span>
          </div>
          <span class="stat-label">{{ stat.label }}</span>
        </div>

        <div class="stat-arrow">
          <span>&rarr;</span>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const auth = useAuthStore()
const loading = ref(false)

interface StatItem {
  key: string
  label: string
  targetValue: number
  displayValue: number | null
  icon: string
  color: string
  link: string
}

const stats = ref<StatItem[]>([
  {
    key: 'vehicles',
    label: 'Véhicules actifs',
    targetValue: 0,
    displayValue: null,
    icon: '/dashboard/vehicules-actifs.png',
    color: '#5A3CDF',
    link: '/fleet/vehicles',
  },
  {
    key: 'customers',
    label: 'Clients vérifiés',
    targetValue: 0,
    displayValue: null,
    icon: '/dashboard/clients-verifies.png',
    color: '#FF9F0A',
    link: '/customers',
  },
  {
    key: 'documents',
    label: 'Documents expirants',
    targetValue: 0,
    displayValue: null,
    icon: '/dashboard/documents-expirants.png',
    color: '#DC2626',
    link: '/fleet/expiring-documents',
  },
  {
    key: 'branches',
    label: 'Agences',
    targetValue: 0,
    displayValue: null,
    icon: '/dashboard/agences.png',
    color: '#16A34A',
    link: '/core/branches',
  },
])

function formatNumber(num: number | null): string {
  if (num === null || num === undefined) return '0'
  return new Intl.NumberFormat('fr-DZ').format(num)
}

function extractTotal(res: any): number {
  if (!res) return 0
  const d = res.data

  // 1. Nested in data.meta.total: { data: { data: [...], meta: { total: 10 } } }
  if (typeof d?.data?.meta?.total === 'number') return d.data.meta.total
  // 2. In data.total: { data: { total: 10 } }
  if (typeof d?.data?.total === 'number') return d.data.total
  // 3. In meta.total: { meta: { total: 10 } }
  if (typeof d?.meta?.total === 'number') return d.meta.total
  // 4. In data array: { data: [...] } (e.g. expiring documents)
  if (Array.isArray(d?.data)) return d.data.length
  // 5. In data.data array
  if (Array.isArray(d?.data?.data)) return d.data.data.length
  // 6. In data.items array: { data: { items: [...] } }
  if (Array.isArray(d?.data?.items)) return d.data.items.length
  // 7. Direct items array
  if (Array.isArray(d?.items)) return d.items.length
  // 8. Direct root array
  if (Array.isArray(d)) return d.length

  return 0
}

/**
 * Smooth dynamic count-up animation
 */
function animateCounter(stat: StatItem, target: number, duration = 1200) {
  stat.targetValue = target
  const start = stat.displayValue ?? 0
  const diff = target - start

  if (diff === 0) {
    stat.displayValue = target
    return
  }

  const startTime = performance.now()

  function step(currentTime: number) {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / duration, 1)
    // Ease Out Cubic
    const ease = 1 - Math.pow(1 - progress, 3)
    stat.displayValue = Math.round(start + diff * ease)

    if (progress < 1) {
      requestAnimationFrame(step)
    } else {
      stat.displayValue = target
    }
  }

  requestAnimationFrame(step)
}

async function fetchStats() {
  loading.value = true
  try {
    const [vRes, cRes, dRes, bRes] = await Promise.allSettled([
      api.get('/fleet/vehicles', { params: { per_page: 1 } }),
      api.get('/customers', { params: { per_page: 1 } }),
      api.get('/fleet/documents/expiring'),
      api.get('/core/branches', { params: { per_page: 1 } }),
    ])

    if (vRes.status === 'fulfilled') {
      const target = extractTotal(vRes.value)
      const item = stats.value.find((s) => s.key === 'vehicles')
      if (item) animateCounter(item, target)
    }

    if (cRes.status === 'fulfilled') {
      const target = extractTotal(cRes.value)
      const item = stats.value.find((s) => s.key === 'customers')
      if (item) animateCounter(item, target)
    }

    if (dRes.status === 'fulfilled') {
      const target = extractTotal(dRes.value)
      const item = stats.value.find((s) => s.key === 'documents')
      if (item) animateCounter(item, target)
    }

    if (bRes.status === 'fulfilled') {
      const target = extractTotal(bRes.value)
      const item = stats.value.find((s) => s.key === 'branches')
      if (item) animateCounter(item, target)
    }
  } catch (err) {
    console.error('Failed to load dashboard dynamic stats:', err)
  } finally {
    loading.value = false
  }
}

let pollingInterval: any = null

onMounted(() => {
  fetchStats()
  // Auto-refresh dynamic counters every 30 seconds
  pollingInterval = setInterval(fetchStats, 30000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.welcome-card {
  background: linear-gradient(135deg, #5A3CDF 0%, #4028C4 100%);
  color: white;
  border-radius: var(--radius-xl);
}

.welcome-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.welcome-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: white;
  margin-block-end: 0.25rem;
}

.welcome-sub {
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.9375rem;
}

.welcome-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.85rem;
  font-size: 0.8125rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: var(--radius-md, 8px);
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
}

.btn-refresh.is-loading .refresh-icon {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.welcome-badge {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  text-decoration: none;
  color: inherit;
  position: relative;
  overflow: hidden;
  border: 1px solid var(--color-border, #E5E7EB);
  background: var(--color-surface, #ffffff);
  border-radius: var(--radius-lg, 12px);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
  border-color: rgba(90, 60, 223, 0.3);
}

.stat-icon-wrap {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  padding: 8px;
  transition: transform 0.2s ease;
}

.stat-card:hover .stat-icon-wrap {
  transform: scale(1.08);
}

.stat-icon-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.stat-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.stat-value-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
}

.stat-value {
  display: block;
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1.1;
  color: var(--color-text-primary, #111827);
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  text-align: center;
}

.stat-pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  opacity: 0.75;
  box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.2);
  animation: pulse-ring 2.5s cubic-bezier(0.45, 0, 0.2, 1) infinite;
}

@keyframes pulse-ring {
  0% { transform: scale(0.95); opacity: 0.6; }
  50% { transform: scale(1.3); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.6; }
}

.stat-skeleton {
  display: inline-block;
  width: 48px;
  height: 28px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.stat-label {
  display: block;
  font-size: 0.8125rem;
  color: var(--color-text-secondary, #6B7280);
  margin-block-start: 0.35rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
  width: 100%;
}

.stat-arrow {
  position: absolute;
  right: 1rem;
  color: var(--color-text-muted, #9CA3AF);
  font-size: 1.125rem;
  opacity: 0;
  transform: translateX(-4px);
  transition: all 0.2s ease;
}

.stat-card:hover .stat-arrow {
  opacity: 1;
  transform: translateX(0);
  color: var(--color-primary, #5A3CDF);
}
</style>
