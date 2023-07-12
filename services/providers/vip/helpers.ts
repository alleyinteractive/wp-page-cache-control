/**
 * Javascript support for VIP Vary Cache.
 */

import { deleteCookie, readCookie, setCookie } from '@/services/cookie';

const COOKIE_SEGMENT = 'vip-go-seg';
const GROUP_SEPARATOR = '---__';
const VALUE_SEPARATOR = '_--_';
const VERSION_PREFIX = 'vc-v1__';

/**
 * Parse the VIP Vary Cache cookies into groups.
 */
const parseSegmentGroups = () => {
  const cookieValue = readCookie(COOKIE_SEGMENT);
  if (!cookieValue) {
    return {};
  }

  const rawGroups = cookieValue.replace(VERSION_PREFIX, '').split(GROUP_SEPARATOR);
  const groups = {} as Record<string, string>;

  rawGroups.forEach((group: string) => {
    const [groupName, groupValue] = group.split(VALUE_SEPARATOR);

    if (groupName && groupValue) {
      groups[groupName] = groupValue;
    }
  });

  return groups;
};

/**
 * Build the cookie value for VIP Vary Cache
 *
 * A Javascript version of `Vary_Cache::stringify_groups()`.
 */
const buildSegmentCookie = (groups: Record<string, string>) => {
  const values = [] as string[];

  Object.keys(groups).sort().forEach((groupName) => {
    const value = groups[groupName];

    if (!value.trim()) {
      return;
    }

    values.push(`${groupName}${VALUE_SEPARATOR}${value}`);
  });

  if (!values.length) {
    return null;
  }

  return `${VERSION_PREFIX}${values.join(GROUP_SEPARATOR)}`;
};

/**
 * Compile the list of groups/segments and save it to the cookie.
 *
 * @param {Object} groups Key/value pairs of groups and segments.
 */
const saveSegmentCookie = (groups: Record<string, string>) => {
  const cookieValue = buildSegmentCookie(groups);

  if (!cookieValue) {
    deleteCookie(COOKIE_SEGMENT);
  } else {
    setCookie(COOKIE_SEGMENT, cookieValue);
  }
};

export {
  buildSegmentCookie,
  COOKIE_SEGMENT,
  GROUP_SEPARATOR,
  parseSegmentGroups,
  saveSegmentCookie,
  VALUE_SEPARATOR,
  VERSION_PREFIX,
};
