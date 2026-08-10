import Icon from './Icon'
import styles from './MedicalPlanList.module.css'

// Accord en genre et en nombre des unités de fréquence.
const UNITS = {
  day: { one: 'jour', many: 'jours', article: 'Tous les' },
  week: { one: 'semaine', many: 'semaines', article: 'Toutes les' },
  month: { one: 'mois', many: 'mois', article: 'Tous les' },
  year: { one: 'an', many: 'ans', article: 'Tous les' },
}

/**
 * Met en forme la fréquence d'un plan : (3, 'month') → "Tous les 3 mois".
 */
function formatFrequency(value, unit) {
  const u = UNITS[unit]
  if (!u) return `${value} ${unit}`
  return value === 1 ? `${u.article} ${u.one}` : `${u.article} ${value} ${u.many}`
}

/**
 * Liste des soins récurrents configurés pour un animal.
 *
 * @param {{plans: Array<object>}} props
 */
function MedicalPlanList({ plans }) {
  return (
    <ul className={styles.list}>
      {plans.map((plan) => (
        <li key={plan.id} className={styles.item}>
          <span className={styles.icon}><Icon name="automation" /></span>
          <div className={styles.body}>
            <span className={styles.name}>{plan.name}</span>
            <span className={styles.meta}>
              {plan.medicalType.name} · {formatFrequency(plan.frequencyValue, plan.frequency)}
            </span>
          </div>
        </li>
      ))}
    </ul>
  )
}

export default MedicalPlanList
