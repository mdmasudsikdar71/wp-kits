<?php

namespace MDMasudSikdar\WpKits\Database;

/**
 * Abstract Base Model for WordPress
 *
 * Provides a robust foundation for creating database-backed models
 * in WordPress plugins. This class integrates with the global `$wpdb` object
 * and provides convenient features for interacting with plugin tables.
 *
 * Features:
 * ✅ Access to `$wpdb` for safe queries
 * ✅ Automatic table prefix handling
 * ✅ Mass assignment protection via `$fillable` attributes
 * ✅ Magic getters and setters for attributes
 * ✅ Query builder support via `query()` method
 * ✅ Static calls proxy to instance methods using `__callStatic`
 * ✅ Easy CRUD operations: create, read, update, delete
 *
 * Responsibilities:
 * 1. Define the table name with `protected static $table`
 * 2. Define fillable fields for mass assignment with `protected $fillable`
 * 3. Provide reusable database query logic for child models
 *
 * @package MDMasudSikdar\WpKits\Models
 */
abstract class Model
{
    /**
     * WordPress database object.
     *
     * Provides access to WordPress database functions such as
     * get_results(), insert(), update(), delete(), etc.
     *
     * Initialized automatically in the constructor.
     *
     * @var \wpdb
     */
    protected \wpdb $db;

    /**
     * Base table name (without WordPress prefix)
     *
     * Child classes must set this to the table they represent,
     * e.g., 'posts', 'users', 'my_custom_table'.
     *
     * @var string
     */
    protected static string $table = '';

    /**
     * Fully prefixed table name (with WordPress $wpdb->prefix)
     *
     * Calculated in the constructor by combining $wpdb->prefix
     * and the child class's static $table property.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Primary key of the table
     *
     * Defaults to 'id'. Child classes can override if needed.
     *
     * @var string
     */
    protected static string $primaryKey = 'id';

    /**
     * Model attributes
     *
     * Holds the actual data of the current model instance.
     * Populated via fill() or database query results.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Mass assignable attributes
     *
     * Only the keys listed here can be set via fill()
     * to protect against mass assignment vulnerabilities.
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * Whether the model uses soft deletes.
     *
     * If true, queries will automatically exclude rows
     * where `deleted_at` is not NULL.
     *
     * @var bool
     */
    protected bool $softDeletes = false;

    /**
     * Whether to include soft-deleted rows in the current query.
     *
     * This is only temporary per query chain. Defaults to false,
     * meaning soft-deleted rows are excluded if soft deletes are enabled.
     *
     * @var bool
     */
    protected bool $includeTrashed = false;

    /**
     * Whether to automatically manage timestamps.
     *
     * If true, the model will automatically handle
     * `created_at` and `updated_at` fields.
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Column name for the "created at" timestamp.
     *
     * @var string
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * Column name for the "updated at" timestamp.
     *
     * @var string
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Column name for the "deleted at" timestamp.
     * Used only if soft deletes are enabled.
     *
     * @var string
     */
    protected string $deletedAtColumn = 'deleted_at';

    /**
     * Internal array of query conditions
     *
     * Each condition is an associative array with:
     * - type: 'basic' | 'in' | 'notIn' | 'null' | 'notNull'
     * - column: column name
     * - operator: '=', '>', etc. (for 'basic' type)
     * - value: value or array of values
     *
     * Example:
     * [
     *     ['type'=>'basic', 'column'=>'status', 'operator'=>'=', 'value'=>'active'],
     *     ['type'=>'in',    'column'=>'id', 'value'=>[1,2,3]],
     *     ['type'=>'notIn', 'column'=>'category_id', 'value'=>[4,5]],
     * ]
     *
     * @var array
     */
    protected array $wheres = [];

    /**
     * Columns to select in the query.
     *
     * Defaults to '*' (all columns).
     *
     * @var array
     */
    protected array $selectedColumns = ['*'];

    /**
     * Maximum number of rows to retrieve for the query.
     *
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * Current page for pagination.
     *
     * Used with setPerPage() to calculate SQL OFFSET.
     *
     * @var int|null
     */
    protected ?int $page = null;

    /**
     * Number of items per page for pagination.
     *
     * Example usage:
     * ```php
     * Post::query()->setPerPage(10)->simplePaginate();
     * ```
     *
     * @var int
     */
    protected int $perPage = 15; // default value

    /**
     * List of relationships to eager load.
     *
     * Used internally by the get() and first() methods when
     * eager loading related models via the with() method.
     *
     * @var array
     */
    protected array $eagerLoad = [];

    /**
     * Holds ORDER BY clauses for the query.
     *
     * Each entry is an array: [$column, $direction]
     *
     * Example:
     * $this->orderBy = [
     *     ['created_at', 'DESC'],
     *     ['title', 'ASC']
     * ];
     *
     * @var array
     */
    protected array $orderBy = [];

    /**
     * Constructor
     *
     * Initializes $wpdb for database access and calculates the
     * fully prefixed table name. This ensures all instance methods
     * have access to $db and $tableName.
     *
     * @return void
     */
    public function __construct()
    {
        // Bring WordPress $wpdb object into scope
        global $wpdb;

        // Assign $wpdb to the model's db property
        $this->db = $wpdb;

        // Combine WordPress table prefix with the child class table name
        $this->tableName = $this->db->prefix . static::$table;
    }

