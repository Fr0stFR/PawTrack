import { useForm, useWatch } from 'react-hook-form'
import { useApi } from '@/hooks/useApi'
import { useMutation } from '@/hooks/useMutation'
import { apiPost } from '@/api'
import Button from '@/components/ui/Button'
import Field from '@/components/ui/Field'
import styles from '@/styles/forms.module.css'

// Format attendu par l'attribut `max` d'un <input type="date">.
const TODAY = new Date().toISOString().slice(0, 10)

/**
 * Formulaire de création d'un animal.
 *
 * @param {{onSuccess: (animal: object) => void}} props
 */
function AnimalForm({ onSuccess }) {
  const {
    register,
    handleSubmit,
    control,
    resetField,
    formState: { errors },
  } = useForm()

  const { data: animalTypes } = useApi('/api/animal_types')

  // L'abonnement au champ espèce provoque le re-rendu qui recalcule le chemin
  // des races ci-dessous. useWatch plutôt que watch() : l'abonnement reste
  // local au composant au lieu de re-rendre tout le formulaire.
  const animalTypeId = useWatch({ control, name: 'animalType' })

  // Tant qu'aucune espèce n'est choisie, le hook reste en veille (path null).
  const { data: breeds, loading: breedsLoading } = useApi(
    animalTypeId ? `/api/breeds?animalType=${animalTypeId}` : null,
  )

  const { mutate, submitting, error: submitError } = useMutation(
    (body) => apiPost('/api/animals', body),
    { onSuccess },
  )

  function onSubmit(data) {
    mutate({
      name: data.name,
      // Les <option> portent l'identifiant brut, nécessaire au filtre
      // ?animalType= ; l'IRI est reconstruite au moment de l'envoi.
      animalType: `/api/animal_types/${data.animalType}`,
      breed: data.breed ? `/api/breeds/${data.breed}` : null,
      gender: data.gender,
      birthdate: data.birthdate,
      // `owner` est volontairement absent : AnimalProcessor l'affecte côté
      // serveur à partir de l'utilisateur authentifié.
    })
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
      <Field label="Nom" error={errors.name}>
        <input
          type="text"
          placeholder="Ex : Vangogh"
          {...register('name', { required: 'Le nom est requis' })}
        />
      </Field>

      <Field label="Espèce" error={errors.animalType}>
        <select
          {...register('animalType', {
            required: "Choisis une espèce",
            // Sans ce reset, une race sélectionnée pour l'espèce précédente
            // resterait dans le formulaire après changement d'espèce.
            onChange: () => resetField('breed'),
          })}
        >
          <option value="">— Choisir —</option>
          {animalTypes?.map((type) => (
            <option key={type.id} value={type.id}>
              {type.name}
            </option>
          ))}
        </select>
      </Field>

      <Field label="Race (facultatif)" error={errors.breed}>
        <select {...register('breed')} disabled={!animalTypeId || breedsLoading}>
          <option value="">
            {!animalTypeId
              ? '— Choisis d’abord une espèce —'
              : breedsLoading
                ? 'Chargement…'
                : '— Aucune / inconnue —'}
          </option>
          {breeds?.map((breed) => (
            <option key={breed.id} value={breed.id}>
              {breed.name}
            </option>
          ))}
        </select>
      </Field>

      <Field label="Sexe" error={errors.gender}>
        <select {...register('gender', { required: 'Choisis un sexe' })}>
          <option value="">— Choisir —</option>
          <option value="M">Mâle</option>
          <option value="F">Femelle</option>
        </select>
      </Field>

      <Field label="Date de naissance" error={errors.birthdate}>
        <input
          type="date"
          max={TODAY}
          {...register('birthdate', {
            required: 'La date de naissance est requise',
            // L'attribut `max` ne contraint que le sélecteur natif : la saisie
            // clavier doit être revalidée.
            validate: (value) =>
              value <= TODAY || 'La date ne peut pas être dans le futur',
          })}
        />
      </Field>

      {submitError && <p className={styles.submitError}>{submitError}</p>}

      <Button type="submit" disabled={submitting}>
        {submitting ? 'Création…' : "Créer l'animal"}
      </Button>
    </form>
  )
}

export default AnimalForm
