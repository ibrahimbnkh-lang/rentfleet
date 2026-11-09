<template>
  <div class="contract-detail-view">
    <!-- Screen Actions Toolbar (Hidden when printing) -->
    <div class="no-print toolbar-header">
      <div class="toolbar-left">
        <RouterLink to="/contracts" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M10 12l-4-4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Retour aux contrats
        </RouterLink>

        <span class="toolbar-contract-num font-mono">
          {{ contract?.contract_number }}
        </span>

        <span v-if="contract" class="status-badge" :class="'status-' + contract.status">
          {{ getStatusLabel(contract.status) }}
        </span>
      </div>

      <div class="toolbar-actions">
        <button
          v-if="contract?.status === 'active'"
          class="btn btn-outline-primary"
          @click="openCheckoutModal"
        >
          🏁 Enregistrer la Restitution
        </button>

        <button
          v-if="contract?.status !== 'draft'"
          class="btn btn-secondary"
          @click="generateOrGoToInvoice"
          :disabled="generatingInvoice"
        >
          📄 {{ generatingInvoice ? 'Génération…' : 'Facturer ce contrat' }}
        </button>

        <button class="btn btn-primary" @click="printContract">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M4 6V2h8v4M4 12H2.5A1.5 1.5 0 011 10.5v-3A1.5 1.5 0 012.5 6h11A1.5 1.5 0 0115 7.5v3a1.5 1.5 0 01-1.5 1.5H12" stroke="currentColor" stroke-width="1.5"/>
            <rect x="4" y="9" width="8" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          Imprimer / Exporter PDF
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state card">
      <div class="spinner"></div>
      <p>Chargement du contrat de location…</p>
    </div>

    <!-- Official Printable Legal Contract Document -->
    <div v-else-if="contract" class="contract-document" id="printable-contract">
      <!-- Legal Header -->
      <div class="doc-header">
        <div class="doc-brand">
          <div class="logo-box">
            <AppLogo :size="32" color="#1e3a8a" />
          </div>
          <div>
            <h1 class="company-title">RentFleet Algérie SARL</h1>
            <p class="company-sub">Location de Véhicules Sans Chauffeur</p>
            <div class="company-legal-info">
              <span>RC : 16/00-1234567B21</span>
              <span>•</span>
              <span>NIF : 002116012345678</span>
              <span>•</span>
              <span>Siège : Bab Ezzouar, Alger</span>
            </div>
          </div>
        </div>

        <div class="doc-contract-info">
          <div class="doc-title-badge">CONTRAT DE LOCATION</div>
          <div class="doc-number font-mono">{{ contract.contract_number }}</div>
          <div class="doc-date">
            Établi le {{ formatDateTime(contract.created_at) }}
          </div>
          <div class="doc-branch font-medium">
            Agence : {{ contract.branch?.name }}
          </div>
        </div>
      </div>

      <!-- Main Contract Grid -->
      <div class="doc-grid">
        <!-- Box 1: Le Locataire -->
        <div class="doc-box">
          <div class="box-header">1. LOCATAIRE (CONDUCTEUR PRINCIPAL)</div>
          <div class="box-content">
            <div class="field-row">
              <span class="f-label">Nom & Prénom / Société :</span>
              <span class="f-val font-semibold">{{ contract.customer?.full_name }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Qualité :</span>
              <span class="f-val">{{ contract.customer?.type === 'company' ? 'Personne Morale (Entreprise)' : 'Particulier' }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">N° Identifiant National (NIN) :</span>
              <span class="f-val font-mono">{{ contract.customer?.national_id_number || 'Non renseigné' }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">N° Permis de Conduire :</span>
              <span class="f-val font-mono">{{ contract.customer?.driver_license_number || 'Non renseigné' }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Délivré le / Wilaya :</span>
              <span class="f-val">
                {{ contract.customer?.driver_license_issued_at ? formatDate(contract.customer.driver_license_issued_at) : '—' }}
                (W.{{ contract.customer?.wilaya_code || '16' }})
              </span>
            </div>
            <div class="field-row">
              <span class="f-label">Téléphone :</span>
              <span class="f-val font-mono">{{ contract.customer?.phone }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Adresse de résidence :</span>
              <span class="f-val">{{ contract.customer?.address || '—' }}, {{ contract.customer?.city || 'Alger' }}</span>
            </div>
          </div>
        </div>

        <!-- Box 2: Le Véhicule & État au Départ -->
        <div class="doc-box">
          <div class="box-header">2. VÉHICULE & ÉTAT DES LIEUX AU DÉPART</div>
          <div class="box-content">
            <div class="field-row">
              <span class="f-label">Marque & Modèle :</span>
              <span class="f-val font-semibold">{{ contract.vehicle?.brand }} {{ contract.vehicle?.model }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Immatriculation (DZ) :</span>
              <span class="f-val font-mono matricule-box">{{ contract.vehicle?.registration_plate }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">N° de Châssis (VIN) :</span>
              <span class="f-val font-mono">{{ contract.vehicle?.vin || '—' }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Catégorie / Énergie :</span>
              <span class="f-val">{{ contract.vehicle?.category?.name }} • {{ contract.vehicle?.fuel_type }}</span>
            </div>
            <div class="field-row highlight-row">
              <span class="f-label">Compteur au départ :</span>
              <span class="f-val font-mono font-bold">{{ contract.start_mileage }} KM</span>
            </div>
            <div class="field-row">
              <span class="f-label">Niveau Carburant départ :</span>
              <span class="f-val font-semibold">{{ contract.start_fuel_level }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Équipements de bord :</span>
              <span class="f-val text-sm">Roue de secours, cric, triangle, gilet de sécurité</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Box 3: Période et Modalités Financières -->
      <div class="doc-box mt-3">
        <div class="box-header">3. PÉRIODE DE LOCATION & CONDITIONS TARIFAIRES (DZD)</div>
        <div class="box-content-multi">
          <div class="col-half">
            <div class="field-row">
              <span class="f-label">Date & Heure de prise en charge :</span>
              <span class="f-val font-semibold">{{ formatDateTime(contract.start_datetime) }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Date & Heure de retour prévue :</span>
              <span class="f-val font-semibold">{{ formatDateTime(contract.end_datetime) }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Agence de restitution convenue :</span>
              <span class="f-val">{{ contract.return_branch?.name || contract.branch?.name }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Durée totale convenue :</span>
              <span class="f-val font-bold">{{ contract.days_count }} jour(s)</span>
            </div>
          </div>

          <div class="col-half bg-fin-box">
            <div class="fin-item">
              <span>Tarif Journalier appliqué :</span>
              <span class="font-mono">{{ formatDzd(contract.daily_rate_dzd) }} / jour</span>
            </div>
            <div class="fin-item total-item">
              <span class="font-bold">Montant Total Location :</span>
              <span class="font-mono font-bold total-val">{{ formatDzd(contract.total_amount_dzd) }}</span>
            </div>
            <div class="fin-item caution-item">
              <span>Dépôt de Garantie (Caution) :</span>
              <span class="font-mono font-semibold">{{ formatDzd(contract.deposit_amount_dzd) }}</span>
            </div>
            <div class="fin-item sub-caution">
              <span class="text-xs">Modalité : {{ formatDepositType(contract.deposit_type) }} — Réf : {{ contract.deposit_reference || 'Enregistrée' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Box 4: Check-out / Restitution Section if Completed -->
      <div v-if="contract.status === 'completed'" class="doc-box mt-3 check-out-box">
        <div class="box-header bg-green-header">4. PROCÈS-VERBAL DE RESTITUTION (RETOUR VÉHICULE)</div>
        <div class="box-content-multi">
          <div class="col-half">
            <div class="field-row">
              <span class="f-label">Restitution effective le :</span>
              <span class="f-val font-semibold">{{ formatDateTime(contract.actual_return_datetime) }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Compteur kilométrique au retour :</span>
              <span class="f-val font-mono font-bold">{{ contract.end_mileage }} KM</span>
            </div>
            <div class="field-row">
              <span class="f-label">Distance totale parcourue :</span>
              <span class="f-val font-mono text-purple font-bold">{{ contract.driven_mileage }} KM</span>
            </div>
            <div class="field-row">
              <span class="f-label">Niveau carburant retour :</span>
              <span class="f-val">{{ contract.end_fuel_level }}</span>
            </div>
          </div>

          <div class="col-half">
            <div class="field-row">
              <span class="f-label">Frais supplémentaires :</span>
              <span class="f-val font-mono">{{ formatDzd((contract.fuel_fee_dzd || 0) + (contract.extra_mileage_fee_dzd || 0) + (contract.damages_fee_dzd || 0)) }}</span>
            </div>
            <div class="field-row">
              <span class="f-label">Observations de retour :</span>
              <span class="f-val text-sm">{{ contract.end_notes || 'Restitution conforme, véhicule inspecté en présence du client.' }}</span>
            </div>
            <div class="field-row highlight-row">
              <span class="f-label">Sort de la caution :</span>
              <span class="f-val text-success font-semibold">Caution restituée au client</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Box 5: Clauses & Conditions Légales Algérie -->
      <div class="doc-box mt-3 legal-clauses-box">
        <div class="box-header">5. CONDITIONS GÉNÉRALES & CLAUSES JURIDIQUES APPLICABLES EN ALGÉRIE</div>
        <div class="clauses-content">
          <p><strong>Art. 1 — Zone de circulation :</strong> Le véhicule loué est exclusivement réservé à la circulation sur l'ensemble du territoire national algérien (58 wilayas). Toute tentative de sortie du territoire douanier algérien est strictement interdite et passible de poursuites pénales.</p>
          <p><strong>Art. 2 — Usage et Conduite :</strong> Le locataire est le seul conducteur autorisé désigné au présent contrat. La sous-location, le prêt à un tiers ou l'utilisation à des fins de transport rémunéré clandestin sont formellement prohibés.</p>
          <p><strong>Art. 3 — Responsabilité et Code de la Route :</strong> Le locataire assume la responsabilité pénale et pécuniaire de toutes infractions au code de la route algérien constatées durant la période contractuelle (radars, verbalisations, stationnement).</p>
          <p><strong>Art. 4 — Restitution et Prolongation :</strong> Le locataire s'engage à restituer le véhicule à l'agence désignée aux date et heure convenues. Tout retard non autorisé supérieur à 2 heures donnera lieu à une facturation complémentaire et pourra faire l'objet d'un signalement aux autorités pour détournement.</p>
        </div>
      </div>

      <!-- Box 6: E-Signatures & Certification -->
      <div class="signatures-container mt-3">
        <!-- Agent Sign -->
        <div class="sig-box">
          <div class="sig-title">Pour l'Agence RentFleet Algérie</div>
          <div class="sig-sub">Le Responsable d'Agence (Visa & Cachet)</div>
          <div class="sig-image-area">
            <div v-if="contract.agent_signature" class="sig-render" v-html="renderSignature(contract.agent_signature)"></div>
            <div class="agency-stamp">
              <span>★ RENTFLEET ALGÉRIE ★</span>
              <span>AGENCE {{ contract.branch?.name }}</span>
              <span>AGRÉMENT MINISTÈRE DES TRANSPORTS</span>
            </div>
          </div>
          <div class="sig-meta">
            <span>Agent : {{ contract.agent?.name || 'Administrateur' }}</span>
          </div>
        </div>

        <!-- Client E-Signature -->
        <div class="sig-box">
          <div class="sig-title">Le Locataire (Conducteur)</div>
          <div class="sig-sub">Mention manuscrite « Lu et approuvé, bon pour accord »</div>
          <div class="sig-image-area">
            <div v-if="contract.client_signature" class="sig-render" v-html="renderSignature(contract.client_signature)"></div>
            <div v-else class="sig-placeholder text-muted">
              (Signature en attente)
            </div>
          </div>
          <div class="sig-meta">
            <span>Signé par : <strong>{{ contract.customer?.full_name }}</strong></span>
            <span v-if="contract.signed_at" class="sig-cert font-mono">
              Horodatage : {{ formatDateTime(contract.signed_at) }} • IP : {{ contract.signer_ip || '197.200.x.x' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Footer Print watermark -->
      <div class="doc-footer">
        <span>RentFleet Algérie — Document contractuel officiel certifié conforme aux dispositions du décret exécutif N° 04-415 fixant les conditions d'exploitation des agences de location de véhicules.</span>
      </div>
    </div>

    <!-- ─── Modal Check-out ──────────────────────────────────────────────── -->
    <div v-if="showCheckout" class="modal-backdrop no-print" @click.self="showCheckout = false">
      <div class="modal modal-md">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Restitution du Véhicule</h2>
            <p class="modal-subtitle">Contrat N° {{ contract?.contract_number }}</p>
          </div>
          <button class="modal-close" @click="showCheckout = false">&times;</button>
        </div>

        <form @submit.prevent="submitCheckout">
          <div class="modal-body">
            <div class="form-grid">
              <div class="form-group">
                <label class="label">Kilométrage de retour (km) *</label>
                <input
                  v-model.number="checkoutForm.end_mileage"
                  type="number"
                  class="input font-mono"
                  :min="contract?.start_mileage"
                  required
                />
              </div>

              <div class="form-group">
                <label class="label">Niveau carburant retour *</label>
                <select v-model="checkoutForm.end_fuel_level" class="input" required>
                  <option value="100%">100% (Plein)</option>
                  <option value="75%">75% (3/4)</option>
                  <option value="50%">50% (1/2)</option>
                  <option value="25%">25% (1/4)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Frais carburant manquant (DZD)</label>
                <input v-model.number="checkoutForm.fuel_fee_dzd" type="number" class="input font-mono" />
              </div>

              <div class="form-group">
                <label class="label">Frais dégradations (DZD)</label>
                <input v-model.number="checkoutForm.damages_fee_dzd" type="number" class="input font-mono" />
              </div>

              <div class="form-group col-span-2">
                <label class="label">Observations lors de la restitution</label>
                <textarea v-model="checkoutForm.end_notes" rows="2" class="input" placeholder="État du véhicule, propreté..."></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCheckout = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submittingCheckout">
              {{ submittingCheckout ? 'Enregistrement...' : 'Valider la restitution' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import AppLogo from '@/components/AppLogo.vue'

const route = useRoute()
const router = useRouter()
const contract = ref<any>(null)
const loading = ref(true)
const showCheckout = ref(false)
const submittingCheckout = ref(false)
const generatingInvoice = ref(false)

const checkoutForm = ref({
  end_mileage: 0,
  end_fuel_level: '100%',
  fuel_fee_dzd: 0,
  damages_fee_dzd: 0,
  end_notes: '',
})

const fetchContractDetail = async () => {
  loading.value = true
  try {
    const res = await api.get(`/contracts/${route.params.id}`)
    contract.value = res.data.data
  } catch (err) {
    console.error('Erreur chargement contrat:', err)
  } finally {
    loading.value = false
  }
}

const printContract = () => {
  window.print()
}

const openCheckoutModal = () => {
  if (!contract.value) return
  checkoutForm.value.end_mileage = (contract.value.start_mileage || 0) + 150
  showCheckout.value = true
}

const generateOrGoToInvoice = async () => {
  if (!contract.value) return
  generatingInvoice.value = true
  try {
    const res = await api.post(`/invoicing/invoices/from-contract/${contract.value.id}`)
    const invoice = res.data.data
    if (invoice && invoice.id) {
      router.push(`/invoices/${invoice.id}`)
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la génération de la facture')
  } finally {
    generatingInvoice.value = false
  }
}

const submitCheckout = async () => {
  if (!contract.value) return
  submittingCheckout.value = true
  try {
    await api.post(`/contracts/${contract.value.id}/checkout`, checkoutForm.value)
    showCheckout.value = false
    await fetchContractDetail()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la restitution')
  } finally {
    submittingCheckout.value = false
  }
}

const renderSignature = (sig: string) => {
  if (!sig) return ''
  if (sig.startsWith('data:image/svg')) {
    return decodeURIComponent(sig.replace('data:image/svg+xml;utf8,', ''))
  }
  return `<img src="${sig}" alt="Signature" style="max-height: 60px; max-width: 180px;" />`
}

const formatDzd = (val: number | string | undefined) => {
  const num = Number(val || 0)
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    maximumFractionDigits: 0,
  }).format(num).replace('DZD', 'DA')
}

const formatDate = (iso: string) => {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatDateTime = (iso: string) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatDepositType = (t: string) => {
  switch (t) {
    case 'cheque': return 'Chèque bancaire de garantie'
    case 'cash': return 'Caution en Espèces'
    case 'card': return 'Empreinte CB'
    case 'bank_transfer': return 'Virement bancaire'
    default: return t || 'Chèque de garantie'
  }
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'active': return 'Actif (En cours)'
    case 'completed': return 'Clôturé (Restitué)'
    case 'draft': return 'Brouillon'
    case 'cancelled': return 'Annulé'
    default: return st
  }
}

onMounted(() => {
  fetchContractDetail()
})
</script>

<style scoped>
.contract-detail-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Toolbar Header */
.toolbar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  padding: 1rem 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.toolbar-contract-num {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e3a8a;
}

.toolbar-actions {
  display: flex;
  gap: 0.75rem;
}

/* Printable Document */
.contract-document {
  background: #ffffff;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  padding: 2.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
  color: #0f172a;
  font-family: inherit;
}

.doc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 2px solid #0f172a;
  padding-bottom: 1.25rem;
}

.doc-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-box {
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 0.5rem;
}

.company-title {
  font-size: 1.375rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.company-sub {
  font-size: 0.8125rem;
  color: #475569;
}

.company-legal-info {
  display: flex;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.doc-contract-info {
  text-align: right;
}

.doc-title-badge {
  display: inline-block;
  background: #1e3a8a;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  padding: 0.2rem 0.6rem;
  border-radius: 0.25rem;
}

.doc-number {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin-top: 0.25rem;
}

.doc-date, .doc-branch {
  font-size: 0.75rem;
  color: #64748b;
}

/* Document Grid Boxes */
.doc-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-top: 1.25rem;
}

.doc-box {
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  overflow: hidden;
  background: #ffffff;
}

.box-header {
  background: #f1f5f9;
  padding: 0.4rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: 0.03em;
  border-bottom: 1px solid #cbd5e1;
}

.bg-green-header {
  background: #dcfce7 !important;
  color: #166534 !important;
}

.box-content {
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.8125rem;
}

.box-content-multi {
  padding: 0.75rem;
  display: flex;
  gap: 1.5rem;
  font-size: 0.8125rem;
}

.col-half {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.f-label {
  color: #64748b;
  font-size: 0.8125rem;
}

.f-val {
  color: #0f172a;
  text-align: right;
}

.matricule-box {
  padding: 0.15rem 0.45rem;
  background: #f8fafc;
  border: 1px solid #94a3b8;
  border-radius: 0.25rem;
  font-weight: 700;
}

.highlight-row {
  background: #f8fafc;
  padding: 0.25rem 0.4rem;
  border-radius: 0.25rem;
}

/* Financial Box */
.bg-fin-box {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 0.375rem;
  padding: 0.75rem;
}

.fin-item {
  display: flex;
  justify-content: space-between;
  padding: 0.25rem 0;
  font-size: 0.8125rem;
}

.total-item {
  border-top: 1px solid #cbd5e1;
  border-bottom: 1px solid #cbd5e1;
  margin: 0.25rem 0;
  padding: 0.4rem 0;
}

.total-val {
  font-size: 1rem;
  color: #1e3a8a;
}

.caution-item {
  margin-top: 0.25rem;
  color: #334155;
}

.sub-caution {
  color: #64748b;
  font-style: italic;
}

/* Clauses */
.legal-clauses-box .clauses-content {
  padding: 0.75rem 1rem;
  font-size: 0.6875rem;
  color: #475569;
  line-height: 1.45;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

/* Signatures */
.signatures-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
  margin-top: 1rem;
}

.sig-box {
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  padding: 0.875rem;
  display: flex;
  flex-direction: column;
}

.sig-title {
  font-weight: 700;
  font-size: 0.8125rem;
  color: #0f172a;
}

.sig-sub {
  font-size: 0.6875rem;
  color: #64748b;
  font-style: italic;
  margin-top: 0.125rem;
}

.sig-image-area {
  height: 90px;
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin: 0.5rem 0;
  border: 1px dashed #e2e8f0;
  border-radius: 0.25rem;
  background: #fbfbfe;
  position: relative;
}

.agency-stamp {
  border: 2px solid #dc2626;
  border-radius: 50%;
  width: 78px;
  height: 78px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #dc2626;
  font-size: 0.45rem;
  font-weight: 800;
  line-height: 1.1;
  transform: rotate(-10deg);
  opacity: 0.85;
}

.sig-render {
  display: flex;
  align-items: center;
  justify-content: center;
}

.sig-meta {
  font-size: 0.6875rem;
  color: #64748b;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.sig-cert {
  color: #15803d;
  font-weight: 600;
}

.doc-footer {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.625rem;
  color: #94a3b8;
  border-top: 1px solid #e2e8f0;
  padding-top: 0.5rem;
}

/* Print Styles */
@media print {
  body {
    background: #ffffff !important;
  }

  .no-print {
    display: none !important;
  }

  .admin-shell, .sidebar, .topbar {
    display: none !important;
  }

  .contract-document {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-width: 100% !important;
    margin: 0 !important;
  }
}
</style>

