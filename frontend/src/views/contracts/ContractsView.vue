<template>
  <div class="contracts-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Contrats de Location & E-Signature</h1>
        <p class="page-subtitle">Gestion juridique certifiée, états des lieux départ/retour et signature électronique conforme Algérie</p>
      </div>

      <div class="header-actions">
        <button v-if="can('contracts.contract.create')" class="btn btn-primary" @click="openCreateModal">
          <span>+</span> Nouveau contrat
        </button>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-grid">
      <div class="card kpi-card">
        <div class="kpi-icon">📜</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.total_contracts }}</div>
          <div class="kpi-label">Total contrats établis</div>
        </div>
      </div>

      <div class="card kpi-card kpi-active">
        <div class="kpi-icon">🚗</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.active_contracts }}</div>
          <div class="kpi-label">Contrats actifs en cours</div>
        </div>
      </div>

      <div class="card kpi-card kpi-completed">
        <div class="kpi-icon">🏁</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.completed_contracts }}</div>
          <div class="kpi-label">Contrats clôturés</div>
        </div>
      </div>

      <div class="card kpi-card kpi-revenue">
        <div class="kpi-icon">💰</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_revenue_dzd) }}</div>
          <div class="kpi-label">Total facturé (DZD)</div>
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
            placeholder="Rechercher par N° contrat (CTR-...), client, NIN, matricule..."
            class="input"
            @input="debounceFetch"
          />
        </div>

        <div class="filter-group">
          <select v-model="filters.status" class="input" @change="() => fetchContracts(1)">
            <option value="">Tous les statuts</option>
            <option value="active">Actif (En cours)</option>
            <option value="completed">Clôturé (Restitué)</option>
            <option value="draft">Brouillon</option>
            <option value="cancelled">Annulé</option>
          </select>
        </div>

        <div class="filter-group">
          <select v-model="filters.branch_id" class="input" @change="() => fetchContracts(1)">
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
        <p>Chargement des contrats…</p>
      </div>

      <div v-else-if="contracts.length === 0" class="empty-state">
        <div class="empty-icon">📁</div>
        <p class="empty-text">Aucun contrat ne correspond aux critères de recherche.</p>
        <button v-if="can('contracts.contract.create')" class="btn btn-primary mt-3" @click="openCreateModal">
          Établir un contrat
        </button>
      </div>

      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="col-num">N° Contrat</th>
              <th class="col-client">Client / Conducteur</th>
              <th class="col-vehicle">Véhicule</th>
              <th class="col-branch">Agence</th>
              <th class="col-period">Période & Kilométrage</th>
              <th class="col-fin">Montant & Caution</th>
              <th class="col-status">Statut & Signature</th>
              <th class="col-actions text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in contracts" :key="c.id">
              <!-- Contract Number -->
              <td class="col-num">
                <RouterLink :to="'/contracts/' + c.id" class="ctr-badge font-mono" :title="'Consulter le contrat ' + c.contract_number">
                  {{ c.contract_number }}
                </RouterLink>
                <div v-if="c.reservation_id" class="res-ref-hint font-mono">
                  Rés. {{ c.reservation?.reference_code }}
                </div>
              </td>

              <!-- Customer -->
              <td class="col-client">
                <div class="client-cell">
                  <span class="client-name font-semibold">{{ c.customer?.full_name || 'Client' }}</span>
                  <div class="client-sub">
                    <span class="client-phone font-mono">{{ c.customer?.phone }}</span>
                    <span v-if="c.customer?.national_id_number" class="nin-pill font-mono">
                      NIN: {{ c.customer.national_id_number.slice(-6) }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Vehicle -->
              <td class="col-vehicle">
                <div class="vehicle-cell">
                  <span class="vehicle-title">{{ c.vehicle?.brand }} {{ c.vehicle?.model }}</span>
                  <div class="vehicle-sub">
                    <span class="plate-pill font-mono">{{ c.vehicle?.registration_plate }}</span>
                    <span v-if="c.vehicle?.category" class="cat-pill">{{ c.vehicle?.category?.name }}</span>
                  </div>
                </div>
              </td>

              <!-- Branch -->
              <td class="col-branch">
                <span class="branch-pill">{{ c.branch?.name || 'Agence' }}</span>
              </td>

              <!-- Period & Mileage -->
              <td class="col-period">
                <div class="period-cell">
                  <div class="period-dates">
                    <span class="date-part">{{ formatDate(c.start_datetime) }}</span>
                    <span class="arrow-sep">→</span>
                    <span class="date-part">{{ formatDate(c.end_datetime) }}</span>
                  </div>
                  <div class="mileage-sub font-mono">
                    <span>Départ: {{ c.start_mileage }} km</span>
                    <span v-if="c.end_mileage"> | Retour: {{ c.end_mileage }} km</span>
                  </div>
                </div>
              </td>

              <!-- Financials -->
              <td class="col-fin">
                <div class="price-cell">
                  <span class="total-dzd font-mono">{{ formatDzd(c.total_amount_dzd) }}</span>
                  <span class="deposit-hint font-mono">
                    Caution : {{ formatDzd(c.deposit_amount_dzd) }} ({{ formatDepositType(c.deposit_type) }})
                  </span>
                </div>
              </td>

              <!-- Status & Signature -->
              <td class="col-status">
                <div class="status-cell">
                  <span class="status-badge" :class="'status-' + c.status">
                    <span class="status-dot"></span>
                    {{ getStatusLabel(c.status) }}
                  </span>
                  <span v-if="c.client_signature" class="sig-indicator text-success">
                    ✓ E-Signature certifiée
                  </span>
                  <span v-else class="sig-indicator text-warning">
                    ⏳ Signature en attente
                  </span>
                </div>
              </td>

              <!-- Actions -->
              <td class="col-actions text-right">
                <div class="actions-group">
                  <!-- Return / Check-out button -->
                  <button
                    v-if="c.status === 'active' && can('contracts.contract.checkout')"
                    class="btn btn-sm btn-outline-primary"
                    title="Enregistrer la restitution du véhicule"
                    @click="openCheckoutModal(c)"
                  >
                    Restitution
                  </button>

                  <!-- View / Print Contract -->
                  <RouterLink
                    :to="'/contracts/' + c.id"
                    class="btn btn-sm btn-secondary"
                    title="Ouvrir / Imprimer le contrat légal"
                  >
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                      <path d="M4 6V2h8v4M4 12H2.5A1.5 1.5 0 011 10.5v-3A1.5 1.5 0 012.5 6h11A1.5 1.5 0 0115 7.5v3a1.5 1.5 0 01-1.5 1.5H12" stroke="currentColor" stroke-width="1.5"/>
                      <rect x="4" y="9" width="8" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    Imprimer
                  </RouterLink>

                  <!-- Delete -->
                  <button
                    v-if="c.status !== 'active' && can('contracts.contract.delete')"
                    class="btn btn-sm btn-ghost text-danger"
                    title="Supprimer"
                    @click="deleteContractPrompt(c)"
                  >
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                      <path d="M3 4h10M6 4V2.5A1.5 1.5 0 017.5 1h1A1.5 1.5 0 0110 2.5V4M12.5 4v9.5A1.5 1.5 0 0111 15H5a1.5 1.5 0 01-1.5-1.5V4" stroke="currentColor" stroke-width="1.5"/>
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
          Affichage de {{ contracts.length }} sur {{ pagination.total }} contrats
        </span>
        <div class="pagination-controls">
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page <= 1"
            @click="fetchContracts(pagination.current_page - 1)"
          >
            Précédent
          </button>
          <span class="page-num font-mono">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchContracts(pagination.current_page + 1)"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: Create Contract ─────────────────────────────────────────── -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Établir un Nouveau Contrat de Location</h2>
            <p class="modal-subtitle">Contrat de location automobile avec relevé d'état des lieux et signature numérique</p>
          </div>
          <button class="modal-close" @click="showCreateModal = false">&times;</button>
        </div>

        <form @submit.prevent="submitCreateContract">
          <div class="modal-body">
            <div v-if="modalError" class="alert alert-danger mb-4">
              {{ modalError }}
            </div>

            <!-- Source toggle: from reservation or direct -->
            <div class="form-section">
              <span class="form-section-title">1. Origine du contrat</span>
              <div class="source-toggle-row">
                <label class="radio-label">
                  <input v-model="creationMode" type="radio" value="direct" />
                  <span>Contrat Direct (Comptoir / Walk-in)</span>
                </label>
                <label class="radio-label">
                  <input v-model="creationMode" type="radio" value="reservation" />
                  <span>Depuis une Réservation Existante</span>
                </label>
              </div>

              <!-- Reservation Selector if mode == reservation -->
              <div v-if="creationMode === 'reservation'" class="form-group mt-3">
                <label class="label">Sélectionner la réservation validée *</label>
                <select v-model="form.reservation_id" class="input" @change="onReservationSelected">
                  <option value="">-- Choisir une réservation --</option>
                  <option v-for="r in pendingReservations" :key="r.id" :value="r.id">
                    {{ r.reference_code }} · {{ r.customer?.full_name }} — {{ r.vehicle?.brand }} {{ r.vehicle?.model }} ({{ r.vehicle?.registration_plate }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Client & Vehicle Selection -->
            <div class="form-section mt-4">
              <span class="form-section-title">2. Client & Véhicule</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Locataire / Client *</label>
                  <select v-model="form.customer_id" class="input" required :disabled="creationMode === 'reservation' && !!form.reservation_id">
                    <option value="">-- Sélectionner le client --</option>
                    <option v-for="c in customersList" :key="c.id" :value="c.id">
                      {{ c.full_name }} ({{ c.phone }})
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="label">Véhicule assigné *</label>
                  <select v-model="form.vehicle_id" class="input" required :disabled="creationMode === 'reservation' && !!form.reservation_id" @change="onVehicleSelect">
                    <option value="">-- Sélectionner le véhicule --</option>
                    <option v-for="v in vehiclesList" :key="v.id" :value="v.id">
                      {{ v.brand }} {{ v.model }} ({{ v.registration_plate }}) — {{ formatDzd(v.daily_price_dzd) }}/j
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Dates & Agency -->
            <div class="form-section mt-4">
              <span class="form-section-title">3. Agences & Période</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Agence de retrait *</label>
                  <select v-model="form.branch_id" class="input" required>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="label">Agence de restitution</label>
                  <select v-model="form.return_branch_id" class="input">
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="label">Date & Heure de départ *</label>
                  <input v-model="form.start_datetime" type="datetime-local" class="input" required />
                </div>

                <div class="form-group">
                  <label class="label">Date & Heure de retour prévue *</label>
                  <input v-model="form.end_datetime" type="datetime-local" class="input" required />
                </div>
              </div>
            </div>

            <!-- Check-in Inspection (Départ) -->
            <div class="form-section mt-4">
              <span class="form-section-title">4. État des Lieux au Départ</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Compteur kilométrique départ (km) *</label>
                  <input v-model.number="form.start_mileage" type="number" class="input font-mono" required />
                </div>

                <div class="form-group">
                  <label class="label">Niveau de carburant départ *</label>
                  <select v-model="form.start_fuel_level" class="input" required>
                    <option value="100%">100% (Plein complet)</option>
                    <option value="75%">75% (3/4)</option>
                    <option value="50%">50% (1/2)</option>
                    <option value="25%">25% (1/4)</option>
                  </select>
                </div>

                <div class="form-group col-span-2">
                  <label class="label">Remarques état carrosserie / accessoires</label>
                  <input
                    v-model="form.start_notes"
                    type="text"
                    class="input"
                    placeholder="ex: Roue de secours présente, gilet, cric, micro-rayure aile arrière..."
                  />
                </div>
              </div>
            </div>

            <!-- Financials & Deposit -->
            <div class="form-section mt-4">
              <span class="form-section-title">5. Tarifs & Cautionnement Algérie</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Tarif journalier (DZD) *</label>
                  <input v-model.number="form.daily_rate_dzd" type="number" class="input font-mono" required />
                </div>

                <div class="form-group">
                  <label class="label">Montant de la caution (DZD) *</label>
                  <input v-model.number="form.deposit_amount_dzd" type="number" class="input font-mono" required />
                </div>

                <div class="form-group">
                  <label class="label">Type de caution *</label>
                  <select v-model="form.deposit_type" class="input" required>
                    <option value="cheque">Chèque de garantie</option>
                    <option value="cash">Dépôt en espèces</option>
                    <option value="card">Empreinte carte bancaire</option>
                    <option value="bank_transfer">Virement bancaire</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="label">Référence caution (N° Chèque / Banque)</label>
                  <input
                    v-model="form.deposit_reference"
                    type="text"
                    class="input font-mono"
                    placeholder="ex: BNA Chèque N° 00481920"
                  />
                </div>
              </div>

              <!-- Live Total card -->
              <div class="total-summary-card mt-3">
                <div class="summary-line">
                  <span>Durée calculée : <strong>{{ calculatedContractDays }} jours</strong></span>
                  <span class="summary-total font-mono">{{ formatDzd(calculatedContractTotal) }}</span>
                </div>
              </div>
            </div>

            <!-- E-Signature Interactive Canvas -->
            <div class="form-section mt-4">
              <span class="form-section-title">6. Émargement & Signature Électronique</span>
              <p class="sig-hint">Signez à l'aide de la souris ou de l'écran tactile ci-dessous :</p>
              
              <div class="signature-pad-container">
                <canvas
                  ref="signatureCanvas"
                  class="signature-canvas"
                  width="480"
                  height="160"
                  @mousedown="startDrawing"
                  @mousemove="draw"
                  @mouseup="stopDrawing"
                  @mouseleave="stopDrawing"
                  @touchstart.passive="startTouchDrawing"
                  @touchmove.passive="touchDraw"
                  @touchend.passive="stopDrawing"
                ></canvas>
                <div class="sig-actions">
                  <button type="button" class="btn btn-sm btn-secondary" @click="clearSignature">
                    Effacer la signature
                  </button>
                  <span v-if="hasSignature" class="sig-ready-badge text-success">
                    ✓ Signature saisie
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting || !form.customer_id || !form.vehicle_id"
            >
              {{ submitting ? 'Génération du contrat...' : 'Établir et Activer le Contrat' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ─── Modal: Return / Check-out ──────────────────────────────────────── -->
    <div v-if="checkoutContract" class="modal-backdrop" @click.self="checkoutContract = null">
      <div class="modal modal-md">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Restitution du Véhicule (Check-out)</h2>
            <p class="modal-subtitle">Contrat N° {{ checkoutContract.contract_number }} · {{ checkoutContract.vehicle?.brand }} {{ checkoutContract.vehicle?.model }}</p>
          </div>
          <button class="modal-close" @click="checkoutContract = null">&times;</button>
        </div>

        <form @submit.prevent="submitCheckout">
          <div class="modal-body">
            <div v-if="checkoutError" class="alert alert-danger mb-4">
              {{ checkoutError }}
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="label">Kilométrage de départ</label>
                <input
                  :value="checkoutContract.start_mileage + ' km'"
                  type="text"
                  class="input font-mono bg-light"
                  disabled
                />
              </div>

              <div class="form-group">
                <label class="label">Kilométrage de retour (km) *</label>
                <input
                  v-model.number="checkoutForm.end_mileage"
                  type="number"
                  class="input font-mono"
                  :min="checkoutContract.start_mileage"
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
                <label class="label">Frais dépassement km (DZD)</label>
                <input v-model.number="checkoutForm.extra_mileage_fee_dzd" type="number" class="input font-mono" />
              </div>

              <div class="form-group">
                <label class="label">Frais dommages / réparations (DZD)</label>
                <input v-model.number="checkoutForm.damages_fee_dzd" type="number" class="input font-mono" />
              </div>

              <div class="form-group col-span-2">
                <label class="label">Observations lors de la restitution</label>
                <textarea
                  v-model="checkoutForm.end_notes"
                  rows="2"
                  class="input"
                  placeholder="État du véhicule, propreté, restitution caution..."
                ></textarea>
              </div>
            </div>

            <div class="checkout-summary-box mt-3">
              <div class="flex-between">
                <span>Distance parcourue :</span>
                <strong class="font-mono">{{ calculatedDrivenKm }} km</strong>
              </div>
              <div class="flex-between mt-1">
                <span>Frais supplémentaires :</span>
                <strong class="font-mono text-purple">{{ formatDzd(calculatedExtraFees) }}</strong>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="checkoutContract = null">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submittingCheckout">
              {{ submittingCheckout ? 'Clôture en cours...' : 'Valider la Restitution' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
function can(permission: string) {
  return auth.hasRole('Super Admin') || auth.hasPermission(permission)
}

interface ContractItem {
  id: number
  contract_number: string
  reservation_id?: number
  customer_id: number
  vehicle_id: number
  branch_id: number
  status: string
  start_datetime: string
  end_datetime: string
  days_count: number
  daily_rate_dzd: number
  total_amount_dzd: number
  deposit_amount_dzd: number
  deposit_type: string
  deposit_reference?: string
  start_mileage: number
  end_mileage?: number
  client_signature?: string
  customer?: {
    id: number
    full_name: string
    phone: string
    national_id_number?: string
  }
  vehicle?: {
    id: number
    brand: string
    model: string
    registration_plate: string
    daily_price_dzd: number
    deposit_dzd: number
    mileage: number
    category?: { name: string }
  }
  branch?: {
    id: number
    name: string
  }
  reservation?: {
    id: number
    reference_code: string
  }
}

const contracts = ref<ContractItem[]>([])
const branches = ref<any[]>([])
const customersList = ref<any[]>([])
const vehiclesList = ref<any[]>([])
const pendingReservations = ref<any[]>([])

const loading = ref(false)
const submitting = ref(false)
const submittingCheckout = ref(false)
const showCreateModal = ref(false)
const modalError = ref('')
const checkoutError = ref('')
const checkoutContract = ref<ContractItem | null>(null)

const creationMode = ref<'direct' | 'reservation'>('direct')

const metrics = ref({
  total_contracts: 0,
  active_contracts: 0,
  completed_contracts: 0,
  total_revenue_dzd: 0,
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

// Dates defaults
const defaultStart = new Date()
const defaultEnd = new Date()
defaultEnd.setDate(defaultEnd.getDate() + 3)

const toLocalIso = (d: Date) => {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const form = ref({
  reservation_id: '' as string | number,
  customer_id: '' as string | number,
  vehicle_id: '' as string | number,
  branch_id: '' as string | number,
  return_branch_id: '' as string | number,
  start_datetime: toLocalIso(defaultStart),
  end_datetime: toLocalIso(defaultEnd),
  daily_rate_dzd: 0,
  deposit_amount_dzd: 0,
  deposit_type: 'cheque',
  deposit_reference: '',
  start_mileage: 0,
  start_fuel_level: '100%',
  start_notes: '',
  client_signature: '',
})

const checkoutForm = ref({
  end_mileage: 0,
  end_fuel_level: '100%',
  fuel_fee_dzd: 0,
  extra_mileage_fee_dzd: 0,
  damages_fee_dzd: 0,
  end_notes: '',
})

// Signature Pad canvas
const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const isDrawing = ref(false)
const hasSignature = ref(false)

const startDrawing = (e: MouseEvent) => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  isDrawing.value = true
  const rect = canvas.getBoundingClientRect()
  ctx.beginPath()
  ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top)
}

const draw = (e: MouseEvent) => {
  if (!isDrawing.value) return
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const rect = canvas.getBoundingClientRect()
  ctx.lineWidth = 2.5
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#1e3a8a'
  ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top)
  ctx.stroke()
  hasSignature.value = true
}

const stopDrawing = () => {
  if (!isDrawing.value) return
  isDrawing.value = false
  if (signatureCanvas.value && hasSignature.value) {
    form.value.client_signature = signatureCanvas.value.toDataURL('image/png')
  }
}

const startTouchDrawing = (e: TouchEvent) => {
  const touch = e.touches[0]
  if (!touch) return
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  isDrawing.value = true
  const rect = canvas.getBoundingClientRect()
  ctx.beginPath()
  ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top)
}

const touchDraw = (e: TouchEvent) => {
  if (!isDrawing.value) return
  const touch = e.touches[0]
  if (!touch) return
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const rect = canvas.getBoundingClientRect()
  ctx.lineWidth = 2.5
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#1e3a8a'
  ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top)
  ctx.stroke()
  hasSignature.value = true
}

const clearSignature = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (ctx) {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
  }
  hasSignature.value = false
  form.value.client_signature = ''
}

// Computeds
const calculatedContractDays = computed(() => {
  if (!form.value.start_datetime || !form.value.end_datetime) return 1
  const start = new Date(form.value.start_datetime).getTime()
  const end = new Date(form.value.end_datetime).getTime()
  if (end <= start) return 1
  return Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)))
})

const calculatedContractTotal = computed(() => {
  return calculatedContractDays.value * (form.value.daily_rate_dzd || 0)
})

const calculatedDrivenKm = computed(() => {
  if (!checkoutContract.value) return 0
  return Math.max(0, (checkoutForm.value.end_mileage || 0) - checkoutContract.value.start_mileage)
})

const calculatedExtraFees = computed(() => {
  return (checkoutForm.value.fuel_fee_dzd || 0) + (checkoutForm.value.extra_mileage_fee_dzd || 0) + (checkoutForm.value.damages_fee_dzd || 0)
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null
const debounceFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchContracts(1)
  }, 350)
}

