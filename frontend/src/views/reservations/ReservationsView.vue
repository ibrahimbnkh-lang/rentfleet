<template>
  <div class="reservations-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Réservations & Engagements</h1>
        <p class="page-subtitle">Moteur de disponibilité, cycle de vie des réservations et planification de la flotte</p>
      </div>
      <div class="header-actions">
        <RouterLink to="/reservations/planning" class="btn btn-secondary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <rect x="1.5" y="1.5" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <path d="M1.5 5.5h13M5.5 9.5h5M5.5 12h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Planning Flotte
        </RouterLink>
        <button v-if="can('reservations.reservation.create')" class="btn btn-primary" @click="openCreateModal">
          <span>+</span> Nouvelle réservation
        </button>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-grid">
      <div class="card kpi-card">
        <div class="kpi-icon">📋</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.total_count }}</div>
          <div class="kpi-label">Total réservations</div>
        </div>
      </div>

      <div class="card kpi-card kpi-info-accent">
        <div class="kpi-icon">🚗</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.in_progress_count }}</div>
          <div class="kpi-label">En cours de location</div>
        </div>
      </div>

      <div class="card kpi-card kpi-success">
        <div class="kpi-icon">✅</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.confirmed_count }}</div>
          <div class="kpi-label">Confirmées à venir</div>
        </div>
      </div>

      <div class="card kpi-card kpi-warning">
        <div class="kpi-icon">⏳</div>
        <div class="kpi-info">
          <div class="kpi-num">{{ metrics.pending_count }}</div>
          <div class="kpi-label">En attente validation</div>
        </div>
      </div>

      <div class="card kpi-card kpi-revenue">
        <div class="kpi-icon">💰</div>
        <div class="kpi-info">
          <div class="kpi-num font-mono">{{ formatDzd(metrics.total_revenue_dzd) }}</div>
          <div class="kpi-label">Volume DZD prévisionnel</div>
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
            placeholder="Rechercher par référence (RES-...), client, matricule ou modèle..."
            class="input"
            @input="debounceFetch"
          />
        </div>

        <div class="filter-group">
          <select v-model="filters.status" class="input" @change="() => fetchReservations(1)">
            <option value="">Tous les statuts</option>
            <option value="confirmed">Confirmée</option>
            <option value="in_progress">En cours</option>
            <option value="pending">En attente</option>
            <option value="completed">Terminée</option>
            <option value="cancelled">Annulée</option>
          </select>
        </div>

        <div class="filter-group">
          <select v-model="filters.branch_id" class="input" @change="() => fetchReservations(1)">
            <option value="">Toutes les agences</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <input
            v-model="filters.from_date"
            type="date"
            class="input"
            title="Date de début"
            @change="() => fetchReservations(1)"
          />
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
        <p>Chargement des réservations…</p>
      </div>

      <div v-else-if="reservations.length === 0" class="empty-state">
        <div class="empty-icon">📂</div>
        <p class="empty-text">Aucune réservation ne correspond à vos critères de recherche.</p>
        <button v-if="can('reservations.reservation.create')" class="btn btn-primary mt-3" @click="openCreateModal">
          Créer une réservation
        </button>
      </div>

      <div v-else class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th class="col-ref">Référence</th>
              <th class="col-client">Client</th>
              <th class="col-vehicle">Véhicule</th>
              <th class="col-branch">Agence</th>
              <th class="col-period">Période & Durée</th>
              <th class="col-price">Montant Total</th>
              <th class="col-status">Statut</th>
              <th class="col-actions text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="res in reservations" :key="res.id">
              <!-- Reference -->
              <td class="col-ref">
                <span class="ref-badge font-mono">{{ res.reference_code }}</span>
              </td>

              <!-- Customer -->
              <td class="col-client">
                <div class="client-cell">
                  <span class="client-name font-semibold">{{ res.customer?.full_name || 'Client inconnu' }}</span>
                  <div class="client-sub">
                    <span class="client-phone font-mono">{{ res.customer?.phone }}</span>
                    <span
                      class="kyc-mini-badge"
                      :class="res.customer?.kyc_status === 'verified' ? 'verified' : res.customer?.is_blacklisted ? 'blacklisted' : 'pending'"
                    >
                      {{ res.customer?.is_blacklisted ? 'Liste Noire' : res.customer?.kyc_status === 'verified' ? 'KYC Vérifié' : 'KYC En attente' }}
                    </span>
                  </div>
                </div>
              </td>

              <!-- Vehicle -->
              <td class="col-vehicle">
                <div class="vehicle-cell">
                  <span class="vehicle-title">{{ res.vehicle?.brand }} {{ res.vehicle?.model }}</span>
                  <div class="vehicle-sub">
                    <span class="plate-pill font-mono">{{ res.vehicle?.registration_plate }}</span>
                    <span v-if="res.vehicle?.category" class="cat-pill">{{ res.vehicle?.category?.name }}</span>
                  </div>
                </div>
              </td>

              <!-- Branch -->
              <td class="col-branch">
                <span class="branch-pill">{{ res.branch?.name || 'Agence' }}</span>
              </td>

              <!-- Period -->
              <td class="col-period">
                <div class="period-cell">
                  <div class="period-dates">
                    <span class="date-part">{{ formatDate(res.pickup_datetime) }}</span>
                    <span class="arrow-sep">→</span>
                    <span class="date-part">{{ formatDate(res.return_datetime) }}</span>
                  </div>
                  <span class="badge duration-badge">{{ res.days_count }} jour{{ res.days_count > 1 ? 's' : '' }}</span>
                </div>
              </td>

              <!-- Financials -->
              <td class="col-price">
                <div class="price-cell">
                  <span class="total-dzd font-mono">{{ formatDzd(res.total_amount_dzd) }}</span>
                  <span class="daily-hint font-mono">{{ formatDzd(res.daily_rate_dzd) }} / jour</span>
                </div>
              </td>

              <!-- Status -->
              <td class="col-status">
                <span class="status-badge" :class="'status-' + res.status">
                  <span class="status-dot"></span>
                  {{ getStatusLabel(res.status) }}
                </span>
              </td>

              <!-- Actions -->
              <td class="col-actions text-right">
                <div class="actions-group">
                  <!-- Quick transitions -->
                  <button
                    v-if="res.status === 'pending' && can('reservations.reservation.confirm')"
                    class="btn btn-sm btn-outline-success"
                    title="Confirmer la réservation"
                    @click="confirmReservation(res)"
                  >
                    Confirmer
                  </button>

                  <button
                    v-if="res.status === 'confirmed' && can('reservations.reservation.start')"
                    class="btn btn-sm btn-outline-primary"
                    title="Démarrer la location (Remise des clés)"
                    @click="startRental(res)"
                  >
                    Démarrer
                  </button>

                  <button
                    v-if="res.status === 'in_progress' && can('reservations.reservation.complete')"
                    class="btn btn-sm btn-outline-accent"
                    title="Clôturer la location (Retour véhicule)"
                    @click="completeRental(res)"
                  >
                    Restitution
                  </button>

                  <!-- Direct Contract Generation -->
                  <button
                    v-if="['confirmed', 'in_progress'].includes(res.status) && can('contracts.contract.create')"
                    class="btn btn-sm btn-outline-secondary"
                    title="Établir le contrat officiel algérien"
                    @click="generateContract(res)"
                  >
                    Contrat
                  </button>

                  <!-- View details -->
                  <button
                    class="btn btn-sm btn-ghost"
                    title="Voir les détails"
                    @click="viewDetails(res)"
                  >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <path d="M1.5 8s2.5-5 6.5-5 6.5 5 6.5 5-2.5 5-6.5 5-6.5-5-6.5-5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                      <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                  </button>

                  <!-- Cancel -->
                  <button
                    v-if="['pending', 'confirmed'].includes(res.status) && can('reservations.reservation.cancel')"
                    class="btn btn-sm btn-ghost text-danger"
                    title="Annuler"
                    @click="cancelReservationPrompt(res)"
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

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="pagination-footer">
        <span class="pagination-info">
          Affichage de {{ reservations.length }} sur {{ pagination.total }} réservations
        </span>
        <div class="pagination-controls">
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            Précédent
          </button>
          <span class="page-num font-mono">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            class="btn btn-sm btn-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- ─── Modal: Create Reservation ──────────────────────────────────────── -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div>
            <h2 class="modal-title">Créer une Réservation</h2>
            <p class="modal-subtitle">Contrôle de disponibilité immédiat et vérification des conditions KYC</p>
          </div>
          <button class="modal-close" @click="showCreateModal = false">&times;</button>
        </div>

        <form @submit.prevent="submitCreateReservation">
          <div class="modal-body">
            <div v-if="modalError" class="alert alert-danger mb-4">
              {{ modalError }}
            </div>

            <!-- Client Selection & KYC check -->
            <div class="form-section">
              <span class="form-section-title">1. Locataire / Client</span>
              <div class="form-grid">
                <div class="form-group col-span-2">
                  <label class="label">Sélectionner le Client *</label>
                  <select v-model="form.customer_id" class="input" required @change="onCustomerSelect">
                    <option value="">-- Choisir un client dans le fichier central --</option>
                    <option v-for="c in customersList" :key="c.id" :value="c.id">
                      {{ c.full_name }} ({{ c.phone }}) — {{ c.is_blacklisted ? '⛔ LISTE NOIRE' : c.kyc_status === 'verified' ? '✅ KYC Vérifié' : '⚠️ KYC En attente' }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Customer Alert / Status Card -->
              <div v-if="selectedCustomer" class="customer-preview-card" :class="{ 'warning-border': selectedCustomer.kyc_status !== 'verified', 'danger-border': selectedCustomer.is_blacklisted }">
                <div class="preview-header">
                  <strong>{{ selectedCustomer.full_name }}</strong>
                  <span
                    class="badge"
                    :class="selectedCustomer.is_blacklisted ? 'badge-danger' : selectedCustomer.kyc_status === 'verified' ? 'badge-success' : 'badge-warning'"
                  >
                    {{ selectedCustomer.is_blacklisted ? 'Inscrit sur liste noire' : selectedCustomer.kyc_status === 'verified' ? 'Éligible à la location' : 'Dossier KYC incomplet' }}
                  </span>
                </div>
                <div class="preview-details">
                  <span>Permis : {{ selectedCustomer.driver_license_number || 'Non renseigné' }}</span>
                  <span>Ancienneté permis : {{ selectedCustomer.license_seniority_years !== null ? selectedCustomer.license_seniority_years + ' an(s)' : 'Inconnue' }}</span>
                  <span>Âge : {{ selectedCustomer.age ? selectedCustomer.age + ' ans' : 'Inconnu' }}</span>
                  <span>Wilaya : {{ selectedCustomer.wilaya_code || '16' }}</span>
                </div>
                <div v-if="selectedCustomer.is_blacklisted" class="preview-warning text-danger">
                  ⛔ ATTENTION : Ce client est inscrit sur liste noire pour incident ou impayé.
                </div>
                <div v-else-if="selectedCustomer.kyc_status !== 'verified'" class="preview-warning text-warning">
                  ⚠️ Note : Les pièces KYC du client ne sont pas encore validées. Vous pourrez quand même créer la réservation, mais la validation sera requise avant remise du véhicule.
                </div>
              </div>
            </div>

            <!-- Dates & Agency -->
            <div class="form-section mt-4">
              <span class="form-section-title">2. Dates & Agence de retrait</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Agence de retrait *</label>
                  <select v-model="form.branch_id" class="input" required @change="onPeriodChange">
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
                  <input
                    v-model="form.pickup_datetime"
                    type="datetime-local"
                    class="input"
                    required
                    @change="onPeriodChange"
                  />
                </div>

                <div class="form-group">
                  <label class="label">Date & Heure de retour *</label>
                  <input
                    v-model="form.return_datetime"
                    type="datetime-local"
                    class="input"
                    required
                    @change="onPeriodChange"
                  />
                </div>
              </div>

              <!-- Calculated Duration -->
              <div v-if="calculatedDays > 0" class="duration-display">
                Durée calculée : <strong class="badge duration-badge">{{ calculatedDays }} jour{{ calculatedDays > 1 ? 's' : '' }}</strong>
              </div>
            </div>

            <!-- Vehicle Selection with Real-time Availability -->
            <div class="form-section mt-4">
              <div class="section-title-row">
                <span class="form-section-title">3. Sélection du Véhicule Disponible</span>
                <span v-if="checkingAvailability" class="avail-loading">Recherche des véhicules disponibles…</span>
              </div>

              <div v-if="availableVehicles.length === 0 && !checkingAvailability" class="alert alert-warning">
                Aucun véhicule n'est disponible pour ces dates et cette agence. Modifiez la plage horaire ou l'agence.
              </div>

              <div v-else class="vehicle-picker-grid">
                <div
                  v-for="v in availableVehicles"
                  :key="v.id"
                  class="vehicle-select-card"
                  :class="{ selected: form.vehicle_id === v.id }"
                  @click="selectVehicle(v)"
                >
                  <div class="veh-card-header">
                    <strong>{{ v.brand }} {{ v.model }}</strong>
                    <span class="badge cat-badge">{{ v.category?.name }}</span>
                  </div>
                  <div class="veh-card-sub font-mono">
                    {{ v.registration_plate }}
                  </div>
                  <div class="veh-card-footer">
                    <span class="veh-price font-mono">{{ formatDzd(v.daily_price_dzd) }} <small>/jour</small></span>
                    <span class="veh-caution font-mono">Caution: {{ formatDzd(v.deposit_dzd) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Financial Summary & Notes -->
            <div class="form-section mt-4">
              <span class="form-section-title">4. Tarification & Notes</span>
              <div class="form-grid">
                <div class="form-group">
                  <label class="label">Tarif journalier négocié (DZD)</label>
                  <input
                    v-model.number="form.daily_rate_dzd"
                    type="number"
                    step="100"
                    class="input font-mono"
                    placeholder="Tarif catalogue automatique"
                  />
                </div>

                <div class="form-group">
                  <label class="label">Caution / Dépôt de garantie (DZD)</label>
                  <input
                    v-model.number="form.deposit_amount_dzd"
                    type="number"
                    step="1000"
                    class="input font-mono"
                  />
                </div>

                <div class="form-group col-span-2">
                  <label class="label">Notes / Modalités particulières</label>
                  <textarea
                    v-model="form.notes"
                    rows="2"
                    class="input"
                    placeholder="ex: Versement d'acompte, chèque de garantie, options supplémentaires, etc."
                  ></textarea>
                </div>
              </div>

              <!-- Total calculation card -->
              <div class="total-summary-card">
                <div class="summary-line">
                  <span>Calcul : {{ calculatedDays }} jours &times; {{ formatDzd(form.daily_rate_dzd || 0) }}</span>
                  <span class="summary-total font-mono">{{ formatDzd(calculatedTotal) }}</span>
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
              :disabled="submitting || !form.vehicle_id || !form.customer_id || calculatedDays <= 0"
            >
              {{ submitting ? 'Validation en cours...' : 'Valider la réservation' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ─── Modal: Reservation Detail / Slide-over ────────────────────────── -->
    <div v-if="selectedReservationDetail" class="modal-backdrop" @click.self="selectedReservationDetail = null">
      <div class="modal modal-md detail-modal">
        <div class="modal-header">
          <div>
            <div class="modal-header-tag">
              <span class="ref-badge font-mono">{{ selectedReservationDetail.reference_code }}</span>
              <span class="status-badge" :class="'status-' + selectedReservationDetail.status">
                {{ getStatusLabel(selectedReservationDetail.status) }}
              </span>
            </div>
            <h2 class="modal-title mt-1">Détail de la Réservation</h2>
          </div>
          <button class="modal-close" @click="selectedReservationDetail = null">&times;</button>
        </div>

        <div class="modal-body">
          <!-- Client Card -->
          <div class="detail-section">
            <div class="section-title">Locataire</div>
            <div class="client-detail-card">
              <div class="name-row">
                <strong>{{ selectedReservationDetail.customer?.full_name }}</strong>
                <span class="client-type-tag">{{ selectedReservationDetail.customer?.type === 'company' ? 'Entreprise' : 'Particulier' }}</span>
              </div>
              <div class="client-meta">
                <span>📞 {{ selectedReservationDetail.customer?.phone }}</span>
                <span v-if="selectedReservationDetail.customer?.email">✉️ {{ selectedReservationDetail.customer?.email }}</span>
                <span>📍 {{ selectedReservationDetail.customer?.city || 'Alger' }} (W.{{ selectedReservationDetail.customer?.wilaya_code || '16' }})</span>
              </div>
              <div class="client-kyc-check mt-2">
                <span
                  class="badge"
                  :class="selectedReservationDetail.customer?.kyc_status === 'verified' ? 'badge-success' : 'badge-warning'"
                >
                  KYC : {{ selectedReservationDetail.customer?.kyc_status === 'verified' ? 'Vérifié & Conforme' : 'En attente' }}
                </span>
                <span class="ml-2 font-mono text-sm">NIN : {{ selectedReservationDetail.customer?.national_id_number || 'Non renseigné' }}</span>
              </div>
            </div>
          </div>

          <!-- Vehicle Card -->
          <div class="detail-section mt-4">
            <div class="section-title">Véhicule assigné</div>
            <div class="vehicle-detail-card">
              <div class="veh-title">
                {{ selectedReservationDetail.vehicle?.brand }} {{ selectedReservationDetail.vehicle?.model }}
                <span class="veh-year text-muted">({{ selectedReservationDetail.vehicle?.year }})</span>
              </div>
              <div class="veh-tags">
                <span class="plate-pill font-mono">{{ selectedReservationDetail.vehicle?.registration_plate }}</span>
                <span class="cat-pill">{{ selectedReservationDetail.vehicle?.category?.name }}</span>
                <span class="fuel-pill">{{ selectedReservationDetail.vehicle?.fuel_type }}</span>
              </div>
            </div>
          </div>

          <!-- Rental Period -->
          <div class="detail-section mt-4">
            <div class="section-title">Période & Agences</div>
            <div class="period-box">
              <div class="period-item">
                <div class="period-label">Départ</div>
                <div class="period-val">{{ formatDateTime(selectedReservationDetail.pickup_datetime) }}</div>
                <div class="period-branch">{{ selectedReservationDetail.branch?.name }}</div>
              </div>
              <div class="period-arrow">
                <span class="badge duration-badge">{{ selectedReservationDetail.days_count }} jours</span>
                <span>→</span>
              </div>
              <div class="period-item text-right">
                <div class="period-label">Retour</div>
                <div class="period-val">{{ formatDateTime(selectedReservationDetail.return_datetime) }}</div>
                <div class="period-branch">{{ selectedReservationDetail.return_branch?.name || selectedReservationDetail.branch?.name }}</div>
              </div>
            </div>
          </div>

          <!-- Financial Breakdown -->
          <div class="detail-section mt-4">
            <div class="section-title">Règlement & Tarification</div>
            <div class="fin-card">
              <div class="fin-row">
                <span>Tarif journalier appliqué</span>
                <span class="font-mono">{{ formatDzd(selectedReservationDetail.daily_rate_dzd) }}</span>
              </div>
              <div class="fin-row">
                <span>Durée facturée</span>
                <span>{{ selectedReservationDetail.days_count }} jours</span>
              </div>
              <div class="fin-row total-row">
                <strong>Montant Total</strong>
                <strong class="font-mono font-bold">{{ formatDzd(selectedReservationDetail.total_amount_dzd) }}</strong>
              </div>
              <div class="fin-row deposit-row text-muted">
                <span>Dépôt de garantie / Caution</span>
                <span class="font-mono">{{ formatDzd(selectedReservationDetail.deposit_amount_dzd) }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedReservationDetail.notes" class="detail-section mt-4">
            <div class="section-title">Remarques & Notes</div>
            <div class="notes-box">
              {{ selectedReservationDetail.notes }}
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="selectedReservationDetail = null">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
function can(permission: string) {
  return auth.hasRole('Super Admin') || auth.hasPermission(permission)
}

interface BranchItem {
  id: number
  name: string
  code: string
}

interface CustomerOption {
  id: number
  full_name: string
  phone: string
  kyc_status: string
  is_blacklisted: boolean
  driver_license_number?: string
  license_seniority_years?: number
  age?: number
  wilaya_code?: string
  type: string
  email?: string
  national_id_number?: string
  city?: string
}

interface VehicleOption {
  id: number
  brand: string
  model: string
  registration_plate: string
  daily_price_dzd: number
  deposit_dzd: number
  category?: { name: string }
  branch_id?: number
  year?: number
  fuel_type?: string
}

interface ReservationItem {
  id: number
  reference_code: string
  customer_id: number
  vehicle_id: number
  branch_id: number
  return_branch_id?: number
  pickup_datetime: string
  return_datetime: string
  days_count: number
  status: string
  daily_rate_dzd: number
  total_amount_dzd: number
  deposit_amount_dzd: number
  notes?: string
  customer?: CustomerOption
  vehicle?: VehicleOption
  branch?: BranchItem
  return_branch?: BranchItem
  created_at: string
}

const reservations = ref<ReservationItem[]>([])
const branches = ref<BranchItem[]>([])
const customersList = ref<CustomerOption[]>([])
const availableVehicles = ref<VehicleOption[]>([])

const loading = ref(false)
const submitting = ref(false)
const checkingAvailability = ref(false)
const showCreateModal = ref(false)
const selectedReservationDetail = ref<ReservationItem | null>(null)
const modalError = ref('')

const metrics = ref({
  total_count: 0,
  confirmed_count: 0,
  in_progress_count: 0,
  pending_count: 0,
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
  from_date: '',
})

// Form state
const defaultPickup = new Date()
defaultPickup.setHours(defaultPickup.getHours() + 2, 0, 0, 0)
const defaultReturn = new Date()
defaultReturn.setDate(defaultReturn.getDate() + 3)
defaultReturn.setHours(defaultPickup.getHours(), 0, 0, 0)

const toLocalIso = (d: Date) => {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const form = ref({
  customer_id: '' as string | number,
  vehicle_id: '' as string | number,
  branch_id: '' as string | number,
  return_branch_id: '' as string | number,
  pickup_datetime: toLocalIso(defaultPickup),
  return_datetime: toLocalIso(defaultReturn),
  daily_rate_dzd: 0,
  deposit_amount_dzd: 0,
  notes: '',
})

const selectedCustomer = computed(() => {
  if (!form.value.customer_id) return null
  return customersList.value.find(c => c.id === Number(form.value.customer_id)) || null
})

const calculatedDays = computed(() => {
  if (!form.value.pickup_datetime || !form.value.return_datetime) return 0
  const start = new Date(form.value.pickup_datetime).getTime()
  const end = new Date(form.value.return_datetime).getTime()
  if (end <= start) return 0
  const hours = (end - start) / (1000 * 60 * 60)
  return Math.max(1, Math.ceil(hours / 24))
})

const calculatedTotal = computed(() => {
  return calculatedDays.value * (form.value.daily_rate_dzd || 0)
})

let debounceTimer: ReturnType<typeof setTimeout> | null = null
const debounceFetch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchReservations(1)
  }, 350)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    branch_id: '',
    from_date: '',
  }
  fetchReservations(1)
}

// ─── API Calls ───────────────────────────────────────────────────────────────
const fetchReservations = async (page = 1) => {
  loading.value = true
  try {
    const params: Record<string, any> = { page }
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id
    if (filters.value.from_date) params.from_date = filters.value.from_date

    const res = await api.get('/reservations', { params })
    reservations.value = res.data.data || []
    if (res.data.meta?.pagination) {
      pagination.value = res.data.meta.pagination
    }
    if (res.data.meta?.metrics) {
      metrics.value = res.data.meta.metrics
    }
  } catch (err) {
    console.error('Erreur chargement réservations:', err)
  } finally {
    loading.value = false
  }
}

const fetchAuxiliaryData = async () => {
  try {
    const [branchesRes, customersRes] = await Promise.all([
      api.get('/core/branches'),
      api.get('/customers', { params: { per_page: 100 } }),
    ])
    branches.value = branchesRes.data.data || []
    customersList.value = customersRes.data.data || []

    if (branches.value.length > 0 && !form.value.branch_id) {
      form.value.branch_id = branches.value[0].id
      form.value.return_branch_id = branches.value[0].id
    }
  } catch (err) {
    console.error('Erreur chargement données auxiliaires:', err)
  }
}

const queryAvailableVehicles = async () => {
  if (!form.value.pickup_datetime || !form.value.return_datetime) return
  checkingAvailability.value = true
  try {
    const res = await api.get('/reservations/availability', {
      params: {
        pickup_datetime: form.value.pickup_datetime,
        return_datetime: form.value.return_datetime,
        branch_id: form.value.branch_id || undefined,
      },
    })
    availableVehicles.value = res.data.data || []
  } catch (err) {
    console.error('Erreur chargement disponibilité véhicules:', err)
    availableVehicles.value = []
  } finally {
    checkingAvailability.value = false
  }
}

// ─── Interactions ────────────────────────────────────────────────────────────
const openCreateModal = () => {
  modalError.value = ''
  form.value = {
    customer_id: customersList.value[0]?.id || '',
    vehicle_id: '',
    branch_id: branches.value[0]?.id || '',
    return_branch_id: branches.value[0]?.id || '',
    pickup_datetime: toLocalIso(defaultPickup),
    return_datetime: toLocalIso(defaultReturn),
    daily_rate_dzd: 0,
    deposit_amount_dzd: 0,
    notes: '',
  }
  showCreateModal.value = true
  queryAvailableVehicles()
}

const onPeriodChange = () => {
  queryAvailableVehicles()
}

const onCustomerSelect = () => {
  // Can trigger customer KYC refresh
}

const selectVehicle = (v: VehicleOption) => {
  form.value.vehicle_id = v.id
  form.value.daily_rate_dzd = Number(v.daily_price_dzd)
  form.value.deposit_amount_dzd = Number(v.deposit_dzd)
}

const submitCreateReservation = async () => {
  modalError.value = ''
  submitting.value = true
  try {
    await api.post('/reservations', {
      customer_id: Number(form.value.customer_id),
      vehicle_id: Number(form.value.vehicle_id),
      branch_id: Number(form.value.branch_id),
      return_branch_id: Number(form.value.return_branch_id || form.value.branch_id),
      pickup_datetime: form.value.pickup_datetime,
      return_datetime: form.value.return_datetime,
      daily_rate_dzd: form.value.daily_rate_dzd,
      deposit_amount_dzd: form.value.deposit_amount_dzd,
      notes: form.value.notes,
      status: 'confirmed',
    })
    showCreateModal.value = false
    await fetchReservations(1)
  } catch (err: any) {
    modalError.value = err.response?.data?.message || err.response?.data?.error?.message || 'Erreur lors de la création de la réservation'
  } finally {
    submitting.value = false
  }
}

const confirmReservation = async (res: ReservationItem) => {
  try {
    await api.post(`/reservations/${res.id}/confirm`)
    await fetchReservations(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur de confirmation')
  }
}

const startRental = async (res: ReservationItem) => {
  if (!confirm(`Démarrer la location pour ${res.reference_code} ? Le véhicule passera en statut "Loué".`)) return
  try {
    await api.post(`/reservations/${res.id}/start`)
    await fetchReservations(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur de démarrage de location')
  }
}

const completeRental = async (res: ReservationItem) => {
  if (!confirm(`Confirmer le retour du véhicule pour ${res.reference_code} ? Le véhicule redeviendra disponible.`)) return
  try {
    await api.post(`/reservations/${res.id}/complete`)
    await fetchReservations(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur de clôture')
  }
}

const generateContract = async (res: ReservationItem) => {
  try {
    const response = await api.post(`/contracts/from-reservation/${res.id}`)
    const contract = response.data.data
    router.push(`/contracts/${contract.id}`)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la génération du contrat')
  }
}

const cancelReservationPrompt = async (res: ReservationItem) => {
  const reason = prompt(`Motif d'annulation pour la réservation ${res.reference_code} :`, 'Annulation à la demande du client')
  if (reason === null) return
  try {
    await api.post(`/reservations/${res.id}/cancel`, { reason })
    await fetchReservations(pagination.value.current_page)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur d\'annulation')
  }
}

const viewDetails = (res: ReservationItem) => {
  selectedReservationDetail.value = res
}

const goToPage = (page: number) => {
  fetchReservations(page)
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
  const d = new Date(iso)
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatDateTime = (iso: string) => {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const getStatusLabel = (st: string) => {
  switch (st) {
    case 'confirmed': return 'Confirmée'
    case 'in_progress': return 'En cours'
    case 'pending': return 'En attente'
    case 'completed': return 'Terminée'
    case 'cancelled': return 'Annulée'
    default: return st
  }
}

onMounted(async () => {
  await fetchAuxiliaryData()
  await fetchReservations()
})
</script>

<style scoped>
.reservations-view {
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
  color: var(--color-slate-900, #0f172a);
}

.page-subtitle {
  color: var(--color-slate-500, #64748b);
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
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
  border: 1px solid var(--color-slate-200, #e2e8f0);
  border-radius: 0.75rem;
}

.kpi-icon {
  font-size: 1.75rem;
  background: var(--color-slate-50, #f8fafc);
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
}

.kpi-info-accent .kpi-num {
  color: #3b82f6;
}

.kpi-success .kpi-num {
  color: #10b981;
}

.kpi-warning .kpi-num {
  color: #f59e0b;
}

.kpi-revenue .kpi-num {
  color: #7c3aed;
  font-size: 1.25rem;
}

.kpi-num {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.kpi-label {
  font-size: 0.8125rem;
  color: var(--color-slate-500, #64748b);
  margin-top: 0.125rem;
}

/* Filters */
.filters-card {
  padding: 1rem 1.25rem;
  background: #ffffff;
  border: 1px solid var(--color-slate-200, #e2e8f0);
  border-radius: 0.75rem;
}

.filters-row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.filter-group {
  display: flex;
}

.flex-1 {
  flex: 1;
}

.btn-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0.75rem;
}

/* Table */
.table-card {
  background: #ffffff;
  border: 1px solid var(--color-slate-200, #e2e8f0);
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
  background: var(--color-slate-50, #f8fafc);
  padding: 0.95rem 1.15rem;
  font-weight: 600;
  font-size: 0.8125rem;
  color: var(--color-slate-600, #475569);
  border-bottom: 1px solid var(--color-slate-200, #e2e8f0);
  white-space: nowrap;
}

.table td {
  padding: 0.95rem 1.15rem;
  border-bottom: 1px solid var(--color-slate-100, #f1f5f9);
  vertical-align: middle;
}

/* Explicit Column Sizing & Spacing */
.col-ref {
  width: 140px;
  min-width: 140px;
  white-space: nowrap;
}

.col-client {
  min-width: 220px;
}

.col-vehicle {
  min-width: 210px;
}

.col-branch {
  min-width: 140px;
  white-space: nowrap;
}

.col-period {
  min-width: 215px;
  white-space: nowrap;
}

.col-price {
  min-width: 155px;
  white-space: nowrap;
}

.col-status {
  min-width: 130px;
  white-space: nowrap;
}

.col-actions {
  min-width: 180px;
  white-space: nowrap;
}

.ref-badge {
  display: inline-block;
  padding: 0.25rem 0.55rem;
  border-radius: 0.375rem;
  background: #f1f5f9;
  color: #334155;
  font-weight: 600;
  font-size: 0.8125rem;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.client-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.client-name {
  color: #0f172a;
  font-size: 0.9rem;
  white-space: nowrap;
}

.client-sub {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.client-phone {
  font-size: 0.75rem;
  color: #64748b;
  white-space: nowrap;
}

.kyc-mini-badge {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.125rem 0.45rem;
  border-radius: 0.25rem;
  white-space: nowrap;
}

.kyc-mini-badge.verified {
  background: #dcfce7;
  color: #15803d;
}

.kyc-mini-badge.pending {
  background: #fef3c7;
  color: #b45309;
}

.kyc-mini-badge.blacklisted {
  background: #fee2e2;
  color: #b91c1c;
}

.vehicle-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.vehicle-title {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
  white-space: nowrap;
}

.vehicle-sub {
  display: flex;
  gap: 0.45rem;
  align-items: center;
  white-space: nowrap;
}

.plate-pill {
  font-size: 0.75rem;
  padding: 0.125rem 0.45rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 0.25rem;
  color: #1e293b;
  white-space: nowrap;
}

.cat-pill {
  font-size: 0.6875rem;
  padding: 0.125rem 0.45rem;
  background: #ede9fe;
  color: #6d28d9;
  border-radius: 0.25rem;
  white-space: nowrap;
}

.branch-pill {
  font-size: 0.8125rem;
  color: #475569;
  white-space: nowrap;
}

.period-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  white-space: nowrap;
}

.period-dates {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8125rem;
  color: #1e293b;
  white-space: nowrap;
}

.date-part {
  font-weight: 500;
}

.arrow-sep {
  color: #94a3b8;
  font-weight: 600;
}

.duration-badge {
  background: #e0f2fe;
  color: #0369a1;
  font-size: 0.6875rem;
  padding: 0.15rem 0.5rem;
  border-radius: 0.25rem;
  width: fit-content;
  white-space: nowrap;
}

.price-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  white-space: nowrap;
}

.total-dzd {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.9375rem;
  white-space: nowrap;
}

.daily-hint {
  font-size: 0.6875rem;
  color: #64748b;
  white-space: nowrap;
}

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
}

.status-confirmed {
  background: #dcfce7;
  color: #166534;
}
.status-confirmed .status-dot {
  background: #22c55e;
}

.status-in_progress {
  background: #dbeafe;
  color: #1e40af;
}
.status-in_progress .status-dot {
  background: #3b82f6;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}
.status-pending .status-dot {
  background: #f59e0b;
}

.status-completed {
  background: #f1f5f9;
  color: #475569;
}
.status-completed .status-dot {
  background: #94a3b8;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}
.status-cancelled .status-dot {
  background: #ef4444;
}

.actions-group {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  justify-content: flex-end;
  white-space: nowrap;
}

.btn-sm {
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
  border-radius: 0.375rem;
}

.btn-outline-success {
  border: 1px solid #22c55e;
  color: #166534;
  background: transparent;
}
.btn-outline-success:hover {
  background: #dcfce7;
}

.btn-outline-primary {
  border: 1px solid #3b82f6;
  color: #1e40af;
  background: transparent;
}
.btn-outline-primary:hover {
  background: #dbeafe;
}

.btn-outline-accent {
  border: 1px solid #8b5cf6;
  color: #6d28d9;
  background: transparent;
}
.btn-outline-accent:hover {
  background: #ede9fe;
}

.btn-ghost {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #64748b;
  padding: 0.375rem;
  border-radius: 0.375rem;
}
.btn-ghost:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.text-danger {
  color: #ef4444 !important;
}

.loading-state, .empty-state {
  padding: 3rem;
  text-align: center;
}

.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid var(--color-slate-200, #e2e8f0);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

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
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-md {
  max-width: 580px;
}

.modal-lg {
  max-width: 780px;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin-top: 0.125rem;
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

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

.form-section {
  background: #ffffff;
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

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.col-span-2 {
  grid-column: span 2;
}

/* Customer preview */
.customer-preview-card {
  margin-top: 0.75rem;
  padding: 0.875rem;
  border-radius: 0.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-size: 0.8125rem;
}

.customer-preview-card.warning-border {
  border-color: #f59e0b;
  background: #fffbeb;
}

.customer-preview-card.danger-border {
  border-color: #ef4444;
  background: #fef2f2;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.preview-details {
  display: flex;
  gap: 1rem;
  color: #64748b;
  flex-wrap: wrap;
}

.preview-warning {
  margin-top: 0.5rem;
  font-weight: 600;
}

.duration-display {
  margin-top: 0.75rem;
  font-size: 0.875rem;
  color: #334155;
}

.section-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.avail-loading {
  font-size: 0.75rem;
  color: #6366f1;
}

/* Vehicle picker */
.vehicle-picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 0.75rem;
  margin-top: 0.75rem;
  max-height: 220px;
  overflow-y: auto;
  padding: 2px;
}

.vehicle-select-card {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.15s ease;
}

.vehicle-select-card:hover {
  border-color: #a5b4fc;
}

.vehicle-select-card.selected {
  border-color: #6366f1;
  background: #f5f3ff;
}

.veh-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  font-size: 0.875rem;
}

.cat-badge {
  background: #ede9fe;
  color: #6d28d9;
  font-size: 0.6875rem;
}

.veh-card-sub {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

.veh-card-footer {
  margin-top: 0.5rem;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.veh-price {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.875rem;
}

.veh-caution {
  font-size: 0.6875rem;
  color: #94a3b8;
}

/* Total summary card */
.total-summary-card {
  margin-top: 1rem;
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

.summary-total {
  font-size: 1.25rem;
  font-weight: 700;
  color: #5b21b6;
}

/* Detail modal elements */
.modal-header-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-section .section-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.client-detail-card, .vehicle-detail-card, .fin-card, .period-box {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
}

.name-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.client-type-tag {
  font-size: 0.75rem;
  background: #e2e8f0;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
}

.client-meta {
  display: flex;
  gap: 0.75rem;
  font-size: 0.8125rem;
  color: #64748b;
  margin-top: 0.375rem;
  flex-wrap: wrap;
}

.veh-title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.veh-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.fuel-pill {
  font-size: 0.6875rem;
  background: #ecfdf5;
  color: #047857;
  padding: 0.125rem 0.375rem;
  border-radius: 0.25rem;
}

.period-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.period-val {
  font-weight: 700;
  color: #0f172a;
  font-size: 0.9375rem;
  margin-top: 0.125rem;
}

.period-branch {
  font-size: 0.75rem;
  color: #64748b;
}

.period-arrow {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  color: #94a3b8;
  font-size: 1.25rem;
}

.fin-row {
  display: flex;
  justify-content: space-between;
  padding: 0.375rem 0;
  font-size: 0.875rem;
}

.total-row {
  border-top: 1px solid #cbd5e1;
  margin-top: 0.375rem;
  padding-top: 0.5rem;
  font-size: 1rem;
  color: #5b21b6;
}

.notes-box {
  padding: 0.75rem 1rem;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #92400e;
}
</style>
