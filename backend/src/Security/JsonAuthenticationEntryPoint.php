<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Punto de entrada del firewall: cuando una peticion anonima toca una ruta
 * protegida, respondemos 401 JSON en lugar de redirigir a un formulario de login
 * (este backend solo sirve un API consumido por la SPA de React).
 */
class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Autenticacion requerida.'], Response::HTTP_UNAUTHORIZED);
    }
}
