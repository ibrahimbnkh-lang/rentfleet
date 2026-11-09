<template>
  <div class="payments-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Journal des Règlements & Caisse</h1>
        <p class="page-subtitle">
          Suivi des encaissements en temps réel : espèces (caisse agence), chèques, virements bancaires et CIB / Edahabia
        </p>
      </div>

      <div class="header-actions">
        <RouterLink to="/invoices" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M4 2h8a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V3a1 1 0 011-1z" stroke="currentColor" stroke-width="1.5"/>
            <path d="M6 5h4M6 8h4M6 11h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Voir les Factures
        </RouterLink>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="card kpi-card kpi-total">
        <div class="kpi-icon">💰</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_collected_dzd) }}</div>
          <div class="kpi-label">Total des Encaissements</div>
        </div>
      </div>

      <div class="card kpi-card kpi-cash">
        <div class="kpi-icon">💵</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.cash_total_dzd) }}</div>
          <div class="kpi-label">Espèces (Fond de Caisse)</div>
        </div>
      </div>

      <div class="card kpi-card kpi-cheque">
        <div class="kpi-icon">📝</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.cheque_total_dzd) }}</div>
          <div class="kpi-label">Chèques encaissés</div>
        </div>
      </div>

      <div class="card kpi-card kpi-bank">
        <div class="kpi-icon">💳</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.transfer_total_dzd + metrics.cib_total_dzd) }}</div>
          <div class="kpi-label">Virements & Carte CIB / Edahabia</div>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par Réf reçu (PAY-...), N° Facture, Client, Détails..."
            class="input"
            @input="debounceFetch"
          />
        </div>

        <div class="filter-group">
          <select v-model="filters.payment_method" class="input" @change="() => fetchPayments(1)">
            <option value="">Tous les modes</option>
            <option value="cash">Espèces (Cash)</option>
            <option value="cheque">Chèque bancaire</option>
            <option value="bank_transfer">Virement bancaire</option>
            <option value="cib_edahabia">Carte CIB / Edahabia</option>
          </select>
        </div>

        <div class="filter-group date-group">
          <label class="filter-label">Du :</label>
          <input
            v-model="filters.from_date"
            type="date"
            class="input input-date"
            @change="() => fetchPayments(1)"
          />
        </div>

        <div class="filter-group date-group">
          <label class="filter-label">Au :</label>
          <input
            v-model="filters.to_date"
            type="date"
            class="input input-date"
            @change="() => fetchPayments(1)"
          />
        </div>

        <div class="filter-group">
          <select v-model="filters.branch_id" class="input" @change="() => fetchPayments(1)">
            <option value="">Toutes les agences</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
        </div>

        <button class="btn btn-secondary btn-icon" title="Réinitialiser filtres" @click="resetFilters">
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
            <path d="M2 2v5h5M14 14v-5h-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M13.5 6.5A6 6 0 003.5 4.5L2 7m12 2l-1.5 2.5a6 6 0 01-10-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card table-card">
      <div v-if="loading" class="state-container">
        <div class="spinner"></div>
        <p>Chargement du journal des encaissements…</p>
      </div>

      <div v-else-if="payments.length === 0" class="state-container empty-state">
        <div class="empty-icon">💸</div>
        <p class="font-medium text-lg">Aucun règlement trouvé</p>
        <p class="text-secondary text-sm">Les encaissements enregistrés sur les factures s'afficheront automatiquement ici.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th style="min-width: 150px;">Réf. Reçu</th>
              <th style="min-width: 130px;">Date & Heure</th>
              <th style="min-width: 140px;">N° Facture</th>
              <th style="min-width: 200px;">Client / Payeur</th>
              <th style="min-width: 150px;">Mode de paiement</th>
              <th style="min-width: 180px;">Réf. Transaction / Détails</th>
              <th style="min-width: 150px;" class="text-right">Montant Encaissé</th>
              <th style="min-width: 140px;">Caissier / Agent</th>
              <th style="min-width: 110px;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payments" :key="p.id">
              <!-- Reçu Ref -->
              <td>
                <span class="ref-badge font-mono">{{ p.payment_reference }}</span>
              </td>

              <!-- Date -->
              <td class="text-sm">
                <div>{{ formatDate(p.payment_date) }}</div>
                <div class="text-muted text-xs">{{ formatTime(p.payment_date || p.created_at) }}</div>
              </td>

              <!-- Invoice -->
              <td>
                <RouterLink
                  v-if="p.invoice"
                  :to="`/invoices/${p.invoice.id}`"
                  class="font-mono text-primary font-semibold hover:underline"
                >
                  {{ p.invoice.invoice_number }}
                </RouterLink>
                <span v-else class="text-muted text-xs">—</span>
              </td>

              <!-- Customer -->
              <td>
                <div class="font-semibold text-sm">
                  {{ p.customer?.full_name || p.invoice?.customer_name || 'Client' }}
                </div>
                <div class="text-muted text-xs font-mono">
                  {{ p.customer?.phone || p.customer?.email || '—' }}
                </div>
              </td>

              <!-- Method -->
              <td>
                <span class="method-tag" :class="'method-' + p.payment_method">
                  <span class="method-symbol">{{ getMethodSymbol(p.payment_method) }}</span>
                  {{ getMethodLabel(p.payment_method) }}
                </span>
              </td>

              <!-- Reference Details -->
              <td class="text-sm text-secondary">
                <span v-if="p.reference_details" class="font-mono">{{ p.reference_details }}</span>
                <span v-else class="text-muted italic">Sans référence</span>
              </td>

              <!-- Amount -->
              <td class="text-right">
                <span class="amount-pill font-mono font-bold text-success">
                  + {{ formatDzd(p.amount_dzd) }}
                </span>
              </td>

              <!-- Receiver -->
              <td class="text-sm">
                <div class="flex items-center gap-1.5">
                  <span class="user-avatar-tiny">{{ getInitials(p.receiver?.name || 'Agent') }}</span>
                  <span>{{ p.receiver?.name || 'Système' }}</span>
                </div>
                <div class="text-muted text-xs">{{ p.branch?.name }}</div>
              </td>

              <!-- Action -->
              <td class="text-center">
                <button
                  class="btn btn-sm btn-secondary"
                  title="Voir et imprimer le reçu officiel"
                  @click="openReceiptModal(p)"
                >
                  🧾 Reçu
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="pagination.total > 0" class="pagination-footer">
        <span class="text-sm text-secondary">
          Affichage de {{ payments.length }} sur {{ pagination.total }} règlements
        </span>
        <div class="pagination-buttons">
          <button
            class="btn btn-secondary btn-sm"
            :disabled="pagination.current_page <= 1"
            @click="fetchPayments(pagination.current_page - 1)"
          >
            Précédent
          </button>
          <span class="page-indicator text-sm font-semibold">
            Page {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button
            class="btn btn-secondary btn-sm"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchPayments(pagination.current_page + 1)"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Official Printable Cash Receipt Modal -->
    <div v-if="receiptModalOpen && selectedPayment" class="modal-backdrop" @click.self="receiptModalOpen = false">
      <div class="modal-box receipt-modal">
        <div class="modal-header no-print">
          <h2 class="modal-title">Reçu d'Encaissement Officiel</h2>
          <div class="modal-actions">
            <button class="btn btn-primary btn-sm" @click="printReceipt">
              🖨️ Imprimer le Reçu
            </button>
            <button class="modal-close" @click="receiptModalOpen = false">✕</button>
          </div>
        </div>

        <!-- Receipt Document Body (Printable) -->
        <div class="receipt-document" id="printable-receipt">
          <div class="receipt-header">
            <div class="receipt-agency">
              <h3 class="receipt-company">RentFleet Algérie SARL</h3>
              <p class="receipt-sub">Location de Véhicules Touristiques & Utilitaires</p>
              <div class="receipt-tax-ids">
                <span>RC : 16/00-1234567B21</span> • 
                <span>NIF : 002116012345678</span> • 
                <span>AI : 16012345678</span>
              </div>
              <p class="receipt-branch">Agence : {{ selectedPayment.branch?.name || 'Alger Centre' }}</p>
            </div>
            <div class="receipt-meta">
              <div class="receipt-badge">REÇU DE CAISSE</div>
              <div class="receipt-ref font-mono">{{ selectedPayment.payment_reference }}</div>
              <div class="receipt-date">Date : {{ formatDateTime(selectedPayment.payment_date || selectedPayment.created_at) }}</div>
            </div>
          </div>

          <div class="receipt-divider"></div>

          <div class="receipt-body">
            <div class="receipt-row">
              <span class="label">Reçu de M. / Société :</span>
              <span class="val font-bold">
                {{ selectedPayment.customer?.full_name || selectedPayment.invoice?.customer_name }}
              </span>
            </div>

            <div class="receipt-row">
              <span class="label">Pour le règlement de la facture :</span>
              <span class="val font-mono font-semibold">
                {{ selectedPayment.invoice?.invoice_number || 'Facture comptant' }}
              </span>
            </div>

            <div class="receipt-row">
              <span class="label">Mode d'encaissement :</span>
              <span class="val font-semibold">
                {{ getMethodLabel(selectedPayment.payment_method) }}
                <span v-if="selectedPayment.reference_details" class="font-mono text-muted">
                  ({{ selectedPayment.reference_details }})
                </span>
              </span>
            </div>

            <div class="receipt-amount-box">
              <div class="amount-title">MONTANT ENCAISSÉ</div>
              <div class="amount-number font-mono">{{ formatDzd(selectedPayment.amount_dzd) }}</div>
              <div class="amount-words">
                Arrêté le présent reçu à la somme de : 
                <strong>{{ convertNumberToFrenchWords(selectedPayment.amount_dzd) }}</strong>
              </div>
            </div>

            <div v-if="selectedPayment.invoice" class="receipt-balance-summary">
              <div>Total facture TTC : {{ formatDzd(selectedPayment.invoice.total_ttc_dzd) }}</div>
              <div>Déjà réglé : {{ formatDzd(selectedPayment.invoice.paid_amount_dzd) }}</div>
              <div class="font-bold" :class="selectedPayment.invoice.remaining_amount_dzd <= 0 ? 'text-success' : 'text-danger'">
                Solde restant à devoir : {{ formatDzd(selectedPayment.invoice.remaining_amount_dzd) }}
              </div>
            </div>

            <div class="receipt-signatures">
              <div class="sig-block">
                <div class="sig-title">Le Client / Payeur</div>
                <div class="sig-hint">« Lu et approuvé »</div>
                <div class="sig-space"></div>
              </div>

              <div class="sig-block">
                <div class="sig-title">Cachet & Signature Caisse</div>
                <div class="sig-agent">Agent : {{ selectedPayment.receiver?.name || 'Caissier' }}</div>
                <div class="sig-space"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api'

interface Branch {
  id: number
  name: string
}

interface PaymentItem {
  id: number
  payment_reference: string
  invoice_id: number
  customer_id: number
  branch_id: number
  amount_dzd: number
  payment_method: string
  payment_date: string
  reference_details: string | null
  notes: string | null
  created_at: string
  invoice?: {
    id: number
    invoice_number: string
    customer_name: string
    total_ttc_dzd: number
    paid_amount_dzd: number
    remaining_amount_dzd: number
  }
  customer?: {
    id: number
    full_name: string
    phone: string
    email: string
  }
  branch?: {
    id: number
    name: string
  }
  receiver?: {
    id: number
    name: string
  }
}

interface Metrics {
  total_collected_dzd: number
  cash_total_dzd: number
  cheque_total_dzd: number
  transfer_total_dzd: number
  cib_total_dzd: number
}

const loading = ref(false)
const payments = ref<PaymentItem[]>([])
const branches = ref<Branch[]>([])

const metrics = ref<Metrics>({
  total_collected_dzd: 0,
  cash_total_dzd: 0,
  cheque_total_dzd: 0,
  transfer_total_dzd: 0,
  cib_total_dzd: 0,
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

const filters = ref({
  search: '',
  payment_method: '',
  from_date: '',
  to_date: '',
  branch_id: '',
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null
function debounceFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchPayments(1)
  }, 300)
}

function resetFilters() {
  filters.value = {
    search: '',
    payment_method: '',
    from_date: '',
    to_date: '',
    branch_id: '',
  }
  fetchPayments(1)
}

async function fetchPayments(page = 1) {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: 20,
    }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.payment_method) params.payment_method = filters.value.payment_method
    if (filters.value.from_date) params.from_date = filters.value.from_date
    if (filters.value.to_date) params.to_date = filters.value.to_date
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id

    const res = await api.get('/invoicing/payments', { params })
    payments.value = res.data.data || []
    if (res.data.meta?.metrics) {
      metrics.value = res.data.meta.metrics
    }
    if (res.data.meta?.pagination) {
      pagination.value = res.data.meta.pagination
    }
  } catch (err) {
    console.error('Failed to fetch payments', err)
  } finally {
    loading.value = false
  }
}

