import Button from './Button'
import Icon from './Icon'
import styles from './ConfirmDialog.module.css'

/**
 * Demande de confirmation avant une action irréversible.
 *
 * Le composant ne s'affiche PAS dans sa propre modale : il est prévu pour être
 * rendu à l'intérieur d'une <Modal> existante, en remplacement de son contenu.
 * Deux modales superposées se disputeraient l'écouteur Échap et le verrou de
 * défilement du <body>.
 *
 * @param {{
 *   title: string,
 *   detail?: string,
 *   confirmLabel?: string,
 *   onConfirm: () => void,
 *   onCancel: () => void,
 *   submitting?: boolean,
 *   error?: string|null
 * }} props
 */
function ConfirmDialog({
  title,
  detail,
  confirmLabel = 'Supprimer',
  onConfirm,
  onCancel,
  submitting = false,
  error,
}) {
  return (
    <div className={styles.confirm}>
      <span className={styles.icon}>
        <Icon name="warning" />
      </span>

      <p className={styles.title}>{title}</p>
      {detail && <p className={styles.detail}>{detail}</p>}

      {error && <p className={styles.error}>{error}</p>}

      <div className={styles.actions}>
        {/* L'action neutre en premier : c'est la sortie la plus probable, et
            elle ne doit pas se trouver sous le doigt qui vient de cliquer. */}
        <Button variant="secondary" onClick={onCancel} disabled={submitting}>
          Annuler
        </Button>
        <Button variant="danger" onClick={onConfirm} disabled={submitting}>
          {submitting ? 'Suppression…' : confirmLabel}
        </Button>
      </div>
    </div>
  )
}

export default ConfirmDialog
