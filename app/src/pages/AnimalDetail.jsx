import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useApi } from '@/hooks/useApi'
import Card from '@/components/ui/Card'
import AsyncSection from '@/components/ui/AsyncSection'
import MedicalEventList from '@/features/medical/MedicalEventList'
import MedicalPlanList from '@/features/medical/MedicalPlanList'
import Button from '@/components/ui/Button'
import Modal from '@/components/ui/Modal'
import EventForm from '@/features/medical/EventForm'
import PlanForm from '@/features/medical/PlanForm'
import Icon from '@/components/ui/Icon'
import styles from './AnimalDetail.module.css'

const GENDERS = { M: 'Mâle', F: 'Femelle' }
const HISTORY_STEP = 10

function formatDate(iso) {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

/**
 * Fiche d'un animal : carnet médical, historique paginé et soins récurrents.
 */
function AnimalDetail() {
  const { id } = useParams()

  // Modifier la taille de page change le chemin, ce qui déclenche le re-fetch.
  const [historyLimit, setHistoryLimit] = useState(HISTORY_STEP)

  // Un seul objet décrit la modale ouverte, plutôt que plusieurs états à tenir
  // cohérents entre eux :
  //   null                                        → aucune modale
  //   { kind: 'medicalEvent' }                    → création d'un événement
  //   { kind: 'medicalEvent', medicalEvent: {…} } → édition de cet événement
  //   { kind: 'medicalPlan' }                     → création d'une automatisation
  const [modal, setModal] = useState(null)

  const { data: animal, loading, error } = useApi(`/api/animals/${id}`)

  const { data: plans, loading: plansLoading, error: plansError, refetch: refetchPlans } =
    useApi(`/api/medical_plans?animal=${id}`)

  const { data: todo, loading: todoLoading, error: todoError, refetch: refetchTodo } =
    useApi(`/api/medical_events?animal=${id}&isDone=false&order[date]=asc`)

  const { data: history, loading: historyLoading, error: historyError, refetch: refetchHistory } =
    useApi(
      // Historique classé par date de réalisation. `order[date]` départage les
      // événements cochés dans la même seconde : sans ordre stable, « Voir plus »
      // pourrait afficher deux fois la même ligne ou en sauter une.
      `/api/medical_events?animal=${id}&isDone=true&order[doneAt]=desc&order[date]=desc&itemsPerPage=${historyLimit}`,
    )

  // Cocher « c'est fait » fait sortir la ligne d'une liste pour la faire entrer
  // dans l'autre : les deux doivent être rechargées, quelle que soit l'origine
  // du clic. C'est la raison d'être de cet état ici plutôt que dans la liste.
  function handleEventSaved() {
    setModal(null)
    refetchTodo()
    refetchHistory()
  }

  if (loading) return <p className={styles.pageState}>Chargement…</p>
  if (error) return <p className={styles.pageState}>Animal introuvable.</p>

  // La réponse ne portant pas le total, une page pleine est interprétée comme
  // l'indice qu'il reste des éléments à charger.
  const hasMoreHistory = history?.length === historyLimit

  return (
    <section className={styles.detail}>
      <Link to="/dashboard" className={styles.back}>← Retour au tableau de bord</Link>

      <header className={styles.header}>
        <span className={styles.avatar}><Icon name="paw" /></span>
        <div>
          <h1>{animal.name}</h1>
          <p className={styles.meta}>
            {[
              animal.animalType?.name,
              animal.breed?.name,
              GENDERS[animal.gender] ?? animal.gender,
              `Né${animal.gender === 'F' ? 'e' : ''} le ${formatDate(animal.birthdate)}`,
            ]
              .filter(Boolean)
              .join(' · ')}
          </p>
        </div>
      </header>

      <div className={styles.grid}>
        <div>
          <Card title="À faire" icon={<Icon name="todo" />} bodyClassName={styles.scrollBody}>
            <AsyncSection
              loading={todoLoading}
              error={todoError}
              isEmpty={todo?.length === 0}
              emptyLabel="Rien à faire pour le moment."
              errorLabel="Impossible de charger les événements à faire."
            >
              <MedicalEventList
                events={todo}
                showAnimal={false}
                onSelect={(medicalEvent) => setModal({ kind: 'medicalEvent', medicalEvent })}
              />
            </AsyncSection>
          </Card>

          <Card title="Historique" icon={<Icon name="history" />} bodyClassName={styles.scrollBody}>
            <AsyncSection
              loading={historyLoading}
              error={historyError}
              isEmpty={history?.length === 0}
              emptyLabel="Aucun événement passé."
              errorLabel="Impossible de charger l'historique."
            >
              <>
                <MedicalEventList
                  events={history}
                  showAnimal={false}
                  onSelect={(medicalEvent) => setModal({ kind: 'medicalEvent', medicalEvent })}
                />
                {hasMoreHistory && (
                  <button
                    type="button"
                    className={styles.more}
                    onClick={() => setHistoryLimit(historyLimit + HISTORY_STEP)}
                  >
                    Voir plus
                  </button>
                )}
              </>
            </AsyncSection>
          </Card>
        </div>

        <div>
          <Card title="Automatisations" icon={<Icon name="automation" />}>
            <AsyncSection
              loading={plansLoading}
              error={plansError}
              isEmpty={plans?.length === 0}
              emptyLabel="Aucune automatisation configurée."
              errorLabel="Impossible de charger les automatisations."
            >
              <MedicalPlanList plans={plans} />
            </AsyncSection>
          </Card>

          <div className={styles.actions}>
            <Button icon={<Icon name="plus" />} onClick={() => setModal({ kind: 'medicalEvent' })}>
              Ajouter un élément
            </Button>
            <Button icon={<Icon name="automation" />} variant="secondary" onClick={() => setModal({ kind: 'medicalPlan' })}>
              Ajouter une automatisation
            </Button>
          </div>
        </div>
      </div>

      {modal?.kind === 'medicalEvent' && (
        <Modal
          title={modal.medicalEvent ? "Modifier l'élément" : 'Ajouter un élément'}
          onClose={() => setModal(null)}
        >
          <EventForm
            animalId={id}
            medicalEvent={modal.medicalEvent}
            onSuccess={handleEventSaved}
          />
        </Modal>
      )}
      {modal?.kind === 'medicalPlan' && (
        <Modal title="Ajouter une automatisation" onClose={() => setModal(null)}>
          <PlanForm
            animalId={id}
            onSuccess={() => {
              setModal(null)
              refetchPlans()
            }}
          />
        </Modal>
      )}
    </section>
  )
}

export default AnimalDetail
