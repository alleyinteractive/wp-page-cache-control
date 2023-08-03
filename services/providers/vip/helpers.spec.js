import { deleteCookie, setCookie } from '@/services/cookie';
import {
  COOKIE_SEGMENT,
  GROUP_SEPARATOR,
  VALUE_SEPARATOR,
  VERSION_PREFIX,
  parseSegmentGroups,
  buildSegmentCookie,
} from './helpers';

describe('vip helpers', () => {
  beforeEach(() => deleteCookie(COOKIE_SEGMENT));

  it('should return no groups if empty', () => {
    expect(parseSegmentGroups()).toEqual({});
  });

  it('should return a group if set', () => {
    setCookie(
      COOKIE_SEGMENT,
      `${VERSION_PREFIX}group_name${VALUE_SEPARATOR}yes`,
    );

    expect(parseSegmentGroups()).toEqual({
      group_name: 'yes',
    });
  });

  it('should return multiple groups if set', () => {
    setCookie(
      COOKIE_SEGMENT,
      `${VERSION_PREFIX}group_name${VALUE_SEPARATOR}yes${GROUP_SEPARATOR}another_group${VALUE_SEPARATOR}no`,
    );

    expect(parseSegmentGroups()).toEqual({
      group_name: 'yes',
      another_group: 'no',
    });
  });

  it('should build cookie groups', () => {
    expect(buildSegmentCookie({
      another_group: 'no',
      group_name: 'yes',
    })).toEqual(`${VERSION_PREFIX}another_group${VALUE_SEPARATOR}no${GROUP_SEPARATOR}group_name${VALUE_SEPARATOR}yes`);
  });

  it('should ignore empty segments', () => {
    expect(buildSegmentCookie({
      another_group: 'no',
      group_name: 'yes',
      empty_group: ' ',
    })).toEqual(`${VERSION_PREFIX}another_group${VALUE_SEPARATOR}no${GROUP_SEPARATOR}group_name${VALUE_SEPARATOR}yes`);
  });

  it('should build cookie groups sorted by key', () => {
    expect(buildSegmentCookie({
      group_name: 'yes',
      another_group: 'no',
    })).toEqual(`${VERSION_PREFIX}another_group${VALUE_SEPARATOR}no${GROUP_SEPARATOR}group_name${VALUE_SEPARATOR}yes`);
  });
});