const resetFilters = () => {
  filters.value = { search: '', status: '', branch_id: '' }
  fetchContracts(1)
}

// ─── API Calls ───────────────────────────────────────────────────────────────
const fetchContracts = async (page = 1) => {
  loading.value = true
  try {
    const params: Record<string, any> = { page }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id

    const res = await api.get('/contracts', { params })
    contracts.value = res.data.data || []
    if (res.data.meta?.pagination) pagination.value = res.data.meta.pagination
    if (res.data.meta?.metrics) metrics.value = res.data.meta.metrics
  } catch (err) {
    console.error('Erreur chargement contrats:', err)
  } finally {
    loading.value = false
  }
}

const fetchAuxiliary = async () => {
  try {
    const [branchesRes, custRes, vehRes, resRes] = await Promise.all([
      api.get('/core/branches'),
      api.get('/customers', { params: { per_page: 100 } }),
      api.get('/fleet/vehicles', { params: { per_page: 100 } }),
      api.get('/reservations', { params: { per_page: 50, status: 'confirmed' } }),
    ])
    branches.value = branchesRes.data.data || []
    customersList.value = custRes.data.data || []
    vehiclesList.value = vehRes.data.data || []
    pendingReservations.value = resRes.data.data || []

    if (branches.value.length > 0 && !form.value.branch_id) {
      form.value.branch_id = branches.value[0].id
      form.value.return_branch_id = branches.value[0].id
    }
  } catch (err) {
    console.error('Erreur chargement données auxiliaires:', err)
  }
}

