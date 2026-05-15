import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import UiButton from '../UiButton.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div />' } }],
});

describe('UiButton', () => {
  it('renders the slot label inside a <button> by default', () => {
    const wrapper = mount(UiButton, { slots: { default: 'Cliquer' } });
    expect(wrapper.element.tagName).toBe('BUTTON');
    expect(wrapper.text()).toContain('Cliquer');
  });

  it('emits click when pressed', async () => {
    const wrapper = mount(UiButton, { slots: { default: 'Go' } });
    await wrapper.trigger('click');
    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('renders a RouterLink when "to" is provided', () => {
    const wrapper = mount(UiButton, {
      props: { to: '/contact' },
      slots: { default: 'Lien' },
      global: { plugins: [router] },
    });
    expect(wrapper.find('a').exists()).toBe(true);
    expect(wrapper.attributes('href')).toBe('/contact');
  });

  it('shows a loading state and disables the button', () => {
    const wrapper = mount(UiButton, { props: { loading: true }, slots: { default: 'Loading' } });
    expect(wrapper.classes()).toContain('ui-button--loading');
    expect(wrapper.attributes('disabled')).toBe('');
  });

  it('applies the variant class', () => {
    const wrapper = mount(UiButton, { props: { variant: 'glow' }, slots: { default: 'X' } });
    expect(wrapper.classes()).toContain('ui-button--glow');
  });
});
