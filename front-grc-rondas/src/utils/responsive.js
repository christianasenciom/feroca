export const calcularAnchoDialog = (anchopc, anchomobile) => {
  const ancho = window.innerWidth
  if (ancho <= 600) {
    return anchomobile
  }
  return anchopc
}

export const alturaPorDispositivo = (alturapc, alturamobile) => {
  const ancho = window.innerWidth
  if (ancho <= 600) {
    return alturamobile
  }
  return alturapc
}
