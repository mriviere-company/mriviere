import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { MotionPlugin } from '@vueuse/motion';
import App from '@/App.vue';
import { router } from '@/router';
import { i18n } from '@/i18n';
import '@fontsource-variable/inter';
import '@fontsource-variable/bricolage-grotesque';
import '@/styles/index.scss';

document.documentElement.lang = i18n.global.locale.value;

// Swap the favicon when `data-theme` changes on <html>. Chrome ignores the
// `media="(prefers-color-scheme)"` attribute on ICO favicons reliably, so we
// drive the swap from the same source of truth as the site's manual toggle.
function syncFavicon() {
  const link = document.getElementById('favicon') as HTMLLinkElement | null;
  if (!link) return;
  const isDark = document.documentElement.dataset.theme === 'dark';
  link.href = isDark ? '/favicon-dark.ico' : '/favicon.ico';
}
syncFavicon();
new MutationObserver(syncFavicon).observe(document.documentElement, {
  attributes: true,
  attributeFilter: ['data-theme'],
});

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(i18n);
app.use(MotionPlugin);

app.mount('#app');
