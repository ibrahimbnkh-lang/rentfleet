<template>
  <div class="customer-detail-view">
    <!-- Top Bar -->
    <div class="detail-nav">
      <RouterLink to="/customers" class="btn btn-ghost btn-sm">
        ← Retour aux clients
      </RouterLink>
      <div v-if="customer" class="nav-actions">
        <button
          v-if="customer.is_blacklisted"
          class="btn btn-secondary btn-sm"
          @click="unblacklistCustomer"
        >
          ✓ Retirer de la liste noire
        </button>
        <button
          v-else
          class="btn btn-ghost btn-sm text-error"
          @click="showBlacklistModal = true"
        >
          ⛔ Inscrire sur liste noire
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement du dossier client...</p>
    </div>

    <!-- Content -->
    <div v-else-if="customer" class="detail-body">
      <!-- Profile Header Card -->
      <div class="card profile-card">
        <div class="profile-main">
          <div class="profile-avatar" :class="customer.type === 'company' ? 'avatar-co' : 'avatar-ind'">
            {{ customer.type === 'company' ? '🏢' : '👤' }}
          </div>
          <div class="profile-text">
            <div class="profile-title-row">
              <h1 class="profile-name">{{ customer.full_name }}</h1>
              <span class="badge badge-neutral">
                {{ customer.type === 'company' ? 'Entreprise' : 'Particulier' }}
              </span>
              <span :class="['badge', getKycBadge(customer.kyc_status).class]">
                {{ getKycBadge(customer.kyc_status).label }}
              </span>
              <span v-if="customer.is_blacklisted" class="badge badge-danger">
                ⛔ FICHÉ LISTE NOIRE
              </span>
            </div>

            <div class="profile-meta-row">
              <span v-if="customer.age">🎂 {{ customer.age }} ans (né le {{ formatDate(customer.birth_date) }})</span>
              <span>📍 Wilaya {{ customer.wilaya_code || '—' }} ({{ customer.city || 'Algérie' }})</span>
              <span v-if="customer.address">🏢 {{ customer.address }}</span>
            </div>

            <div class="profile-contacts-row">
              <a :href="`tel:${customer.phone}`" class="contact-pill">
                📞 {{ customer.phone }}
              </a>
              <a
                :href="`https://wa.me/${cleanPhone(customer.phone)}`"
                target="_blank"
                class="contact-pill whatsapp"
              >
                💬 WhatsApp
              </a>
              <a v-if="customer.email" :href="`mailto:${customer.email}`" class="contact-pill email">
                ✉️ {{ customer.email }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Automated Rental Eligibility Banner -->
      <div
        class="card eligibility-banner"
        :class="eligibility?.is_eligible ? 'banner-eligible' : 'banner-ineligible'"
      >
        <div class="banner-icon">
          {{ eligibility?.is_eligible ? '🛡️' : '⚠️' }}
        </div>
        <div class="banner-content">
          <div class="banner-title">
            {{ eligibility?.is_eligible ? 'CLIENT ÉLIGIBLE À LA LOCATION' : 'LOCATION NON AUTORISÉE — CRITÈRES NON SATISFAITS' }}
          </div>
          <p v-if="eligibility?.is_eligible" class="banner-desc">
            Le client remplit l'ensemble des conditions légales algériennes : permis de conduire valide (> 2 ans), âge requis, dossier KYC vérifié et aucune inscription sur la liste noire.
          </p>
          <div v-else class="banner-issues">
            <p class="issues-title">Motifs d'inéligibilité :</p>
            <ul class="issues-list">
              <li v-for="(reason, idx) in eligibility?.reasons" :key="idx">
                {{ reason }}
              </li>
            </ul>
          </div>
        </div>

        <div v-if="customer.kyc_status === 'pending'" class="banner-action">
          <button class="btn btn-primary btn-sm" @click="verifyKyc">
            Valider le KYC maintenant
          </button>
        </div>
      </div>

      <!-- Two Columns: Technical Identity & KYC Control -->
      <div class="detail-grid">
        <!-- Identity & Legal credentials -->
        <div class="card">
          <h3 class="section-heading">📑 Pièces d'Identité & Permis (Algérie)</h3>
          <div class="data-rows">
            <div class="data-row">
              <span class="data-lbl">Numéro d'Identification Nationale (NIN) :</span>
              <span class="data-val font-mono font-bold">{{ customer.national_id_number || 'Non renseigné' }}</span>
            </div>

            <div class="data-row">
              <span class="data-lbl">Numéro de Permis de Conduire :</span>
              <span class="data-val font-mono font-bold">{{ customer.driver_license_number || 'Non renseigné' }}</span>
            </div>

            <div class="data-row">
              <span class="data-lbl">Date de délivrance du permis :</span>
              <span class="data-val">
                {{ formatDate(customer.driver_license_issued_at) }}
                <span v-if="customer.license_seniority_years !== null" class="text-xs text-muted">
                  ({{ customer.license_seniority_years }} an(s) d'ancienneté)
                </span>
              </span>
            </div>

            <div class="data-row">
              <span class="data-lbl">Wilaya de délivrance :</span>
              <span class="data-val">{{ customer.driver_license_wilaya || '—' }}</span>
            </div>

            <template v-if="customer.type === 'company'">
              <div class="data-row">
                <span class="data-lbl">Raison Sociale :</span>
                <span class="data-val font-bold">{{ customer.company_name }}</span>
              </div>
              <div class="data-row">
                <span class="data-lbl">NIF / Registre Commerce (RC) :</span>
                <span class="data-val font-mono">{{ customer.company_tax_id || '—' }}</span>
              </div>
            </template>
          </div>
        </div>

        <!-- KYC Decision Panel -->
        <div class="card">
          <div class="panel-header">
            <h3 class="section-heading mb-0">🔍 Contrôle & Validation KYC</h3>
            <span :class="['badge', getKycBadge(customer.kyc_status).class]">
              {{ getKycBadge(customer.kyc_status).label }}
            </span>
          </div>

          <div class="kyc-info-box mt-3">
            <div v-if="customer.kyc_status === 'verified'" class="kyc-verified-box">
              <p>Dossier validé le <strong>{{ formatDate(customer.kyc_verified_at) }}</strong> par l'agent <strong>{{ customer.verified_by?.name || 'Administrateur' }}</strong>.</p>
            </div>
            <div v-else-if="customer.kyc_status === 'rejected'" class="kyc-rejected-box">
              <p class="font-bold text-error">Dossier KYC rejeté :</p>
              <p class="text-sm mt-1">{{ customer.kyc_rejection_reason }}</p>
            </div>
            <div v-else class="kyc-pending-box">
              <p>Vérifiez la concordance entre les scans de pièces d'identité et les informations saisies avant de valider.</p>
            </div>
          </div>

          <div class="kyc-actions mt-4">
            <button
              v-if="customer.kyc_status !== 'verified'"
              class="btn btn-primary"
              :disabled="actionLoading"
              @click="verifyKyc"
            >
              ✓ Approuver le KYC
            </button>
            <button
              v-if="customer.kyc_status !== 'rejected'"
              class="btn btn-secondary text-error"
              :disabled="actionLoading"
              @click="showRejectModal = true"
            >
              ✕ Rejeter le dossier
            </button>
          </div>
        </div>
      </div>

      <!-- Documents Section -->
      <div class="card">
        <div class="panel-header">
          <div>
            <h3 class="section-heading mb-0">📂 Documents & Justificatifs Téléversés</h3>
            <p class="section-sub">Pièces d'identité CNI biométrique, permis de conduire et justificatifs</p>
          </div>
          <button class="btn btn-secondary btn-sm" @click="showDocModal = true">
            + Ajouter un document
          </button>
        </div>

        <div v-if="documents.length === 0" class="empty-docs">
          <p class="text-sm">Aucun document numérique associé à ce client.</p>
        </div>

        <div v-else class="docs-grid">
          <div v-for="doc in documents" :key="doc.id" class="doc-card">
            <div class="doc-icon-wrap">
              {{ getDocIcon(doc.type) }}
            </div>
            <div class="doc-details">
              <div class="doc-title">{{ formatDocType(doc.type) }}</div>
              <div class="doc-name font-mono text-xs text-muted">{{ doc.file_name || doc.file_path }}</div>
              <div class="doc-meta mt-1">
                <span :class="['badge badge-sm', doc.status === 'approved' ? 'badge-success' : doc.status === 'rejected' ? 'badge-danger' : 'badge-warning']">
                  {{ doc.status }}
                </span>
                <span class="text-xs text-muted">{{ formatDate(doc.created_at) }}</span>
              </div>
            </div>
            <button class="doc-del-btn" title="Supprimer" @click="deleteDoc(doc)">✕</button>
          </div>
        </div>
      </div>

      <!-- Blacklist History Section -->
      <div class="card">
        <div class="panel-header">
          <div>
            <h3 class="section-heading mb-0">⛔ Historique Liste Noire & Incidents</h3>
            <p class="section-sub">Signalements pour impayés, dégradations, vol ou comportement abusif</p>
          </div>
          <button
            v-if="!customer.is_blacklisted"
            class="btn btn-ghost btn-sm text-error"
            @click="showBlacklistModal = true"
          >
            + Inscrire sur liste noire
          </button>
        </div>

        <div v-if="!customer.blacklists || customer.blacklists.length === 0" class="empty-docs">
          <p class="text-sm">Aucun incident enregistré. Le client est en règle avec l'agence.</p>
        </div>

        <div v-else class="incidents-list">
          <div
            v-for="inc in customer.blacklists"
            :key="inc.id"
            class="incident-item"
            :class="`sev-${inc.severity}`"
          >
            <div class="incident-header">
              <span class="incident-badge badge badge-danger">{{ inc.reason }}</span>
              <span class="text-xs text-muted font-bold">Gravité : {{ inc.severity?.toUpperCase() }}</span>
              <span class="text-xs text-muted">Date incident : {{ formatDate(inc.incident_date) }}</span>
            </div>
            <p class="incident-notes mt-2">{{ inc.notes }}</p>
            <div class="incident-footer mt-2 text-xs text-muted">
              Signalé par : {{ inc.reported_by?.name || 'Agent' }} • Agence : {{ inc.branch?.city || 'Réseau' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject KYC Modal -->
    <div v-if="showRejectModal" class="modal-backdrop" @click.self="showRejectModal = false">
      <div class="modal-content card max-w-md">
        <div class="modal-header">
          <h3>Rejeter le dossier KYC</h3>
          <button class="modal-close-btn" @click="showRejectModal = false">✕</button>
        </div>
        <form @submit.prevent="rejectKyc">
          <div class="form-group">
            <label class="label">Motif du rejet *</label>
            <textarea
              v-model="rejectionReason"
              required
              rows="3"
              placeholder="ex: Permis de conduire illisible, justificatif expiré, NIN erroné..."
              class="input"
            ></textarea>
          </div>
          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="showRejectModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary bg-error" :disabled="actionLoading">
              Confirmer le rejet
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Blacklist Modal -->
    <div v-if="showBlacklistModal" class="modal-backdrop" @click.self="showBlacklistModal = false">
      <div class="modal-content card max-w-lg">
        <div class="modal-header">
          <div>
            <h3 class="text-error">⛔ Inscription sur la Liste Noire</h3>
            <p class="text-xs text-muted">Le client ne pourra plus louer de véhicule dans tout le réseau RentFleet Algérie</p>
          </div>
          <button class="modal-close-btn" @click="showBlacklistModal = false">✕</button>
        </div>

        <form @submit.prevent="submitBlacklist">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="label">Motif de l'inscription *</label>
              <select v-model="blacklistForm.reason" required class="input">
                <option value="impayes">Impayés / Chèque de caution sans provision</option>
                <option value="degradation">Dégradation grave du véhicule</option>
                <option value="vol">Tentative de vol / Détournement</option>
                <option value="infraction_grave">Infraction routière grave / Délit</option>
                <option value="comportement">Comportement agressif / Litige</option>
                <option value="autre">Autre motif</option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Niveau de gravité *</label>
              <select v-model="blacklistForm.severity" required class="input">
                <option value="high">Élevé (Interdiction stricte)</option>
                <option value="medium">Moyen (Surveillance requise)</option>
                <option value="low">Faible</option>
              </select>
            </div>

            <div class="form-group">
              <label class="label">Date de l'incident</label>
              <input v-model="blacklistForm.incident_date" type="date" class="input" />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Détails de l'incident / Justificatif *</label>
              <textarea
                v-model="blacklistForm.notes"
                required
                rows="3"
                placeholder="Circonstances détaillées de l'incident, immatriculation du véhicule concerné, montant des dégâts ou impayés..."
                class="input"
              ></textarea>
            </div>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="showBlacklistModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary bg-error" :disabled="actionLoading">
              Inscrire sur liste noire
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Document Modal -->
    <div v-if="showDocModal" class="modal-backdrop" @click.self="showDocModal = false">
      <div class="modal-content card max-w-md">
        <div class="modal-header">
          <h3>Ajouter un justificatif</h3>
          <button class="modal-close-btn" @click="showDocModal = false">✕</button>
        </div>

        <form @submit.prevent="submitDoc">
          <div class="form-group">
            <label class="label">Type de document *</label>
            <select v-model="docForm.type" required class="input">
              <option value="cni_recto">CNI Recto (Biométrique)</option>
              <option value="cni_verso">CNI Verso</option>
              <option value="permis_recto">Permis de Conduire Recto</option>
              <option value="permis_verso">Permis de Conduire Verso</option>
              <option value="passeport">Passeport</option>
              <option value="justificatif_domicile">Justificatif de domicile (Facture Sonelgaz / Résidence)</option>
              <option value="registre_commerce">Registre de Commerce (RC)</option>
              <option value="autre">Autre document</option>
            </select>
          </div>

          <div class="form-group mt-3">
            <label class="label">Nom ou chemin du fichier *</label>
            <input
              v-model="docForm.file_path"
              required
              class="input font-mono"
              placeholder="/uploads/customers/cni_client.jpg"
            />
          </div>

          <div class="form-group mt-3">
            <label class="label">Statut initial</label>
            <select v-model="docForm.status" class="input">
              <option value="pending">En attente de vérification</option>
              <option value="approved">Validé conforme</option>
              <option value="rejected">Rejeté / Illisible</option>
            </select>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="showDocModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="actionLoading">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'

const route = useRoute()
const customer = ref<any>(null)
const documents = ref<any[]>([])
const eligibility = ref<any>(null)
const loading = ref(true)
const actionLoading = ref(false)

const showRejectModal = ref(false)
const rejectionReason = ref('')

const showBlacklistModal = ref(false)
const blacklistForm = ref({
  reason: 'impayes',
  severity: 'high',
  incident_date: new Date().toISOString().split('T')[0],
  notes: '',
})

const showDocModal = ref(false)
const docForm = ref({
  type: 'cni_recto',
  file_path: '',
  status: 'pending',
})

async function fetchCustomer() {
  loading.value = true
  try {
    const id = route.params.id
    const { data } = await api.get(`/customers/${id}`)
    customer.value = data.data.customer
    eligibility.value = data.data.eligibility
    documents.value = customer.value.documents || []
  } catch (err) {
    console.error('Failed to load customer details', err)
  } finally {
    loading.value = false
  }
}

async function verifyKyc() {
  actionLoading.value = true
  try {
    const { data } = await api.post(`/customers/${customer.value.id}/kyc/verify`)
    customer.value = data.data.customer
    await refreshEligibility()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la validation KYC')
  } finally {
    actionLoading.value = false
  }
}

async function rejectKyc() {
  actionLoading.value = true
  try {
    const { data } = await api.post(`/customers/${customer.value.id}/kyc/reject`, {
      reason: rejectionReason.value,
    })
    customer.value = data.data.customer
    showRejectModal.value = false
    await refreshEligibility()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors du rejet KYC')
  } finally {
    actionLoading.value = false
  }
}

async function submitBlacklist() {
  actionLoading.value = true
  try {
    await api.post(`/customers/${customer.value.id}/blacklist`, blacklistForm.value)
    showBlacklistModal.value = false
    await fetchCustomer()
  } catch (err: any) {
    alert(err.response?.data?.message || "Erreur lors de l'inscription liste noire")
  } finally {
    actionLoading.value = false
  }
}

async function unblacklistCustomer() {
  if (!confirm('Êtes-vous sûr de vouloir lever le fichage sur la liste noire pour ce client ?')) return
  actionLoading.value = true
  try {
    await api.delete(`/customers/${customer.value.id}/blacklist`)
    await fetchCustomer()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la levée de liste noire')
  } finally {
    actionLoading.value = false
  }
}

async function submitDoc() {
  actionLoading.value = true
  try {
    await api.post(`/customers/${customer.value.id}/documents`, docForm.value)
    showDocModal.value = false
    await fetchCustomer()
  } catch (err: any) {
    alert(err.response?.data?.message || "Erreur lors de l'ajout du document")
  } finally {
    actionLoading.value = false
  }
}

async function deleteDoc(doc: any) {
  if (!confirm('Supprimer ce document ?')) return
  try {
    await api.delete(`/customers/${customer.value.id}/documents/${doc.id}`)
    await fetchCustomer()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la suppression')
  }
}

async function refreshEligibility() {
  try {
    const { data } = await api.get(`/customers/${customer.value.id}/eligible`)
    eligibility.value = data.data
  } catch (err) {
    console.error('Failed to refresh eligibility', err)
  }
}

function getKycBadge(status?: string) {
  switch (status) {
    case 'verified': return { class: 'badge-success', label: '🟢 KYC Vérifié' }
    case 'rejected': return { class: 'badge-danger', label: '🔴 KYC Rejeté' }
    default: return { class: 'badge-warning', label: '🟡 KYC En Attente' }
  }
}

function getDocIcon(type: string): string {
  if (type.includes('cni') || type.includes('passeport')) return '🆔'
  if (type.includes('permis')) return '🚗'
  if (type.includes('domicile')) return '🏠'
  if (type.includes('commerce')) return '🏢'
  return '📄'
}

function formatDocType(type: string): string {
  switch (type) {
    case 'cni_recto': return 'CNI Recto (Biométrique)'
    case 'cni_verso': return 'CNI Verso'
    case 'permis_recto': return 'Permis de Conduire Recto'
    case 'permis_verso': return 'Permis de Conduire Verso'
    case 'passeport': return 'Passeport'
    case 'justificatif_domicile': return 'Justificatif de domicile'
    case 'registre_commerce': return 'Registre de Commerce (RC)'
    default: return type
  }
}

function formatDate(d?: string): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-DZ', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function cleanPhone(phone?: string): string {
  if (!phone) return ''
  return phone.replace(/[^0-9]/g, '')
}

onMounted(() => {
  fetchCustomer()
})
</script>

<style scoped>
.customer-detail-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.detail-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-body {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.profile-card {
  padding: 1.5rem;
}

.profile-main {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.profile-avatar {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.avatar-ind { background: #EEF2FF; }
.avatar-co { background: #F0FDF4; }

.profile-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.35rem;
}

.profile-name {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.profile-meta-row {
  display: flex;
  gap: 1.25rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.profile-contacts-row {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.contact-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.75rem;
  border-radius: 99px;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
  background: var(--color-surface-2);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}

.contact-pill.whatsapp {
  background: #DCFCE7;
  color: #16A34A;
  border-color: #BBF7D0;
}

/* Eligibility Banner */
.eligibility-banner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem 1.5rem;
  border-radius: var(--radius-lg);
}

.banner-eligible {
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  color: #166534;
}

.banner-ineligible {
  background: #FEF2F2;
  border: 1px solid #FECACA;
  color: #991B1B;
}

.banner-icon {
  font-size: 2.5rem;
}

.banner-content {
  flex: 1;
}

.banner-title {
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.banner-desc {
  font-size: 0.85rem;
  margin-top: 0.25rem;
  opacity: 0.9;
}

.issues-title {
  font-size: 0.8rem;
  font-weight: 700;
  margin-top: 0.4rem;
}

.issues-list {
  font-size: 0.825rem;
  margin-top: 0.25rem;
  padding-left: 1.25rem;
}

/* Detail Grid */
.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.section-heading {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin-bottom: 1rem;
}

.panel-header .section-heading {
  margin-bottom: 0.25rem;
}

.section-sub {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
}

.data-rows {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.data-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border);
}

.data-lbl {
  color: var(--color-text-secondary);
  font-size: 0.85rem;
}

.data-val {
  font-size: 0.9rem;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.empty-docs {
  padding: 1.75rem 1.5rem;
  background: var(--color-surface-2);
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  text-align: center;
  color: var(--color-text-secondary);
}

.kyc-info-box {
  padding: 1rem;
  border-radius: var(--radius-md);
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
}

.kyc-verified-box { color: #166534; }
.kyc-rejected-box { color: #991B1B; }

.kyc-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

/* Docs Grid */
.docs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.doc-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1rem;
  border-radius: var(--radius-md);
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  position: relative;
}

.doc-icon-wrap {
  font-size: 1.75rem;
}

.doc-details {
  flex: 1;
}

.doc-title {
  font-weight: 700;
  font-size: 0.85rem;
}

.doc-del-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--color-text-secondary);
  font-size: 1rem;
}

.incidents-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.incident-item {
  padding: 1rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-2);
}

.incident-item.sev-high {
  border-left: 4px solid #DC2626;
}

.incident-header {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.incident-notes {
  font-size: 0.875rem;
  line-height: 1.4;
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
  padding: 1rem;
}

.modal-content {
  width: 100%;
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow-modal);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
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
  gap: 0.85rem;
}

.col-span-2 { grid-column: span 2; }

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.bg-error {
  background: #DC2626 !important;
  border-color: #DC2626 !important;
}

.loading-state {
  text-align: center;
  padding: 3rem 1.5rem;
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

.badge-sm {
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
}

@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
