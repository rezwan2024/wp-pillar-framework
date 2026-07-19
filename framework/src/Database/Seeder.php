<?php

declare(strict_types=1);

namespace WPPillar\Framework\Database;

/**
 * Abstract database seeder base class.
 *
 * Every plugin seeder must extend this class and implement run(). Passed to
 * Installer::activate() as a Seeder[] — the installer tracks which seeder
 * classes have already run per plugin slug so re-activating a plugin never
 * re-runs a seeder that already inserted data.
 *
 * Usage:
 *   class ExampleSeeder extends Seeder
 *   {
 *       public function run(): void
 *       {
 *           ExampleModel::insertOrIgnore([...]);
 *       }
 *   }
 */
abstract class Seeder
{
    /**
     * Insert the seeder's data.
     */
    abstract public function run(): void;
}
