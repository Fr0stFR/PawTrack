import styles from './ProtectionStatusList.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

/** Clés alignées sur les constantes STATUS_* de ProtectionStatus côté API. */
const STATUS = {
  expired: {
    label: 'Expiré',
    className: 'expired',
    detail: (date) => `depuis le ${formatDate(date)}`,
  },
  expiring_soon: {
    label: 'À renouveler',
    className: 'soon',
    detail: (date) => `avant le ${formatDate(date)}`,
  },
  up_to_date: {
    label: 'À jour',
    className: 'ok',
    detail: (date) => `jusqu'au ${formatDate(date)}`,
  },
  never: {
    label: 'Jamais fait',
    className: 'never',
    detail: () => null,
  },
}

/**
 * État de protection d'un animal, une ligne par protection de son espèce.
 * Présentationnel : ni tri ni filtrage, la liste arrive déjà calculée.
 *
 * @param {{ statuses: Array<object> }} props
 */
function ProtectionStatusList({ statuses }) {
  return (
    <ul className={styles.list}>
      {statuses.map((status) => {
        // Repli si l'API ajoute un statut que cette table ne connaît pas.
        const config = STATUS[status.status] ?? STATUS.never
        const detail = status.expiresAt ? config.detail(status.expiresAt) : null

        return (
          <li key={status.id} className={styles.item}>
            <span className={`${styles.dot} ${styles[config.className]}`} aria-hidden="true" />

            <span className={styles.body}>

              <span className={styles.name} title={status.protection.description ?? undefined}>
                {status.protection.name}
              </span>
              {detail && <span className={styles.detail}>{detail}</span>}
            </span>

            <span className={`${styles.label} ${styles[config.className]}`}>{config.label}</span>
          </li>
        )
      })}
    </ul>
  )
}

export default ProtectionStatusList
