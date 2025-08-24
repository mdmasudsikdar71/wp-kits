<?php

namespace MDMasudSikdar\WpKits\Database;

use wpdb;

abstract class Model
{
    /**
     * WordPress database object.
     *
     * @var wpdb
     */
    protected wpdb $db;

    /**
     * Database table name without prefix.
     *
     * @var string
     */
    protected string $table;

    /**
     * Primary key column name.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Enable automatic timestamps management (created_at, updated_at).
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Enable soft deletes by using deleted_at column.
     *
     * @var bool
     */
    protected bool $softDeletes = false;

    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Collection of where clauses for query building.
     *
     * @var array<int, array{string, mixed}>
     */
    protected array $wheres = [];

    /**
     * Order by clause as [column, direction].
     *
     * @var array{string, string}|null
     */
    protected ?array $orderBy = null;

    /**
     * Limit number of results.
     *
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * Offset for results.
     *
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * Constructor: Initialize $wpdb.
     */
    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Create a new query instance.
     *
     * @return static
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Add a where condition to the query.
     *
     * @param string $column
     * @param mixed $value
     * @return static
     *
     * @example
     * Model::query()->where('status', 'active')->get();
     */
    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = [$column, $value];
        return $this;
    }

    /**
     * Get records based on the query builder conditions.
     *
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $users = User::query()->where('status', 'active')->get();
     */
    public function get(): array
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = "SELECT * FROM {$tableName}";

        $conditions = [];

        // Add soft delete condition if enabled
        if ($this->softDeletes) {
            $conditions[] = "deleted_at IS NULL";
        }

        // Add where conditions
        foreach ($this->wheres as [$column, $value]) {
            // Use prepare to prevent SQL injection
            $conditions[] = $this->db->prepare("{$column} = %s", $value);
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Add order by
        if ($this->orderBy) {
            [$column, $direction] = $this->orderBy;
            $sql .= " ORDER BY {$column} {$direction}";
        }

        // Add limit & offset
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $this->db->get_results($sql, ARRAY_A);
    }

    /**
     * Get the first record based on the query builder conditions.
     *
     * @return array<string, mixed>|null
     *
     * @example
     * $user = User::query()->where('email', 'test@example.com')->first();
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Set a limit for the query.
     *
     * @param int $limit
     * @return static
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Add an ORDER BY clause to the query.
     *
     * @param string $column
     * @param string $direction 'ASC' or 'DESC'
     * @return static
     *
     * @example
     * $users = User::query()->orderBy('created_at', 'DESC')->get();
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $this->orderBy = [$column, $direction];
        return $this;
    }

    /**
     * Delete a record (soft delete if enabled).
     *
     * @param int|null $id
     * @return bool
     *
     * @example
     * User::query()->delete(5); // soft delete user with ID 5
     */
    public function delete(?int $id = null): bool
    {
        $tableName = $this->db->prefix . $this->table;
        $id = $id ?? $this->{$this->primaryKey} ?? null;

        if (!$id) {
            return false;
        }

        if ($this->softDeletes) {
            // Soft delete: set deleted_at timestamp
            $time = current_time('mysql');
            $result = $this->db->update(
                $tableName,
                ['deleted_at' => $time],
                [$this->primaryKey => $id],
                ['%s'],
                ['%d']
            );
        } else {
            // Hard delete
            $result = $this->db->delete(
                $tableName,
                [$this->primaryKey => $id],
                ['%d']
            );
        }

        return $result !== false;
    }

    public function upsert(array $data, array $uniqueKeys = []): int|false
    {
        $tableName = $this->db->prefix . $this->table;

        // Only allow fillable attributes
        $data = array_intersect_key($data, array_flip($this->fillable));

        if ($this->timestamps) {
            $time = current_time('mysql');
            if (!isset($data['created_at'])) $data['created_at'] = $time;
            $data['updated_at'] = $time;
        }

        // If no unique keys specified, use primary key
        if (empty($uniqueKeys)) {
            $uniqueKeys = [$this->primaryKey];
        }

        // Prepare INSERT part
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '%s');
        $insertFormats = $placeholders;

        // Prepare UPDATE part
        $updateData = $data;
        foreach ($uniqueKeys as $key) {
            unset($updateData[$key]); // don't update unique keys
        }
        $updateColumns = array_keys($updateData);
        $updatePlaceholders = array_map(fn($col) => "$col = %s", $updateColumns);

        // Build raw SQL
        $sql = "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        if (!empty($updateColumns)) {
            $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updatePlaceholders);
        }

        // Combine values for insert and update
        $values = array_merge(array_values($data), array_values($updateData));

        $result = $this->db->query($this->db->prepare($sql, ...$values));

        if ($result !== false) {
            return (int)($this->db->insert_id ?: $data[$this->primaryKey] ?? 0);
        }

        return false;
    }

    public function insert(array $data): int|false
    {
        $tableName = $this->db->prefix . $this->table;

        // Only allow fillable attributes
        $data = array_intersect_key($data, array_flip($this->fillable));

        // Handle timestamps
        if ($this->timestamps) {
            $time = current_time('mysql');
            $data['created_at'] = $time;
            $data['updated_at'] = $time;
        }

        // Prepare formats for $wpdb
        $formats = array_fill(0, count($data), '%s');

        // Perform insert
        $result = $this->db->insert($tableName, $data, $formats);

        if ($result !== false) {
            return (int)$this->db->insert_id; // return new record ID
        }

        return false; // insert failed
    }

    /**
     * Restore a soft-deleted record.
     *
     * @param int $id
     * @return bool
     *
     * @example
     * User::query()->restore(5); // restore user with ID 5
     */
    public function restore(int $id): bool
    {
        if (!$this->softDeletes) {
            return false;
        }

        $tableName = $this->db->prefix . $this->table;

        $result = $this->db->update(
            $tableName,
            ['deleted_at' => null],
            [$this->primaryKey => $id],
            ['%s'],
            ['%d']
        );

        return $result !== false;
    }

    public function find(int $id): ?array
    {
        $tableName = $this->db->prefix . $this->table;

        $query = $this->db->prepare(
            "SELECT * FROM {$tableName} WHERE {$this->primaryKey} = %d LIMIT 1",
            $id
        );

        $result = $this->db->get_row($query, ARRAY_A);

        return $result ?: null; // return null if not found
    }

    public function pluck(string $column, int $id): mixed
    {
        $tableName = $this->db->prefix . $this->table;

        $query = $this->db->prepare(
            "SELECT {$column} FROM {$tableName} WHERE {$this->primaryKey} = %d LIMIT 1",
            $id
        );

        return $this->db->get_var($query); // returns value of the column or null
    }

    /**
     * Insert a new record into the table.
     *
     * @param array $data
     * @return int|false Inserted ID or false on failure
     *
     * @example
     * $userId = User::query()->create(['name' => 'John', 'email' => 'john@example.com']);
     */
    public function create(array $data): int|false
    {
        $tableName = $this->db->prefix . $this->table;

        // Only allow fillable attributes
        $data = array_intersect_key($data, array_flip($this->fillable));

        if ($this->timestamps) {
            $time = current_time('mysql');
            $data['created_at'] = $time;
            $data['updated_at'] = $time;
        }

        $formats = array_fill(0, count($data), '%s');

        $result = $this->db->insert($tableName, $data, $formats);

        if ($result !== false) {
            return (int)$this->db->insert_id;
        }

        return false;
    }

    /**
     * Update an existing record.
     *
     * @param int $id
     * @param array $data
     * @return bool
     *
     * @example
     * User::query()->update(5, ['name' => 'Jane']);
     */
    public function update(int $id, array $data): bool
    {
        $tableName = $this->db->prefix . $this->table;

        // Only allow fillable attributes
        $data = array_intersect_key($data, array_flip($this->fillable));

        if ($this->timestamps) {
            $data['updated_at'] = current_time('mysql');
        }

        $formats = array_fill(0, count($data), '%s');

        $result = $this->db->update(
            $tableName,
            $data,
            [$this->primaryKey => $id],
            $formats,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get all records from the table.
     *
     * Ignores any where/order/limit/offset constraints.
     *
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $allUsers = User::query()->all();
     */
    public function all(): array
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = "SELECT * FROM {$tableName}";

        if ($this->softDeletes) {
            $sql .= " WHERE deleted_at IS NULL";
        }

        return $this->db->get_results($sql, ARRAY_A);
    }

    /**
     * Increment a numeric column.
     *
     * @param int $id
     * @param string $column
     * @param int $amount
     * @return bool
     *
     * @example
     * User::query()->increment(5, 'login_count', 1);
     */
    public function increment(int $id, string $column, int $amount = 1): bool
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = $this->db->prepare(
            "UPDATE {$tableName} SET {$column} = {$column} + %d WHERE {$this->primaryKey} = %d",
            $amount,
            $id
        );

        return $this->db->query($sql) !== false;
    }

    /**
     * Decrement a numeric column.
     *
     * @param int $id
     * @param string $column
     * @param int $amount
     * @return bool
     *
     * @example
     * User::query()->decrement(5, 'login_count', 1);
     */
    public function decrement(int $id, string $column, int $amount = 1): bool
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = $this->db->prepare(
            "UPDATE {$tableName} SET {$column} = {$column} - %d WHERE {$this->primaryKey} = %d",
            $amount,
            $id
        );

        return $this->db->query($sql) !== false;
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $relatedModel Fully qualified class name of related model
     * @param string $foreignKey Foreign key column in related table
     * @param string|null $localKey Local primary key
     * @return array<string, mixed>|null
     *
     * @example
     * $profile = User::query()->hasOne(Profile::class, 'user_id');
     */
    public function hasOne(string $relatedModel, string $foreignKey, ?string $localKey = null): ?array
    {
        $localKey = $localKey ?? $this->primaryKey;
        $related = new $relatedModel();

        return $related::query()->where($foreignKey, $this->{$localKey})->first();
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $relatedModel Fully qualified class name of related model
     * @param string $foreignKey Foreign key column in related table
     * @param string|null $localKey Local primary key
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $posts = User::query()->hasMany(Post::class, 'user_id');
     */
    public function hasMany(string $relatedModel, string $foreignKey, ?string $localKey = null): array
    {
        $localKey = $localKey ?? $this->primaryKey;
        $related = new $relatedModel();

        return $related::query()->where($foreignKey, $this->{$localKey})->get();
    }

    /**
     * Define an inverse one-to-one or many-to-one relationship.
     *
     * @param string $relatedModel Fully qualified class name of related model
     * @param string $foreignKey Foreign key column in this table
     * @param string|null $ownerKey Primary key of related table
     * @return array<string, mixed>|null
     *
     * @example
     * $user = Profile::query()->belongsTo(User::class, 'user_id');
     */
    public function belongsTo(string $relatedModel, string $foreignKey, ?string $ownerKey = null): ?array
    {
        $ownerKey = $ownerKey ?? 'id';
        $related = new $relatedModel();

        return $related::query()->where($ownerKey, $this->{$foreignKey})->first();
    }

    /**
     * Get the count of records based on current query conditions.
     *
     * @return int
     *
     * @example
     * $activeUsers = User::query()->where('status', 'active')->count();
     */
    public function count(): int
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = "SELECT COUNT(*) FROM {$tableName}";

        $conditions = [];

        // Soft delete condition
        if ($this->softDeletes) {
            $conditions[] = "deleted_at IS NULL";
        }

        // Add where conditions
        foreach ($this->wheres as [$column, $value]) {
            $conditions[] = $this->db->prepare("{$column} = %s", $value);
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return (int) $this->db->get_var($sql);
    }

    /**
     * Set an offset for the query.
     *
     * @param int $offset
     * @return static
     *
     * @example
     * $users = User::query()->limit(10)->offset(20)->get();
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * Only attributes defined in $fillable are allowed.
     *
     * @param array $attributes
     * @return static
     *
     * @example
     * $user = User::query()->fill(['name' => 'John', 'email' => 'john@example.com']);
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable, true)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Create a new instance of the model.
     *
     * @param array $attributes Optional attributes to fill
     * @return static
     *
     * @example
     * $user = User::query()->newInstance(['name' => 'John']);
     */
    public function newInstance(array $attributes = []): static
    {
        $instance = new static();

        if (!empty($attributes)) {
            $instance->fill($attributes);
        }

        return $instance;
    }

    /**
     * Paginate results.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     *
     * @example
     * $users = User::query()->where('status', 'active')->paginate(10, 2);
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();

        $this->limit($perPage)->offset(($page - 1) * $perPage);

        $data = $this->get();

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Apply a query scope.
     *
     * @param callable $callback Receives the current query instance
     * @return static
     *
     * @example
     * $activeUsers = User::query()->scope(fn($query) => $query->where('status', 'active'))->get();
     */
    public function scope(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * Execute a raw SQL query and return results.
     *
     * @param string $sql
     * @param array $params Optional parameters for prepare()
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $users = User::query()->raw("SELECT * FROM {$wpdb->prefix}users WHERE status = %s", ['active']);
     */
    public function raw(string $sql, array $params = []): array
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_results($sql, ARRAY_A);
    }

    /**
     * Specify the columns to select in the query.
     *
     * @param string ...$columns
     * @return static
     *
     * @example
     * $users = User::query()->select('id', 'name', 'email')->get();
     */
    protected array $selectColumns = [];

    public function select(string ...$columns): static
    {
        $this->selectColumns = $columns;
        return $this;
    }

    /**
     * Begin a database transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->query('START TRANSACTION') !== false;
    }

    /**
     * Commit the current transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->query('COMMIT') !== false;
    }

    /**
     * Rollback the current transaction.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->db->query('ROLLBACK') !== false;
    }

    /**
     * Retrieve the first record matching attributes or create it.
     *
     * @param array $attributes Attributes to search for
     * @param array $values Additional attributes to set if creating
     * @return static
     *
     * @example
     * $user = User::query()->firstOrCreate(
     *     ['email' => 'john@example.com'],
     *     ['name' => 'John']
     * );
     */
    public function firstOrCreate(array $attributes, array $values = []): static
    {
        // Attempt to find the first record matching the attributes
        $query = static::query();
        foreach ($attributes as $key => $value) {
            $query->where($key, $value);
        }

        $record = $query->first();

        if ($record) {
            return $this->newInstance($record);
        }

        // Merge attributes and additional values for creation
        $data = array_merge($attributes, $values);
        $id = $this->create($data);

        return $this->newInstance(array_merge($data, [$this->primaryKey => $id]));
    }

    /**
     * Add a WHERE IN condition to the query.
     *
     * @param string $column
     * @param array $values
     * @return static
     *
     * @example
     * $users = User::query()->whereIn('id', [1,2,3])->get();
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            // If empty, return no results
            $this->wheres[] = ['1', '0']; // 1 = 0
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $this->wheres[] = ["{$column} IN ($placeholders)", $values];

        return $this;
    }

    /**
     * Add a WHERE NOT IN condition to the query.
     *
     * @param string $column
     * @param array $values
     * @return static
     *
     * @example
     * $users = User::query()->whereNotIn('id', [1,2,3])->get();
     */
    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $this->wheres[] = ["{$column} NOT IN ($placeholders)", $values];

        return $this;
    }

    /**
     * Check if any record exists based on current query conditions.
     *
     * @return bool
     *
     * @example
     * $exists = User::query()->where('email', 'test@example.com')->exists();
     */
    public function exists(): bool
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = "SELECT 1 FROM {$tableName}";

        $conditions = [];

        // Soft delete condition
        if ($this->softDeletes) {
            $conditions[] = "deleted_at IS NULL";
        }

        // Add where conditions
        foreach ($this->wheres as [$column, $value]) {
            if (is_array($value)) {
                $placeholders = implode(',', array_fill(0, count($value), '%s'));
                $conditions[] = $this->db->prepare("{$column} IN ($placeholders)", ...$value);
            } else {
                $conditions[] = $this->db->prepare("{$column} = %s", $value);
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " LIMIT 1";

        return (bool) $this->db->get_var($sql);
    }

    /**
     * Reset the query builder state.
     *
     * @return static
     *
     * @example
     * $users = User::query()->where('status', 'active')->resetQuery()->get();
     */
    public function resetQuery(): static
    {
        $this->wheres = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->selectColumns = [];

        return $this;
    }
}
