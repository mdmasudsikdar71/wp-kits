<?php


namespace MDMasudSikdar\WpKits\Database;

use wpdb;

/**
 * Class Schema
 *
 * Fluent interface for creating and modifying database tables using WordPress dbDelta.
 *
 * Usage:
 * ```php
 * Schema::create('my_table', function($table) {
 *    $table->increments('id');
 *    $table->string('title', 255);
 *    $table->integer('count')->default(0);
 *    $table->timestamps();
 * });
 * ```
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class Schema
{
    /**
     * @var wpdb
     */
    protected wpdb $wpdb;

    /**
     * @var string The table name (without WP prefix)
     */
    protected string $table;

    /**
     * @var string[]
     */
    protected array $columns = [];

    /**
     * @var string[]
     */
    protected array $primaryKeys = [];

    /**
     * @var string[]
     */
    protected array $indexes = [];

    /**
     * Schema constructor.
     *
     * @param string $table Table name without prefix
     */
    public function __construct(string $table)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . $table;
    }

    /**
     * Create a new schema instance and run the callback.
     *
     * @param string $table Table name without prefix
     * @param callable $callback Callback with Schema instance
     * @return void
     */
    public static function create(string $table, callable $callback): void
    {
        $schema = new static($table);
        $callback($schema);
        $schema->build();
    }

    /**
     * Drop a table
     *
     * Can be called statically:
     * ```php
     * Schema::drop('my_table'); // 'my_table' without prefix
     * ```
     *
     * @param string $table Table name without prefix
     * @return void
     */
    public static function drop(string $table): void
    {
        $schema = new static($table);
        $schema->wpdb->query("DROP TABLE IF EXISTS {$schema->table}");
    }

    /**
     * Add an auto-incrementing primary key column (usually 'id' INT UNSIGNED AUTO_INCREMENT PRIMARY KEY).
     *
     * @param string $name Column name
     * @return $this
     */
    public function increments(string $name = 'id'): self
    {
        $this->columns[] = "`$name` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }

    /**
     * Add an integer column.
     *
     * @param string $name Column name
     * @param bool $unsigned
     * @param int|null $default Default value or null for none
     * @return $this
     */
    public function integer(string $name, bool $unsigned = true, ?int $default = null): self
    {
        $col = "`$name` INT";
        if ($unsigned) {
            $col .= " UNSIGNED";
        }
        $col .= " NOT NULL";
        if ($default !== null) {
            $col .= " DEFAULT " . intval($default);
        }
        $this->columns[] = $col;
        return $this;
    }

    /**
     * Add a varchar column.
     *
     * @param string $name Column name
     * @param int $length Length of varchar
     * @param string|null $default Default value or null for none
     * @return $this
     */
    public function string(string $name, int $length = 191, ?string $default = null): self
    {
        $col = "`$name` VARCHAR($length) NOT NULL";
        if ($default !== null) {
            $col .= " DEFAULT '" . esc_sql($default) . "'";
        }
        $this->columns[] = $col;
        return $this;
    }

    /**
     * Add a text column.
     *
     * @param string $name Column name
     * @return $this
     */
    public function text(string $name): self
    {
        $this->columns[] = "`$name` TEXT NOT NULL";
        return $this;
    }

    /**
     * Add a datetime column.
     *
     * @param string $name Column name
     * @return $this
     */
    public function dateTime(string $name): self
    {
        $this->columns[] = "`$name` DATETIME NOT NULL";
        return $this;
    }

    /**
     * Add created_at and updated_at timestamps.
     *
     * @return $this
     */
    public function timestamps(): self
    {
        $this->columns[] = "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }

    /**
     * Add an index to a column.
     *
     * @param string $column Column name
     * @param string|null $indexName Optional index name
     * @return $this
     */
    public function index(string $column, ?string $indexName = null): self
    {
        $indexName = $indexName ?: $this->table . "_{$column}_index";
        $this->indexes[] = "KEY `$indexName` (`$column`)";
        return $this;
    }

    /**
     * Alter an existing table.
     *
     * Usage:
     * ```php
     * Schema::alter('my_table', function($table) {
     *    $table->addColumn('new_col', 'VARCHAR(100) NOT NULL DEFAULT ""');
     *    $table->dropColumn('old_col');
     *    $table->addIndex('new_col');
     * });
     * ```
     *
     * @param string $table Table name without prefix
     * @param callable $callback
     * @return void
     */
    public static function alter(string $table, callable $callback): void
    {
        $schema = new static($table);
        $callback($schema);
        $schema->runAlter();
    }

    /**
     * Add a raw column definition for ALTER TABLE
     *
     * Example: $table->addColumn('status', 'TINYINT(1) NOT NULL DEFAULT 0');
     *
     * @param string $name
     * @param string $definition
     * @return $this
     */
    public function addColumn(string $name, string $definition): self
    {
        $this->columns[] = "`$name` $definition";
        return $this;
    }

    /**
     * Drop a column in ALTER TABLE
     *
     * @param string $name
     * @return $this
     */
    public function dropColumn(string $name): self
    {
        $this->columns[] = "DROP COLUMN `$name`";
        return $this;
    }

    /**
     * Add an index in ALTER TABLE
     *
     * @param string $column
     * @param string|null $indexName
     * @return $this
     */
    public function addIndex(string $column, ?string $indexName = null): self
    {
        $indexName = $indexName ?: $this->table . "_{$column}_index";
        $this->indexes[] = "ADD KEY `$indexName` (`$column`)";
        return $this;
    }

    /**
     * Drop an index in ALTER TABLE
     *
     * @param string $indexName
     * @return $this
     */
    public function dropIndex(string $indexName): self
    {
        $this->indexes[] = "DROP INDEX `$indexName`";
        return $this;
    }

    /**
     * Execute ALTER TABLE with all queued columns/indexes
     *
     * @return void
     */
    protected function runAlter(): void
    {
        $alterParts = array_merge($this->columns, $this->indexes);
        if (empty($alterParts)) return;

        $sql = "ALTER TABLE {$this->table} " . implode(", ", $alterParts) . ";";
        $this->wpdb->query($sql);
    }

    /**
     * Create a table if it does not already exist.
     *
     * This method first checks whether the table with the WordPress prefix
     * already exists in the database. If it does, the callback is skipped
     * and no SQL is executed. If the table does not exist, it calls
     * `Schema::create()` to build the table using the provided callback.
     *
     * Usage:
     * ```php
     * Schema::createIfNotExists('plugin_migrations', function($table) {
     *     $table->increments('id');
     *     $table->string('migration', 191);
     *     $table->integer('batch');
     *     $table->dateTime('created_at');
     * });
     * ```
     *
     * @param string   $table    Table name **without** WordPress prefix.
     * @param callable $callback Callback that receives the Schema instance to define columns, indexes, etc.
     *
     * @return void
     */
    public static function createIfNotExists(string $table, callable $callback): void
    {
        $schema = new static($table);

        // Check if table already exists; skip if true
        if ($schema->wpdb->get_var("SHOW TABLES LIKE '{$schema->table}'") === $schema->table) {
            return;
        }

        // Table does not exist, create it using Schema
        $callback($schema);
        $schema->build();
    }

    /**
     * Compile and run the SQL using dbDelta.
     *
     * @return void
     */
    public function build(): void
    {
        if (empty($this->columns)) return;

        $sql = "CREATE TABLE {$this->table} (\n";
        $sql .= implode(",\n", $this->columns);

        if (!empty($this->indexes)) {
            $sql .= ",\n" . implode(",\n", $this->indexes);
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
