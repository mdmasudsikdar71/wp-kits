<?php

namespace MDMasudSikdar\WpKits\Database;

use MDMasudSikdar\WpKits\Traits\ForeignKeyTrait;

/**
 * Class Schema
 *
 * Provides a clean and fluent API for managing database schemas in WordPress plugins.
 *
 * Features:
 * ✅ Fluent table creation and modification syntax
 * ✅ Column type helpers (increments, string, integer, boolean, timestamps, etc.)
 * ✅ Supports foreign keys and indexes
 * ✅ Automatic handling of table prefixes and charset
 *
 * Responsibilities:
 * 1. `create()`  - Create a new table with columns and constraints
 * 2. `drop()`    - Drop a table safely
 *
 * @package MDMasudSikdar\WpKits\Database
 */
class Schema
{
    use ForeignKeyTrait;

    /**
     * WordPress database object.
     *
     * @var \wpdb
     * @example global $wpdb; $wpdb->prefix; $wpdb->get_var();
     */
    protected \wpdb $wpdb;

    /**
     * Full table name including WordPress prefix.
     *
     * @var string
     * @example 'wp_my_table'
     */
    protected string $table;

    /**
     * Columns definitions for the table.
     *
     * @var string[]
     * @example $this->columns[] = "`id` mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY";
     */
    protected array $columns = [];

    /**
     * Character set and collation for the table.
     *
     * @var string
     * @example $this->charset_collate = $wpdb->get_charset_collate();
     */
    protected string $charset_collate;

