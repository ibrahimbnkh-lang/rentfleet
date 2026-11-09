<template>
  <div class="vehicles-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-title-row">
          <h1 class="page-title">Gestion de la Flotte</h1>
          <span
            v-if="quota"
            :class="['badge font-mono', quota.is_reached ? 'badge-error' : 'badge-neutral']"
            :title="`Quota licence : ${quota.current} sur ${quota.max} autorisés`"
          >
            Quota : {{ quota.current }} / {{ quota.max }}
          </span>
        </div>
        <p class="page-subtitle">Inventaire des véhicules, affectations par agence et suivi des disponibilités</p>
      </div>
      <button v-if="can('fleet.vehicle.create')" class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouveau véhicule
      </button>
    </div>

    <!-- Filters -->
    <div class="card filters-card">
      <div class="filters-row">
        <div class="filter-group flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par marque, modèle, immatriculation ou VIN..."
            class="input"
            @input="debounceFetch"
          />
        </div>
        <div class="filter-group w-48">
          <select v-model="filters.branch_id" class="input" @change="fetchVehicles">
            <option value="">Toutes les agences</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">
              {{ b.name }}
            </option>
          </select>
        </div>
        <div class="filter-group w-40">
          <select v-model="filters.category_id" class="input" @change="fetchVehicles">
            <option value="">Toutes catégories</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>
        <div class="filter-group w-36">
          <select v-model="filters.status" class="input" @change="fetchVehicles">
            <option value="">Tous les statuts</option>
            <option value="available">Disponible</option>
            <option value="rented">En location</option>
            <option value="maintenance">Maintenance</option>
            <option value="reserved">Réservé</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading / Empty -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement du parc de véhicules...</p>
    </div>

    <div v-else-if="vehicles.length === 0" class="card empty-state">
      <div class="empty-icon">🚗</div>
      <h3>Aucun véhicule trouvé</h3>
      <p>Ajustez vos filtres ou ajoutez votre premier véhicule au parc.</p>
      <button v-if="can('fleet.vehicle.create')" class="btn btn-primary btn-sm mt-3" @click="openModal()">+ Ajouter un véhicule</button>
    </div>

    <!-- Vehicles Grid -->
    <div v-else class="vehicles-grid">
      <div v-for="v in vehicles" :key="v.id" class="card vehicle-card">
        <!-- Vehicle Photo -->
        <div class="vehicle-photo-wrap">
          <img
            :src="getVehiclePhoto(v)"
            :alt="`${v.brand} ${v.model}`"
            class="vehicle-photo"
            @error="(e) => ((e.target as HTMLImageElement).src = getVehiclePlaceholder(v.category?.name))"
          />
          <span :class="['status-overlay-badge', 'badge', getStatusBadgeClass(v.status)]">
            {{ formatStatus(v.status) }}
          </span>
          <span v-if="v.photos?.length" class="photos-count-badge" title="Photos réelles enregistrées">
            📷 {{ v.photos.length }}
          </span>
        </div>

        <div class="vehicle-card-body">
          <div class="card-top">
            <div class="category-badge">{{ v.category?.name ?? 'Non classé' }}</div>
          </div>

          <div class="vehicle-title-wrap">
            <h3 class="vehicle-name">{{ v.brand }} {{ v.model }}</h3>
            <span class="vehicle-year">{{ v.year }}</span>
          </div>

          <div class="plate-box">
            <span class="dz-flag">🇩🇿</span>
            <span class="plate-number">{{ v.registration_plate }}</span>
          </div>

          <div class="vehicle-specs-list">
            <div class="spec-row">
              <span>🏢 Agence :</span>
              <strong>{{ v.branch?.name ?? '—' }}</strong>
            </div>
            <div class="spec-row">
              <span>⚙️ Transmission :</span>
              <span>{{ v.transmission === 'automatique' ? 'Automatique' : 'Manuelle' }} • {{ v.fuel_type }}</span>
            </div>
            <div class="spec-row">
              <span>🛣️ Kilométrage :</span>
              <span>{{ formatNumber(v.mileage) }} km</span>
            </div>
          </div>

          <div class="vehicle-card-footer">
            <div class="price-row">
              <div class="price-indicator">
                <span class="price-val">{{ formatNumber(v.daily_price_dzd || v.category?.daily_rate_dzd || 5000) }}</span>
                <span class="price-cur">DZD / jour</span>
              </div>
              <div v-if="v.deposit_dzd" class="deposit-chip">
                Caution : {{ formatNumber(v.deposit_dzd) }} DZD
              </div>
            </div>

            <div class="actions-group">
              <RouterLink :to="`/fleet/vehicles/${v.id}`" class="card-btn btn-details">
                <span>📄</span> Fiche
              </RouterLink>
              <button v-if="can('fleet.vehicle.update')" class="card-btn btn-edit" @click="openModal(v)">
                <span>✏️</span> Modifier
              </button>
              <button v-if="can('fleet.vehicle.delete')" class="card-btn btn-delete" title="Supprimer ce véhicule" @click="deleteVehicle(v)">
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Create / Edit Vehicle -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content card max-w-2xl">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <span class="modal-title-icon">{{ editingId ? '✏️' : '🚗' }}</span>
            <div>
              <h3>{{ editingId ? `Modifier le véhicule : ${form.brand} ${form.model}` : 'Ajouter un nouveau véhicule' }}</h3>
              <p class="modal-subtitle">Renseignez les détails du véhicule, les tarifs et la photo</p>
            </div>
          </div>
          <button class="modal-close-btn" @click="closeModal" title="Fermer">✕</button>
        </div>

        <form @submit.prevent="saveVehicle" class="modal-form">
          <div class="modal-body-scroll">
            <div v-if="!editingId && quota?.is_reached" class="modal-error mb-4">
              ⚠️ <strong>Quota licence atteint :</strong> Votre licence autorise un maximum de {{ quota.max }} véhicules. Vous ne pouvez pas en ajouter davantage sans renouveler ou mettre à niveau votre licence.
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="label">Marque *</label>
                <input v-model="form.brand" required class="input" placeholder="ex: Renault, Dacia, Hyundai..." />
              </div>

              <div class="form-group">
                <label class="label">Modèle *</label>
                <input v-model="form.model" required class="input" placeholder="ex: Symbol, Duster, Tucson..." />
              </div>

              <div class="form-group">
                <label class="label">Catégorie *</label>
                <select v-model="form.category_id" required class="input">
                  <option value="">Sélectionner une catégorie</option>
                  <option v-for="c in categories" :key="c.id" :value="c.id">
                    {{ c.name }} (Base : {{ formatNumber(c.daily_rate_dzd) }} DZD)
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Agence d'affectation *</label>
                <select v-model="form.branch_id" required class="input">
                  <option value="">Sélectionner une agence</option>
                  <option v-for="b in branches" :key="b.id" :value="b.id">
                    {{ b.name }} (Wilaya {{ b.wilaya_code }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Plaque d'immatriculation *</label>
                <input
                  v-model="form.registration_plate"
                  required
                  class="input font-mono"
                  placeholder="ex: 01234-123-16"
                />
              </div>

              <div class="form-group">
                <label class="label">Numéro de châssis (VIN) *</label>
                <input
                  v-model="form.vin"
                  required
                  class="input font-mono text-xs uppercase"
                  placeholder="17 caractères"
                />
              </div>

              <div class="form-group">
                <label class="label">Année de mise en circulation *</label>
                <input v-model.number="form.year" type="number" min="2000" max="2027" required class="input" />
              </div>

              <div class="form-group">
                <label class="label">Kilométrage actuel (km) *</label>
                <input v-model.number="form.mileage" type="number" min="0" required class="input" />
              </div>

              <div class="form-group">
                <label class="label">Motorisation / Carburant *</label>
                <select v-model="form.fuel_type" class="input">
                  <option value="essence">Essence</option>
                  <option value="diesel">Diesel</option>
                  <option value="hybride">Hybride</option>
                  <option value="gpl">GPL (Gaz)</option>
                  <option value="electrique">Électrique</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Boîte de vitesses *</label>
                <select v-model="form.transmission" class="input">
                  <option value="manuelle">Manuelle</option>
                  <option value="automatique">Automatique</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Prix journalier spécifique (DZD)</label>
                <input
                  v-model.number="form.daily_price_dzd"
                  type="number"
                  min="0"
                  class="input"
                  placeholder="Laisser vide pour utiliser le prix de la catégorie"
                />
              </div>

              <div class="form-group">
                <label class="label">Montant de caution (DZD)</label>
                <input
                  v-model.number="form.deposit_dzd"
                  type="number"
                  min="0"
                  class="input"
                  placeholder="ex: 50000"
                />
              </div>

              <div class="form-group">
                <label class="label">Statut du véhicule</label>
                <select v-model="form.status" class="input">
                  <option value="available">🟢 Disponible à la location</option>
                  <option value="rented">🟡 En cours de location</option>
                  <option value="maintenance">🔴 En maintenance / atelier</option>
                  <option value="reserved">🔵 Réservé</option>
                </select>
              </div>

              <div class="form-group">
                <label class="label">Couleur</label>
                <input v-model="form.color" class="input" placeholder="ex: Blanc Glacier, Gris Comète..." />
              </div>

              <!-- Real Photo Upload -->
              <div class="form-group col-span-2 photo-upload-section">
                <label class="label">Photo réelle du véhicule (Optionnelle)</label>
                <div class="photo-uploader">
                  <div v-if="photoPreview" class="photo-preview-wrap">
                    <img :src="photoPreview" alt="Aperçu photo" class="photo-preview-thumb" />
                    <button type="button" class="btn-clear-photo" @click="clearPhotoSelection" title="Retirer">✕</button>
                  </div>
                  <div class="upload-actions">
                    <label class="btn btn-secondary btn-sm upload-btn">
                      <span>📷 {{ photoPreview ? 'Changer la photo' : 'Importer une photo réelle' }}</span>
                      <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        class="hidden-file-input"
                        @change="handlePhotoUpload"
                      />
                    </label>
                    <span class="upload-hint">Formats acceptés : JPG, PNG, WEBP (Max 10 Mo)</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="modalError" class="modal-error mt-4">
              {{ modalError }}
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-modal-action" @click="closeModal">Annuler</button>
            <button type="submit" class="btn btn-primary btn-modal-action" :disabled="modalSaving">
              {{ modalSaving ? 'Enregistrement...' : (editingId ? 'Mettre à jour' : 'Ajouter à la flotte') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

function can(perm: string): boolean {
  if (auth.hasRole('Super Admin')) return true
  return auth.hasPermission(perm)
}

interface VehiclePhotoItem {
  id: number
  file_path: string
  caption?: string
  is_primary: boolean
}

interface VehicleItem {
  id: number
  brand: string
  model: string
  year: number
  registration_plate: string
  vin: string
  color?: string
  fuel_type: string
  transmission: string
  mileage: number
  daily_price_dzd?: number
  deposit_dzd?: number
  status: string
  photo_url?: string | null
  photos?: VehiclePhotoItem[]
  branch?: { id: number; name: string }
  category?: { id: number; name: string; daily_rate_dzd: number }
}

const vehicles = ref<VehicleItem[]>([])
const quota = ref<{ current: number; max: number; is_reached: boolean } | null>(null)
const branches = ref<any[]>([])
const categories = ref<any[]>([])
const loading = ref(false)
const showModal = ref(false)
const editingId = ref<number | null>(null)
const modalSaving = ref(false)
const modalError = ref<string | null>(null)

// Photo upload state
const selectedPhotoFile = ref<File | null>(null)
const photoPreview = ref<string | null>(null)

function getVehiclePlaceholder(categoryName?: string): string {
  const norm = (categoryName || '').toLowerCase()
  if (norm.includes('suv') || norm.includes('4x4')) {
    return '/placeholders/suv-4x4.png'
  }
  if (norm.includes('berlin')) {
    return '/placeholders/berline.png'
  }
  if (norm.includes('premium') || norm.includes('luxe')) {
    return '/placeholders/premium.png'
  }
  if (norm.includes('eco') || norm.includes('éco')) {
    return '/placeholders/economique.png'
  }
  return '/car-placeholder.png'
}

function getVehiclePhoto(v: VehicleItem): string {
  const primary = v.photos?.find((p) => p.is_primary)?.file_path ?? v.photos?.[0]?.file_path
  if (primary) return primary
  if (v.photo_url) return v.photo_url
  return getVehiclePlaceholder(v.category?.name)
}

function handlePhotoUpload(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (file.size > 10 * 1024 * 1024) {
    alert('La photo ne doit pas dépasser 10 Mo.')
    return
  }

  selectedPhotoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

function clearPhotoSelection() {
  selectedPhotoFile.value = null
  if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoPreview.value = null
}

const filters = ref({
  search: '',
  branch_id: '',
  category_id: '',
  status: '',
})

const form = ref({
  branch_id: '' as any,
  category_id: '' as any,
  brand: '',
  model: '',
  year: 2024,
  registration_plate: '',
  vin: '',
  color: '',
  fuel_type: 'essence',
  transmission: 'manuelle',
  mileage: 15000,
  daily_price_dzd: null as number | null,
  deposit_dzd: null as number | null,
  status: 'available',
})

let debounceTimer: any = null
function debounceFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchVehicles, 300)
}

function formatNumber(num?: number): string {
  if (num === undefined || num === null) return '0'
  return new Intl.NumberFormat('fr-DZ').format(num)
}

function formatStatus(status: string): string {
  switch (status) {
    case 'available': return 'Disponible'
    case 'rented': return 'En location'
    case 'maintenance': return 'Maintenance'
    case 'reserved': return 'Réservé'
    default: return status
  }
}

function getStatusBadgeClass(status: string): string {
  switch (status) {
    case 'available': return 'badge-success'
    case 'rented': return 'badge-warning'
    case 'maintenance': return 'badge-error'
    case 'reserved': return 'badge-info'
    default: return 'badge-neutral'
  }
}

async function fetchMetadata() {
  try {
    const [bRes, cRes] = await Promise.all([
      api.get('/core/branches?per_page=100'),
      api.get('/fleet/categories'),
    ])
    branches.value = bRes.data.data.data ?? bRes.data.data
    categories.value = cRes.data.data
  } catch (err) {
    console.error('Failed to load metadata', err)
  }
}

async function fetchVehicles() {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id
    if (filters.value.category_id) params.category_id = filters.value.category_id
    if (filters.value.status) params.status = filters.value.status

    const { data } = await api.get('/fleet/vehicles', { params })
    vehicles.value = data.data.data ?? data.data
    if (data.data?.meta?.quota) {
      quota.value = data.data.meta.quota
    } else if (data.meta?.quota) {
      quota.value = data.meta.quota
    }
  } catch (err) {
    console.error('Failed to load vehicles', err)
  } finally {
    loading.value = false
  }
}

function openModal(vehicle?: VehicleItem) {
  modalError.value = null
  clearPhotoSelection()

  if (vehicle) {
    editingId.value = vehicle.id
    const primary = vehicle.photos?.find((p) => p.is_primary)?.file_path ?? vehicle.photos?.[0]?.file_path
    photoPreview.value = primary || vehicle.photo_url || null

    form.value = {
      branch_id: vehicle.branch?.id ?? '',
      category_id: vehicle.category?.id ?? '',
      brand: vehicle.brand,
      model: vehicle.model,
      year: vehicle.year,
      registration_plate: vehicle.registration_plate,
      vin: vehicle.vin,
      color: vehicle.color ?? '',
      fuel_type: vehicle.fuel_type,
      transmission: vehicle.transmission,
      mileage: vehicle.mileage,
      daily_price_dzd: vehicle.daily_price_dzd ?? null,
      deposit_dzd: vehicle.deposit_dzd ?? null,
      status: vehicle.status,
    }
  } else {
    editingId.value = null
    form.value = {
      branch_id: branches.value[0]?.id ?? '',
      category_id: categories.value[0]?.id ?? '',
      brand: '',
      model: '',
      year: 2024,
      registration_plate: '',
      vin: '',
      color: '',
      fuel_type: 'essence',
      transmission: 'manuelle',
      mileage: 10000,
      daily_price_dzd: null,
      deposit_dzd: null,
      status: 'available',
    }
  }
  showModal.value = true
}

function closeModal() {
  clearPhotoSelection()
  showModal.value = false
}

async function saveVehicle() {
  modalSaving.value = true
  modalError.value = null
  try {
    let vehicleId = editingId.value
    if (editingId.value) {
      await api.put(`/fleet/vehicles/${editingId.value}`, form.value)
    } else {
      const res = await api.post('/fleet/vehicles', form.value)
      vehicleId = res.data.data.id
    }

    // If an image file was selected, upload it as a real vehicle photo
    if (selectedPhotoFile.value && vehicleId) {
      const formData = new FormData()
      formData.append('photo', selectedPhotoFile.value)
      formData.append('is_primary', '1')
      await api.post(`/fleet/vehicles/${vehicleId}/photos`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    closeModal()
    fetchVehicles()
  } catch (err: any) {
    modalError.value = err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement'
  } finally {
    modalSaving.value = false
  }
}

async function deleteVehicle(vehicle: VehicleItem) {
  if (!confirm(`Supprimer le véhicule "${vehicle.brand} ${vehicle.model} (${vehicle.registration_plate})" ?`)) return
  try {
    await api.delete(`/fleet/vehicles/${vehicle.id}`)
    fetchVehicles()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Erreur lors de la suppression')
  }
}

onMounted(() => {
  fetchMetadata()
  fetchVehicles()
})
</script>

<style scoped>
.vehicles-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
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

.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.vehicle-card {
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  padding: 0;
  overflow: hidden;
  border-radius: var(--radius-lg, 12px);
  border: 1px solid var(--color-border, #E5E7EB);
  background: var(--color-surface, #ffffff);
}

.vehicle-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
  border-color: rgba(90, 60, 223, 0.3);
}

.vehicle-photo-wrap {
  position: relative;
  width: 100%;
  height: 180px;
  background: var(--color-surface-alt, #f3f4f6);
  overflow: hidden;
  flex-shrink: 0;
  border-bottom: 1px solid var(--color-border, #E5E7EB);
}

.vehicle-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.vehicle-card:hover .vehicle-photo {
  transform: scale(1.05);
}

.status-overlay-badge {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
}

.vehicle-card-body {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
  padding: 1.15rem 1.25rem 1.25rem;
  flex: 1;
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.category-badge {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-primary);
  background: var(--color-primary-50);
  padding: 0.25rem 0.65rem;
  border-radius: var(--radius-sm);
  letter-spacing: 0.02em;
}

.vehicle-title-wrap {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
}

.vehicle-name {
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.2;
}

.vehicle-year {
  font-size: 0.85rem;
  color: var(--color-text-muted);
  font-weight: 600;
}

.plate-box {
  background: #FEF9C3;
  border: 1.5px solid #EAB308;
  border-radius: var(--radius-sm, 6px);
  padding: 0.35rem 0.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  width: fit-content;
}

.dz-flag { font-size: 1rem; }
.plate-number {
  font-family: monospace;
  font-weight: 800;
  color: #854D0E;
  letter-spacing: 0.08em;
  font-size: 0.95rem;
}

.vehicle-specs-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  background: var(--color-surface-2, #F8FAFC);
  padding: 0.75rem 0.9rem;
  border-radius: var(--radius-md, 8px);
  border: 1px solid var(--color-border, #E2E8F0);
}

.spec-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vehicle-card-footer {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border, #E5E7EB);
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.price-indicator {
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.price-val {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-primary);
  line-height: 1;
}

.price-cur {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  font-weight: 600;
}

.deposit-chip {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  background: var(--color-surface-2, #F3F4F6);
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm, 6px);
  font-weight: 500;
}

.actions-group {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
}

.card-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.825rem;
  font-weight: 600;
  border-radius: var(--radius-md, 8px);
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-details {
  flex: 1;
  background: var(--color-primary-50, #EEF2FF);
  color: var(--color-primary, #4F46E5);
  border: 1px solid rgba(79, 70, 229, 0.2);
}

.btn-details:hover {
  background: var(--color-primary, #4F46E5);
  color: #ffffff;
}

.btn-edit {
  flex: 1;
  background: var(--color-surface, #ffffff);
  color: var(--color-text-primary, #1F2937);
  border: 1px solid var(--color-border, #D1D5DB);
}

.btn-edit:hover {
  background: var(--color-surface-alt, #F3F4F6);
  border-color: var(--color-text-secondary, #9CA3AF);
}

.btn-delete {
  width: 34px;
  height: 34px;
  padding: 0;
  flex-shrink: 0;
  background: transparent;
  color: var(--color-text-muted, #9CA3AF);
  border: 1px solid var(--color-border, #E5E7EB);
  border-radius: var(--radius-md, 8px);
  font-size: 0.85rem;
}

.btn-delete:hover {
  background: #FEE2E2;
  color: var(--color-error, #DC2626);
  border-color: #FECACA;
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
  z-index: 1000;
  padding: 1.5rem;
}

.modal-content {
  width: 100%;
  max-width: 720px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  padding: 0;
  overflow: hidden;
  border-radius: var(--radius-xl, 16px);
  background: var(--color-surface, #ffffff);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid var(--color-border, #E5E7EB);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid var(--color-border, #E5E7EB);
  background: var(--color-surface, #ffffff);
  flex-shrink: 0;
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.modal-title-icon {
  font-size: 1.6rem;
  line-height: 1;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary, #111827);
  margin: 0;
  line-height: 1.2;
}

.modal-subtitle {
  font-size: 0.825rem;
  color: var(--color-text-secondary, #6B7280);
  margin: 0.25rem 0 0 0;
}

.modal-close-btn {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: var(--color-text-secondary, #6B7280);
  cursor: pointer;
  padding: 0.35rem;
  border-radius: var(--radius-sm, 6px);
  line-height: 1;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background: var(--color-surface-2, #F3F4F6);
  color: var(--color-text-primary, #111827);
}

.modal-form {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.modal-body-scroll {
  padding: 1.75rem;
  overflow-y: auto;
  flex: 1;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.15rem;
}

.photo-upload-section {
  margin-top: 0.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--color-border, #E5E7EB);
}

.photo-uploader {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.15rem;
  background: var(--color-surface-2, #F8FAFC);
  border: 1.5px dashed var(--color-border, #CBD5E1);
  border-radius: var(--radius-md, 10px);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.75rem;
  border-top: 1px solid var(--color-border, #E5E7EB);
  background: var(--color-surface-2, #F8FAFC);
  flex-shrink: 0;
}

.btn-modal-action {
  padding: 0.65rem 1.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: var(--radius-md, 8px);
}

.modal-error {
  background: var(--color-error-light);
  color: var(--color-error);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
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

.col-span-2 {
  grid-column: span 2;
}

.photos-count-badge {
  position: absolute;
  bottom: 0.6rem;
  left: 0.6rem;
  background: rgba(0, 0, 0, 0.65);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: var(--radius-sm, 6px);
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.photo-uploader {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem;
  background: var(--color-surface-alt, #F9FAFB);
  border: 1.5px dashed var(--color-border, #E5E7EB);
  border-radius: var(--radius-md, 8px);
}

.photo-preview-wrap {
  position: relative;
  width: 90px;
  height: 65px;
  border-radius: var(--radius-sm, 6px);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
  background: #000;
}

.photo-preview-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.btn-clear-photo {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  color: #fff;
  border: none;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-clear-photo:hover {
  background: var(--color-error, #EF4444);
}

.upload-actions {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.upload-btn {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  width: fit-content;
}

.hidden-file-input {
  display: none;
}

.upload-hint {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #6B7280);
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
