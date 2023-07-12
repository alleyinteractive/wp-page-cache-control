import WPPageCacheControlProvider from '../interface';
import { parseSegmentGroups, saveSegmentCookie } from './helpers';

/**
 * WordPress VIP Cache Segmentation Provider
 */
class WordPressVipCacheProvider implements WPPageCacheControlProvider {
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

    saveSegmentCookie(this.groups);

    return true;
  }

  read() {
    this.groups = parseSegmentGroups();
  }
}

export default WordPressVipCacheProvider;
