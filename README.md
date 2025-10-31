# Entités Symfony - JessyMax

## 📦 Fichiers créés

### Enums (src/Enum/)
- `PaymentStatus.php` - Statuts de paiement (pending, paid, failed, refunded)
- `RefundStatus.php` - Statuts de remboursement (pending, approved, rejected, processed)
- `CategoryType.php` - Types de catégories (event, formation, both)

### Entités (src/Entity/)
- `User.php` - Utilisateurs avec Symfony Security
- `Event.php` - Événements
- `Formation.php` - Formations
- `Ticket.php` - Billets avec contrainte event XOR formation
- `Location.php` - Lieux avec coordonnées GPS
- `Category.php` - Catégories
- `RefundRequest.php` - Demandes de remboursement
- `ContactMessage.php` - Messages de contact

## 🚀 Prochaines étapes

### 1. Copier les fichiers
```bash
cp -r src/Enum votre-projet-symfony/src/
cp -r src/Entity votre-projet-symfony/src/
```

### 2. Configurer la base de données
Assure-toi que ton `.env` contient :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/myapp?serverVersion=8.0"
```

### 3. Créer la migration
```bash
php bin/console make:migration
```

### 4. Vérifier la migration générée
Ouvre le fichier dans `migrations/` et vérifie que tout est correct.

### 5. Exécuter la migration
```bash
php bin/console doctrine:migrations:migrate
```

## ⚡ Points clés

### Contrainte Ticket (Event XOR Formation)
Le ticket a une validation automatique via `@ORM\PrePersist` qui vérifie qu'il est lié à **soit** un Event **soit** une Formation, jamais les deux ni aucun.

### UUID natifs
Tous les IDs utilisent `Symfony\Component\Uid\Uuid` en v4.

### Relations Doctrine
Les relations bidirectionnelles sont gérées automatiquement :
- Pas besoin de stocker manuellement les arrays d'IDs
- `$event->getTickets()` retourne tous les tickets liés
- `$ticket->getEvent()` retourne l'événement lié

### Timestamps automatiques
- `Event` et `Formation` : `@ORM\PreUpdate` pour mettre à jour `updatedAt`
- Tous : `createdAt` initialisé dans le constructeur

### Enums PHP natifs
Doctrine gère nativement les enums PHP 8.1+ :
```php
$ticket->setPaymentStatus(PaymentStatus::PAID);
```

### API Platform
Toutes les entités sont exposées via API Platform avec les opérations standard (GET, POST, PUT, DELETE).

## 🔧 Optimisations faites

### 1. Pas d'arrays d'IDs redondants
❌ Supprimé : `tickets: UUID[]`, `categories: UUID[]`
✅ Doctrine gère les relations automatiquement

### 2. Types précis
- `decimal(10,2)` pour les prix
- `decimal(10,8)` et `decimal(11,8)` pour GPS
- `DateTimeImmutable` pour l'immutabilité

### 3. Validations Symfony
- `@Assert\Email` pour les emails
- `@Assert\Url` pour les URLs
- `@Assert\PositiveOrZero` pour les montants
- `@Assert\NotBlank` pour les champs requis

### 4. Méthodes helper
- `Ticket::isUsed()` et `markAsUsed()`
- `RefundRequest::markAsProcessed()`
- `Formation::getAvailableTickets()` calculé dynamiquement

## 🎯 Intégration Stripe

Les champs Stripe sont déjà en place :
- `Ticket::stripeCheckoutSessionId`
- `Ticket::stripePaymentIntentId`
- `RefundRequest::stripeRefundId`

**Flow recommandé :**
1. User clique "Acheter" → Créer Ticket avec `status: PENDING`
2. Créer Checkout Session → Stocker `stripeCheckoutSessionId`
3. Webhook `checkout.session.completed` :
   - `setPaymentStatus(PaymentStatus::PAID)`
   - `setPurchasedAt(new \DateTimeImmutable())`
   - `setStripePaymentIntentId($paymentIntent)`
   - Générer et stocker le QR code
   - Décrémenter `Event::availableTickets`

## 🔐 Sécurité

### User entity
Implémente `UserInterface` et `PasswordAuthenticatedUserInterface` pour Symfony Security.

### Password hashing
Configure dans `security.yaml` :
```yaml
security:
    password_hashers:
        App\Entity\User: 'auto'
```

## 📋 TODO

1. Configurer JWT pour l'API (LexikJWTAuthenticationBundle déjà installé)
2. Créer les endpoints custom si besoin (ex: `/tickets/{id}/validate`)
3. Implémenter les webhooks Stripe
4. Ajouter les DataFixtures pour les tests
5. Configurer les voters pour les permissions
6. Ajouter les filtres API Platform (ex: filtrer events par date)
7. Implémenter la génération de QR codes

## 🎨 Bonus

### Relations many-to-many
Les tables pivot sont créées automatiquement :
- `event_category`
- `formation_category`

### Soft delete (optionnel)
Si tu veux du soft delete, installe :
```bash
composer require stof/doctrine-extensions-bundle
```

Bon dev ! 🚀