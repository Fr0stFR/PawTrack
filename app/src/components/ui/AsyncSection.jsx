import styles from './AsyncSection.module.css'

/**
 * Rend les états d'une section alimentée par une requête : chargement, erreur,
 * résultat vide, ou contenu.
 *
 * @param {{
 *   loading: boolean,
 *   error: boolean,
 *   isEmpty: boolean,
 *   emptyLabel?: string,
 *   errorLabel?: string,
 *   children: React.ReactNode
 * }} props
 */
function AsyncSection({
  loading,
  error,
  isEmpty,
  emptyLabel = 'Rien à afficher.',
  errorLabel = 'Une erreur est survenue.',
  children,
}) {
  if (loading) return <p className={styles.state}>Chargement…</p>
  if (error) return <p className={`${styles.state} ${styles.error}`}>{errorLabel}</p>
  if (isEmpty) return <p className={styles.state}>{emptyLabel}</p>

  return children
}

export default AsyncSection
