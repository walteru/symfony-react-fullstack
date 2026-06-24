<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Security\CsrfValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * CRUD de tareas privadas por usuario. Toda operacion opera SOLO sobre tareas
 * del usuario autenticado; una tarea ajena o inexistente devuelve 404 (no se
 * distingue, para no filtrar la existencia de recursos de otros usuarios).
 *
 * El firewall ya exige ROLE_USER en /api, asi que aqui el usuario siempre existe.
 */
#[Route('/api/tasks')]
class TaskController extends AbstractApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskRepository $tasks,
        private readonly ValidatorInterface $validator,
        private readonly CsrfValidator $csrf,
        private readonly Security $security,
    ) {
    }

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map(
            fn (Task $t) => $this->taskToArray($t),
            $this->tasks->findByOwner($this->currentUser())
        );

        return new JsonResponse($items);
    }

    #[Route('', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);
        $data = $this->decodeJson($request);

        $task = new Task();
        $task->setOwner($this->currentUser());
        $task->setTitle(trim((string) ($data['title'] ?? '')));
        if (\array_key_exists('done', $data)) {
            $task->setDone((bool) $data['done']);
        }

        $violations = $this->validator->validate($task);
        if (\count($violations) > 0) {
            return $this->validationErrorFromViolations($violations);
        }

        $this->em->persist($task);
        $this->em->flush();

        return new JsonResponse($this->taskToArray($task), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_tasks_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);
        $task = $this->ownedTaskOr404($id);
        $data = $this->decodeJson($request);

        if (\array_key_exists('title', $data)) {
            $task->setTitle(trim((string) $data['title']));
        }
        if (\array_key_exists('done', $data)) {
            $task->setDone((bool) $data['done']);
        }

        $violations = $this->validator->validate($task);
        if (\count($violations) > 0) {
            return $this->validationErrorFromViolations($violations);
        }

        $this->em->flush();

        return new JsonResponse($this->taskToArray($task));
    }

    #[Route('/{id}', name: 'api_tasks_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);
        $task = $this->ownedTaskOr404($id);

        $this->em->remove($task);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // No deberia ocurrir: el firewall protege /api. Defensa en profundidad.
            throw new NotFoundHttpException();
        }

        return $user;
    }

    /**
     * Devuelve la tarea SOLO si pertenece al usuario actual; si no, 404.
     */
    private function ownedTaskOr404(int $id): Task
    {
        $task = $this->tasks->findOneBy(['id' => $id, 'owner' => $this->currentUser()]);
        if (null === $task) {
            throw new NotFoundHttpException('Tarea no encontrada.');
        }

        return $task;
    }
}
