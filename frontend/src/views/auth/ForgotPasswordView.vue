<template>
  <div class="page-stub">
    <div class="stub-icon">🔒</div>
    <h2>Réinitialisation du mot de passe</h2>
    <p>Entrez votre e-mail pour recevoir un lien de réinitialisation.</p>
    <RouterLink to="/login" class="btn btn-ghost" style="margin-block-start:1.5rem;display:inline-flex">
      ← Retour à la connexion
    </RouterLink>
  </div>
</template>
<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>
<style scoped>
.page-stub { text-align: center; padding: 4rem 2rem; }
.stub-icon { font-size: 3rem; margin-block-end: 1rem; }
h2 { font-size: 1.5rem; color: var(--color-text-primary); margin-block-end: 0.5rem; }
p { color: var(--color-text-secondary); }
</style>
