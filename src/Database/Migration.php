<?php

namespace MDMasudSikdar\WpKits\Database;

/**
 * Abstract Migration class.
 *
 * Provides a structured way to create, modify, and rollback database tables
 * for your WordPress plugin using migrations.
 *
 * Features:
 * ✅ Standardized migration pattern
 * ✅ Separate methods for applying and rolling back changes
 * ✅ Encourages consistent database versioning
 *
 * Responsibilities:
 * 1. `up()`   - Create or modify tables, add columns or indexes
 * 2. `down()` - Reverse changes made in `up()`, drop or modify tables
 *
 * @package MDMasudSikdar\WpKits\Database
 */
abstract class Migration
{
    /**
     * Apply the migration.
     *
     * Use this method to create or update database tables,
     * add indexes, or modify schema as needed.
     *
     * Example usage in a concrete migration:
     * ```php
     * public function up(): void
     * {
     *     Schema::create('my_table', function($table) {
     *      $table->increments('id');
     *    });
     * }
     * ```
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Rollback the migration.
     *
     * Use this method to undo changes applied in `up()`,
     * such as dropping tables, removing columns, or deleting indexes.
     *
     * Example usage in a concrete migration:
     * ```php
     * public function down(): void
     * {
     *      Schema::drop('my_table');
     * }
     * ```
     *
     * @return void
     */
    abstract public function down(): void;
}
