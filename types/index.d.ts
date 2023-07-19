/**
 * WordPress Page Cache Control
 *
 * @link https://github.com/alleyinteractive/wp-page-cache-control
 */
declare namespace wpPageCacheControl {
  /**
   * Cache groups for the current user.
   */
  const groups: Record<string, string>;

  /**
   * Method called when the cache groups should be read/updated.
   */
  function read(): void;

  /**
   * Check if the current user is in a cache group.
   */
  function isUserInGroup(group: string): boolean;

  /**
   * Check if the current user is in a cache group segment.
   */
  function isUserInGroupSegment(group: string, segment: string): boolean;

  /**
   * Set the cache group segment for the current user.
   */
  function setGroupForUser(group: string, segment: string): boolean;

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

  namespace events {
    /**
     * Event fired when the page cache control provider is ready.
     */
    interface ReadyEvent extends CustomEvent {
      detail: {
        provider: WPPageCacheControlProvider;
      };
    }
  }
}
