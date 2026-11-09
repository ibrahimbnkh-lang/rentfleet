<template>
  <div class="audit-log-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Journal d'audit</h1>
        <p class="page-subtitle">Traçabilité complète des actions système, créations, modifications et suppressions</p>
      </div>
      <button class="btn btn-secondary" @click="fetchLogs">
        🔄 Actualiser
      </button>
    </div>

    <!-- Filters -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group w-44">
          <select v-model="filters.module" class="input" @change="fetchLogs">
            <option value="">Tous les modules</option>
            <option value="M00Core">M00 — Core</option>
            <option value="M01Fleet">M01 — Flotte</option>
            <option value="M02Customers">M02 — Clients</option>
          </select>
        </div>
        <div class="filter-group flex-1">
          <input
            v-model="filters.action"
            type="text"
            placeholder="Filtrer par action (ex: login, branch.created)..."
            class="input"
            @input="debounceFetch"
          />
        </div>
        <div class="filter-group w-44">
          <input
            v-model="filters.date_from"
            type="date"
            class="input"
            title="Date de début"
            @change="fetchLogs"
          />
        </div>
        <div class="filter-group w-44">
          <input
            v-model="filters.date_to"
            type="date"
            class="input"
            title="Date de fin"
            @change="fetchLogs"
          />
        </div>
      </div>
    </div>

    <!-- Loading / Empty -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement du journal d'audit...</p>
    </div>

    <div v-else-if="logs.length === 0" class="card empty-state">
      <div class="empty-icon">📋</div>
      <h3>Aucun événement d'audit enregistré</h3>
      <p>Les opérations système apparaîtront ici automatiquement.</p>
    </div>

    <!-- Logs Table -->
    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Date & Heure</th>
            <th>Utilisateur</th>
            <th>Module</th>
            <th>Action</th>
            <th>Adresse IP</th>
            <th class="text-right">Détails</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td class="font-mono text-xs whitespace-nowrap">
              {{ formatDate(log.created_at) }}
            </td>
            <td>
              <div class="user-cell">
                <span class="user-avatar-small">
                  {{ (log.user_name || 'S').charAt(0).toUpperCase() }}
                </span>
                <span>{{ log.user_name || 'Système' }}</span>
              </div>
            </td>
            <td>
              <span class="badge badge-primary">{{ log.module }}</span>
            </td>
            <td>
              <span :class="['badge', getActionBadgeClass(log.action)]">
                {{ log.action }}
              </span>
            </td>
            <td class="font-mono text-xs text-muted">
              {{ log.ip_address || '—' }}
            </td>
            <td class="text-right">
              <button
                v-if="log.old_values || log.new_values"
                class="btn btn-ghost btn-sm"
                @click="openDiffModal(log)"
              >
                Inspecter
              </button>
              <span v-else class="text-muted text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Diff Modal -->
    <div v-if="showModal && activeLog" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal-content card max-w-3xl">
        <div class="modal-header">
          <div>
            <h3>Détails de l'action : {{ activeLog.action }}</h3>
            <p class="text-xs text-muted mt-1">
              Par {{ activeLog.user_name }} le {{ formatDate(activeLog.created_at) }}
            </p>
          </div>
          <button class="modal-close-btn" @click="showModal = false">✕</button>
        </div>

        <div class="diff-container">
          <div class="diff-box">
            <h4 class="diff-box-title text-error">Valeurs précédentes (Old)</h4>
            <pre class="diff-pre">{{ formatJson(activeLog.old_values) }}</pre>
          </div>
          <div class="diff-box">
            <h4 class="diff-box-title text-success">Nouvelles valeurs (New)</h4>
            <pre class="diff-pre">{{ formatJson(activeLog.new_values) }}</pre>
          </div>
        </div>

        <div class="modal-footer mt-4">
          <button class="btn btn-secondary" @click="showModal = false">Fermer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'

interface AuditLogItem {
  id: number
  user_name?: string
  module: string
  action: string
  old_values?: any
  new_values?: any
  ip_address?: string
  created_at: string
}

const logs = ref<AuditLogItem[]>([])
const loading = ref(false)
const showModal = ref(false)
const activeLog = ref<AuditLogItem | null>(null)

const filters = ref({
  module: '',
  action: '',
  date_from: '',
  date_to: '',
})

let debounceTimer: any = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchLogs, 300)
}

function formatDate(iso: string): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleString('fr-DZ', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function getActionBadgeClass(action: string): string {
  if (action.includes('created')) return 'badge-success'
  if (action.includes('updated')) return 'badge-info'
  if (action.includes('deleted')) return 'badge-error'
  if (action.includes('login')) return 'badge-warning'
  return 'badge-neutral'
}

function formatJson(val: any): string {
  if (!val) return '— Aucun contenu —'
  return JSON.stringify(val, null, 2)
}

function openDiffModal(log: AuditLogItem) {
  activeLog.value = log
  showModal.value = true
}

async function fetchLogs() {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.module) params.module = filters.value.module
    if (filters.value.action) params.action = filters.value.action
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to

    const { data } = await api.get('/core/audit-log', { params })
    logs.value = data.data.data ?? data.data
  } catch (err) {
    console.error('Failed to load audit logs', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.audit-log-view {
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

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-avatar-small {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-weight: 700;
  font-size: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.text-right { text-align: right; }
.text-muted { color: var(--color-text-muted); }
.text-success { color: var(--color-success); }
.text-error { color: var(--color-error); }

/* Diff Modal */
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
  max-width: 800px;
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
  font-size: 1.2rem;
  cursor: pointer;
}

.diff-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.diff-box {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.diff-box-title {
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.diff-pre {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 0.75rem;
  font-size: 0.8rem;
  font-family: monospace;
  overflow-x: auto;
  max-height: 350px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
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
