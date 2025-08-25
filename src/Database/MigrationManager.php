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
 * ✅ Executes migrations only once per request
 * ✅ Supports rollback by batch
 * ✅ Uses WordPress $wpdb
 * ✅ Prevents duplicate execution/logging in the same request
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class MigrationManager
{
    use SingletonTrait;

    /**
     * WordPress database object, used to execute queries.
     *
     * @var \wpdb
     */
    protected \wpdb $wpdb;

    /**
     * Name of the migrations table without prefix.
     *
     * @var string
     */
    protected string $migrationTable = 'plugin_migrations';

    /**
     * Cache the current batch number for this request.
     *
     * @var int|null
     */
    protected static ?int $currentBatch = null;

    /**
     * Track migrations already executed in this request to avoid duplicates.
     *
     * @var array
     */
    protected static array $ranMigrations = [];

    /**
     * Private constructor for singleton pattern.
     *
     * @param string|null $migrationTable Optional table name (without prefix)
     */
    private function __construct(?string $migrationTable = null)
    {
        global $wpdb; // Get global WP database object

        $this->wpdb = $wpdb; // Store WP DB object in instance

        // If user passed a custom migration table, store it
        if ($migrationTable) {
            $this->migrationTable = $migrationTable;
        }

        // Ensure the migrations table exists in the database
        $this->createMigrationsTable();
    }

    /**
     * Create the migrations tracking table if it does not exist.
     *
     * Uses the Schema builder to create columns and handles
     * automatic prefixing internally.
     *
     * @return void
     */
    protected function createMigrationsTable(): void
    {
        // Compute full table name with WordPress prefix
        $fullTable = $this->wpdb->prefix . $this->migrationTable;

        // Check if table already exists in database
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$fullTable}'")) {
            // Table exists, skip creation
            return;
        }

        // Initialize Schema builder with table name (Schema adds prefix automatically)
        $schema = new Schema($this->migrationTable);

        $schema->increments();            // Add auto-increment primary key column 'id'
        $schema->string('migration');     // Add 'migration' column (name of migration class)
        $schema->integer('batch');        // Add 'batch' column (migration batch number)
        $schema->timestamp('created_at'); // Add 'created_at' column for timestamp

        // Build the table in database
        $schema->build();
    }

    /**
     * Run an array of migration instances.
     *
     * Executes `up()` on migrations that have not yet run.
     * Ensures same migration does not execute twice in a request.
     *
     * @param Migration[] $migrations Array of Migration instances
     * @return void
     */
    public static function runMigrations(array $migrations): void
    {
        // Get singleton instance
        $instance = self::init();

        // Cache batch number once per request to prevent double increments
        if (self::$currentBatch === null) {
            // Fetch highest batch from DB and increment by 1
            self::$currentBatch = $instance->getCurrentBatch() + 1;
        }

        // Iterate through all migrations passed
        foreach ($migrations as $migration) {
            // Skip if not a valid Migration object
            if (!$migration instanceof Migration) {
                continue;
            }

            // Get fully qualified class name
            $migrationName = get_class($migration);

            // Skip if migration has already run (DB or in-memory)
            if ($instance->hasRun($migrationName)) {
                continue;
            }

            // Execute migration's up() method
            $migration->up();

            // Log migration as executed in current batch
            $instance->logMigration($migrationName, self::$currentBatch);
        }
    }

    /**
     * Rollback latest migrations by batch.
     *
     * Can rollback multiple batches.
     *
     * @param int $steps Number of batches to rollback
     * @return void
     */
    public static function rollback(int $steps = 1): void
    {
        if ($steps < 1) {
            return; // No rollback needed
        }

        // Get singleton instance
        $instance = self::init();

        // Compute full table name
        $fullTable = $instance->wpdb->prefix . $instance->migrationTable;

        // Get latest batch numbers, descending
        $batches = $instance->wpdb->get_col(
            "SELECT DISTINCT batch FROM {$fullTable} ORDER BY batch DESC LIMIT $steps"
        );

        // Iterate through each batch to rollback
        foreach ($batches as $batch) {
            // Fetch migrations in batch in reverse order
            $migrations = $instance->wpdb->get_col(
                $instance->wpdb->prepare(
                    "SELECT migration FROM {$fullTable} WHERE batch = %d ORDER BY id DESC",
                    $batch
                )
            );

            // Rollback each migration
            foreach ($migrations as $migrationName) {
                // Skip if migration class doesn't exist
                if (!class_exists($migrationName)) {
                    continue;
                }

                // Instantiate migration
                $migration = new $migrationName();

                // Execute down() if exists
                if (method_exists($migration, 'down')) {
                    $migration->down();
                }

                // Delete migration log from DB
                $instance->wpdb->delete($fullTable, ['migration' => $migrationName]);
            }
        }
    }

    /**
     * Check if a migration has already run.
     *
     * Checks both in-memory for current request and database.
     *
     * @param string $migrationName Fully qualified class name
     * @return bool
     */
    protected function hasRun(string $migrationName): bool
    {
        // Check in-memory executed migrations for this request
        if (in_array($migrationName, self::$ranMigrations, true)) {
            return true;
        }

        // Query DB for migration existence
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}{$this->migrationTable} WHERE migration = %s",
                $migrationName
            )
        );

        return $count > 0;
    }

    /**
     * Get the current highest batch number in the database.
     *
     * @return int Current max batch number
     */
    protected function getCurrentBatch(): int
    {
        return (int) $this->wpdb->get_var(
            "SELECT MAX(batch) FROM {$this->wpdb->prefix}{$this->migrationTable}"
        ) ?: 0; // Default 0 if table empty
    }

    /**
     * Log a migration as applied.
     *
     * Stores migration in-memory and in database with batch number and timestamp.
     *
     * @param string $migrationName Fully qualified class name
     * @param int $batch Batch number
     * @return void
     */
    protected function logMigration(string $migrationName, int $batch): void
    {
        // Track in-memory to prevent double execution in same request
        self::$ranMigrations[] = $migrationName;

        // Insert record into migrations table
        $this->wpdb->insert(
            $this->wpdb->prefix . $this->migrationTable,
            [
                'migration' => $migrationName,
                'batch' => $batch,
                'created_at' => current_time('mysql'),
            ]
        );
    }
}
