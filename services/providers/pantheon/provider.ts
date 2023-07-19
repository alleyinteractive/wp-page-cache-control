import { readCookiesWithPrefix, setCookie } from '@/services/cookie';

const COOKIE_PREFIX = 'STYXKEY-';

/**
 * @link https://docs.pantheon.io/cookies#cache-varying-cookies
 */
class PantheonCacheProvider implements wpPageCacheControl.WPPageCacheControlProvider {
  groups: Record<string, string> = {};

  constructor() {
    this.read();
  }

  isUserInGroup(group: string) {
    return !!this.groups[group];
  }

  isUserInGroupSegment(group: string, segment: string) {
    return this.isUserInGroup(group) && this.groups[group] === segment;
  }

  setGroupForUser(group: string, segment: string) {
    // Check if the group is registered.
    const {
      wpPageCacheControlSettings: { registeredGroups = [] },
    } = window;

    if (!registeredGroups.includes(group)) {
      console.error(`WP Page Cache Control: The group "${group}" is not registered.`); // eslint-disable-line no-console

      return false;
    }

    this.groups[group] = segment;

    // Sort the groups alphabetically by key.
    const sortedGroups = Object.keys(this.groups).sort().reduce((acc, key) => {
      acc[key] = this.groups[key];

      return acc;
    }, {} as Record<string, string>);

    // Set the cookies for the groups.
    Object.keys(sortedGroups).forEach((key) => {
      setCookie(`${COOKIE_PREFIX}${key}`, sortedGroups[key]);
    });

    this.groups = sortedGroups;

    return true;
  }

  read(): void {
    const cookies = readCookiesWithPrefix(COOKIE_PREFIX);

    if (!Object.keys(cookies).length) {
      this.groups = {};
      return;
    }

    // Set the groups from the values of the cookies.
    Object.keys(cookies).forEach((key) => {
      const group = key.replace(COOKIE_PREFIX, '');
      this.groups[group] = cookies[key];
    });
  }
}

export default PantheonCacheProvider;
