<?php

namespace App\Tests\E2E;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\PaymentStatus;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class AbstractE2ETest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected User $testUser;
    protected Event $testEvent;
    protected Ticket $testTicket;
    protected ?string $jwtToken = null;
    protected string $validQrCode;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        $this->em = $container->get('doctrine')->getManager();

        $this->createSchema();
        $this->createTestUser();
        $this->createTestEvent();
        $this->createTestTicket();
        $this->authenticateUser();
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    private function createTestUser(): void
    {
        $this->testUser = new User();
        $this->testUser->setEmail('bob.durand@example.com');
        $this->testUser->setRoles(['ROLE_USER']);
        $this->testUser->setFirstName('Bob');
        $this->testUser->setLastName('Durand');
        // Génère avec: php bin/console security:hash-password bobpass
        $this->testUser->setPassword('$2y$13$Q0aI2oXFccpnO1xK5V9zQ.P7qa683A.F3lE9qruUj.lqfeUQAmN9q');

        $this->em->persist($this->testUser);
        $this->em->flush();
    }

    private function createTestEvent(): void
    {
        $this->testEvent = new Event();
        $this->testEvent->setTitle('Concert Test');
        $this->testEvent->setDescription('Description du concert test');
        $this->testEvent->setDate(new \DateTimeImmutable('+1 month'));
        $this->testEvent->setImageUrl('https://example.com/image.jpg');
        $this->testEvent->setPrice('100.00');
        $this->testEvent->setTotalTickets(100);

        $this->em->persist($this->testEvent);
        $this->em->flush();
    }

    private function createTestTicket(): void
    {
        $qrCodeService = $this->client->getContainer()->get(QrCodeService::class);

        $this->testTicket = new Ticket();
        $this->testTicket->setEvent($this->testEvent);
        $this->testTicket->setUser($this->testUser);
        $this->testTicket->setCustomerName('Bob Durand');
        $this->testTicket->setCustomerEmail('bob.durand@example.com');
        $this->testTicket->setTotalPrice('100.00');
        $this->testTicket->setPaymentStatus(PaymentStatus::PAID);
        $this->testTicket->setPurchasedAt(new \DateTimeImmutable());

        $this->em->persist($this->testTicket);
        $this->em->flush();

        $this->validQrCode = $qrCodeService->generateQrCode($this->testTicket);
        $this->testTicket->setQrCode($this->validQrCode);
        $this->em->flush();
    }

    private function authenticateUser(): void
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'bob.durand@example.com',
                'password' => 'bobpass'
            ])
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);
        $this->jwtToken = $data['token'] ?? null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();

        // Supprimer la DB de test
        $dbPath = __DIR__ . '/../../var/test.db';
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }
}
