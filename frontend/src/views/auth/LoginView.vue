<template>
  <div class="login-shell">
    <!-- Left decorative panel -->
    <div class="login-panel" aria-hidden="true">
      <div class="panel-content">
        <div class="panel-logo">
          <div class="panel-logo-icon">
            <AppLogo :size="32" color="#ffffff" />
          </div>
          <span class="panel-brand">RentFleet</span>
        </div>

        <div class="panel-hero">
          <h2 class="panel-title">Gérez votre flotte <br>avec excellence</h2>
          <p class="panel-sub">Plateforme complète de gestion de véhicules<br>pour les agences de location en Algérie</p>
        </div>

        <!-- Decorative circles -->
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
      </div>
    </div>

    <!-- Right login form -->
    <div class="login-form-side">
      <div class="form-container">
        <div class="form-header">
          <h1 class="form-title">{{ t('auth.loginTitle') }}</h1>
          <p class="form-subtitle">{{ t('auth.loginSubtitle') }}</p>
        </div>

        <!-- Error Alert -->
        <Transition name="slide-up">
          <div v-if="errorMsg" class="error-alert" role="alert">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
              <path d="M8 5v3M8 11v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            {{ errorMsg }}
          </div>
        </Transition>

        <form @submit.prevent="handleLogin" novalidate>
          <!-- Email -->
          <div class="field">
            <label class="label" for="login-email">{{ t('auth.email') }}</label>
            <input
              id="login-email"
              v-model="form.email"
              type="email"
              class="input"
              :class="{ error: errors.email }"
              autocomplete="email"
              required
              :placeholder="t('auth.email')"
            />
            <span v-if="errors.email" class="field-error">{{ errors.email }}</span>
          </div>

          <!-- Password -->
          <div class="field">
            <div class="field-header">
              <label class="label" for="login-password">{{ t('auth.password') }}</label>
              <RouterLink to="/forgot-password" class="forgot-link">
                {{ t('auth.forgotPassword') }}
              </RouterLink>
            </div>
            <div class="input-wrapper">
              <input
                id="login-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="input"
                :class="{ error: errors.password }"
                autocomplete="current-password"
                required
                :placeholder="t('auth.password')"
              />
              <button
                type="button"
                class="eye-btn"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
              >
                <svg v-if="showPassword" width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <path d="M1 9s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                  <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <path d="M2 2l14 14M7.5 4.5A6.8 6.8 0 019 4c5 0 8 5 8 5a14.3 14.3 0 01-2.5 3M5 5.5A14.3 14.3 0 001 9s3 5 8 5a6.8 6.8 0 004.5-1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </button>
            </div>
            <span v-if="errors.password" class="field-error">{{ errors.password }}</span>
          </div>

          <!-- 2FA Code (shown after initial login if required) -->
          <Transition name="slide-up">
            <div v-if="needs2FA" class="field">
              <label class="label" for="login-2fa">{{ t('auth.twoFactor') }}</label>
              <input
                id="login-2fa"
                v-model="form.twoFactorCode"
                type="text"
                class="input"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                placeholder="000000"
                autocomplete="one-time-code"
              />
            </div>
          </Transition>

          <button
            type="submit"
            class="btn btn-primary btn-lg submit-btn"
            :disabled="auth.loading"
            id="login-submit"
          >
            <span v-if="auth.loading" class="spinner-sm"></span>
            {{ auth.loading ? t('common.loading') : t('auth.login') }}
          </button>
        </form>

        <!-- Quick Demo Accounts -->
        <div class="demo-accounts-box">
          <div class="demo-title">Comptes de démonstration (1-clic) :</div>
          <div class="demo-chips">
            <button
              type="button"
              class="demo-chip chip-admin"
              @click="fillDemo('admin@rentfleet.dz', 'password')"
              title="Super Administrateur"
            >
              👑 Super Admin
            </button>
            <button
              type="button"
              class="demo-chip chip-manager"
              @click="fillDemo('manager.alger@rentfleet.dz', 'password')"
              title="Responsable Agence Alger"
            >
              🏢 Manager Alger
            </button>
            <button
              type="button"
              class="demo-chip chip-manager"
              @click="fillDemo('manager.oran@rentfleet.dz', 'password')"
              title="Responsable Agence Oran"
            >
              🏢 Manager Oran
            </button>
            <button
              type="button"
              class="demo-chip chip-agent"
              @click="fillDemo('agent.alger@rentfleet.dz', 'password')"
              title="Agent de Comptoir Alger"
            >
              👤 Agent Alger
            </button>
          </div>
        </div>

        <!-- Language switcher -->
        <div class="bottom-lang">
          <button
            v-for="lang in [{ code: 'fr', label: 'Français' }, { code: 'en', label: 'English' }]"
            :key="lang.code"
            class="lang-opt"
            :class="{ active: locale === lang.code }"
            @click="switchLocale(lang.code)"
          >
            {{ lang.label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import AppLogo from '@/components/AppLogo.vue'

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '', twoFactorCode: '' })
const errors = reactive({ email: '', password: '' })
const errorMsg = ref('')
const showPassword = ref(false)
const needs2FA = ref(false)

