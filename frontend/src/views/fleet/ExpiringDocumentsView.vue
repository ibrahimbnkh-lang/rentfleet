<template>
  <div class="expiring-docs-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Conformité & Documents Expirants</h1>
        <p class="page-subtitle">Suivi réglementaire du parc algérien (Assurance, Contrôle technique, Carte grise, Vignette)</p>
      </div>
      <div class="header-actions">
        <select v-model.number="daysWindow" class="input days-select" @change="fetchExpiringDocs">
          <option :value="7">Fenêtre : 7 jours</option>
          <option :value="15">Fenêtre : 15 jours</option>
          <option :value="30">Fenêtre : 30 jours</option>
          <option :value="60">Fenêtre : 60 jours</option>
          <option :value="90">Fenêtre : 90 jours</option>
        </select>
        <button class="btn btn-secondary" @click="fetchExpiringDocs">
          🔄 Actualiser
        </button>
      </div>
    </div>

    <!-- Alert KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="card kpi-card kpi-danger">
        <div class="kpi-icon">🚨</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ expiredCount }}</div>
          <div class="kpi-label">Déjà expirés (Immobilisation requise)</div>
        </div>
      </div>

      <div class="card kpi-card kpi-warning">
        <div class="kpi-icon">⚠️</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ urgentCount }}</div>
          <div class="kpi-label">Expirent sous 7 jours</div>
        </div>
      </div>

      <div class="card kpi-card kpi-info-box">
        <div class="kpi-icon">📅</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ totalDocsCount }}</div>
          <div class="kpi-label">Total alertes sous {{ daysWindow }} jours</div>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Filtrer par immatriculation, véhicule, numéro de police..."
            class="input"
          />
        </div>
        <div class="filter-group w-52">
          <select v-model="filterType" class="input">
            <option value="">Tous les types de document</option>
            <option value="assurance">Assurance automobile</option>
            <option value="controle_technique">Contrôle technique</option>
            <option value="carte_grise">Carte grise</option>
            <option value="vignette">Vignette automobile</option>
            <option value="autorisation">Autorisation d'exploitation</option>
          </select>
        </div>
        <div class="filter-group w-40">
          <select v-model="filterUrgency" class="input">
            <option value="">Toutes urgences</option>
            <option value="expired">Expirés uniquement</option>
            <option value="critical">&lt; 7 jours</option>
            <option value="upcoming">&gt;= 7 jours</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Analyse de la conformité réglementaire...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredDocs.length === 0" class="card empty-state">
      <div class="empty-icon">✅</div>
      <h3>Aucun document expirant</h3>
      <p v-if="documents.length === 0">
        Félicitations ! L'ensemble des véhicules de votre flotte dispose de documents réglementaires valides pour les {{ daysWindow }} prochains jours.
      </p>
      <p v-else>
        Aucun document ne correspond à vos filtres de recherche.
      </p>
    </div>

    <!-- Documents Table -->
    <div v-else class="card table-card">
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>Statut / Échéance</th>
              <th>Document</th>
              <th>Référence / Émetteur</th>
              <th>Véhicule</th>
              <th>Agence</th>
              <th>Date d'expiration</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in filteredDocs" :key="doc.id">
              <td>
                <span :class="['badge', getUrgencyBadge(doc).badgeClass]">
                  {{ getUrgencyBadge(doc).label }}
                </span>
                <div class="days-indicator" :class="getUrgencyBadge(doc).textClass">
                  {{ getUrgencyBadge(doc).subtext }}
                </div>
              </td>
              <td>
                <div class="doc-type-cell">
                  <span class="doc-icon">{{ getDocIcon(doc.type) }}</span>
                  <div>
                    <strong class="doc-title">{{ formatDocType(doc.type) }}</strong>
                  </div>
                </div>
              </td>
              <td>
                <div class="font-mono text-xs font-bold">{{ doc.reference_number }}</div>
                <div class="text-xs text-muted">{{ doc.issuer || 'Non spécifié' }}</div>
              </td>
              <td>
                <div v-if="doc.vehicle">
                  <div class="font-bold">{{ doc.vehicle.brand }} {{ doc.vehicle.model }} ({{ doc.vehicle.year }})</div>
                  <div class="plate-mini font-mono">{{ formatPlate(doc.vehicle.registration_plate) }}</div>
                </div>
                <div v-else class="text-muted">Véhicule supprimé</div>
              </td>
              <td>
                <div v-if="doc.vehicle?.branch">
                  <span class="branch-tag">{{ doc.vehicle.branch.city }} ({{ doc.vehicle.branch.code }})</span>
                </div>
                <div v-else class="text-muted">—</div>
              </td>
              <td>
                <span class="font-semibold">{{ formatDate(doc.expires_at) }}</span>
              </td>
              <td class="text-right">
                <RouterLink
                  v-if="doc.vehicle"
                  :to="`/fleet/vehicles/${doc.vehicle.id}`"
                  class="btn btn-ghost btn-sm text-primary"
                >
                  Gérer le véhicule →
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/api'

interface VehicleDocumentItem {
  id: number
  vehicle_id: number
  type: string
  reference_number: string
  issuer?: string
  issued_at?: string
  expires_at: string
  file_path?: string
  notes?: string
  vehicle?: {
    id: number
    brand: string
    model: string
    year: number
    registration_plate: string
    branch?: {
      id: number
      name: string
      code: string
      city: string
    }
  }
}

const documents = ref<VehicleDocumentItem[]>([])
const loading = ref(false)
const daysWindow = ref(30)
const searchQuery = ref('')
const filterType = ref('')
const filterUrgency = ref('')

