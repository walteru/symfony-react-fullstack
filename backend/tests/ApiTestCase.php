<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base de los tests funcionales del API. Recrea el esquema en la base de tests
 * (app_test, MySQL) antes de cada test para partir de un estado limpio, y ofrece
 * helpers para el token CSRF y las peticiones JSON.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    /**
     * Obtiene un token CSRF valido para la sesion del cliente de test.
     */
    protected function csrfToken(): string
    {
        $this->client->request('GET', '/api/csrf-token');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        return $data['token'];
    }

    /**
     * Hace una peticion JSON. Por defecto adjunta un token CSRF valido en las
     * mutaciones; pasar $csrf=false para probar el rechazo por CSRF ausente.
     *
     * @param array<string, mixed>|null $body
     */
    protected function json(string $method, string $uri, ?array $body = null, bool $csrf = true): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($csrf && 'GET' !== $method) {
            $server['HTTP_X_CSRF_TOKEN'] = $this->csrfToken();
        }

        $this->client->request($method, $uri, [], [], $server, null !== $body ? json_encode($body) : null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseData(): array
    {
        $content = $this->client->getResponse()->getContent();

        return '' === $content ? [] : (json_decode($content, true) ?? []);
    }

    protected function register(string $email, string $password): void
    {
        $this->json('POST', '/api/register', ['email' => $email, 'password' => $password]);
    }

    protected function login(string $email, string $password): void
    {
        $this->json('POST', '/api/login', ['email' => $email, 'password' => $password]);
    }
}
