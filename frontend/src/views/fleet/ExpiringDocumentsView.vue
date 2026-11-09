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
                <div class="actions-cell">
                  <button
                    type="button"
                    class="btn btn-renew"
                    @click="openRenewModal(doc)"
                    title="Mettre à jour ou renouveler le document"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                    </svg>
                    <span>Renouveler</span>
                  </button>
                  <RouterLink
                    v-if="doc.vehicle"
                    :to="`/fleet/vehicles/${doc.vehicle.id}`"
                    class="btn btn-secondary btn-sm"
                  >
                    <span>Véhicule</span>
                    <span class="arrow-icon">→</span>
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Renewal / Update Document Modal -->
    <div v-if="showRenewModal && selectedDoc" class="modal-backdrop" @click.self="showRenewModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <span class="modal-icon">{{ getDocIcon(selectedDoc.type) }}</span>
            <div>
              <h3 class="modal-title">Renouveler le document</h3>
              <p class="modal-subtitle">
                {{ formatDocType(selectedDoc.type) }} —
                <span class="font-bold">{{ selectedDoc.vehicle?.brand }} {{ selectedDoc.vehicle?.model }}</span>
                ({{ formatPlate(selectedDoc.vehicle?.registration_plate) }})
              </p>
            </div>
          </div>
          <button class="modal-close" @click="showRenewModal = false">✕</button>
        </div>

        <form @submit.prevent="submitRenewal" class="modal-form">
          <div class="form-grid">
            <div class="form-group">
              <label class="label">Numéro de police / Référence *</label>
              <input
                v-model="renewForm.reference_number"
                type="text"
                class="input"
                required
                placeholder="Ex: POL-2026-99882"
              />
            </div>

            <div class="form-group">
              <label class="label">Organisme / Émetteur</label>
              <input
                v-model="renewForm.issuer"
                type="text"
                class="input"
                placeholder="Ex: SAA Assurances, CAAT, Centre Technique..."
              />
            </div>

            <div class="form-group">
              <label class="label">Date d'émission</label>
              <input
                v-model="renewForm.issued_at"
                type="date"
                class="input"
              />
            </div>

            <div class="form-group">
              <label class="label">Nouvelle date d'expiration *</label>
              <input
                v-model="renewForm.expires_at"
                type="date"
                class="input"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label class="label">Notes / Observations</label>
            <textarea
              v-model="renewForm.notes"
              rows="2"
              class="input textarea"
              placeholder="Informations complémentaires sur le renouvellement..."
            ></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showRenewModal = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="renewSubmitting">
              <span v-if="renewSubmitting">Enregistrement...</span>
              <span v-else>💾 Valider le renouvellement</span>
            </button>
          </div>
        </form>
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

// Renewal modal state
const showRenewModal = ref(false)
const selectedDoc = ref<VehicleDocumentItem | null>(null)
const renewSubmitting = ref(false)
const renewForm = ref({
  reference_number: '',
  issuer: '',
  issued_at: '',
  expires_at: '',
  notes: '',
})

function openRenewModal(doc: VehicleDocumentItem) {
  selectedDoc.value = doc
  renewForm.value = {
    reference_number: doc.reference_number || '',
    issuer: doc.issuer || '',
    issued_at: doc.issued_at ? String(doc.issued_at).split('T')[0] : '',
    expires_at: doc.expires_at ? String(doc.expires_at).split('T')[0] : '',
    notes: doc.notes || '',
  }
  showRenewModal.value = true
}

async function submitRenewal() {
  if (!selectedDoc.value) return
  renewSubmitting.value = true
  try {
    await api.put(
      `/fleet/vehicles/${selectedDoc.value.vehicle_id}/documents/${selectedDoc.value.id}`,
      renewForm.value
    )
    showRenewModal.value = false
    await fetchExpiringDocs()
  } catch (err: any) {
    alert(err.response?.data?.error?.message || err.response?.data?.message || 'Erreur lors du renouvellement')
  } finally {
    renewSubmitting.value = false
  }
}

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
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  return documents.value.filter(d => {
    if (!d.expires_at) return false
    const exp = new Date(d.expires_at)
    exp.setHours(0, 0, 0, 0)
    return exp.getTime() < now.getTime()
  }).length
})

const urgentCount = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const sevenDays = new Date(now)
  sevenDays.setDate(sevenDays.getDate() + 7)
  return documents.value.filter(d => {
    if (!d.expires_at) return false
    const exp = new Date(d.expires_at)
    exp.setHours(0, 0, 0, 0)
    return exp.getTime() >= now.getTime() && exp.getTime() <= sevenDays.getTime()
  }).length
})

const filteredDocs = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const sevenDays = new Date(now)
  sevenDays.setDate(sevenDays.getDate() + 7)

  return documents.value.filter(doc => {
    // Type filter
    if (filterType.value && doc.type !== filterType.value) return false

    // Urgency filter
    if (filterUrgency.value) {
      if (!doc.expires_at) return false
      const exp = new Date(doc.expires_at)
      exp.setHours(0, 0, 0, 0)

      if (filterUrgency.value === 'expired' && exp.getTime() >= now.getTime()) return false
      if (filterUrgency.value === 'critical' && (exp.getTime() < now.getTime() || exp.getTime() > sevenDays.getTime())) return false
      if (filterUrgency.value === 'upcoming' && exp.getTime() <= sevenDays.getTime()) return false
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

  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const exp = new Date(doc.expires_at)
  exp.setHours(0, 0, 0, 0)
  const diffDays = Math.round((exp.getTime() - now.getTime()) / (1000 * 3600 * 24))

  if (diffDays < 0) {
    return {
      badgeClass: 'badge-danger',
      label: 'EXPIRÉ',
      subtext: `Expiré depuis ${Math.abs(diffDays)} j`,
      textClass: 'text-error font-bold',
    }
  }

  if (diffDays === 0) {
    return {
      badgeClass: 'badge-danger',
      label: 'EXPIRE AUJOURD\'HUI',
      subtext: 'Échéance aujourd\'hui',
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

/* Actions Cell */
.actions-cell {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

.btn-renew {
  background: linear-gradient(135deg, #5A3CDF 0%, #4322C4 100%);
  color: #ffffff !important;
  font-weight: 600;
  font-size: 0.8125rem;
  padding: 0.42rem 0.85rem;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  box-shadow: 0 2px 6px rgba(90, 60, 223, 0.35);
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  line-height: 1;
}

.btn-renew:hover {
  background: linear-gradient(135deg, #4d31cb 0%, #3719af 100%);
  box-shadow: 0 4px 12px rgba(90, 60, 223, 0.5);
  transform: translateY(-1px);
  color: #ffffff !important;
}

.btn-renew:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(90, 60, 223, 0.3);
}

.arrow-icon {
  font-size: 1.05rem;
  line-height: 1;
  transition: transform 0.15s ease;
}

.actions-cell a:hover .arrow-icon {
  transform: translateX(2px);
}

/* Modal Styling */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  width: 100%;
  max-width: 580px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modal-enter 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

@keyframes modal-enter {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-surface-2);
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-icon {
  font-size: 1.75rem;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.modal-subtitle {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-top: 0.15rem;
}

.modal-close {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.25rem;
  line-height: 1;
  border-radius: 6px;
  transition: all 0.2s;
}

.modal-close:hover {
  color: var(--color-text-primary);
  background: var(--color-surface-3);
}

.modal-form {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.textarea {
  resize: vertical;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.5rem;
  border-top: 1px solid var(--color-border);
}
</style>
