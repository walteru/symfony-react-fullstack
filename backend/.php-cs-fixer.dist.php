<?php

/*
 * Configuracion de PHP CS Fixer para este demo.
 *
 * Reglas:
 *  - @PSR12:   estandar de estilo de la comunidad PHP.
 *  - @Symfony: convenciones de estilo del framework (orden de imports, espacios,
 *              PHPDoc, etc.). Es un superconjunto de PSR-12 afinado para Symfony.
 *
 * No activamos reglas "risky" (las que podrian cambiar el comportamiento del
 * codigo): en un demo publico preferimos que el formateo sea 100% seguro.
 *
 * Solo formateamos el codigo que escribimos a mano (src/ y tests/). Lo generado
 * o de terceros (var/, vendor/, migrations/, config/) queda fuera.
 */

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
