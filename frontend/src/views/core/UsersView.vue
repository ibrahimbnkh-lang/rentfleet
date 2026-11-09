<template>
  <div class="users-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-subtitle">Gestion des comptes, accès et affectations d'agences</p>
      </div>
      <button v-if="can('core.user.create')" class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouvel utilisateur
      </button>
    </div>

    <!-- Filters -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par nom, email ou téléphone..."
            class="input"
            @input="debounceFetch"
          />
        </div>
        <div class="filter-group w-48">
          <select v-model="filters.branch_id" class="input" @change="fetchUsers">
            <option value="">Toutes les agences</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
        </div>
        <div class="filter-group w-40">
          <select v-model="filters.role" class="input" @change="fetchUsers">
            <option value="">Tous les rôles</option>
            <option v-for="r in roles" :key="r.id" :value="r.name">
              {{ r.name }}
            </option>
          </select>
        </div>
        <div class="filter-group w-36">
          <select v-model="filters.status" class="input" @change="fetchUsers">
            <option value="">Tous les états</option>
            <option value="1">Actifs</option>
            <option value="0">Inactifs</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading / Empty -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement des utilisateurs...</p>
    </div>

    <div v-else-if="users.length === 0" class="card empty-state">
      <div class="empty-icon">👥</div>
      <h3>Aucun utilisateur trouvé</h3>
      <p>Ajustez vos filtres ou créez un nouveau compte.</p>
    </div>

    <!-- Users Table -->
    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Agence</th>
            <th>Rôles</th>
            <th>Double Auth (2FA)</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>
              <div class="user-meta">
                <div class="avatar-circle">
                  {{ u.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="user-name">{{ u.name }}</div>
                  <div class="user-email">{{ u.email }}</div>
                  <div v-if="u.phone" class="user-phone">📞 {{ u.phone }}</div>
                </div>
              </div>
            </td>
            <td>
              <span v-if="u.branch" class="branch-pill">
                🏢 {{ u.branch.name }}
              </span>
              <span v-else class="text-muted">Toutes (Global)</span>
            </td>
            <td>
              <div class="roles-pills">
                <span v-for="r in u.roles" :key="r.id ?? r" class="badge badge-primary">
                  {{ typeof r === 'string' ? r : r.name }}
                </span>
                <span v-if="!u.roles?.length" class="text-muted text-xs">—</span>
              </div>
            </td>
            <td>
              <span :class="['badge', u.has_2fa ? 'badge-success' : 'badge-neutral']">
                {{ u.has_2fa ? 'Activé' : 'Non configuré' }}
              </span>
            </td>
            <td>
              <span :class="['badge', u.is_active ? 'badge-success' : 'badge-error']">
                {{ u.is_active ? 'Actif' : 'Désactivé' }}
              </span>
            </td>
            <td class="text-right">
              <div class="actions-group">
                <button v-if="can('core.user.update')" class="btn btn-secondary btn-sm" @click="openModal(u)">
                  Modifier
                </button>
                <button
                  v-if="u.id !== currentUserId && can('core.user.delete')"
                  class="btn btn-ghost btn-sm text-error"
                  @click="deleteUser(u)"
                >
                  Supprimer
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Create / Edit -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content card">
        <div class="modal-header">
          <h3>{{ editingId ? 'Modifier l\'utilisateur' : 'Créer un utilisateur' }}</h3>
          <button class="modal-close-btn" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="saveUser">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="label">Nom complet *</label>
              <input v-model="form.name" required class="input" placeholder="ex: Karim Benali" />
            </div>

            <div class="form-group">
              <label class="label">Adresse e-mail *</label>
              <input v-model="form.email" type="email" required class="input" placeholder="karim@rentfleet.dz" />
            </div>

            <div class="form-group">
              <label class="label">Téléphone</label>
              <input v-model="form.phone" class="input" placeholder="+213 555 00 00 00" />
            </div>

            <div class="form-group col-span-2">
              <label class="label">
                {{ editingId ? 'Nouveau mot de passe (laisser vide pour ne pas modifier)' : 'Mot de passe initial *' }}
              </label>
              <input
                v-model="form.password"
                type="password"
                :required="!editingId"
                class="input"
                placeholder="Au moins 8 caractères"
                minlength="8"
              />
            </div>

            <div class="form-group">
              <label class="label">Agence de rattachement</label>
              <select v-model="form.branch_id" class="input">
                <option :value="null">Aucune (Siège / Super Admin)</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">
                  {{ b.name }} ({{ b.city }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Rôle attribué *</label>
              <select v-model="form.selectedRole" class="input" required>
                <option value="">Sélectionner un rôle</option>
                <option v-for="r in roles" :key="r.id" :value="r.name">
                  {{ r.name }}
                </option>
              </select>
            </div>

            <div class="form-group col-span-2 flex items-center gap-2">
              <input id="user_active" v-model="form.is_active" type="checkbox" />
              <label for="user_active" class="label mb-0 cursor-pointer">Compte actif (autorisé à se connecter)</label>
            </div>
          </div>

          <div v-if="modalError" class="modal-error mt-3">
            {{ modalError }}
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="modalSaving">
              {{ modalSaving ? 'Enregistrement...' : (editingId ? 'Mettre à jour' : 'Créer l\'utilisateur') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const currentUserId = computed(() => authStore.user?.id)
function can(permission: string) {
  return authStore.hasRole('Super Admin') || authStore.hasPermission(permission)
}

interface UserItem {
  id: number
  name: string
  email: string
  phone?: string
  branch_id?: number
  branch?: { id: number; name: string }
  roles: any[]
  has_2fa?: boolean
  is_active: boolean
}

const users = ref<UserItem[]>([])
const branches = ref<any[]>([])
const roles = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const modalSaving = ref(false)
const modalError = ref<string | null>(null)

const filters = ref({
  search: '',
  branch_id: '',
  role: '',
  status: '',
})

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  branch_id: null as number | null,
  selectedRole: 'Agent',
  is_active: true,
})

let debounceTimer: any = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchUsers, 300)
}

async function fetchMetadata() {
  try {
    const [bRes, rRes] = await Promise.all([
      api.get('/core/branches?per_page=100'),
      api.get('/core/roles'),
    ])
    branches.value = bRes.data.data.data ?? bRes.data.data
    roles.value = rRes.data.data
  } catch (err) {
    console.error('Failed to fetch metadata', err)
  }
}

async function fetchUsers() {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id
    if (filters.value.role) params.role = filters.value.role
    if (filters.value.status !== '') params.is_active = filters.value.status

    const { data } = await api.get('/core/users', { params })
    users.value = data.data.data ?? data.data
  } catch (err) {
    console.error('Failed to fetch users', err)
  } finally {
    loading.value = false
  }
}

function openModal(user?: UserItem) {
  modalError.value = null
  if (user) {
    editingId.value = user.id
    const currentRole = user.roles?.[0]
    form.value = {
      name: user.name,
      email: user.email,
      phone: user.phone ?? '',
      password: '',
      branch_id: user.branch_id ?? null,
      selectedRole: typeof currentRole === 'string' ? currentRole : (currentRole?.name ?? 'Agent'),
      is_active: user.is_active,
    }
  } else {
    editingId.value = null
    form.value = {
      name: '',
      email: '',
      phone: '',
      password: '',
      branch_id: null,
      selectedRole: 'Agent',
      is_active: true,
    }
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function saveUser() {
  modalSaving.value = true
  modalError.value = null
  try {
    const payload: any = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || null,
      branch_id: form.value.branch_id,
      roles: form.value.selectedRole ? [form.value.selectedRole] : [],
      is_active: form.value.is_active,
    }

    if (form.value.password) {
      payload.password = form.value.password
    }

    if (editingId.value) {
      await api.put(`/core/users/${editingId.value}`, payload)
    } else {
      await api.post('/core/users', payload)
    }

    closeModal()
    fetchUsers()
  } catch (err: any) {
    modalError.value = err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement'
  } finally {
    modalSaving.value = false
  }
}

async function deleteUser(user: UserItem) {
  if (!confirm(`Confirmez-vous la suppression de l'utilisateur "${user.name}" ?`)) return
  try {
    await api.delete(`/core/users/${user.id}`)
    fetchUsers()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Erreur lors de la suppression')
  }
}

onMounted(() => {
  fetchMetadata()
  fetchUsers()
})
</script>

<style scoped>
.users-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.user-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
}

.user-name {
  font-weight: 600;
  color: var(--color-text-primary);
}

.user-email {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.user-phone {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}

.branch-pill {
  font-size: 0.85rem;
  color: var(--color-text-primary);
}

.roles-pills {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.actions-group {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.text-right { text-align: right; }
.text-muted { color: var(--color-text-muted); }
.text-error { color: var(--color-error); }

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

.col-span-2 { grid-column: span 2; }

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
