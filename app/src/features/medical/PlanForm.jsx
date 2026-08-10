import { useForm } from 'react-hook-form'
import { useApi } from '@/hooks/useApi'
import { useMutation } from '@/hooks/useMutation'
import { apiPost } from '@/api'
import Button from '@/components/ui/Button'
import Field from '@/components/ui/Field'
import styles from '@/styles/forms.module.css'

const UNITS = [
  { value: 'day', label: 'jour(s)' },
  { value: 'week', label: 'semaine(s)' },
  { value: 'month', label: 'mois' },
  { value: 'year', label: 'an(s)' },
]

/**
 * Formulaire de création d'un soin récurrent (MedicalPlan).
 *
 * @param {{animalId: string, onSuccess: (plan: object) => void}} props
 */
function PlanForm({ animalId, onSuccess }) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm()

  const { data: types } = useApi('/api/medical_types')

  const { mutate, submitting, error: submitError } = useMutation(
    (body) => apiPost('/api/medical_plans', body),
    { onSuccess },
  )

  function onSubmit(data) {
    mutate({
      name: data.name,
      medicalType: data.medicalType,
      animal: `/api/animals/${animalId}`,
      frequency: data.frequency,
      frequencyValue: data.frequencyValue,
    })
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className={styles.form}>
      <Field label="Nom" error={errors.name}>
        <input
          type="text"
          placeholder="Ex : Vermifuge trimestriel"
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

      <Field label="Tous les (nombre)" error={errors.frequencyValue}>
        {/* valueAsNumber : le back attend un entier, pas la chaîne saisie. */}
        <input
          type="number"
          min="1"
          placeholder="3"
          {...register('frequencyValue', {
            required: 'Requis',
            valueAsNumber: true,
            min: { value: 1, message: 'Au moins 1' },
          })}
        />
      </Field>

      <Field label="Unité" error={errors.frequency}>
        <select {...register('frequency', { required: 'Choisis une unité' })}>
          {UNITS.map((u) => (
            <option key={u.value} value={u.value}>
              {u.label}
            </option>
          ))}
        </select>
      </Field>

      {submitError && <p className={styles.submitError}>{submitError}</p>}

      <Button type="submit" disabled={submitting}>
        {submitting ? 'Ajout…' : 'Ajouter'}
      </Button>
    </form>
  )
}

export default PlanForm