async function fetchBranches() {
  try {
    const res = await api.get('/branches')
    branches.value = res.data.data || []
  } catch (err) {
    console.error('Failed to fetch branches', err)
  }
}

// Receipt Modal
const receiptModalOpen = ref(false)
const selectedPayment = ref<PaymentItem | null>(null)

function openReceiptModal(payment: PaymentItem) {
  selectedPayment.value = payment
  receiptModalOpen.value = true
}

function printReceipt() {
  window.print()
}

// Formatting helpers
function formatDzd(val: number | string | undefined): string {
  const num = typeof val === 'number' ? val : parseFloat(val || '0')
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    maximumFractionDigits: 2,
    minimumFractionDigits: 2,
  }).format(num).replace('DZD', 'DA')
}

function formatDate(d: string | undefined): string {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-DZ', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function formatTime(d: string | undefined): string {
  if (!d) return ''
  return new Date(d).toLocaleTimeString('fr-DZ', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDateTime(d: string | undefined): string {
  if (!d) return '—'
  return `${formatDate(d)} à ${formatTime(d)}`
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(p => p[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

function getMethodLabel(m: string): string {
  const map: Record<string, string> = {
    cash: 'Espèces',
    cheque: 'Chèque',
    bank_transfer: 'Virement bancaire',
    cib_edahabia: 'Carte CIB / Edahabia',
  }
  return map[m] || m
}

function getMethodSymbol(m: string): string {
  const map: Record<string, string> = {
    cash: '💵',
    cheque: '📝',
    bank_transfer: '🏦',
    cib_edahabia: '💳',
  }
  return map[m] || '💰'
}

function convertNumberToFrenchWords(num: number): string {
  if (!num || num === 0) return 'Zéro Dinar Algérien'
  const units = ['', 'Un', 'Deux', 'Trois', 'Quatre', 'Cinq', 'Six', 'Sept', 'Huit', 'Neuf', 'Dix', 'Onze', 'Douze', 'Treize', 'Quatorze', 'Quinze', 'Seize', 'Dix-sept', 'Dix-huit', 'Dix-neuf']
  const tens = ['', '', 'Vingt', 'Trente', 'Quarante', 'Cinquante', 'Soixante', 'Soixante', 'Quatre-vingt', 'Quatre-vingt']

  function convertChunk(n: number): string {
    let chunk = ''
    if (n >= 100) {
      const hundredDigit = Math.floor(n / 100)
      if (hundredDigit === 1) {
        chunk += 'Cent '
      } else {
        chunk += units[hundredDigit] + ' Cent '
      }
      n %= 100
    }
    if (n >= 20) {
      const tenDigit = Math.floor(n / 10)
      const unitDigit = n % 10
      if (tenDigit === 7 || tenDigit === 9) {
        chunk += tens[tenDigit] + '-' + units[10 + unitDigit] + ' '
      } else {
        if (unitDigit === 1 && tenDigit < 8) {
          chunk += tens[tenDigit] + ' et Un '
        } else if (unitDigit > 0) {
          chunk += tens[tenDigit] + '-' + units[unitDigit] + ' '
        } else {
          chunk += tens[tenDigit] + ' '
        }
      }
    } else if (n > 0) {
      chunk += units[n] + ' '
    }
    return chunk
  }

  const integerPart = Math.floor(num)
  const cents = Math.round((num - integerPart) * 100)

  let words = ''
  const millions = Math.floor(integerPart / 1000000)
  const thousands = Math.floor((integerPart % 1000000) / 1000)
  const remainder = integerPart % 1000

  if (millions > 0) {
    words += (millions === 1 ? 'Un Million ' : convertChunk(millions) + 'Millions ')
  }
  if (thousands > 0) {
    words += (thousands === 1 ? 'Mille ' : convertChunk(thousands) + 'Mille ')
  }
  if (remainder > 0) {
    words += convertChunk(remainder)
  }

  words = words.trim() + ' Dinars Algériens'
  if (cents > 0) {
    words += ' et ' + convertChunk(cents).trim() + ' Centimes'
  }
  return words
}

onMounted(() => {
  fetchPayments(1)
  fetchBranches()
})
</script>

<style scoped>
.payments-view {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.page-title {
  font-size: 1.625rem;
  font-weight: 700;
  color: var(--color-text, #0f172a);
  margin: 0;
}

.page-subtitle {
  color: var(--color-text-secondary, #64748b);
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* KPI Summary */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 1rem;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.125rem 1.25rem;
  border-radius: var(--radius-lg, 12px);
  background: white;
  border: 1px solid var(--color-border, #e2e8f0);
}

.kpi-icon {
  font-size: 1.75rem;
  width: 48px;
  height: 48px;
  border-radius: 10px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-total .kpi-icon { background: #ecfdf5; }
.kpi-cash .kpi-icon { background: #fef3c7; }
.kpi-cheque .kpi-icon { background: #eff6ff; }
.kpi-bank .kpi-icon { background: #f5f3ff; }

.kpi-num {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.kpi-label {
  font-size: 0.775rem;
  color: #64748b;
  margin-top: 0.125rem;
}

/* Filters */
.filters-card {
  padding: 0.875rem 1.25rem;
  background: white;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 12px);
}

.filters-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
}

.filter-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-right: 0.35rem;
  font-weight: 500;
}

.input-date {
  font-size: 0.825rem;
  padding: 0.4rem 0.6rem;
}

.btn-icon {
  padding: 0.55rem;
}

/* Table */
.table-card {
  background: white;
  border: 1px solid var(--color-border, #e2e8f0);
  border-radius: var(--radius-lg, 12px);
  overflow: hidden;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
}

.table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.table tbody tr:hover {
  background: #f8fafc;
}

.ref-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #1e293b;
}

.amount-pill {
  font-size: 0.95rem;
}

.method-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.775rem;
  font-weight: 600;
}

.method-cash { background: #dcfce7; color: #15803d; }
.method-cheque { background: #e0f2fe; color: #0369a1; }
.method-bank_transfer { background: #ede9fe; color: #6d28d9; }
.method-cib_edahabia { background: #fef3c7; color: #b45309; }

.user-avatar-tiny {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #334155;
  font-size: 0.65rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 1.5rem;
}

.modal-box {
  background: white;
  border-radius: var(--radius-xl, 16px);
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
}

.modal-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #64748b;
}

/* Receipt Print Styling */
.receipt-document {
  padding: 2rem;
  background: white;
  color: #0f172a;
}

.receipt-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.receipt-company {
  font-size: 1.35rem;
  font-weight: 800;
  color: #1e3a8a;
  margin: 0;
}

.receipt-sub {
  font-size: 0.825rem;
  color: #475569;
  margin: 0.2rem 0;
}

.receipt-tax-ids {
  font-size: 0.75rem;
  color: #64748b;
  font-family: monospace;
}

.receipt-branch {
  font-size: 0.85rem;
  font-weight: 600;
  margin-top: 0.35rem;
}

.receipt-meta {
  text-align: right;
}

.receipt-badge {
  display: inline-block;
  background: #1e3a8a;
  color: white;
  padding: 0.35rem 0.85rem;
  font-size: 0.8rem;
  font-weight: 700;
  border-radius: 4px;
  letter-spacing: 0.05em;
}

.receipt-ref {
  font-size: 1.1rem;
  font-weight: 700;
  margin-top: 0.35rem;
}

.receipt-date {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.receipt-divider {
  height: 2px;
  background: #e2e8f0;
  margin: 1.25rem 0;
}

.receipt-body {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.receipt-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.4rem;
  border-bottom: 1px dashed #e2e8f0;
}

.receipt-row .label {
  color: #475569;
  font-size: 0.9rem;
}

.receipt-row .val {
  font-size: 0.95rem;
}

.receipt-amount-box {
  margin-top: 0.5rem;
  padding: 1.25rem;
  background: #f8fafc;
  border: 2px solid #cbd5e1;
  border-radius: 8px;
  text-align: center;
}

.amount-title {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #475569;
}

.amount-number {
  font-size: 1.75rem;
  font-weight: 800;
  color: #047857;
  margin: 0.25rem 0;
}

.amount-words {
  font-size: 0.85rem;
  color: #334155;
  font-style: italic;
  margin-top: 0.35rem;
}

.receipt-balance-summary {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: #f1f5f9;
  border-radius: 6px;
  font-size: 0.825rem;
}

.receipt-signatures {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-top: 2rem;
}

.sig-block {
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  padding: 1rem;
  text-align: center;
}

.sig-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
}

.sig-hint, .sig-agent {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.sig-space {
  height: 65px;
}

/* State displays */
.state-container {
  padding: 3rem 1.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.empty-icon {
  font-size: 2.5rem;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media print {
  body * {
    visibility: hidden;
  }
  .receipt-modal,
  .receipt-modal * {
    visibility: visible;
  }
  .receipt-modal {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    max-width: 100%;
    box-shadow: none;
    border: none;
  }
  .no-print {
    display: none !important;
  }
}
</style>
