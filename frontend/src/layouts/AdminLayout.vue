<template>
  <div class="admin-shell" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <!-- ─── Sidebar ────────────────────────────────────────────────────────── -->
    <aside class="sidebar" role="navigation" :aria-label="t('nav.core')">
      <!-- Brand -->
      <div class="sidebar-brand">
        <RouterLink to="/dashboard" class="brand-link" :title="sidebarCollapsed ? 'RentFleet' : undefined">
          <div class="brand-logo">
            <AppLogo :size="22" color="#ffffff" />
          </div>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="brand-name">RentFleet</span>
          </Transition>
        </RouterLink>
        <button
          class="sidebar-toggle"
          @click="sidebarCollapsed = !sidebarCollapsed"
          :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path :d="sidebarCollapsed ? 'M6 4l4 4-4 4' : 'M10 4l-4 4 4 4'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <!-- Dashboard -->
        <RouterLink to="/dashboard" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="1" y="1" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <rect x="10" y="1" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <rect x="1" y="10" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <rect x="10" y="10" width="7" height="7" rx="2" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.dashboard') }}</span>
          </Transition>
        </RouterLink>

        <!-- M00 Core Section -->
        <div v-if="hasAny(['core.branch.view', 'core.user.view', 'core.role.view', 'core.audit.view', 'core.license.view'])" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.core') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('core.branch.view')" to="/core/branches" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M2 16V8l7-5 7 5v8" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <rect x="6" y="11" width="6" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.branches') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('core.user.view')" to="/core/users" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="6" r="3.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M2 16c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.users') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('core.role.view')" to="/core/roles" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M9 5v4l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.roles') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('core.audit.view')" to="/core/audit-log" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M14 2H4a1 1 0 00-1 1v12a1 1 0 001 1h10a1 1 0 001-1V3a1 1 0 00-1-1z" stroke="currentColor" stroke-width="1.5"/>
              <path d="M5 6h8M5 9h8M5 12h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.auditLog') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('core.license.view')" to="/core/license" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="1.5" y="3.5" width="15" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M5 8h4M5 11h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="13" cy="9.5" r="2" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.license') }}</span>
          </Transition>
        </RouterLink>

        <!-- M01 Fleet Section -->
        <div v-if="hasAny(['fleet.vehicle.view', 'fleet.category.view', 'fleet.document.view'])" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.fleet') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('fleet.vehicle.view')" to="/fleet/vehicles" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="1" y="6" width="16" height="8" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M3 6l2.5-3h7L15 6" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <circle cx="5" cy="14" r="1.5" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="13" cy="14" r="1.5" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.vehicles') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('fleet.category.view')" to="/fleet/categories" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M2 5h14M2 9h14M2 13h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.categories') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('fleet.document.view')" to="/fleet/expiring-documents" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M9 2v5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="9" cy="9" r="1" fill="currentColor"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.expiringDocs') }}</span>
          </Transition>
        </RouterLink>

        <!-- M02 Customers Section -->
        <div v-if="can('customers.customer.view')" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.customersSection') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('customers.customer.view')" to="/customers" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="6.5" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/>
              <path d="M1 16c0-2.761 2.462-5 5.5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="13" cy="8" r="2.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M10 16c0-2.209 1.343-4 3-4s3 1.791 3 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.customers') }}</span>
          </Transition>
        </RouterLink>

        <!-- M03 Reservations & Planning Section -->
        <div v-if="hasAny(['reservations.reservation.view', 'reservations.planning.view'])" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.reservationsSection') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('reservations.reservation.view')" to="/reservations" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2" y="3" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M2 7h14M6 1.5v3M12 1.5v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <path d="M6 11l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.reservations') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('reservations.planning.view')" to="/reservations/planning" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <path d="M2 6h14M6 10h6M6 13h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.planning') }}</span>
          </Transition>
        </RouterLink>

        <!-- M05 Contracts Section -->
        <div v-if="can('contracts.contract.view')" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.contractsSection') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('contracts.contract.view')" to="/contracts" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <path d="M11 1.5H4a1.5 1.5 0 00-1.5 1.5v12A1.5 1.5 0 004 16.5h10a1.5 1.5 0 001.5-1.5V6L11 1.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M11 1.5V6h4.5" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M5.5 10h7M5.5 13h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.contracts') }}</span>
          </Transition>
        </RouterLink>

        <!-- M07 Invoicing & Payments Section -->
        <div v-if="hasAny(['invoicing.invoice.view', 'invoicing.payment.view'])" class="nav-section">
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-section-label">{{ t('nav.invoicingSection') }}</span>
          </Transition>
        </div>

        <RouterLink v-if="can('invoicing.invoice.view')" to="/invoices" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2.5" y="1.5" width="13" height="15" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M6 5.5h6M6 8.5h6M6 11.5h3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.invoices') }}</span>
          </Transition>
        </RouterLink>

        <RouterLink v-if="can('invoicing.payment.view')" to="/payments" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="1.5" y="4" width="15" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/>
              <circle cx="9" cy="9.5" r="2.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M1.5 7.5h15" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.payments') }}</span>
          </Transition>
        </RouterLink>
      </nav>

      <!-- Bottom: Settings + User -->
      <div class="sidebar-bottom">
        <RouterLink v-if="can('core.settings.view')" to="/core/settings" class="nav-item" active-class="active">
          <span class="nav-icon">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/>
              <path d="M9 1v2M9 15v2M1 9h2M15 9h2M3.22 3.22l1.41 1.41M13.37 13.37l1.41 1.41M3.22 14.78l1.41-1.41M13.37 4.63l1.41-1.41" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </span>
          <Transition name="fade">
            <span v-if="!sidebarCollapsed" class="nav-label">{{ t('nav.settings') }}</span>
          </Transition>
        </RouterLink>

        <div class="user-widget">
          <div class="user-avatar">
            {{ userInitials }}
          </div>
          <Transition name="fade">
            <div v-if="!sidebarCollapsed" class="user-info">
              <span class="user-name">{{ auth.user?.name }}</span>
              <span class="user-role">{{ auth.user?.roles?.[0] ?? 'User' }}</span>
            </div>
          </Transition>
          <Transition name="fade">
            <button v-if="!sidebarCollapsed" class="user-logout" @click="handleLogout" :title="t('auth.logout')">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M6 2H3a1 1 0 00-1 1v10a1 1 0 001 1h3M11 11l3-3-3-3M14 8H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </Transition>
        </div>
      </div>
    </aside>

    <!-- ─── Main Content ──────────────────────────────────────────────────────── -->
    <main class="main-content" role="main">
      <!-- Top Bar -->
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">{{ currentPageTitle }}</h1>
        </div>
        <div class="topbar-right">
          <!-- Language Switcher -->
          <div class="lang-switcher">
            <button
              v-for="lang in ['fr', 'en']"
              :key="lang"
              class="lang-btn"
              :class="{ active: locale === lang }"
              @click="switchLocale(lang)"
            >
              {{ lang.toUpperCase() }}
            </button>
          </div>

          <!-- Notifications Bell (placeholder) -->
          <button class="icon-btn" title="Notifications">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M10 2a6 6 0 016 6c0 2.5.8 4 2 5H2c1.2-1 2-2.5 2-5a6 6 0 016-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
              <path d="M8 16a2 2 0 004 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <div class="page-content">
        <RouterView v-slot="{ Component, route }">
          <Transition name="slide-up" mode="out-in">
            <component :is="Component" :key="route.path" />
          </Transition>
        </RouterView>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/AppLogo.vue'

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const sidebarCollapsed = ref(false)

