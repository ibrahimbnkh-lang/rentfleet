<template>
  <div class="invoices-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Facturation & Règlements</h1>
        <p class="page-subtitle">Factures fiscales conformes aux normes algériennes (TVA 19%, droit de timbre), suivi des créances et encaissements</p>
      </div>

      <div class="header-actions">
        <RouterLink to="/payments" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="4" width="13" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M1.5 7h13" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          Journal des Règlements
        </RouterLink>
        <button v-if="can('invoicing.invoice.create')" class="btn btn-primary" @click="openCreateModal">
          <span>+</span> Nouvelle facture
        </button>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-grid">
      <div class="card kpi-card kpi-billed">
        <div class="kpi-icon">📄</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_billed_dzd) }}</div>
          <div class="kpi-label">Total facturé TTC</div>
        </div>
      </div>

      <div class="card kpi-card kpi-paid">
        <div class="kpi-icon">🟢</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_paid_dzd) }}</div>
          <div class="kpi-label">Total encaissé</div>
        </div>
      </div>

      <div class="card kpi-card kpi-due">
        <div class="kpi-icon">⏳</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_due_dzd) }}</div>
          <div class="kpi-label">Reste à recouvrer (Créances)</div>
        </div>
      </div>

      <div class="card kpi-card kpi-count">
        <div class="kpi-icon">📊</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.paid_count }} / {{ metrics.paid_count + metrics.unpaid_count }}</div>
          <div class="kpi-label">Factures soldées</div>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par N° facture (FAC-...), client, NIF, contrat..."
            class="input"
            @input="debounceFetch"
          />
        </div>

        <div class="filter-group">
          <select v-model="filters.status" class="input" @change="() => fetchInvoices(1)">
            <option value="">Tous les statuts</option>
            <option value="paid">Payée (Soldée)</option>
            <option value="partially_paid">Partiellement payée</option>
            <option value="issued">Émise (En attente)</option>
            <option value="cancelled">Annulée</option>
          </select>
        </div>

        <div class="filter-group">
          <select v-model="filters.branch_id" class="input" @change="() => fetchInvoices(1)">
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
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Chargement des factures…</p>
      </div>

      <div v-else-if="invoices.length === 0" class="empty-state">
        <div class="empty-icon">📁</div>
        <p class="empty-text">Aucune facture ne correspond aux critères de recherche.</p>
        <button v-if="can('invoicing.invoice.create')" class="btn btn-primary mt-3" @click="openCreateModal">
          Créer une facture
        </button>
      </div>

      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="col-num">N° Facture</th>
              <th class="col-client">Client / Raison Sociale</th>
              <th class="col-contract">Contrat lié</th>
              <th class="col-dates">Date & Échéance</th>
              <th class="col-amounts">Montants TTC (DZD)</th>
              <th class="col-status">Statut de Règlement</th>
              <th class="col-actions text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in invoices" :key="inv.id">
              <!-- Invoice Number -->
              <td class="col-num">
                <RouterLink :to="'/invoices/' + inv.id" class="inv-badge font-mono" :title="'Consulter la facture ' + inv.invoice_number">
                  {{ inv.invoice_number }}
                </RouterLink>
                <div class="tax-badge-row">
                  <span class="tva-pill">TVA {{ inv.vat_rate }}%</span>
                  <span v-if="inv.stamp_duty_dzd > 0" class="stamp-pill">Timbre</span>
                </div>
              </td>

              <!-- Customer -->
              <td class="col-client">
                <div class="client-cell">
                  <span class="client-name font-semibold">{{ inv.customer_name }}</span>
                  <div class="client-sub">
                    <span v-if="inv.customer_nif" class="nif-pill font-mono">
                      NIF: {{ inv.customer_nif }}
                    </span>
                    <span class="payment-method-pill">{{ formatPaymentMethod(inv.payment_method) }}</span>
                  </div>
                </div>
              </td>

              <!-- Linked Contract -->
              <td class="col-contract">
                <div v-if="inv.contract" class="contract-cell">
                  <RouterLink :to="'/contracts/' + inv.contract.id" class="ctr-link font-mono">
                    {{ inv.contract.contract_number }}
                  </RouterLink>
                  <span v-if="inv.contract.vehicle" class="veh-hint">
                    {{ inv.contract.vehicle.brand }} {{ inv.contract.vehicle.model }}
                  </span>
                </div>
                <span v-else class="text-muted text-xs">Prestation directe</span>
              </td>

              <!-- Dates -->
              <td class="col-dates">
                <div class="dates-cell">
                  <span class="issue-date">Émise le {{ formatDate(inv.issue_date) }}</span>
                  <span class="due-date text-xs text-muted">Échéance : {{ formatDate(inv.due_date) }}</span>
                </div>
              </td>

              <!-- Financials -->
              <td class="col-amounts">
                <div class="amounts-cell">
                  <span class="total-ttc font-mono font-bold">{{ formatDzd(inv.total_ttc_dzd) }}</span>
                  <div class="amounts-sub font-mono">
                    <span class="text-success">Payé : {{ formatDzd(inv.paid_amount_dzd) }}</span>
                    <span v-if="inv.remaining_amount_dzd > 0" class="text-due">Reste : {{ formatDzd(inv.remaining_amount_dzd) }}</span>
                  </div>
                </div>
              </td>

              <!-- Status -->
              <td class="col-status">
                <span class="status-badge" :class="'status-' + inv.status">
                  <span class="status-dot"></span>
                  {{ getStatusLabel(inv.status) }}
                </span>
              </td>

              <!-- Actions -->
              <td class="col-actions text-right">
                <div class="actions-group">
                  <!-- Record Payment Button if remaining > 0 -->
                  <button
                    v-if="inv.status !== 'paid' && inv.status !== 'cancelled' && inv.remaining_amount_dzd > 0 && can('invoicing.payment.create')"
                    class="btn btn-sm btn-outline-success"
                    title="Enregistrer un encaissement"
                    @click="openPaymentModal(inv)"
                  >
                    Encaisser
                  </button>

                  <!-- View / Print Button -->
                  <RouterLink
                    :to="'/invoices/' + inv.id"
                    class="btn btn-sm btn-secondary"
                    title="Ouvrir la facture fiscale"
                  >
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                      <path d="M4 6V2h8v4M4 12H2.5A1.5 1.5 0 011 10.5v-3A1.5 1.5 0 012.5 6h11A1.5 1.5 0 0115 7.5v3a1.5 1.5 0 01-1.5 1.5H12" stroke="currentColor" stroke-width="1.5"/>
                      <rect x="4" y="9" width="8" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    Facture
                  </RouterLink>

                  <!-- Cancel Button if not paid -->
                  <button
                    v-if="inv.status !== 'paid' && inv.status !== 'cancelled' && inv.paid_amount_dzd === 0 && can('invoicing.invoice.cancel')"
                    class="btn btn-sm btn-ghost text-danger"
                    title="Annuler la facture"
                    @click="cancelInvoicePrompt(inv)"
                  >
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                      <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div v-if="pagination.total > pagination.per_page" class="pagination-footer">
        <span class="pagination-info">
          Affichage de {{ invoices.length }} sur {{ pagination.total }} factures
        </span>
        <div class="pagination-controls">
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page <= 1"
            @click="fetchInvoices(pagination.current_page - 1)"
          >
            Précédent
          </button>
          <span class="page-num font-mono">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchInvoices(pagination.current_page + 1)"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: Create Invoice ───────────────────────────────────────────── -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Émettre une Facture Commerciale (DZD)</h2>
            <p class="modal-subtitle">Ventilation TVA 19%, calcul automatique du droit de timbre fiscal et mentions légales algériennes</p>
          </div>
          <button class="modal-close" @click="showCreateModal = false">&times;</button>
        </div>

        <form @submit.prevent="submitCreateInvoice">
          <div class="modal-body">
            <div v-if="modalError" class="alert alert-danger mb-4">
              {{ modalError }}
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="label">Client / Locataire *</label>
                <select v-model="form.customer_id" class="input" required>
                  <option value="">-- Sélectionner le client --</option>
                  <option v-for="c in customersList" :key="c.id" :value="c.id">
                    {{ c.full_name }} ({{ c.phone }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Agence émettrice *</label>
                <select v-model="form.branch_id" class="input" required>
                  <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Date d'émission *</label>
                <input v-model="form.issue_date" type="date" class="input" required />
              </div>

              <div class="form-group">
                <label class="label">Date d'échéance *</label>
                <input v-model="form.due_date" type="date" class="input" required />
              </div>

              <div class="form-group">
                <label class="label">Mode de règlement prévu *</label>
                <select v-model="form.payment_method" class="input" required>
                  <option value="cash">Espèces (Droit de timbre applicable)</option>
                  <option value="cheque">Chèque bancaire</option>
                  <option value="bank_transfer">Virement bancaire</option>
                  <option value="cib_edahabia">Carte CIB / Edahabia</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Conditions de paiement</label>
                <input v-model="form.payment_terms" type="text" class="input" placeholder="ex: Règlement à réception" />
              </div>
            </div>

            <!-- Line Items -->
            <div class="items-section mt-4">
              <div class="section-title-row">
                <span class="form-section-title">Prestations / Lignes de facturation (HT)</span>
                <button type="button" class="btn btn-sm btn-secondary" @click="addItemRow">
                  + Ajouter une ligne
                </button>
              </div>

              <div class="items-table-wrap mt-2">
                <table class="items-table">
                  <thead>
                    <tr>
                      <th style="width: 55%;">Désignation prestation</th>
                      <th style="width: 15%;">Qté / Jours</th>
                      <th style="width: 20%;">Prix unit. HT (DZD)</th>
                      <th style="width: 10%;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, idx) in form.items" :key="idx">
                      <td>
                        <input v-model="item.description" type="text" class="input" required placeholder="ex: Location Peugeot 208 - 4 jours" />
                      </td>
                      <td>
                        <input v-model.number="item.quantity" type="number" step="0.5" min="0.5" class="input font-mono" required />
                      </td>
                      <td>
                        <input v-model.number="item.unit_price_ht_dzd" type="number" step="100" min="0" class="input font-mono" required />
                      </td>
                      <td class="text-center">
                        <button v-if="form.items.length > 1" type="button" class="btn-del" @click="removeItemRow(idx)">&times;</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Live Algerian Tax Summary Card -->
            <div class="tax-summary-box mt-4">
              <div class="tax-row">
                <span>Total Hors Taxes (HT) :</span>
                <span class="font-mono">{{ formatDzd(liveTaxes.subtotal_ht) }}</span>
              </div>
              <div class="tax-row">
                <span>TVA Algérie (19,00%) :</span>
                <span class="font-mono">{{ formatDzd(liveTaxes.vat_amount) }}</span>
              </div>
              <div class="tax-row" v-if="liveTaxes.stamp_duty > 0">
                <span>Droit de Timbre (Paiement espèces) :</span>
                <span class="font-mono">{{ formatDzd(liveTaxes.stamp_duty) }}</span>
              </div>
              <div class="tax-row total-ttc-row">
                <span class="font-bold">Total Général TTC :</span>
                <span class="font-mono font-bold total-ttc-val">{{ formatDzd(liveTaxes.total_ttc) }}</span>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Émission en cours...' : 'Émettre la Facture' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ─── Modal: Record Payment ──────────────────────────────────────────── -->
    <div v-if="paymentModalInvoice" class="modal-backdrop" @click.self="paymentModalInvoice = null">
      <div class="modal modal-md">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Enregistrer un Encaissement</h2>
            <p class="modal-subtitle">Facture N° {{ paymentModalInvoice.invoice_number }} · {{ paymentModalInvoice.customer_name }}</p>
          </div>
          <button class="modal-close" @click="paymentModalInvoice = null">&times;</button>
        </div>

        <form @submit.prevent="submitRecordPayment">
          <div class="modal-body">
            <div v-if="paymentError" class="alert alert-danger mb-4">
              {{ paymentError }}
            </div>

            <div class="due-reminder-box mb-4">
              <div class="flex-between">
                <span>Total facture TTC :</span>
                <span class="font-mono">{{ formatDzd(paymentModalInvoice.total_ttc_dzd) }}</span>
              </div>
              <div class="flex-between mt-1">
                <span class="font-semibold text-danger">Solde restant à payer :</span>
                <strong class="font-mono font-bold text-danger">{{ formatDzd(paymentModalInvoice.remaining_amount_dzd) }}</strong>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="label">Montant encaissé (DZD) *</label>
                <input
                  v-model.number="paymentForm.amount_dzd"
                  type="number"
                  step="100"
                  class="input font-mono font-bold text-purple"
                  :max="paymentModalInvoice.remaining_amount_dzd"
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
                <label class="label">Références règlement (N° Chèque / Banque / N° Transaction)</label>
                <input
                  v-model="paymentForm.reference_details"
                  type="text"
                  class="input font-mono"
                  placeholder="ex: Chèque BNA N° 0048123 ou Virement 192083"
                />
              </div>

              <div class="form-group col-span-2">
                <label class="label">Observations / Notes d'encaissement</label>
                <input v-model="paymentForm.notes" type="text" class="input" placeholder="ex: Reçu de caisse délivré au client" />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="paymentModalInvoice = null">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submittingPayment || paymentForm.amount_dzd <= 0">
              {{ submittingPayment ? 'Validation...' : 'Valider l\'encaissement' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function can(permission: string) {
  return auth.hasRole('Super Admin') || auth.hasPermission(permission)
}

interface InvoiceItem {
  id: number
  invoice_number: string
  contract_id?: number
  customer_id: number
  branch_id: number
  status: string
  issue_date: string
  due_date: string
  subtotal_ht_dzd: number
  vat_rate: number
  vat_amount_dzd: number
  stamp_duty_dzd: number
  total_ttc_dzd: number
  paid_amount_dzd: number
  remaining_amount_dzd: number
  customer_name: string
  customer_nif?: string
  payment_method: string
  customer?: { full_name: string; phone: string }
  branch?: { name: string }
  contract?: { id: number; contract_number: string; vehicle?: { brand: string; model: string } }
}

const invoices = ref<InvoiceItem[]>([])
const branches = ref<any[]>([])
const customersList = ref<any[]>([])

const loading = ref(false)
const submitting = ref(false)
const submittingPayment = ref(false)
const showCreateModal = ref(false)
const modalError = ref('')
const paymentError = ref('')
const paymentModalInvoice = ref<InvoiceItem | null>(null)

const metrics = ref({
  total_billed_dzd: 0,
  total_paid_dzd: 0,
  total_due_dzd: 0,
  paid_count: 0,
  unpaid_count: 0,
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const filters = ref({
  search: '',
  status: '',
  branch_id: '',
})

const form = ref({
  customer_id: '' as string | number,
  branch_id: '' as string | number,
  issue_date: new Date().toISOString().split('T')[0],
  due_date: new Date(Date.now() + 15 * 86400000).toISOString().split('T')[0],
  payment_method: 'cheque',
  payment_terms: 'Règlement à réception',
  notes: '',
  items: [
    { description: 'Location véhicule automobile', quantity: 3, unit_price_ht_dzd: 4500 },
  ],
})

const paymentForm = ref({
  amount_dzd: 0,
  payment_method: 'cash',
  reference_details: '',
  notes: '',
})

// Live Tax Calculation
const liveTaxes = computed(() => {
  let subtotal = 0
  form.value.items.forEach(it => {
    subtotal += (Number(it.quantity) || 0) * (Number(it.unit_price_ht_dzd) || 0)
  })
  const vat = Math.round(subtotal * 0.19 * 100) / 100
  let stamp = 0
  if (form.value.payment_method === 'cash' && subtotal > 0) {
    stamp = Math.max(20, Math.min(2500, Math.ceil((subtotal + vat) * 0.01)))
  }
  const ttc = Math.round((subtotal + vat + stamp) * 100) / 100
  return {
    subtotal_ht: subtotal,
    vat_amount: vat,
    stamp_duty: stamp,
    total_ttc: ttc,
  }
})

const addItemRow = () => {
  form.value.items.push({ description: '', quantity: 1, unit_price_ht_dzd: 0 })
}

const removeItemRow = (idx: number) => {
  if (form.value.items.length > 1) {
    form.value.items.splice(idx, 1)
  }
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null
const debounceFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchInvoices(1)
  }, 350)
}

const resetFilters = () => {
  filters.value = { search: '', status: '', branch_id: '' }
  fetchInvoices(1)
}

// ─── API ─────────────────────────────────────────────────────────────────────
const fetchInvoices = async (page = 1) => {
  loading.value = true
  try {
    const params: Record<string, any> = { page }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id

    const res = await api.get('/invoicing/invoices', { params })
    invoices.value = res.data.data || []
    if (res.data.meta?.pagination) pagination.value = res.data.meta.pagination
    if (res.data.meta?.metrics) metrics.value = res.data.meta.metrics
  } catch (err) {
    console.error('Erreur chargement factures:', err)
  } finally {
    loading.value = false
  }
}

const fetchAuxiliary = async () => {
  try {
    const [bRes, cRes] = await Promise.all([
      api.get('/core/branches'),
      api.get('/customers', { params: { per_page: 100 } }),
    ])
    branches.value = bRes.data.data || []
    customersList.value = cRes.data.data || []
    if (branches.value.length > 0 && !form.value.branch_id) {
      form.value.branch_id = branches.value[0].id
    }
  } catch (err) {
    console.error('Erreur données auxiliaires:', err)
  }
}

const openCreateModal = () => {
  modalError.value = ''
  form.value = {
    customer_id: customersList.value[0]?.id || '',
    branch_id: branches.value[0]?.id || '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 15 * 86400000).toISOString().split('T')[0],
    payment_method: 'cheque',
    payment_terms: 'Règlement à réception',
    notes: '',
    items: [
      { description: 'Location véhicule automobile', quantity: 3, unit_price_ht_dzd: 4500 },
    ],
  }
  showCreateModal.value = true
}

const submitCreateInvoice = async () => {
  modalError.value = ''
  submitting.value = true
  try {
    await api.post('/invoicing/invoices', {
      ...form.value,
      customer_id: Number(form.value.customer_id),
      branch_id: Number(form.value.branch_id),
    })
    showCreateModal.value = false
    await fetchInvoices(1)
  } catch (err: any) {
    modalError.value = err.response?.data?.message || err.response?.data?.error?.message || 'Erreur lors de la création de la facture'
  } finally {
    submitting.value = false
  }
}

const openPaymentModal = (inv: InvoiceItem) => {
  paymentModalInvoice.value = inv
  paymentError.value = ''
  paymentForm.value = {
    amount_dzd: Number(inv.remaining_amount_dzd),
    payment_method: inv.payment_method === 'cash' ? 'cash' : 'cheque',
    reference_details: '',
    notes: '',
  }
}

const submitRecordPayment = async () => {
  if (!paymentModalInvoice.value) return
  paymentError.value = ''
  submittingPayment.value = true
  try {
    await api.post(`/invoicing/invoices/${paymentModalInvoice.value.id}/payments`, paymentForm.value)
    paymentModalInvoice.value = null
    await fetchInvoices(pagination.value.current_page)
  } catch (err: any) {
    paymentError.value = err.response?.data?.message || err.response?.data?.error?.message || 'Erreur lors de l\'encaissement'
  } finally {
    submittingPayment.value = false
  }
}

const cancelInvoicePrompt = async (inv: InvoiceItem) => {
  if (!confirm(`Annuler définitivement la facture ${inv.invoice_number} ?`)) return
  try {
    await api.post(`/invoicing/invoices/${inv.id}/cancel`)
    await fetchInvoices(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur d\'annulation')
  }
}

// ─── Formatters ──────────────────────────────────────────────────────────────
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

const formatPaymentMethod = (m: string) => {
  switch (m) {
    case 'cash': return 'Espèces'
    case 'cheque': return 'Chèque'
    case 'bank_transfer': return 'Virement'
    case 'cib_edahabia': return 'Carte CIB'
    default: return m || 'Paiement'
  }
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'paid': return 'Payée'
    case 'partially_paid': return 'Partielle'
    case 'issued': return 'En attente'
    case 'cancelled': return 'Annulée'
    default: return st
  }
}

onMounted(async () => {
  await fetchAuxiliary()
  await fetchInvoices()
})
</script>

<style scoped>
.invoices-view {
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
  font-size: 1.625rem;
  font-weight: 700;
  color: #0f172a;
}

.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

/* KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 1rem;
}

.kpi-card {
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}

.kpi-icon {
  font-size: 1.75rem;
  background: #f8fafc;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
}

.kpi-billed .kpi-num { color: #1e40af; font-size: 1.25rem; }
.kpi-paid .kpi-num { color: #10b981; font-size: 1.25rem; }
.kpi-due .kpi-num { color: #d97706; font-size: 1.25rem; }
.kpi-count .kpi-num { color: #6d28d9; }

.kpi-num { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
.kpi-label { font-size: 0.8125rem; color: #64748b; margin-top: 0.125rem; }

/* Filters */
.filters-card {
  padding: 1rem 1.25rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}

.filters-row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.flex-1 { flex: 1; }

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.75rem;
}

/* Table */
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  overflow: hidden;
}

.table-responsive { overflow-x: auto; }

.table {
  width: 100%;
  min-width: 1180px;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.875rem;
}

.table th {
  background: #f8fafc;
  padding: 0.95rem 1.15rem;
  font-weight: 600;
  font-size: 0.8125rem;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.table td {
  padding: 0.95rem 1.15rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

/* Columns */
.col-num { width: 150px; min-width: 150px; white-space: nowrap; }
.col-client { min-width: 230px; }
.col-contract { min-width: 160px; }
.col-dates { min-width: 170px; white-space: nowrap; }
.col-amounts { min-width: 190px; white-space: nowrap; }
.col-status { min-width: 130px; white-space: nowrap; }
.col-actions { min-width: 180px; white-space: nowrap; }

.inv-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 0.375rem;
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 700;
  font-size: 0.8125rem;
  text-decoration: none;
}
.inv-badge:hover { text-decoration: underline; color: #2563eb; }

.tax-badge-row {
  display: flex;
  gap: 0.35rem;
  margin-top: 0.25rem;
}

.tva-pill {
  font-size: 0.6875rem;
  padding: 0.1rem 0.35rem;
  border-radius: 0.25rem;
  background: #e0f2fe;
  color: #0369a1;
}

.stamp-pill {
  font-size: 0.6875rem;
  padding: 0.1rem 0.35rem;
  border-radius: 0.25rem;
  background: #fef3c7;
  color: #92400e;
}

.client-cell, .dates-cell, .amounts-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.client-name { color: #0f172a; font-size: 0.9rem; white-space: nowrap; }
.client-sub { display: flex; gap: 0.5rem; align-items: center; white-space: nowrap; }
.nif-pill { font-size: 0.6875rem; background: #f8fafc; border: 1px solid #cbd5e1; padding: 0.1rem 0.35rem; border-radius: 0.25rem; color: #475569; }
.payment-method-pill { font-size: 0.6875rem; color: #64748b; }

.contract-cell { display: flex; flex-direction: column; gap: 0.2rem; }
.ctr-link { font-size: 0.8125rem; color: #2563eb; font-weight: 600; text-decoration: none; }
.ctr-link:hover { text-decoration: underline; }
.veh-hint { font-size: 0.75rem; color: #64748b; }

.issue-date { font-weight: 600; color: #1e293b; }
.due-date { font-size: 0.75rem; color: #64748b; }

.total-ttc { font-size: 0.9375rem; color: #0f172a; }
.amounts-sub { display: flex; gap: 0.5rem; font-size: 0.75rem; }
.text-due { color: #b45309; font-weight: 600; }

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  width: fit-content;
}

.status-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; }

.status-paid { background: #dcfce7; color: #166534; }
.status-paid .status-dot { background: #22c55e; }

.status-partially_paid { background: #fef3c7; color: #92400e; }
.status-partially_paid .status-dot { background: #f59e0b; }

.status-issued { background: #e0f2fe; color: #075985; }
.status-issued .status-dot { background: #0284c7; }

.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-cancelled .status-dot { background: #ef4444; }

.actions-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: flex-end;
}

.btn-sm { padding: 0.25rem 0.625rem; font-size: 0.75rem; border-radius: 0.375rem; }
.btn-outline-success { border: 1px solid #16a34a; color: #15803d; background: transparent; }
.btn-outline-success:hover { background: #dcfce7; }

.btn-ghost { background: transparent; border: none; cursor: pointer; color: #64748b; padding: 0.375rem; border-radius: 0.375rem; }
.btn-ghost:hover { background: #f1f5f9; color: #0f172a; }

.loading-state, .empty-state { padding: 3rem; text-align: center; }

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.pagination-controls { display: flex; align-items: center; gap: 0.75rem; }

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: #ffffff;
  border-radius: 1rem;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-md { max-width: 580px; }
.modal-lg { max-width: 820px; }

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-title { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
.modal-subtitle { font-size: 0.8125rem; color: #64748b; margin-top: 0.125rem; }

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
}

.modal-body { padding: 1.5rem; overflow-y: auto; }
.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8fafc;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.col-span-2 { grid-column: span 2; }

/* Items table */
.items-table-wrap {
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  overflow: hidden;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th {
  background: #f8fafc;
  padding: 0.6rem 0.75rem;
  font-size: 0.75rem;
  color: #475569;
  font-weight: 600;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.items-table td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.btn-del {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: #ef4444;
  cursor: pointer;
}

.tax-summary-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.tax-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #334155;
}

.total-ttc-row {
  border-top: 1px solid #cbd5e1;
  padding-top: 0.5rem;
  margin-top: 0.25rem;
  font-size: 1.0625rem;
  color: #0f172a;
}

.total-ttc-val { color: #1e40af; }

.due-reminder-box {
  padding: 0.875rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.flex-between {
  display: flex;
  justify-content: space-between;
}

.text-purple { color: #6d28d9; }
</style>

