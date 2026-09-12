import { useForm, useWatch } from 'react-hook-form'
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

function getDefaultDate() {
  // 'sv-SE' est le seul format de locale qui sorte du YYYY-MM-DD, et il reste en heure locale.
  return new Date().toLocaleDateString('sv-SE')
}

/**
 * Formulaire de création d'un soin récurrent (MedicalPlan).
 *
 * @param {{
 *   animalId: string,
 *   animalTypeIri: string,
 *   onSuccess: (plan: object) => void
 * }} props
 */
function PlanForm({ animalId, animalTypeIri, onSuccess }) {
  const {
    register,
    handleSubmit,
    control,
    setValue,
    getValues,
    formState: { errors },
  } = useForm({
    defaultValues: {
      protection: '',
      startsAt: getDefaultDate(),
    },
  })

  const protectionId = useWatch({ control, name: 'protection' })

  const { data: types } = useApi('/api/medical_types')
  const { data: protections } = useApi(`/api/protections?animalTypes=${animalTypeIri}`)

  const selectedProtection = protections?.find((p) => String(p.id) === protectionId)

  /**
   * Choisir une protection pré-remplit le rythme depuis le référentiel. Simples
   * défauts : l'utilisateur reste libre de les modifier ensuite.
   */
  function applyProtectionDefaults(event) {
    const protection = protections?.find((p) => String(p.id) === event.target.value)

    if (!protection) return

    setValue('frequency', protection.defaultFrequency)
    setValue('frequencyValue', protection.defaultFrequencyValue)

    // Champ libre : jamais écrasé s'il a déjà été rempli.
    if (!getValues('name')) {
      setValue('name', protection.name)
    }
  }

  const { mutate, submitting, error: submitError } = useMutation(
    (body) => apiPost('/api/medical_plans', body),
    { onSuccess },
  )

  function onSubmit(data) {
    mutate({
      name: data.name,
      medicalType: `/api/medical_types/${data.medicalType}`,
      protection: data.protection ? `/api/protections/${data.protection}` : null,
      animal: `/api/animals/${animalId}`,
      frequency: data.frequency,
      frequencyValue: data.frequencyValue,
      startsAt: data.startsAt,
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
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
      </Field>

      {/* Avant la fréquence : le choix pré-remplit les deux champs suivants. */}
      <Field
        label="Protection (facultatif)"
        error={errors.protection}
        hint={selectedProtection?.description}
      >
        <select {...register('protection', { onChange: applyProtectionDefaults })}>
          <option value="">— Aucune —</option>
          {protections?.map((protection) => (
            <option key={protection.id} value={protection.id}>
              {protection.name}
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
      
      <Field label="Première échéance le" error={errors.startsAt}>
        <input type="date" {...register('startsAt', { required: 'La date est requise' })} />
      </Field>

      {submitError && <p className={styles.submitError}>{submitError}</p>}

      <Button type="submit" disabled={submitting}>
        {submitting ? 'Ajout…' : 'Ajouter'}
      </Button>
    </form>
  )
}

export default PlanForm
