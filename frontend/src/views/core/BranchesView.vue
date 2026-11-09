<template>
  <div class="branches-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-title-row">
          <h1 class="page-title">Agences</h1>
          <span
            v-if="quota"
            :class="['badge font-mono', quota.is_reached ? 'badge-error' : 'badge-neutral']"
            :title="`Quota licence : ${quota.current} sur ${quota.max} autorisées`"
          >
            Quota : {{ quota.current }} / {{ quota.max }}
          </span>
        </div>
        <p class="page-subtitle">Gestion des succursales et points de retrait en Algérie</p>
      </div>
      <button v-if="can('core.branch.create')" class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouvelle agence
      </button>
    </div>

    <!-- Filters Bar -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par nom, code ou ville..."
            class="input"
            @input="debounceFetch"
          />
        </div>
        <div class="filter-group w-48">
          <select v-model="filters.wilaya" class="input" @change="fetchBranches">
            <option value="">Toutes les Wilayas</option>
            <option v-for="w in wilayas" :key="w.code" :value="w.code">
              {{ w.code }} - {{ w.name }}
            </option>
          </select>
        </div>
        <div class="filter-group w-36">
          <select v-model="filters.status" class="input" @change="fetchBranches">
            <option value="">Tous les états</option>
            <option value="1">Actives</option>
            <option value="0">Inactives</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading / Error states -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement des agences...</p>
    </div>

    <div v-else-if="branches.length === 0" class="card empty-state">
      <div class="empty-icon">🏢</div>
      <h3>Aucune agence trouvée</h3>
      <p>Ajustez vos filtres ou créez votre première agence.</p>
      <button v-if="can('core.branch.create')" class="btn btn-primary btn-sm mt-3" @click="openModal()">+ Ajouter une agence</button>
    </div>

    <!-- Branches Grid -->
    <div v-else class="branches-grid">
      <div v-for="b in branches" :key="b.id" class="card branch-card">
        <div class="branch-card-header">
          <div class="branch-code-badge">{{ b.code }}</div>
          <span :class="['badge', b.is_active ? 'badge-success' : 'badge-neutral']">
            {{ b.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <h3 class="branch-name">{{ b.name }}</h3>

        <div class="branch-info-list">
          <div class="info-item">
            <span class="icon">📍</span>
            <span>Wilaya {{ b.wilaya_code }} — {{ b.city }}</span>
          </div>
          <div class="info-item">
            <span class="icon">🏢</span>
            <span class="text-truncate">{{ b.address }}</span>
          </div>
          <div class="info-item">
            <span class="icon">📞</span>
            <span>{{ b.phone }}</span>
          </div>
          <div v-if="b.email" class="info-item">
            <span class="icon">✉️</span>
            <span>{{ b.email }}</span>
          </div>
          <div class="info-item">
            <span class="icon">👥</span>
            <span>{{ b.users_count ?? 0 }} utilisateur(s)</span>
          </div>
        </div>

        <div class="branch-card-footer">
          <a :href="`/agences/${b.code}`" target="_blank" class="btn btn-ghost btn-sm text-primary">
            🌐 Voir le site
          </a>
          <button v-if="can('core.branch.update')" class="btn btn-secondary btn-sm" @click="openModal(b)">
            Modifier
          </button>
          <button v-if="can('core.branch.delete')" class="btn btn-ghost btn-sm text-error" @click="deleteBranch(b)">
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Create / Edit -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content card">
        <div class="modal-header">
          <h3>{{ editingId ? 'Modifier l\'agence' : 'Créer une nouvelle agence' }}</h3>
          <button class="modal-close-btn" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="saveBranch">
          <div v-if="!editingId && quota?.is_reached" class="modal-error mb-4">
            ⚠️ <strong>Quota licence atteint :</strong> Votre licence autorise un maximum de {{ quota.max }} agences. Vous ne pouvez pas en créer davantage sans renouveler ou mettre à niveau votre licence.
          </div>

          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="label">Nom de l'agence *</label>
              <input v-model="form.name" required class="input" placeholder="ex: Agence Alger Centre" />
            </div>

            <div class="form-group">
              <label class="label">Code unique *</label>
              <input v-model="form.code" required class="input" placeholder="ex: ALG-01" />
            </div>

            <div class="form-group">
              <label class="label">Wilaya *</label>
              <select v-model="form.wilaya_code" required class="input" @change="onWilayaChange">
                <option value="">Sélectionner une wilaya</option>
                <option v-for="w in wilayas" :key="w.code" :value="w.code">
                  {{ w.code }} - {{ w.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Ville / Commune *</label>
              <input v-model="form.city" required class="input" placeholder="ex: Alger" />
            </div>

            <div class="form-group">
              <label class="label">Téléphone *</label>
              <input v-model="form.phone" required class="input" placeholder="+213 21 00 00 00" />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Adresse postale *</label>
              <input v-model="form.address" required class="input" placeholder="Rue, quartier, numéro..." />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Email de l'agence</label>
              <input v-model="form.email" type="email" class="input" placeholder="agence@rentfleet.dz" />
            </div>

            <div class="form-group col-span-2 flex items-center gap-2">
              <input id="branch_active" v-model="form.is_active" type="checkbox" />
              <label for="branch_active" class="label mb-0 cursor-pointer">Agence active</label>
            </div>
          </div>

          <div v-if="modalError" class="modal-error mt-3">
            {{ modalError }}
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="modalSaving">
              {{ modalSaving ? 'Enregistrement...' : (editingId ? 'Mettre à jour' : 'Créer l\'agence') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function can(permission: string) {
  return auth.hasRole('Super Admin') || auth.hasPermission(permission)
}

interface Branch {
  id: number
  name: string
  code: string
  wilaya_code: string
  city: string
  address: string
  phone: string
  email?: string
  is_active: boolean
  users_count?: number
}

const branches = ref<Branch[]>([])
const quota = ref<{ current: number; max: number; is_reached: boolean } | null>(null)
const loading = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const modalSaving = ref(false)
const modalError = ref<string | null>(null)

const filters = ref({
  search: '',
  wilaya: '',
  status: '',
})

const form = ref({
  name: '',
  code: '',
  wilaya_code: '',
  city: '',
  address: '',
  phone: '',
  email: '',
  is_active: true,
})

const wilayas = [
  { code: '01', name: 'Adrar' },
  { code: '02', name: 'Chlef' },
  { code: '03', name: 'Laghouat' },
  { code: '04', name: 'Oum El Bouaghi' },
  { code: '05', name: 'Batna' },
  { code: '06', name: 'Béjaïa' },
  { code: '07', name: 'Biskra' },
  { code: '08', name: 'Béchar' },
  { code: '09', name: 'Blida' },
  { code: '10', name: 'Bouira' },
  { code: '11', name: 'Tamanrasset' },
  { code: '12', name: 'Tébessa' },
  { code: '13', name: 'Tlemcen' },
  { code: '14', name: 'Tiaret' },
  { code: '15', name: 'Tizi Ouzou' },
  { code: '16', name: 'Alger' },
  { code: '17', name: 'Djelfa' },
  { code: '18', name: 'Jijel' },
  { code: '19', name: 'Sétif' },
  { code: '20', name: 'Saïda' },
  { code: '21', name: 'Skikda' },
  { code: '22', name: 'Sidi Bel Abbès' },
  { code: '23', name: 'Annaba' },
  { code: '24', name: 'Guelma' },
  { code: '25', name: 'Constantine' },
  { code: '26', name: 'Médéa' },
  { code: '27', name: 'Mostaganem' },
  { code: '28', name: 'M\'Sila' },
  { code: '29', name: 'Mascara' },
  { code: '30', name: 'Ouargla' },
  { code: '31', name: 'Oran' },
  { code: '35', name: 'Boumerdès' },
  { code: '39', name: 'El Oued' },
  { code: '42', name: 'Tipaza' },
  { code: '47', name: 'Ghardaïa' },
]

let debounceTimer: any = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchBranches, 300)
}

function onWilayaChange() {
  const selected = wilayas.find(w => w.code === form.value.wilaya_code)
  if (selected && !form.value.city) {
    form.value.city = selected.name
  }
}

async function fetchBranches() {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.wilaya) params.wilaya_code = filters.value.wilaya
    if (filters.value.status !== '') params.is_active = filters.value.status

    const { data } = await api.get('/core/branches', { params })
    branches.value = data.data.data ?? data.data
    if (data.data?.meta?.quota) {
      quota.value = data.data.meta.quota
    } else if (data.meta?.quota) {
      quota.value = data.meta.quota
    }
  } catch (err: any) {
    console.error('Failed to fetch branches', err)
  } finally {
    loading.value = false
  }
}

function openModal(branch?: Branch) {
  modalError.value = null
  if (branch) {
    editingId.value = branch.id
    form.value = {
      name: branch.name,
      code: branch.code,
      wilaya_code: branch.wilaya_code,
      city: branch.city,
      address: branch.address,
      phone: branch.phone,
      email: branch.email ?? '',
      is_active: branch.is_active,
    }
  } else {
    editingId.value = null
    form.value = {
      name: '',
      code: '',
      wilaya_code: '16',
      city: 'Alger',
      address: '',
      phone: '+213 ',
      email: '',
      is_active: true,
    }
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function saveBranch() {
  modalSaving.value = true
  modalError.value = null
  try {
    if (editingId.value) {
      await api.put(`/core/branches/${editingId.value}`, form.value)
    } else {
      await api.post('/core/branches', form.value)
    }
    closeModal()
    fetchBranches()
  } catch (err: any) {
    modalError.value = err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement'
  } finally {
    modalSaving.value = false
  }
}

async function deleteBranch(branch: Branch) {
  if (!confirm(`Confirmez-vous la suppression de l'agence "${branch.name}" ?`)) return
  try {
    await api.delete(`/core/branches/${branch.id}`)
    fetchBranches()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Erreur lors de la suppression')
  }
}

onMounted(() => {
  fetchBranches()
})
</script>

<style scoped>
.branches-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--color-text-primary);
}

.page-subtitle {
  color: var(--color-text-secondary);
  font-size: 0.9rem;
}

.filters-card {
  padding: 1rem;
}

.filters-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.branches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.branch-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.branch-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-elevated);
}

.branch-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.branch-code-badge {
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-weight: 700;
  font-size: 0.8rem;
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-sm);
  letter-spacing: 0.05em;
}

.branch-name {
  font-size: 1.15rem;
  font-weight: 700;
}

.branch-info-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-item .icon {
  font-size: 1rem;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.branch-card-footer {
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid var(--color-border);
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.text-error {
  color: var(--color-error);
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.modal-close-btn {
  background: transparent;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: var(--color-text-secondary);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.col-span-2 {
  grid-column: span 2;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.modal-error {
  background: var(--color-error-light);
  color: var(--color-error);
  padding: 0.6rem 0.8rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
