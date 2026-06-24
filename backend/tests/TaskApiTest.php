<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class TaskApiTest extends ApiTestCase
{
    public function testTasksRequireAuthentication(): void
    {
        $this->client->request('GET', '/api/tasks');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateRequiresCsrfToken(): void
    {
        $this->registerAndLogin('owner@example.com');
        $this->json('POST', '/api/tasks', ['title' => 'Sin CSRF'], csrf: false);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateListAndComplete(): void
    {
        $this->registerAndLogin('owner@example.com');

        $this->json('POST', '/api/tasks', ['title' => 'Primera tarea']);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->responseData();
        self::assertFalse($created['done']);

        $this->client->request('GET', '/api/tasks');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->responseData());

        $this->json('PATCH', '/api/tasks/'.$created['id'], ['done' => true]);
        self::assertResponseIsSuccessful();
        self::assertTrue($this->responseData()['done']);
    }

    public function testCreateRejectsEmptyTitle(): void
    {
        $this->registerAndLogin('owner@example.com');
        $this->json('POST', '/api/tasks', ['title' => '   ']);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('title', $this->responseData()['errors']);
    }

    public function testDelete(): void
    {
        $this->registerAndLogin('owner@example.com');
        $this->json('POST', '/api/tasks', ['title' => 'Borrame']);
        $id = $this->responseData()['id'];

        $this->json('DELETE', '/api/tasks/'.$id);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->request('GET', '/api/tasks');
        self::assertCount(0, $this->responseData());
    }

    public function testUsersCannotAccessForeignTasks(): void
    {
        // Usuario A crea una tarea.
        $this->registerAndLogin('a@example.com');
        $this->json('POST', '/api/tasks', ['title' => 'Tarea de A']);
        $foreignId = $this->responseData()['id'];

        // Usuario B inicia sesion (misma cookie de cliente, nueva sesion).
        $this->registerAndLogin('b@example.com');

        // B no ve las tareas de A.
        $this->client->request('GET', '/api/tasks');
        self::assertCount(0, $this->responseData());

        // B no puede modificar ni borrar la tarea de A: 404 (no se filtra existencia).
        $this->json('PATCH', '/api/tasks/'.$foreignId, ['done' => true]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->json('DELETE', '/api/tasks/'.$foreignId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateNonexistentTaskIsNotFound(): void
    {
        $this->registerAndLogin('owner@example.com');
        $this->json('PATCH', '/api/tasks/9999', ['done' => true]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function registerAndLogin(string $email): void
    {
        $this->register($email, 'secret123');
        $this->login($email, 'secret123');
        self::assertResponseIsSuccessful();
    }
}
