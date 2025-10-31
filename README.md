# JessyMax Backend - Documentation complète

---

## 📚 Index rapide

-   Vue d'ensemble, installation, structure, commandes, API, sécurité, Stripe, QR codes, tests, monitoring, troubleshooting.

---

## 1️⃣ Installation complète

### Prérequis

```bash
php -v         # PHP 8.2+
composer -V    # Composer
symfony -V     # Symfony CLI (optionnel)
```

### Copier les fichiers

```bash
cp -r src/Enum votre-projet/src/
cp -r src/Entity votre-projet/src/
cp examples/TicketCheckoutProcessor.php votre-projet/src/State/
cp examples/StripeWebhookController.php votre-projet/src/Controller/
cp examples/QrCodeService.php votre-projet/src/Service/
cp examples/TicketRepository.php votre-projet/src/Repository/
cp examples/TicketValidationController.php votre-projet/src/Controller/
```

### Installer les dépendances

```bash
composer require stripe/stripe-php
composer require firebase/php-jwt
composer require endroid/qr-code
composer require symfony/mailer
composer require nelmio/cors-bundle
```

### Configurer les variables d'environnement

```bash
cp .env .env.local
nano .env.local
```

Ajoute :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/jessymax?serverVersion=8.0&charset=utf8mb4"
STRIPE_SECRET_KEY="sk_test_your_key_here"
STRIPE_PUBLISHABLE_KEY="pk_test_your_key_here"
STRIPE_WEBHOOK_SECRET="whsec_your_webhook_secret_here"
FRONTEND_URL="http://localhost:3000"
JWT_QR_SECRET="your-super-secret-key-for-qr-codes"
```

### Configurer les services

Dans `config/services.yaml` :

```yaml
parameters:
    stripe_secret_key: "%env(STRIPE_SECRET_KEY)%"
    stripe_webhook_secret: "%env(STRIPE_WEBHOOK_SECRET)%"
    frontend_url: "%env(FRONTEND_URL)%"
    jwt_qr_secret: "%env(JWT_QR_SECRET)%"

services:
    # ... (garde le contenu existant)

    App\State\TicketCheckoutProcessor:
        decorates: "api_platform.doctrine.orm.state.persist_processor"
        arguments:
            $decorated: "@.inner"
            $stripeSecretKey: "%stripe_secret_key%"
            $frontendUrl: "%frontend_url%"

    App\Controller\StripeWebhookController:
        arguments:
            $stripeSecretKey: "%stripe_secret_key%"
            $stripeWebhookSecret: "%stripe_webhook_secret%"
        tags: ["controller.service_arguments"]

    App\Service\QrCodeService:
        arguments:
            $jwtSecret: "%jwt_qr_secret%"

    App\Repository\TicketRepository:
        factory: ["@doctrine", "getRepository"]
        arguments:
            - 'App\Entity\Ticket'
```

### Créer la base de données et les migrations

```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Configurer Stripe Webhooks (local)

```bash
brew install stripe/stripe-cli/stripe
stripe login
stripe listen --forward-to localhost:8000/webhook/stripe
# Copie le webhook secret affiché dans .env.local
```

### Lancer le serveur

```bash
symfony server:start -d
# ou
php -S localhost:8000 -t public/
```

### Commandes utiles

```bash
php bin/console debug:router | grep api
php bin/console cache:clear
symfony server:log
tail -f var/log/dev.log
php bin/console make:migration
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:migrate prev
php bin/console doctrine:fixtures:load --no-interaction
```

---

## 2️⃣ Structure du projet

```
src/
├── Enum/ (PaymentStatus, RefundStatus, CategoryType)
├── Entity/ (User, Event, Formation, Ticket, Location, Category, RefundRequest, ContactMessage)
├── Controller/ (StripeWebhookController, TicketValidationController)
├── State/ (TicketCheckoutProcessor)
├── Service/ (QrCodeService)
├── Repository/ (TicketRepository)
```

---

## 3️⃣ Points clés & Architecture

-   **Ticket** : lié à soit Event soit Formation (XOR), validé par `@ORM\PrePersist`
-   **UUID natifs** : tous les IDs sont des UUID v4
-   **Enums PHP 8.1+** : statuts gérés nativement
-   **Relations Doctrine** : bidirectionnelles, pas d'arrays d'IDs manuels
-   **Timestamps** : `createdAt` dans le constructeur, `updatedAt` via `@ORM\PreUpdate`
-   **API Platform** : toutes les entités exposées en REST
-   **Validations Symfony** : emails, URLs, montants, champs requis
-   **Méthodes helper** : ex. `Ticket::isUsed()`, `RefundRequest::markAsProcessed()`
-   **Many-to-many** : tables pivot auto (event_category, formation_category)
-   **Soft delete (optionnel)** : `composer require stof/doctrine-extensions-bundle`