    /**
     * Start a new query for this model
     *
     * Returns an instance of the model that can be used
     * for chaining query methods such as where(), get(), etc.
     *
     * @return static
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Magic static caller
     *
     * Allows calling instance methods statically.
     * Example: Post::all() will create an instance and call all().
     *
     * @param string $method The method being called
     * @param array $args Arguments passed to the method
     * @return mixed The return value of the called instance method
     */
    public static function __callStatic(string $method, array $args)
    {
        // Create a temporary instance of the called class
        $instance = new static();

        // Call the instance method with provided arguments
        return $instance->$method(...$args);
    }

    /**
     * Fill model attributes
     *
     * Assigns values from an input array to the model's
     * attributes if they are listed in $fillable. Automatically
     * excludes system-managed columns like `created_at`,
     * `updated_at`, and `deleted_at`.
     *
     * @param array $attributes Key-value pairs to assign
     * @return void
     */
    public function fill(array $attributes): void
    {
        // List of system-managed columns we should NOT mass assign
        $guarded = [$this->createdAtColumn, $this->updatedAtColumn];

        if ($this->softDeletes) {
            $guarded[] = $this->deletedAtColumn;
        }

        foreach ($attributes as $key => $value) {
            // Skip guarded columns
            if (in_array($key, $guarded, true)) {
                continue;
            }

            // Assign only if key is in $fillable
            if (in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Magic getter
     *
     * Allows accessing model attributes via property syntax:
     * $model->attribute
     *
     * @param string $key Attribute name
     * @return mixed|null Returns value if set, otherwise null
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic setter
     *
     * Allows setting model attributes via property syntax:
     * $model->attribute = $value
     *
     * @param string $key Attribute name
     * @param mixed $value Value to set
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Include soft-deleted records in the query.
     *
     * By default, queries exclude rows where `deleted_at` is not NULL
     * if soft deletes are enabled. Calling this method disables that
     * filter so "trashed" rows are also included.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->withTrashed()->all();
     * ```
     *
     * @return static
     */
    public function withTrashed(): static
    {
        // Flip a temporary flag to disable soft-delete filtering
        $this->includeTrashed = true;

        // Return the current instance for chaining
        return $this;
    }

    /**
     * Set a LIMIT for the query.
     *
     * Example usage:
     * ```php
     * Post::query()->where('status', 'active')->limit(10)->get();
     * ```
     *
     * @param int $number Number of rows to limit
     * @return static
     */
    public function limit(int $number): static
    {
        $this->limit = $number;
        return $this;
    }

    /**
     * Alias for limit() to mimic Laravel's take().
     *
     * Example usage:
     * ```php
     * Post::query()->where('status', 'active')->take(5)->get();
     * ```
     *
     * @param int $number Number of rows to take
     * @return static
     */
    public function take(int $number): static
    {
        return $this->limit($number);
    }

    /**
     * Retrieve all records matching the current query conditions,
     * including any eager-loaded relationships.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()
     *     ->where('status', 'active')
     *     ->with(['author', 'comments'])
     *     ->get();
     * ```
     *
     * @return array Array of associative arrays or model instances with relations
     */
    public function get(): array
    {
        // Fetch raw rows using the current query builder conditions
        $rows = $this->fetchRows(); // use your existing query builder logic

        // If no eager-loaded relationships, return rows as-is
        if (empty($this->eagerLoad)) {
            return $rows;
        }

        // Loop through each row and attach the requested relationships
        foreach ($rows as &$row) {
            foreach ($this->eagerLoad as $relation) {
                // Check if the relation method exists on the model
                if (method_exists($this, $relation)) {
                    // Call the relation method and attach the result
                    $row[$relation] = $this->$relation();
                }
            }
        }

        return $rows;
    }

    /**
     * Retrieve the first record matching the current query conditions,
     * including any eager-loaded relationships.
     *
     * Example usage:
     * ```php
     * $post = Post::query()
     *     ->where('status', 'active')
     *     ->with(['author', 'comments'])
     *     ->first();
     * ```
     *
     * @return array|null Associative array of the first row with relations, or null
     */
    public function first(): ?array
    {
        // Fetch the first row using the current query builder logic
        $row = $this->fetchFirstRow();

        // If no row or no eager-loaded relationships, return as-is
        if (!$row || empty($this->eagerLoad)) {
            return $row;
        }

        // Attach eager-loaded relationships
        foreach ($this->eagerLoad as $relation) {
            if (method_exists($this, $relation)) {
                $row[$relation] = $this->$relation();
            }
        }

        return $row;
    }

    /**
     * Retrieve multiple records by an array of primary keys.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->findMany([1, 2, 3]);
     * ```
     *
     * @param array $ids Array of primary key values to find
     * @return array Array of associative arrays representing rows
     */
    public function findMany(array $ids): array
    {
        // If no IDs provided, return empty array early
        if (empty($ids)) {
            return [];
        }

        // Escape each ID for safety
        $escapedIds = array_map('esc_sql', $ids);

        // Build the WHERE IN clause for primary key
        $this->wheres[] = [
            'type' => 'in',
            'column' => static::$primaryKey,
            'value' => $escapedIds,
        ];

        // Use get() to execute query with all conditions including soft deletes
        return $this->get();
    }

    /**
     * Fetch multiple rows using current query builder state.
     *
     * @return array
     */
    protected function fetchRows(): array
    {
        return $this->db->get_results($this->buildQuerySql(), ARRAY_A);
    }

    /**
     * Fetch the first row using current query builder state.
     *
     * @return array|null
     */
    protected function fetchFirstRow(): ?array
    {
        $row = $this->db->get_row($this->buildQuerySql(1), ARRAY_A);
        return $row ?: null;
    }

    /**
     * Get the raw SQL string of the current query builder state.
     *
     * @return string
     */
    public function toSql(): string
    {
        return $this->buildQuerySql();
    }

    /**
     * Paginate the query results.
     *
     * Example usage:
     * ```php
     * $pagination = Post::query()
     *     ->where('status', 'active')
     *     ->paginate(10, 2); // 10 per page, page 2
     *
     * // $pagination structure:
     * // [
     * //   'data' => [...],      // array of rows
     * //   'current_page' => 2,
     * //   'per_page' => 10,
     * //   'total' => 55,
     * //   'last_page' => 6
     * // ]
     * ```
     *
     * @param int $perPage Number of records per page
     * @param int|null $page Current page (1-based). Uses $this->page if not provided
     * @return array Structured pagination array
     */
    public function paginate(int $perPage = 15, ?int $page = null): array
    {
        // Set per-page limit
        $this->setPerPage($perPage);

        // Determine current page
        $currentPage = $page ?? $this->page ?? 1;
        $this->page($currentPage);

        // Get total count of records matching the current query
        $total = $this->count();

        // Fetch the actual rows for the current page
        $data = $this->get();

        // Calculate total pages
        $lastPage = (int) ceil($total / $perPage);

        // Return structured pagination array
        return [
            'data' => $data,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Simple pagination for the query results.
     *
     * Faster than paginate() because it does not calculate total count.
     * Useful for large datasets.
     *
     * Example usage:
     * ```php
     * $pagination = Post::query()
     *     ->where('status', 'active')
     *     ->simplePaginate(10, 2);
     *
     * // $pagination structure:
     * // [
     * //   'data' => [...],       // array of rows
     * //   'current_page' => 2,
     * //   'per_page' => 10,
     * //   'has_more' => true     // true if there are more pages
     * // ]
     * ```
     *
     * @param int $perPage Number of records per page
     * @param int|null $page Current page (1-based). Uses $this->page if not provided
     * @return array Structured pagination array
     */
    public function simplePaginate(int $perPage = 15, ?int $page = null): array
    {
        // Set per-page limit
        $this->setPerPage($perPage);

        // Determine current page
        $currentPage = $page ?? $this->page ?? 1;
        $this->page($currentPage);

        // Fetch one extra row to check if there are more pages
        $originalLimit = $this->limit;
        $this->limit($perPage + 1);

        // Get the rows for current page (+1 for checking next page)
        $rows = $this->get();

        // Determine if there’s more pages
        $hasMore = count($rows) > $perPage;

        // Only return the exact number of requested perPage rows
        $data = array_slice($rows, 0, $perPage);

        return [
            'data' => $data,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Specify relationships to eager load.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->with(['author', 'comments'])->get();
     * ```
     *
     * @param array|string $relations Relationship names to eager load
     * @return static
     */
    public function with(array|string $relations): static
    {
        // Normalize to array if string provided
        $relations = is_array($relations) ? $relations : [$relations];

        // Store the relationships for later use in get() or first()
        $this->eagerLoad = $relations;

        return $this;
    }

    /**
     * Retrieve values of a single column from the current query or model attributes.
     *
     * - Works for both single model instances and query builder results.
     * - Optionally allows specifying a key column to return key-value pairs.
     *
     * Example usage:
     * ```php
     * $titles = Post::query()->where('status', 'active')->pluck('title');
     * $keyed = Post::query()->pluck('title', 'id'); // key-value pairs
     * ```
     *
     * @param string $column Column name to retrieve
     * @param string|null $key Optional column to use as array keys
     * @return array Array of values or key-value pairs
     */
    public function pluck(string $column, ?string $key = null): array
    {
        // Convert the current model or query results to an array
        $rows = $this->toArray();

        $values = [];

        // Loop through each row to extract the requested column
        foreach ($rows as $row) {
            // Use key-value mapping if $key is specified and exists in row
            if ($key !== null && array_key_exists($key, $row)) {
                $values[$row[$key]] = $row[$column] ?? null;
            } else {
                // Otherwise just append the value
                $values[] = $row[$column] ?? null;
            }
        }

        return $values;
    }

    /**
     * Check if any record exists matching the current query.
     *
     * @return bool True if at least one row exists, false otherwise
     */
    public function exists(): bool
    {
        // Base SQL to select 1 (more efficient than selecting full row)
        $sql = "SELECT " . $this->buildSelectColumns('1') . " FROM {$this->tableName}";

        // Append WHERE clause using helper
        $sql .= $this->buildWhereClause();

        // Limit to 1 row for performance
        $sql .= " LIMIT 1";

        // Execute query and return true if any row exists
        return $this->db->get_var($sql) !== null;
    }

    /**
     * Count the number of records matching the current query.
     *
     * @return int Number of matching rows
     */
    public function count(): int
    {
        // Base SQL to count rows
        $sql = "SELECT " . $this->buildSelectColumns('COUNT(*)') . " FROM {$this->tableName}";

        // Append WHERE clause using helper
        $sql .= $this->buildWhereClause();

        // Execute query and return integer count
        return (int) $this->db->get_var($sql);
    }

    /**
     * Retrieve a record by primary key.
     *
     * Example usage:
     * ```php
     * $post = Post::query()->find(5);
     * ```
     *
     * Respects soft deletes unless `withTrashed()` is used.
     *
     * @param mixed $id Primary key value
     * @return array|null Associative array of row or null if not found
     */
    public function find(mixed $id): ?array
    {
        // Add primary key condition
        $this->where(static::$primaryKey, $id);

        // Return the first matching row
        return $this->first();
    }

    /**
     * Convert the current model or query results to an array.
     *
     * - If called on an instance with attributes, returns those attributes as an array.
     * - If called on a query builder, returns the results of get() as an array.
     *
     * Example usage:
     * ```php
     * $post = Post::find(1);
     * $array = $post->toArray(); // single record as array
     *
     * $postsArray = Post::query()->where('status', 'active')->toArray(); // multiple records
     * ```
     *
     * @return array Model attributes or query results
     */
    public function toArray(): array
    {
        // If the model instance has attributes, return them
        if (!empty($this->attributes)) {
            return $this->attributes;
        }

        // Otherwise, return the results from the current query
        return $this->get();
    }

    /**
     * Get model attributes excluding the given keys.
     *
     * Example usage:
     * ```php
     * $data = $post->except(['created_at', 'updated_at']);
     * ```
     *
     * @param array $keys List of attribute keys to exclude
     * @return array Attributes excluding the specified keys
     */
    public function except(array $keys): array
    {
        // Filter $this->attributes and remove keys listed in $keys
        return array_filter(
            $this->attributes,
            fn($attrKey) => !in_array($attrKey, $keys),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Order query results by a given column in descending order.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->latest()->get(); // order by created_at DESC
     * $posts = Post::query()->latest('updated_at')->get();
     * ```
     *
     * @param ?string $column Column to order by, defaults to created_at
     * @return static
     */
    public function latest(string $column = null): static
    {
        $column = $column ?? ($this->timestamps ? $this->createdAtColumn : static::$primaryKey);
        $this->orderBy[] = [$column, 'DESC'];
        return $this;
    }

    /**
     * Order query results by a given column in ascending order.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->oldest()->get(); // order by created_at ASC
     * $posts = Post::query()->oldest('updated_at')->get();
     * ```
     *
     * @param ?string $column Column to order by, defaults to created_at
     * @return static
     */
    public function oldest(string $column = null): static
    {
        $column = $column ?? ($this->timestamps ? $this->createdAtColumn : static::$primaryKey);
        $this->orderBy[] = [$column, 'ASC'];
        return $this;
    }

    /**
     * Convert the current model or query results to JSON.
     *
     * Relies on toArray() to get the data, then encodes it to JSON.
     *
     * Example usage:
     * ```php
     * $post = Post::find(1);
     * echo $post->toJson(); // single record
     *
     * $postsJson = Post::query()->where('status', 'active')->toJson(); // multiple records
     * ```
     *
     * @param int $options Optional JSON encoding options (default: JSON_UNESCAPED_UNICODE)
     * @return string JSON string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        // Convert model or query results to array first
        $data = $this->toArray();

        // Encode the array to JSON
        return json_encode($data, $options);
    }

    /**
     * Pluck values of a single column (optionally keyed by another column) and return as JSON.
     *
     * Example usage:
     * ```php
     * $titlesJson = Post::query()->where('status', 'active')->pluckJson('title');
     * $keyedJson = Post::query()->pluckJson('title', 'id'); // key-value JSON
     * ```
     *
     * @param string $column Column name to retrieve
     * @param string|null $key Optional column to use as array keys
     * @param int $options Optional JSON encoding options (default: JSON_UNESCAPED_UNICODE)
     * @return string JSON string of plucked values
     */
    public function pluckJson(string $column, ?string $key = null, int $options = JSON_UNESCAPED_UNICODE): string
    {
        // Use the existing pluck() method to get an array of values
        $values = $this->pluck($column, $key);

        // Encode the array as JSON
        return json_encode($values, $options);
    }

    /**
     * Add a WHERE condition to the query.
     *
     * Supports chaining multiple conditions. Allows operator-first style:
     * ```php
     * where('rank', '>', 1)
     * ```
     * or simple '=':
     * ```php
     * where('status', 'active')
     * ```
     *
     * Stores the condition in the internal $wheres array as type 'basic'.
     *
     * @param string $column   Column name
     * @param mixed  $operator_or_value Either operator (if 3 params) or value (if 2 params)
     * @param mixed|null $value    Value to compare (required if 3 params)
     * @return static
     */
    public function where(string $column, mixed $operator_or_value, mixed $value = null): static
    {
        // Determine operator and value based on parameters
        if ($value === null) {
            $operator = '=';
            $val = $operator_or_value;
        } else {
            $operator = $operator_or_value;
            $val = $value;
        }

        // Initialize $wheres array if not already
        if (!isset($this->wheres)) {
            $this->wheres = [];
        }

        // Add a 'basic' condition to $wheres
        $this->wheres[] = [
            'type'     => 'basic',
            'column'   => $column,
            'operator' => $operator,
            'value'    => $val
        ];

        return $this;
    }

    /**
     * Add an OR WHERE condition to the query.
     *
     * Example usage:
     * ```php
     * Post::query()
     *     ->where('status', 'active')
     *     ->orWhere('rank', '>', 5)
     *     ->get();
     * ```
     *
     * @param string $column Column name
     * @param mixed  $operator_or_value Either operator (if 3 params) or value (if 2 params)
     * @param mixed|null $value Value to compare (required if 3 params)
     * @return static
     */
    public function orWhere(string $column, mixed $operator_or_value, mixed $value = null): static
    {
        // Determine if operator is provided or using default '='
        if ($value === null) {
            // Only 2 params given → operator defaults to '='
            $operator = '=';
            $val = $operator_or_value;
        } else {
            // 3 params given → operator is second param
            $operator = $operator_or_value;
            $val = $value;
        }

        // Initialize wheres array if not set
        if (!isset($this->wheres)) {
            $this->wheres = [];
        }

        // Add OR condition
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $val,
            'boolean' => 'or', // mark this condition as OR
        ];

        return $this;
    }

    /**
     * Add a WHERE IN condition to the query.
     *
     * Supports chaining multiple conditions.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->whereIn('id', [1, 3, 5])->get();
     * ```
     *
     * Stores the condition in the internal $wheres array as type 'in'.
     *
     * @param string $column Column name
     * @param array  $values Array of values
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        // Initialize $wheres array if not already
        if (!isset($this->wheres)) {
            $this->wheres = [];
        }

        // Add an 'in' condition to $wheres
        $this->wheres[] = [
            'type'   => 'in',
            'column' => $column,
            'value'  => $values
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT IN condition to the query.
     *
     * Supports chaining multiple conditions.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->whereNotIn('category_id', [4, 5])->get();
     * ```
     *
     * Stores the condition in the internal $wheres array as type 'notIn'.
     *
     * @param string $column Column name
     * @param array  $values Array of values
     * @return static
     */
    public function whereNotIn(string $column, array $values): static
    {
        // Initialize $wheres array if not already
        if (!isset($this->wheres)) {
            $this->wheres = [];
        }

        // Add a 'notIn' condition to $wheres
        $this->wheres[] = [
            'type'   => 'notIn',
            'column' => $column,
            'value'  => $values
        ];

        return $this;
    }

    /**
     * Retrieve all records from the model's table.
     *
     * Respects soft deletes unless `withTrashed()` is applied.
     *
     * Example usage:
     * ```php
     * $posts = Post::all();               // fetch all non-deleted posts
     * $posts = Post::query()->all();      // same as above
     * $posts = Post::query()->withTrashed()->all(); // include deleted
     * ```
     *
     * @return array Array of associative arrays representing rows
     */
    public function all(): array
    {
        // Clear any previously applied where conditions
        $this->wheres = [];

        return $this->get();
    }

    /**
     * Save the current model to the database.
     *
     * - Performs an INSERT if the primary key is not set.
     * - Performs an UPDATE if the primary key exists.
     * - Automatically handles timestamps if enabled.
     *
     * @return bool True on success, false on failure
     */
    public function save(): bool
    {
        // Determine whether we are inserting or updating
        $id = $this->attributes[static::$primaryKey] ?? null;

        // Prepare data only with fillable attributes
        $data = [];
        foreach ($this->fillable as $key) {
            if (array_key_exists($key, $this->attributes)) {
                $data[$key] = $this->attributes[$key];
            }
        }

        // Manage timestamps if enabled
        if (!empty($this->timestamps)) {
            $now = current_time('mysql');

            // On insert, set created_at
            if (empty($id)) {
                $data[$this->createdAtColumn] = $now;
                $this->attributes[$this->createdAtColumn] = $now;
            }

            // Always update updated_at
            $data[$this->updatedAtColumn] = $now;
            $this->attributes[$this->updatedAtColumn] = $now;
        }

        // Update or Insert
        if (!empty($id)) {
            // Update existing record
            return $this->db->update(
                    $this->tableName,
                    $data,
                    [static::$primaryKey => $id]
                ) !== false;
        }

        // Insert new record
        $inserted = $this->db->insert($this->tableName, $data);
        if ($inserted !== false) {
            $this->attributes[static::$primaryKey] = $this->db->insert_id;
            return true;
        }

        return false;
    }

    /**
     * Insert a new record into the database.
     *
     * @return bool True on success, false on failure
     */
    public function insert(): bool
    {
        // Force save to insert
        $this->attributes[static::$primaryKey] = null;
        return $this->save();
    }

    /**
     * Update the current model in the database.
     *
     * @return bool True on success, false on failure
     */
    public function update(): bool
    {
        // Only save if primary key exists
        $id = $this->attributes[static::$primaryKey] ?? null;
        if (empty($id)) {
            return false;
        }

        return $this->save();
    }

    /**
     * Create a new model and save to database.
     *
     * @param array $attributes Attributes to fill
     * @return static|null Newly created model or null on failure
     */
    public static function create(array $attributes): ?static
    {
        $model = new static();
        $model->fill($attributes);

        return $model->insert() ? $model : null;
    }

    /**
     * Create a new record or update if it already exists.
     *
     * Example:
     * ```php
     * $post = Post::query()->updateOrCreate([
     *     'id' => 5,
     *     'title' => 'Updated Title'
     * ]);
     * ```
     *
     * @param array $values Attributes to insert or update
     * @return static
     */
    public function updateOrCreate(array $values): static
    {
        return $this->createOrUpdateInternal($values, true);
    }

    /**
     * Retrieve the first record matching the query or create it if none exists.
     *
     * Example:
     * ```php
     * $post = Post::query()->firstOrCreate([
     *     'title' => 'Hello World',
     *     'status' => 'draft'
     * ]);
     * ```
     *
     * @param array $defaults Attributes to insert if record doesn't exist
     * @return static
     */
    public function firstOrCreate(array $defaults = []): static
    {
        return $this->createOrUpdateInternal($defaults, false);
    }

    /**
     * Insert a new record if primary key is not set or update existing by primary key.
     *
     * Example:
     * ```php
     * $post = Post::query()->upsert([
     *     'id' => 5,
     *     'title' => 'Some Title'
     * ]);
     * ```
     *
     * @param array $attributes Attributes to insert or update
     * @return static
     */
    public static function upsert(array $attributes): static
    {
        $model = new static();
        return $model->createOrUpdateInternal($attributes, true);
    }

    /**
     * Increment a numeric column by a given value.
     *
     * Example usage:
     * ```php
     * Post::find(1)->increment('views');       // increment by 1
     * Post::find(1)->increment('likes', 5);    // increment by 5
     * ```
     *
     * @param string $column Column name to increment
     * @param int $amount Amount to increment (default 1)
     * @return bool True on success, false on failure
     */
    public function increment(string $column, int $amount = 1): bool
    {
        // Ensure the model has a primary key
        $id = $this->attributes[static::$primaryKey] ?? null;
        if (empty($id)) {
            return false;
        }

        // Current value of the column or 0
        $current = (int) ($this->attributes[$column] ?? 0);

        // New value after increment
        $newValue = $current + $amount;

        // Update the column in the database
        $updated = $this->db->update(
            $this->tableName,
            [$column => $newValue],
            [static::$primaryKey => $id]
        );

        if ($updated !== false) {
            // Update model attribute
            $this->attributes[$column] = $newValue;
            return true;
        }

        return false;
    }

    /**
     * Decrement a numeric column by a given value.
     *
     * Example usage:
     * ```php
     * Post::find(1)->decrement('stock');       // decrement by 1
     * Post::find(1)->decrement('stock', 3);    // decrement by 3
     * ```
     *
     * @param string $column Column name to decrement
     * @param int $amount Amount to decrement (default 1)
     * @return bool True on success, false on failure
     */
    public function decrement(string $column, int $amount = 1): bool
    {
        // Ensure the model has a primary key
        $id = $this->attributes[static::$primaryKey] ?? null;
        if (empty($id)) {
            return false;
        }

        // Current value of the column or 0
        $current = (int) ($this->attributes[$column] ?? 0);

        // New value after decrement
        $newValue = $current - $amount;

        // Update the column in the database
        $updated = $this->db->update(
            $this->tableName,
            [$column => $newValue],
            [static::$primaryKey => $id]
        );

        if ($updated !== false) {
            // Update model attribute
            $this->attributes[$column] = $newValue;
            return true;
        }

        return false;
    }

    /**
     * Execute a raw SQL query and return results.
     *
     * Example usage:
     * ```php
     * $results = Post::query()->raw("SELECT * FROM {$wpdb->prefix}posts WHERE status = 'active'");
     * ```
     *
     * @param string $sql The raw SQL query to execute
     * @param string $output Format of returned results (ARRAY_A, ARRAY_N, OBJECT)
     * @return array|object Raw query results
     */
    public function raw(string $sql, string $output = ARRAY_A): object|array
    {
        // Make sure $sql is not empty
        if (empty($sql)) {
            return [];
        }

        // Use $wpdb to execute raw SQL query
        return $this->db->get_results($sql, $output);
    }

    /**
     * Specify the columns to select in the query.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->select('id', 'title', 'status')->where('status', 'active')->get();
     * ```
     *
     * @param string ...$columns List of columns to retrieve
     * @return static Returns the model instance for chaining
     */
    public function select(string ...$columns): static
    {
        // If no columns provided, default to all columns (*)
        if (empty($columns)) {
            $this->selectedColumns = ['*'];
        } else {
            $this->selectedColumns = $columns;
        }

        return $this;
    }

    /**
     * Delete the model from the database.
     *
     * - Soft delete if enabled (sets `deleted_at` timestamp).
     * - Hard delete if soft deletes are disabled.
     *
     * Example usage:
     * ```php
     * $post = Post::find(1);
     * $post->delete(); // soft delete if enabled
     * ```
     *
     * @return bool True on success, false on failure
     */
    public function delete(): bool
    {
        $id = $this->attributes[static::$primaryKey] ?? null;

        // Cannot delete without primary key
        if (empty($id)) {
            return false;
        }

        // Soft delete
        if (!empty($this->softDeletes)) {
            $now = current_time('mysql');

            $success = $this->db->update(
                $this->tableName,
                [$this->deletedAtColumn => $now],
                [static::$primaryKey => $id]
            );

            if ($success !== false) {
                $this->attributes[$this->deletedAtColumn] = $now;
                return true;
            }

            return false;
        }

        // Hard delete
        return $this->db->delete(
                $this->tableName,
                [static::$primaryKey => $id]
            ) !== false;
    }

    /**
     * Restore a soft-deleted model.
     *
     * Only works if soft deletes are enabled.
     *
     * Example usage:
     * ```php
     * $post = Post::query()->withTrashed()->find(1);
     * $post->restore(); // sets deleted_at to null
     * ```
     *
     * @return bool True on success, false on failure
     */
    public function restore(): bool
    {
        // Soft deletes must be enabled
        if (empty($this->softDeletes)) {
            return false;
        }

        $id = $this->attributes[static::$primaryKey] ?? null;

        // Cannot restore without primary key
        if (empty($id)) {
            return false;
        }

        $success = $this->db->update(
            $this->tableName,
            [$this->deletedAtColumn => null],
            [static::$primaryKey => $id]
        );

        if ($success !== false) {
            $this->attributes[$this->deletedAtColumn] = null;
            return true;
        }

        return false;
    }

    /**
     * Permanently delete the model from the database.
     *
     * Bypasses soft deletes and removes the row completely.
     *
     * Example usage:
     * ```php
     * $post = Post::find(5);
     * $post->forceDelete(); // permanently deletes row
     * ```
     *
     * @return bool True on success, false on failure
     */
    public function forceDelete(): bool
    {
        $id = $this->attributes[static::$primaryKey] ?? null;

        // Cannot force delete without primary key
        if (empty($id)) {
            return false;
        }

        return $this->db->delete(
                $this->tableName,
                [static::$primaryKey => $id]
            ) !== false;
    }

    /**
     * Delete one or multiple records by primary key.
     *
     * Supports soft deletes (default) or force delete if `$force` is true.
     *
     * Example usage:
     * ```php
     * Post::destroy(5);                  // soft delete
     * Post::destroy([1, 2, 3]);          // soft delete multiple
     * Post::destroy([4, 5], true);       // force delete
     * ```
     *
     * @param int|array $ids Primary key(s) to delete
     * @param bool $force If true, perform a hard delete even if soft deletes enabled
     * @return int Number of rows affected
     */
    public static function destroy(int|array $ids, bool $force = false): int
    {
        // Convert single ID to array
        $ids = (array) $ids;

        if (empty($ids)) {
            return 0; // nothing to delete
        }

        // Create instance to access $db and table info
        $instance = new static();

        // Sanitize IDs for SQL
        $ids = array_map('intval', $ids);
        $idList = implode(',', $ids);

        if (!empty($instance->softDeletes) && $instance->softDeletes === true && !$force) {
            // Soft delete: set deleted_at timestamp
            $now = current_time('mysql');
            $query = "UPDATE {$instance->tableName} 
                  SET {$instance->deletedAtColumn} = '{$now}' 
                  WHERE " . static::$primaryKey . " IN ({$idList})";
        } else {
            // Hard delete
            $query = "DELETE FROM {$instance->tableName} 
                  WHERE " . static::$primaryKey . " IN ({$idList})";
        }

        $result = $instance->db->query($query);

        return $result !== false ? count($ids) : 0;
    }

    /**
     * Set the number of records to retrieve per page.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()->where('status', 'active')->setPerPage(10)->get();
     * ```
     *
     * @param int $count Number of records per page
     * @return static Returns the current query builder instance for chaining
     */
    public function setPerPage(int $count): static
    {
        // Ensure the count is positive
        $this->limit = max(1, $count);

        // Return $this for chaining
        return $this;
    }

    /**
     * Set the current page for pagination.
     *
     * Works together with setPerPage() to calculate OFFSET for SQL.
     *
     * Example usage:
     * ```php
     * $posts = Post::query()
     *     ->where('status', 'active')
     *     ->setPerPage(10)
     *     ->page(2)
     *     ->get(); // retrieves records 11–20
     * ```
     *
     * @param int $page Page number (1-based)
     * @return static Returns the current query builder instance for chaining
     */
    public function page(int $page): static
    {
        // Ensure page is at least 1
        $this->page = max(1, $page);

        return $this;
    }

    /**
     * Internal helper to handle insert or update logic based on current query.
     *
     * - Merges current WHERE conditions with provided attributes.
     * - Inserts a new record if none exists.
     * - Updates existing record if found and $updateIfExists is true.
     *
     * @param array $values Attributes to insert or update
     * @param bool $updateIfExists Whether to update if record exists
     * @return static The model instance after operation
     */
    protected function createOrUpdateInternal(array $values, bool $updateIfExists = false): static
    {
        // Attempt to fetch the first row matching current query
        $existing = $this->first();

        // Merge WHERE conditions into attributes for creation or update
        $data = [];
        foreach ($this->wheres as $where) {
            if ($where['type'] === 'basic') {
                $data[$where['column']] = $where['value'];
            }
        }

        // Merge user-provided values
        $data = array_merge($data, $values);

        if ($existing) {
            // Fill model attributes from existing row
            $this->fill($existing);

            if ($updateIfExists) {
                // Update record with new data
                $this->fill($data);
                $this->update();
            }

            return $this;
        }

        // Insert new record
        $this->fill($data);
        $this->insert();

        return $this;
    }

    /**
     * Build the WHERE clause for SQL queries.
     *
     * Supports AND/OR, IN/NOT IN, and soft deletes automatically.
     *
     * Example usage:
     * ```php
     * $sqlWhere = $this->buildWhereClause();
     * $sql = "SELECT * FROM {$this->tableName} " . $sqlWhere;
     * ```
     *
     * @return string SQL WHERE clause starting with "WHERE" or empty string
     */
    protected function buildWhereClause(): string
    {
        // Return empty string if no conditions and soft deletes not applied
        if (empty($this->wheres) && (!$this->softDeletes || $this->includeTrashed)) {
            return '';
        }

        $clauses = []; // holds individual SQL conditions

        // Loop through each condition in $this->wheres
        foreach ($this->wheres as $index => $where) {
            $boolean = $where['boolean'] ?? 'and'; // default AND

            switch ($where['type']) {
                case 'basic':
                    // Simple column operator value condition
                    $col = $where['column'];
                    $op = $where['operator'];
                    $val = esc_sql($where['value']);
                    $clause = "`$col` $op '$val'";
                    break;

                case 'in':
                    // WHERE IN condition
                    $col = $where['column'];
                    $vals = array_map('esc_sql', $where['value']);
                    $clause = "`$col` IN ('" . implode("','", $vals) . "')";
                    break;

                case 'notIn':
                    // WHERE NOT IN condition
                    $col = $where['column'];
                    $vals = array_map('esc_sql', $where['value']);
                    $clause = "`$col` NOT IN ('" . implode("','", $vals) . "')";
                    break;

                default:
                    continue 2; // skip unknown types
            }

            // Add boolean operator for chaining
            if ($index > 0) {
                $clause = strtoupper($boolean) . ' ' . $clause;
            }

            $clauses[] = $clause;
        }

        // Apply soft deletes if enabled and not including trashed rows
        if ($this->softDeletes && empty($this->includeTrashed)) {
            $clauses[] = "AND {$this->deletedAtColumn} IS NULL";
        }

        if (empty($clauses)) {
            return '';
        }

        // Combine all clauses into single WHERE string
        return 'WHERE ' . implode(' ', $clauses);
    }

    /**
     * Build the full SQL query based on current builder state.
     *
     * @param int|null $limit Optional limit override
     * @param int|null $offset Optional offset override
     * @return string The SQL query string
     */
    protected function buildQuerySql(?int $limit = null, ?int $offset = null): string
    {
        $sql = "SELECT " . $this->buildSelectColumns() . " FROM {$this->tableName}";
        $sql .= ' ' . $this->buildWhereClause();

        // Apply ORDER BY if defined
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as [$column, $direction]) {
                $orders[] = "`$column` $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        // Apply LIMIT if set
        $finalLimit = (int) ($limit ?? $this->limit);
        if ($finalLimit > 0) {
            $sql .= " LIMIT " . $finalLimit;
        }

        // Apply OFFSET if set
        $finalOffset = (int) ($offset ?? (($this->page > 0 ? $this->page : 1) - 1) * $this->perPage);
        if ($finalOffset > 0) {
            $sql .= " OFFSET " . $finalOffset;
        }

        return $sql;
    }

    /**
     * Build the SELECT part of the SQL query.
     *
     * Uses $this->selectedColumns if defined; defaults to '*'.
     * Can override with custom columns (e.g., '1' for EXISTS queries).
     *
     * Example usage:
     * ```php
     * $columnsSql = $this->buildSelectColumns();       // "id, title, status"
     * $columnsSql = $this->buildSelectColumns('1');    // "1"
     * ```
     *
     * @param string|array|null $columns Optional columns to override selectedColumns
     * @return string Comma-separated list of columns or '*'
     */
    protected function buildSelectColumns(string|array|null $columns = null): string
    {
        // If override provided
        if (!empty($columns)) {
            if (is_array($columns)) {
                $escaped = array_map(fn($col) => "`" . str_replace("`", "``", $col) . "`", $columns);
                return implode(', ', $escaped);
            }
            return $columns; // e.g., "1" for EXISTS
        }

        // If no columns explicitly set, default to all columns
        if (empty($this->selectedColumns)) {
            return '*';
        }

        // Escape each column name
        $escaped = array_map(fn($col) => "`" . str_replace("`", "``", $col) . "`", $this->selectedColumns);

        return implode(', ', $escaped);
    }
}
