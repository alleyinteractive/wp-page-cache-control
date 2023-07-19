/* eslint-disable import/extensions */

export * from './provider';

/**
 * Global Type Roots
 *
 * @link https://www.typescriptlang.org/tsconfig/#typeRoots
 */
declare global {
  interface Window {
    wpPageCacheControlSettings: {
      provider: 'VIPProvider' | 'PantheonProvider' | 'TestableProvider';
      registeredGroups: string[];
    };
    wpPageCacheControl: wpPageCacheControl.WPPageCacheControlProvider;
  }
}
