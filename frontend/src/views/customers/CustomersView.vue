<template>
  <div class="customers-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Gestion des Clients & KYC</h1>
        <p class="page-subtitle">Répertoire centralisé des locataires (Particuliers & Entreprises) et conformité KYC Algérie</p>
      </div>
      <button v-if="can('customers.customer.create')" class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouveau client
      </button>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-grid">
      <div class="card kpi-card">
        <div class="kpi-icon">👥</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ totalClients }}</div>
          <div class="kpi-label">Total clients enregistrés</div>
        </div>
      </div>

      <div class="card kpi-card kpi-success">
        <div class="kpi-icon">🟢</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ verifiedKycCount }}</div>
          <div class="kpi-label">Dossiers KYC validés</div>
        </div>
      </div>

      <div class="card kpi-card kpi-warning">
        <div class="kpi-icon">🟡</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ pendingKycCount }}</div>
          <div class="kpi-label">En attente de vérification</div>
        </div>
      </div>

      <div class="card kpi-card kpi-danger">
        <div class="kpi-icon">⛔</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ blacklistedCount }}</div>
          <div class="kpi-label">Inscrits sur liste noire</div>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par nom, téléphone, NIN (18 chiffres), permis..."
            class="input"
            @input="debounceFetch"
          />
        </div>

        <div class="filter-group w-36">
          <select v-model="filters.type" class="input" @change="fetchCustomers">
            <option value="">Tous types</option>
            <option value="individual">Particulier</option>
            <option value="company">Entreprise</option>
          </select>
        </div>

        <div class="filter-group w-40">
          <select v-model="filters.kyc_status" class="input" @change="fetchCustomers">
            <option value="">Tous statuts KYC</option>
            <option value="verified">🟢 Vérifié</option>
            <option value="pending">🟡 En attente</option>
            <option value="rejected">🔴 Rejeté</option>
          </select>
        </div>

        <div class="filter-group w-40">
          <select v-model="filters.is_blacklisted" class="input" @change="fetchCustomers">
            <option value="">Tous les profils</option>
            <option value="1">⛔ Liste Noire</option>
            <option value="0">Éligibles standard</option>
          </select>
        </div>

        <div class="filter-group w-44">
          <select v-model="filters.wilaya_code" class="input" @change="fetchCustomers">
            <option value="">Toutes Wilayas</option>
            <option v-for="w in wilayas" :key="w.code" :value="w.code">
              {{ w.code }} - {{ w.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement des dossiers clients...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="customers.length === 0" class="card empty-state">
      <div class="empty-icon">👥</div>
      <h3>Aucun client trouvé</h3>
      <p>Ajustez vos filtres de recherche ou enregistrez un nouveau client.</p>
      <button class="btn btn-primary btn-sm mt-3" @click="openModal()">+ Nouveau client</button>
    </div>

    <!-- Customers Table -->
    <div v-else class="card table-card">
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>Client / Type</th>
              <th>Identifiants Algérie (NIN & Permis)</th>
              <th>Coordonnées</th>
              <th class="th-wilaya">Wilaya / Ville</th>
              <th class="th-kyc">Statut KYC</th>
              <th>Risque / Fichage</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in customers" :key="c.id" :class="[c.is_blacklisted && 'row-blacklisted']">
              <td>
                <div class="client-cell">
                  <div class="avatar-badge" :class="c.type === 'company' ? 'avatar-company' : 'avatar-individual'">
                    {{ c.type === 'company' ? '🏢' : '👤' }}
                  </div>
                  <div>
                    <strong class="client-name">{{ c.full_name }}</strong>
                    <div class="text-xs text-muted">
                      {{ c.type === 'company' ? 'Entreprise / Flotte' : `${c.age ? c.age + ' ans' : 'Particulier'}` }}
                    </div>
                  </div>
                </div>
              </td>

              <td>
                <div class="id-cell">
                  <div v-if="c.national_id_number" class="id-item">
                    <span class="id-label">NIN :</span>
                    <span class="font-mono font-bold">{{ c.national_id_number }}</span>
                  </div>
                  <div v-if="c.driver_license_number" class="id-item">
                    <span class="id-label">Permis :</span>
                    <span class="font-mono">{{ c.driver_license_number }}</span>
                    <span v-if="c.license_seniority_years !== null" class="seniority-tag">
                      ({{ c.license_seniority_years }} ans)
                    </span>
                  </div>
                  <div v-if="!c.national_id_number && !c.driver_license_number" class="text-muted text-xs">
                    Non renseigné
                  </div>
                </div>
              </td>

              <td>
                <div class="contact-cell">
                  <div class="phone-link">
                    <a :href="`tel:${c.phone}`">📞 {{ c.phone }}</a>
                  </div>
                  <div v-if="c.email" class="email-text">
                    ✉️ {{ c.email }}
                  </div>
                </div>
              </td>

              <td class="cell-wilaya">
                <span class="wilaya-pill">
                  <span v-if="c.wilaya_code" class="wilaya-code">W.{{ c.wilaya_code }}</span>
                  <span class="wilaya-city">{{ c.city || '—' }}</span>
                </span>
              </td>

              <td class="cell-kyc">
                <span :class="['badge', 'kyc-badge', getKycBadge(c.kyc_status).class]">
                  <span class="kyc-dot">{{ getKycBadge(c.kyc_status).icon }}</span>
                  <span>{{ getKycBadge(c.kyc_status).text }}</span>
                </span>
              </td>

              <td>
                <div v-if="c.is_blacklisted">
                  <span class="badge badge-danger">⛔ LISTE NOIRE</span>
                </div>
                <div v-else>
                  <span :class="['badge', c.kyc_status === 'verified' ? 'badge-success' : 'badge-neutral']">
                    {{ c.kyc_status === 'verified' ? '✓ Éligible' : 'Conditionné' }}
                  </span>
                </div>
              </td>

              <td class="text-right">
                <div class="actions-group">
                  <RouterLink :to="`/customers/${c.id}`" class="btn btn-ghost btn-sm text-primary">
                    Fiche client →
                  </RouterLink>
                  <button v-if="can('customers.customer.update')" class="btn btn-secondary btn-sm" @click="openModal(c)">
                    Modifier
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Create / Edit Customer -->
    <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal-content card max-w-2xl">
        <div class="modal-header">
          <div>
            <h3>{{ editingId ? 'Modifier la fiche client' : 'Nouveau dossier client' }}</h3>
            <p class="text-xs text-muted">Informations d'identité conformes aux normes de location en Algérie</p>
          </div>
          <button class="modal-close-btn" @click="showModal = false">✕</button>
        </div>

        <form @submit.prevent="saveCustomer">
          <div v-if="errorMsg" class="alert alert-error mb-4">
            {{ errorMsg }}
          </div>

          <!-- Type selection -->
          <div class="type-selector mb-4">
            <button
              type="button"
              :class="['type-tab-btn', form.type === 'individual' && 'active']"
              @click="form.type = 'individual'"
            >
              👤 Client Particulier
            </button>
            <button
              type="button"
              :class="['type-tab-btn', form.type === 'company' && 'active']"
              @click="form.type = 'company'"
            >
              🏢 Société / Entreprise
            </button>
          </div>

          <div class="form-grid">
            <!-- Individual Fields -->
            <template v-if="form.type === 'individual'">
              <div class="form-group">
                <label class="label">Prénom *</label>
                <input v-model="form.first_name" type="text" required class="input" placeholder="ex: Amine" />
              </div>

              <div class="form-group">
                <label class="label">Nom de famille *</label>
                <input v-model="form.last_name" type="text" required class="input" placeholder="ex: Belkacem" />
              </div>

              <div class="form-group">
                <label class="label">Date de naissance</label>
                <input v-model="form.birth_date" type="date" class="input" />
              </div>

              <div class="form-group">
                <label class="label">Lieu de naissance</label>
                <input v-model="form.birth_place" type="text" class="input" placeholder="ex: Alger Centre" />
              </div>
            </template>

            <!-- Company Fields -->
            <template v-else>
              <div class="form-group col-span-2">
                <label class="label">Raison Sociale / Nom Entreprise *</label>
                <input v-model="form.company_name" type="text" required class="input" placeholder="ex: SARL NUMIDIA SERVICES" />
              </div>

              <div class="form-group">
                <label class="label">NIF / N° Registre Commerce (RC)</label>
                <input v-model="form.company_tax_id" type="text" class="input font-mono" placeholder="ex: 001916012345678" />
              </div>

              <div class="form-group">
                <label class="label">Contact / Représentant</label>
                <input v-model="form.first_name" type="text" class="input" placeholder="ex: Yacine Benali" />
              </div>
            </template>

            <!-- Contact -->
            <div class="form-group">
              <label class="label">Téléphone (Algérie) *</label>
              <input v-model="form.phone" type="text" required class="input" placeholder="+213 550 00 00 00" />
            </div>

            <div class="form-group">
              <label class="label">Adresse E-mail</label>
              <input v-model="form.email" type="email" class="input" placeholder="contact@domaine.dz" />
            </div>

            <!-- Identifiers Algérie -->
            <div class="section-divider col-span-2">
              <span class="divider-title">Pièces d'Identité & Permis de Conduire</span>
            </div>

            <div class="form-group col-span-2">
              <label class="label">Numéro d'Identification Nationale (NIN) ou CNI</label>
              <input
                v-model="form.national_id_number"
                type="text"
                class="input font-mono"
                placeholder="NIN 18 chiffres (figurant sur la CNI biométrique ou passeport)"
              />
            </div>

            <div class="form-group">
              <label class="label">Numéro de Permis de Conduire</label>
              <input
                v-model="form.driver_license_number"
                type="text"
                class="input font-mono"
                placeholder="ex: 16/2018/98412"
              />
            </div>

            <div class="form-group">
              <label class="label">Date de délivrance du permis</label>
              <input v-model="form.driver_license_issued_at" type="date" class="input" />
            </div>

            <!-- Address & Wilaya -->
            <div class="section-divider col-span-2">
              <span class="divider-title">Localisation</span>
            </div>

            <div class="form-group">
              <label class="label">Wilaya de résidence</label>
              <select v-model="form.wilaya_code" class="input">
                <option value="">Sélectionner la Wilaya</option>
                <option v-for="w in wilayas" :key="w.code" :value="w.code">
                  {{ w.code }} - {{ w.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Ville / Commune</label>
              <input v-model="form.city" type="text" class="input" placeholder="ex: Bab Ezzouar, Hydra..." />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Adresse complète</label>
              <input v-model="form.address" type="text" class="input" placeholder="Rue, numéro, quartier..." />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Notes internes</label>
              <textarea v-model="form.notes" rows="2" class="input" placeholder="Remarques particulières..."></textarea>
            </div>
          </div>

          <div class="modal-footer mt-5">
            <button type="button" class="btn btn-secondary" @click="showModal = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Enregistrement...' : editingId ? 'Mettre à jour' : 'Enregistrer' }}
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
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function can(permission: string) {
  return auth.hasRole('Super Admin') || auth.hasPermission(permission)
}

const wilayas = [
  { code: '01', name: 'Adrar' },
  { code: '02', name: 'Chlef' },
  { code: '06', name: 'Béjaïa' },
  { code: '09', name: 'Blida' },
  { code: '13', name: 'Tlemcen' },
  { code: '15', name: 'Tizi Ouzou' },
  { code: '16', name: 'Alger' },
  { code: '19', name: 'Sétif' },
  { code: '25', name: 'Constantine' },
  { code: '31', name: 'Oran' },
  { code: '35', name: 'Boumerdès' },
  { code: '42', name: 'Tipaza' },
]

interface CustomerItem {
  id: number
  type: string
  first_name?: string
  last_name?: string
  full_name: string
  company_name?: string
  company_tax_id?: string
  phone: string
  email?: string
  national_id_number?: string
  driver_license_number?: string
  driver_license_issued_at?: string
  driver_license_wilaya?: string
  birth_date?: string
  age?: number
  license_seniority_years?: number
  address?: string
  wilaya_code?: string
  city?: string
  kyc_status: string
  is_blacklisted: boolean
  is_active: boolean
  documents_count?: number
}

const customers = ref<CustomerItem[]>([])
const loading = ref(false)
const submitting = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const errorMsg = ref('')

const filters = ref({
  search: '',
  type: '',
  kyc_status: '',
  is_blacklisted: '',
  wilaya_code: '',
})

const form = ref({
  type: 'individual',
  first_name: '',
  last_name: '',
  company_name: '',
  company_tax_id: '',
  phone: '+213 ',
  email: '',
  national_id_number: '',
  driver_license_number: '',
  driver_license_issued_at: '',
  birth_date: '',
  birth_place: '',
  address: '',
  wilaya_code: '',
  city: '',
  notes: '',
})

let debounceTimer: any = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchCustomers()
  }, 350)
}

async function fetchCustomers() {
  loading.value = true
  try {
    const { data } = await api.get('/customers', {
      params: filters.value,
    })
    customers.value = data.data.items || []
  } catch (err) {
    console.error('Failed to load customers', err)
  } finally {
    loading.value = false
  }
}

const totalClients = computed(() => customers.value.length)
const verifiedKycCount = computed(() => customers.value.filter(c => c.kyc_status === 'verified').length)
const pendingKycCount = computed(() => customers.value.filter(c => c.kyc_status === 'pending').length)
const blacklistedCount = computed(() => customers.value.filter(c => c.is_blacklisted).length)

function getKycBadge(status: string) {
  switch (status) {
    case 'verified': return { class: 'badge-success', icon: '🟢', text: 'Vérifié' }
    case 'rejected': return { class: 'badge-danger', icon: '🔴', text: 'Rejeté' }
    default: return { class: 'badge-warning', icon: '🟡', text: 'En attente' }
  }
}

function openModal(client?: CustomerItem) {
  errorMsg.value = ''
  if (client) {
    editingId.value = client.id
    form.value = {
      type: client.type || 'individual',
      first_name: client.first_name || '',
      last_name: client.last_name || '',
      company_name: client.company_name || '',
      company_tax_id: client.company_tax_id || '',
      phone: client.phone || '+213 ',
      email: client.email || '',
      national_id_number: client.national_id_number || '',
      driver_license_number: client.driver_license_number || '',
      driver_license_issued_at: client.driver_license_issued_at || '',
      birth_date: client.birth_date || '',
      birth_place: '',
      address: client.address || '',
      wilaya_code: client.wilaya_code || '',
      city: client.city || '',
      notes: '',
    }
  } else {
    editingId.value = null
    form.value = {
      type: 'individual',
      first_name: '',
      last_name: '',
      company_name: '',
      company_tax_id: '',
      phone: '+213 ',
      email: '',
      national_id_number: '',
      driver_license_number: '',
      driver_license_issued_at: '',
      birth_date: '',
      birth_place: '',
      address: '',
      wilaya_code: '',
      city: '',
      notes: '',
    }
  }
  showModal.value = true
}

async function saveCustomer() {
  submitting.value = true
  errorMsg.value = ''
  try {
    if (editingId.value) {
      await api.put(`/customers/${editingId.value}`, form.value)
    } else {
      await api.post('/customers', form.value)
    }
    showModal.value = false
    await fetchCustomers()
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Erreur lors de la sauvegarde du client'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchCustomers()
})
</script>

<style scoped>
.customers-view {
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

/* KPI Summary */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.kpi-icon {
  font-size: 2rem;
}

.kpi-num {
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1.1;
}

.kpi-label {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-top: 0.2rem;
}

.kpi-success .kpi-num { color: #16A34A; }
.kpi-warning .kpi-num { color: #D97706; }
.kpi-danger .kpi-num { color: #DC2626; }

/* Filters */
.filters-row {
  display: flex;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.w-36 { width: 9rem; }
.w-40 { width: 10rem; }
.w-44 { width: 11rem; }

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

.row-blacklisted {
  background: #FEF2F2 !important;
}

.client-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar-badge {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.avatar-individual { background: #EEF2FF; }
.avatar-company { background: #F0FDF4; }

.client-name {
  color: var(--color-text-primary);
  font-size: 0.95rem;
}

.id-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.id-item {
  font-size: 0.8rem;
}

.id-label {
  color: var(--color-text-secondary);
  margin-right: 0.25rem;
}

.seniority-tag {
  color: #16A34A;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 0.25rem;
}

.contact-cell {
  font-size: 0.8rem;
}

.phone-link a {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 600;
}

.email-text {
  color: var(--color-text-secondary);
  margin-top: 0.15rem;
}

.th-wilaya,
.cell-wilaya {
  white-space: nowrap;
  min-width: 140px;
}

.th-kyc,
.cell-kyc {
  white-space: nowrap;
  min-width: 130px;
}

.wilaya-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: var(--color-surface-2);
  padding: 0.3rem 0.65rem;
  border-radius: 99px;
  font-size: 0.8125rem;
  font-weight: 600;
  border: 1px solid var(--color-border);
  white-space: nowrap;
}

.wilaya-code {
  color: var(--color-primary);
  font-weight: 700;
  background: var(--color-primary-50);
  padding: 0.15rem 0.4rem;
  border-radius: 6px;
  font-size: 0.75rem;
  line-height: 1.2;
}

.wilaya-city {
  color: var(--color-text-primary);
}

.kyc-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.75rem;
  border-radius: var(--radius-pill);
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
  line-height: 1;
}

.kyc-dot {
  font-size: 0.65rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.actions-group {
  display: flex;
  justify-content: flex-end;
  gap: 0.4rem;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-content {
  width: 100%;
  max-width: 680px;
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow-modal);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.25rem;
}

.modal-close-btn {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: var(--color-text-secondary);
}

.type-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  background: var(--color-surface-2);
  padding: 0.35rem;
  border-radius: var(--radius-md);
}

.type-tab-btn {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);
  font-weight: 600;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.type-tab-btn.active {
  background: var(--color-surface);
  color: var(--color-primary);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}

.col-span-2 { grid-column: span 2; }

.section-divider {
  border-top: 1px solid var(--color-border);
  margin-top: 0.5rem;
  padding-top: 0.5rem;
}

.divider-title {
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

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

.badge-success {
  background: #DCFCE7;
  color: #16A34A;
  font-weight: 700;
}

.badge-neutral {
  background: var(--color-surface-3);
  color: var(--color-text-secondary);
}
</style>
