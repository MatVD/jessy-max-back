# 📚 Index des fichiers - JessyMax Backend

## 📖 Documentation

### 🚀 [README.md](README.md)
Vue d'ensemble du projet, structure des entités, optimisations faites, et points clés.

### ⚡ [QUICKSTART.md](QUICKSTART.md)
Guide de démarrage rapide avec exemples de commandes curl pour tester l'API.

### 🔧 [INSTALLATION.md](INSTALLATION.md)
Guide d'installation complet étape par étape, de la copie des fichiers au test des webhooks.

### 🔄 [MIGRATION_FROM_SUPABASE.md](MIGRATION_FROM_SUPABASE.md)
Guide de migration depuis Supabase vers Symfony (auth, données, frontend).

## 📁 Structure des fichiers

```
outputs/
├── README.md                           # Vue d'ensemble
├── QUICKSTART.md                       # Démarrage rapide
├── INSTALLATION.md                     # Installation détaillée
├── MIGRATION_FROM_SUPABASE.md         # Guide de migration
│
├── src/
│   ├── Enum/                          # Enums PHP 8.1+
│   │   ├── PaymentStatus.php          # pending, paid, failed, refunded
│   │   ├── RefundStatus.php           # pending, approved, rejected, processed
│   │   └── CategoryType.php           # event, formation, both
│   │
│   └── Entity/                        # Entités Doctrine + API Platform
│       ├── User.php                   # Utilisateurs (Symfony Security)
│       ├── Event.php                  # Événements
│       ├── Formation.php              # Formations
│       ├── Ticket.php                 # Billets (event XOR formation)
│       ├── Location.php               # Lieux avec GPS
│       ├── Category.php               # Catégories
│       ├── RefundRequest.php          # Demandes de remboursement
│       └── ContactMessage.php         # Messages de contact
│
└── examples/                          # Exemples d'implémentation
    ├── .env.example                   # Variables d'environnement
    ├── services_config.yaml           # Config services.yaml
    ├── TicketCheckoutProcessor.php    # State Processor Stripe
    ├── StripeWebhookController.php    # Webhook handler Stripe
    ├── QrCodeService.php              # Génération QR codes JWT
    ├── TicketRepository.php           # Repository custom avec queries
    └── TicketValidationController.php # API de validation tickets
```

## 🎯 Fichiers à copier dans ton projet

### Obligatoires
```bash
# Copier les entités et enums
cp -r src/Enum votre-projet/src/
cp -r src/Entity votre-projet/src/
```

### Recommandés
```bash
# Copier les exemples
cp examples/TicketCheckoutProcessor.php votre-projet/src/State/
cp examples/StripeWebhookController.php votre-projet/src/Controller/
cp examples/QrCodeService.php votre-projet/src/Service/
cp examples/TicketRepository.php votre-projet/src/Repository/
cp examples/TicketValidationController.php votre-projet/src/Controller/
```

### Configuration
```bash
# Variables d'environnement
cat examples/.env.example >> votre-projet/.env.local

# Services (ajouter manuellement dans config/services.yaml)
cat examples/services_config.yaml
```

## 📦 Dépendances à installer

```bash
# Stripe
composer require stripe/stripe-php

# JWT pour QR codes
composer require firebase/php-jwt

# QR code images (optionnel)
composer require endroid/qr-code

# Email
composer require symfony/mailer

# CORS
composer require nelmio/cors-bundle
```

## 🗂️ Description des entités

| Entité | Description | Relations |
|--------|-------------|-----------|
| **User** | Utilisateurs avec auth Symfony | → Ticket (OneToMany) |
| **Event** | Événements payants | → Location, Category, Ticket |
| **Formation** | Formations payantes | → Location, Category, Ticket |
| **Ticket** | Billets (event OU formation) | → Event/Formation, User, RefundRequest |
| **Location** | Lieux avec coordonnées GPS | ← Event, Formation |
| **Category** | Catégories (event/formation/both) | ← Event, Formation |
| **RefundRequest** | Demandes de remboursement | → Ticket, User |
| **ContactMessage** | Messages de contact | (aucune) |

## 🔑 Points clés

### Contrainte Ticket
Un ticket doit être lié à **soit** un Event **soit** une Formation, jamais les deux ni aucun.
Validé automatiquement avec `@ORM\PrePersist`.

### UUID natifs
Tous les IDs utilisent `Symfony\Component\Uid\Uuid` v4.

### Enums PHP
Les statuts utilisent les enums PHP 8.1+ natifs :
```php
$ticket->setPaymentStatus(PaymentStatus::PAID);
```

### Relations automatiques
Doctrine gère les relations automatiquement. Pas besoin de stocker manuellement les arrays d'IDs.

