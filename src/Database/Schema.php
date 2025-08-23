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
        $this->columns[] = "`$name` INT UNSIGNED NOT NULL AUTO_INCREMENT";
        $this->primaryKeys[] = $name;
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
