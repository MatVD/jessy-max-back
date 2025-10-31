# 🚀 Installation complète - Étape par étape

## 1️⃣ Prérequis
```bash
# PHP 8.2+
php -v

# Composer
composer -V

# Symfony CLI (optionnel mais recommandé)
symfony -V
```

## 2️⃣ Copier les fichiers dans ton projet Symfony

```bash
# Depuis le dossier outputs/
cd outputs

# Copier les entités et enums
cp -r src/Enum ../votre-projet/src/
cp -r src/Entity ../votre-projet/src/

# Copier les exemples
mkdir -p ../votre-projet/src/State
mkdir -p ../votre-projet/src/Service
mkdir -p ../votre-projet/src/Repository

cp examples/TicketCheckoutProcessor.php ../votre-projet/src/State/
cp examples/StripeWebhookController.php ../votre-projet/src/Controller/
cp examples/QrCodeService.php ../votre-projet/src/Service/
cp examples/TicketRepository.php ../votre-projet/src/Repository/
cp examples/TicketValidationController.php ../votre-projet/src/Controller/
```

## 3️⃣ Installer les dépendances

```bash
cd ../votre-projet

# Stripe SDK
composer require stripe/stripe-php

# JWT pour les QR codes sécurisés
composer require firebase/php-jwt

# QR code generator (optionnel, pour les images)
composer require endroid/qr-code

# Mailer (pour envoyer les tickets par email)
composer require symfony/mailer

# CORS (si frontend séparé)
composer require nelmio/cors-bundle
```

## 4️⃣ Configurer les variables d'environnement

```bash
# Copier le fichier .env.example
cp .env .env.local

# Éditer .env.local avec tes vraies valeurs
nano .env.local
```

Ajoute ces lignes dans `.env.local` :
```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/jessymax?serverVersion=8.0&charset=utf8mb4"

# Stripe
STRIPE_SECRET_KEY="sk_test_your_key_here"
STRIPE_PUBLISHABLE_KEY="pk_test_your_key_here"
STRIPE_WEBHOOK_SECRET="whsec_your_webhook_secret_here"

# Frontend URL
FRONTEND_URL="http://localhost:3000"

# JWT pour QR codes
JWT_QR_SECRET="your-super-secret-key-for-qr-codes"
```

## 5️⃣ Configurer les services

Ouvre `config/services.yaml` et ajoute :

```yaml
parameters:
    stripe_secret_key: '%env(STRIPE_SECRET_KEY)%'
    stripe_webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'
    frontend_url: '%env(FRONTEND_URL)%'
    jwt_qr_secret: '%env(JWT_QR_SECRET)%'

services:
    # ... (garde le contenu existant)

    # State Processor pour Stripe Checkout
    App\State\TicketCheckoutProcessor:
        decorates: 'api_platform.doctrine.orm.state.persist_processor'
        arguments:
            $decorated: '@.inner'
            $stripeSecretKey: '%stripe_secret_key%'
            $frontendUrl: '%frontend_url%'

    # Webhook Controller
    App\Controller\StripeWebhookController:
        arguments:
            $stripeSecretKey: '%stripe_secret_key%'
            $stripeWebhookSecret: '%stripe_webhook_secret%'
        tags: ['controller.service_arguments']

    # QR Code Service
    App\Service\QrCodeService:
        arguments:
            $jwtSecret: '%jwt_qr_secret%'

    # Repository
    App\Repository\TicketRepository:
        factory: ['@doctrine', 'getRepository']
        arguments:
            - 'App\Entity\Ticket'
```

## 6️⃣ Créer la base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Créer la migration
php bin/console make:migration

# Vérifier le fichier de migration généré dans migrations/
# Puis exécuter
php bin/console doctrine:migrations:migrate
```

## 7️⃣ Configurer Stripe Webhooks (développement local)

```bash
# Installer Stripe CLI
brew install stripe/stripe-cli/stripe  # macOS
# ou télécharger depuis https://stripe.com/docs/stripe-cli

