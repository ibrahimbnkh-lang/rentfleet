<template>
  <div class="categories-view">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Catégories de Véhicules</h1>
        <p class="page-subtitle">Tarification de référence et cautions par segment de véhicule</p>
      </div>
      <button class="btn btn-primary" @click="openModal()">
        <span>+</span> Nouvelle catégorie
      </button>
    </div>

    <!-- Loading / Empty states -->
    <div v-if="loading" class="card loading-state">
      <div class="spinner"></div>
      <p>Chargement des catégories...</p>
    </div>

    <div v-else-if="categories.length === 0" class="card empty-state">
      <div class="empty-icon">📂</div>
      <h3>Aucune catégorie configurée</h3>
      <p>Créez votre première catégorie pour classifier votre parc de véhicules.</p>
      <button class="btn btn-primary btn-sm mt-3" @click="openModal()">+ Ajouter une catégorie</button>
    </div>

    <!-- Categories Grid -->
    <div v-else class="categories-grid">
      <div v-for="cat in categories" :key="cat.id" class="card cat-card">
        <div class="cat-card-header">
          <h3 class="cat-name">{{ cat.name }}</h3>
          <div class="cat-vehicle-count">
            <span class="count-badge">{{ cat.vehicles_count ?? 0 }}</span>
            <span class="count-label">véhicule(s)</span>
          </div>
        </div>

        <div class="cat-body">
          <p class="cat-desc">{{ cat.description || 'Aucune description spécifique pour ce segment.' }}</p>

          <!-- Pricing metrics -->
          <div class="pricing-box">
            <div class="price-col">
              <span class="price-label">Tarif de référence</span>
              <span class="price-value">{{ formatDzd(cat.daily_rate_dzd) }} <span class="unit">DZD / j</span></span>
            </div>
            <div class="divider-v"></div>
            <div class="price-col">
              <span class="price-label">Caution requise</span>
              <span class="price-value caution">{{ formatDzd(cat.deposit_amount_dzd) }} <span class="unit">DZD</span></span>
            </div>
          </div>
        </div>

        <div class="cat-footer">
          <button class="btn btn-secondary btn-sm" @click="openModal(cat)">
            Modifier
          </button>
          <button
            class="btn btn-ghost btn-sm text-error"
            :disabled="(cat.vehicles_count ?? 0) > 0"
            :title="(cat.vehicles_count ?? 0) > 0 ? 'Impossible de supprimer : contient des véhicules' : 'Supprimer'"
            @click="deleteCategory(cat)"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Create / Edit -->
    <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal-content card max-w-lg">
        <div class="modal-header">
          <h3>{{ editingId ? 'Modifier la catégorie' : 'Créer une catégorie' }}</h3>
          <button class="modal-close-btn" @click="showModal = false">✕</button>
        </div>

        <form @submit.prevent="saveCategory">
          <div v-if="errorMsg" class="alert alert-error mb-4">
            {{ errorMsg }}
          </div>

          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="label">Nom de la catégorie *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="ex: Berline, SUV & 4x4, Économique..."
                class="input"
              />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Identifiant unique (slug)</label>
              <input
                v-model="form.slug"
                type="text"
                placeholder="Laisser vide pour génération automatique (ex: suv-4x4)"
                class="input font-mono"
              />
            </div>

            <div class="form-group col-span-2">
              <label class="label">Description</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Caractéristiques de ce segment (habitabilité, confort, usage recommandé)..."
                class="input"
              ></textarea>
            </div>

            <div class="form-group">
              <label class="label">Tarif journalier de référence (DZD) *</label>
              <input
                v-model.number="form.daily_rate_dzd"
                type="number"
                min="0"
                step="100"
                required
                class="input"
                placeholder="ex: 8000"
              />
            </div>

            <div class="form-group">
              <label class="label">Caution / Dépôt de garantie (DZD) *</label>
              <input
                v-model.number="form.deposit_amount_dzd"
                type="number"
                min="0"
                step="1000"
                required
                class="input"
                placeholder="ex: 50000"
              />
            </div>
          </div>

          <div class="modal-footer mt-5">
            <button type="button" class="btn btn-secondary" @click="showModal = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Enregistrement...' : editingId ? 'Mettre à jour' : 'Créer' }}
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

