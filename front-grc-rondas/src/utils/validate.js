/**
 * Comprueba si una URL es externa.
 * @param {string} path - La URL a comprobar.
 * @returns {boolean} - Devuelve true si la URL es externa, de lo contrario devuelve false.
 */
export function isExternal(path) {
  return /^(https?:|mailto:|tel:)/.test(path)
}

/**
 * Comprueba si una URL es válida.
 * @param {string} url - La URL a comprobar.
 * @returns {boolean} - Devuelve true si la URL es válida, de lo contrario devuelve false.
 */
export function validURL(url) {
  const reg =
    /^(https?|ftp):\/\/([a-zA-Z0-9.-]+(:[a-zA-Z0-9.&%$-]+)*@)*((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(:[0-9]+)*(\/($|[a-zA-Z0-9.,?'\\+&%$#=~_-]+))*$/
  return reg.test(url)
}

/**
 * Comprueba si una cadena de texto contiene solo minúsculas.
 * @param {string} str - La cadena de texto a comprobar.
 * @returns {boolean} - Devuelve true si la cadena de texto contiene solo minúsculas, de lo contrario devuelve false.
 */
export function validLowerCase(str) {
  const reg = /^[a-z]+$/
  return reg.test(str)
}

/**
 * Comprueba si una cadena de texto contiene solo mayúsculas.
 * @param {string} str - La cadena de texto a comprobar.
 * @returns {boolean} - Devuelve true si la cadena de texto contiene solo mayúsculas, de lo contrario devuelve false.
 */
export function validUpperCase(str) {
  const reg = /^[A-Z]+$/
  return reg.test(str)
}

/**
 * Comprueba si una cadena de texto contiene solo letras.
 * @param {string} str - La cadena de texto a comprobar.
 * @returns {boolean} - Devuelve true si la cadena de texto contiene solo letras, de lo contrario devuelve false.
 */
export function validAlphabets(str) {
  const reg = /^[A-Za-z]+$/
  return reg.test(str)
}

/**
 * Comprueba si un valor es una cadena de texto.
 * @param {any} str - El valor a comprobar.
 * @returns {boolean} - Devuelve true si el valor es una cadena de texto, de lo contrario devuelve false.
 */
export function isString(str) {
  return typeof str === 'string' || str instanceof String
}

/**
 * Comprueba si un valor es un array.
 * @param {any} arg - El valor a comprobar.
 * @returns {boolean} - Devuelve true si el valor es un array, de lo contrario devuelve false.
 */
export function isArray(arg) {
  if (typeof Array.isArray === 'undefined') {
    return Object.prototype.toString.call(arg) === '[object Array]'
  }
  return Array.isArray(arg)
}