# Se connecter à Stripe
stripe login

# Écouter les webhooks en local
stripe listen --forward-to localhost:8000/webhook/stripe

# ⚠️ IMPORTANT : Copie le webhook secret qui s'affiche
# Il ressemble à : whsec_xxxxxxxxxxxxx
# Et mets-le dans .env.local : STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

## 8️⃣ Lancer le serveur

```bash
# Avec Symfony CLI (recommandé)
symfony server:start -d

# Ou avec PHP built-in server
php -S localhost:8000 -t public/

# Vérifier que ça fonctionne
curl http://localhost:8000/api
```

## 9️⃣ Tester l'API

### Créer une location
```bash
curl -X POST http://localhost:8000/api/locations \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Palais des Congrès",
    "address": "2 Place de la Porte Maillot, 75017 Paris",
    "latitude": "48.8783",
    "longitude": "2.2828"
  }'
```

### Créer une catégorie
```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tech Conference",
    "type": "event"
  }'
```

### Créer un événement
```bash
curl -X POST http://localhost:8000/api/events \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Symfony World 2025",
    "description": "La plus grande conférence Symfony",
    "date": "2025-12-15T09:00:00+00:00",
    "location": "/api/locations/1",
    "imageUrl": "https://example.com/event.jpg",
    "price": "99.99",
    "availableTickets": 500,
    "totalTickets": 500,
    "categories": ["/api/categories/1"]
  }'
```

### Acheter un ticket (déclenche Stripe Checkout)
```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "event": "/api/events/1",
    "customerName": "John Doe",
    "customerEmail": "john@example.com",
    "totalPrice": "99.99"
  }'

# La réponse contiendra stripeCheckoutSessionId
# Utilise cet ID pour rediriger vers Stripe Checkout
```

## 🔟 Tester le webhook Stripe

```bash
# Dans un terminal, lance l'écoute des webhooks
stripe listen --forward-to localhost:8000/webhook/stripe

# Dans un autre terminal, déclenche un événement de test
stripe trigger checkout.session.completed

# Vérifie les logs pour voir si le webhook a bien été reçu
```

## 1️⃣1️⃣ Valider un ticket

### Vérifier un QR code
```bash
curl -X POST http://localhost:8000/api/tickets/check \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }'
```

### Valider et marquer comme utilisé
```bash
curl -X POST http://localhost:8000/api/tickets/validate \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }'
```

## 📊 Commandes utiles

```bash
# Voir toutes les routes API
php bin/console debug:router | grep api

# Vider le cache
php bin/console cache:clear

# Voir les logs Symfony
symfony server:log

# Voir les logs en temps réel
tail -f var/log/dev.log

# Créer une nouvelle migration après modification d'entités
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Rollback d'une migration
php bin/console doctrine:migrations:migrate prev

# Fixtures (si tu les crées)
php bin/console doctrine:fixtures:load --no-interaction
```

## 🎯 Prochaines étapes

1. **Authentification JWT**
   ```bash
   # Les clés JWT sont déjà configurées pour LexikJWTAuthenticationBundle
   php bin/console lexik:jwt:generate-keypair
   ```

2. **Envoi d'emails**
   - Configure MAILER_DSN dans .env
   - Crée un service pour envoyer le ticket par email après paiement

3. **Tests**
   ```bash
   # Installer PHPUnit
   composer require --dev symfony/test-pack
   
   # Lancer les tests
   php bin/phpunit
   ```

4. **Production**
   - Configure APP_ENV=prod dans .env
   - Configure le vrai webhook endpoint Stripe
   - Active le cache Redis/APCu
   - Configure le rate limiting

## 🆘 Debugging

### Si erreur de migration
```bash
# Supprimer la base et recommencer
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Si erreur de cache
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Si webhook Stripe ne fonctionne pas
- Vérifie que Stripe CLI est bien lancé
- Vérifie que le STRIPE_WEBHOOK_SECRET est correct
- Vérifie les logs : `symfony server:log`

Tout est prêt ! 🎉