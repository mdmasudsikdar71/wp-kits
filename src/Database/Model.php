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
     * Add a where condition.
     *
     * @param string $column
     * @param mixed $value
     * @return static
     *
     * @example
     * ```php
     * Model::query()->where('age', 30)->get();
     * ```
     */
    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = [$column, $value];
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
}
