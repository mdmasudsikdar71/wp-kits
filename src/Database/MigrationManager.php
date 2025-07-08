<?php

namespace MDMasudSikdar\WpKits\Database;

/**
 * Class MigrationManager
 *
 * Manages plugin database migrations and tracks applied migrations.
 *
 * Usage:
 * ```php
 * $migrator = new MigrationManager('plugin_migrations');
 * $migrator->runMigrations([
 *    new CreateBooksTable(),
 *    new AddAuthorsTable(),
 * ]);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class MigrationManager
{
    /**
     * The wpdb global object.
     *
     * @var \wpdb
     */
    protected \wpdb $wpdb;

    /**
     * Name of the migrations tracking table (without prefix).
     *
     * @var string
     */
    protected string $migrationTable;

    /**
     * Full table name with prefix.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * MigrationManager constructor.
     *
     * @param string $migrationTable Table name without prefix (default: plugin_migrations)
     */
    public function __construct(string $migrationTable = 'plugin_migrations')
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->migrationTable = $migrationTable;
        $this->tableName = $wpdb->prefix . $migrationTable;

        $this->createMigrationsTable();
    }

    /**
     * Create the migrations tracking table if it does not exist.
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        $schema = new Schema($this->migrationTable);

        // Create table with migration name and batch/timestamps
        $schema->increments('id');
        $schema->string('migration', 191);
        $schema->integer('batch');
        $schema->dateTime('created_at');
        $schema->build();
    }

    /**
     * Run an array of migration instances.
     *
     * Migration classes should have a `up()` method to create/update tables.
     *
     * @param object[] $migrations Array of migration class instances.
     * @return void
     */
    public function runMigrations(array $migrations): void
    {
        foreach ($migrations as $migration) {
            $migrationName = get_class($migration);

            if ($this->hasRun($migrationName)) {
                continue; // Skip if already run
            }

            $migration->up();

            // Record the migration run with batch number
            $batch = $this->getCurrentBatch() + 1;
            $this->logMigration($migrationName, $batch);
        }
    }

    /**
     * Check if a migration has already been run.
     *
     * @param string $migrationName Fully qualified class name.
     * @return bool
     */
    protected function hasRun(string $migrationName): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE migration = %s", $migrationName)
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
        return (int) $this->wpdb->get_var("SELECT MAX(batch) FROM {$this->tableName}") ?: 0;
    }

    /**
     * Log the migration as run.
     *
     * @param string $migrationName
     * @param int $batch
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
