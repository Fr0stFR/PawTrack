import { useForm, useWatch } from 'react-hook-form'
import { useMutation } from '@/hooks/useMutation'
import { apiPatch } from '@/api'
import Button from '@/components/ui/Button'
import Field from '@/components/ui/Field'
import styles from '@/styles/forms.module.css'
import visitStyles from './VisitForm.module.css'

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
}

/**
 * Enregistre une visite : plusieurs échéances validées d'un coup, même date et
 * mêmes notes.
 *
 * Aucune entité « visite » n'existe en base : ce sont N MedicalEvent partageant
 * un `doneAt`, chacun faisant avancer son plan. D'où N PATCH et pas d'endpoint
 * dédié.
 *
 * @param {{
 *   events: Array<object>,
 *   preselectedId?: number,
 *   onSuccess: () => void
 * }} props `events` = les échéances encore ouvertes de l'animal.
 */
function VisitForm({ events, preselectedId, onSuccess }) {
  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
  } = useForm({
    defaultValues: {
      eventIds: preselectedId ? [String(preselectedId)] : [],
      doneAt: new Date().toLocaleDateString('sv-SE'),
      description: '',
    },
  })

  const checkedIds = useWatch({ control, name: 'eventIds' }) ?? []

  const { mutate, submitting, error } = useMutation(async (data) => {
    for (const id of data.eventIds) {
      await apiPatch(`/api/medical_events/${id}`, {
        isDone: true,
        doneAt: data.doneAt,
        description: data.description || null,
      })
    }
  }, { onSuccess })

  return (
    <form onSubmit={handleSubmit(mutate)} className={styles.form}>
      <p className={visitStyles.intro}>
        Coche ce qui a été fait lors de cette visite. La date et les notes
        s’appliqueront à tout ce qui est coché.
      </p>

      <Field label="Fait le" error={errors.doneAt}>
        <input type="date" {...register('doneAt', { required: 'La date est requise' })} />
      </Field>

      <fieldset className={visitStyles.group}>
        <legend className={visitStyles.legend}>Soins réalisés</legend>

        {events.map((event) => (
          <label key={event.id} className={visitStyles.choice}>
            <input
              type="checkbox"
              value={event.id}
              {...register('eventIds', {
                validate: (ids) => ids.length > 0 || 'Coche au moins un soin',
              })}
            />
            <span className={visitStyles.choiceBody}>
              <span className={visitStyles.choiceName}>{event.name}</span>
              <span className={visitStyles.choiceMeta}>
                {event.medicalType.name} · prévu le {formatDate(event.date)}
              </span>
            </span>
          </label>
        ))}

        {errors.eventIds && <span className={visitStyles.error}>{errors.eventIds.message}</span>}
      </fieldset>

      <Field label="Notes (facultatif)" error={errors.description}>
        <textarea
          rows={3}
          placeholder="Consultation Dr Martin, 85 €…"
          {...register('description')}
        />
      </Field>

      {error && <p className={styles.submitError}>{error}</p>}

      <Button type="submit" disabled={submitting || checkedIds.length === 0}>
        {submitting ? 'Enregistrement…' : `Valider (${checkedIds.length})`}
      </Button>
    </form>
  )
}

export default VisitForm
