<template>
  <div class="roles-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Rôles & Permissions</h1>
        <p class="page-subtitle">Définition des profils d'accès et attribution des droits granulaires</p>
      </div>
      <button v-if="can('core.role.create')" class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouveau rôle
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement des rôles et autorisations...</p>
    </div>

    <!-- Roles Grid -->
    <div v-else class="roles-grid">
      <div v-for="role in roles" :key="role.id" class="card role-card">
        <div class="role-card-header">
          <div class="role-title-wrap">
            <h3 class="role-name">{{ role.name }}</h3>
            <span class="badge badge-primary">
              {{ role.permissions?.length ?? 0 }} permission(s)
            </span>
          </div>
          <span v-if="role.name === 'Super Admin'" class="badge badge-warning">
            Protégé
          </span>
        </div>

        <p class="role-desc">
          {{ getRoleDescription(role.name) }}
        </p>

        <!-- Preview of top permissions -->
        <div class="permissions-preview">
          <span
            v-for="p in (role.permissions || []).slice(0, 6)"
            :key="p.id ?? p.name"
            class="permission-tag"
          >
            {{ formatPerm(p.name) }}
          </span>
          <span v-if="(role.permissions?.length ?? 0) > 6" class="text-xs text-muted">
            +{{ (role.permissions?.length ?? 0) - 6 }} autre(s)
          </span>
        </div>

        <div class="role-card-footer">
          <button v-if="can('core.role.update')" class="btn btn-secondary btn-sm" @click="openModal(role)">
            Modifier les droits
          </button>
          <button
            v-if="role.name !== 'Super Admin' && can('core.role.delete')"
            class="btn btn-ghost btn-sm text-error"
            @click="deleteRole(role)"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Create / Edit Role -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content card max-w-2xl">
        <div class="modal-header">
          <h3>{{ editingId ? `Modifier le rôle: ${form.name}` : 'Créer un nouveau rôle' }}</h3>
          <button class="modal-close-btn" @click="closeModal">✕</button>
        </div>

        <form @submit.prevent="saveRole">
          <div class="form-group mb-4">
            <label class="label">Nom du rôle *</label>
            <input
              v-model="form.name"
              required
              class="input"
              placeholder="ex: Superviseur d'Agence"
              :disabled="form.name === 'Super Admin'"
            />
          </div>

          <div class="permissions-manager">
            <div class="flex justify-between items-center mb-2">
              <label class="label mb-0">Permissions accordées</label>
              <div class="flex gap-2">
                <button type="button" class="btn btn-ghost btn-sm text-xs" @click="selectAllPerms">
                  Tout sélectionner
                </button>
                <button type="button" class="btn btn-ghost btn-sm text-xs" @click="clearAllPerms">
                  Tout désélectionner
                </button>
              </div>
            </div>

            <!-- Grouped by Module -->
            <div class="modules-accordion">
              <div v-for="(resources, modKey) in groupedPermissions" :key="modKey" class="module-perm-block card">
                <div class="module-perm-header">
                  <h4 class="module-title">{{ formatModuleName(modKey) }}</h4>
                  <button
                    type="button"
                    class="text-xs text-primary font-medium hover:underline"
                    @click="toggleModulePerms(modKey)"
                  >
                    Basculer le module
                  </button>
                </div>

                <div class="resources-grid">
                  <div v-for="(perms, resKey) in resources" :key="resKey" class="resource-col">
                    <h5 class="resource-name">{{ resKey }}</h5>
                    <div class="perms-list">
                      <label
                        v-for="p in perms"
                        :key="p.name"
                        class="perm-checkbox-label"
                      >
                        <input
                          type="checkbox"
                          :value="p.name"
                          :checked="form.selectedPerms.includes(p.name)"
                          @change="togglePerm(p.name)"
                        />
                        <span>{{ p.action }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="modalError" class="modal-error mt-3">
            {{ modalError }}
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="closeModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="modalSaving">
              {{ modalSaving ? 'Enregistrement...' : 'Enregistrer les autorisations' }}
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

interface RoleItem {
  id: number
  name: string
  permissions: any[]
}

const roles = ref<RoleItem[]>([])
const groupedPermissions = ref<Record<string, Record<string, any[]>>>({})
const allPermissionNames = ref<string[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const modalSaving = ref(false)
const modalError = ref<string | null>(null)

const form = ref({
  name: '',
  selectedPerms: [] as string[],
})

function getRoleDescription(name: string): string {
  switch (name) {
    case 'Super Admin':
      return 'Accès complet sans restriction à l\'ensemble des modules et configurations.'
    case 'Manager':
      return 'Gestion d\'agence, supervision des utilisateurs, de la flotte et des clients.'
    case 'Agent':
      return 'Opérations quotidiennes : accueil client, vérification des dossiers et inspection.'
    case 'Accountant':
      return 'Consultation financière, états des factures et bilans sans modification technique.'
    case 'Mechanic':
      return 'Maintenance technique, kilométrage et mise à jour du statut des véhicules.'
    default:
      return 'Rôle personnalisé configuré par l\'administrateur.'
  }
}

function formatPerm(perm: string): string {
  return perm.replace('.', ' › ')
}

function formatModuleName(key: string): string {
  switch (key.toLowerCase()) {
    case 'core':
      return '⚙️ Module M00 — Plateforme & Sécurité'
    case 'fleet':
      return '🚗 Module M01 — Flotte & Documents'
    case 'customers':
      return '👥 Module M02 — Clients & KYC'
    default:
      return `📦 ${key.toUpperCase()}`
  }
}

async function fetchRolesAndPerms() {
  loading.value = true
  try {
    const [rRes, pRes] = await Promise.all([
      api.get('/core/roles'),
      api.get('/core/permissions'),
    ])
    roles.value = rRes.data.data
    groupedPermissions.value = pRes.data.data.grouped ?? {}
    allPermissionNames.value = (pRes.data.data.all ?? []).map((p: any) => p.name)
  } catch (err) {
    console.error('Failed to load roles and permissions', err)
  } finally {
    loading.value = false
  }
}

function openModal(role?: RoleItem) {
  modalError.value = null
  if (role) {
    editingId.value = role.id
    form.value = {
      name: role.name,
      selectedPerms: (role.permissions || []).map((p: any) => p.name),
    }
  } else {
    editingId.value = null
    form.value = {
      name: '',
      selectedPerms: [],
    }
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function togglePerm(name: string) {
  const index = form.value.selectedPerms.indexOf(name)
  if (index >= 0) {
    form.value.selectedPerms.splice(index, 1)
  } else {
    form.value.selectedPerms.push(name)
  }
}

function selectAllPerms() {
  form.value.selectedPerms = [...allPermissionNames.value]
}

function clearAllPerms() {
  form.value.selectedPerms = []
}

function toggleModulePerms(modKey: string) {
  const modulePerms: string[] = []
  const resources = groupedPermissions.value[modKey] || {}
  for (const resKey in resources) {
    for (const p of resources[resKey]) {
      modulePerms.push(p.name)
    }
  }

  const allSelected = modulePerms.every(p => form.value.selectedPerms.includes(p))
  if (allSelected) {
    form.value.selectedPerms = form.value.selectedPerms.filter(p => !modulePerms.includes(p))
  } else {
    const set = new Set([...form.value.selectedPerms, ...modulePerms])
    form.value.selectedPerms = Array.from(set)
  }
}

async function saveRole() {
  modalSaving.value = true
  modalError.value = null
  try {
    const payload = {
      name: form.value.name,
      permissions: form.value.selectedPerms,
    }

    if (editingId.value) {
      await api.put(`/core/roles/${editingId.value}`, payload)
    } else {
      await api.post('/core/roles', payload)
    }

    closeModal()
    fetchRolesAndPerms()
  } catch (err: any) {
    modalError.value = err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement'
  } finally {
    modalSaving.value = false
  }
}

async function deleteRole(role: RoleItem) {
  if (!confirm(`Confirmez-vous la suppression du rôle "${role.name}" ?`)) return
  try {
    await api.delete(`/core/roles/${role.id}`)
    fetchRolesAndPerms()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Erreur lors de la suppression')
  }
}

onMounted(() => {
  fetchRolesAndPerms()
})
</script>

<style scoped>
.roles-view {
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

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.25rem;
}

.role-card {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.role-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-elevated);
}

.role-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.role-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.role-name {
  font-size: 1.2rem;
  font-weight: 700;
}

.role-desc {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.permissions-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  padding: 0.75rem 0;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.permission-tag {
  background: var(--color-surface-2);
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
  font-family: monospace;
}

.role-card-footer {
  margin-top: auto;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
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
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
}

.max-w-2xl { max-width: 720px; }

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
}

.modules-accordion {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 48vh;
  overflow-y: auto;
  padding-right: 0.25rem;
}

.module-perm-block {
  padding: 1rem;
  background: var(--color-surface-2);
}

.module-perm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.module-title {
  font-size: 0.95rem;
  font-weight: 700;
}

.resources-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.75rem;
}

.resource-col {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.resource-name {
  font-size: 0.8rem;
  text-transform: uppercase;
  color: var(--color-text-muted);
  font-weight: 700;
  letter-spacing: 0.05em;
}

.perms-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.perm-checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.825rem;
  cursor: pointer;
  color: var(--color-text-primary);
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

.loading-state {
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
