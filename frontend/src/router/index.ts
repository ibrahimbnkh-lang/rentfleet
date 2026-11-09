import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ─── Auth ─────────────────────────────────────────────────────────────
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { public: true, layout: 'blank' },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/auth/ForgotPasswordView.vue'),
      meta: { public: true, layout: 'blank' },
    },
    // ─── Public Single Agency Website ─────────────────────────────────────
    {
      path: '/agences/:code',
      name: 'agency-public',
      component: () => import('@/views/public/AgencyPublicView.vue'),
      meta: { public: true, layout: 'blank' },
    },

    // ─── Admin Shell ───────────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard',
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/DashboardView.vue'),
          meta: { title: 'nav.dashboard', icon: 'dashboard' },
        },

        // ─── Core: Branches ─────────────────────────────────────────────
        {
          path: 'core/branches',
          name: 'branches',
          component: () => import('@/views/core/BranchesView.vue'),
          meta: { title: 'nav.branches', module: 'M00Core', permission: 'core.branch.view' },
        },

        // ─── Core: Users ────────────────────────────────────────────────
        {
          path: 'core/users',
          name: 'users',
          component: () => import('@/views/core/UsersView.vue'),
          meta: { title: 'nav.users', module: 'M00Core', permission: 'core.user.view' },
        },

        // ─── Core: Roles ────────────────────────────────────────────────
        {
          path: 'core/roles',
          name: 'roles',
          component: () => import('@/views/core/RolesView.vue'),
          meta: { title: 'nav.roles', module: 'M00Core', permission: 'core.role.view' },
        },

        // ─── Core: Audit Log ────────────────────────────────────────────
        {
          path: 'core/audit-log',
          name: 'audit-log',
          component: () => import('@/views/core/AuditLogView.vue'),
          meta: { title: 'nav.auditLog', module: 'M00Core', permission: 'core.audit.view' },
        },

        // ─── Core: Settings ─────────────────────────────────────────────
        {
          path: 'core/settings',
          name: 'settings',
          component: () => import('@/views/core/SettingsView.vue'),
          meta: { title: 'nav.settings', module: 'M00Core', permission: 'core.settings.view' },
        },

        // ─── Core: License ──────────────────────────────────────────────
        {
          path: 'core/license',
          name: 'license',
          component: () => import('@/views/core/LicenseView.vue'),
          meta: { title: 'nav.license', module: 'M00Core', permission: 'core.license.view' },
        },

        // ─── Fleet: Vehicles ────────────────────────────────────────────
        {
          path: 'fleet/vehicles',
          name: 'vehicles',
          component: () => import('@/views/fleet/VehiclesView.vue'),
          meta: { title: 'nav.vehicles', module: 'M01Fleet', permission: 'fleet.vehicle.view' },
        },
        {
          path: 'fleet/vehicles/:id',
          name: 'vehicle-detail',
          component: () => import('@/views/fleet/VehicleDetailView.vue'),
          meta: { title: 'nav.vehicleDetail', module: 'M01Fleet', permission: 'fleet.vehicle.view' },
        },
        {
          path: 'fleet/categories',
          name: 'vehicle-categories',
          component: () => import('@/views/fleet/CategoriesView.vue'),
          meta: { title: 'nav.categories', module: 'M01Fleet', permission: 'fleet.category.view' },
        },
        {
          path: 'fleet/expiring-documents',
          name: 'expiring-documents',
          component: () => import('@/views/fleet/ExpiringDocumentsView.vue'),
          meta: { title: 'nav.expiringDocs', module: 'M01Fleet', permission: 'fleet.document.view' },
        },

        // ─── Customers ──────────────────────────────────────────────────
        {
          path: 'customers',
          name: 'customers',
          component: () => import('@/views/customers/CustomersView.vue'),
          meta: { title: 'nav.customers', module: 'M02Customers', permission: 'customers.customer.view' },
        },
        {
          path: 'customers/:id',
          name: 'customer-detail',
          component: () => import('@/views/customers/CustomerDetailView.vue'),
          meta: { title: 'nav.customerDetail', module: 'M02Customers', permission: 'customers.customer.view' },
        },

        // ─── Reservations & Planning ────────────────────────────────────
        {
          path: 'reservations',
          name: 'reservations',
          component: () => import('@/views/reservations/ReservationsView.vue'),
          meta: { title: 'nav.reservations', module: 'M03Reservations', permission: 'reservations.reservation.view' },
        },
        {
          path: 'reservations/planning',
          name: 'reservations-planning',
          component: () => import('@/views/reservations/PlanningView.vue'),
          meta: { title: 'nav.planning', module: 'M03Reservations', permission: 'reservations.planning.view' },
        },

        // ─── Contracts & E-Signature ────────────────────────────────────
        {
          path: 'contracts',
          name: 'contracts',
          component: () => import('@/views/contracts/ContractsView.vue'),
          meta: { title: 'nav.contracts', module: 'M05Contracts', permission: 'contracts.contract.view' },
        },
        {
          path: 'contracts/:id',
          name: 'contract-detail',
          component: () => import('@/views/contracts/ContractDetailView.vue'),
          meta: { title: 'nav.contractDetail', module: 'M05Contracts', permission: 'contracts.contract.view' },
        },

        // ─── Invoicing & Payments ───────────────────────────────────────
        {
          path: 'invoices',
          name: 'invoices',
          component: () => import('@/views/invoicing/InvoicesView.vue'),
          meta: { title: 'nav.invoices', module: 'M07Invoicing', permission: 'invoicing.invoice.view' },
        },
        {
          path: 'invoices/:id',
          name: 'invoice-detail',
          component: () => import('@/views/invoicing/InvoiceDetailView.vue'),
          meta: { title: 'nav.invoiceDetail', module: 'M07Invoicing', permission: 'invoicing.invoice.view' },
        },
        {
          path: 'payments',
          name: 'payments',
          component: () => import('@/views/invoicing/PaymentsView.vue'),
          meta: { title: 'nav.payments', module: 'M07Invoicing', permission: 'invoicing.payment.view' },
        },
      ],
    },

    // ─── 404 ──────────────────────────────────────────────────────────────
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { public: true, layout: 'blank' },
    },
  ],
})

// ─── Navigation Guard ─────────────────────────────────────────────────────────
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Always refresh user permissions on app start or refresh if token exists but user profile is missing
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      return { name: 'login' }
    }
  }

  if (to.meta.public) {
    if (to.name === 'login' && auth.isLoggedIn) {
      return { name: 'dashboard' }
    }
    return
  }

  if (!auth.isLoggedIn) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Super Admin bypasses all checks
  if (auth.hasRole('Super Admin')) {
    return
  }

  // Check required permission
  if (to.meta.permission && typeof to.meta.permission === 'string') {
    if (!auth.hasPermission(to.meta.permission)) {
      console.warn(`[Access Guard] Blocked navigation to ${to.path}. Missing permission: ${to.meta.permission}`)
      return { name: 'dashboard' }
    }
  }

  // Check required role
  if (to.meta.role && typeof to.meta.role === 'string') {
    if (!auth.hasRole(to.meta.role)) {
      console.warn(`[Access Guard] Blocked navigation to ${to.path}. Missing role: ${to.meta.role}`)
      return { name: 'dashboard' }
    }
  }
})

export default router
