<?php

namespace MDMasudSikdar\WpKits\Traits;

/**
 * Trait ForeignKeyTrait
 *
 * Provides fluent foreign key support for Schema tables.
 * Foreign keys are automatically added when build() is called.
 *
 * Example usage:
 * ```php
 * Schema::create('posts', function ($table) {
 *     $table->increments('id');
 *     $table->foreignId('user_id')
 *           ->references('id')
 *           ->on('users')
 *           ->onDelete('cascade')
 *           ->onUpdate('cascade');
 * });
 * ```
 */
trait ForeignKeyTrait
{
    /**
     * Current foreign key column being defined.
     *
     * @var string|null
     */
    protected ?string $fkColumn = null;

    /**
     * Column referenced by the foreign key.
     *
     * @var string|null
     */
    protected ?string $fkReferences = null;

    /**
     * Table referenced by the foreign key.
     *
     * @var string|null
     */
    protected ?string $fkOn = null;

    /**
     * Action on delete (CASCADE, SET NULL, RESTRICT).
     *
     * @var string|null
     */
    protected ?string $fkOnDelete = null;

    /**
     * Action on update (CASCADE, SET NULL, RESTRICT).
     *
     * @var string|null
     */
    protected ?string $fkOnUpdate = null;

    /**
     * List of all pending foreign keys to append during build().
     *
     * @var array<int,array<string,string|null>>
     */
    protected array $foreignKeys = [];

    /**
     * Define an unsigned foreign ID column.
     *
     * Adds an `INT UNSIGNED NOT NULL` column and prepares for foreign key chaining.
     *
     * @param string $column Column name
     * @return self
     * @example
     * $table->foreignId('user_id'); // Adds `user_id` INT UNSIGNED NOT NULL
     */
    public function foreignId(string $column): self
    {
        $this->columns[] = "`$column` INT UNSIGNED NOT NULL";
        $this->fkColumn = $column;

        // Reset other FK properties
        $this->fkReferences = null;
        $this->fkOn = null;
        $this->fkOnDelete = null;
        $this->fkOnUpdate = null;

        return $this;
    }

    /**
     * Set the column that this foreign key references.
     *
     * @param string $column Column name in the referenced table
     * @return self
     * @example
     * $table->foreignId('user_id')->references('id');
     */
    public function references(string $column): self
    {
        $this->fkReferences = $column;
        return $this;
    }

    /**
     * Set the table that this foreign key references.
     *
     * @param string $table Table name without prefix
     * @return self
     * @example
     * $table->foreignId('user_id')->on('users');
     */
    public function on(string $table): self
    {
        $this->fkOn = $this->wpdb->prefix . $table;
        return $this;
    }

    /**
     * Set the ON DELETE action for the foreign key.
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return self
     * @example
     * $table->foreignId('user_id')->onDelete('cascade');
     */
    public function onDelete(string $action): self
    {
        $this->fkOnDelete = strtoupper($action);
        return $this;
    }

    /**
     * Set the ON UPDATE action for the foreign key.
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return self
     * @example
     * $table->foreignId('user_id')->onUpdate('cascade');
     */
    public function onUpdate(string $action): self
    {
        $this->fkOnUpdate = strtoupper($action);
        return $this;
    }

    /**
     * Add the current foreign key definition to the pending list.
     *
     * Called automatically during build() in the Schema class.
     *
     * @return void
     */
    protected function addPendingForeignKey(): void
    {
        if (!$this->fkColumn || !$this->fkReferences || !$this->fkOn) {
            return;
        }

        $this->foreignKeys[] = [
            'column'     => $this->fkColumn,
            'references' => $this->fkReferences,
            'on'         => $this->fkOn,
            'onDelete'   => $this->fkOnDelete,
            'onUpdate'   => $this->fkOnUpdate,
        ];

        // Reset current FK
        $this->fkColumn = null;
        $this->fkReferences = null;
        $this->fkOn = null;
        $this->fkOnDelete = null;
        $this->fkOnUpdate = null;
    }

    /**
     * Append all pending foreign keys to the columns array.
     *
     * Called automatically from Schema::build().
     *
     * @return void
     */
    protected function appendForeignKeys(): void
    {
        // Add last FK if still in progress
        $this->addPendingForeignKey();

        foreach ($this->foreignKeys as $fk) {
            $fkName = "{$this->table}_{$fk['column']}_fk";
            $sql = "CONSTRAINT `$fkName` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}` (`{$fk['references']}`)";

            if ($fk['onDelete']) {
                $sql .= " ON DELETE {$fk['onDelete']}";
            }

            if ($fk['onUpdate']) {
                $sql .= " ON UPDATE {$fk['onUpdate']}";
            }

            $this->columns[] = $sql;
        }

        // Clear pending FKs
        $this->foreignKeys = [];
    }
}