---

## 4️⃣ Stripe & QR Codes

-   Champs Stripe déjà présents :
    -   `Ticket::stripeCheckoutSessionId`
    -   `Ticket::stripePaymentIntentId`
    -   `RefundRequest::stripeRefundId`
-   Flow recommandé :
    1. Création Ticket → Stripe Checkout Session
    2. Webhook Stripe → met à jour le ticket (PAID, QR code, etc.)
    3. Validation QR code à l'entrée
-   QR codes JWT : signés, expirent après l'événement, validation par signature

---

## 5️⃣ Sécurité

-   **User** : implémente `UserInterface` et `PasswordAuthenticatedUserInterface`
-   **Password hashing** :
    ```yaml
    security:
        password_hashers:
            App\Entity\User: "auto"
    ```
-   **JWT** : LexikJWTAuthenticationBundle, clés générées avec `php bin/console lexik:jwt:generate-keypair`
-   **Voters** : pour permissions avancées
-   **CORS** : configure nelmio_cors.yaml
-   **Stripe** : webhooks signés, secrets en env
-   **QR Codes** : signés, expiration auto

---

## 6️⃣ API & Endpoints

-   Toutes les entités exposées via API Platform (GET, POST, PUT, DELETE)
-   Endpoints custom :
    -   `/tickets/{id}/validate` (validation QR)
    -   `/tickets/check` (vérification QR)
    -   `/tickets/event/{id}/stats` (stats événement)

---

## 7️⃣ Exemples d'utilisation

### Créer une Location

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

### Créer une Category

```bash
curl -X POST http://localhost:8000/api/categories \
    -H "Content-Type: application/json" \
    -d '{
        "name": "Tech Conference",
        "type": "event"
    }'
```

### Créer un Event

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

### Acheter un ticket (Stripe Checkout)

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
```

### Webhooks Stripe (local)

```bash
stripe listen --forward-to localhost:8000/webhook/stripe
stripe trigger checkout.session.completed
```

### Vérifier/valider un QR code

```bash
curl -X POST http://localhost:8000/api/tickets/check \
    -H "Content-Type: application/json" \
    -d '{ "qrCode": "eyJ0eXAiOiJKV1QiLCJhbGc..." }'

curl -X POST http://localhost:8000/api/tickets/validate \
    -H "Content-Type: application/json" \
    -d '{ "qrCode": "eyJ0eXAiOiJKV1QiLCJhbGc..." }'
```

---

## 8️⃣ Tests & Monitoring

### Tests

```bash
php bin/phpunit tests/Entity/
php bin/phpunit tests/Api/
php bin/phpunit tests/Integration/Stripe/
```

### Monitoring

-   Logs Symfony : `var/log/dev.log`
-   Stripe Dashboard : webhooks delivery status
-   API Platform : metrics via Mercure

---

## 9️⃣ Développement, Debug & Tips

-   `symfony server:start -d` pour hot reload
-   Activer le Symfony Profiler en dev
-   Tester l'API avec Postman ou Insomnia
-   Stripe : `stripe listen` pour webhooks
-   DB : phpMyAdmin via docker-compose.yaml

### Problèmes courants

-   **Erreur migration** :
    ```bash
    php bin/console doctrine:database:drop --force
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```
-   **Webhook non reçu** : Stripe CLI lancé, secret correct
-   **CORS error** : configure nelmio_cors.yaml
-   **Token JWT invalide** :
    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```

---

## 🔗 Ressources

-   [Symfony Documentation](https://symfony.com/doc/current/index.html)
-   [API Platform Documentation](https://api-platform.com/docs/)
-   [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
-   [Stripe PHP Documentation](https://stripe.com/docs/api?lang=php)
-   [JWT Documentation](https://jwt.io/)

---

## 📋 TODO & prochaines étapes

1. Configurer JWT pour l'API
2. Créer endpoints custom (validation ticket, stats, etc.)
3. Implémenter les webhooks Stripe
4. Ajouter DataFixtures pour les tests
5. Configurer les voters pour permissions
6. Ajouter filtres API Platform
7. Générer QR codes
8. Configurer email (confirmation, ticket)
9. Tests unitaires et API
10. Monitoring et alertes

---

Bon développement ! 🚀