// ─── Interactions ────────────────────────────────────────────────────────────
const openCreateModal = () => {
  modalError.value = ''
  creationMode.value = 'direct'
  form.value = {
    reservation_id: '',
    customer_id: customersList.value[0]?.id || '',
    vehicle_id: vehiclesList.value[0]?.id || '',
    branch_id: branches.value[0]?.id || '',
    return_branch_id: branches.value[0]?.id || '',
    start_datetime: toLocalIso(defaultStart),
    end_datetime: toLocalIso(defaultEnd),
    daily_rate_dzd: vehiclesList.value[0]?.daily_price_dzd || 0,
    deposit_amount_dzd: vehiclesList.value[0]?.deposit_dzd || 0,
    deposit_type: 'cheque',
    deposit_reference: '',
    start_mileage: vehiclesList.value[0]?.mileage || 0,
    start_fuel_level: '100%',
    start_notes: '',
    client_signature: '',
  }
  showCreateModal.value = true
  hasSignature.value = false
  nextTick(() => {
    clearSignature()
  })
}

const onVehicleSelect = () => {
  const v = vehiclesList.value.find(item => item.id === Number(form.value.vehicle_id))
  if (v) {
    form.value.daily_rate_dzd = Number(v.daily_price_dzd)
    form.value.deposit_amount_dzd = Number(v.deposit_dzd)
    form.value.start_mileage = Number(v.mileage || 0)
  }
}

