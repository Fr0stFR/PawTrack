import { Link } from 'react-router-dom'
import Icon from './Icon'
import styles from './AnimalList.module.css'

/**
 * Liste des animaux, chaque entrée menant à la fiche détaillée.
 *
 * @param {{animals: Array<object>}} props
 */
function AnimalList({ animals }) {
  return (
    <ul className={styles.list}>
      {animals.map((animal) => (
        <li key={animal.id}>
          <Link to={`/animals/${animal.id}`} className={styles.card}>
            <span className={styles.avatar}><Icon name="paw" /></span>
            <span className={styles.name}>{animal.name}</span>
            {/* `breed` est nullable : filter(Boolean) évite un séparateur orphelin. */}
            <span className={styles.meta}>
              {[animal.animalType?.name, animal.breed?.name].filter(Boolean).join(' · ')}
            </span>
          </Link>
        </li>
      ))}
    </ul>
  )
}

export default AnimalList
