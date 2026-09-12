import styles from './Field.module.css'

/**
 * Habillage d'un champ de formulaire : libellé, contrôle et message d'erreur.
 *
 * Le contrôle est passé en `children` afin que le parent y applique lui-même
 * son register() : la ref de React Hook Form se pose alors directement sur
 * l'élément natif, sans forwardRef.
 *
 * `hint` affiche une précision sous le contrôle, indépendante de `error`.
 *
 * @param {{
 *   label: string,
 *   error?: {message: string},
 *   hint?: React.ReactNode,
 *   children: React.ReactNode
 * }} props
 */
function Field({ label, error, hint, children }) {
  return (
    <label className={styles.field}>
      <span className={styles.label}>{label}</span>
      {children}
      {hint && <span className={styles.hint}>{hint}</span>}
      {error && <span className={styles.err}>{error.message}</span>}
    </label>
  )
}

export default Field
