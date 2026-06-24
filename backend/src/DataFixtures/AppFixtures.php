<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Datos de ejemplo para que el demo arranque con algo usable.
 *
 * Credenciales demo (publicas, NO secretas): demo@example.com / demo1234.
 * Idempotente: si el usuario demo ya existe, no hace nada, asi correrla con
 * --append no duplica datos.
 */
class AppFixtures extends Fixture
{
    public const DEMO_EMAIL = 'demo@example.com';
    public const DEMO_PASSWORD = 'demo1234';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => self::DEMO_EMAIL]);
        if (null !== $existing) {
            return;
        }

        $user = new User();
        $user->setEmail(self::DEMO_EMAIL);
        $user->setPassword($this->hasher->hashPassword($user, self::DEMO_PASSWORD));
        $manager->persist($user);

        foreach (['Probar el demo full-stack' => true, 'Leer el post en sincrodev.com' => false] as $title => $done) {
            $task = new Task();
            $task->setOwner($user);
            $task->setTitle($title);
            $task->setDone($done);
            $manager->persist($task);
        }

        $manager->flush();
    }
}