async function fetchExpiringDocs() {
  loading.value = true
  try {
    const { data } = await api.get('/fleet/documents/expiring', {
      params: { days: daysWindow.value },
    })
    documents.value = data.data || []
  } catch (err) {
    console.error('Failed to load expiring documents', err)
  } finally {
    loading.value = false
  }
}

const totalDocsCount = computed(() => documents.value.length)

const expiredCount = computed(() => {
  const now = new Date().getTime()
  return documents.value.filter(d => {
    if (!d.expires_at) return false
    return new Date(d.expires_at).getTime() < now
  }).length
})

const urgentCount = computed(() => {
  const now = new Date().getTime()
  const sevenDays = now + 7 * 86400000
  return documents.value.filter(d => {
    if (!d.expires_at) return false
    const exp = new Date(d.expires_at).getTime()
    return exp >= now && exp <= sevenDays
  }).length
})

const filteredDocs = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  const now = new Date().getTime()
  const sevenDays = now + 7 * 86400000

  return documents.value.filter(doc => {
    // Type filter
    if (filterType.value && doc.type !== filterType.value) return false

    // Urgency filter
    if (filterUrgency.value) {
      const exp = doc.expires_at ? new Date(doc.expires_at).getTime() : 0
      if (filterUrgency.value === 'expired' && exp >= now) return false
      if (filterUrgency.value === 'critical' && (exp < now || exp > sevenDays)) return false
      if (filterUrgency.value === 'upcoming' && exp <= sevenDays) return false
    }

    // Search query
    if (q) {
      const matchPlate = doc.vehicle?.registration_plate?.toLowerCase().includes(q)
      const matchBrand = doc.vehicle?.brand?.toLowerCase().includes(q)
      const matchModel = doc.vehicle?.model?.toLowerCase().includes(q)
      const matchRef = doc.reference_number?.toLowerCase().includes(q)
      const matchIssuer = doc.issuer?.toLowerCase().includes(q)
      if (!matchPlate && !matchBrand && !matchModel && !matchRef && !matchIssuer) return false
    }

    return true
  })
})

function getUrgencyBadge(doc: VehicleDocumentItem) {
  if (!doc.expires_at) {
    return { badgeClass: 'badge-neutral', label: 'Indéterminé', subtext: '', textClass: '' }
  }

  const now = new Date().setHours(0, 0, 0, 0)
  const exp = new Date(doc.expires_at).setHours(0, 0, 0, 0)
  const diffDays = Math.ceil((exp - now) / (1000 * 3600 * 24))

  if (diffDays < 0) {
    return {
      badgeClass: 'badge-danger',
      label: 'EXPIRÉ',
      subtext: `Expiré depuis ${Math.abs(diffDays)} j`,
      textClass: 'text-error font-bold',
    }
  }

  if (diffDays <= 7) {
    return {
      badgeClass: 'badge-warning',
      label: 'URGENT',
      subtext: `Reste ${diffDays} jour(s)`,
      textClass: 'text-warning-dark font-bold',
    }
  }

  return {
    badgeClass: 'badge-neutral',
    label: 'À RENOUVELER',
    subtext: `Reste ${diffDays} jour(s)`,
    textClass: 'text-muted',
  }
}

function getDocIcon(type: string): string {
  switch (type) {
    case 'assurance': return '🛡️'
    case 'controle_technique': return '🔧'
    case 'carte_grise': return '📄'
    case 'vignette': return '🏷️'
    case 'autorisation': return '🏛️'
    default: return '📁'
  }
}

function formatDocType(type: string): string {
  switch (type) {
    case 'assurance': return 'Assurance Automobile'
    case 'controle_technique': return 'Contrôle Technique'
    case 'carte_grise': return 'Carte Grise'
    case 'vignette': return 'Vignette Fiscale'
    case 'autorisation': return "Autorisation d'Exploitation"
    default: return type
  }
}

function formatPlate(plate?: string): string {
  return plate || '—'
}

function formatDate(d?: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-DZ', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

onMounted(() => {
  fetchExpiringDocs()
})
</script>

<style scoped>
.expiring-docs-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.page-subtitle {
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.days-select {
  width: 180px;
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.kpi-icon {
  font-size: 2.25rem;
}

.kpi-num {
  font-size: 1.85rem;
  font-weight: 800;
  line-height: 1.1;
}

.kpi-label {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
}

.kpi-danger .kpi-num { color: #DC2626; }
.kpi-warning .kpi-num { color: #D97706; }
.kpi-info-box .kpi-num { color: var(--color-primary); }

/* Table */
.table-card {
  padding: 0;
  overflow: hidden;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

.table-container {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.table th {
  background: var(--color-surface-2);
  padding: 0.85rem 1rem;
  text-align: left;
  font-weight: 700;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border);
}

.table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.table tbody tr:hover {
  background: var(--color-surface-2);
}

.doc-type-cell {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.doc-icon {
  font-size: 1.4rem;
}

.doc-title {
  color: var(--color-text-primary);
}

.days-indicator {
  font-size: 0.75rem;
  margin-top: 0.2rem;
}

.plate-mini {
  display: inline-block;
  background: #FEF08A;
  color: #854D0E;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  border: 1px solid #FDE047;
  margin-top: 0.2rem;
}

.branch-tag {
  background: var(--color-surface-3);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}

.filters-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.w-52 { width: 13rem; }
.w-40 { width: 10rem; }

.badge-danger {
  background: #FEE2E2;
  color: #DC2626;
  font-weight: 700;
}

.badge-warning {
  background: #FEF3C7;
  color: #D97706;
  font-weight: 700;
}

.badge-neutral {
  background: var(--color-surface-3);
  color: var(--color-text-secondary);
}

.text-error { color: #DC2626; }
.text-warning-dark { color: #D97706; }

.loading-state, .empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}
</style>
