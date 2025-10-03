<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
}
