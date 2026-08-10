import { useState } from 'react'
import FlashMessage from '@/components/ui/FlashMessage'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/context/AuthContext'
import { apiGet, apiPost } from '@/api'
import Icon from '@/components/ui/Icon'

function Login() {
  const [flash, setFlash] = useState(null)
  const navigate = useNavigate()
  const { setUser } = useAuth()

  async function handleLogin(formData) {
    const email = formData.get('email')
    const password = formData.get('password')

    try {
      await apiPost('/api/auth/login', { username: email, password })

      // Le cookie JWT est posé mais reste invisible au JS : l'état du contexte
      // est rafraîchi via /api/me.
      const me = await apiGet('/api/me')
      setUser(me)

      navigate('/dashboard', {
        state: { flash: { type: 'success', message: 'Connecté avec succès' } },
      })
    } catch (err) {
      // Couvre aussi bien un 401 qu'un back injoignable.
      setFlash({ type: 'danger', message: err.message })
    }
  }

  return (
    <section className="login">
      <h1>Connexion <Icon name="paw" /></h1>

      {flash && (
        <FlashMessage
          type={flash.type}
          message={flash.message}
          onClose={() => setFlash(null)}
        />
      )}

      <form action={handleLogin}>
        <div>
          <label htmlFor="email">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="toi@exemple.com"
          />
        </div>

        <div>
          <label htmlFor="password">Mot de passe</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
          />
        </div>

        <button type="submit">Se connecter</button>
      </form>
    </section>
  )
}

export default Login