    /**
     * Schema constructor.
     *
     * Initializes the schema with a table name and sets up the database object.
     *
     * @param string $table Table name without prefix
     * @example new Schema('my_table');
     */
    public function __construct(string $table)
    {
        global $wpdb; // Access the global WordPress database object
        $this->wpdb = $wpdb; // Store it in the class property for later use

        // Combine the WP prefix and table name for the full table identifier
        $this->table = $this->wpdb->prefix . $table;

        // Default charset/collation from WP settings
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    /**
     * Create a table if it does not already exist.
     *
     * This method checks whether the table exists, and if it doesn't,
     * it runs the callback where columns can be defined.
     *
     * @param string $table Table name without prefix
     * @param callable $callback Callback function to define columns
     * @return void
     * @example
     * Schema::create('my_table', function($table) {
     *     $table->increments('id');
     * });
     */
    public static function create(string $table, callable $callback): void
    {
        // Create a new instance of the Schema class for this table
        $schema = new static($table);

        // Check if the table already exists in the database
        // If it does, exit early to avoid overwriting
        if ($schema->wpdb->get_var("SHOW TABLES LIKE '{$schema->table}'")) {
            return;
        }

        // Table does not exist, run the callback to define columns
        $callback($schema);

        // Call build() to actually create the table in the database
        $schema->build();
    }

    /**
     * Drop a table if it exists.
     *
     * This method checks whether the table exists, and if it does,
     * it safely drops it from the database using WordPress $wpdb.
     *
     * @param string $table Table name without prefix
     * @return void
     * @example
     * Schema::drop('my_table'); // Drops 'wp_my_table' if it exists
     */
    public static function drop(string $table): void
    {
        // Create a new instance of the Schema class for this table
        $schema = new static($table);

        // Check if the table exists in the database
        if ($schema->wpdb->get_var("SHOW TABLES LIKE '{$schema->table}'")) {
            // Drop the table safely
            $schema->wpdb->query("DROP TABLE {$schema->table}");
        }
    }

    /**
     * Add an auto-incrementing primary key column.
     *
     * @param string $name Column name, defaults to 'id'
     * @return $this
     * @example
     * $table->increments('id'); // Adds "id" as primary key
     */
    public function increments(string $name = 'id'): self
    {
        // Define the column as UNSIGNED, not null, auto-increment, and primary key
        $this->columns[] = "`$name` UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";

        // Return $this to allow method chaining like Laravel
        return $this;
    }

    /**
     * Add a string (VARCHAR) column to the table.
     *
     * @param string $name Column name
     * @param int $length Maximum length of the string, default 255
     * @param string|null $default Optional default value
     * @return $this
     * @example
     * $table->string('title'); // varchar(255) NOT NULL
     * $table->string('status', 50, 'draft'); // varchar(50) NOT NULL DEFAULT 'draft'
     */
    public function string(string $name, int $length = 255, ?string $default = null): self
    {
        // Start building the column definition
        $sql = "`$name` varchar($length) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            // Escape single quotes in default value
            $escaped = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escaped'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a CHAR column to the table.
     *
     * @param string $name Column name
     * @param int $length Length of the CHAR column, default 1
     * @param string|null $default Optional default value
     * @return $this
     * @example
     * $table->char('gender'); // CHAR(255) NOT NULL
     * $table->char('status', 10, 'active'); // CHAR(10) NOT NULL DEFAULT 'active'
     */
    public function char(string $name, int $length = 255, ?string $default = null): self
    {
        // Start building the column definition
        $sql = "`$name` CHAR($length) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            // Escape single quotes
            $escaped = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escaped'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow chaining
    }

    /**
     * Add a TEXT column to the table.
     *
     * @param string $name Column name
     * @param string|null $default Optional default value (rarely used for TEXT, can be ignored)
     * @return $this
     * @example
     * $table->text('description'); // TEXT NOT NULL
     */
    public function text(string $name, ?string $default = null): self
    {
        // Start building the column definition
        $sql = "`$name` TEXT NOT NULL";

        // Add default value if provided (not recommended for TEXT, MySQL may ignore)
        if ($default !== null) {
            $escaped = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escaped'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a LONGTEXT column to the table.
     *
     * @param string $name Column name
     * @param string|null $default Optional default value (rarely used for LONGTEXT)
     * @return $this
     * @example
     * $table->longText('content'); // LONGTEXT NOT NULL
     */
    public function longText(string $name, ?string $default = null): self
    {
        // Start building the column definition
        $sql = "`$name` LONGTEXT NOT NULL";

        // Add default value if provided (MySQL may ignore defaults for LONGTEXT)
        if ($default !== null) {
            $escaped = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escaped'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a JSON column to the table.
     *
     * @param string $name Column name
     * @param string|null $default Optional default value (JSON string)
     * @return $this
     * @example
     * $table->json('meta'); // JSON NOT NULL
     * $table->json('settings', '{}'); // JSON NOT NULL with default '{}'
     */
    public function json(string $name, ?string $default = null): self
    {
        // Start building the column definition
        $sql = "`$name` JSON NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            // Escape single quotes in default value
            $escaped = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escaped'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a BLOB column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->blob('data'); // BLOB NOT NULL
     */
    public function blob(string $name): self
    {
        // Column definition for BLOB
        $sql = "`$name` BLOB NOT NULL";

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a MEDIUMBLOB column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->mediumBlob('data'); // MEDIUMBLOB NOT NULL
     */
    public function mediumBlob(string $name): self
    {
        // Column definition for MEDIUMBLOB
        $sql = "`$name` MEDIUMBLOB NOT NULL";

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a LONGBLOB column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->longBlob('data'); // LONGBLOB NOT NULL
     */
    public function longBlob(string $name): self
    {
        // Column definition for LONGBLOB
        $sql = "`$name` LONGBLOB NOT NULL";

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add an INTEGER column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->integer('count'); // INT NOT NULL
     */
    public function integer(string $name): self
    {
        // Column definition without default value
        $sql = "`$name` INT NOT NULL";

        // Add column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a TINYINT column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->tinyInteger('status'); // TINYINT NOT NULL
     */
    public function tinyInteger(string $name): self
    {
        $sql = "`$name` TINYINT NOT NULL";

        $this->columns[] = $sql;

        return $this;
    }

    /**
     * Add a BIGINT column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->bigInteger('views'); // BIGINT NOT NULL
     */
    public function bigInteger(string $name): self
    {
        $sql = "`$name` BIGINT NOT NULL";

        $this->columns[] = $sql;

        return $this;
    }

    /**
     * Add a FLOAT column to the table.
     *
     * @param string $name Column name
     * @param int $precision Total digits (optional, default 8)
     * @param int $scale Digits after the decimal (optional, default 2)
     * @param float|null $default Optional default value
     * @return $this
     * @example
     * $table->float('price'); // FLOAT(8,2) NOT NULL
     * $table->float('discount', 5, 2, 0.00); // FLOAT(5,2) NOT NULL DEFAULT 0.00
     */
    public function float(string $name, int $precision = 8, int $scale = 2, ?float $default = null): self
    {
        // Build column definition
        $sql = "`$name` FLOAT($precision,$scale) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            $sql .= " DEFAULT $default";
        }

        // Add the column to the schema
        $this->columns[] = $sql;

        return $this; // Allow chaining
    }

    /**
     * Add a DOUBLE column to the table.
     *
     * @param string $name Column name
     * @param int $precision Total digits (optional, default 16)
     * @param int $scale Digits after the decimal (optional, default 4)
     * @param float|null $default Optional default value
     * @return $this
     * @example
     * $table->double('rating'); // DOUBLE(16,4) NOT NULL
     * $table->double('score', 10, 2, 0.00); // DOUBLE(10,2) NOT NULL DEFAULT 0.00
     */
    public function double(string $name, int $precision = 16, int $scale = 4, ?float $default = null): self
    {
        // Build column definition
        $sql = "`$name` DOUBLE($precision,$scale) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            $sql .= " DEFAULT $default";
        }

        // Add the column to the schema
        $this->columns[] = $sql;

        return $this; // Allow chaining
    }

    /**
     * Add a DECIMAL column to the table.
     *
     * @param string $name Column name
     * @param int $precision Total digits (optional, default 8)
     * @param int $scale Digits after the decimal (optional, default 2)
     * @param float|string|null $default Optional default value
     * @return $this
     * @example
     * $table->decimal('price'); // DECIMAL(8,2) NOT NULL
     * $table->decimal('tax', 5, 2, 0.00); // DECIMAL(5,2) NOT NULL DEFAULT 0.00
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2, mixed $default = null): self
    {
        // Build column definition
        $sql = "`$name` DECIMAL($precision,$scale) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            $sql .= " DEFAULT $default";
        }

        // Add the column to the schema
        $this->columns[] = $sql;

        return $this; // Allow chaining
    }

    /**
     * Add an ENUM column to the table.
     *
     * @param string $name Column name
     * @param array $values Allowed values for the enum
     * @param string|null $default Optional default value (must be one of $values)
     * @return $this
     * @example
     * $table->enum('status', ['draft', 'published', 'archived']);
     * $table->enum('role', ['admin', 'editor', 'user'], 'user');
     */
    public function enum(string $name, array $values, ?string $default = null): self
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Enum values array cannot be empty.');
        }

        // Escape values and join as string
        $escapedValues = array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $values);
        $valuesList = implode(',', $escapedValues);

        // Start building column definition
        $sql = "`$name` ENUM($valuesList) NOT NULL";

        // Add default value if provided
        if ($default !== null) {
            if (!in_array($default, $values, true)) {
                throw new \InvalidArgumentException("Default value '{$default}' is not in enum values.");
            }
            $escapedDefault = str_replace("'", "''", $default);
            $sql .= " DEFAULT '$escapedDefault'";
        }

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow chaining
    }

    /**
     * Add a BOOLEAN (TINYINT(1)) column to the table.
     *
     * @param string $name Column name
     * @return $this
     * @example
     * $table->boolean('is_active'); // TINYINT(1) NOT NULL
     */
    public function boolean(string $name): self
    {
        // Column definition for boolean
        $sql = "`$name` TINYINT(1) NOT NULL";

        // Add the column definition to the columns array
        $this->columns[] = $sql;

        return $this; // Allow method chaining
    }

    /**
     * Add a DATE column to the table.
     *
     * DATE stores only the date (YYYY-MM-DD) without time.
     * Useful for birthdays, deadlines, or other date-only fields.
     *
     * @param string $name Column name
     * @return $this Fluent chainable
     *
     * @example
     * $table->date('birthday'); // DATE NOT NULL
     */
    public function date(string $name): self
    {
        // Start building the column definition
        $sql = "`$name` DATE NOT NULL";

        // Add this column definition to the schema
        $this->columns[] = $sql;

        // Return $this for chaining
        return $this;
    }

    /**
     * Add a TIME column to the table.
     *
     * TIME stores only the time portion (HH:MM:SS).
     * Useful for schedules, working hours, or event times.
     *
     * @param string $name Column name
     * @return $this Fluent chainable
     *
     * @example
     * $table->time('start_time'); // TIME NOT NULL
     */
    public function time(string $name): self
    {
        // Build column definition with NOT NULL constraint
        $sql = "`$name` TIME NOT NULL";

        // Add the column to the schema
        $this->columns[] = $sql;

        // Allow chaining
        return $this;
    }

    /**
     * Add a DATETIME column to the table.
     *
     * DATETIME stores both date and time (YYYY-MM-DD HH:MM:SS),
     * without timezone. Useful for recording exact events.
     *
     * @param string $name Column name
     * @return $this Fluent chainable
     *
     * @example
     * $table->dateTime('published_at'); // DATETIME NOT NULL
     */
    public function dateTime(string $name): self
    {
        // Build DATETIME column definition with NOT NULL
        $sql = "`$name` DATETIME NOT NULL";

        // Add the column definition to the schema
        $this->columns[] = $sql;

        // Return $this to allow chaining with other methods
        return $this;
    }

    /**
     * Add a TIMESTAMP column to the table.
     *
     * TIMESTAMP is useful for tracking events with both date and time,
     * and it can optionally use CURRENT_TIMESTAMP as the default value.
     *
     * @param string $name Column name
     * @param bool $useCurrent Set CURRENT_TIMESTAMP as default if true
     * @return $this Fluent chainable
     *
     * @example
     * $table->timestamp('created_at'); // TIMESTAMP NOT NULL
     * $table->timestamp('updated_at', true); // TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
     */
    public function timestamp(string $name, bool $useCurrent = false): self
    {
        // Start building the column definition with NOT NULL
        $sql = "`$name` TIMESTAMP NOT NULL";

        // If requested, add default CURRENT_TIMESTAMP
        if ($useCurrent) {
            $sql .= " DEFAULT CURRENT_TIMESTAMP";
        }

        // Add this column definition to the columns array
        $this->columns[] = $sql;

        // Return $this to allow fluent chaining with other methods
        return $this;
    }

    /**
     * Add Laravel-style timestamp columns: `created_at` and `updated_at`.
     *
     * - `created_at`: TIMESTAMP with default CURRENT_TIMESTAMP
     * - `updated_at`: TIMESTAMP updated automatically on row update
     *
     * @return $this Fluent chainable
     * @example
     * $table->timestamps();
     * // Adds:
     * // `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
     * // `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
     */
    public function timestamps(): self
    {
        // Add `created_at` column: automatically set to current timestamp when row is created
        $this->columns[] = "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";

        // Add `updated_at` column: automatically updated to current timestamp on row update
        $this->columns[] = "`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        // Return $this for method chaining
        return $this;
    }

    /**
     * Add a `deleted_at` column to support soft deletes.
     *
     * - `deleted_at`: DATETIME, nullable
     * When this column is NULL, the row is considered active.
     * When it contains a timestamp, the row is considered "soft deleted".
     *
     * @return $this Fluent chainable
     * @example
     * $table->softDeletes();
     * // Adds:
     * // `deleted_at` DATETIME NULL
     */
    public function softDeletes(): self
    {
        // Add `deleted_at` column, nullable
        $this->columns[] = "`deleted_at` DATETIME NULL";

        // Return $this for method chaining
        return $this;
    }

    /**
     * Make the last defined column nullable.
     *
     * @return $this
     * @example
     * $table->string('title')->nullable(); // VARCHAR column becomes NULLABLE
     * $table->integer('count')->nullable(); // INT column becomes NULLABLE
     */
    public function nullable(): self
    {
        // If there are no columns defined, do nothing
        if (empty($this->columns)) {
            return $this;
        }

        // Get the last column definition
        $lastIndex = count($this->columns) - 1;
        $lastColumn = $this->columns[$lastIndex];

        // Replace NOT NULL with NULL in the last column definition
        $lastColumn = str_replace('NOT NULL', 'NULL', $lastColumn);

        // Update the last column in the array
        $this->columns[$lastIndex] = $lastColumn;

        return $this; // Allow chaining
    }

    /**
     * Add an index to one or more columns, or the last defined column if none specified.
     *
     * @param string|array|null $columns Column name(s) or null to use last defined column
     * @return $this
     * @example
     * $table->string('username')->index(); // column-level
     * $table->index(['first_name', 'last_name']); // multi-column
     */
    public function index(string|array|null $columns = null): self
    {
        if ($columns === null && !empty($this->columns)) {
            // Use last defined column
            preg_match('/`([^`]+)`/', end($this->columns), $matches);
            $columns = $matches[1] ?? null;
        }

        // Normalize to array
        $columns = is_array($columns) ? $columns : [$columns];

        if (!empty($columns)) {
            $indexName = $this->table . '_' . implode('_', $columns) . '_index';
            $cols = implode('`,`', $columns);
            $this->columns[] = "INDEX `$indexName`(`$cols`)";
        }

        return $this; // Allow chaining
    }

    /**
     * Make the last defined column UNIQUE.
     *
     * @return $this
     * @example
     * $table->string('email')->unique(); // makes 'email' column unique
     */
    public function unique(): self
    {
        if (empty($this->columns)) {
            return $this;
        }

        // Get last column name from its definition
        $lastIndex = count($this->columns) - 1;
        preg_match('/`([^`]+)`/', $this->columns[$lastIndex], $matches);
        $column = $matches[1] ?? null;

        if ($column) {
            $uniqueName = "{$this->table}_{$column}_unique";
            $this->columns[] = "UNIQUE `$uniqueName`(`$column`)";
        }

        return $this; // Allow chaining
    }

    /**
     * Set a default value for the last defined column.
     *
     * @param mixed $value Default value (string, int, etc.)
     * @return $this
     * @example
     * $table->string('status')->default('draft'); // Sets default for last column
     * $table->integer('count')->default(0);       // Sets default for last column
     */
    public function default(mixed $value): self
    {
        if (empty($this->columns)) {
            return $this;
        }

        // Get the last column definition
        $lastIndex = count($this->columns) - 1;
        $lastColumn = $this->columns[$lastIndex];

        // Remove existing DEFAULT if present
        $lastColumn = preg_replace('/\s+DEFAULT\s+[^ ]+/i', '', $lastColumn);

        // Determine how to format the value
        if (is_string($value)) {
            $escaped = str_replace("'", "''", $value); // escape single quotes
            $default = "'$escaped'";
        } elseif (is_bool($value)) {
            $default = $value ? '1' : '0';
        } else {
            $default = $value;
        }

        // Append DEFAULT clause
        $lastColumn .= " DEFAULT $default";

        // Update last column
        $this->columns[$lastIndex] = $lastColumn;

        return $this; // Allow chaining
    }

    /**
     * Set a primary key on one or more columns.
     *
     * If no columns are provided, the last defined column is used.
     *
     * @param string|array|null $columns Column name(s) or null for last defined column
     * @return $this
     * @example
     * $table->increments('id')->primary(); // Uses last column 'id' as primary key
     * $table->primary(['first_name', 'last_name']); // Composite primary key
     */
    public function primary(string|array|null $columns = null): self
    {
        // If no columns provided, use last defined column
        if ($columns === null) {
            if (empty($this->columns)) {
                return $this; // Nothing to do
            }
            // Extract the last column name from its definition
            preg_match('/`([^`]+)`/', end($this->columns), $matches);
            $columns = $matches[1] ?? null;
            if ($columns === null) {
                return $this;
            }
        }

        // Normalize to array
        $columns = is_array($columns) ? $columns : [$columns];

        // Generate primary key SQL
        if (!empty($columns)) {
            $primaryName = $this->table . '_primary';
            $cols = implode('`,`', $columns);
            $this->columns[] = "PRIMARY KEY `$primaryName`(`$cols`)";
        }

        return $this;
    }

    /**
     * Add a comment to the last defined column.
     *
     * MySQL `COMMENT` syntax is appended to the column definition.
     *
     * @param string $text Comment text
     * @return $this
     * @example
     * $table->string('username')->comment('The unique username of the user');
     */
    public function comment(string $text): self
    {
        if (empty($this->columns)) {
            return $this; // Nothing to comment on
        }

        // Get last column
        $lastIndex = count($this->columns) - 1;
        $lastColumn = $this->columns[$lastIndex];

        // Escape single quotes in comment
        $escaped = str_replace("'", "''", $text);

        // Append COMMENT clause
        $lastColumn .= " COMMENT '$escaped'";

        // Update last column
        $this->columns[$lastIndex] = $lastColumn;

        return $this; // Allow chaining
    }

    /**
     * Set the column position using AFTER.
     *
     * Moves the last defined column after another column in the table.
     *
     * @param string $column Column name after which the last column should be placed
     * @return $this
     * @example
     * $table->string('middle_name')->after('first_name'); // Places middle_name after first_name
     */
    public function after(string $column): self
    {
        if (empty($this->columns)) {
            return $this; // No column to position
        }

        // Get the last column definition
        $lastIndex = count($this->columns) - 1;
        $lastColumn = $this->columns[$lastIndex];

        // Append AFTER clause
        $lastColumn .= " AFTER `$column`";

        // Update the last column definition
        $this->columns[$lastIndex] = $lastColumn;

        return $this; // Allow chaining
    }

    /**
     * Build the table in the database.
     *
     * Generates the CREATE TABLE SQL from the defined columns
     * and executes it using WordPress' dbDelta function.
     *
     * @return void
     * @example
     * $schema->build(); // Executes the SQL and creates the table
     */
    public function build(): void
    {
        // If the Schema class uses ForeignKeyTrait, append pending foreign keys
        if (method_exists($this, 'appendForeignKeys')) {
            $this->appendForeignKeys();
        }

        // Join all column definitions into a single SQL string
        $columns_sql = implode(",\n", $this->columns);

        // Build the full CREATE TABLE SQL statement
        $sql = "CREATE TABLE {$this->table} (\n$columns_sql\n) {$this->charset_collate};";

        // Include the WordPress upgrade functions to use dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Execute the SQL safely, creating or updating the table
        dbDelta($sql);
    }

    /**
     * Alter an existing table with multiple operations.
     *
     * Provides a Schema instance for the table, allowing you to:
     * - Add new columns using methods like string(), integer(), etc.
     * - Modify existing columns using modifyColumn()
     * - Drop columns using dropColumn()
     * - Rename columns using renameColumn()
     *
     * All ALTER statements are collected and executed in buildAlter().
     *
     * @param string $table Table name without prefix
     * @param callable $callback Callback to define table alterations
     * @return void
     * @example
     * Schema::alter('users', function($table) {
     *     $table->string('nickname')->nullable(); // Adds a new column
     *     $table->modifyColumn('age')->integer()->nullable()->default(0); // Modify existing column
     *     $table->dropColumn('middle_name'); // Drop column
     *     $table->renameColumn('old_name', 'new_name'); // Rename column
     * });
     */
    public static function alter(string $table, callable $callback): void
    {
        $schema = new static($table);

        // Skip if the table does not exist
        if (!$schema->wpdb->get_var("SHOW TABLES LIKE '{$schema->table}'")) {
            return;
        }

        // Execute the user-defined callback to populate $schema->columns
        $callback($schema);

        // Execute all ALTER TABLE statements
        $schema->buildAlter();
    }

    /**
     * Rename an existing column.
     *
     * Stores the rename operation in $columns for execution in buildAlter().
     *
     * @param string $from Current column name
     * @param string $to New column name
     * @return $this Chainable for multiple operations
     * @example
     * $table->renameColumn('old_name', 'new_name');
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->columns[] = [
            'type' => 'rename',
            'from' => $from,
            'to' => $to
        ];

        return $this;
    }

    /**
     * Modify an existing column.
     *
     * Starts a MODIFY operation. Actual SQL will be generated in buildAlter().
     * Supports chainable methods for type, nullable, default, comment, etc.
     *
     * @param string $name Column name to modify
     * @return $this Chainable for multiple operations
     * @example
     * $table->modifyColumn('age')->integer()->nullable()->default(0);
     */
    public function modifyColumn(string $name): self
    {
        $this->columns[] = [
            'type' => 'modify',
            'definition' => "`$name`" // Placeholder, will be appended with full type & constraints
        ];

        return $this;
    }

    /**
     * Drop a column from the table.
     *
     * Adds a DROP COLUMN operation to $columns for execution in buildAlter().
     *
     * @param string $column Column name to drop
     * @return $this Chainable for multiple operations
     * @example
     * $table->dropColumn('middle_name');
     */
    public function dropColumn(string $column): self
    {
        $this->columns[] = [
            'type' => 'drop',
            'name' => $column
        ];

        return $this;
    }

    /**
     * Execute all accumulated table alterations.
     *
     * Iterates over the $columns array and executes the appropriate
     * ALTER TABLE SQL for each operation:
     * - ADD COLUMN (default)
     * - MODIFY COLUMN
     * - DROP COLUMN
     * - RENAME COLUMN
     *
     * Each operation is executed immediately using $wpdb->query().
     * Unknown types are safely skipped.
     *
     * @return void
     * @example
     * $table->string('nickname', 100)->nullable()->comment('Optional nickname');
     * $table->modifyColumn('age')->integer()->nullable()->default(0);
     * $table->dropColumn('middle_name');
     * $table->renameColumn('old_name', 'new_name');
     * $table->buildAlter(); // Executes all ALTER TABLE statements
     */
    public function buildAlter(): void
    {
        foreach ($this->columns as $column) {

            // Determine operation type, default to 'add'
            $type = $column['type'] ?? 'add';

            // Generate proper ALTER TABLE statement based on type
            if ($type === 'add') {
                $sql = "ALTER TABLE {$this->table} ADD COLUMN {$column['definition']}";
            } elseif ($type === 'modify') {
                $sql = "ALTER TABLE {$this->table} MODIFY COLUMN {$column['definition']}";
            } elseif ($type === 'drop') {
                $sql = "ALTER TABLE {$this->table} DROP COLUMN `{$column['name']}`";
            } elseif ($type === 'rename') {
                $sql = "ALTER TABLE {$this->table} RENAME COLUMN `{$column['from']}` TO `{$column['to']}`";
            } else {
                // Skip unknown operation type
                continue;
            }

            // Execute SQL query
            $this->wpdb->query($sql);
        }
    }
}
