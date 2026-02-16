# QCM-Net 🎓 - API Backend

> Une API REST spécialisée pour la gestion dynamique des banques de questions, la validation des réponses en temps réel et le suivi des performances académiques.

## 🛠️ Environnement de Développement

### Versions Requises

| Composant      | Version |
| -------------- | ------- |
| **PostgreSQL** | v18.0   |
| **PHP**        | 8.2.12+ |
| **Composer**   | 2.8.11+ |
| **Laravel**    | 12.32.5 |

## 🚀 Installation et Démarrage

1. **Cloner le Projet**

```bash
git clone https://github.com/Marouankh1/qcm-net-backend.git
cd qcm-net-backend

```

2. **Configuration du Serveur**

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js pour le frontend (si nécessaire)
npm install

# Copier le fichier d'environnement
cp .env.example .env

# Configurer la base de données PostgreSQL dans .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=qcmnetDB
DB_USERNAME=postgres
DB_PASSWORD=VOTRE_MOT_DE_PASSE

# Générer la clé de l'application
php artisan key:generate

# Générer la clé JWT
php artisan jwt:secret

# Exécuter les migrations
php artisan migrate

# Démarrer le serveur de développement
php artisan serve --port=5174

```
