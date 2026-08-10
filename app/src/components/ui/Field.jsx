import styles from './Field.module.css'

/**
 * Habillage d'un champ de formulaire : libellé, contrôle et message d'erreur.
 *
 * Le contrôle est passé en `children` afin que le parent y applique lui-même
 * son register() : la ref de React Hook Form se pose alors directement sur
 * l'élément natif, sans forwardRef.
 *
 * @param {{label: string, error?: {message: string}, children: React.ReactNode}} props
 */
function Field({ label, error, children }) {
  return (
    <label className={styles.field}>
      <span className={styles.label}>{label}</span>
      {children}
      {error && <span className={styles.err}>{error.message}</span>}
    </label>
  )
}

export default Field
