export const title = 'RONDAS CAMPESINAS - GOBIERNO REGIONAL CAJAMARCA'

export default function getPageTitle(pageTitle) {
  if (pageTitle) {
    return `${pageTitle} - ${title}`
  }
  return `${title}`
}
