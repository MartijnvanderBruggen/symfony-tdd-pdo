<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    public function testRegisterPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[plainPassword]"]');
    }
}
