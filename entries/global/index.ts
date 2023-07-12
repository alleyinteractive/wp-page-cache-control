/**
 * WP Page Cache Control
 *
 * @todo Only load the current provider instead of all of them.
 */

import { WordPressVipCacheProvider } from '@/services/providers';

const {
  wpPageCacheControlSettings: {
    provider = 'TestableProvider',
  },
} = window;

const providers = {
  VIPProvider: WordPressVipCacheProvider,
  // PantheonProvider: PantheonCacheProvider,
};

if (typeof providers[provider as keyof typeof providers] === 'undefined') {
  console.error(`WP Page Cache Control: Unknown provider: ${provider}`); // eslint-disable-line no-console
} else {
  window.wpPageCacheControl = new providers[provider as keyof typeof providers]();

  console.log(`WP Page Cache Control: Using provider: ${provider}`); // eslint-disable-line no-console

  // Fire a custom event to let other scripts know that the provider is ready.
  window.dispatchEvent(new CustomEvent('wp-page-cache-control:ready', {
    detail: {
      provider: window.wpPageCacheControl,
    },
  }));
}
