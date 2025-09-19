Booking - Déploiement (Frontend Vercel + Backend Supabase + Fly/Render)

But: ceci est un guide pas-à-pas. Certaines étapes nécessitent d'exécuter des commandes depuis ta machine (accès internet). J'ai automatisé les fichiers Docker + SQL de base.

1) Créer la base Supabase
- Sur https://app.supabase.com -> new project -> nom: Booking
- Récupère la Connection string (Settings -> Database -> Connection string).
- Copie la DATABASE_URL complète.

2) Créer le schéma (exécuter dans Supabase SQL Editor)
- Ouvre Supabase -> SQL Editor
- Colle le contenu de `server/sql/init_schema.sql` et clique Run

3) (Optionnel) Migrer les données MySQL -> Supabase avec pgloader
- Installer pgloader
- Exemple:
  pgloader mysql://bafode:YOUR_MYSQL_PASSWORD@127.0.0.1:3306/booking \
    postgresql://postgres:YOUR_SUPABASE_PASSWORD@db.<REF>.supabase.co:5432/postgres

4) Mettre à jour `server/.env` localement (déjà mis par le repo)
- Vérifie que `DATABASE_URL` contient la connection Supabase
- Vérifie `CORS_ALLOW_ORIGIN` inclut ton domaine Vercel

5) Lancer les migrations (si tu préfères doctrine)
- Depuis server/:
  export DATABASE_URL="postgresql://postgres:YOUR_SUPABASE_PASSWORD@db.<REF>.supabase.co:5432/postgres?sslmode=require&serverVersion=14&charset=utf8"
  composer install --no-interaction
  php bin/console doctrine:migrations:migrate --no-interaction

6) Déployer le backend (Fly.io exemple)
- Installer flyctl
- fly launch --name booking-backend (ou `fly init`)
- fly secrets set DATABASE_URL="postgresql://..." APP_SECRET="..." MAILER_DSN="..."
- fly deploy

7) Déployer le frontend (Vercel)
- Connecte ton repo GitHub sur Vercel
- Root directory: client
- Build command: npm run build
- Output dir: dist
- Env vars: VITE_API_URL=https://<ton-backend-public>

8) Tests E2E
- Ouvre le frontend Vercel -> réserve un créneau -> vérifie dans Supabase
- Teste annulation mail/endpoint

Si tu veux, je peux:
- Générer un commit qui ajoute le Dockerfile et README (déjà fait).
- Te guider pas-à-pas pendant que tu exécutes les commandes (colle la sortie ici).
- Préparer des scripts d'import CSV si besoin.

---

Si tu veux que je fasse des actions supplémentaires automatiquement dans le repo (par ex. ajouter fly workflow, GitHub Actions, etc.), dis‑le et je les ajoute.
