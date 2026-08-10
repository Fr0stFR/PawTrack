import { useNavigate, Link } from 'react-router-dom'
import Card from '@/components/ui/Card'
import AnimalForm from '@/features/animals/AnimalForm'
import Icon from '@/components/ui/Icon'
import styles from './NewAnimal.module.css'

/**
 * Page de création d'un animal : elle assure la navigation, AnimalForm porte
 * la saisie et l'envoi.
 */
function NewAnimal() {
  const navigate = useNavigate()

  return (
    <section className={styles.page}>
      <Link to="/dashboard" className={styles.back}>← Retour au tableau de bord</Link>

      <header className={styles.header}>
        <span className={styles.avatar}><Icon name="paw" /></span>
        <h1>Nouvel animal</h1>
      </header>

      <Card>
        <AnimalForm
          // replace : le formulaire soumis est retiré de l'historique, pour
          // qu'un retour arrière ne ramène pas sur un envoi déjà effectué.
          onSuccess={(animal) => navigate(`/animals/${animal.id}`, { replace: true })}
        />
      </Card>
    </section>
  )
}

export default NewAnimal
