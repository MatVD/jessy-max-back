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
        // Création d'événements (Concerts)
        $events = [
            [
                'title' => 'Concert Jazz Night',
                'description' => 'Une soirée jazz exceptionnelle avec les meilleurs musiciens de la région. Plongez dans l\'univers du jazz avec des standards et des créations originales.',
                'eventType' => 'concert',
                'date' => (new \DateTimeImmutable())->modify('+15 days')->setTime(20, 0),
                'location' => 'Salle Pleyel, Paris',
                'imageUrl' => 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?w=800',
                'price' => '45.00',
                'totalTickets' => 200,
                'availableTickets' => 200,
            ],
            [
                'title' => 'Rock Festival 2025',
                'description' => 'Le plus grand festival de rock de l\'année avec des groupes internationaux. Trois jours de musique non-stop avec camping sur place.',
                'eventType' => 'festival',
                'date' => (new \DateTimeImmutable())->modify('+30 days')->setTime(14, 0),
                'location' => 'Parc des Expositions, Lyon',
                'imageUrl' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=800',
                'price' => '120.00',
                'totalTickets' => 5000,
                'availableTickets' => 4850,
            ],
            [
                'title' => 'Soirée Electro Underground',
                'description' => 'Une nuit électro avec les DJs les plus en vogue du moment. Ambiance underground garantie jusqu\'au petit matin.',
                'eventType' => 'concert',
                'date' => (new \DateTimeImmutable())->modify('+7 days')->setTime(23, 0),
                'location' => 'Le Rex Club, Paris',
                'imageUrl' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800',
                'price' => '25.00',
                'totalTickets' => 300,
                'availableTickets' => 280,
            ],
            [
                'title' => 'Concert Classique - Orchestre Philharmonique',
                'description' => 'Soirée de musique classique avec l\'Orchestre Philharmonique de Paris. Au programme : Beethoven, Mozart et Chopin.',
                'eventType' => 'concert',
                'date' => (new \DateTimeImmutable())->modify('+45 days')->setTime(20, 30),
                'location' => 'Opéra Garnier, Paris',
                'imageUrl' => 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?w=800',
                'price' => '65.00',
                'totalTickets' => 800,
                'availableTickets' => 800,
            ],
            [
                'title' => 'Tribute Night - The Beatles',
                'description' => 'Revivez la magie des Beatles avec le meilleur tribute band d\'Europe. Tous les hits légendaires en live.',
                'eventType' => 'concert',
                'date' => (new \DateTimeImmutable())->modify('+20 days')->setTime(21, 0),
                'location' => 'Le Trianon, Paris',
                'imageUrl' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=800',
                'price' => '38.00',
                'totalTickets' => 400,
                'availableTickets' => 350,
            ],
        ];

        foreach ($events as $eventData) {
            $event = new Event();
            $event->setTitle($eventData['title'])
                ->setDescription($eventData['description'])
                ->setEventType($eventData['eventType'])
                ->setDate($eventData['date'])
                ->setLocation($eventData['location'])
                ->setImageUrl($eventData['imageUrl'])
                ->setPrice($eventData['price'])
                ->setTotalTickets($eventData['totalTickets'])
                ->setAvailableTickets($eventData['availableTickets']);

            $manager->persist($event);
        }

        // Création de formations
        $formations = [
            [
                'title' => 'Cours de Guitare - Débutant',
                'description' => 'Apprenez les bases de la guitare avec un professeur expérimenté. Cours hebdomadaires pendant 3 mois avec exercices pratiques et théorie musicale.',
                'imageUrl' => 'https://images.unsplash.com/photo-1564186763535-ebb21ef5277f?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+10 days')->setTime(18, 0),
                'duration' => '3 mois (12 séances)',
                'price' => '180.00',
                'maxParticipants' => 12,
                'currentParticipants' => 8,
                'instructor' => 'Jean Dupont',
                'category' => 'guitare',
            ],
            [
                'title' => 'Masterclass Piano Jazz',
                'description' => 'Formation intensive de piano jazz avec les techniques d\'improvisation et d\'accompagnement. Pour musiciens intermédiaires à avancés.',
                'imageUrl' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+25 days')->setTime(14, 0),
                'duration' => '2 semaines intensives',
                'price' => '450.00',
                'maxParticipants' => 8,
                'currentParticipants' => 5,
                'instructor' => 'Sophie Martin',
                'category' => 'piano',
            ],
            [
                'title' => 'Atelier Chant et Technique Vocale',
                'description' => 'Développez votre voix et apprenez les techniques de chant professionnel. Travail sur la respiration, la justesse et l\'interprétation.',
                'imageUrl' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+5 days')->setTime(19, 0),
                'duration' => '6 semaines',
                'price' => '220.00',
                'maxParticipants' => 10,
                'currentParticipants' => 10,
                'instructor' => 'Marie Dubois',
                'category' => 'chant',
            ],
            [
                'title' => 'Production Musicale et MAO',
                'description' => 'Formation complète à la production musicale assistée par ordinateur. Logiciels, composition, mixage et mastering.',
                'imageUrl' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800',
                'startDate' => (new \DateTimeImmutable())->modify('+40 days')->setTime(10, 0),
                'duration' => '4 mois',
                'price' => '650.00',
                'maxParticipants' => 15,
                'currentParticipants' => 3,
                'instructor' => 'Alex Rousseau',
                'category' => 'production',
            ],
        ];

        foreach ($formations as $formationData) {
            $formation = new Formation();
            $formation->setTitle($formationData['title'])
                ->setDescription($formationData['description'])
                ->setImageUrl($formationData['imageUrl'])
                ->setStartDate($formationData['startDate'])
                ->setDuration($formationData['duration'])
                ->setPrice($formationData['price'])
                ->setMaxParticipants($formationData['maxParticipants'])
                ->setCurrentParticipants($formationData['currentParticipants'])
                ->setInstructor($formationData['instructor'])
                ->setCategory($formationData['category']);

            $manager->persist($formation);
        }

        // Création de quelques messages de contact
        $contactMessages = [
            [
                'name' => 'Pierre Leroy',
                'email' => 'pierre.leroy@example.com',
                'message' => 'Bonjour, je souhaiterais obtenir plus d\'informations sur les cours de guitare pour débutants. Est-ce que le matériel est fourni ?',
            ],
            [
                'name' => 'Camille Bernard',
                'email' => 'camille.bernard@example.com',
                'message' => 'Est-il possible d\'avoir des réductions pour les groupes ? Nous sommes 5 personnes intéressées par la formation de production musicale.',
            ],
            [
                'name' => 'Thomas Petit',
                'email' => 'thomas.petit@example.com',
                'message' => 'Y a-t-il un parking à proximité de la Salle Pleyel pour le concert Jazz Night ?',
            ],
        ];

        foreach ($contactMessages as $messageData) {
            $message = new ContactMessage();
            $message->setName($messageData['name'])
                ->setEmail($messageData['email'])
                ->setMessage($messageData['message']);

            $manager->persist($message);
        }

        $manager->flush();
    }
}
