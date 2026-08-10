import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

/**
 * Garde d'accès : ne rend ses routes enfants que pour un utilisateur connecté.
 */
function ProtectedRoute() {
  const { user, loading } = useAuth()

  // Tant que /api/me n'a pas répondu, `user` est null sans que le statut soit
  // connu : rediriger ici déconnecterait l'utilisateur à chaque rafraîchissement.
  if (loading) {
    return <p>Chargement…</p>
  }

  // replace : évite d'empiler la route protégée dans l'historique, sinon le
  // bouton retour renvoie en boucle vers la redirection.
  if (!user) {
    return <Navigate to="/login" replace />
  }

  return <Outlet />
}

export default ProtectedRoute
