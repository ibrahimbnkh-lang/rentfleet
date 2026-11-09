<template>
  <div class="settings-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Paramètres Généraux</h1>
        <p class="page-subtitle">Configuration globale de l'entreprise, localisation et règles fiscales</p>
      </div>
      <button class="btn btn-primary" :disabled="saving" @click="saveSettings">
        {{ saving ? 'Enregistrement...' : '💾 Sauvegarder les modifications' }}
      </button>
    </div>

    <!-- Alert Feedback -->
    <div v-if="successMsg" class="alert alert-success">
      ✅ {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="alert alert-error">
      ⚠️ {{ errorMsg }}
    </div>

    <!-- Tabs -->
    <div class="settings-tabs">
      <button
        :class="['tab-btn', activeTab === 'company' && 'active']"
        @click="activeTab = 'company'"
      >
        🏢 Entreprise & Légal (Algérie)
      </button>
      <button
        :class="['tab-btn', activeTab === 'financial' && 'active']"
        @click="activeTab = 'financial'"
      >
        💰 Facturation & Fiscalité (DZD / TVA)
      </button>
      <button
        :class="['tab-btn', activeTab === 'system' && 'active']"
        @click="activeTab = 'system'"
      >
        ⚙️ Système & Localisation
      </button>
    </div>

    <!-- Tab 1: Company -->
    <div v-if="activeTab === 'company'" class="card tab-panel">
      <h3 class="panel-title">Informations de l'entreprise</h3>
      <p class="panel-subtitle">Ces coordonnées figureront sur les contrats, fiches clients et exports.</p>

      <div class="form-grid mt-4">
        <div class="form-group col-span-2">
          <label class="label">Raison Sociale / Nom légal *</label>
          <input v-model="form.company_name" class="input" placeholder="ex: RentFleet Algérie SARL" />
        </div>

        <div class="form-group">
          <label class="label">Numéro d'Identification Fiscale (NIF)</label>
          <input v-model="form.company_nif" class="input" placeholder="ex: 002116012345678" />
        </div>

        <div class="form-group">
          <label class="label">Registre de Commerce (RC)</label>
          <input v-model="form.company_rc" class="input" placeholder="ex: 16/00-1234567B21" />
        </div>

        <div class="form-group">
          <label class="label">Téléphone principal</label>
          <input v-model="form.company_phone" class="input" placeholder="+213 23 00 00 00" />
        </div>

        <div class="form-group">
          <label class="label">Email de contact</label>
          <input v-model="form.company_email" type="email" class="input" placeholder="contact@rentfleet.dz" />
        </div>

        <div class="form-group col-span-2">
          <label class="label">Adresse du siège social</label>
          <input v-model="form.company_address" class="input" placeholder="Adresse complète..." />
        </div>
      </div>
    </div>

    <!-- Tab 2: Financial -->
    <div v-if="activeTab === 'financial'" class="card tab-panel">
      <h3 class="panel-title">Fiscalité et monnaie</h3>
      <p class="panel-subtitle">Règles financières appliquées aux réservations et calculs de cautions.</p>

      <div class="form-grid mt-4">
        <div class="form-group">
          <label class="label">Devise par défaut</label>
          <input v-model="form.currency" class="input" disabled value="DZD (Dinar Algérien)" />
          <span class="text-xs text-muted mt-1 block">Monnaie nationale officielle d'Algérie.</span>
        </div>

        <div class="form-group">
          <label class="label">Taux de TVA (%)</label>
          <div class="flex items-center gap-2">
            <input v-model="form.vat_rate" type="number" class="input" placeholder="19" />
            <span class="font-bold text-muted">%</span>
          </div>
          <span class="text-xs text-muted mt-1 block">Taux légal normal en Algérie (19%).</span>
        </div>
      </div>
    </div>

    <!-- Tab 3: System -->
    <div v-if="activeTab === 'system'" class="card tab-panel">
      <h3 class="panel-title">Préférences régionales & système</h3>
      <p class="panel-subtitle">Fuseau horaire et formats d'affichage.</p>

      <div class="form-grid mt-4">
        <div class="form-group">
          <label class="label">Fuseau horaire</label>
          <select v-model="form.timezone" class="input">
            <option value="Africa/Algiers">Africa/Algiers (UTC+1)</option>
            <option value="UTC">UTC</option>
          </select>
        </div>

        <div class="form-group">
          <label class="label">Format de date</label>
          <select v-model="form.date_format" class="input">
            <option value="d/m/Y">JJ/MM/AAAA (ex: 29/10/2026)</option>
            <option value="Y-m-d">AAAA-MM-JJ (ISO 8601)</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'

const activeTab = ref<'company' | 'financial' | 'system'>('company')
const saving = ref(false)
const successMsg = ref<string | null>(null)
const errorMsg = ref<string | null>(null)

const form = ref({
  company_name: 'RentFleet Algérie SARL',
  company_phone: '+213 23 45 67 89',
  company_email: 'contact@rentfleet.dz',
  company_address: 'Bab Ezzouar, Alger',
  company_nif: '002116012345678',
  company_rc: '16/00-1234567B21',
  currency: 'DZD',
  vat_rate: '19',
  timezone: 'Africa/Algiers',
  date_format: 'd/m/Y',
})

async function fetchSettings() {
  try {
    const { data } = await api.get('/core/settings')
    const res = data.data
    for (const k in res) {
      if (k in form.value) {
        (form.value as any)[k] = res[k]
      }
    }
  } catch (err) {
    console.error('Failed to load settings', err)
  }
}

async function saveSettings() {
  saving.value = true
  successMsg.value = null
  errorMsg.value = null

  try {
    const settingsArray = Object.entries(form.value).map(([key, value]) => ({
      key,
      value,
    }))

    await api.put('/core/settings', { settings: settingsArray })
    successMsg.value = 'Paramètres enregistrés avec succès.'
    setTimeout(() => { successMsg.value = null }, 3500)
  } catch (err: any) {
    errorMsg.value = err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement des paramètres'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.settings-view {
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

.settings-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.tab-btn {
  background: transparent;
  border: none;
  font-family: var(--font-sans);
  font-weight: 600;
  font-size: 0.9rem;
  padding: 0.6rem 1rem;
  border-radius: var(--radius-sm);
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: var(--color-surface-3);
  color: var(--color-text-primary);
}

.tab-btn.active {
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-weight: 700;
}

.tab-panel {
  padding: 2rem;
}

.panel-title {
  font-size: 1.25rem;
  font-weight: 700;
}

.panel-subtitle {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  margin-top: 0.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.col-span-2 { grid-column: span 2; }

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

.block { display: block; }
</style>
