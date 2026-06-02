<?php

declare(strict_types=1);

use Core\Database\Seeder;

class CallbackSeeder extends Seeder
{
    public static array $executed = [];

    public function run(): void
    {
        self::$executed[] = static::class;
    }
}

test('seeder call executa run() de cada seeder', function () {
    CallbackSeeder::$executed = [];

    $seeder = new class () extends Seeder {
        public function run(): void
        {
            $this->call(CallbackSeeder::class);
        }
    };

    $seeder->run();

    expect(CallbackSeeder::$executed)->toHaveCount(1);
});

test('seeder call executa multiplos seeders em ordem', function () {
    CallbackSeeder::$executed = [];

    $seeder = new class () extends Seeder {
        public function run(): void
        {
            $this->call(
                CallbackSeeder::class,
                CallbackSeeder::class,
            );
        }
    };

    $seeder->run();

    expect(CallbackSeeder::$executed)->toHaveCount(2);
});
