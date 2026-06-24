<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Helpers compartidos por los controladores del API: parseo de JSON, formato de
 * errores de validacion y normalizacion de entidades a arrays (contrato JSON
 * explicito; la contrasena del User nunca se incluye).
 *
 * Extiende AbstractController para que el autoconfigure registre las subclases
 * como controladores-servicio (necesario al inyectar dependencias por constructor).
 */
abstract class AbstractApiController extends AbstractController
{
    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequestHttpException('El cuerpo de la peticion no es JSON valido.');
        }

        return \is_array($data) ? $data : [];
    }

    /**
     * @param array<string, string> $errors
     */
    protected function validationError(array $errors): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function validationErrorFromViolations(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->validationError($errors);
    }

    /**
     * @return array{id: int|null, email: string|null}
     */
    protected function userToArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * @return array{id: int|null, title: string|null, done: bool, createdAt: string|null}
     */
    protected function taskToArray(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'done' => $task->isDone(),
            'createdAt' => $task->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
