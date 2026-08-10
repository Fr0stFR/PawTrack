import { createContext, useContext, useState, useEffect } from 'react'
import { apiGet } from '@/api'

const AuthContext = createContext(null)

/**
 * Expose l'état d'authentification à toute l'application.
 *
 * Le JWT étant stocké dans un cookie httpOnly, il est invisible au JS : l'état
 * de connexion ne peut être déterminé qu'en interrogeant le back au démarrage.
 */
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiGet('/api/me')
      .then((data) => setUser(data))
      .catch(() => setUser(null)) // 401 ou back injoignable
      .finally(() => setLoading(false))
  }, [])

  return (
    <AuthContext.Provider value={{ user, setUser, loading }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  return useContext(AuthContext)
}
