# Ajouter une page publique au site

> Ce guide est pour les ajouts à la **vitrine** publique (`/forfaits`, `/profil`, `/contact`, etc.). L'admin (`/admin/*`) suit un workflow différent et reste en français uniquement.

Avant de toucher au code, vérifie que la page est vraiment nécessaire — l'architecture privilégie peu de pages denses plutôt que beaucoup de pages courtes. Si l'info peut tenir dans une section d'une page existante, c'est probablement la bonne option.

## Checklist (8 étapes)

### 1. Choisir l'URL canonique en français

Toutes les URLs publiques sont **plates** et en français. Pas de préfixe `/fr/...` ni `/en/...` (sauf la home, voir `assets/router/index.ts`).

Convention : un mot, kebab-case si nécessaire. Ex. `/forfaits`, `/mentions-legales`, `/cgv`.

### 2. Créer la vue Vue

Fichier : `assets/views/MaPageView.vue`. Squelette type :

```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import UiButton from '@/components/atoms/UiButton.vue';
// import des organisms / molecules si besoin

const { t } = useI18n();
</script>

<template>
  <div class="ma-page container">
    <section class="ma-page__hero section">
      <span class="eyebrow">{{ t('maPage.eyebrow') }}</span>
      <h1 class="heading-display">{{ t('maPage.title1') }}<br /><span class="text-accent">{{ t('maPage.title2') }}</span></h1>
      <p class="text-soft">{{ t('maPage.lead') }}</p>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.ma-page {
  // Mobile first. Aucun hex en dur — `var(--color-*)` uniquement.
  // Aucune font-family en dur — `var(--font-display|body|mono)`.
}
</style>
```

**Règles non-négociables :**
- `<script setup lang="ts">` (Composition API + TypeScript strict, pas de `any`).
- Toute chaîne UI passe par `t('clé.imbriquée')` — jamais de FR ni EN en dur.
- Les composants partagés viennent du **design system** : atoms (`UiButton`, `UiField`, `UiTag`) ou molecules (`UiCard`, `UiSparkline`). Si tu as besoin d'un nouveau bouton/champ/carte ad hoc, soit tu enrichis l'atom existant, soit tu en crées un nouveau dans le bon niveau (`atoms/`, `molecules/`, `organisms/`).
- SCSS mobile first, jamais `max-width` comme breakpoint principal.

### 3. Enregistrer la route Vue Router

Dans `assets/router/index.ts`, ajouter dans le tableau `publicRoutes` :

```ts
{ path: '/ma-page', name: 'ma-page', component: () => import('@/views/MaPageView.vue'), meta: { titleKey: 'nav.maPage' } },
```

Le `import('@/views/...')` est lazy → la page sera dans son propre chunk Vite. Le `meta.titleKey` alimente le `<title>` dynamique côté client.

Si la page doit être référencée par un helper (ex. `localePath('ma-page')`), ajouter aussi son slug à l'objet `PATHS` plus haut dans le même fichier.

### 4. Ajouter les clés i18n dans `fr.json` ET `en.json`

Dans `assets/locales/fr.json` :

```json
"maPage": {
  "eyebrow": "Section",
  "title1": "Premier morceau de titre",
  "title2": "second morceau accentué.",
  "lead": "Phrase d'accroche."
}
```

Dans `assets/locales/en.json` : **les deux fichiers doivent rester en parité**. Toute clé dans l'un doit exister dans l'autre, même si le contenu diffère.

Le rate limiter de l'audit (script dans STATUS.md §11) compte les clés orphelines — laisser des clés inutilisées est OK temporairement, mais à nettoyer avant un commit propre.

### 5. Lien dans le header / footer si nécessaire

Si la page est navigable depuis le menu :

- `assets/components/organisms/SiteHeader.vue` : ajouter une `<RouterLink :to="localePath('ma-page')">{{ t('nav.maPage') }}</RouterLink>` dans la `<nav>`.
- `assets/components/organisms/SiteFooter.vue` : idem dans la liste appropriée.

Sinon, la page reste accessible uniquement via lien direct ou CTA explicite.

### 6. Meta Open Graph dynamique

`SpaController::resolveOgMeta(string $path)` (dans `src/Controller/SpaController.php`) mappe chaque route à un titre/sous-titre/eyebrow pour l'OG image dynamique (`/og.png`). Ajouter un cas :

```php
$path === '/ma-page' => [
    'Titre court mais fort',
    'Sous-titre en une phrase qui contextualise.',
    'MA PAGE',
],
```

C'est ce que les crawlers FB/Twitter/LinkedIn voient.

### 7. Sitemap

`SeoController` (`src/Controller/SeoController.php`) génère le sitemap dynamique. Si la page doit être indexée, ajouter son URL dans la liste interne du controller. Si c'est une page interne au flux (confirmation après paiement, par exemple), la **sortir** de l'indexation via `robots.txt` ou simplement ne pas l'ajouter au sitemap.

### 8. Tests

- **Vitest** (optionnel) : si la page a une logique non triviale (form, calcul), ajouter un test sous `assets/views/__tests__/`.
- **Playwright E2E** (recommandé) : ajouter une assertion minimale dans `tests/e2e/` que le `h1` rend bien dans les deux locales (cf. `pricing.spec.ts` pour le pattern).
- **PHPUnit** : nécessaire uniquement si tu ajoutes un endpoint backend pour cette page.

## Vérification avant commit

```bash
npm run type-check        # vue-tsc
npm test                  # Vitest
npm run build             # Vite — vérifie qu'aucune clé i18n n'explose à l'exécution
npx playwright test       # E2E
php bin/phpunit
```

## Anti-checklist (ce qu'il **ne faut pas** faire)

- ❌ Préfixer l'URL par `/fr/...` ou `/en/...` (cassé le SEO bilingue, le routing FR canonique est verrouillé).
- ❌ Mettre du texte FR ou EN en dur dans le `<template>`.
- ❌ Importer un hex (`#0F2A5F`) ou une font-family (`'Inter'`) directement — passer par `var(--color-*)` et `var(--font-*)`.
- ❌ Créer un bouton/champ/carte custom — étendre les `Ui*` existants.
- ❌ Mettre de la logique métier dans le `<template>` ou Twig — la logique vit dans des composables Vue ou des services Symfony.
- ❌ Ajouter un nouveau `.twig` hors `templates/emails/`. Le shell SPA est rendu en PHP par `SpaController`.