const onReservationSelected = () => {
  const r = pendingReservations.value.find(item => item.id === Number(form.value.reservation_id))
  if (r) {
    form.value.customer_id = r.customer_id
    form.value.vehicle_id = r.vehicle_id
    form.value.branch_id = r.branch_id
    form.value.return_branch_id = r.return_branch_id || r.branch_id
    form.value.start_datetime = r.pickup_datetime.slice(0, 16)
    form.value.end_datetime = r.return_datetime.slice(0, 16)
    form.value.daily_rate_dzd = Number(r.daily_rate_dzd)
    form.value.deposit_amount_dzd = Number(r.deposit_amount_dzd)
    const veh = vehiclesList.value.find(v => v.id === r.vehicle_id)
    if (veh) form.value.start_mileage = veh.mileage || 0
  }
}

const submitCreateContract = async () => {
  modalError.value = ''
  submitting.value = true
  try {
    await api.post('/contracts', {
      ...form.value,
      customer_id: Number(form.value.customer_id),
      vehicle_id: Number(form.value.vehicle_id),
      branch_id: Number(form.value.branch_id),
      return_branch_id: Number(form.value.return_branch_id || form.value.branch_id),
      reservation_id: form.value.reservation_id ? Number(form.value.reservation_id) : undefined,
    })
    showCreateModal.value = false
    await fetchContracts(1)
  } catch (err: any) {
    modalError.value = err.response?.data?.message || err.response?.data?.error?.message || 'Erreur lors de la création du contrat'
  } finally {
    submitting.value = false
  }
}

