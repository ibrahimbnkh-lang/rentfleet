<template>
  <div class="vehicle-detail-view">
    <!-- Header -->
    <div class="page-header">
      <div class="flex items-center gap-3">
        <RouterLink to="/fleet/vehicles" class="btn btn-secondary btn-sm">
          ← Retour à la flotte
        </RouterLink>
        <div>
          <h1 class="page-title">{{ vehicle?.brand }} {{ vehicle?.model }} ({{ vehicle?.year }})</h1>
          <p class="page-subtitle">Immatriculation : {{ vehicle?.registration_plate }} • VIN : {{ vehicle?.vin }}</p>
        </div>
      </div>

      <div class="header-status-box">
        <select
          v-model="statusSelect"
          class="input font-bold"
          @change="updateStatus"
        >
          <option value="available">🟢 Disponible</option>
          <option value="rented">🟡 En location</option>
          <option value="maintenance">🔴 En maintenance</option>
          <option value="reserved">🔵 Réservé</option>
        </select>
      </div>
    </div>

    <!-- Compliance Alert Banner -->
    <div
      v-if="compliance"
      :class="['alert mb-4', compliance.is_compliant ? 'alert-success' : 'alert-error']"
    >
      <div class="flex items-center gap-2">
        <span class="text-xl">{{ compliance.is_compliant ? '🛡️' : '⚠️' }}</span>
        <div>
          <strong>Statut de conformité légale :</strong>
          {{ compliance.is_compliant ? 'Tous les documents obligatoires sont valides et conformes.' : 'Ce véhicule présente des non-conformités administratives.' }}
          <div v-if="compliance.issues?.length" class="text-xs mt-1">
            <span v-for="iss in compliance.issues" :key="iss" class="block">• {{ iss }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="settings-tabs">
      <button
        :class="['tab-btn', activeTab === 'overview' && 'active']"
        @click="activeTab = 'overview'"
      >
        📋 Vue d'ensemble
      </button>
      <button
        :class="['tab-btn', activeTab === 'photos' && 'active']"
        @click="activeTab = 'photos'"
      >
        📷 Galerie Photos ({{ vehicle?.photos?.length ?? 0 }})
      </button>
      <button
        :class="['tab-btn', activeTab === 'documents' && 'active']"
        @click="activeTab = 'documents'"
      >
        📄 Documents & Assurance ({{ vehicle?.documents?.length ?? 0 }})
      </button>
      <button
        :class="['tab-btn', activeTab === 'equipment' && 'active']"
        @click="activeTab = 'equipment'"
      >
        🛠️ Équipements de Sécurité ({{ vehicle?.equipment?.length ?? 0 }})
      </button>
    </div>

    <!-- Tab 1: Overview -->
    <div v-if="activeTab === 'overview'" class="tab-panel card">
      <!-- Overview Hero Banner with Car Photo -->
      <div class="vehicle-overview-hero mb-6">
        <div class="overview-photo-container">
          <img
            :src="getPrimaryPhoto()"
            :alt="`${vehicle?.brand} ${vehicle?.model}`"
            class="overview-car-img"
            @error="(e) => ((e.target as HTMLImageElement).src = getVehiclePlaceholder(vehicle?.category?.name))"
          />
          <div class="overview-photo-badge">
            <span v-if="vehicle?.photos?.length" class="badge badge-success">⭐ Photo réelle ({{ vehicle.photos.length }})</span>
            <span v-else class="badge badge-neutral">🖼️ Image type : {{ vehicle?.category?.name }}</span>
          </div>
        </div>
        <div class="overview-hero-info">
          <div class="hero-brand-line">
            <h2>{{ vehicle?.brand }} {{ vehicle?.model }}</h2>
            <span class="hero-year">{{ vehicle?.year }}</span>
          </div>
          <div class="plate-box-lg">
            <span class="dz-flag">🇩🇿</span>
            <span class="plate-number-lg">{{ vehicle?.registration_plate }}</span>
          </div>
          <p class="hero-desc">
            {{ vehicle?.category?.description || 'Véhicule de flotte commerciale géré par RentFleet.' }}
          </p>
          <div class="mt-4 flex gap-3">
            <button class="btn btn-primary btn-sm" @click="activeTab = 'photos'">
              <span>📷</span> {{ vehicle?.photos?.length ? 'Gérer les photos (' + vehicle.photos.length + ')' : 'Ajouter une photo réelle' }}
            </button>
          </div>
        </div>
      </div>

      <div class="overview-grid">
        <!-- Section 1: Caractéristiques Techniques -->
        <div class="overview-section-card">
          <div class="overview-section-header">
            <div class="section-icon-badge" style="background: rgba(90, 60, 223, 0.1); color: #5A3CDF;">
              <span>⚙️</span>
            </div>
            <div>
              <h3 class="section-title-main">Caractéristiques Techniques</h3>
              <p class="section-subtitle-main">Spécifications mécaniques et administratives du véhicule</p>
            </div>
          </div>

          <div class="specs-table-modern">
            <div class="spec-cell-modern">
              <span class="lbl-modern">🏷️ Catégorie</span>
              <span class="badge badge-primary">{{ vehicle?.category?.name }}</span>
            </div>
            <div class="spec-cell-modern">
              <span class="lbl-modern">🏢 Agence de rattachement</span>
              <strong>{{ vehicle?.branch?.name }} (Wilaya {{ vehicle?.branch?.wilaya_code }})</strong>
            </div>
            <div class="spec-cell-modern">
              <span class="lbl-modern">⛽ Type de carburant</span>
              <span class="capitalize font-semibold">{{ vehicle?.fuel_type }}</span>
            </div>
            <div class="spec-cell-modern">
              <span class="lbl-modern">🕹️ Transmission</span>
              <span class="capitalize font-semibold">{{ vehicle?.transmission === 'automatique' ? 'Automatique' : 'Manuelle' }}</span>
            </div>
            <div class="spec-cell-modern">
              <span class="lbl-modern">🛣️ Kilométrage actuel</span>
              <strong class="font-mono text-base">{{ formatNumber(vehicle?.mileage) }} km</strong>
            </div>
            <div class="spec-cell-modern">
              <span class="lbl-modern">🎨 Couleur carrosserie</span>
              <span>{{ vehicle?.color || 'Non spécifiée' }}</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Tarification & Caution (DZD) -->
        <div class="overview-section-card">
          <div class="overview-section-header">
            <div class="section-icon-badge" style="background: rgba(22, 163, 74, 0.1); color: #16A34A;">
              <span>💰</span>
            </div>
            <div>
              <h3 class="section-title-main">Tarification & Caution (DZD)</h3>
              <p class="section-subtitle-main">Conditions financières et tarification journalière</p>
            </div>
          </div>

          <div class="pricing-content-box">
            <div class="pricing-highlight-card">
              <span class="pricing-label-top">Tarif journalier spécifique</span>
              <div class="price-big-row">
                <span class="price-big-number">{{ formatNumber(vehicle?.daily_price_dzd || vehicle?.category?.daily_rate_dzd || 5000) }}</span>
                <span class="price-big-unit">DZD / jour</span>
              </div>
              <div class="weekly-estimate">
                <span>Estimation 7 jours :</span>
                <strong>{{ formatNumber((vehicle?.daily_price_dzd || vehicle?.category?.daily_rate_dzd || 5000) * 7) }} DZD</strong>
              </div>
            </div>

            <div class="deposit-highlight-card">
              <div class="deposit-header-row">
                <div class="flex items-center gap-2">
                  <span class="text-base">🛡️</span>
                  <span class="font-semibold text-sm">Caution de garantie exigée</span>
                </div>
                <strong class="deposit-amount-highlight">{{ formatNumber(vehicle?.deposit_dzd || vehicle?.category?.deposit_amount_dzd || 50000) }} DZD</strong>
              </div>
              <p class="deposit-explainer">
                Montant bloqué par empreinte bancaire ou chèque de caution, restitué à la fin du contrat.
              </p>
            </div>

            <div class="included-perks-row">
              <span class="perk-pill">✅ Assurance tous risques</span>
              <span class="perk-pill">✅ Kilométrage illimité</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Photos Gallery -->
    <div v-if="activeTab === 'photos'" class="tab-panel card">
      <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
        <div>
          <h3 class="section-title">Galerie Photos du Véhicule</h3>
          <p class="section-subtitle">Gérez les photos réelles de ce véhicule pour le catalogue et l'état des lieux</p>
        </div>
        <button class="btn btn-primary btn-sm" @click="showUploadBox = !showUploadBox">
          {{ showUploadBox ? '✕ Fermer' : '+ Ajouter une photo' }}
        </button>
      </div>

      <!-- Upload Box -->
      <div v-if="showUploadBox" class="card photo-upload-box mb-6">
        <h4 class="font-bold text-base mb-3">Téléverser une nouvelle photo</h4>
        <div class="upload-form-grid">
          <div class="upload-drop-area">
            <div v-if="photoPreview" class="upload-preview">
              <img :src="photoPreview" alt="Aperçu photo" class="upload-preview-img" />
              <button type="button" class="btn-remove-preview" @click="clearPhotoUpload" title="Changer">✕</button>
            </div>
            <div v-else class="upload-prompt">
              <input
                type="file"
                id="photo-file-upload"
                accept="image/jpeg,image/png,image/webp,image/avif"
                class="hidden-input"
                @change="onPhotoFileSelect"
              />
              <label for="photo-file-upload" class="upload-drop-label">
                <span class="upload-icon">📷</span>
                <span class="font-bold">Sélectionner une photo</span>
                <small class="text-muted">JPG, PNG, WEBP (Max 10 Mo)</small>
              </label>
            </div>
          </div>

          <div class="upload-details">
            <div class="form-group">
              <label class="label">Légende / Description (Optionnelle)</label>
              <input
                v-model="photoCaption"
                type="text"
                class="input"
                placeholder="ex: Vue avant 3/4, Habitacle intérieur..."
              />
            </div>
            <div class="form-group flex items-center gap-2 mt-3">
              <input
                type="checkbox"
                id="chk-primary"
                v-model="photoIsPrimary"
                class="checkbox"
              />
              <label for="chk-primary" class="cursor-pointer text-sm font-medium">
                Définir comme photo principale de la fiche
              </label>
            </div>

            <div class="upload-actions-bar mt-5">
              <button
                type="button"
                class="btn btn-primary"
                :disabled="!photoFile || photoUploading"
                @click="uploadVehiclePhoto"
              >
                {{ photoUploading ? 'Téléversement en cours...' : 'Enregistrer la photo' }}
              </button>
              <button type="button" class="btn btn-secondary" @click="clearPhotoUpload">
                Annuler
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Photos Gallery Grid -->
      <div v-if="vehicle?.photos?.length" class="photos-gallery-grid">
        <div
          v-for="photo in vehicle.photos"
          :key="photo.id"
          class="photo-gallery-card card"
        >
          <div class="photo-img-wrap">
            <img :src="photo.file_path" :alt="photo.caption || 'Photo véhicule'" class="photo-gallery-img" />
            <span v-if="photo.is_primary" class="primary-star-badge">
              ⭐ Principale
            </span>
          </div>

          <div class="photo-card-info">
            <p class="photo-caption">{{ photo.caption || 'Sans légende' }}</p>
            <div class="photo-card-actions">
              <button
                v-if="!photo.is_primary"
                class="btn btn-secondary btn-xs"
                title="Définir comme photo principale"
                @click="setAsPrimary(photo.id)"
              >
                ⭐ Définir principale
              </button>
              <button
                class="btn btn-ghost btn-xs text-error"
                title="Supprimer cette photo"
                @click="deleteVehiclePhoto(photo.id)"
              >
                🗑️ Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-photos-state">
        <div class="empty-photo-placeholder-wrap">
          <img
            :src="getVehiclePlaceholder(vehicle?.category?.name)"
            alt="Placeholder"
            class="empty-photo-preview"
          />
        </div>
        <h4 class="font-bold text-base mt-3">Aucune photo réelle enregistrée</h4>
        <p class="text-secondary max-w-md mx-auto text-sm mt-1">
          Ce véhicule utilise actuellement l'image de substitution par défaut de sa catégorie ({{ vehicle?.category?.name }}).
        </p>
        <button class="btn btn-primary btn-sm mt-4" @click="showUploadBox = true">
          + Ajouter la première photo réelle
        </button>
      </div>
    </div>

    <!-- Tab 2: Documents -->
    <div v-if="activeTab === 'documents'" class="tab-panel card">
      <div class="flex justify-between items-center mb-4">
        <div>
          <h3 class="section-title">Documents du véhicule</h3>
          <p class="section-subtitle">Assurance automobile, contrôle technique et certificat d'immatriculation</p>
        </div>
        <button class="btn btn-primary btn-sm" @click="showDocModal = true">
          + Ajouter un document
        </button>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Type de document</th>
              <th>Référence</th>
              <th>Organisme / Émetteur</th>
              <th>Date d'expiration</th>
              <th>État</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in vehicle?.documents" :key="doc.id">
              <td>
                <strong>{{ formatDocType(doc.type) }}</strong>
              </td>
              <td class="font-mono text-xs">{{ doc.reference_number }}</td>
              <td>{{ doc.issuer || '—' }}</td>
              <td class="font-mono text-xs">
                {{ doc.expires_at ? formatDate(doc.expires_at) : 'Permanent' }}
              </td>
              <td>
                <span :class="['badge', getDocStatusBadge(doc)]">
                  {{ getDocStatusLabel(doc) }}
                </span>
              </td>
              <td class="text-right">
                <button class="btn btn-ghost btn-sm text-error" @click="deleteDoc(doc.id)">
                  Supprimer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 3: Equipment -->
    <div v-if="activeTab === 'equipment'" class="tab-panel card">
      <div class="flex justify-between items-center mb-4">
        <div>
          <h3 class="section-title">Équipements & Accessoires de bord</h3>
          <p class="section-subtitle">Checklist de conformité et matériel d'urgence obligatoire en Algérie</p>
        </div>
        <button class="btn btn-primary btn-sm" @click="saveEquipment">
          💾 Enregistrer la checklist
        </button>
      </div>

      <div class="equipment-list">
        <div v-for="eq in equipmentItems" :key="eq.id || eq.name" class="equipment-row">
          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="eq.is_present" type="checkbox" />
            <span class="font-semibold">{{ eq.name }}</span>
          </label>
          <select v-model="eq.condition" class="input w-44">
            <option value="bon_etat">Bon état</option>
            <option value="use">Usé / Fonctionnel</option>
            <option value="a_remplacer">À remplacer</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Modal Add Document -->
    <div v-if="showDocModal" class="modal-backdrop" @click.self="showDocModal = false">
      <div class="modal-content card max-w-lg">
        <div class="modal-header">
          <h3>Ajouter un document réglementaire</h3>
          <button class="modal-close-btn" @click="showDocModal = false">✕</button>
        </div>

        <form @submit.prevent="addDocument">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="label">Type de document *</label>
              <select v-model="docForm.type" required class="input">
                <option value="assurance">Assurance Automobile</option>
                <option value="controle_technique">Contrôle Technique</option>
                <option value="carte_grise">Carte Grise</option>
                <option value="vignette">Vignette Automobile</option>
                <option value="autorisation">Autorisation de circulation</option>
              </select>
            </div>

            <div class="form-group col-span-2">
              <label class="label">Numéro de police / Référence *</label>
              <input v-model="docForm.reference_number" required class="input" placeholder="ex: POL-SAA-987654" />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Compagnie / Organisme émetteur</label>
              <input v-model="docForm.issuer" class="input" placeholder="ex: SAA, CAAT, CIAR, DGSN..." />
            </div>

            <div class="form-group">
              <label class="label">Date de délivrance</label>
              <input v-model="docForm.issued_at" type="date" class="input" />
            </div>

            <div class="form-group">
              <label class="label">Date d'expiration</label>
              <input v-model="docForm.expires_at" type="date" class="input" />
            </div>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="showDocModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer le document</button>
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
const vehicleId = route.params.id

const vehicle = ref<any>(null)
const compliance = ref<any>(null)
const activeTab = ref<'overview' | 'photos' | 'documents' | 'equipment'>('overview')
const statusSelect = ref('available')
const showDocModal = ref(false)
const equipmentItems = ref<any[]>([])

// Photo gallery & upload state
const showUploadBox = ref(false)
const photoFile = ref<File | null>(null)
const photoPreview = ref<string | null>(null)
const photoCaption = ref('')
const photoIsPrimary = ref(true)
const photoUploading = ref(false)

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

function getPrimaryPhoto(): string {
  const primary = vehicle.value?.photos?.find((p: any) => p.is_primary)?.file_path ?? vehicle.value?.photos?.[0]?.file_path
  if (primary) return primary
  if (vehicle.value?.photo_url) return vehicle.value.photo_url
  return getVehiclePlaceholder(vehicle.value?.category?.name)
}

function onPhotoFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (file.size > 10 * 1024 * 1024) {
    alert('La photo ne doit pas dépasser 10 Mo.')
    return
  }

  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

function clearPhotoUpload() {
  photoFile.value = null
  if (photoPreview.value && photoPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoPreview.value = null
  photoCaption.value = ''
  photoIsPrimary.value = true
  showUploadBox.value = false
}

async function uploadVehiclePhoto() {
  if (!photoFile.value) return
  photoUploading.value = true
  try {
    const formData = new FormData()
    formData.append('photo', photoFile.value)
    if (photoCaption.value) {
      formData.append('caption', photoCaption.value)
    }
    formData.append('is_primary', photoIsPrimary.value ? '1' : '0')

    await api.post(`/fleet/vehicles/${vehicleId}/photos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    clearPhotoUpload()
    fetchVehicle()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Échec du téléversement de la photo.')
  } finally {
    photoUploading.value = false
  }
}

async function setAsPrimary(photoId: number) {
  try {
    await api.patch(`/fleet/vehicles/${vehicleId}/photos/${photoId}/primary`)
    fetchVehicle()
  } catch (err: any) {
    alert('Échec de la mise à jour de la photo principale.')
  }
}

async function deleteVehiclePhoto(photoId: number) {
  if (!confirm('Supprimer cette photo ?')) return
  try {
    await api.delete(`/fleet/vehicles/${vehicleId}/photos/${photoId}`)
    fetchVehicle()
  } catch (err: any) {
    alert('Erreur lors de la suppression de la photo.')
  }
}

const docForm = ref({
  type: 'assurance',
  reference_number: '',
  issuer: 'SAA Assurances',
  issued_at: '',
  expires_at: '',
})

function formatNumber(num?: number): string {
  if (num === undefined || num === null) return '0'
  return new Intl.NumberFormat('fr-DZ').format(num)
}

function formatDate(val: string): string {
  if (!val) return '—'
  return new Date(val).toLocaleDateString('fr-DZ')
}

function formatDocType(type: string): string {
  switch (type) {
    case 'assurance': return '🛡️ Assurance Automobile'
    case 'controle_technique': return '🔧 Contrôle Technique'
    case 'carte_grise': return '📄 Carte Grise'
    case 'vignette': return '🎫 Vignette Automobile'
    default: return type
  }
}

function getDocStatusBadge(doc: any): string {
  if (!doc.expires_at) return 'badge-neutral'
  const exp = new Date(doc.expires_at).getTime()
  const now = Date.now()
  if (exp < now) return 'badge-error'
  if (exp - now < 30 * 86400000) return 'badge-warning'
  return 'badge-success'
}

function getDocStatusLabel(doc: any): string {
  if (!doc.expires_at) return 'Permanent'
  const exp = new Date(doc.expires_at).getTime()
  const now = Date.now()
  if (exp < now) return 'Expiré'
  if (exp - now < 30 * 86400000) return 'Expire bientôt'
  return 'Valide'
}

async function fetchVehicle() {
  try {
    const { data } = await api.get(`/fleet/vehicles/${vehicleId}`)
    vehicle.value = data.data.vehicle
    compliance.value = data.data.compliance
    statusSelect.value = vehicle.value.status
    equipmentItems.value = JSON.parse(JSON.stringify(vehicle.value.equipment || []))
  } catch (err) {
    console.error('Failed to load vehicle details', err)
  }
}

async function updateStatus() {
  try {
    await api.put(`/fleet/vehicles/${vehicleId}`, {
      status: statusSelect.value,
    })
    fetchVehicle()
  } catch (err) {
    alert('Échec de la mise à jour du statut')
  }
}

async function addDocument() {
  try {
    await api.post(`/fleet/vehicles/${vehicleId}/documents`, docForm.value)
    showDocModal.value = false
    docForm.value = {
      type: 'assurance',
      reference_number: '',
      issuer: 'SAA Assurances',
      issued_at: '',
      expires_at: '',
    }
    fetchVehicle()
  } catch (err: any) {
    alert(err.response?.data?.error?.message ?? 'Erreur lors de l\'enregistrement')
  }
}

async function deleteDoc(docId: number) {
  if (!confirm('Supprimer ce document ?')) return
  try {
    await api.delete(`/fleet/vehicles/${vehicleId}/documents/${docId}`)
    fetchVehicle()
  } catch (err) {
    alert('Erreur lors de la suppression')
  }
}

async function saveEquipment() {
  try {
    await api.put(`/fleet/vehicles/${vehicleId}/equipment`, {
      items: equipmentItems.value,
    })
    alert('Checklist équipements mise à jour avec succès.')
    fetchVehicle()
  } catch (err) {
    alert('Erreur lors de la sauvegarde des équipements')
  }
}

onMounted(() => {
  fetchVehicle()
})
</script>

<style scoped>
.vehicle-detail-view {
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

.tab-btn:hover { background: var(--color-surface-3); }
.tab-btn.active {
  background: var(--color-primary-50);
  color: var(--color-primary);
  font-weight: 700;
}

.tab-panel {
  padding: 1.75rem;
}

.overview-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.75rem;
}

@media (max-width: 900px) {
  .overview-grid {
    grid-template-columns: 1fr;
  }
}

.overview-section-card {
  background: var(--color-surface, #ffffff);
  border: 1px solid var(--color-border, #E2E8F0);
  border-radius: var(--radius-lg, 12px);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.overview-section-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding-bottom: 1rem;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--color-border, #E2E8F0);
}

.section-icon-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.section-title-main {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--color-text-primary, #0F172A);
  margin: 0;
  line-height: 1.25;
}

.section-subtitle-main {
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748B);
  margin: 0.2rem 0 0 0;
}

/* Modern Specs Table */
.specs-table-modern {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  flex: 1;
}

.spec-cell-modern {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.65rem 0.85rem;
  background: var(--color-surface-2, #F8FAFC);
  border-radius: var(--radius-md, 8px);
  font-size: 0.875rem;
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.lbl-modern {
  color: var(--color-text-secondary, #64748B);
  font-weight: 500;
}

/* Modern Pricing Box */
.pricing-content-box {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
  flex: 1;
}

.pricing-highlight-card {
  background: linear-gradient(135deg, rgba(90, 60, 223, 0.06) 0%, rgba(64, 40, 196, 0.02) 100%);
  border: 1px solid rgba(90, 60, 223, 0.2);
  border-radius: var(--radius-md, 10px);
  padding: 1.25rem;
}

.pricing-label-top {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-primary, #5A3CDF);
}

.price-big-row {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.price-big-number {
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--color-primary, #5A3CDF);
  line-height: 1;
}

.price-big-unit {
  font-size: 0.85rem;
  color: var(--color-text-secondary, #64748B);
  font-weight: 600;
}

.weekly-estimate {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.75rem;
  padding-top: 0.65rem;
  border-top: 1px dashed rgba(90, 60, 223, 0.2);
  font-size: 0.825rem;
  color: var(--color-text-secondary, #64748B);
}

.deposit-highlight-card {
  background: #FEF9C3;
  border: 1.5px solid #FDE047;
  border-radius: var(--radius-md, 10px);
  padding: 1.15rem;
}

.deposit-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.deposit-amount-highlight {
  font-size: 1.15rem;
  font-weight: 800;
  color: #854D0E;
}

.deposit-explainer {
  font-size: 0.775rem;
  color: #713F12;
  margin-top: 0.45rem;
  line-height: 1.4;
}

.included-perks-row {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-top: auto;
}

.perk-pill {
  font-size: 0.775rem;
  font-weight: 600;
  color: #166534;
  background: #DCFCE7;
  padding: 0.3rem 0.65rem;
  border-radius: 99px;
  border: 1px solid #BBF7D0;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.equipment-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  max-width: 600px;
}

.equipment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-surface-2);
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.alert {
  padding: 1rem 1.25rem;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
}

.alert-success { background: var(--color-success-light); color: var(--color-success); }
.alert-error { background: var(--color-error-light); color: var(--color-error); }

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  width: 100%;
  max-width: 520px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.modal-close-btn { background: transparent; border: none; font-size: 1.25rem; cursor: pointer; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.col-span-2 { grid-column: span 2; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; }
.text-right { text-align: right; }
.text-error { color: var(--color-error); }
.capitalize { text-transform: capitalize; }

/* Overview Hero Banner */
.vehicle-overview-hero {
  display: flex;
  gap: 2rem;
  background: var(--color-surface-2, #F8FAFC);
  border: 1px solid var(--color-border, #E2E8F0);
  border-radius: var(--radius-lg, 12px);
  padding: 1.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.overview-photo-container {
  position: relative;
  width: 320px;
  height: 200px;
  border-radius: var(--radius-md, 8px);
  overflow: hidden;
  background: #ffffff;
  border: 1px solid var(--color-border, #E2E8F0);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.overview-car-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.overview-photo-badge {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
}

.overview-hero-info {
  flex: 1;
  min-width: 260px;
}

.hero-brand-line {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.hero-brand-line h2 {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-primary);
}

.hero-year {
  font-size: 1rem;
  color: var(--color-text-secondary);
  font-weight: 600;
}

.plate-box-lg {
  background: #FEF9C3;
  border: 1.5px solid #EAB308;
  border-radius: var(--radius-sm);
  padding: 0.4rem 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.plate-number-lg {
  font-family: monospace;
  font-weight: 800;
  color: #854D0E;
  font-size: 1.1rem;
  letter-spacing: 0.08em;
}

.hero-desc {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  line-height: 1.5;
  max-width: 600px;
}

/* Photo Gallery & Upload */
.photo-upload-box {
  background: var(--color-surface-2, #F8FAFC);
  border: 1px solid var(--color-border);
  padding: 1.5rem;
}

.upload-form-grid {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 640px) {
  .upload-form-grid {
    grid-template-columns: 1fr;
  }
}

.upload-drop-area {
  position: relative;
  width: 100%;
  height: 160px;
  background: #ffffff;
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.upload-preview {
  position: relative;
  width: 100%;
  height: 100%;
}

.upload-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.btn-remove-preview {
  position: absolute;
  top: 6px;
  right: 6px;
  background: rgba(0, 0, 0, 0.75);
  color: #fff;
  border: none;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-prompt {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-drop-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  cursor: pointer;
  padding: 1rem;
  text-align: center;
}

.upload-icon {
  font-size: 2rem;
  margin-bottom: 0.25rem;
}

.hidden-input {
  display: none;
}

.upload-actions-bar {
  display: flex;
  gap: 0.75rem;
}

/* Gallery Grid */
.photos-gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.25rem;
}

.photo-gallery-card {
  padding: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}

.photo-gallery-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-elevated);
}

.photo-img-wrap {
  position: relative;
  width: 100%;
  height: 180px;
  background: #f1f5f9;
}

.photo-gallery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.primary-star-badge {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  background: #EAB308;
  color: #000;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.photo-card-info {
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.photo-caption {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  margin: 0;
  min-height: 1.2rem;
}

.photo-card-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.25rem;
}

/* Empty photos state */
.empty-photos-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-photo-placeholder-wrap {
  width: 220px;
  height: 140px;
  margin: 0 auto;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
}

.empty-photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
