<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Valida el token CSRF de las mutaciones. El token se obtiene en GET
 * /api/csrf-token (intencion "api_write", ligado a la sesion) y el frontend lo
 * reenvia en la cabecera X-CSRF-Token en cada POST/PUT/PATCH/DELETE.
 *
 * Que una mutacion exija un valor que solo se entrega via GET en la misma sesion
 * es lo que impide el CSRF: un sitio atacante no puede leer ese token.
 */
class CsrfValidator
{
    public const INTENTION = 'api_write';

    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    /**
     * Lanza 403 si el token de la cabecera X-CSRF-Token falta o es invalido.
     */
    public function assertValid(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token', '');

        if ('' === $token || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::INTENTION, $token))) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Token CSRF invalido o ausente.');
        }
    }

    /**
     * Devuelve un token valido para la sesion actual (lo consume GET /api/csrf-token).
     */
    public function currentToken(): string
    {
        return $this->csrfTokenManager->getToken(self::INTENTION)->getValue();
    }
}
