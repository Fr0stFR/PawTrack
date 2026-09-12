import Icon from '@/components/ui/Icon'
import styles from './MedicalEventList.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

// Comparaison au JOUR : `doneAt` porte l'heure du clic, qui diffère toujours de
// l'heure prévue. Comparer les horodatages afficherait la mention en permanence.
function isSameDay(a, b) {
  return new Date(a).toDateString() === new Date(b).toDateString()
}

/**
 * Liste d'événements médicaux, utilisée aussi bien sur le tableau de bord que
 * sur la fiche d'un animal.
 *
 * Le composant ne connaît ni l'API ni la notion de « fait » : il signale
 * seulement qu'une ligne a été choisie, et laisse le parent décider de la suite.
 *
 * `onValidate` ajoute un raccourci sur les lignes ouvertes, distinct de
 * `onSelect` : la ligne ouvre l'édition, le ✓ enregistre une visite.
 *
 * @param {{
 *   events: Array<object>,
 *   showAnimal?: boolean,
 *   onSelect?: (event: object) => void,
 *   onValidate?: (event: object) => void
 * }} props showAnimal masque le nom de l'animal lorsqu'on est déjà sur sa fiche.
 */
function MedicalEventList({ events, showAnimal = true, onSelect, onValidate }) {
  return (
    <ul className={styles.list}>
      {events.map((event) => {
        // Le statut est dérivé de la donnée plutôt que reçu en prop : le
        // composant reste cohérent quel que soit l'écran qui l'utilise.
        const overdue = !event.isDone && new Date(event.date) < new Date()

        const itemClass = [
          styles.item,
          overdue && styles.overdue,
          event.isDone && styles.done,
        ]
          .filter(Boolean)
          .join(' ')

        // Signalé seulement si la réalisation a glissé par rapport au prévu :
        // sur un événement fait le jour dit, la mention n'apprendrait rien.
        const doneOffSchedule =
          event.isDone && event.doneAt && !isSameDay(event.date, event.doneAt)

        // Deux colonnes de deux lignes plutôt que trois lignes empilées : la
        // mention « Fait le » se loge en face du type au lieu d'ajouter une
        // ligne à la carte.
        const content = (
          <>
            <div className={styles.main}>
              <span className={styles.name}>{event.name}</span>
              <div className={styles.sub}>
                <span className={styles.type}>{event.medicalType.name}</span>
                {showAnimal && <span className={styles.meta}>{event.animal.name}</span>}
                {overdue && <span className={styles.badge}>En retard</span>}
              </div>
            </div>

            <div className={styles.dates}>
              <span className={styles.date}>{formatDate(event.date)}</span>
              {doneOffSchedule && (
                <span className={styles.doneAt}>Fait le {formatDate(event.doneAt)}</span>
              )}
            </div>
          </>
        )

        return (
          <li key={event.id} className={itemClass}>
            {/* Côte à côte et non imbriquées : un <button> dans un <button>
                est du HTML invalide. */}
            <div className={styles.row}>
              {onSelect ? (
                <button
                  type="button"
                  className={`${styles.body} ${styles.trigger}`}
                  onClick={() => onSelect(event)}
                >
                  {content}
                </button>
              ) : (
                <div className={styles.body}>{content}</div>
              )}

              {onValidate && !event.isDone && (
                <button
                  type="button"
                  className={styles.validate}
                  onClick={() => onValidate(event)}
                  title="Marquer comme fait"
                  aria-label={`Marquer « ${event.name} » comme fait`}
                >
                  <Icon name="visit" />
                </button>
              )}
            </div>
          </li>
        )
      })}
    </ul>
  )
}

export default MedicalEventList
