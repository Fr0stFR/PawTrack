import styles from './MedicalEventList.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

/**
 * Liste d'événements médicaux, utilisée aussi bien sur le tableau de bord que
 * sur la fiche d'un animal.
 *
 * @param {{events: Array<object>, showAnimal?: boolean}} props showAnimal masque
 *        le nom de l'animal lorsqu'on est déjà sur sa fiche.
 */
function MedicalEventList({ events, showAnimal = true }) {
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

        return (
          <li key={event.id} className={itemClass}>
            <div className={styles.row}>
              <span className={styles.name}>{event.name}</span>
              <span className={styles.date}>{formatDate(event.date)}</span>
            </div>

            <div className={styles.row}>
              <span className={styles.type}>{event.medicalType.name}</span>
              {showAnimal && <span className={styles.meta}>{event.animal.name}</span>}
              {overdue && <span className={styles.badge}>En retard</span>}
            </div>
          </li>
        )
      })}
    </ul>
  )
}

export default MedicalEventList
