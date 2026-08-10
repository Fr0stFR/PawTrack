import { useEffect } from 'react'
import './FlashMessage.css'

/**
 * Notification temporaire, masquée automatiquement après `duration`.
 *
 * @param {{
 *   type: 'success'|'danger',
 *   message: string,
 *   onClose: () => void,
 *   duration?: number
 * }} props
 */
function FlashMessage({ type, message, onClose, duration = 3000 }) {
  useEffect(() => {
    const timer = setTimeout(onClose, duration)

    return () => clearTimeout(timer)
  }, [onClose, duration])

  return (
    <div className={`flash flash-${type}`} role="alert">
      {message}
    </div>
  )
}

export default FlashMessage
