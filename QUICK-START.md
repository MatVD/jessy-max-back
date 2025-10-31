# Guide de démarrage rapide 🚀

## Installation

### 1. Copier les fichiers
```bash
# Depuis le dossier outputs/
cp -r src/Enum votre-projet/src/
cp -r src/Entity votre-projet/src/
cp examples/TicketCheckoutProcessor.php votre-projet/src/State/
cp examples/StripeWebhookController.php votre-projet/src/Controller/
```

### 2. Installer Stripe PHP SDK
```bash
composer require stripe/stripe-php
```

### 3. Configurer les variables d'environnement
```bash
# Copier le fichier exemple
cp examples/.env.example .env

# Éditer .env avec tes vraies clés Stripe
nano .env
```

### 4. Ajouter la configuration des services
Ouvre `config/services.yaml` et ajoute le contenu de `examples/services_config.yaml`

### 5. Créer et exécuter la migration
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 6. Générer les clés JWT (si tu utilises l'authentification)
```bash
php bin/console lexik:jwt:generate-keypair
```

## Test rapide

### 1. Créer une Location
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

### 2. Créer une Category
```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Conférence Tech",
    "type": "event"
  }'
```

### 3. Créer un Event
```bash
curl -X POST http://localhost:8000/api/events \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Symfony World 2025",
    "description": "La plus grande conférence Symfony de l année",
    "date": "2025-12-15T09:00:00+00:00",
    "location": "/api/locations/{id}",
    "imageUrl": "https://example.com/event.jpg",
    "price": "99.99",
    "availableTickets": 500,
    "totalTickets": 500,
    "categories": ["/api/categories/{id}"]
  }'
```

### 4. Créer un Ticket (lance automatiquement Stripe Checkout)
```bash
curl -X POST http://localhost:8000/api/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "event": "/api/events/{id}",
    "customerName": "John Doe",
    "customerEmail": "john@example.com",
    "totalPrice": "99.99"
  }'
```

La réponse contiendra le `stripeCheckoutSessionId` à utiliser pour rediriger vers Stripe.

## Webhooks Stripe

### Configuration en local avec Stripe CLI
```bash
# Installer Stripe CLI
brew install stripe/stripe-cli/stripe

# Se connecter
stripe login

# Écouter les webhooks en local
stripe listen --forward-to localhost:8000/webhook/stripe

# Récupérer le webhook secret
# stripe listen affichera quelque chose comme:
# > Ready! Your webhook signing secret is whsec_xxxxx
# Copie ce secret dans .env : STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

### Test du webhook
```bash
# Simuler un événement checkout.session.completed
stripe trigger checkout.session.completed
```

## Frontend - Redirection vers Stripe

### React/TypeScript exemple
```typescript
// 1. Créer le ticket
const response = await fetch('http://localhost:8000/api/tickets', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    event: '/api/events/123',
    customerName: 'John Doe',
    customerEmail: 'john@example.com',
    totalPrice: '99.99'
  })
});

const ticket = await response.json();

// 2. Rediriger vers Stripe Checkout
const stripe = await loadStripe(process.env.STRIPE_PUBLISHABLE_KEY);
await stripe.redirectToCheckout({
  sessionId: ticket.stripeCheckoutSessionId
});
```

## Commandes utiles

```bash
# Voir les routes API Platform
php bin/console debug:router | grep api

# Créer un utilisateur admin
php bin/console app:create-admin

# Vider le cache
php bin/console cache:clear

# Fixtures (si configurées)
php bin/console doctrine:fixtures:load

# Voir la structure SQL générée
php bin/console doctrine:schema:update --dump-sql

# Lancer le serveur Symfony
symfony server:start -d

# Voir les logs en temps réel
symfony server:log
```

## Architecture recommandée

```
src/
├── Controller/
│   └── StripeWebhookController.php
├── Entity/
│   ├── Category.php
│   ├── ContactMessage.php
│   ├── Event.php
│   ├── Formation.php
│   ├── Location.php
│   ├── RefundRequest.php
│   ├── Ticket.php
│   └── User.php
├── Enum/
│   ├── CategoryType.php
│   ├── PaymentStatus.php
│   └── RefundStatus.php
├── Repository/
│   ├── TicketRepository.php
│   └── ...
├── State/
│   └── TicketCheckoutProcessor.php
├── Service/
│   ├── QrCodeGenerator.php (à créer)
│   ├── EmailService.php (à créer)
│   └── StripeService.php (à créer)
└── EventListener/
    └── TicketPurchaseListener.php (optionnel)
```

## Prochaines étapes recommandées

1. **Sécurité**
   - Configurer JWT authentication
   - Ajouter des Voters pour les permissions
   - Sécuriser les endpoints sensibles

2. **Email**
   - Installer symfony/mailer
   - Configurer l'envoi d'emails de confirmation
   - Envoyer le QR code par email

3. **QR Code**
   - Installer endroid/qr-code
   - Générer des QR codes signés (JWT)
   - Créer endpoint de validation

4. **Tests**
   - Tests unitaires des entités
   - Tests API Platform
   - Tests d'intégration Stripe (mode test)

5. **Performance**
   - Configurer le cache APCu/Redis
   - Ajouter des indexes sur les colonnes fréquemment requêtées
   - Paginer les collections API Platform

6. **Monitoring**
   - Logger les transactions Stripe
   - Monitoring des webhooks ratés
   - Alertes email pour les erreurs

Bon développement ! 🎉