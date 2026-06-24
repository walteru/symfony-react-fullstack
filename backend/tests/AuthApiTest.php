<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class AuthApiTest extends ApiTestCase
{
    public function testRegisterAndLoginFlow(): void
    {
        $this->register('ana@example.com', 'secret123');
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('ana@example.com', $this->responseData()['email']);

        $this->login('ana@example.com', 'secret123');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/me');
        self::assertResponseIsSuccessful();
        self::assertSame('ana@example.com', $this->responseData()['email']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->register('dup@example.com', 'secret123');
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->register('dup@example.com', 'otherpass1');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('email', $this->responseData()['errors']);
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $this->register('short@example.com', '123');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginWithWrongPasswordIsUnauthorized(): void
    {
        $this->register('bob@example.com', 'secret123');
        $this->login('bob@example.com', 'wrongpass');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithUnknownUserIsUnauthorized(): void
    {
        $this->login('nobody@example.com', 'whatever1');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithoutCsrfTokenIsForbidden(): void
    {
        $this->register('eve@example.com', 'secret123');
        // Mutacion sin token CSRF: debe rechazarse aunque las credenciales sean validas.
        $this->json('POST', '/api/login', ['email' => 'eve@example.com', 'password' => 'secret123'], csrf: false);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMeWithoutSessionIsUnauthorized(): void
    {
        $this->client->request('GET', '/api/me');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutEndsSession(): void
    {
        $this->register('carl@example.com', 'secret123');
        $this->login('carl@example.com', 'secret123');

        $this->json('POST', '/api/logout');
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Tras el logout, una mutacion con la cookie anterior ya no autentica.
        $this->json('POST', '/api/tasks', ['title' => 'no deberia entrar']);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testPasswordIsNeverReturned(): void
    {
        $this->register('nopass@example.com', 'secret123');
        self::assertArrayNotHasKey('password', $this->responseData());
    }
}
