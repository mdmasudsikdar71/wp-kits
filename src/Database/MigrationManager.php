<?php

namespace MDMasudSikdar\WpKits\Database;

use wpdb;

/**
 * Class MigrationManager
 *
 * Handles plugin database migrations.
 * Tracks applied migrations in a dedicated table and ensures each migration runs only once.
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class MigrationManager
{
    /**
     * WordPress database object.
     *
     * @var wpdb
     */
    protected wpdb $wpdb;

    /**
     * Name of the migrations tracking table (without prefix).
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
     * Constructor.
     *
     * Initializes the migration manager and sets up the migrations tracking table.
     *
     * @param string|null $migrationTable Optional table name (without prefix).
     */
    public function __construct(?string $migrationTable = null)
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        if ($migrationTable) {
            $this->migrationTable = $migrationTable;
        }

        // Full table name with WordPress prefix
        $this->tableName = $this->wpdb->prefix . $this->migrationTable;

        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
    }

    /**
     * Creates the migrations tracking table if it does not exist.
     *
     * Columns:
     * - id: primary key
     * - migration: class name of the migration
     * - batch: batch number
     * - created_at: timestamp when migration was run
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$this->tableName}'")) {
            return;
        }

        // Here we will use your Schema class to build the table
        $schema = new Schema($this->migrationTable);

        $schema->increments();       // id
        $schema->string('migration'); // migration class name
        $schema->integer('batch');    // batch number
        $schema->timestamp('created_at'); // timestamp
        $schema->build();
    }

    /**
     * Run an array of migration instances.
     *
     * Each migration class should have an `up()` method that defines
     * the database changes for that migration.
     *
     * Only migrations that have not yet been run will be executed.
     *
     * @param Migration[] $migrations Array of migration class instances
     * @return void
     * @example
     * MigrationManager::runMigrations([
     *     new CreateBooksTable(),
     *     new AddAuthorsTable(),
     * ]);
     */
    public function runMigrations(array $migrations): void
    {
        // Determine the next batch number
        $batch = $this->getCurrentBatch() + 1;

        foreach ($migrations as $migration) {

            // Ensure it is a Migration instance
            if (! $migration instanceof Migration) {
                continue; // Skip invalid objects
            }

            $migrationName = get_class($migration);

            // Skip migrations that have already run
            if ($this->hasRun($migrationName)) {
                continue;
            }

            // Execute the migration
            $migration->up();

            // Log the migration as applied
            $this->logMigration($migrationName, $batch);
        }
    }

    /**
     * Rollback migrations.
     *
     * By default, rolls back the latest batch. You can specify a number
     * of batches to rollback.
     *
     * @param int $steps Number of batches to rollback, default 1
     * @return void
     * @example
     * MigrationManager::rollback(); // Rolls back latest batch
     * MigrationManager::rollback(2); // Rolls back last 2 batches
     */
    public function rollback(int $steps = 1): void
    {
        if ($steps < 1) {
            return;
        }

        // Get the highest batch numbers in descending order
        $batches = $this->wpdb->get_col(
            "SELECT DISTINCT batch FROM {$this->tableName} ORDER BY batch DESC LIMIT $steps"
        );

        foreach ($batches as $batch) {

            // Get all migrations in this batch in reverse order
            $migrations = $this->wpdb->get_col(
                $this->wpdb->prepare(
                    "SELECT migration FROM {$this->tableName} WHERE batch = %d ORDER BY id DESC",
                    $batch
                )
            );

            foreach ($migrations as $migrationName) {

                if (!class_exists($migrationName)) {
                    continue; // Skip if class not found
                }

                $migration = new $migrationName();

                // Only rollback if `down()` exists
                if (method_exists($migration, 'down')) {
                    $migration->down();
                }

                // Remove migration from log
                $this->wpdb->delete(
                    $this->tableName,
                    ['migration' => $migrationName]
                );
            }
        }
    }

    /**
     * Check if a migration has already been run.
     *
     * @param string $migrationName Fully qualified class name of migration
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
