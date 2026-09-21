<?php

namespace Tests\Feature;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

/**
 * Chaque notification doit pouvoir être chargée et mise en file.
 *
 * Une notification n'est chargée qu'au moment où on l'envoie : une erreur de
 * composition de classe ne se voit donc ni au déploiement ni dans les tests
 * métier — elle casse la confirmation de commande en production. C'est arrivé
 * en redéclarant `$afterCommit`, que le trait Queueable définit déjà : PHP 8.3
 * refuse la classe entière. Ce test charge les classes pour de bon.
 */
class NotificationsBootTest extends TestCase
{
    /** @return array<int, class-string> */
    private function notificationClasses(): array
    {
        return collect(glob(app_path('Notifications/*.php')))
            ->map(fn ($path) => 'App\\Notifications\\' . basename($path, '.php'))
            ->values()
            ->all();
    }

    public function test_every_notification_class_can_be_loaded(): void
    {
        $classes = $this->notificationClasses();
        $this->assertNotEmpty($classes, 'aucune notification trouvée');

        foreach ($classes as $class) {
            // `class_exists` déclenche l'autoload, donc la composition classe +
            // traits : c'est là que PHP refuserait une propriété incompatible.
            $this->assertTrue(class_exists($class), "{$class} ne se charge pas");
        }
    }

    public function test_queued_notifications_dispatch_after_commit(): void
    {
        foreach ($this->notificationClasses() as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->implementsInterface(ShouldQueue::class) || $reflection->isAbstract()) {
                continue;
            }

            $instance = $reflection->newInstanceWithoutConstructor();
            $reflection->getConstructor()?->invokeArgs($instance, $this->constructorStubs($reflection));

            $this->assertTrue(
                $instance->afterCommit,
                Str::afterLast($class, '\\') . ' doit attendre le commit avant de partir en file'
            );
        }
    }

    /**
     * Arguments factices pour appeler le constructeur : on ne teste que la
     * composition de la classe, pas le contenu du message.
     * @return array<int, mixed>
     */
    private function constructorStubs(ReflectionClass $reflection): array
    {
        return collect($reflection->getConstructor()?->getParameters() ?? [])
            ->map(function ($parameter) {
                $type = $parameter->getType();

                if ($type && ! $type->isBuiltin()) {
                    return (new ReflectionClass($type->getName()))->newInstanceWithoutConstructor();
                }

                return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            })
            ->all();
    }
}
