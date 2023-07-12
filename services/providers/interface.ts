interface WPPageCacheControlProvider {
  /**
   * Cache groups for the current user.
   */
  groups: Record<string, string>;
  /**
   * Method called when the cache groups should be read/updated.
   */
  read: () => void;
  /**
   * Check if the current user is in a cache group.
   */
  isUserInGroup: (group: string) => boolean;
  /**
   * Check if the current user is in a cache group segment.
   */
  isUserInGroupSegment: (group: string, segment: string) => boolean;
  /**
   * Set the cache group segment for the current user.
   */
  setGroupForUser: (group: string, segment: string) => boolean;
}

export default WPPageCacheControlProvider;
