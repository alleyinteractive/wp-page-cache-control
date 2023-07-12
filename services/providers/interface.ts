interface WPPageCacheControlProvider {
  groups: Record<string, string>;
  isUserInGroup: (group: string) => boolean;
  isUserInGroupSegment: (group: string, segment: string) => boolean;
  setGroupForUser: (group: string, segment: string) => void;
}

export default WPPageCacheControlProvider;
