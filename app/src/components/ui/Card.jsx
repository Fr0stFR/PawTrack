import styles from './Card.module.css'

/**
 * Panneau de contenu du kit UI.
 *
 * `icon` et `action` reçoivent des nœuds déjà construits plutôt que des données
 * brutes : Card les positionne sans rien connaître du registre d'icônes ni des
 * routes de l'application.
 *
 * @param {{
 *   title?: string,
 *   icon?: React.ReactNode,
 *   action?: React.ReactNode,
 *   bodyClassName?: string,
 *   children: React.ReactNode
 * }} props
 */
function Card({ title, icon, action, bodyClassName = '', children }) {
  return (
    <section className={styles.card}>
      {/* L'en-tête vit hors du corps : il reste fixe si l'appelant applique un
          overflow via bodyClassName. */}
      {(title || action) && (
        <header className={styles.header}>
          {title && (
            <h2 className={styles.title}>
              {icon && <span className={styles.icon}>{icon}</span>}
              {title}
            </h2>
          )}
          {action}
        </header>
      )}

      <div className={`${styles.body} ${bodyClassName}`}>{children}</div>
    </section>
  )
}

export default Card
