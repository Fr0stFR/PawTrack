import { useState } from 'react'

/**
 * Pendant de useApi pour les requêtes d'écriture : expose l'état d'envoi et
 * centralise la gestion d'erreur des formulaires.
 *
 * @param {(...args: any[]) => Promise<any>} mutationFn Appel réseau à exécuter
 * @param {{onSuccess?: (result: any) => void}} [options]
 * @returns {{mutate: (...args: any[]) => Promise<void>, submitting: boolean, error: string|null}}
 */
export function useMutation(mutationFn, { onSuccess } = {}) {
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState(null)

  async function mutate(...args) {
    setSubmitting(true)
    setError(null)
    try {
      const result = await mutationFn(...args)
      onSuccess?.(result)
    } catch (err) {
      setError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  return { mutate, submitting, error }
}
