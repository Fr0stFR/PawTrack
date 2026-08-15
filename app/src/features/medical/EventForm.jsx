import { useForm } from 'react-hook-form'
import { useApi } from '@/hooks/useApi'
import { useMutation } from '@/hooks/useMutation'
import { apiPost, apiPatch } from '@/api'
import Button from '@/components/ui/Button'
import Field from '@/components/ui/Field'
import styles from '@/styles/forms.module.css'

// <input type="date"> n'accepte que 'YYYY-MM-DD', l'API renvoie un ISO complet.
function toDateInput(iso) {
  return iso ? iso.slice(0, 10) : ''
}

/**
 * Formulaire d'un événement médical, en création ou en édition.
 *
 * @param {{
 *   animalId: string,
 *   medicalEvent?: object,
 *   onSuccess: (medicalEvent: object) => void
 * }} props `medicalEvent` absent = création (POST), présent = édition (PATCH).
 */
function EventForm({ animalId, medicalEvent, onSuccess }) {
  const isEdit = Boolean(medicalEvent)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    // Équivalent React du `createForm(EventType::class, $event)` de Symfony :
    // le même formulaire part vide ou pré-rempli selon l'entité qu'on lui donne.
    defaultValues: {
      name: medicalEvent?.name ?? '',
      medicalType: medicalEvent ? String(medicalEvent.medicalType.id) : '',
      date: toDateInput(medicalEvent?.date),
      description: medicalEvent?.description ?? '',
      isDone: medicalEvent?.isDone ?? false,
    },
  })

  const { data: types } = useApi('/api/medical_types')

  const { mutate, submitting, error: submitError } = useMutation(
    (body) =>
      isEdit
        ? apiPatch(`/api/medical_events/${medicalEvent.id}`, body)
        : apiPost('/api/medical_events', body),
    { onSuccess },
  )

  function onSubmit(data) {
    mutate({
      name: data.name,
      medicalType: `/api/medical_types/${data.medicalType}`,
      animal: `/api/animals/${animalId}`,
      date: data.date,
      isDone: data.isDone,
      // Chaîne vide → null : on efface la valeur côté API plutôt que d'y
      // stocker une chaîne vide qui se ferait passer pour une description.
      description: data.description || null,
      // `doneAt` est volontairement absent : MedicalEventProcessor le déduit
      // de `isDone` côté serveur.
    })
  }

  // Le <select> ne peut pas retrouver sa valeur par défaut tant que ses <option>
  // n'existent pas : on attend la liste des types avant de monter le formulaire.
  if (!types) return <p className={styles.loading}>Chargement…</p>

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
          {types.map((type) => (
            <option key={type.id} value={type.id}>
              {type.name}
            </option>
          ))}
        </select>
      </Field>

      <Field label="Date" error={errors.date}>
        <input type="date" {...register('date', { required: 'La date est requise' })} />
      </Field>

      <Field label="Description (facultatif)" error={errors.description}>
        <textarea
          rows={4}
          placeholder="Compte-rendu, traitement prescrit, effets constatés…"
          {...register('description')}
        />
      </Field>

      <label className={styles.checkbox}>
        <input type="checkbox" {...register('isDone')} />
        <span>C’est fait</span>
      </label>

      {submitError && <p className={styles.submitError}>{submitError}</p>}

      <Button type="submit" disabled={submitting}>
        {submitting ? 'Enregistrement…' : isEdit ? 'Enregistrer' : 'Ajouter'}
      </Button>
    </form>
  )
}

export default EventForm