### Timestamps automatiques
- `createdAt` : initialisé dans le constructeur
- `updatedAt` : automatique via `@ORM\PreUpdate`

### API Platform
Toutes les entités exposées via REST API avec opérations standard.

## 🎨 Exemples d'utilisation

### State Processor (TicketCheckoutProcessor)
Intercepte la création de tickets et crée automatiquement une Checkout Session Stripe.

### Webhook Controller (StripeWebhookController)
Écoute les événements Stripe et met à jour les tickets :
- `checkout.session.completed` → Marque le ticket comme payé + génère QR code
- `payment_intent.payment_failed` → Marque comme échoué
- `charge.refunded` → Traite le remboursement

### QR Code Service (QrCodeService)
Génère des QR codes sécurisés avec JWT :
- Payload signé avec secret
- Expiration automatique après la date de l'événement
- Validation avec signature

### Repository (TicketRepository)
Méthodes custom pour queries complexes :
- `findPaidByEvent()` - Tous les tickets payés d'un événement
- `findByQrCode()` - Trouver un ticket par son QR code
- `getSalesStatsByMonth()` - Statistiques de ventes

### Validation Controller (TicketValidationController)
API endpoints pour valider les tickets :
- `POST /api/tickets/validate` - Valide et marque comme utilisé
- `POST /api/tickets/check` - Vérifie sans marquer
- `GET /api/tickets/event/{id}/stats` - Stats d'un événement

## 🔐 Sécurité

### Authentication
- JWT tokens via LexikJWTAuthenticationBundle
- User entity implémente `UserInterface` et `PasswordAuthenticatedUserInterface`

### Authorization
- Roles configurables dans User entity (JSON array)
- Access control dans `security.yaml`

### Stripe
- Webhooks signés et vérifiés
- Secrets stockés dans variables d'environnement

### QR Codes
- Signés avec JWT
- Expiration automatique
- Impossible de forger sans le secret

## 🚀 Workflow complet

1. **Utilisateur achète un ticket**
   ```
   POST /api/tickets → TicketCheckoutProcessor
   → Crée Stripe Checkout Session
   → Retourne sessionId au frontend
   ```

2. **Utilisateur paie sur Stripe**
   ```
   Stripe → Webhook /webhook/stripe
   → StripeWebhookController
   → Met à jour ticket (PAID)
   → Génère QR code (JWT signé)
   → Envoie email (optionnel)
   ```

3. **Validation à l'entrée**
   ```
   POST /api/tickets/validate
   → Vérifie signature JWT
   → Vérifie statut payé
   → Vérifie non utilisé
   → Marque comme utilisé
   → Retourne succès
   ```

## 📊 Performances

- **Doctrine cache** : APCu ou Redis pour les queries
- **API Platform cache** : HTTP cache headers automatiques
- **Indexes DB** : Sur les colonnes fréquemment requêtées
- **Pagination** : Automatique via API Platform

## 🧪 Tests

```bash
# Tests unitaires des entités
php bin/phpunit tests/Entity/

# Tests API Platform
php bin/phpunit tests/Api/

# Tests d'intégration Stripe (mode test)
php bin/phpunit tests/Integration/Stripe/
```

## 📈 Monitoring

- **Logs Symfony** : `var/log/dev.log`
- **Stripe Dashboard** : Webhooks delivery status
- **API Platform** : Built-in metrics via Mercure

## 🎓 Ressources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform Documentation](https://api-platform.com/docs/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Stripe PHP Documentation](https://stripe.com/docs/api?lang=php)
- [JWT Documentation](https://jwt.io/)

## 💡 Tips

1. **Développement** : Utilise `symfony server:start -d` pour hot reload
2. **Debugging** : Activer le Symfony Profiler en dev
3. **API Testing** : Utilise [Postman](https://www.postman.com/) ou [Insomnia](https://insomnia.rest/)
4. **Stripe Testing** : Utilise `stripe listen` pour tester les webhooks localement
5. **Database** : phpMyAdmin déjà configuré dans docker-compose.yaml

## 🆘 Problèmes courants

**Erreur de migration** : Supprime la DB et recommence
```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**Webhook non reçu** : Vérifie que Stripe CLI est lancé et le secret est correct

**CORS error** : Configure nelmio_cors.yaml avec l'origine du frontend

**Token JWT invalide** : Régénère les clés JWT
```bash
php bin/console lexik:jwt:generate-keypair
```

---

Tout est prêt pour ta migration ! 🎉

Si tu as des questions, consulte d'abord les fichiers de documentation, puis n'hésite pas à demander.

Bon dev ! 💪