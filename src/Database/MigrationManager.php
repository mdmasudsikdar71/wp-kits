<?php

namespace MDMasudSikdar\WpKits\Database;

use MDMasudSikdar\WpKits\Traits\SingletonTrait;

/**
 * Class MigrationManager
 *
 * Manages plugin database migrations and tracks applied migrations.
 *
 * Usage:
 * ```php
 * $migrator = MigrationManager::init(); // Singleton instance
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
    use SingletonTrait;

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
    protected string $migrationTable = 'plugin_migrations';

    /**
     * Full table name with prefix.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Private constructor for singleton.
     *
     * @param string|null $migrationTable Optional table name without prefix
     */
    private function __construct(?string $migrationTable = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        if ($migrationTable) {
            $this->migrationTable = $migrationTable;
        }

        $this->tableName = $wpdb->prefix . $this->migrationTable;

        $this->createMigrationsTable();
    }

    /**
     * Create the migrations tracking table if it does not exist.
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        // Get the table name safely for SQL
        $table = esc_sql($this->tableName);

        // Check if table exists; returns table name or null
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
            return; // Table already exists, skip creation
        }

        $schema = new Schema($this->migrationTable);

        // Define columns
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
    public static function runMigrations(array $migrations): void
    {
        $instance = static::init(); // Get singleton instance
        foreach ($migrations as $migration) {
            $migrationName = get_class($migration);

            if ($instance->hasRun($migrationName)) {
                continue; // Skip if already run
            }

            $migration->up();

            // Record the migration run with batch number
            $batch = $instance->getCurrentBatch() + 1;
            $instance->logMigration($migrationName, $batch);
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
