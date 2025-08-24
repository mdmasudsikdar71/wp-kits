<?php


namespace MDMasudSikdar\WpKits\Database;

use wpdb;

/**
 * Class Model
 *
 * Dynamic Laravel-style ORM base model for WordPress custom tables.
 * Supports soft deletes, timestamps, fluent query building, basic relationships.
 *
 * @package MDMasudSikdar\WpKits\Database
 */
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
     *
     * @example
     * ```php
     * $users = User::query()->where('status', 'active')->get();
     * ```
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Get all records from the table.
     *
     * Ignores any where/order/limit/offset constraints.
     *
     * @return array<int, array<string, mixed>>
     *
     * @example
     * ```php
     * $allProviders = NotificationProviderModel::query()->all();
     * ```
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
     * Add a where condition.
     *
     * @param array|string $column
     * @param mixed $value
     * @return static
     *
     * @example
     * ```php
     * Model::query()->where('age', 30)->get();
     * ```
     */
    public function where(array|string $column, mixed $value = null): static
    {
        if (is_array($column)) {
            // multiple conditions at once
            foreach ($column as $col => $val) {
                $this->wheres[$col] = $val;
            }
        } else {
            // single condition
            $this->wheres[$column] = $value;
        }
        return $this;
    }

    /**
     * Add an order by clause.
     *
     * @param string $column
     * @param string $direction ASC|DESC (default ASC)
     * @return static
     *
     * @example
     * ```php
     * Model::query()->orderBy('created_at', 'DESC')->get();
     * ```
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy = [$column, strtoupper($direction)];
        return $this;
    }

    /**
     * Set limit on number of rows returned.
     *
     * @param int $limit
     * @return static
     *
     * @example
     * ```php
     * Model::query()->limit(10)->get();
     * ```
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set offset for the query.
     *
     * @param int $offset
     * @return static
     *
     * @example
     * ```php
     * Model::query()->offset(5)->limit(10)->get();
     * ```
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Get all records matching query conditions.
     *
     * @return array<int, array<string, mixed>>
     *
     * @example
     * ```php
     * $results = Model::query()->where('active', 1)->get();
     * ```
     */
    public function get(): array
    {
        $tableName = $this->db->prefix . $this->table;

        $sql = "SELECT * FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        if ($this->orderBy) {
            [$col, $dir] = $this->orderBy;
            $sql .= " ORDER BY {$col} {$dir}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        return $this->db->get_results($preparedSql, ARRAY_A);
    }

    /**
     * Get first matching record or null.
     *
     * @return array<string, mixed>|null
     *
     * @example
     * ```php
     * $first = Model::query()->where('id', 1)->first();
     * ```
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find record by primary key.
     *
     * @param int|string $id
     * @return array<string, mixed>|null
     *
     * @example
     * ```php
     * $user = Model::query()->find(1);
     * ```
     */
    public function find(int|string $id): ?array
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT * FROM {$tableName} WHERE {$this->primaryKey} = %s";

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, $id);
        return $this->db->get_row($preparedSql, ARRAY_A);
    }

    /**
     * Insert a new record.
     *
     * @param array<string, mixed> $data
     * @return int Inserted record ID
     *
     * @example
     * ```php
     * $id = Model::query()->create(['name' => 'Test']);
     * ```
     */
    public function create(array $data): int
    {
        if ($this->timestamps) {
            $now = current_time('mysql');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $this->db->insert($this->db->prefix . $this->table, $data);
        return (int)$this->db->insert_id;
    }

    /**
     * Update a record by primary key.
     *
     * @param int|string $id
     * @param array<string, mixed> $data
     * @return bool
     *
     * @example
     * ```php
     * Model::query()->update(1, ['name' => 'Updated']);
     * ```
     */
    public function update(int|string $id, array $data): bool
    {
        if ($this->timestamps) {
            $data['updated_at'] = current_time('mysql');
        }

        return (bool)$this->db->update(
            $this->db->prefix . $this->table,
            $data,
            [$this->primaryKey => $id]
        );
    }

    /**
     * Soft or hard delete a record by primary key.
     *
     * @param int|string $id
     * @return bool
     *
     * @example
     * ```php
     * Model::query()->delete(1);
     * ```
     */
    public function delete(int|string $id): bool
    {
        if ($this->softDeletes) {
            return $this->update($id, ['deleted_at' => current_time('mysql')]);
        }

        return (bool)$this->db->delete(
            $this->db->prefix . $this->table,
            [$this->primaryKey => $id]
        );
    }

    /**
     * Restore a soft-deleted record.
     *
     * @param int|string $id
     * @return bool
     *
     * @example
     * ```php
     * Model::query()->restore(1);
     * ```
     */
    public function restore(int|string $id): bool
    {
        if (!$this->softDeletes) {
            return false;
        }

        return $this->update($id, ['deleted_at' => null]);
    }

    /**
     * Relationship: hasMany
     *
     * Defines a one-to-many relationship.
     *
     * @param string $relatedModel Fully qualified class name of related model
     * @param string $foreignKey Foreign key column on related table
     * @param string $localKey Local key column (usually primary key)
     * @return array<int, array<string, mixed>>
     *
     * @example
     * ```php
     * // In User model:
     * $posts = $this->hasMany(Post::class, 'user_id', 'id');
     * ```
     */
    public function hasMany(string $relatedModel, string $foreignKey, string $localKey = 'id'): array
    {
        /** @var Model $related */
        $related = new $relatedModel();

        $tableName = $this->db->prefix . $related->table;
        $sql = "SELECT * FROM {$tableName} WHERE {$foreignKey} = %s";

        $preparedSql = $this->db->prepare($sql, $this->find($localKey)[$localKey] ?? null);
        return $this->db->get_results($preparedSql, ARRAY_A);
    }

    /**
     * Relationship: belongsTo
     *
     * Defines an inverse one-to-one or many-to-one relationship.
     *
     * @param string $relatedModel Fully qualified class name of related model
     * @param string $foreignKey Foreign key column on this model's table
     * @param string $ownerKey Owner's primary key column (usually 'id')
     * @return array<string, mixed>|null
     *
     * @example
     * ```php
     * // In Post model:
     * $author = $this->belongsTo(User::class, 'user_id', 'id');
     * ```
     */
    public function belongsTo(string $relatedModel, string $foreignKey, string $ownerKey = 'id'): ?array
    {
        /** @var Model $related */
        $related = new $relatedModel();

        $foreignValue = $this->find($this->primaryKey)[$foreignKey] ?? null;

        if ($foreignValue === null) {
            return null;
        }

        return $related->find($foreignValue);
    }

    /**
     * Get a single column from all results.
     *
     * @param string $column
     * @return array<int, mixed>
     *
     * @example
     * ```php
     * $names = User::query()->pluck('name');
     * ```
     */
    public function pluck(string $column): array
    {
        $results = $this->get();
        return array_map(fn($row) => $row[$column] ?? null, $results);
    }

    /**
     * Count records matching current query.
     *
     * @return int
     *
     * @example
     * ```php
     * $count = User::query()->where('status', 'active')->count();
     * ```
     */
    public function count(): int
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT COUNT(*) as count FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        $result = $this->db->get_row($preparedSql, ARRAY_A);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Check if any record exists matching query.
     *
     * @return bool
     *
     * @example
     * ```php
     * $exists = User::query()->where('email', 'test@example.com')->exists();
     * ```
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Increment a numeric column by a value.
     *
     * @param string $column
     * @param int|float $amount
     * @param array<string, mixed> $wheres
     * @return bool
     *
     * @example
     * ```php
     * User::query()->where('id', 1)->increment('login_count', 1);
     * ```
     */
    public function increment(string $column, int|float $amount = 1, array $wheres = []): bool
    {
        return $this->modifyColumn($column, $amount, ' + ', $wheres);
    }

    /**
     * Decrement a numeric column by a value.
     *
     * @param string $column
     * @param int|float $amount
     * @param array<string, mixed> $wheres
     * @return bool
     *
     * @example
     * ```php
     * User::query()->where('id', 1)->decrement('credits', 5);
     * ```
     */
    public function decrement(string $column, int|float $amount = 1, array $wheres = []): bool
    {
        return $this->modifyColumn($column, $amount, ' - ', $wheres);
    }

    /**
     * Truncate the table.
     *
     * @return bool
     *
     * @example
     * ```php
     * User::query()->truncate();
     * ```
     */
    public function truncate(): bool
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "TRUNCATE TABLE {$tableName}";
        return (bool)$this->db->query($sql);
    }

    /**
     * Get first record matching attributes or create it.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return array<string, mixed>|int
     *
     * @example
     * ```php
     * $user = User::query()->firstOrCreate(
     *     ['email' => 'test@example.com'],
     *     ['name' => 'Test User']
     * );
     * ```
     */
    public function firstOrCreate(array $attributes, array $values = []): array|int
    {
        foreach ($attributes as $col => $val) {
            $this->where($col, $val);
        }

        $record = $this->first();
        if ($record) {
            return $record;
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * Update records matching query conditions.
     *
     * @param array<string, mixed> $data
     * @return bool
     *
     * @example
     * ```php
     * User::query()->where('status', 'inactive')->updateWhere(['status' => 'active']);
     * ```
     */
    public function updateWhere(array $data): bool
    {
        $tableName = $this->db->prefix . $this->table;

        if ($this->timestamps) {
            $data['updated_at'] = current_time('mysql');
        }

        $sql = "UPDATE {$tableName} SET ";
        $setParts = [];
        $params = [];

        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = %s";
            $params[] = $val;
        }

        $sql .= implode(', ', $setParts) . " WHERE 1=1";

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        return (bool)$this->db->query($preparedSql);
    }

    /**
     * Helper method to increment/decrement a numeric column.
     *
     * @internal
     */
    protected function modifyColumn(string $column, int|float $amount, string $operator, array $wheres = []): bool
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "UPDATE {$tableName} SET {$column} = {$column}{$operator}%d WHERE 1=1";
        $params = [$amount];

        foreach (array_merge($this->wheres, $wheres) as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        return (bool)$this->db->query($preparedSql);
    }

    /**
     * Add a WHERE IN clause.
     *
     * @param string $column
     * @param array<int, mixed> $values
     * @return static
     *
     * @example
     * $users = User::query()->whereIn('role', ['admin', 'editor'])->get();
     */
    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) return $this;
        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $this->wheres[] = [$column, $values, 'IN', $placeholders];
        return $this;
    }

    /**
     * Paginate results.
     *
     * @param int $perPage
     * @param int $page
     * @return array{data: array<int, array<string, mixed>>, total: int, per_page: int, current_page: int, last_page: int}
     *
     * @example
     * $pageData = User::query()->where('status', 'active')->paginate(10, 2);
     */
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $this->limit($perPage)->offset(($page - 1) * $perPage);
        $data = $this->get();
        $total = $this->count();
        $lastPage = (int)ceil($total / $perPage);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Insert multiple records.
     *
     * @param array<int, array<string, mixed>> $records
     * @return bool
     *
     * @example
     * User::query()->bulkInsert([
     *     ['name' => 'John', 'email' => 'john@example.com'],
     *     ['name' => 'Jane', 'email' => 'jane@example.com'],
     * ]);
     */
    public function bulkInsert(array $records): bool
    {
        if (empty($records)) return false;

        $tableName = $this->db->prefix . $this->table;
        $columns = array_keys($records[0]);
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '%s')) . ')';
        $allPlaceholders = implode(',', array_fill(0, count($records), $placeholders));
        $values = [];
        foreach ($records as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        $sql = "INSERT INTO {$tableName} (" . implode(',', $columns) . ") VALUES {$allPlaceholders}";
        $preparedSql = $this->db->prepare($sql, ...$values);

        return (bool)$this->db->query($preparedSql);
    }

    /**
     * Add a WHERE NOT IN clause.
     *
     * @param string $column
     * @param array<int, mixed> $values
     * @return static
     *
     * @example
     * $users = User::query()->whereNotIn('status', ['inactive'])->get();
     */
    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) return $this;

        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $this->wheres[] = [$column, $values, 'NOT IN', $placeholders];
        return $this;
    }

    /**
     * Order by column descending.
     *
     * @param string $column
     * @return static
     *
     * @example
     * $latestUsers = User::query()->latest('created_at')->get();
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Order by column ascending.
     *
     * @param string $column
     * @return static
     *
     * @example
     * $oldestUsers = User::query()->oldest('created_at')->get();
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql
     * @param array<int, mixed> $params
     * @param bool $singleRow
     * @return array<int, array<string, mixed>>|array<string, mixed>|null
     *
     * @example
     * $results = User::query()->raw("SELECT * FROM {$wpdb->prefix}users WHERE status=%s", ['active']);
     * $row = User::query()->raw("SELECT * FROM {$wpdb->prefix}users WHERE id=%d", [1], true);
     */
    public function raw(string $sql, array $params = [], bool $singleRow = false)
    {
        $preparedSql = empty($params) ? $sql : $this->db->prepare($sql, ...$params);
        return $singleRow ? $this->db->get_row($preparedSql, ARRAY_A) : $this->db->get_results($preparedSql, ARRAY_A);
    }

    /**
     * Update a record matching attributes or create a new one.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     * @return array<string, mixed>|int
     *
     * @example
     * $user = User::query()->updateOrCreate(
     *     ['email' => 'john@example.com'],
     *     ['name' => 'John Doe', 'status' => 'active']
     * );
     */
    public function updateOrCreate(array $attributes, array $values = []): array|int
    {
        foreach ($attributes as $col => $val) {
            $this->where($col, $val);
        }

        $record = $this->first();
        if ($record) {
            $this->update((int)$record[$this->primaryKey], $values);
            return array_merge($record, $values);
        }

        return $this->create(array_merge($attributes, $values));
    }

    /**
     * Get the first record matching query or throw exception if not found.
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     * @example
     * $user = User::query()->where('email', 'john@example.com')->firstOrFail();
     */
    public function firstOrFail(): array
    {
        $record = $this->first();
        if (!$record) {
            throw new \Exception("Record not found in {$this->table}.");
        }
        return $record;
    }

    /**
     * Get a random record or multiple records.
     *
     * @param int $count
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $randomUsers = User::query()->inRandomOrder()->limit(3)->get();
     */
    public function inRandomOrder(int $count = 1): array
    {
        $this->orderBy('RAND()');
        if ($count > 1) {
            $this->limit($count);
        }
        return $this->get();
    }

    /**
     * Apply a raw WHERE clause.
     *
     * @param string $raw
     * @param array<int, mixed> $bindings
     * @return static
     *
     * @example
     * $users = User::query()->whereRaw("LENGTH(name) > %d", [5])->get();
     */
    public function whereRaw(string $raw, array $bindings = []): static
    {
        $this->wheres[] = ['RAW', $raw, $bindings];
        return $this;
    }

    /**
     * Delete multiple records matching query conditions (hard or soft delete).
     *
     * @return bool
     *
     * @example
     * User::query()->where('status', 'inactive')->deleteWhere();
     */
    public function deleteWhere(): bool
    {
        $records = $this->get();
        $success = true;

        foreach ($records as $record) {
            $result = $this->delete((int)$record[$this->primaryKey]);
            if (!$result) $success = false;
        }

        return $success;
    }

    /**
     * Update a single column for all records matching query.
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     *
     * @example
     * User::query()->where('status', 'inactive')->updateColumn('status', 'active');
     */
    public function updateColumn(string $column, mixed $value): bool
    {
        return $this->updateWhere([$column => $value]);
    }

    /**
     * Get the maximum value of a column.
     *
     * @param string $column
     * @return int|float|null
     *
     * @example
     * $maxAge = User::query()->max('age');
     */
    public function max(string $column): int|float|null
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT MAX({$column}) AS max_value FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        $result = $this->db->get_row($preparedSql, ARRAY_A);
        return $result['max_value'] ?? null;
    }

    /**
     * Get the minimum value of a column.
     *
     * @param string $column
     * @return int|float|null
     *
     * @example
     * $minAge = User::query()->min('age');
     */
    public function min(string $column): int|float|null
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT MIN({$column}) AS min_value FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        $result = $this->db->get_row($preparedSql, ARRAY_A);
        return $result['min_value'] ?? null;
    }

    /**
     * Get the sum of a column.
     *
     * @param string $column
     * @return int|float
     *
     * @example
     * $totalCredits = User::query()->sum('credits');
     */
    public function sum(string $column): int|float
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT SUM({$column}) AS total FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        $result = $this->db->get_row($preparedSql, ARRAY_A);
        return (float)($result['total'] ?? 0);
    }

    /**
     * Group results by a column and optionally aggregate.
     *
     * @param string $column
     * @param string|null $aggregateColumn
     * @param string $aggregateFunction COUNT|SUM|AVG|MAX|MIN
     * @return array<int, array<string, mixed>>
     *
     * @example
     * $grouped = User::query()->groupBy('role', 'id', 'COUNT');
     */
    public function groupBy(string $column, ?string $aggregateColumn = null, string $aggregateFunction = 'COUNT'): array
    {
        $tableName = $this->db->prefix . $this->table;
        $select = $aggregateColumn ? "{$aggregateFunction}({$aggregateColumn}) AS aggregate, {$column}" : "*";
        $sql = "SELECT {$select} FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " GROUP BY {$column}";
        $preparedSql = $this->db->prepare($sql, ...$params);
        return $this->db->get_results($preparedSql, ARRAY_A);
    }

    /**
     * Get average value of a column.
     *
     * @param string $column
     * @return float
     *
     * @example
     * $avgAge = User::query()->avg('age');
     */
    public function avg(string $column): float
    {
        $tableName = $this->db->prefix . $this->table;
        $sql = "SELECT AVG({$column}) AS average FROM {$tableName} WHERE 1=1";
        $params = [];

        foreach ($this->wheres as [$col, $val]) {
            $sql .= " AND {$col} = %s";
            $params[] = $val;
        }

        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        $preparedSql = $this->db->prepare($sql, ...$params);
        $result = $this->db->get_row($preparedSql, ARRAY_A);
        return (float)($result['average'] ?? 0);
    }
}
