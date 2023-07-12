/**
 * Read cookie value by name.
 *
 * @param {String} name Cookie name
 * @returns {String|null} Cookie value
 */
export const readCookie = (name: string): string | null => {
  const cookies = document.cookie.split(';').map((cookie) => cookie.trim());

  for (let i = 0; i < cookies.length; i += 1) {
    const [cookieName = '', cookieValue = ''] = cookies[i].split('=');

    if (cookieName === name) {
      return cookieValue;
    }
  }

  return null;
};

/**
 * Read all cookies with a prefix.
 *
 * @param {String} prefix Cookie prefix
 * @returns {Record<string, string>} Cookies
 */
export const readCookiesWithPrefix = (prefix: string): Record<string, string> => {
  const cookies = document.cookie.split(';').map((cookie) => cookie.trim());
  const result: Record<string, string> = {};

  for (let i = 0; i < cookies.length; i += 1) {
    const [cookieName = '', cookieValue = ''] = cookies[i].split('=');

    if (cookieName.startsWith(prefix)) {
      result[cookieName] = cookieValue;
    }
  }

  return result;
};

/**
 * Set a cookie.
 *
 * @param {String} name Cookie name
 * @param {String} value Cookie value
 * @param {Number} days Number of days to expire
 */
export const setCookie = (name: string, value: string, days: number = 30): void => {
  const date = new Date();
  date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
  const expires = `expires=${date.toUTCString()}`;
  document.cookie = `${name}=${value};${expires};path=/`;
};

/**
 * Delete a cookie
 *
 * @param {String} name Cookie name
 */
export const deleteCookie = (name: string): void => {
  document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
};
