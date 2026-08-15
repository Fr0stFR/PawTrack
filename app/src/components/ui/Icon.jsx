import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { config } from '@fortawesome/fontawesome-svg-core'
import '@fortawesome/fontawesome-svg-core/styles.css'
import {
  faPaw,
  faHourglassHalf,
  faClockRotateLeft,
  faArrowsRotate,
  faPlus,
  faXmark,
  faTrash,
  faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

// FontAwesome injecte sa feuille de style au runtime par défaut, ce qui provoque
// un affichage des icônes en taille brute avant redimensionnement. Le CSS est
// donc importé ci-dessus pour être inclus au bundle et présent au premier rendu.
config.autoAddCss = false

// Les icônes sont nommées par leur rôle et non par leur dessin : changer de
// pictogramme se limite à une ligne ici. L'import unitaire (et non le pack
// entier) permet le tree-shaking.
const ICONS = {
  paw: faPaw,
  todo: faHourglassHalf,
  history: faClockRotateLeft,
  automation: faArrowsRotate,
  plus: faPlus,
  close: faXmark,
  delete: faTrash,
  warning: faTriangleExclamation,
}

/**
 * Point d'entrée unique des icônes de l'application.
 *
 * @param {{name: keyof typeof ICONS}} props Les props restantes sont transmises
 *                                           à FontAwesomeIcon.
 */
function Icon({ name, ...props }) {
  const icon = ICONS[name]

  if (!icon) {
    console.warn(`<Icon name="${name}" /> : nom inconnu. Clés valides :`, Object.keys(ICONS))
    return null
  }

  return <FontAwesomeIcon icon={icon} {...props} />
}

export default Icon