function validate(): boolean {
  errors.email = ''
  errors.password = ''
  let valid = true
  if (!form.email) { errors.email = 'L\'e-mail est requis'; valid = false }
  if (!form.password) { errors.password = 'Le mot de passe est requis'; valid = false }
  return valid
}

async function handleLogin() {
  if (!validate()) return
  errorMsg.value = ''
  try {
    await auth.login(form.email, form.password)
    const redirect = route.query.redirect as string
    router.push(redirect || '/dashboard')
  } catch (e: any) {
    const code = e.response?.data?.error?.code
    if (code === 'two_factor_required') {
      needs2FA.value = true
      errorMsg.value = 'Veuillez saisir votre code d\'authentification.'
    } else {
      errorMsg.value = e.response?.data?.error?.message ?? t('auth.loginError')
    }
  }
}

function switchLocale(lang: string) {
  locale.value = lang
  localStorage.setItem('locale', lang)
}

function fillDemo(email: string, pass: string) {
  form.email = email
  form.password = pass
  errorMsg.value = ''
}
</script>

<style scoped>
.login-shell {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-block-size: 100dvh;
}

/* ─── Left Panel ─────────────────────────────────────────────────────────── */
.login-panel {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #2D14A0;
}

.login-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('/login-bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  z-index: 0;
}

.login-panel::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    145deg,
    rgba(90, 60, 223, 0.82) 0%,
    rgba(61, 31, 175, 0.86) 55%,
    rgba(40, 18, 140, 0.92) 100%
  );
  z-index: 0;
}

.panel-content {
  padding: 3rem;
  position: relative;
  z-index: 1;
  max-inline-size: 440px;
}

.panel-logo {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  margin-block-end: 3.5rem;
}

.panel-logo-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(8px);
}

.panel-brand {
  font-size: 1.75rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.03em;
}

.panel-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.15;
  margin-block-end: 1.25rem;
  letter-spacing: -0.03em;
}

.panel-sub {
  font-size: 1rem;
  color: rgba(255,255,255,0.7);
  line-height: 1.6;
}

/* Decorative circles */
.deco-circle {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

.deco-1 {
  inset-block-start: -100px;
  inset-inline-end: -100px;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
}

.deco-2 {
  inset-block-end: -60px;
  inset-inline-start: -60px;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255,159,10,0.12) 0%, transparent 70%);
}

/* ─── Right Form Side ─────────────────────────────────────────────────────── */
.login-form-side {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: var(--color-surface);
}

.form-container {
  width: 100%;
  max-inline-size: 420px;
}

.form-header { margin-block-end: 2rem; }

.form-title {
  font-size: 1.875rem;
  font-weight: 800;
  color: var(--color-text-primary);
  margin-block-end: 0.5rem;
  letter-spacing: -0.02em;
}

.form-subtitle {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  background: var(--color-error-light);
  color: var(--color-error);
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-block-end: 1.25rem;
  border: 1px solid rgba(220,38,38,0.2);
}

.field {
  margin-block-end: 1.25rem;
}

.field-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-block-end: 0.375rem;
}

.label { margin-block-end: 0; }

.forgot-link {
  font-size: 0.8125rem;
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
}
.forgot-link:hover { text-decoration: underline; }

.input-wrapper { position: relative; }

.input-wrapper .input {
  padding-inline-end: 2.75rem;
}

.eye-btn {
  position: absolute;
  inset-block-start: 50%;
  inset-inline-end: 0.75rem;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-muted);
  display: flex;
  padding: 4px;
  border-radius: 4px;
  transition: color 0.15s;
}
.eye-btn:hover { color: var(--color-text-secondary); }

.field-error {
  display: block;
  font-size: 0.8125rem;
  color: var(--color-error);
  margin-block-start: 0.375rem;
}

.submit-btn {
  width: 100%;
  margin-block-start: 0.5rem;
  gap: 0.625rem;
}

.spinner-sm {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  flex-shrink: 0;
}

@keyframes spin { to { transform: rotate(360deg); } }

.bottom-lang {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-block-start: 2rem;
  padding-block-start: 1.5rem;
  border-block-start: 1px solid var(--color-border);
}

.lang-opt {
  background: none;
  border: none;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-muted);
  cursor: pointer;
  font-family: var(--font-sans);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  transition: all 0.15s;
}

.lang-opt.active,
.lang-opt:hover {
  color: var(--color-primary);
  background: var(--color-primary-50);
}

/* ─── Demo Accounts ──────────────────────────────────────────────────────── */
.demo-accounts-box {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
}

.demo-title {
  font-size: 0.775rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.demo-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.demo-chip {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.demo-chip:hover {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #1d4ed8;
  transform: translateY(-1px);
}

.chip-admin:hover {
  border-color: #8b5cf6;
  background: #f5f3ff;
  color: #6d28d9;
}

.chip-manager:hover {
  border-color: #0ea5e9;
  background: #f0f9ff;
  color: #0284c7;
}

.chip-agent:hover {
  border-color: #10b981;
  background: #ecfdf5;
  color: #047857;
}

/* ─── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .login-shell { grid-template-columns: 1fr; }
  .login-panel { display: none; }
}
</style>
