import { Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import ProtectedRoute from './components/ProtectedRoute'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import AnimalDetail from './pages/AnimalDetail'
import NewAnimal from './pages/NewAnimal'

/**
 * Table de routage de l'application.
 */
function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path="/login" element={<Login />} />

        <Route element={<ProtectedRoute />}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/animals/new" element={<NewAnimal />} />
          <Route path="/animals/:id" element={<AnimalDetail />} />
        </Route>

        <Route path="/" element={<Navigate to="/dashboard" replace />} />
      </Route>
    </Routes>
  )
}

export default App
