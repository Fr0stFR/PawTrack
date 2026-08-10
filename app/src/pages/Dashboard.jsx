import { Link } from 'react-router-dom'
import Card from '../components/Card'
import AsyncSection from '../components/AsyncSection'
import AnimalList from '../components/AnimalList'
import MedicalEventList from '../components/MedicalEventList'
import Icon from '../components/Icon'
import { useApi } from '../hooks/useApi'
import styles from './Dashboard.module.css'

function Dashboard() {
  const { data: animals, loading, error } = useApi('/api/animals')

  // Le filtre porte sur le statut et non sur la date, afin que les événements
  // en retard restent visibles dans les prochaines échéances.
  const {
    data: events,
    loading: eventsLoading,
    error: eventsError,
  } = useApi('/api/medical_events?isDone=false&order[date]=asc&itemsPerPage=5')

  return (
    <section className={styles.dashboard}>
      <header className={styles.header}>
        <h1>Tableau de bord <Icon name="paw" /></h1>
      </header>

      <div className={styles.grid}>
        <Card
          title="Mes animaux"
          action={
            <Link to="/animals/new" className={styles.addLink}>
              <Icon name="plus" /> Ajouter un animal
            </Link>
          }
        >
          <AsyncSection
            loading={loading}
            error={error}
            isEmpty={animals?.length === 0}
            emptyLabel="Vous n'avez pour le moment aucun animal, ajoutez-en un."
            errorLabel="Impossible de récupérer vos animaux."
          >
            <AnimalList animals={animals} />
          </AsyncSection>
        </Card>

        <Card title="Prochaines échéances">
          <AsyncSection
            loading={eventsLoading}
            error={eventsError}
            isEmpty={events?.length === 0}
            emptyLabel="Aucune échéance à venir."
            errorLabel="Impossible de récupérer les échéances."
          >
            <MedicalEventList events={events} />
          </AsyncSection>
        </Card>
      </div>
    </section>
  )
}

export default Dashboard
