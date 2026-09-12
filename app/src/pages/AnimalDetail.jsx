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
import VisitForm from '@/features/medical/VisitForm'
import ProtectionStatusList from '@/features/health/ProtectionStatusList'
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

/** Âge en clair : « 2 ans », ou en mois avant le premier anniversaire. */
function formatAge(iso) {
  const birth = new Date(iso)
  const now = new Date()

  let months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth())

  // Le mois en cours ne compte que s'il est révolu au jour près.
  if (now.getDate() < birth.getDate()) months -= 1

  if (months < 0) return ''
  if (months < 12) return `${months} mois`

  const years = Math.floor(months / 12)

  return `${years} an${years > 1 ? 's' : ''}`
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

  // Dérivé côté serveur des événements faits et des plans : toute écriture sur
  // l'un ou l'autre doit le faire recharger.
  const {
    data: statuses,
    loading: statusesLoading,
    error: statusesError,
    refetch: refetchStatuses,
  } = useApi(`/api/animals/${id}/protection_statuses`)

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
    refetchStatuses()
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
        <div className={styles.identity}>
          <div className={styles.identityHead}>
            <span className={styles.avatar}><Icon name="paw" /></span>
            <h1>{animal.name}</h1>
          </div>

          {/* Chaque paire enveloppée dans un <div> : HTML5 l'autorise et ça
              permet de les placer en grille sans casser l'association dt/dd. */}
          <dl className={styles.facts}>
            <div>
              <dt>Espèce</dt>
              <dd>{animal.animalType?.name ?? '—'}</dd>
            </div>
            <div>
              <dt>Race</dt>
              <dd>{animal.breed?.name ?? '—'}</dd>
            </div>
            <div>
              <dt>Sexe</dt>
              <dd>{GENDERS[animal.gender] ?? animal.gender ?? '—'}</dd>
            </div>
            <div>
              <dt>Naissance</dt>
              <dd>
                {formatDate(animal.birthdate)}
                <span className={styles.age}>{formatAge(animal.birthdate)}</span>
              </dd>
            </div>
          </dl>
        </div>

        <div className={styles.protections}>
          <h2 className={styles.protectionsTitle}>Protections</h2>
          <AsyncSection
            loading={statusesLoading}
            error={statusesError}
            isEmpty={statuses?.length === 0}
            emptyLabel="Aucune protection référencée pour cette espèce."
            errorLabel="Impossible de charger l'état des protections."
          >
            <ProtectionStatusList statuses={statuses} />
          </AsyncSection>
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
                onValidate={(medicalEvent) => setModal({ kind: 'visit', medicalEvent })}
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
            {/* Sans échéance ouverte, la modale serait vide. */}
            {todo?.length > 0 && (
              <Button
                icon={<Icon name="visit" />}
                variant="secondary"
                onClick={() => setModal({ kind: 'visit' })}
              >
                Enregistrer une visite
              </Button>
            )}
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
            animalTypeIri={animal.animalType['@id']}
            medicalEvent={modal.medicalEvent}
            onSuccess={handleEventSaved}
          />
        </Modal>
      )}
      {modal?.kind === 'visit' && (
        <Modal title="Enregistrer une visite" onClose={() => setModal(null)}>
          <VisitForm
            events={todo}
            preselectedId={modal.medicalEvent?.id}
            onSuccess={handleEventSaved}
          />
        </Modal>
      )}
      {modal?.kind === 'medicalPlan' && (
        <Modal title="Ajouter une automatisation" onClose={() => setModal(null)}>
          <PlanForm
            animalId={id}
            animalTypeIri={animal.animalType['@id']}
            onSuccess={() => {
              setModal(null)
              refetchPlans()
              // La fréquence du plan prime sur la durée par défaut : créer un
              // plan peut changer une date d'expiration affichée au-dessus.
              refetchStatuses()
            }}
          />
        </Modal>
      )}
    </section>
  )
}

export default AnimalDetail
