<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DashboardControllerTest extends WebTestCase
{
    public function testNonAdminUserIsRedirectedAwayFromDashboard(): void
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
        $user->setUsername('regular_user');
        $user->setEmail('regular_user@example.com');
        $user->setIsVerified(true);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $client->request('GET', '/dashboard');

        self::assertResponseRedirects('/');
    }

    public function testUserAdminCanAccessDashboard(): void
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
        $user->setUsername('admin_user');
        $user->setEmail('admin_user@example.com');
        $user->setIsVerified(true);
        $user->setRoles([User::ROLE_USER_ADMIN]);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/dashboard');

        self::assertResponseIsSuccessful();
        self::assertSame('Dashboard', $crawler->filter('h1')->text());
    }
}
