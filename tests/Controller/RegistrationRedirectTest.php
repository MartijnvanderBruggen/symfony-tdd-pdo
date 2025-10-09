<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationRedirectTest extends WebTestCase
{
    public function testSuccessfulRegistrationRedirectsToLogin(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);

        $this->removeExistingUsers($entityManager, $userRepository);

        $client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $unique = uniqid('user_', true);
        $client->submitForm('Register', [
            'registration_form[email]' => $unique.'@example.com',
            'registration_form[username]' => $unique,
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects('/login');
    }

    public function testEmailVerificationMarksUserAsVerified(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var UserRepository $userRepository */
        $userRepository = $container->get(UserRepository::class);

        $this->removeExistingUsers($entityManager, $userRepository);

        $unique = uniqid('verify_', true);
        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[email]' => $unique.'@example.com',
            'registration_form[username]' => $unique,
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects('/login');

        self::assertEmailCount(1);
        self::assertCount(1, $messages = $this->getMailerMessages());

        /** @var TemplatedEmail $templatedEmail */
        $templatedEmail = $messages[0];
        self::assertInstanceOf(TemplatedEmail::class, $templatedEmail);

        $htmlBody = $templatedEmail->getHtmlBody();
        self::assertIsString($htmlBody);

        self::assertMatchesRegularExpression('#href="(http://localhost/verify/email[^"]+)"#', $htmlBody);
        preg_match('#href="(http://localhost/verify/email[^"]+)"#', $htmlBody, $matches);
        self::assertArrayHasKey(1, $matches);
        $verificationUrl = $matches[1];

        $user = $userRepository->findOneBy(['email' => $unique.'@example.com']);
        self::assertNotNull($user);

        $client->followRedirect();
        $client->loginUser($user);

        $client->request('GET', $verificationUrl);
        $client->followRedirect();

        $entityManager->clear();

        $verifiedUser = $userRepository->find($user->getId());
        self::assertNotNull($verifiedUser);
        self::assertTrue($verifiedUser->isVerified());
    }

    private function removeExistingUsers(EntityManagerInterface $entityManager, UserRepository $userRepository): void
    {
        foreach ($userRepository->findAll() as $user) {
            $entityManager->remove($user);
        }

        $entityManager->flush();
    }
}
