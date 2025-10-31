# Migration de Supabase vers Symfony 🔄

## Changements d'architecture

### Avant (Supabase)
```
Frontend → Supabase (auth + database + storage)
          ↓
       Stripe webhooks → Supabase Edge Functions
```

### Après (Symfony)
```
Frontend → Symfony API (REST API Platform)
          ↓
       MySQL + Stripe webhooks → Symfony
```

## 🔐 Authentification

### Supabase Auth → Symfony JWT

**Avant (Supabase)**
```typescript
// Frontend
import { createClient } from '@supabase/supabase-js'

const supabase = createClient(url, key)
await supabase.auth.signUp({ email, password })
await supabase.auth.signIn({ email, password })
```

**Après (Symfony JWT)**
```typescript
// Frontend
// 1. Register
await fetch('http://localhost:8000/api/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ 
    email, 
    password, 
    firstname, 
    lastname 
  })
})

// 2. Login
const response = await fetch('http://localhost:8000/api/login_check', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: email, password })
})
const { token } = await response.json()

// 3. Utiliser le token
await fetch('http://localhost:8000/api/tickets', {
  headers: { 
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
```

### Configuration Symfony pour l'auth

Crée `src/Controller/AuthController.php` :
```php
<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/api/register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );
        $user->setRoles(['ROLE_USER']);
        
        $em->persist($user);
        $em->flush();
        
        return $this->json(['message' => 'User created'], 201);
    }
}
```

Configure `config/packages/security.yaml` :
```yaml
security:
    password_hashers:
        App\Entity\User: 'auto'
    
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        
        register:
            pattern: ^/api/register
            stateless: true
            
        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login_check
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
    
    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/events, roles: PUBLIC_ACCESS, methods: [GET] }
        - { path: ^/api/formations, roles: PUBLIC_ACCESS, methods: [GET] }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

## 🗄️ Données

### Supabase Queries → Symfony API

**Avant (Supabase)**
```typescript
// Lister les events
const { data } = await supabase
  .from('events')
  .select('*')
  .gte('date', new Date().toISOString())
  .order('date')

// Créer un ticket
const { data, error } = await supabase
  .from('tickets')
  .insert({
    event_id: eventId,
    user_id: userId,
    // ...
  })
```

**Après (Symfony API Platform)**
```typescript
// Lister les events (avec filtres API Platform)
const response = await fetch('http://localhost:8000/api/events?date[gte]=2025-01-01')
const events = await response.json()

// Créer un ticket
const response = await fetch('http://localhost:8000/api/tickets', {
  method: 'POST',
  headers: { 
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}` 
  },
  body: JSON.stringify({
    event: '/api/events/123',
    customerName: 'John Doe',
    customerEmail: 'john@example.com',
    totalPrice: '99.99'
  })
})
```

## 🌐 CORS Configuration

Configure `config/packages/nelmio_cors.yaml` :
```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'OPTIONS', 'PATCH']
            max_age: 3600
```

Ajoute dans `.env` :
```env
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## 📦 Paiements Stripe

### Supabase Edge Functions → Symfony Webhooks

**Avant (Supabase Edge Function)**
```typescript
// supabase/functions/stripe-webhook/index.ts
import { serve } from 'https://deno.land/std/http/server.ts'
import Stripe from 'https://esm.sh/stripe'

serve(async (req) => {
  const stripe = new Stripe(Deno.env.get('STRIPE_SECRET_KEY'))
  const signature = req.headers.get('stripe-signature')
  // ...
})
```

**Après (Symfony)**
Déjà implémenté dans `StripeWebhookController.php` ! ✅

## 🔄 Migration des données

### Script de migration Supabase → MySQL

```typescript
// migrate-data.ts
import { createClient } from '@supabase/supabase-js'
import axios from 'axios'

const supabase = createClient(SUPABASE_URL, SUPABASE_KEY)
const SYMFONY_API = 'http://localhost:8000/api'

// 1. Migrer les utilisateurs
const { data: users } = await supabase.from('users').select('*')
for (const user of users) {
  await axios.post(`${SYMFONY_API}/users`, {
    email: user.email,
    firstname: user.firstname,
    lastname: user.lastname,
    // Note: les passwords devront être réinitialisés
  })
}

// 2. Migrer les événements
const { data: events } = await supabase.from('events').select('*')
for (const event of events) {
  await axios.post(`${SYMFONY_API}/events`, {
    title: event.title,
    description: event.description,
    date: event.date,
    price: event.price,
    // ...
  })
}

// 3. Migrer les tickets
const { data: tickets } = await supabase.from('tickets').select('*')
for (const ticket of tickets) {
  await axios.post(`${SYMFONY_API}/tickets`, {
    event: `/api/events/${ticket.event_id}`,
    customerName: ticket.customer_name,
    customerEmail: ticket.customer_email,
    totalPrice: ticket.total_price,
    paymentStatus: ticket.payment_status,
    // ...
  })
}
```

## 🎨 Frontend - Changements à faire

### 1. Remplacer Supabase client
```bash
npm uninstall @supabase/supabase-js
```

### 2. Créer un API client
```typescript
// src/lib/api.ts
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

class ApiClient {
  private token: string | null = null

  setToken(token: string) {
    this.token = token
    localStorage.setItem('token', token)
  }

  getToken() {
    if (!this.token) {
      this.token = localStorage.getItem('token')
    }
    return this.token
  }

  async fetch(endpoint: string, options: RequestInit = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
      ...options.headers,
    }

    const response = await fetch(`${API_URL}${endpoint}`, {
      ...options,
      headers,
    })

    if (!response.ok) {
      throw new Error(`API error: ${response.status}`)
    }

    return response.json()
  }

  // Events
  async getEvents() {
    return this.fetch('/events')
  }

  async getEvent(id: string) {
    return this.fetch(`/events/${id}`)
  }

  // Tickets
  async createTicket(data: any) {
    return this.fetch('/tickets', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }

  // Auth
  async login(email: string, password: string) {
    const response = await this.fetch('/login_check', {
      method: 'POST',
      body: JSON.stringify({ username: email, password }),
    })
    this.setToken(response.token)
    return response
  }

  async register(data: any) {
    return this.fetch('/register', {
      method: 'POST',
      body: JSON.stringify(data),
    })
  }
}

export const api = new ApiClient()
```

### 3. Utiliser le nouveau client
```typescript
// Avant
const { data: events } = await supabase.from('events').select('*')

// Après
const events = await api.getEvents()
```

## ✅ Checklist de migration

- [ ] Backend Symfony installé et configuré
- [ ] Base de données MySQL créée et migrée
- [ ] Stripe configuré (clés + webhooks)
- [ ] JWT configuré pour l'authentification
- [ ] CORS configuré pour le frontend
- [ ] Données migrées depuis Supabase
- [ ] Frontend mis à jour (supprimer Supabase client)
- [ ] API client créé dans le frontend
- [ ] Authentification testée
- [ ] Paiements testés
- [ ] Webhooks Stripe testés
- [ ] Variables d'environnement en production configurées
- [ ] Tests E2E passés

## 🚀 Avantages de Symfony vs Supabase

✅ **Contrôle total** sur la logique métier
✅ **Performance** optimisée avec doctrine cache
✅ **Extensibilité** infinie avec bundles Symfony
✅ **Pas de vendor lock-in**
✅ **Coûts** plus prévisibles (pas de surprise de facturation)
✅ **Type-safety** avec PHP strict types
✅ **Debugging** plus facile avec Symfony profiler
✅ **Tests** plus robustes avec PHPUnit

## 📞 Support

Si tu as des questions pendant la migration, n'hésite pas !