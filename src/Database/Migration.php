<?php

namespace MDMasudSikdar\WpKits\Database;

/**
 * Abstract Migration class.
 *
 * Extend this class to define migrations for your plugin.
 * Each migration must implement the `up()` and `down()` methods.
 *
 * Use `up()` to create or modify tables,
 * and `down()` to rollback or drop tables.
 *
 * @package MDMasudSikdar\WpKits\Database
 */
abstract class Migration
{
    /**
     * Run the migration.
     *
     * Create or update database schema.
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Reverse the migration.
     *
     * Rollback database schema changes.
     *
     * @return void
     */
    abstract public function down(): void;
}
