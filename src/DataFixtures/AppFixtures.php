<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Formation;
use App\Entity\ContactMessage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Catégories
        $categories = [];
        foreach (
            [
                ['name' => 'Jazz', 'type' => \App\Enum\CategoryType::EVENT],
                ['name' => 'Rock', 'type' => \App\Enum\CategoryType::EVENT],
                ['name' => 'Guitare', 'type' => \App\Enum\CategoryType::FORMATION],
                ['name' => 'Piano', 'type' => \App\Enum\CategoryType::FORMATION],
                ['name' => 'Festival', 'type' => \App\Enum\CategoryType::BOTH],
            ] as $catData
        ) {
            $cat = new \App\Entity\Category();
            $cat->setName($catData['name']);
            $cat->setType($catData['type']);
            $manager->persist($cat);
            $categories[$catData['name']] = $cat;
        }

        $manager->flush();

        // Lieux
        $locations = [];
        foreach (
            [
                ['name' => 'Salle Pleyel', 'address' => '252 Rue du Faubourg Saint-Honoré, 75008 Paris', 'lat' => '48.8761', 'lng' => '2.3095'],
                ['name' => 'Parc des Expositions Lyon', 'address' => 'Avenue Louis Blériot, 69680 Chassieu', 'lat' => '45.7578', 'lng' => '4.9572'],
                ['name' => 'Le Rex Club', 'address' => '5 Boulevard Poissonnière, 75002 Paris', 'lat' => '48.8706', 'lng' => '2.3472'],
                ['name' => 'Opéra Garnier', 'address' => '8 Rue Scribe, 75009 Paris', 'lat' => '48.8719', 'lng' => '2.3316'],
                ['name' => 'Le Trianon', 'address' => '80 Boulevard de Rochechouart, 75018 Paris', 'lat' => '48.8821', 'lng' => '2.3446'],
            ] as $locData
        ) {
            $loc = new \App\Entity\Location();
            $loc->setName($locData['name']);
            $loc->setAddress($locData['address']);
            $loc->setLatitude($locData['lat']);
            $loc->setLongitude($locData['lng']);
            $manager->persist($loc);
            $locations[$locData['name']] = $loc;
        }

        $manager->flush();

        // Utilisateurs
        $users = [];
        foreach (
            [
                ['firstname' => 'Alice', 'lastname' => 'Martin', 'email' => 'alice.martin@example.com', 'password' => 'password', 'roles' => ['ROLE_USER']],
                ['firstname' => 'Bob', 'lastname' => 'Durand', 'email' => 'bob.durand@example.com', 'password' => 'password', 'roles' => ['ROLE_USER']],
                ['firstname' => 'Admin', 'lastname' => 'Root', 'email' => 'admin@example.com', 'password' => 'adminpass', 'roles' => ['ROLE_ADMIN']],
            ] as $userData
        ) {
            $user = new \App\Entity\User();
            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setEmail($userData['email']);
            // Pour la démo, mot de passe en clair (à remplacer par hash en prod)
            $user->setPassword(password_hash($userData['password'], PASSWORD_BCRYPT));
            $user->setRoles($userData['roles']);
            $manager->persist($user);
            $users[$userData['email']] = $user;
        }

        $manager->flush();

        // Événements
        $eventsData = [
            [
                'title' => 'Concert Jazz Night',
                'description' => 'Une soirée jazz exceptionnelle avec les meilleurs musiciens de la région.',
                'date' => (new \DateTimeImmutable())->modify('+15 days')->setTime(20, 0),
                'location' => $locations['Salle Pleyel'],
                'imageUrl' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?w=800',
                'price' => '45.00',
                'totalTickets' => 200,
                'categories' => [$categories['Jazz'], $categories['Festival']],
            ],
            [
                'title' => 'Rock Festival 2025',
                'description' => 'Le plus grand festival de rock de l\'année avec des groupes internationaux.',
                'date' => (new \DateTimeImmutable())->modify('+30 days')->setTime(14, 0),
                'location' => $locations['Parc des Expositions Lyon'],
                'imageUrl' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=800',
                'price' => '120.00',
                'totalTickets' => 5000,
                'categories' => [$categories['Rock'], $categories['Festival']],
            ],
            [
                'title' => 'Soirée Electro Underground',
                'description' => 'Une nuit électro avec les DJs les plus en vogue du moment.',
                'date' => (new \DateTimeImmutable())->modify('+7 days')->setTime(23, 0),
                'location' => $locations['Le Rex Club'],
                'imageUrl' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800',
                'price' => '25.00',
                'totalTickets' => 300,
                'categories' => [$categories['Jazz']],
            ],
            [
                'title' => 'Concert Classique - Orchestre Philharmonique',
                'description' => 'Soirée de musique classique avec l\'Orchestre Philharmonique de Paris.',
                'date' => (new \DateTimeImmutable())->modify('+45 days')->setTime(20, 30),
                'location' => $locations['Opéra Garnier'],
                'imageUrl' => 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?w=800',
                'price' => '65.00',
                'totalTickets' => 800,
                'categories' => [$categories['Jazz']],
            ],
            [
                'title' => 'Tribute Night - The Beatles',
                'description' => 'Revivez la magie des Beatles avec le meilleur tribute band d\'Europe.',
                'date' => (new \DateTimeImmutable())->modify('+20 days')->setTime(21, 0),
                'location' => $locations['Le Trianon'],
                'imageUrl' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
                'price' => '38.00',
                'totalTickets' => 400,
                'categories' => [$categories['Rock']],
            ],
        ];
        $events = [];
        foreach ($eventsData as $eventData) {
            $event = new \App\Entity\Event();
            $event->setTitle($eventData['title'])
                ->setDescription($eventData['description'])
                ->setDate($eventData['date'])
                ->setImageUrl($eventData['imageUrl'])
                ->setPrice($eventData['price'])
                ->setTotalTickets($eventData['totalTickets'])
                ->setLocation($eventData['location']);
            foreach ($eventData['categories'] as $cat) {
                $event->addCategory($cat);
            }
            $manager->persist($event);
            $events[] = $event;
        }

        // Formations
        $formationsData = [
            [
                'title' => 'Cours de Guitare - Débutant',
                'description' => 'Apprenez les bases de la guitare avec un professeur expérimenté.',
                'imageUrl' => 'https://images.unsplash.com/photo-1564186763535-ebb21ef5277f?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+10 days')->setTime(18, 0),
                'duration' => '3 mois (12 séances)',
                'price' => '180.00',
                'maxParticipants' => 12,
                'instructor' => 'Jean Dupont',
                'location' => $locations['Salle Pleyel'],
                'categories' => [$categories['Guitare']],
            ],
            [
                'title' => 'Masterclass Piano Jazz',
                'description' => 'Formation intensive de piano jazz avec les techniques d\'improvisation.',
                'imageUrl' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+25 days')->setTime(14, 0),
                'duration' => '2 semaines intensives',
                'price' => '450.00',
                'maxParticipants' => 8,
                'instructor' => 'Sophie Martin',
                'location' => $locations['Parc des Expositions Lyon'],
                'categories' => [$categories['Piano']],
            ],
            [
                'title' => 'Atelier Chant et Technique Vocale',
                'description' => 'Développez votre voix et apprenez les techniques de chant professionnel.',
                'imageUrl' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+5 days')->setTime(19, 0),
                'duration' => '6 semaines',
                'price' => '220.00',
                'maxParticipants' => 10,
                'instructor' => 'Marie Dubois',
                'location' => $locations['Le Rex Club'],
                'categories' => [$categories['Guitare']],
            ],
            [
                'title' => 'Production Musicale et MAO',
                'description' => 'Formation complète à la production musicale assistée par ordinateur.',
                'imageUrl' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+40 days')->setTime(10, 0),
                'duration' => '4 mois',
                'price' => '650.00',
                'maxParticipants' => 15,
                'instructor' => 'Alex Rousseau',
                'location' => $locations['Le Trianon'],
                'categories' => [$categories['Piano']],
            ],
        ];
        $formations = [];
        foreach ($formationsData as $formationData) {
            $formation = new \App\Entity\Formation();
            $formation->setTitle($formationData['title'])
                ->setDescription($formationData['description'])
                ->setImageUrl($formationData['imageUrl'])
                ->setStartDate($formationData['startDate'])
                ->setDuration($formationData['duration'])
                ->setPrice($formationData['price'])
                ->setMaxParticipants($formationData['maxParticipants'])
                ->setInstructor($formationData['instructor'])
                ->setLocation($formationData['location']);
            foreach ($formationData['categories'] as $cat) {
                $formation->addCategory($cat);
            }
            $manager->persist($formation);
            $formations[] = $formation;
        }

        // Tickets (liés à events/formations et users)
        $tickets = [];
        foreach (
            [
                // Tickets pour événements
                ['event' => $events[0], 'user' => $users['alice.martin@example.com'], 'customerName' => 'Alice Martin', 'customerEmail' => 'alice.martin@example.com', 'totalPrice' => '45.00', 'paymentStatus' => \App\Enum\PaymentStatus::PAID],
                ['event' => $events[1], 'user' => $users['bob.durand@example.com'], 'customerName' => 'Bob Durand', 'customerEmail' => 'bob.durand@example.com', 'totalPrice' => '120.00', 'paymentStatus' => \App\Enum\PaymentStatus::PENDING],
                // Tickets pour formations
                ['formation' => $formations[0], 'user' => $users['alice.martin@example.com'], 'customerName' => 'Alice Martin', 'customerEmail' => 'alice.martin@example.com', 'totalPrice' => '180.00', 'paymentStatus' => \App\Enum\PaymentStatus::PAID],
                ['formation' => $formations[1], 'user' => $users['bob.durand@example.com'], 'customerName' => 'Bob Durand', 'customerEmail' => 'bob.durand@example.com', 'totalPrice' => '450.00', 'paymentStatus' => \App\Enum\PaymentStatus::PAID],
            ] as $ticketData
        ) {
            $ticket = new \App\Entity\Ticket();
            if (isset($ticketData['event'])) {
                $ticket->setEvent($ticketData['event']);
            }
            if (isset($ticketData['formation'])) {
                $ticket->setFormation($ticketData['formation']);
            }
            $ticket->setUser($ticketData['user']);
            $ticket->setCustomerName($ticketData['customerName']);
            $ticket->setCustomerEmail($ticketData['customerEmail']);
            $ticket->setPrice($ticketData['totalPrice']);
            $ticket->setPaymentStatus($ticketData['paymentStatus']);
            $manager->persist($ticket);
            $tickets[] = $ticket;
        }

        // RefundRequests
        foreach (
            [
                ['ticket' => $tickets[0], 'user' => $users['alice.martin@example.com'], 'customerName' => 'Alice Martin', 'customerEmail' => 'alice.martin@example.com', 'reason' => 'Je ne peux plus venir.', 'refundAmount' => '45.00', 'status' => \App\Enum\RefundStatus::PENDING],
                ['ticket' => $tickets[3], 'user' => $users['bob.durand@example.com'], 'customerName' => 'Bob Durand', 'customerEmail' => 'bob.durand@example.com', 'reason' => 'Problème de santé.', 'refundAmount' => '450.00', 'status' => \App\Enum\RefundStatus::PROCESSED],
            ] as $refundData
        ) {
            $refund = new \App\Entity\RefundRequest();
            $refund->setTicket($refundData['ticket']);
            $refund->setUser($refundData['user']);
            $refund->setCustomerName($refundData['customerName']);
            $refund->setCustomerEmail($refundData['customerEmail']);
            $refund->setReason($refundData['reason']);
            $refund->setRefundAmount($refundData['refundAmount']);
            $refund->setStatus($refundData['status']);
            $manager->persist($refund);
        }

        // Messages de contact
        foreach (
            [
                ['name' => 'Pierre Leroy', 'email' => 'pierre.leroy@example.com', 'message' => 'Bonjour, je souhaiterais obtenir plus d\'informations sur les cours de guitare pour débutants. Est-ce que le matériel est fourni ?'],
                ['name' => 'Camille Bernard', 'email' => 'camille.bernard@example.com', 'message' => 'Est-il possible d\'avoir des réductions pour les groupes ? Nous sommes 5 personnes intéressées par la formation de production musicale.'],
                ['name' => 'Thomas Petit', 'email' => 'thomas.petit@example.com', 'message' => 'Y a-t-il un parking à proximité de la Salle Pleyel pour le concert Jazz Night ?'],
            ] as $messageData
        ) {
            $message = new \App\Entity\ContactMessage();
            $message->setName($messageData['name']);
            $message->setEmail($messageData['email']);
            $message->setMessage($messageData['message']);
            $manager->persist($message);
        }

        $manager->flush();
    }
}