const userInitials = computed(() => {
  const name = auth.user?.name ?? ''
  return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase() || 'U'
})

const currentPageTitle = computed(() => {
  const titleKey = route.meta?.title as string
  return titleKey ? t(titleKey) : 'RentFleet'
})

function can(permission: string): boolean {
  if (auth.hasRole('Super Admin')) return true
  return auth.hasPermission(permission)
}

function hasAny(permissions: string[]): boolean {
  if (auth.hasRole('Super Admin')) return true
  return permissions.some(p => auth.hasPermission(p))
}

function switchLocale(lang: string) {
  locale.value = lang
  localStorage.setItem('locale', lang)
  document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr'
}

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
/* ─── Admin Shell ─────────────────────────────────────────────────────────── */
.admin-shell {
  display: grid;
  grid-template-columns: 256px 1fr;
  min-block-size: 100dvh;
  transition: grid-template-columns 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.admin-shell.sidebar-collapsed {
  grid-template-columns: 72px 1fr;
}

/* ─── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar {
  background: linear-gradient(180deg, #5A3CDF 0%, #4028C4 100%);
  display: flex;
  flex-direction: column;
  block-size: 100dvh;
  position: sticky;
  inset-block-start: 0;
  overflow: hidden;
  box-shadow: var(--shadow-sidebar);
  z-index: 100;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.25rem 1rem;
  border-block-end: 1px solid rgba(255,255,255,0.1);
  min-block-size: 64px;
  position: relative;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  min-width: 0;
}

.brand-logo {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(255,255,255,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.brand-name {
  font-size: 1.125rem;
  font-weight: 800;
  color: #fff;
  white-space: nowrap;
  letter-spacing: -0.02em;
}

.sidebar-toggle {
  margin-inline-start: auto;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: rgba(255,255,255,0.1);
  border: none;
  cursor: pointer;
  color: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.2s;
}
.sidebar-toggle:hover { background: rgba(255,255,255,0.2); }

/* ─── Navigation ─────────────────────────────────────────────────────────── */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-nav::-webkit-scrollbar { width: 0; }

.nav-section {
  padding-inline: 0.5rem;
  padding-block: 0.75rem 0.25rem;
}

.nav-section-label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(255,255,255,0.45);
  white-space: nowrap;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  border-radius: 10px;
  color: rgba(255,255,255,0.75);
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
  white-space: nowrap;
  border: none;
  background: none;
  width: 100%;
  font-size: 0.875rem;
  font-weight: 500;
  font-family: var(--font-sans);
}

