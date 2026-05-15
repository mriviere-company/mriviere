<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, RouterView } from 'vue-router';
import SiteHeader from '@/components/organisms/SiteHeader.vue';
import SiteFooter from '@/components/organisms/SiteFooter.vue';
import { supportsViewTransitions } from '@/router';

const route = useRoute();
const isAdmin = computed(() => route.path.startsWith('/admin'));

// When the View Transitions API is available, the browser handles the
// animation natively (see assets/styles/transitions.scss). Otherwise we
// fall back to Vue's <Transition> wrapper.
const useFallbackTransition = !supportsViewTransitions;

// Toggle full-viewport scroll snap on the homepage only. The class lives on
// <html> so it can scope `scroll-snap-type` from the document scroll context.
function applySnapClass() {
  const isHome = route.name === 'home' || route.name === 'home-fr' || route.name === 'home-en';
  document.documentElement.classList.toggle('home-snap', isHome);
}

onMounted(applySnapClass);
watch(() => route.name, applySnapClass);
onUnmounted(() => document.documentElement.classList.remove('home-snap'));
</script>

<template>
  <SiteHeader v-if="!isAdmin" />
  <main :class="{ 'main--admin': isAdmin }">
    <RouterView v-slot="{ Component, route: r }">
      <Transition v-if="useFallbackTransition" name="page" mode="out-in">
        <component :is="Component" :key="r.fullPath" />
      </Transition>
      <component v-else :is="Component" :key="r.fullPath" />
    </RouterView>
  </main>
  <SiteFooter v-if="!isAdmin" />
</template>

<style lang="scss">
.main--admin {
  min-height: 100vh;
}
</style>
