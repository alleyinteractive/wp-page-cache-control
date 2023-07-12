import WPPageCacheControlProvider from '../interface';
import { parseSegmentGroups, saveSegmentCookie } from './helpers';

/**
 * WordPress VIP Cache Segmentation Provider
 */
class WordPressVipCacheProvider implements WPPageCacheControlProvider {
  groups: Record<string, string>;

  constructor() {
    this.groups = parseSegmentGroups();
  }

  isUserInGroup(group: string) {
    return !!this.groups[group];
  }

  isUserInGroupSegment(group: string, segment: string) {
    return this.isUserInGroup(group) && this.groups[group] === segment;
  }

  setGroupForUser(group: string, segment: string) {
    this.groups[group] = segment;
    saveSegmentCookie(this.groups);
  }
}

export default WordPressVipCacheProvider;