.nav-item:hover {
  background: rgba(255,255,255,0.1);
  color: #fff;
}

.nav-item.active {
  background: rgba(255,255,255,0.18);
  color: #fff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.nav-icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.nav-label {
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ─── Sidebar Bottom ─────────────────────────────────────────────────────── */
.sidebar-bottom {
  padding: 0.75rem;
  border-block-start: 1px solid rgba(255,255,255,0.1);
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.user-widget {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 0.75rem;
  border-radius: 10px;
  background: rgba(0,0,0,0.15);
  margin-block-start: 0.25rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(255,159,10,0.8);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  overflow: hidden;
}

.user-name {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  display: block;
  font-size: 0.6875rem;
  color: rgba(255,255,255,0.55);
  white-space: nowrap;
}

.user-logout {
  background: none;
  border: none;
  color: rgba(255,255,255,0.5);
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  transition: color 0.15s, background 0.15s;
}
.user-logout:hover {
  color: #fff;
  background: rgba(255,255,255,0.1);
}

/* ─── Main Content ────────────────────────────────────────────────────────── */
.main-content {
  display: flex;
  flex-direction: column;
  min-block-size: 100dvh;
  overflow: hidden;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  block-size: 64px;
  background: var(--color-surface);
  border-block-end: 1px solid var(--color-border);
  position: sticky;
  inset-block-start: 0;
  z-index: 50;
}

.topbar-left { display: flex; align-items: center; gap: 1rem; }
.topbar-right { display: flex; align-items: center; gap: 0.75rem; }

.page-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.lang-switcher {
  display: flex;
  gap: 2px;
  background: var(--color-surface-3);
  padding: 3px;
  border-radius: 8px;
}

.lang-btn {
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  border: none;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: var(--color-text-secondary);
  transition: all 0.15s;
  font-family: var(--font-sans);
}

.lang-btn.active {
  background: var(--color-surface);
  color: var(--color-primary);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  background: var(--color-surface-3);
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}
.icon-btn:hover {
  background: var(--color-surface-2);
  color: var(--color-text-primary);
}

.page-content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}
</style>
