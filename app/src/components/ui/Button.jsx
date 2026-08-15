import styles from './Button.module.css'

/**
 * Bouton d'action du kit UI.
 *
 * @param {{
 *   variant?: 'primary'|'secondary'|'danger',
 *   type?: string,
 *   icon?: React.ReactNode,
 *   children: React.ReactNode
 * }} props Les props restantes (onClick, disabled…) sont transmises au <button>.
 */
function Button({ variant = 'primary', type = 'button', icon, children, ...props }) {
  // `primary` n'a pas de classe dédiée : styles.primary vaut undefined et
  // disparaît au filtrage. Ajouter une variante = ajouter une classe au CSS.
  const className = [styles.button, styles[variant]].filter(Boolean).join(' ')

  return (
    <button type={type} className={className} {...props}>
      {icon && <span className={styles.icon}>{icon}</span>}
      {children}
    </button>
  )
}

export default Button
