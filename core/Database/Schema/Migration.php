<?php

declare(strict_types=1);

namespace Core\Database\Schema;

abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}
