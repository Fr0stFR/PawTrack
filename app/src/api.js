/**
 * Client HTTP unique vers l'API Symfony.
 */

const BASE_URL = import.meta.env.VITE_API_URL

/**
 * Normalise une réponse fetch : données parsées, ou Error avec le message du back.
 *
 * @param {Response} response
 * @returns {Promise<object|null>}
 */
async function handleResponse(response) {
  // fetch ne rejette pas sur 4xx/5xx : le statut doit être vérifié manuellement.
  if (!response.ok) {
    let message = `Erreur ${response.status}`
    try {
      const data = await response.json()
      // 'message' côté Lexik (auth), 'detail' côté API Platform.
      message = data.message || data.detail || message
    } catch {
      // Réponse sans corps JSON : on conserve le message générique.
    }
    throw new Error(message)
  }

  if (response.status === 204) return null

  return response.json()
}

/**
 * @param {string} path Chemin absolu côté API, ex. '/api/animals'
 * @returns {Promise<object|null>}
 */
export async function apiGet(path) {
  const response = await fetch(`${BASE_URL}${path}`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
  return handleResponse(response)
}

/**
 * @param {string} path Chemin absolu côté API
 * @param {object} body Corps sérialisé en JSON
 * @returns {Promise<object|null>}
 */
export async function apiPost(path, body) {
  const response = await fetch(`${BASE_URL}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  })
  return handleResponse(response)
}

/**
 * Mise à jour partielle : seules les clés envoyées sont modifiées, les autres
 * restent intactes (`null` efface explicitement une valeur).
 *
 * @param {string} path Chemin absolu côté API, ex. '/api/medical_events/12'
 * @param {object} body Champs à modifier
 * @returns {Promise<object|null>}
 */
export async function apiPatch(path, body) {
  const response = await fetch(`${BASE_URL}${path}`, {
    method: 'PATCH',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      // API Platform refuse le PATCH en 'application/json' (415) : il impose
      // le type MIME du JSON Merge Patch (RFC 7396).
      'Content-Type': 'application/merge-patch+json',
    },
    body: JSON.stringify(body),
  })
  return handleResponse(response)
}
