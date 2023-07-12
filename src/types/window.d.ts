import { WPPageCacheControlProvider } from '@/services/providers';

/**
 * Global Type Roots
 *
 * @link https://www.typescriptlang.org/tsconfig/#typeRoots
 */
declare global {
  interface Window {
    wpPageCacheControlSettings: {
      provider: 'VIPProvider' | 'PantheonProvider' | 'TestableProvider';
    };
    wpPageCacheControl: WPPageCacheControlProvider;
  }
}
