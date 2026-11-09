<template>
  <div class="license-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Licence & Modules</h1>
        <p class="page-subtitle">Gestion de la clé de licence, des quotas de véhicules et des modules activés</p>
      </div>
      <button class="btn btn-secondary" @click="fetchLicenseAndModules">
        🔄 Actualiser le statut
      </button>
    </div>

    <!-- Alert Feedback -->
    <div v-if="successMsg" class="alert alert-success">
      ✅ {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="alert alert-error">
      ⚠️ {{ errorMsg }}
    </div>

    <!-- Active License Summary Card -->
    <div class="card license-hero-card">
      <div class="hero-top">
        <div class="hero-client-meta">
          <div class="license-status-badge">
            <span :class="['badge badge-lg', license?.is_active && !license?.is_expired ? 'badge-success' : 'badge-error']">
              {{ license?.is_active && !license?.is_expired ? '🟢 Licence Active & Vérifiée' : '🔴 Licence Inactive ou Expirée' }}
            </span>
          </div>
          <h2 class="client-name">{{ license?.client_name ?? 'RentFleet Algérie' }}</h2>
          <p class="client-email">✉️ {{ license?.client_email ?? 'contact@rentfleet.dz' }}</p>
        </div>

        <div class="countdown-tile">
          <div class="days-number">{{ license?.days_remaining ?? 0 }}</div>
          <div class="days-label">Jours restants</div>
          <div class="expires-date">Expire le {{ formatDate(license?.expires_at) }}</div>
        </div>
      </div>

      <div class="license-key-bar">
        <span class="label mb-0 text-muted">Clé de licence :</span>
        <code class="license-code">{{ license?.license_key ?? 'RF-DEV-0000-0000' }}</code>
      </div>

      <!-- Quotas Row -->
      <div class="quotas-row">
        <div class="quota-card">
          <div class="quota-icon">🚗</div>
          <div>
            <div class="quota-value">{{ license?.max_vehicles ?? 50 }}</div>
            <div class="quota-label">Véhicules maximum autorisés</div>
          </div>
        </div>
        <div class="quota-card">
          <div class="quota-icon">🏢</div>
          <div>
            <div class="quota-value">{{ license?.max_branches ?? 5 }}</div>
            <div class="quota-label">Agences maximum autorisées</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modules Status Grid -->
    <div class="card modules-section">
      <h3 class="section-title">État des modules RentFleet</h3>
      <p class="section-subtitle">Chaque module est contrôlé par le manifeste et validé par la signature cryptographique de la licence.</p>

      <div class="modules-grid mt-4">
        <div
          v-for="mod in modules"
          :key="mod.id"
          :class="['module-card', mod.is_active ? 'module-active' : 'module-inactive']"
        >
          <div class="module-card-header">
            <div>
              <div class="module-id-badge">{{ mod.id }}</div>
              <h4 class="module-card-name">{{ mod.name }}</h4>
            </div>
            <span :class="['badge', mod.is_active ? 'badge-success' : 'badge-neutral']">
              {{ mod.is_active ? 'Activé' : 'Verrouillé' }}
            </span>
          </div>

          <p class="module-desc">{{ mod.description }}</p>

          <div class="module-card-footer">
            <span v-if="mod.is_core" class="text-xs text-muted">Socle obligatoire</span>
            <span v-else-if="mod.is_licensed" class="text-xs text-success font-medium">✓ Inclus dans la licence</span>
            <span v-else class="text-xs text-error font-medium">✗ Non souscrit</span>
          </div>
        </div>
      </div>
    </div>

    <!-- License Activation Panel -->
    <div class="card activation-panel">
      <h3 class="section-title">Activer ou renouveler une licence</h3>
      <p class="section-subtitle">Collez le jeton de licence signé ou téléversez le fichier .lic fourni par RentFleet.</p>

      <form class="mt-4" @submit.prevent="activateToken">
        <div class="form-group">
          <label class="label">Jeton de licence signé (Base64 HMAC / Ed25519)</label>
          <textarea
            v-model="tokenInput"
            rows="4"
            class="input font-mono text-xs"
            placeholder="Collez ici le jeton de licence complet..."
            required
          ></textarea>
        </div>

        <div class="activation-actions">
          <button type="submit" class="btn btn-primary" :disabled="activating">
            {{ activating ? 'Vérification en cours...' : '🔑 Activer cette licence' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'

const license = ref<any>(null)
const modules = ref<any[]>([])
const tokenInput = ref('')
const activating = ref(false)
const successMsg = ref<string | null>(null)
const errorMsg = ref<string | null>(null)

function formatDate(iso: string): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-DZ', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

async function fetchLicenseAndModules() {
  try {
    const [licRes, modRes] = await Promise.all([
      api.get('/core/license'),
      api.get('/core/modules'),
    ])
    license.value = licRes.data.data
    modules.value = Object.values(modRes.data.data)
  } catch (err) {
    console.error('Failed to load license details', err)
  }
}

async function activateToken() {
  activating.value = true
  successMsg.value = null
  errorMsg.value = null
  try {
    const { data } = await api.post('/core/license/activate', {
      license_token: tokenInput.value.trim(),
    })
    successMsg.value = data.data.message ?? 'Licence activée avec succès !'
    tokenInput.value = ''
    fetchLicenseAndModules()
  } catch (err: any) {
    errorMsg.value = err.response?.data?.error?.message ?? 'Échec de l\'activation de la licence'
  } finally {
    activating.value = false
  }
}

onMounted(() => {
  fetchLicenseAndModules()
})
</script>

<style scoped>
.license-view {
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

.license-hero-card {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-primary-50) 100%);
  border: 1.5px solid var(--color-border);
}

.hero-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.client-name {
  font-size: 1.5rem;
  font-weight: 800;
  margin-top: 0.5rem;
}

.client-email {
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.countdown-tile {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 1.25rem 1.75rem;
  text-align: center;
  box-shadow: var(--shadow-card);
}

.days-number {
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--color-primary);
  line-height: 1;
}

.days-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
}

.expires-date {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-top: 0.4rem;
}

.license-key-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: var(--color-surface);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.license-code {
  font-weight: 700;
  color: var(--color-primary);
  letter-spacing: 0.05em;
  font-size: 0.95rem;
}

.quotas-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.quota-card {
  background: var(--color-surface);
  border-radius: var(--radius-md);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid var(--color-border);
}

.quota-icon {
  font-size: 1.75rem;
}

.quota-value {
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--color-text-primary);
}

.quota-label {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.modules-section {
  padding: 1.75rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
}

.section-subtitle {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
}

.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

.module-card {
  padding: 1.25rem;
  border-radius: var(--radius-md);
  border: 1.5px solid var(--color-border);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  transition: all 0.2s;
}

.module-card.module-active {
  border-color: var(--color-primary-100);
}

.module-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.module-id-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-primary);
  font-family: monospace;
}

.module-card-name {
  font-size: 1.1rem;
  font-weight: 700;
  margin-top: 0.2rem;
}

.module-desc {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

.module-card-footer {
  margin-top: auto;
  padding-top: 0.6rem;
  border-top: 1px solid var(--color-border);
}

.activation-panel {
  padding: 1.75rem;
}

.activation-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.alert {
  padding: 0.75rem 1.25rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 500;
}

.alert-success {
  background: var(--color-success-light);
  color: var(--color-success);
}

.alert-error {
  background: var(--color-error-light);
  color: var(--color-error);
}
</style>
