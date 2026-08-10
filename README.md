# 🐾 PawTrack

**Suivi médical pour animaux de compagnie.** Vaccins, antiparasitaires, visites
vétérinaires : PawTrack centralise le carnet de santé et signale ce qui arrive à
échéance.

Application web construite autour d'une API REST Symfony et d'un front React
entièrement découplé.

---

## Fonctionnalités

**Disponibles**

- Authentification par JWT stocké en cookie `httpOnly`
- Gestion multi-animaux (espèce, race, sexe, date de naissance)
- Carnet médical par animal : événements à faire, historique paginé, détection
  des retards
- Soins récurrents configurables (« tous les 3 mois »)
- Cloisonnement des données : un utilisateur ne voit que ses animaux

**Prévues**

- Rappels programmés et notifications push
- Export PDF du carnet de santé
- Partage d'une fiche animal avec un vétérinaire via code temporaire

---

## Stack

| Backend (`api/`) | | Frontend (`app/`) | |
|---|---|---|---|
| PHP | ≥ 8.2 | React | 19 |
| Symfony | 7.4 | Vite | 8 |
| API Platform | 4.3 | React Router | 7 |
| Doctrine ORM | 3.6 | React Hook Form | 7 |
| MySQL | 8.0 | CSS Modules | — |
| LexikJWTAuthenticationBundle | 3.2 | | |

Les deux applications sont indépendantes et ne communiquent qu'en HTTP/JSON.

---

## Architecture

```
pawtrack/
├── api/     Application Symfony — API REST (port 8000)
├── app/     Application React — client web (port 5173)
└── docker/  Images PHP-FPM et configuration Nginx
```

### Modèle de données

```
User ──< Animal ──< MedicalEvent >── MedicalType
              │            └──< Reminder
              ├──< MedicalPlan          (soins récurrents)
              ├──< WeightRecord
              └──> AnimalType, Breed    (référentiel)
```

`Animal` porte un `owner`, et les ressources qui en dépendent héritent de cette
relation pour les contrôles d'accès.

---

## Installation locale

**Prérequis :** Docker et Docker Compose.

```bash
git clone https://github.com/Fr0stFR/PawTrack.git
cd PawTrack
```

Créer `api/.env.local` :

```dotenv
DATABASE_URL="mysql://<user>:<password>@mysql:3306/pawtrack?serverVersion=8.0&charset=utf8mb4"
APP_SECRET=<32 caractères hexadécimaux, ex. `openssl rand -hex 16`>
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=<passphrase de votre choix>
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

Créer `app/.env` :

```dotenv
VITE_API_URL=http://localhost:8000
```

Démarrer et initialiser :

```bash
docker compose up -d
docker compose exec php composer install
docker compose exec php php bin/console lexik:jwt:generate-keypair
docker compose exec php php bin/console doctrine:migrations:migrate
```

| Service | URL |
|---|---|
| Front React | http://localhost:5173 |
| API | http://localhost:8000 |
| Documentation OpenAPI | http://localhost:8000/api/docs |

---

## Licence

Code source consultable, réutilisation non autorisée. Voir [LICENSE](LICENSE).
