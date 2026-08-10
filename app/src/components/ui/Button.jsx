import styles from './Button.module.css'

/**
 * Bouton d'action du kit UI.
 *
 * @param {{
 *   variant?: 'primary'|'secondary',
 *   type?: string,
 *   icon?: React.ReactNode,
 *   children: React.ReactNode
 * }} props Les props restantes (onClick, disabled…) sont transmises au <button>.
 */
function Button({ variant = 'primary', type = 'button', icon, children, ...props }) {
  const className =
    variant === 'secondary' ? `${styles.button} ${styles.secondary}` : styles.button

  return (
    <button type={type} className={className} {...props}>
      {icon && <span className={styles.icon}>{icon}</span>}
      {children}
    </button>
  )
}

export default Button
