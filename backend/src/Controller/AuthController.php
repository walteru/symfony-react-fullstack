<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\CsrfValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Registro, login (sesion cookie HttpOnly), logout y usuario actual.
 *
 * El login y el registro tambien exigen CSRF: aunque sean rutas publicas, son
 * mutaciones, y asi cerramos el flujo completo como pidio la revision.
 */
class AuthController extends AbstractApiController
{
    private const FIREWALL = 'main';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
        private readonly CsrfValidator $csrf,
        private readonly Security $security,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * Entrega un token CSRF ligado a la sesion actual (y crea la sesion si no
     * existe). El frontend lo pide antes de cualquier mutacion.
     */
    #[Route('/api/csrf-token', name: 'api_csrf_token', methods: ['GET'])]
    public function csrfToken(): JsonResponse
    {
        return new JsonResponse(['token' => $this->csrf->currentToken()]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);
        $data = $this->decodeJson($request);

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (\strlen($password) < 8) {
            return $this->validationError(['password' => 'La contrasena debe tener al menos 8 caracteres.']);
        }

        $user = new User();
        $user->setEmail($email);

        // Validamos email/unicidad antes de hashear para no gastar el hash en vano.
        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            return $this->validationErrorFromViolations($violations);
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse($this->userToArray($user), Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);
        $data = $this->decodeJson($request);

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $user = $this->users->findOneBy(['email' => $email]);

        // Mensaje generico: no revelamos si fallo el email o la contrasena.
        if (null === $user || !$this->hasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'Credenciales invalidas.'], Response::HTTP_UNAUTHORIZED);
        }

        // Regeneramos el id de sesion al autenticar (la sesion ya existia desde
        // que se pidio el token CSRF). Esto evita session fixation; migrate(true)
        // conserva los atributos, incluido el propio token CSRF.
        $session = $request->getSession();
        $session->migrate(true);

        // Login manual: creamos el token y lo guardamos en la sesion. No usamos
        // Security::login() porque exige un autenticador interactivo en el
        // firewall, y aqui el login es 100% manual (para poder exigir CSRF).
        $token = new UsernamePasswordToken($user, self::FIREWALL, $user->getRoles());
        $this->tokenStorage->setToken($token);
        $session->set('_security_'.self::FIREWALL, serialize($token));

        return new JsonResponse($this->userToArray($user));
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $this->csrf->assertValid($request);

        // Cerramos la sesion: limpiamos el token (para que el firewall no lo
        // vuelva a persistir en la respuesta) e invalidamos la sesion.
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Usuario autenticado actual (o 401 si no hay sesion). El frontend lo usa
     * para decidir si muestra el login o la lista de tareas al cargar.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse($this->userToArray($user));
    }
}