interface CategoryItem {
  id: number
  name: string
  slug: string
  description?: string
  daily_rate_dzd: number
  deposit_amount_dzd: number
  vehicles_count?: number
}

const categories = ref<CategoryItem[]>([])
const loading = ref(false)
const showModal = ref(false)
const submitting = ref(false)
const editingId = ref<number | null>(null)
const errorMsg = ref('')

const form = ref({
  name: '',
  slug: '',
  description: '',
  daily_rate_dzd: 5000,
  deposit_amount_dzd: 40000,
})

function formatDzd(val: number | undefined): string {
  if (val === undefined || val === null) return '0'
  return new Intl.NumberFormat('fr-DZ').format(val)
}

async function fetchCategories() {
  loading.value = true
  try {
    const { data } = await api.get('/fleet/categories')
    categories.value = data.data || []
  } catch (err) {
    console.error('Failed to load vehicle categories', err)
  } finally {
    loading.value = false
  }
}

function openModal(cat?: CategoryItem) {
  errorMsg.value = ''
  if (cat) {
    editingId.value = cat.id
    form.value = {
      name: cat.name,
      slug: cat.slug,
      description: cat.description || '',
      daily_rate_dzd: cat.daily_rate_dzd,
      deposit_amount_dzd: cat.deposit_amount_dzd,
    }
  } else {
    editingId.value = null
    form.value = {
      name: '',
      slug: '',
      description: '',
      daily_rate_dzd: 5000,
      deposit_amount_dzd: 40000,
    }
  }
  showModal.value = true
}

async function saveCategory() {
  submitting.value = true
  errorMsg.value = ''
  try {
    if (editingId.value) {
      await api.put(`/fleet/categories/${editingId.value}`, form.value)
    } else {
      await api.post('/fleet/categories', form.value)
    }
    showModal.value = false
    await fetchCategories()
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Erreur lors de la sauvegarde de la catégorie'
  } finally {
    submitting.value = false
  }
}

async function deleteCategory(cat: CategoryItem) {
  if (!confirm(`Supprimer définitivement la catégorie "${cat.name}" ?`)) return
  try {
    await api.delete(`/fleet/categories/${cat.id}`)
    await fetchCategories()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Erreur lors de la suppression')
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
.categories-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.page-subtitle {
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.cat-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.25rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  box-shadow: var(--shadow-card);
  transition: transform 0.2s, box-shadow 0.2s;
}

.cat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-elevated);
}

.cat-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  gap: 1rem;
}

.cat-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-primary);
  margin: 0;
  letter-spacing: -0.01em;
}

.cat-vehicle-count {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  flex-shrink: 0;
}

.count-badge {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-primary);
  line-height: 1;
}

.count-label {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.cat-desc {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
  margin-bottom: 1rem;
  min-height: 2.4rem;
}

.pricing-box {
  display: flex;
  background: var(--color-surface-2);
  border-radius: var(--radius-md);
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border: 1px solid var(--color-border);
}

.price-col {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.divider-v {
  width: 1px;
  background: var(--color-border);
  margin: 0 0.85rem;
}

.price-label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-secondary);
}

.price-value {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--color-primary);
  margin-top: 0.15rem;
}

.price-value.caution {
  color: var(--color-warning-dark, #b45309);
}

.price-value .unit {
  font-size: 0.7rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.cat-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  border-top: 1px solid var(--color-border);
  padding-top: 0.85rem;
  margin-top: auto;
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
  max-width: 540px;
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow-modal);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
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
  gap: 1rem;
}

.col-span-2 {
  grid-column: span 2;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}
</style>
