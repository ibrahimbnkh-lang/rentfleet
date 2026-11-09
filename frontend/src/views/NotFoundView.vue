<template>
  <div class="not-found-page">
    <div class="not-found-code">404</div>
    <h1 class="not-found-title">Page introuvable</h1>
    <p class="not-found-sub">La page que vous cherchez n'existe pas ou a été déplacée.</p>
    <RouterLink to="/" class="btn btn-primary" style="margin-block-start:2rem;display:inline-flex">
      Retour à l'accueil
    </RouterLink>
  </div>
</template>
<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>
<style scoped>
.not-found-page {
  min-block-size: 100dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem;
  background: var(--color-surface-2);
}
.not-found-code {
  font-size: 8rem;
  font-weight: 900;
  color: var(--color-primary);
  line-height: 1;
  opacity: 0.15;
  letter-spacing: -0.05em;
}
.not-found-title {
  font-size: 2rem;
  margin-block-end: 0.75rem;
  margin-block-start: -2rem;
}
.not-found-sub { color: var(--color-text-secondary); font-size: 1rem; }
</style>
