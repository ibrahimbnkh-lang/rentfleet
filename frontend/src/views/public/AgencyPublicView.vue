<template>
  <div class="agency-site">
    <!-- Top Navigation -->
    <header class="public-navbar">
      <div class="nav-container">
        <div class="brand-group">
          <div class="brand-icon">
            <AppLogo :size="24" color="#5A3CDF" />
          </div>
          <div>
            <div class="brand-name">RentFleet <span class="badge-algeria">DZ</span></div>
            <div class="brand-sub">{{ branch?.name ?? 'Agence Algérie' }}</div>
          </div>
        </div>

        <div class="nav-actions">
          <a :href="`tel:${branch?.phone}`" class="contact-pill" title="Appeler l'agence">
            <svg class="nav-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
            </svg>
            <span class="phone-text">{{ branch?.phone ?? '+213 21 00 00 00' }}</span>
          </a>
          <a
            :href="`https://wa.me/${cleanPhone(branch?.phone)}`"
            target="_blank"
            class="whatsapp-pill"
            title="Contacter sur WhatsApp"
          >
            <svg class="nav-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
            </svg>
            <span class="wa-text">WhatsApp</span>
          </a>
          <RouterLink to="/login" class="btn btn-ghost btn-sm nav-login-btn">
            <span class="pro-full-text">Espace Pro</span>
            <span class="pro-short-text">Pro</span>
          </RouterLink>
        </div>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="loading-wrap">
      <div class="spinner"></div>
      <p>Chargement du site de l'agence...</p>
    </div>

    <!-- Agency Hero -->
    <main v-else-if="branch" class="public-content">
      <section class="hero-wrapper">
        <div class="hero-card">
          <div class="hero-header-content">
            <h1 class="hero-title">{{ branch.name }}</h1>
            <p class="hero-address">
              {{ branch.address }}, {{ branch.city }} — Wilaya {{ branch.wilaya_code }}
            </p>

            <div class="hero-badge-row">
              <span class="hero-pill-glass">Agence Officielle RentFleet Algérie</span>
              <span class="hero-pill-white">
                <span class="status-dot"></span>
                Ouvert • 08h30 - 18h30
              </span>
              <span class="hero-pill-glass">Code : {{ branch.code }}</span>
            </div>
          </div>

          <!-- Quick Agency Feature Highlights -->
          <div class="agency-highlights">
            <div class="highlight-item">
              <div class="highlight-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="5" width="20" height="14" rx="2"/>
                  <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
              </div>
              <div class="highlight-info">
                <div class="highlight-val">DZD</div>
                <div class="highlight-lbl">Paiement Dinar / CIB / Espèces</div>
              </div>
            </div>

            <div class="highlight-item">
              <div class="highlight-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <circle cx="12" cy="12" r="4"/>
                  <line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/>
                  <line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/>
                  <line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/>
                  <line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/>
                </svg>
              </div>
              <div class="highlight-info">
                <div class="highlight-val">24/7</div>
                <div class="highlight-lbl">Assistance Dépannage Algérie</div>
              </div>
            </div>

            <div class="highlight-item">
              <div class="highlight-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <path d="M8 12l2.5 2.5L16 9"/>
                </svg>
              </div>
              <div class="highlight-info">
                <div class="highlight-val">0 DZD</div>
                <div class="highlight-lbl">Annulation gratuite jusqu'à 48h</div>
              </div>
            </div>

            <div class="highlight-item">
              <div class="highlight-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="M9 12l2 2 4-4"/>
                </svg>
              </div>
              <div class="highlight-info">
                <div class="highlight-val">100%</div>
                <div class="highlight-lbl">Véhicules climatisés & récents</div>
              </div>
            </div>
          </div>

          <!-- Divider -->
          <div class="hero-divider"></div>

          <!-- Switch to other branches -->
          <div class="branch-switcher">
            <span class="switcher-label">Changer d'agence :</span>
            <div class="switcher-btns">
              <button
                v-for="other in otherBranches"
                :key="other.code"
                :class="['branch-btn', other.code === branch.code && 'active']"
                @click="switchBranch(other.code)"
              >
                {{ other.city }} ({{ other.code }})
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Fleet Showcase Section -->
      <section class="fleet-section">
        <div class="section-head">
          <div>
            <h2 class="section-title">Flotte disponible à cette agence</h2>
            <p class="section-subtitle">Véhicules entretenus, assurés tous risques et prêts pour vos déplacements</p>
          </div>
          <div class="category-filters">
            <button
              v-for="cat in categories"
              :key="cat"
              :class="[
                'cat-btn',
                activeCategory === cat && 'active',
                getCategoryCount(cat) === 0 && 'disabled'
              ]"
              :disabled="getCategoryCount(cat) === 0"
              :title="getCategoryCount(cat) === 0 ? `${cat} (Aucun véhicule disponible)` : `${cat} (${getCategoryCount(cat)} disponible${getCategoryCount(cat) > 1 ? 's' : ''})`"
              @click="selectCategory(cat)"
            >
              <span>{{ cat }}</span>
              <span class="cat-pill" :class="{ 'zero': getCategoryCount(cat) === 0 }">
                {{ getCategoryCount(cat) }}
              </span>
            </button>
          </div>
        </div>

        <div v-if="filteredFleet.length === 0" class="empty-fleet-card card">
          <div class="empty-icon">🚗</div>
          <h3>Aucun véhicule disponible</h3>
          <p class="text-sm text-muted">Aucun véhicule n'est disponible actuellement pour cette agence.</p>
        </div>

        <div v-else class="fleet-grid">
          <div
            v-for="car in filteredFleet"
            :key="car.id"
            class="car-card card"
          >
            <div class="car-photo-box">
              <span class="car-card-badge">{{ car.category }}</span>
              <img
                :src="getCarImage(car)"
                :alt="car.name"
                class="car-showcase-photo"
                @error="(e) => ((e.target as HTMLImageElement).src = getCategoryPlaceholder(car.category))"
              />
            </div>

            <div class="car-meta">
              <h3 class="car-name">{{ car.name }}</h3>
              <p class="car-spec">{{ car.year }} • {{ car.transmission }} • {{ car.fuel }} • 5 Places</p>
            </div>

            <div class="car-features">
              <span class="feature-tag">❄️ Climatisation</span>
              <span class="feature-tag">🛡️ Tous risques</span>
              <span class="feature-tag">🛣️ Illimité</span>
            </div>

            <div class="car-footer">
              <div class="price-box">
                <span class="price-amount">{{ formatDzd(car.dailyPrice) }}</span>
                <span class="price-unit">DZD / jour</span>
              </div>
              <button class="btn btn-primary car-reserve-btn" @click="openBookingModal(car)">
                Réserver
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Agency Location & Services Details -->
      <section class="info-section">
        <div class="info-grid">
          <!-- Coordinates card -->
          <div class="card info-card">
            <h3 class="card-title">📍 Coordonnées & Accès</h3>
            <div class="info-lines">
              <div class="line">
                <span class="line-icon">🏢</span>
                <div>
                  <strong>Adresse complète :</strong>
                  <p>{{ branch.address }}, {{ branch.city }}</p>
                </div>
              </div>
              <div class="line">
                <span class="line-icon">📞</span>
                <div>
                  <strong>Téléphone agence :</strong>
                  <p><a :href="`tel:${branch.phone}`" class="text-primary">{{ branch.phone }}</a></p>
                </div>
              </div>
              <div v-if="branch.email" class="line">
                <span class="line-icon">✉️</span>
                <div>
                  <strong>Email professionnel :</strong>
                  <p><a :href="`mailto:${branch.email}`" class="text-primary">{{ branch.email }}</a></p>
                </div>
              </div>
              <div class="line">
                <span class="line-icon">⏰</span>
                <div>
                  <strong>Horaires d'ouverture :</strong>
                  <p>Samedi - Jeudi : 08h30 – 18h30<br><span class="text-muted">Vendredi : Fermé (Permanence urgences)</span></p>
                </div>
              </div>
            </div>

            <a
              :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(branch.name + ' ' + branch.address + ' ' + branch.city)}`"
              target="_blank"
              class="btn btn-secondary btn-block mt-4"
            >
              🗺️ Ouvrir l'itinéraire Google Maps
            </a>
          </div>

          <!-- Rental Conditions Card -->
          <div class="card info-card">
            <h3 class="card-title">📄 Conditions de Location en Algérie</h3>
            <ul class="conditions-list">
              <li>
                <span class="check-icon">✓</span>
                <div>
                  <strong>Âge minimal :</strong>
                  <span>23 ans révolus avec permis de conduire valide depuis plus de 2 ans.</span>
                </div>
              </li>
              <li>
                <span class="check-icon">✓</span>
                <div>
                  <strong>Documents requis :</strong>
                  <span>Pièce d'identité nationale (CNI biométrique ou passeport) et permis de conduire algérien original.</span>
                </div>
              </li>
              <li>
                <span class="check-icon">✓</span>
                <div>
                  <strong>Caution de garantie :</strong>
                  <span>Caution restituée au retour du véhicule (dépôt par chèque de banque ou empreinte monétique).</span>
                </div>
              </li>
              <li>
                <span class="check-icon">✓</span>
                <div>
                  <strong>Restitution inter-agences :</strong>
                  <span>Possibilité de restitution dans une autre agence du réseau (Alger, Oran, Constantine).</span>
                </div>
              </li>
            </ul>

            <div class="help-box mt-4">
              <span class="help-icon">ℹ️</span>
              <span>Besoin d'un devis sur mesure pour société ou longue durée ? Contactez directement notre chef d'agence.</span>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Error State -->
    <div v-else class="error-wrap card">
      <h2>Agence introuvable</h2>
      <p>L'agence demandée n'existe pas ou est temporairement indisponible.</p>
      <RouterLink to="/core/branches" class="btn btn-primary mt-3">Retour à l'administration</RouterLink>
    </div>

    <!-- Booking Modal -->
    <div v-if="showModal && selectedCar" class="modal-backdrop" @click.self="showModal = false">
      <div class="modal-content card max-w-lg">
        <div class="modal-header">
          <div>
            <h3>Demande de réservation — {{ selectedCar.name }}</h3>
            <p class="text-xs text-muted">{{ branch?.name }} ({{ branch?.code }})</p>
          </div>
          <button class="modal-close-btn" @click="showModal = false">✕</button>
        </div>

        <div v-if="bookingSuccess" class="alert alert-success">
          🎉 Votre demande a été transmise à l'agence {{ branch?.name }} ! Un agent vous contactera sous 30 minutes au numéro indiqué.
          <div class="mt-3">
            <button class="btn btn-primary btn-sm" @click="showModal = false">Fermer</button>
          </div>
        </div>

        <form v-else @submit.prevent="submitBooking">
          <div class="car-summary-banner">
            <div class="car-sum-thumb">
              <img :src="getCarImage(selectedCar)" :alt="selectedCar.name" class="car-sum-photo" />
            </div>
            <div class="car-sum-info">
              <strong>{{ selectedCar.name }}</strong>
              <div class="car-sum-price">{{ formatDzd(selectedCar.dailyPrice) }} DZD / jour</div>
            </div>
          </div>

          <div class="form-grid mt-3">
            <div class="form-group col-span-2">
              <label class="label">Nom & Prénom *</label>
              <input v-model="bookingForm.clientName" required class="input" placeholder="ex: Sofiane Haddad" />
            </div>

            <div class="form-group">
              <label class="label">Numéro de téléphone *</label>
              <input v-model="bookingForm.phone" required class="input" placeholder="+213 555 00 00 00" />
            </div>

            <div class="form-group">
              <label class="label">Adresse e-mail</label>
              <input v-model="bookingForm.email" type="email" class="input" placeholder="sofiane@email.dz" />
            </div>

            <div class="form-group">
              <label class="label">Date de départ *</label>
              <input v-model="bookingForm.startDate" type="date" required class="input" />
            </div>

            <div class="form-group">
              <label class="label">Date de retour *</label>
              <input v-model="bookingForm.endDate" type="date" required class="input" />
            </div>
          </div>

          <!-- Total Calculation -->
          <div class="total-bar mt-3">
            <span>Estimation totale ({{ calcDays }} jour(s)) :</span>
            <span class="total-price">{{ formatDzd(calcTotal) }} DZD</span>
          </div>

          <div class="modal-footer mt-4">
            <button type="button" class="btn btn-secondary" @click="showModal = false">Annuler</button>
            <button type="submit" class="btn btn-primary">Confirmer ma demande</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Public Footer -->
    <footer class="public-footer">
      <div class="footer-inner">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <div class="brand-icon" style="width: 28px; height: 28px; border-radius: 6px;">
              <AppLogo :size="16" color="#5A3CDF" />
            </div>
            <div class="brand-name" style="font-size: 1.1rem;">RentFleet Algérie</div>
          </div>
          <p class="text-xs text-muted">Plateforme nationale de gestion et réservation de véhicules de location</p>
        </div>
        <div class="footer-links">
          <span>{{ branch?.city }} • Wilaya {{ branch?.wilaya_code }}</span>
          <span>© 2026 RentFleet. Tous droits réservés.</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api'
import AppLogo from '@/components/AppLogo.vue'

const route = useRoute()
const router = useRouter()

const branch = ref<any>(null)
const loading = ref(true)
const activeCategory = ref('Tous')
const showModal = ref(false)
const selectedCar = ref<any>(null)
const bookingSuccess = ref(false)

const otherBranches = ref<any[]>([
  { code: 'ALG-01', city: 'Alger' },
  { code: 'ORN-01', city: 'Oran' },
  { code: 'CST-01', city: 'Constantine' },
])

const defaultCategories = ['Tous', 'Économique', 'Berline', 'SUV & 4x4', 'Premium']

const categories = computed(() => {
  const cats = new Set<string>()
  cats.add('Tous')
  defaultCategories.filter(c => c !== 'Tous').forEach(c => cats.add(c))
  fleet.value.forEach(car => {
    if (car.category) {
      cats.add(car.category)
    }
  })
  return Array.from(cats)
})

function getCategoryCount(cat: string): number {
  if (cat === 'Tous') return fleet.value.length
  return fleet.value.filter(c => c.category === cat).length
}

function selectCategory(cat: string) {
  if (getCategoryCount(cat) === 0) return
  activeCategory.value = cat
}

const fleet = ref<any[]>([])

function getVehicleEmoji(brand: string, cat?: string): string {
  const c = (cat || '').toLowerCase()
  if (c.includes('suv') || c.includes('4x4')) return '🚙'
  if (c.includes('prem') || c.includes('luxe')) return '✨'
  if (c.includes('berline')) return '🚘'
  return '🚗'
}

const filteredFleet = computed(() => {
  if (activeCategory.value === 'Tous') return fleet.value
  return fleet.value.filter(c => c.category === activeCategory.value)
})

watch(fleet, () => {
  if (activeCategory.value !== 'Tous' && getCategoryCount(activeCategory.value) === 0) {
    activeCategory.value = 'Tous'
  }
}, { deep: true })

const bookingForm = ref({
  clientName: '',
  phone: '+213 ',
  email: '',
  startDate: new Date().toISOString().split('T')[0],
  endDate: new Date(Date.now() + 3 * 86400000).toISOString().split('T')[0],
})

const calcDays = computed(() => {
  const start = new Date(bookingForm.value.startDate).getTime()
  const end = new Date(bookingForm.value.endDate).getTime()
  const diff = Math.ceil((end - start) / (1000 * 3600 * 24))
  return diff > 0 ? diff : 1
})

const calcTotal = computed(() => {
  return (selectedCar.value?.dailyPrice ?? 0) * calcDays.value
})

function getCategoryPlaceholder(catName?: string): string {
  const norm = (catName || '').toLowerCase()
  if (norm.includes('suv') || norm.includes('4x4')) return '/placeholders/suv-4x4.png'
  if (norm.includes('berlin')) return '/placeholders/berline.png'
  if (norm.includes('premium') || norm.includes('luxe')) return '/placeholders/premium.png'
  if (norm.includes('eco') || norm.includes('éco')) return '/placeholders/economique.png'
  return '/car-placeholder.png'
}

function getCarImage(car: any): string {
  if (!car) return '/car-placeholder.png'
  if (car.image) return car.image
  if (car.photo_url) return car.photo_url
  return getCategoryPlaceholder(car.category)
}

function formatDzd(val: number): string {
  return new Intl.NumberFormat('fr-DZ').format(val)
}

function cleanPhone(phone?: string): string {
  if (!phone) return '213550000000'
  return phone.replace(/[^0-9]/g, '')
}

async function loadBranch(code: string) {
  loading.value = true
  try {
    // Try fetching branch with fleet
    const { data } = await api.get(`/public/branches/${code}/fleet`)
    branch.value = data.data.branch
    const apiVehicles = data.data.vehicles || []
    fleet.value = apiVehicles.map((v: any) => ({
      id: v.id,
      name: `${v.brand} ${v.model}`,
      category: v.category?.name || 'Standard',
      year: v.year,
      transmission: v.transmission === 'automatique' ? 'Automatique' : 'Manuelle',
      fuel: v.fuel_type ? v.fuel_type.charAt(0).toUpperCase() + v.fuel_type.slice(1) : 'Essence',
      dailyPrice: Number(v.daily_price_dzd || v.category?.daily_rate_dzd || 6000),
      image: v.photos?.find((p: any) => p.is_primary)?.file_path ?? v.photo_url ?? getCategoryPlaceholder(v.category?.name),
      emoji: getVehicleEmoji(v.brand, v.category?.name),
    }))
    if (data.data.branches && data.data.branches.length > 0) {
      otherBranches.value = data.data.branches
    }
  } catch (err) {
    console.warn('Fallback to public branch endpoint', err)
    try {
      const { data } = await api.get(`/public/branches/${code}`)
      branch.value = data.data
      fleet.value = []
    } catch (e) {
      console.error('Failed to load agency public profile', e)
      branch.value = null
      fleet.value = []
    }
  } finally {
    loading.value = false
  }
}

function switchBranch(code: string) {
  router.push(`/agences/${code}`)
  loadBranch(code)
}

function openBookingModal(car: any) {
  selectedCar.value = car
  bookingSuccess.value = false
  showModal.value = true
}

function submitBooking() {
  bookingSuccess.value = true
}

onMounted(() => {
  const code = (route.params.code as string) || 'ALG-01'
  loadBranch(code)
})
</script>

<style scoped>
.agency-site {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--color-surface-2);
  color: var(--color-text-primary);
  font-family: var(--font-sans);
}

/* Navbar */
.public-navbar {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 50;
  box-shadow: var(--shadow-card);
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0.85rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--color-primary-50);
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text-primary);
  line-height: 1.1;
}

.badge-algeria {
  background: #16A34A;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  margin-left: 0.25rem;
}

.brand-sub {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.contact-pill, .whatsapp-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.9rem;
  border-radius: 99px;
  font-size: 0.825rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
  line-height: 1;
}

.nav-btn-icon {
  flex-shrink: 0;
}

.contact-pill {
  background: var(--color-primary-50);
  color: var(--color-primary);
  border: 1px solid rgba(79, 70, 229, 0.15);
}

.contact-pill:hover {
  background: var(--color-primary-100, #E0E7FF);
}

.whatsapp-pill {
  background: #DCFCE7;
  color: #16A34A;
  border: 1px solid rgba(22, 163, 74, 0.2);
}

.whatsapp-pill:hover {
  background: #BBF7D0;
}

.nav-login-btn {
  font-weight: 600;
  border-radius: var(--radius-sm);
}

.pro-short-text {
  display: none;
}

.pro-full-text {
  display: inline;
}

/* Mobile Responsive Navbar */
@media (max-width: 680px) {
  .nav-container {
    padding: 0.6rem 0.85rem;
    gap: 0.5rem;
  }

  .brand-group {
    gap: 0.5rem;
    min-width: 0;
  }

  .brand-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    flex-shrink: 0;
  }

  .brand-name {
    font-size: 1.05rem;
    white-space: nowrap;
  }

  .brand-sub {
    font-size: 0.68rem;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .nav-actions {
    gap: 0.35rem;
    flex-shrink: 0;
  }

  .contact-pill, .whatsapp-pill {
    padding: 0.45rem;
    min-width: 36px;
    height: 36px;
    justify-content: center;
    border-radius: 50%;
  }

  .phone-text, .wa-text {
    display: none;
  }

  .pro-full-text {
    display: none;
  }

  .pro-short-text {
    display: inline;
    font-weight: 700;
  }

  .nav-login-btn {
    padding: 0.4rem 0.65rem;
    font-size: 0.775rem;
    height: 36px;
    display: inline-flex;
    align-items: center;
  }
}

@media (max-width: 380px) {
  .brand-sub {
    display: none;
  }
}

/* Hero Section */
.hero-wrapper {
  max-width: 1240px;
  margin: 1.5rem auto 2.5rem;
  padding: 0 1.5rem;
}

.hero-card {
  position: relative;
  border-radius: 24px;
  padding: 3.5rem 2.5rem 2.25rem;
  overflow: hidden;
  color: #ffffff;
  /* Diagonal repeating translucent stripes + vibrant indigo-purple gradient */
  background: 
    repeating-linear-gradient(
      -45deg,
      rgba(255, 255, 255, 0.045) 0,
      rgba(255, 255, 255, 0.045) 16px,
      transparent 16px,
      transparent 32px
    ),
    linear-gradient(135deg, #5345E6 0%, #6857F3 50%, #4D3FE0 100%);
  box-shadow: 0 14px 38px -8px rgba(79, 70, 229, 0.32);
}

/* Header Text (Centered) */
.hero-header-content {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 2.25rem;
}

.hero-title {
  font-size: 2.75rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.025em;
  margin: 0 0 0.5rem 0;
  line-height: 1.2;
}

.hero-address {
  font-size: 1.05rem;
  color: rgba(255, 255, 255, 0.92);
  margin: 0 0 1.25rem 0;
  font-weight: 400;
}

.hero-badge-row {
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
}

.hero-pill-glass {
  background: rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: #ffffff;
  padding: 0.35rem 1.15rem;
  border-radius: 99px;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.hero-pill-white {
  background: #ffffff;
  color: #0F172A;
  padding: 0.35rem 1.15rem;
  border-radius: 99px;
  font-size: 0.85rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.status-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #10B981;
  display: inline-block;
}

/* 4 Value Proposition Cards */
.agency-highlights {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .agency-highlights {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .agency-highlights {
    grid-template-columns: 1fr;
  }
}

.highlight-item {
  background: #ffffff;
  border-radius: 18px;
  padding: 1.15rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  text-align: left;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.highlight-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.highlight-icon {
  width: 48px;
  height: 48px;
  border-radius: 13px;
  background: linear-gradient(135deg, #FB923C 0%, #EA580C 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  flex-shrink: 0;
  box-shadow: 0 3px 8px rgba(234, 88, 12, 0.25);
}

.highlight-info {
  display: flex;
  flex-direction: column;
}

.highlight-val {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0F172A;
  line-height: 1.2;
}

.highlight-lbl {
  font-size: 0.775rem;
  color: #64748B;
  margin-top: 0.2rem;
  line-height: 1.35;
  font-weight: 500;
}

/* Divider Line */
.hero-divider {
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.22);
  margin: 1.75rem 0 1.5rem;
}

/* Branch Switcher (Bottom, Centered) */
.branch-switcher {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.switcher-label {
  font-size: 0.925rem;
  font-weight: 700;
  color: #ffffff;
}

.switcher-btns {
  display: flex;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.branch-btn {
  background: rgba(255, 255, 255, 0.16);
  color: #ffffff;
  border: 1.5px solid rgba(255, 255, 255, 0.45);
  padding: 0.45rem 1.35rem;
  border-radius: 99px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}

.branch-btn:hover {
  background: rgba(255, 255, 255, 0.26);
  border-color: #ffffff;
}

.branch-btn.active {
  background: #ffffff;
  color: #4F46E5;
  border-color: #ffffff;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

/* Fleet Section */
.public-content {
  flex: 1;
}

.fleet-section {
  max-width: 1200px;
  margin: 3rem auto;
  padding: 0 1.5rem;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.85rem;
  font-weight: 800;
}

.section-subtitle {
  color: var(--color-text-secondary);
  font-size: 0.95rem;
  margin-top: 0.25rem;
}

.category-filters {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.cat-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  padding: 0.4rem 0.95rem;
  border-radius: 99px;
  font-size: 0.825rem;
  font-weight: 600;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.2s;
  user-select: none;
}

.cat-btn:hover:not(:disabled) {
  background: var(--color-surface-3);
  border-color: var(--color-border);
}

.cat-btn.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.cat-btn:disabled,
.cat-btn.disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: var(--color-surface-2, #F8FAFC);
  border-color: var(--color-border, #E2E8F0);
  color: var(--color-text-muted, #94A3B8);
  box-shadow: none;
  transform: none;
}

.cat-pill {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 99px;
  background: var(--color-surface-3, #E2E8F0);
  color: var(--color-text-secondary, #475569);
  line-height: 1.2;
}

.cat-btn.active .cat-pill {
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
}

.cat-btn:disabled .cat-pill,
.cat-pill.zero {
  background: transparent;
  color: var(--color-text-muted, #94A3B8);
  border: 1px solid var(--color-border, #E2E8F0);
}

.fleet-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.empty-fleet-card {
  text-align: center;
  padding: 3rem 1.5rem;
  background: var(--color-surface, #ffffff);
  border-radius: var(--radius-lg, 16px);
  border: 1px dashed var(--color-border);
}

.empty-fleet-card .empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.car-card {
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  gap: 0.85rem;
  position: relative;
  background: var(--color-surface, #ffffff);
  border-radius: var(--radius-lg, 16px);
  border: 1px solid var(--color-border);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.car-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.09), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
  border-color: rgba(79, 70, 229, 0.25);
}

.car-photo-box {
  background: linear-gradient(180deg, #F8FAFC 0%, #EEF2F6 100%);
  border-radius: var(--radius-md, 12px);
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  padding: 0.75rem 1rem;
}

.car-card-badge {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  z-index: 2;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: var(--color-primary, #4F46E5);
  border: 1px solid rgba(79, 70, 229, 0.18);
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 99px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  letter-spacing: 0.01em;
}

.car-showcase-photo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.car-card:hover .car-showcase-photo {
  transform: scale(1.06);
}

.car-sum-thumb {
  width: 70px;
  height: 50px;
  border-radius: var(--radius-sm);
  overflow: hidden;
  flex-shrink: 0;
  background: var(--color-surface-2);
}

.car-sum-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.car-meta {
  display: flex;
  flex-direction: column;
}

.car-name {
  font-size: 1.18rem;
  font-weight: 700;
  color: var(--color-text, #0F172A);
  margin: 0;
  line-height: 1.35;
}

.car-spec {
  font-size: 0.825rem;
  color: var(--color-text-secondary, #64748B);
  margin: 0.35rem 0 0 0;
  line-height: 1.4;
}

.car-features {
  display: flex;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.feature-tag {
  background: var(--color-surface-2, #F1F5F9);
  border: 1px solid var(--color-border-subtle, rgba(0, 0, 0, 0.05));
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  color: var(--color-text-secondary, #475569);
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.car-footer {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border, #E2E8F0);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.car-reserve-btn {
  padding: 0.65rem 1.45rem;
  font-size: 0.95rem;
  font-weight: 700;
  border-radius: 10px;
  background: var(--color-primary, #4F46E5);
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  white-space: nowrap;
  letter-spacing: 0.01em;
  cursor: pointer;
  border: none;
}

.car-reserve-btn:hover {
  background: var(--color-primary-dark, #4338CA);
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(79, 70, 229, 0.4);
}

.car-reserve-btn:active {
  transform: translateY(0);
}

.price-box {
  display: flex;
  flex-direction: column;
}

.price-amount {
  font-size: 1.45rem;
  font-weight: 800;
  color: var(--color-primary, #4F46E5);
  line-height: 1.1;
  letter-spacing: -0.02em;
}

.price-unit {
  font-size: 0.75rem;
  color: var(--color-text-muted, #94A3B8);
  margin-top: 0.2rem;
  font-weight: 500;
  display: block;
}

/* Info Section */
.info-section {
  max-width: 1200px;
  margin: 0 auto 4rem;
  padding: 0 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
  gap: 1.5rem;
}

.info-card {
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.info-lines {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  font-size: 0.9rem;
}

.line {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.line-icon {
  font-size: 1.25rem;
}

.btn-block {
  width: 100%;
}

.conditions-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  font-size: 0.85rem;
}

.conditions-list li {
  display: flex;
  gap: 0.6rem;
  align-items: flex-start;
}

.check-icon {
  background: var(--color-success-light);
  color: var(--color-success);
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.help-box {
  background: var(--color-primary-50);
  border: 1px solid var(--color-primary-100);
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.25rem;
}

.modal-content {
  width: 100%;
  max-width: 540px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
  padding: 1.85rem 2rem;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 1.1rem;
  border-bottom: 1px solid var(--color-border, #E2E8F0);
  margin-bottom: 1.35rem;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--color-text, #0F172A);
  margin: 0;
  line-height: 1.3;
}

.modal-header p {
  margin: 0.35rem 0 0;
  font-size: 0.85rem;
  color: var(--color-text-secondary, #64748B);
}

.modal-close-btn {
  background: var(--color-surface-2, #F1F5F9);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  color: var(--color-text-secondary, #64748B);
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.modal-close-btn:hover {
  background: var(--color-surface-3, #E2E8F0);
  color: var(--color-text, #0F172A);
  transform: rotate(90deg);
}

.car-summary-banner {
  background: linear-gradient(135deg, rgba(79, 70, 229, 0.06) 0%, rgba(79, 70, 229, 0.02) 100%);
  border: 1px solid rgba(79, 70, 229, 0.16);
  padding: 0.85rem 1.15rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.car-sum-thumb {
  width: 80px;
  height: 52px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  background: #ffffff;
  border: 1px solid var(--color-border, #E2E8F0);
  display: flex;
  align-items: center;
  justify-content: center;
}

.car-sum-photo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 3px;
}

.car-sum-info strong {
  display: block;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text, #0F172A);
}

.car-sum-price {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-primary, #4F46E5);
  margin-top: 0.25rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.15rem 1rem;
  margin-bottom: 1.5rem;
}

.col-span-2 {
  grid-column: span 2;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group .label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text, #334155);
}

.form-group .input {
  width: 100%;
  height: 42px;
  padding: 0.55rem 0.85rem;
  border: 1px solid var(--color-border, #CBD5E1);
  border-radius: 8px;
  font-size: 0.9rem;
  background: #ffffff;
  color: var(--color-text, #0F172A);
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-group .input:focus {
  outline: none;
  border-color: var(--color-primary, #4F46E5);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.total-bar {
  background: var(--color-surface-2, #F8FAFC);
  border: 1px solid var(--color-border, #E2E8F0);
  padding: 0.9rem 1.25rem;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  font-size: 0.925rem;
  color: var(--color-text-secondary, #475569);
  margin-bottom: 1.5rem;
}

.total-price {
  color: var(--color-primary, #4F46E5);
  font-size: 1.35rem;
  font-weight: 800;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.85rem;
  padding-top: 1.15rem;
  border-top: 1px solid var(--color-border, #E2E8F0);
}

.modal-footer .btn {
  padding: 0.65rem 1.4rem;
  font-size: 0.925rem;
  font-weight: 600;
  border-radius: 8px;
}

@media (max-width: 640px) {
  .modal-content {
    padding: 1.25rem;
    max-height: 92vh;
  }
  .form-grid {
    grid-template-columns: 1fr;
    gap: 0.9rem;
  }
  .col-span-2 {
    grid-column: span 1;
  }
  .modal-footer {
    flex-direction: column-reverse;
  }
  .modal-footer .btn {
    width: 100%;
  }
}

/* Footer */
.public-footer {
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  padding: 2rem 1.5rem;
  margin-top: auto;
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-links {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.loading-wrap, .error-wrap {
  text-align: center;
  padding: 5rem 1.5rem;
  max-width: 500px;
  margin: 4rem auto;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
