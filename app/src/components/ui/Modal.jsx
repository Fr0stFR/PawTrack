import { useEffect } from 'react'
import { createPortal } from 'react-dom'
import Icon from './Icon'
import styles from './Modal.module.css'

/**
 * Fenêtre modale : fond assombri, boîte centrée, fermeture au clic ou à Échap.
 *
 * @param {{title: string, onClose: () => void, children: React.ReactNode}} props
 */
function Modal({ title, onClose, children }) {
  useEffect(() => {
    function handleKey(e) {
      if (e.key === 'Escape') onClose()
    }
    document.addEventListener('keydown', handleKey)
    document.body.style.overflow = 'hidden'

    return () => {
      document.removeEventListener('keydown', handleKey)
      document.body.style.overflow = ''
    }
  }, [onClose])

  // Rendu dans <body> pour échapper aux contextes d'empilement et aux overflow
  // des parents, tout en restant dans l'arbre React (les callbacks fonctionnent).
  return createPortal(
    <div className={styles.backdrop} onClick={onClose}>
      {/* Empêche un clic dans la boîte de remonter au fond et de la fermer. */}
      <div
        className={styles.modal}
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
      >
        <header className={styles.header}>
          <h2 className={styles.title}>{title}</h2>
          <button
            type="button"
            className={styles.close}
            onClick={onClose}
            aria-label="Fermer"
          >
            <Icon name="close" />
          </button>
        </header>
        <div className={styles.body}>{children}</div>
      </div>
    </div>,
    document.body,
  )
}

export default Modal
