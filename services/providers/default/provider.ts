/* eslint-disable class-methods-use-this */

/**
 * A default provider to prevent errors when unable to detect the provider for
 * the current environment.
 */
class DefaultCacheProvider implements wpPageCacheControl.WPPageCacheControlProvider {
  groups: Record<string, string> = {};

  isUserInGroup() {
    return false;
  }

  isUserInGroupSegment() {
    return false;
  }

  setGroupForUser() {
    return false;
  }

  read(): void {}
}

export default DefaultCacheProvider;
