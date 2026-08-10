import { useForm } from 'react-hook-form'
import { useApi } from '@/hooks/useApi'
import { useMutation } from '@/hooks/useMutation'
import { apiPost } from '@/api'
import Button from '@/components/ui/Button'
import Field from '@/components/ui/Field'
import styles from '@/styles/forms.module.css'

/**
 * Formulaire de création d'un événement médical.
 *
 * @param {{animalId: string, onSuccess: (event: object) => void}} props
 */
function EventForm({ animalId, onSuccess }) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm()

  const { data: types } = useApi('/api/medical_types')

  const { mutate, submitting, error: submitError } = useMutation(
    (body) => apiPost('/api/medical_events', body),
    { onSuccess },
  )

  function onSubmit(data) {
    mutate({
      name: data.name,
      medicalType: data.medicalType, // déjà une IRI (valeur de l'<option>)
      animal: `/api/animals/${animalId}`,
      date: data.date,
      isDone: false,
    })
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
      <Field label="Nom" error={errors.name}>
        <input
          type="text"
          placeholder="Ex : Rappel vaccin"
          {...register('name', { required: 'Le nom est requis' })}
        />
      </Field>

      <Field label="Type" error={errors.medicalType}>
        <select {...register('medicalType', { required: 'Choisis un type' })}>
          <option value="">— Choisir —</option>
          {types?.map((t) => (
            <option key={t.id} value={`/api/medical_types/${t.id}`}>
              {t.name}
            </option>
          ))}
        </select>
      </Field>

      <Field label="Date" error={errors.date}>
        <input type="date" {...register('date', { required: 'La date est requise' })} />
      </Field>

      {submitError && <p className={styles.submitError}>{submitError}</p>}

      <Button type="submit" disabled={submitting}>
        {submitting ? 'Ajout…' : 'Ajouter'}
      </Button>
    </form>
  )
}

export default EventForm