const openCheckoutModal = (c: ContractItem) => {
  checkoutContract.value = c
  checkoutError.value = ''
  checkoutForm.value = {
    end_mileage: c.start_mileage + 150,
    end_fuel_level: '100%',
    fuel_fee_dzd: 0,
    extra_mileage_fee_dzd: 0,
    damages_fee_dzd: 0,
    end_notes: '',
  }
}

const submitCheckout = async () => {
  if (!checkoutContract.value) return
  checkoutError.value = ''
  submittingCheckout.value = true
  try {
    await api.post(`/contracts/${checkoutContract.value.id}/checkout`, checkoutForm.value)
    checkoutContract.value = null
    await fetchContracts(pagination.value.current_page)
  } catch (err: any) {
    checkoutError.value = err.response?.data?.message || err.response?.data?.error?.message || 'Erreur lors de la clôture du contrat'
  } finally {
    submittingCheckout.value = false
  }
}

const deleteContractPrompt = async (c: ContractItem) => {
  if (!confirm(`Supprimer le contrat ${c.contract_number} ?`)) return
  try {
    await api.delete(`/contracts/${c.id}`)
    await fetchContracts(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur de suppression')
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

const formatDepositType = (t: string) => {
  switch (t) {
    case 'cheque': return 'Chèque'
    case 'cash': return 'Espèces'
    case 'card': return 'Carte'
    case 'bank_transfer': return 'Virement'
    default: return t || 'Chèque'
  }
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'active': return 'Actif'
    case 'completed': return 'Clôturé'
    case 'draft': return 'Brouillon'
    case 'cancelled': return 'Annulé'
    default: return st
  }
}

onMounted(async () => {
  await fetchAuxiliary()
  await fetchContracts()
})
</script>

<style scoped>
.contracts-view {
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

.kpi-active .kpi-num { color: #3b82f6; }
.kpi-completed .kpi-num { color: #10b981; }
.kpi-revenue .kpi-num { color: #7c3aed; font-size: 1.25rem; }

.kpi-num {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.kpi-label {
  font-size: 0.8125rem;
  color: #64748b;
  margin-top: 0.125rem;
}

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

.table-responsive {
  overflow-x: auto;
}

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

/* Column Sizing */
.col-num { width: 140px; min-width: 140px; white-space: nowrap; }
.col-client { min-width: 220px; }
.col-vehicle { min-width: 210px; }
.col-branch { min-width: 140px; white-space: nowrap; }
.col-period { min-width: 220px; white-space: nowrap; }
.col-fin { min-width: 165px; white-space: nowrap; }
.col-status { min-width: 155px; white-space: nowrap; }
.col-actions { min-width: 180px; white-space: nowrap; }

.ctr-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 0.375rem;
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 700;
  font-size: 0.8125rem;
  text-decoration: none;
}
.ctr-badge:hover {
  text-decoration: underline;
}

.res-ref-hint {
  font-size: 0.6875rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.client-cell, .vehicle-cell, .period-cell, .price-cell, .status-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.client-name { color: #0f172a; font-size: 0.9rem; white-space: nowrap; }
.client-sub { display: flex; gap: 0.5rem; align-items: center; white-space: nowrap; }
.client-phone { font-size: 0.75rem; color: #64748b; }
.nin-pill { font-size: 0.6875rem; background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 0.25rem; color: #475569; }

.vehicle-title { font-weight: 600; color: #1e293b; font-size: 0.9rem; white-space: nowrap; }
.vehicle-sub { display: flex; gap: 0.45rem; align-items: center; white-space: nowrap; }

.plate-pill {
  font-size: 0.75rem;
  padding: 0.125rem 0.45rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 0.25rem;
  color: #1e293b;
}

.cat-pill {
  font-size: 0.6875rem;
  padding: 0.125rem 0.45rem;
  background: #ede9fe;
  color: #6d28d9;
  border-radius: 0.25rem;
}

.branch-pill { font-size: 0.8125rem; color: #475569; }

.period-dates { display: flex; align-items: center; gap: 0.45rem; font-size: 0.8125rem; color: #1e293b; }
.arrow-sep { color: #94a3b8; font-weight: 600; }
.mileage-sub { font-size: 0.75rem; color: #64748b; }

.total-dzd { font-weight: 700; color: #0f172a; font-size: 0.9375rem; }
.deposit-hint { font-size: 0.6875rem; color: #64748b; }

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

.status-active { background: #dbeafe; color: #1e40af; }
.status-active .status-dot { background: #3b82f6; }

.status-completed { background: #dcfce7; color: #166534; }
.status-completed .status-dot { background: #22c55e; }

.status-draft { background: #fef3c7; color: #92400e; }
.status-draft .status-dot { background: #f59e0b; }

.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-cancelled .status-dot { background: #ef4444; }

.sig-indicator { font-size: 0.6875rem; font-weight: 600; }
.text-success { color: #15803d; }
.text-warning { color: #b45309; }

.actions-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: flex-end;
}

.btn-sm { padding: 0.25rem 0.625rem; font-size: 0.75rem; border-radius: 0.375rem; }
.btn-outline-primary { border: 1px solid #3b82f6; color: #1e40af; background: transparent; }
.btn-outline-primary:hover { background: #dbeafe; }

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
.modal-lg { max-width: 800px; }

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

.form-section-title {
  display: block;
  font-size: 0.875rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.source-toggle-row {
  display: flex;
  gap: 1.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.col-span-2 { grid-column: span 2; }

.total-summary-card {
  padding: 1rem;
  background: #f1f5f9;
  border-radius: 0.5rem;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9375rem;
}

.summary-total { font-size: 1.25rem; font-weight: 700; color: #5b21b6; }

/* Signature Pad */
.sig-hint {
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.signature-pad-container {
  border: 2px dashed #cbd5e1;
  border-radius: 0.5rem;
  padding: 0.5rem;
  background: #fafafa;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.signature-canvas {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  cursor: crosshair;
  touch-action: none;
}

.sig-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  max-width: 480px;
  margin-top: 0.5rem;
}

.sig-ready-badge {
  font-size: 0.8125rem;
  font-weight: 600;
}

.checkout-summary-box {
  padding: 0.875rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.flex-between {
  display: flex;
  justify-content: space-between;
}

.text-purple { color: #6d28d9; }
.bg-light { background: #f8fafc; }
</style>

