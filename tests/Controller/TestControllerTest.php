<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TestControllerTest extends WebTestCase
{
    public function testIndexReturnsJsonResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/test');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'message' => 'Welcome to your new controller!',
                'path' => 'src/Controller/TestController.php',
            ]),
            $client->getResponse()->getContent()
        );
    }
}
