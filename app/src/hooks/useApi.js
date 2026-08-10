import { useState, useEffect } from 'react'
import { apiGet } from '@/api'

/**
 * Encapsule le cycle de vie d'une requête GET : chargement, erreur, données.
 *
 * @param {string|null} path Chemin API, ou null pour désactiver la requête
 *                           (utile pour un appel conditionnel, la règle des
 *                           hooks interdisant un appel sous condition).
 * @returns {{data: object|null, loading: boolean, error: boolean, refetch: () => void}}
 */
export function useApi(path) {
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)
  const [reloadIndex, setReloadIndex] = useState(0)

  useEffect(() => {
    if (!path) return

    let ignore = false
    setLoading(true)
    setError(false)

    apiGet(path)
      .then((result) => { if (!ignore) setData(result) })
      .catch(() => { if (!ignore) setError(true) })
      .finally(() => { if (!ignore) setLoading(false) })

    // Ignore la réponse d'une requête obsolète (changement de path, démontage).
    return () => { ignore = true }
  }, [path, reloadIndex])

  const refetch = () => setReloadIndex((i) => i + 1)

  // État dérivé plutôt qu'un setState dans l'effet, qui déclencherait un rendu
  // en cascade à chaque activation/désactivation du hook.
  if (!path) {
    return { data: null, loading: false, error: false, refetch }
  }

  return { data, loading, error, refetch }
}
