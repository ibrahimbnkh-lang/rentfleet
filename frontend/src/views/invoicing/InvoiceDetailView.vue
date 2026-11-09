<template>
  <div class="invoice-detail-view">
    <!-- Screen Actions Toolbar (Hidden when printing) -->
    <div class="no-print toolbar-header">
      <div class="toolbar-left">
        <RouterLink to="/invoices" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M10 12l-4-4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Retour aux factures
        </RouterLink>

        <span class="toolbar-inv-num font-mono">
          {{ invoice?.invoice_number }}
        </span>

        <span v-if="invoice" class="status-badge" :class="'status-' + invoice.status">
          {{ getStatusLabel(invoice.status) }}
        </span>
      </div>

      <div class="toolbar-actions">
        <button
          v-if="invoice && invoice.remaining_amount_dzd > 0 && invoice.status !== 'cancelled'"
          class="btn btn-outline-success"
          @click="showPaymentModal = true"
        >
          💳 Enregistrer un Paiement
        </button>

        <button class="btn btn-primary" @click="printInvoice">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M4 6V2h8v4M4 12H2.5A1.5 1.5 0 011 10.5v-3A1.5 1.5 0 012.5 6h11A1.5 1.5 0 0115 7.5v3a1.5 1.5 0 01-1.5 1.5H12" stroke="currentColor" stroke-width="1.5"/>
            <rect x="4" y="9" width="8" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          Imprimer la Facture (PDF)
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state card">
      <div class="spinner"></div>
      <p>Chargement de la facture…</p>
    </div>

    <!-- Official Printable Algerian Fiscal Invoice -->
    <div v-else-if="invoice" class="invoice-document" id="printable-invoice">
      <!-- Legal Header -->
      <div class="doc-header">
        <div class="doc-brand">
          <div class="logo-box">
            <AppLogo :size="36" color="#1e40af" />
          </div>
          <div>
            <h1 class="company-title">RentFleet Algérie SARL</h1>
            <p class="company-sub">Entreprise de Location de Véhicules de Tourisme & Utilitaires</p>
            <div class="company-tax-grid">
              <span><strong>RC :</strong> 16/00-1234567B21</span>
              <span><strong>NIF :</strong> 002116012345678</span>
              <span><strong>NIS :</strong> 002116010012345</span>
              <span><strong>AI :</strong> 16012345678</span>
              <span><strong>Cap. Social :</strong> 10 000 000 DA</span>
              <span><strong>Siège :</strong> Bab Ezzouar, Alger</span>
            </div>
          </div>
        </div>

        <div class="doc-invoice-card">
          <div class="doc-type-badge">FACTURE COMMERCIALE</div>
          <div class="doc-number font-mono">{{ invoice.invoice_number }}</div>
          <div class="doc-date">Date : {{ formatDate(invoice.issue_date) }}</div>
          <div class="doc-date">Échéance : {{ formatDate(invoice.due_date) }}</div>
          <div v-if="invoice.contract" class="doc-contract font-mono">
            Réf. Contrat : {{ invoice.contract.contract_number }}
          </div>
        </div>
      </div>

      <!-- Parties Block -->
      <div class="parties-grid mt-4">
        <!-- Émetteur -->
        <div class="party-box">
          <div class="party-title">ÉMETTEUR / AGENCE</div>
          <div class="party-body">
            <strong>{{ invoice.branch?.name || 'RentFleet Algérie' }}</strong>
            <div>Adresse : {{ invoice.branch?.address || 'Alger Centre, Alger' }}</div>
            <div>Tél : {{ invoice.branch?.phone || '+213 21 00 00 00' }}</div>
            <div>Email : {{ invoice.branch?.email || 'contact@rentfleet.dz' }}</div>
          </div>
        </div>

        <!-- Client (Doit) -->
        <div class="party-box client-box">
          <div class="party-title">CLIENT / DOIT</div>
          <div class="party-body">
            <strong class="client-title">{{ invoice.customer_name }}</strong>
            <div v-if="invoice.customer_nif"><strong>NIF :</strong> <span class="font-mono">{{ invoice.customer_nif }}</span></div>
            <div v-if="invoice.customer_rc"><strong>RC :</strong> <span class="font-mono">{{ invoice.customer_rc }}</span></div>
            <div><strong>Adresse :</strong> {{ invoice.customer_address || 'Algérie' }}</div>
            <div><strong>Mode de règlement :</strong> {{ formatPaymentMethod(invoice.payment_method) }}</div>
          </div>
        </div>
      </div>

      <!-- Prestations Table -->
      <div class="items-table-container mt-4">
        <table class="doc-table">
          <thead>
            <tr>
              <th style="width: 5%;">N°</th>
              <th style="width: 50%;">Désignation des Prestations / Véhicule</th>
              <th style="width: 12%;" class="text-center">Qté / Jours</th>
              <th style="width: 15%;" class="text-right">Prix Unit. HT (DZD)</th>
              <th style="width: 8%;" class="text-center">TVA</th>
              <th style="width: 15%;" class="text-right">Total HT (DZD)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in invoice.items" :key="item.id">
              <td class="text-center font-mono">{{ Number(idx) + 1 }}</td>
              <td>
                <span class="font-medium">{{ item.description }}</span>
              </td>
              <td class="text-center font-mono">{{ item.quantity }}</td>
              <td class="text-right font-mono">{{ formatDzd(item.unit_price_ht_dzd) }}</td>
              <td class="text-center font-mono">{{ item.vat_rate }}%</td>
              <td class="text-right font-mono font-semibold">{{ formatDzd(item.total_ht_dzd) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Financial Calculation Block -->
      <div class="financial-summary-section mt-4">
        <div class="words-and-notes">
          <div class="words-box">
            <span class="words-title">Arrêtée la présente facture à la somme de :</span>
            <div class="words-text font-bold">
              {{ numberToWordsDzd(invoice.total_ttc_dzd) }}
            </div>
          </div>

          <div v-if="invoice.notes" class="invoice-notes-box mt-3">
            <span class="notes-title">Mentions & Conditions :</span>
            <p>{{ invoice.notes }}</p>
          </div>
        </div>

        <div class="taxes-breakdown-card">
          <div class="calc-row">
            <span>Total Hors Taxes (HT) :</span>
            <span class="font-mono font-semibold">{{ formatDzd(invoice.subtotal_ht_dzd) }}</span>
          </div>

          <div class="calc-row">
            <span>TVA Algérie ({{ invoice.vat_rate }}%) :</span>
            <span class="font-mono font-semibold">{{ formatDzd(invoice.vat_amount_dzd) }}</span>
          </div>

          <div v-if="invoice.stamp_duty_dzd > 0" class="calc-row">
            <span>Droit de Timbre (Espèces) :</span>
            <span class="font-mono font-semibold">{{ formatDzd(invoice.stamp_duty_dzd) }}</span>
          </div>

          <div class="calc-row grand-total-row">
            <span class="font-bold">Total Général TTC :</span>
            <span class="font-mono font-bold grand-total-val">{{ formatDzd(invoice.total_ttc_dzd) }}</span>
          </div>

          <div class="calc-row paid-row text-success">
            <span>Montant Encaissé :</span>
            <span class="font-mono font-semibold">{{ formatDzd(invoice.paid_amount_dzd) }}</span>
          </div>

          <div class="calc-row due-row" :class="invoice.remaining_amount_dzd > 0 ? 'text-danger' : 'text-success'">
            <span class="font-bold">Solde Restant Dû :</span>
            <span class="font-mono font-bold">{{ formatDzd(invoice.remaining_amount_dzd) }}</span>
          </div>
        </div>
      </div>

      <!-- Payments & Receipts Table if payments exist -->
      <div v-if="invoice.payments && invoice.payments.length > 0" class="payments-history-section mt-4">
        <div class="section-sub-title">HISTORIQUE DES ENCAISSEMENTS & QUITTANCES DE RÈGLEMENT</div>
        <table class="payments-sub-table mt-2">
          <thead>
            <tr>
              <th>Réf. Quittance</th>
              <th>Date de règlement</th>
              <th>Mode d'encaissement</th>
              <th>Références (N° Chèque / Virement)</th>
              <th class="text-right">Montant Encaissé (DZD)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pay in invoice.payments" :key="pay.id">
              <td class="font-mono font-bold">{{ pay.payment_reference }}</td>
              <td>{{ formatDateTime(pay.payment_date) }}</td>
              <td>{{ formatPaymentMethod(pay.payment_method) }}</td>
              <td class="font-mono text-muted">{{ pay.reference_details || 'Règlement direct' }}</td>
              <td class="text-right font-mono font-bold text-success">{{ formatDzd(pay.amount_dzd) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Stamp and Signatures -->
      <div class="signatures-footer-section mt-4">
        <div class="sig-note">
          <span>Règlement par chèque bancaire libellé à l'ordre de <strong>« RentFleet Algérie SARL »</strong> ou virement bancaire sur compte <strong>BNA N° 002 00012 1200004819 82</strong>.</span>
        </div>

        <div class="stamp-box">
          <span class="stamp-label">Cachet et Signature de l'Agence</span>
          <div class="stamp-circle">
            <span>★ RENTFLEET ALGÉRIE ★</span>
            <span>SERVICE COMPTABILITÉ & FACTURATION</span>
            <span>ALGER</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── Modal Record Payment ─────────────────────────────────────────── -->
    <div v-if="showPaymentModal" class="modal-backdrop no-print" @click.self="showPaymentModal = false">
      <div class="modal modal-md">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Enregistrer un Règlement</h2>
            <p class="modal-subtitle">Facture N° {{ invoice?.invoice_number }}</p>
          </div>
          <button class="modal-close" @click="showPaymentModal = false">&times;</button>
        </div>

        <form @submit.prevent="submitPayment">
          <div class="modal-body">
            <div class="due-box mb-4">
              <span>Solde restant à payer :</span>
              <strong class="font-mono text-danger">{{ formatDzd(invoice?.remaining_amount_dzd) }}</strong>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="label">Montant perçu (DZD) *</label>
                <input
                  v-model.number="paymentForm.amount_dzd"
                  type="number"
                  step="100"
                  class="input font-mono font-bold"
                  :max="invoice?.remaining_amount_dzd"
                  required
                />
              </div>

              <div class="form-group">
                <label class="label">Mode de règlement *</label>
                <select v-model="paymentForm.payment_method" class="input" required>
                  <option value="cash">Espèces</option>
                  <option value="cheque">Chèque bancaire</option>
                  <option value="bank_transfer">Virement bancaire</option>
                  <option value="cib_edahabia">Carte CIB / Edahabia</option>
                </select>
              </div>

              <div class="form-group col-span-2">
                <label class="label">Références (N° Chèque / Banque / Transaction)</label>
                <input v-model="paymentForm.reference_details" type="text" class="input font-mono" placeholder="ex: BNA Chèque N° 004812" />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showPaymentModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="submittingPayment">
              {{ submittingPayment ? 'Validation...' : 'Valider l\'encaissement' }}
            </button>
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
import AppLogo from '@/components/AppLogo.vue'

const route = useRoute()
const invoice = ref<any>(null)
const loading = ref(true)
const showPaymentModal = ref(false)
const submittingPayment = ref(false)

const paymentForm = ref({
  amount_dzd: 0,
  payment_method: 'cheque',
  reference_details: '',
  notes: '',
})

const fetchInvoiceDetail = async () => {
  loading.value = true
  try {
    const res = await api.get(`/invoicing/invoices/${route.params.id}`)
    invoice.value = res.data.data
    if (invoice.value) {
      paymentForm.value.amount_dzd = Number(invoice.value.remaining_amount_dzd)
    }
  } catch (err) {
    console.error('Erreur chargement facture:', err)
  } finally {
    loading.value = false
  }
}

const printInvoice = () => {
  window.print()
}

const submitPayment = async () => {
  if (!invoice.value) return
  submittingPayment.value = true
  try {
    await api.post(`/invoicing/invoices/${invoice.value.id}/payments`, paymentForm.value)
    showPaymentModal.value = false
    await fetchInvoiceDetail()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur d\'encaissement')
  } finally {
    submittingPayment.value = false
  }
}

// ─── Number in words helper (French/Algerian Dinar) ──────────────────────────
const numberToWordsDzd = (num: number): string => {
  if (!num || num === 0) return 'Zéro Dinar Algérien'

  const units = ['', 'Un', 'Deux', 'Trois', 'Quatre', 'Cinq', 'Six', 'Sept', 'Huit', 'Neuf', 'Dix', 'Onze', 'Douze', 'Treize', 'Quatorze', 'Quinze', 'Seize', 'Dix-sept', 'Dix-huit', 'Dix-neuf']
  const tens = ['', '', 'Vingt', 'Trente', 'Quarante', 'Cinquante', 'Soixante', 'Soixante-dix', 'Quatre-vingts', 'Quatre-vingt-dix']

  const convertGroup = (n: number): string => {
    let res = ''
    if (n >= 100) {
      const h = Math.floor(n / 100)
      res += (h > 1 ? units[h] + ' ' : '') + 'Cent '
      n %= 100
    }
    if (n >= 20) {
      const t = Math.floor(n / 10)
      const u = n % 10
      res += tens[t] + (u > 0 ? '-' + units[u] : '') + ' '
    } else if (n > 0) {
      res += units[n] + ' '
    }
    return res.trim()
  }

  const rounded = Math.floor(num)
  const cents = Math.round((num - rounded) * 100)

  let words = ''
  if (rounded >= 1000000) {
    const millions = Math.floor(rounded / 1000000)
    words += convertGroup(millions) + ' Million' + (millions > 1 ? 's ' : ' ')
  }
  const remThousands = rounded % 1000000
  if (remThousands >= 1000) {
    const thousands = Math.floor(remThousands / 1000)
    words += (thousands > 1 ? convertGroup(thousands) + ' ' : '') + 'Mille '
  }
  const unitsPart = remThousands % 1000
  if (unitsPart > 0 || words === '') {
    words += convertGroup(unitsPart)
  }

  words = words.trim() + ' Dinars Algériens'
  if (cents > 0) {
    words += ' et ' + convertGroup(cents) + ' Centimes'
  }

  return words
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

const formatPaymentMethod = (m: string) => {
  switch (m) {
    case 'cash': return 'Espèces'
    case 'cheque': return 'Chèque bancaire'
    case 'bank_transfer': return 'Virement bancaire'
    case 'cib_edahabia': return 'Carte CIB'
    default: return m || 'Paiement'
  }
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'paid': return 'Payée (Soldée)'
    case 'partially_paid': return 'Partiellement payée'
    case 'issued': return 'En attente'
    case 'cancelled': return 'Annulée'
    default: return st
  }
}

onMounted(() => {
  fetchInvoiceDetail()
})
</script>

<style scoped>
.invoice-detail-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

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

.toolbar-inv-num {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1e40af;
}

.toolbar-actions {
  display: flex;
  gap: 0.75rem;
}

/* Printable Invoice Document */
.invoice-document {
  background: #ffffff;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  padding: 2.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
  color: #0f172a;
}

.doc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 2px solid #1e40af;
  padding-bottom: 1.25rem;
}

.doc-brand {
  display: flex;
  gap: 1rem;
  max-width: 60%;
}

.logo-box {
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  border-radius: 0.5rem;
  flex-shrink: 0;
}

.company-title {
  font-size: 1.375rem;
  font-weight: 800;
  color: #0f172a;
}

.company-sub {
  font-size: 0.8125rem;
  color: #475569;
}

.company-tax-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 0.85rem;
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.35rem;
}

.doc-invoice-card {
  text-align: right;
}

.doc-type-badge {
  display: inline-block;
  background: #1e40af;
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

.doc-date, .doc-contract {
  font-size: 0.75rem;
  color: #64748b;
}

/* Parties */
.parties-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.party-box {
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  padding: 0.875rem;
}

.client-box {
  background: #f8fafc;
}

.party-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}

.party-body {
  font-size: 0.8125rem;
  line-height: 1.45;
  color: #1e293b;
}

.client-title {
  font-size: 0.95rem;
  color: #0f172a;
}

/* Table */
.items-table-container {
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  overflow: hidden;
}

.doc-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.doc-table th {
  background: #f1f5f9;
  padding: 0.6rem 0.75rem;
  font-weight: 700;
  color: #1e293b;
  border-bottom: 1px solid #cbd5e1;
}

.doc-table td {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

/* Financials */
.financial-summary-section {
  display: flex;
  gap: 1.5rem;
}

.words-and-notes {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.words-box {
  padding: 0.875rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
}

.words-title {
  font-size: 0.75rem;
  color: #64748b;
  font-style: italic;
  display: block;
}

.words-text {
  margin-top: 0.25rem;
  color: #1e40af;
}

.invoice-notes-box {
  padding: 0.75rem;
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  color: #475569;
}

.notes-title {
  font-weight: 700;
  display: block;
  margin-bottom: 0.2rem;
}

.taxes-breakdown-card {
  width: 320px;
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  padding: 0.875rem;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.8125rem;
}

.calc-row {
  display: flex;
  justify-content: space-between;
}

.grand-total-row {
  border-top: 2px solid #cbd5e1;
  border-bottom: 2px solid #cbd5e1;
  padding: 0.4rem 0;
  margin: 0.25rem 0;
  font-size: 0.95rem;
}

.grand-total-val {
  color: #1e40af;
}

.paid-row {
  font-size: 0.8125rem;
}

.due-row {
  border-top: 1px solid #cbd5e1;
  padding-top: 0.35rem;
  margin-top: 0.2rem;
  font-size: 0.9375rem;
}

/* Payments history */
.payments-history-section {
  border: 1px solid #cbd5e1;
  border-radius: 0.375rem;
  padding: 0.75rem;
  background: #ffffff;
}

.section-sub-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: #475569;
  letter-spacing: 0.05em;
}

.payments-sub-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.75rem;
}

.payments-sub-table th {
  background: #f8fafc;
  padding: 0.4rem 0.6rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.payments-sub-table td {
  padding: 0.4rem 0.6rem;
  border-bottom: 1px solid #f1f5f9;
}

/* Signatures footer */
.signatures-footer-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #e2e8f0;
  padding-top: 1rem;
}

.sig-note {
  font-size: 0.6875rem;
  color: #64748b;
  max-width: 55%;
  line-height: 1.4;
}

.stamp-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.stamp-label {
  font-size: 0.6875rem;
  color: #64748b;
  font-style: italic;
}

.stamp-circle {
  border: 2px solid #1e40af;
  border-radius: 50%;
  width: 82px;
  height: 82px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #1e40af;
  font-size: 0.45rem;
  font-weight: 800;
  line-height: 1.1;
  transform: rotate(-8deg);
  opacity: 0.85;
}

.due-box {
  display: flex;
  justify-content: space-between;
  padding: 0.875rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
}

/* Print */
@media print {
  body { background: #ffffff !important; }
  .no-print { display: none !important; }
  .admin-shell, .sidebar, .topbar { display: none !important; }
  .invoice-document {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    max-width: 100% !important;
    margin: 0 !important;
  }
}
</style>
