<?php

declare(strict_types=1);

namespace Core\Database;

class Seeder
{
    public function run(): void
    {
        // Override in subclasses
    }

    protected function call(string ...$seeders): void
    {
        foreach ($seeders as $seederClass) {
            $instance = new $seederClass();
            $instance->run();
        }
    }
}
