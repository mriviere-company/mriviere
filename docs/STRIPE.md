# Configuration Stripe — pattern acompte + abonnement (Canada)

> Le projet utilise Stripe Checkout en mode `subscription` avec un acompte
> 50 % ajouté à la première facture comme `InvoiceItem`. Compte Stripe **Canada**,
> devise **CAD** partout.

---

## 1. Vue d'ensemble

Pour chaque commande client :

1. Le client soumet `/api/quotes` avec un `Quote` choisi (STARTER/STANDARD/PREMIUM).
2. Côté serveur, on crée un **Stripe Customer** (implicite via Checkout) puis une **Stripe Checkout Session** en mode `subscription` avec uniquement le `Price` mensuel correspondant.
3. Le client paie son premier mois via la Checkout Session.
4. Le webhook `checkout.session.completed` détecte le paiement réussi, lit `metadata.deposit_cents` et appelle `addDepositInvoiceItem()` qui crée un **InvoiceItem** (currency `cad`) lié au customer + à la subscription.
5. Stripe ajoute automatiquement cet `InvoiceItem` à la **première facture** de la subscription (qui n'est pas encore finalisée à ce moment).
6. La première facture devient donc : `[acompte 50 % création]` + `[premier mois récurrent]`. Les factures suivantes ne contiennent que le récurrent.

Référence Stripe officielle : « Subscriptions with one-time setup fees » (`https://stripe.com/docs/billing/subscriptions/setup-fees`).

---

## 2. Configuration côté Stripe Dashboard

### 2.1 Compte

Le compte Stripe doit être configuré pour le **Canada** (`Account country: Canada`). Sinon les Prices CAD ne pourront pas être créés correctement et les paiements seront convertis en USD.

### 2.2 Prices à créer

3 produits, chacun avec un seul Price récurrent mensuel :

| Produit | Price | Devise | Type | Variable env |
|---|---|---|---|---|
| **mriviere — Forfait STARTER** | 29,00 | CAD | Recurring monthly | `STRIPE_PRICE_STARTER` |
| **mriviere — Forfait STANDARD** | 49,00 | CAD | Recurring monthly | `STRIPE_PRICE_STANDARD` |
| **mriviere — Forfait PREMIUM** | 79,00 | CAD | Recurring monthly | `STRIPE_PRICE_PREMIUM` |

Paramètres exacts pour chaque Price :
- **Pricing model** : Standard pricing
- **Type** : Recurring
- **Billing period** : Monthly
- **Tax behavior** : Exclusive (laisse par défaut). **Ne pas activer Stripe Tax automatique** — Matthieu est petit fournisseur, pas de TPS/TVQ à percevoir.
- **Trial period** : aucun

⚠ Les 3 Price IDs (`price_xxxxxxxxxxxxxxx`) sont injectés dans **deux endroits** :
1. `.env.local` → `STRIPE_PRICE_STARTER` etc.
2. La table `packages` en BDD via les fixtures (qui lisent `$_ENV['STRIPE_PRICE_*']` au load)

Pour qu'un Price ID soit pris en compte par les forfaits existants, il faut soit recharger les fixtures (`./start-local.sh --reset-db` en local), soit faire un `UPDATE packages SET stripe_monthly_price_id = '...' WHERE slug = 'starter'`.

### 2.3 Acomptes : pas de Price à créer

Les acomptes 50 % ne sont **pas** des Prices Stripe. Ils sont calculés côté serveur :

| Forfait | Création totale | Acompte |
|---|---|---|
| STARTER | 399 $ CAD | **199,50 $** |
| STANDARD | 599 $ CAD | **299,50 $** |
| PREMIUM | 899 $ CAD | **449,50 $** |

L'acompte est ajouté à la première facture comme `InvoiceItem` à la volée par `StripeService::addDepositInvoiceItem()` avec la description « Acompte 50 % — création STARTER » (ou autre nom de forfait).

---

## 3. Webhook

### 3.1 Endpoint à configurer

Dashboard Stripe → **Developers → Webhooks → Add endpoint** :

- **Endpoint URL** : `https://www.rivierematthieu.com/stripe/webhook`
- **Events** :
  - `checkout.session.completed` — déclenche la création de l'`InvoiceItem` acompte + marque le devis `deposit_paid`
  - `customer.subscription.created` — lie le `subscription_id` au devis et passe en `active`
  - `invoice.payment_failed` — log seulement (pour le moment)

### 3.2 Signing secret

Stripe affiche un `whsec_…` lors de la création du webhook. **Le copier immédiatement** (il ne sera plus affiché après) et le coller dans `.env.local` :

```ini
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Le contrôleur `StripeWebhookController.php` valide la signature à chaque requête via `Webhook::constructEvent()`. **Toute requête sans signature valide retourne 400** — c'est anti-rejeu et anti-spoof.

### 3.3 Tester en dev local

Installer la CLI Stripe :
```bash
# macOS
brew install stripe/stripe-cli/stripe

# Linux (.deb)
curl -s https://packages.stripe.dev/api/security/keypair/stripe-cli-gpg/public \
  | gpg --dearmor | sudo tee /usr/share/keyrings/stripe.gpg
echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.dev/stripe-cli-debian-local stable main" \
  | sudo tee -a /etc/apt/sources.list.d/stripe.list
sudo apt update && sudo apt install stripe
```

Puis :
```bash
stripe login                                                  # ouvre une page de login Stripe
stripe listen --forward-to localhost:8000/stripe/webhook      # forward + affiche le whsec_ test à mettre dans .env.local
stripe trigger checkout.session.completed                     # simuler un événement
```

Les événements simulés portent le bon format de signature, ils passeront le `Webhook::constructEvent`.

---

## 4. Côté code Symfony

### 4.1 Création de la session (`StripeService::createCheckoutSession`)

```php
$this->client->checkout->sessions->create([
    'mode' => 'subscription',
    'customer_email' => $quote->getClientEmail(),
    'client_reference_id' => (string) $quote->getId(),
    'line_items' => [
        ['price' => $monthlyPriceId, 'quantity' => 1],
    ],
    'subscription_data' => [
        'metadata' => [
            'quote_id' => (string) $quote->getId(),
            'package' => $quote->getPackage()->getSlug()->value,
        ],
    ],
    'metadata' => [
        'quote_id' => (string) $quote->getId(),
        'deposit_cents' => (string) $depositCents,
        'package' => $quote->getPackage()->getSlug()->value,
    ],
    'success_url' => $this->publicBaseUrl . '/devis/confirmation/' . $quote->getId(),
    'cancel_url' => $this->publicBaseUrl . '/devis?cancelled=1',
]);
```

Le `metadata.deposit_cents` est ce qui permet au webhook de retrouver le montant à facturer en plus.

### 4.2 Ajout de l'`InvoiceItem` (`StripeService::addDepositInvoiceItem`)

```php
$this->client->invoiceItems->create([
    'customer' => $customerId,
    'subscription' => $subscriptionId,
    'amount' => $depositCents,
    'currency' => 'cad',
    'description' => sprintf('Acompte 50 %% — création %s', $packageName),
]);
```

L'`InvoiceItem` est rattaché à la **subscription** (pas seulement au customer), ce qui garantit qu'il sera consommé par la **prochaine facture** de cette subscription — qui est la première, encore non finalisée à ce moment.

### 4.3 Webhook handler (extrait)

```php
match ($event->type) {
    'checkout.session.completed' => $this->onCheckoutCompleted($event),
    'customer.subscription.created' => $this->onSubscriptionCreated($event),
    'invoice.payment_failed' => $this->onInvoiceFailed($event),
    default => null,
};
```

Détails dans `src/Controller/StripeWebhookController.php`.

---

## 5. Solde restant (50 % à la livraison)

Le code **ne facture pas automatiquement** le solde 50 % restant. Quand le site est livré, le solde doit être facturé manuellement. Plusieurs options :

### Option A : Lien de paiement Stripe one-shot (recommandé pour l'instant)

1. Dashboard Stripe → **Payment Links** → créer un lien pour le montant exact (199,50 / 299,50 / 449,50 $)
2. Envoyer le lien au client par email
3. Stripe envoie une confirmation au client + à toi automatiquement

### Option B : Facture Stripe manuelle

Dashboard → **Billing → Invoices → Create invoice** sur le customer existant. Ajouter un line item « Solde 50 % création XXXXX ».

### Option C : Automatiser dans le projet (à faire plus tard)

Ajouter un endpoint admin `POST /api/admin/quotes/{id}/charge-balance` qui :
1. Crée un `Stripe\Invoice` lié au customer
2. Ajoute un `InvoiceItem` du montant restant
3. Finalise la facture (`->finalizeInvoice()`)
4. Met à jour le devis en `delivered` ou similaire

À considérer en Sprint 2 du projet.

---

## 6. Migration test → live

### 6.1 Prérequis

- Compte Stripe **activé pour les paiements live** (KYC complété)
- 3 Prices recréés en mode **live** (les Prices test ne sont pas réutilisables)
- Webhook **live** créé avec son propre `whsec_…`

### 6.2 Procédure

1. Sur Stripe Dashboard, basculer du mode **Test** vers **Live**
2. Recréer les 3 produits + Prices en CAD
3. Créer le webhook `https://www.rivierematthieu.com/stripe/webhook` en mode live
4. Mettre à jour `.env.local` (sur Hostinger) :
   ```
   STRIPE_SECRET_KEY=sk_live_<remplacer-par-votre-cle-secrete>
   STRIPE_WEBHOOK_SECRET=whsec_<remplacer-par-votre-secret-webhook>
   STRIPE_PRICE_STARTER=price_<id-stripe-starter>
   STRIPE_PRICE_STANDARD=price_<id-stripe-standard>
   STRIPE_PRICE_PREMIUM=price_<id-stripe-premium>
   STRIPE_DASHBOARD_BASE=https://dashboard.stripe.com   (sans /test)
   ```
5. `UPDATE packages SET stripe_monthly_price_id = '...' WHERE slug = ...` pour chaque forfait avec les Price IDs live
6. `php bin/console cache:clear --env=prod`
7. Test avec une **vraie carte** (idéalement la tienne) sur un parcours complet → si tout passe, bravo

### 6.3 Sandboxer pour les tests post-déploiement

Si tu veux pouvoir tester sans carter de vraies sommes après mise en prod, garde une env `STRIPE_*_TEST` à part dans `.env.local` et un mécanisme de switch (par ex. `?test=1` dans l'URL admin pour basculer le service Stripe en mode test). Mais c'est une complexité optionnelle.

---

## 7. Côté admin

`/admin/subscriptions` affiche la liste des abonnements actifs en **lecture seule** via l'API Stripe. Toute modification (annulation, reschedule) doit se faire **dans le dashboard Stripe** — le central garde une vue, pas un état modifiable.

`/admin/quotes/{id}` montre les `stripe_*_id` du devis pour faciliter le débogage : copier le `stripe_subscription_id`, ouvrir Stripe → coller dans la barre de recherche.

---

## 8. Pièges connus

- **Currency casing** : Stripe attend `'cad'` minuscule, pas `'CAD'`. Vérifié dans `addDepositInvoiceItem`.
- **Le `customer_email` dans Checkout Session ne crée pas le customer immédiatement** — il est créé quand la session est complétée. Donc avant le webhook `checkout.session.completed`, on n'a pas de `customer_id` dans le devis.
- **`subscription` field dans la session** : accessible après complétion seulement. Avant ça, le devis a juste le `checkout_session_id`.
- **`metadata` est limité à 50 keys et 500 chars par valeur**. On en utilise 3, donc OK.
- **Les `InvoiceItem` doivent être créés AVANT la finalisation de la première facture**. Stripe finalise la facture dans les secondes après `customer.subscription.created`. Notre webhook traite `checkout.session.completed` qui arrive AVANT — donc on a le temps. Mais si une lenteur réseau empêche notre webhook de répondre dans les ~5 sec, on peut rater le coche. Solution : ajouter un retry. Actuellement non implémenté.
- **Les remboursements partiels** sont possibles via le dashboard Stripe mais ne sont pas reflétés automatiquement dans le devis (le statut reste `deposit_paid`). À gérer manuellement si nécessaire.

---

## 9. Logs et débogage

`StripeWebhookController` log via Monolog (channel `request`). En cas de souci, vérifier `var/log/prod.log`.

Pour un debug live, activer `APP_DEBUG=1` temporairement (mais surtout pas en prod en permanence — fuit le trace) :
```ini
APP_ENV=prod
APP_DEBUG=1
```
puis `cache:clear --env=prod` et reproduire l'erreur. Ensuite remettre `APP_DEBUG=0`.

Stripe Dashboard → **Developers → Logs** offre une vue très utile : chaque appel API, chaque webhook, avec response code et payload. Premier endroit où regarder en cas de doute.
