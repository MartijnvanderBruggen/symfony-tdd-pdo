<?php
namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    public function testLoginFormDisplaysCorrectly(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form([
            '_username' => 'wronguser',
            '_password' => 'wrongpass',
        ]);
        $client->submit($form);
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
        $this->assertStringContainsString('Invalid credentials', $client->getResponse()->getContent());
    }

    public function testLoginRedirectsToDashboardWithValidCredentials(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);

        foreach ($userRepository->findAll() as $user) {
            $entityManager->remove($user);
        }
        $entityManager->flush();

        $user = new User();
        $user->setUsername('dashboard_user');
        $user->setEmail('dashboard_user@example.com');
        $user->setIsVerified(true);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form([
            '_username' => 'dashboard_user',
            '_password' => 'password',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/dashboard');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dashboard');
    }

    public function testUnverifiedUserCannotLogin(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);

        foreach ($userRepository->findAll() as $existingUser) {
            $entityManager->remove($existingUser);
        }
        $entityManager->flush();

        $user = new User();
        $user->setUsername('unverified_user');
        $user->setEmail('unverified_user@example.com');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form([
            '_username' => 'unverified_user',
            '_password' => 'password',
        ]);

        $client->submit($form);
        $client->followRedirect();

        self::assertSelectorExists('.alert-danger');
        self::assertStringContainsString('Your account is not verified.', $client->getResponse()->getContent());
    }
}
