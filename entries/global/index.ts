/**
 * WP Page Cache Control
 *
 * @todo Only load the current provider instead of all of them.
 */

import {
  DefaultCacheProvider,
  PantheonCacheProvider,
  WordPressVipCacheProvider,
} from '@/services/providers';

declare global {
  interface Window {
    wpPageCacheControlSettings: {
      provider: 'VIPProvider' | 'PantheonProvider' | 'DefaultCacheProvider';
      registeredGroups: string[];
    };
    wpPageCacheControl: wpPageCacheControl.WPPageCacheControlProvider;
  }
}

const {
  wpPageCacheControlSettings: {
    provider = 'DefaultCacheProvider',
  },
} = window;

const providers = {
  VIPProvider: WordPressVipCacheProvider,
  PantheonProvider: PantheonCacheProvider,
};

if (typeof providers[provider as keyof typeof providers] === 'undefined' || provider === 'DefaultCacheProvider') {
  console.error(`WP Page Cache Control: Unknown provider: ${provider}`); // eslint-disable-line no-console
  window.wpPageCacheControl = new DefaultCacheProvider();
} else {
  window.wpPageCacheControl = new providers[provider as keyof typeof providers]();

  console.log(`WP Page Cache Control: Using provider: ${provider}`); // eslint-disable-line no-console
}

// Fire a custom event to let other scripts know that the provider is ready.
window.dispatchEvent(new CustomEvent('wp-page-cache-control:ready', {
  detail: {
    provider: window.wpPageCacheControl,
  },
}) as wpPageCacheControl.events.ReadyEvent);

// Fire a custom event to let other scripts know that the provider should be
// updated from the cookies.
window.addEventListener('wp-page-cache-control:read', () => {
  window.wpPageCacheControl.read();
});
