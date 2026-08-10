import { useState, useEffect } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import FlashMessage from './FlashMessage'

/**
 * Coque commune à toutes les pages : affiche le message flash transporté par
 * navigate('/x', { state: { flash } }), puis rend la page courante.
 */
function Layout() {
  const location = useLocation()
  const [flash, setFlash] = useState(null)

  useEffect(() => {
    if (location.state?.flash) {
      setFlash(location.state.flash)
      // Le flash est consommé : on le retire de l'historique pour qu'un
      // rafraîchissement ou un retour arrière ne le réaffiche pas.
      window.history.replaceState({}, '')
    }
  }, [location])

  return (
    <>
      {flash && (
        <FlashMessage
          type={flash.type}
          message={flash.message}
          onClose={() => setFlash(null)}
        />
      )}

      <Outlet />
    </>
  )
}

export default Layout
