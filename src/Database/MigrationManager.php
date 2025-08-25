<?php

namespace MDMasudSikdar\WpKits\Database;

use MDMasudSikdar\WpKits\Traits\SingletonTrait;

/**
 * Class MigrationManager
 *
 * Handles plugin database migrations with batch tracking.
 *
 * Features:
 * ✅ Singleton pattern to ensure only one instance exists
 * ✅ Tracks applied migrations in a dedicated table
 * ✅ Executes migrations only once
 * ✅ Supports rollback by batch
 * ✅ Uses WordPress $wpdb
 *
 * Responsibilities:
 * - Manage migrations table creation
 * - Run new migrations
 * - Log executed migrations
 * - Rollback migrations
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class MigrationManager
{
    use SingletonTrait;

    /**
     * WordPress database object.
     *
     * @var wpdb
     * @example
     * ```php
     * global $wpdb;
     * echo $wpdb->prefix;
     * ```
     */
    protected wpdb $wpdb;

    /**
     * Name of the migrations table without prefix.
     *
     * @var string
     */
    protected string $migrationTable = 'plugin_migrations';

    /**
     * Full table name including WordPress prefix.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Private constructor for singleton.
     *
     * @param string|null $migrationTable Optional table name (without prefix)
     */
    private function __construct(?string $migrationTable = null)
    {
        global $wpdb;

        // Assign WP database object
        $this->wpdb = $wpdb;

        // Set table name if provided
        if ($migrationTable) {
            $this->migrationTable = $migrationTable;
        }

        // Compute full table name with prefix
        $this->tableName = $this->wpdb->prefix . $this->migrationTable;

        // Ensure migrations table exists
        $this->createMigrationsTable();
    }

    /**
     * Create the migrations tracking table if it does not exist.
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        // Skip if table exists
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->tableName}'")) {
            return;
        }

        // Use Schema builder
        $schema = new Schema($this->migrationTable);

        $schema->increments();         // id
        $schema->string('migration');  // migration class
        $schema->integer('batch');     // batch number
        $schema->timestamp('created_at'); // timestamp

        $schema->build();              // create table
    }

    /**
     * Run an array of migration instances.
     *
     * Executes `up()` on migrations not yet executed.
     *
     * @param Migration[] $migrations Array of Migration instances
     * @return void
     * @example
     * ```php
     * MigrationManager::runMigrations([new CreateBooksTable()]);
     * ```
     */
    public static function runMigrations(array $migrations): void
    {
        // Get singleton instance
        $instance = self::getInstance();

        // Determine next batch number
        $batch = $instance->getCurrentBatch() + 1;

        foreach ($migrations as $migration) {
            // Only Migration instances
            if (!$migration instanceof Migration) {
                continue;
            }

            $migrationName = get_class($migration);

            // Skip already executed
            if ($instance->hasRun($migrationName)) {
                continue;
            }

            // Execute migration
            $migration->up();

            // Log migration
            $instance->logMigration($migrationName, $batch);
        }
    }

    /**
     * Rollback latest migrations by batch.
     *
     * @param int $steps Number of batches to rollback
     * @return void
     * @example
     * ```php
     * MigrationManager::rollback();
     * MigrationManager::rollback(2);
     * ```
     */
    public static function rollback(int $steps = 1): void
    {
        if ($steps < 1) {
            return;
        }

        // Get singleton instance
        $instance = self::getInstance();

        // Get latest batch numbers descending
        $batches = $instance->wpdb->get_col(
            "SELECT DISTINCT batch FROM {$instance->tableName} ORDER BY batch DESC LIMIT $steps"
        );

        foreach ($batches as $batch) {

            // Get migrations in batch reverse order
            $migrations = $instance->wpdb->get_col(
                $instance->wpdb->prepare(
                    "SELECT migration FROM {$instance->tableName} WHERE batch = %d ORDER BY id DESC",
                    $batch
                )
            );

            foreach ($migrations as $migrationName) {

                if (!class_exists($migrationName)) {
                    continue;
                }

                $migration = new $migrationName();

                // Only rollback if down() exists
                if (method_exists($migration, 'down')) {
                    $migration->down();
                }

                // Remove from migration log
                $instance->wpdb->delete($instance->tableName, ['migration' => $migrationName]);
            }
        }
    }

    /**
     * Check if a migration has already run.
     *
     * @param string $migrationName Fully qualified class name
     * @return bool
     */
    protected function hasRun(string $migrationName): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE migration = %s",
                $migrationName
            )
        );

        return $count > 0;
    }

    /**
     * Get the current highest batch number.
     *
     * @return int
     */
    protected function getCurrentBatch(): int
    {
        return (int) $this->wpdb->get_var(
            "SELECT MAX(batch) FROM {$this->tableName}"
        ) ?: 0;
    }

    /**
     * Log a migration as applied.
     *
     * @param string $migrationName Fully qualified class name
     * @param int $batch Batch number
     * @return void
     */
    protected function logMigration(string $migrationName, int $batch): void
    {
        $this->wpdb->insert(
            $this->tableName,
            [
                'migration' => $migrationName,
                'batch' => $batch,
                'created_at' => current_time('mysql'),
            ]
        );
    }
}
