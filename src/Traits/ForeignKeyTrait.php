<?php

namespace MDMasudSikdar\WpKits\Traits;

/**
 * Trait ForeignKeyTrait
 *
 * Provides fluent foreign key support for Schema tables in WordPress.
 *
 * Features:
 * ✅ Fluent foreign key definition using `foreignId()`, `references()`, `on()`, `onDelete()`, `onUpdate()`.
 * ✅ Automatically tracks pending foreign keys for `build()` execution.
 * ✅ Supports multiple foreign keys per table.
 *
 * Example usage:
 * ```php
 * Schema::create('posts', function ($table) {
 *     $table->increments('id');
 *
 *     // Add a foreign key referencing users.id
 *     $table->foreignId('user_id')
 *           ->references('id')
 *           ->on('users')
 *           ->onDelete('CASCADE')
 *           ->onUpdate('CASCADE');
 * });
 * ```
 *
 * @package MDMasudSikdar\WpKits\Traits
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
     * Action to perform on delete (CASCADE, SET NULL, RESTRICT, NO ACTION).
     *
     * @var string|null
     */
    protected ?string $fkOnDelete = null;

    /**
     * Action to perform on update (CASCADE, SET NULL, RESTRICT, NO ACTION).
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
     * This method prepares the column and sets it as the current foreign key in the trait.
     * It also resets any previous foreign key state to prevent accidental carryover.
     *
     * @param string $column Column name
     * @return self
     *
     * @example
     * ```php
     * $table->foreignId('user_id'); // Adds `user_id` INT UNSIGNED NOT NULL
     * ```
     */
    public function foreignId(string $column): self
    {
        // Add the column definition to the schema
        $this->columns[] = "`$column` INT UNSIGNED NOT NULL";

        // Set as current foreign key column
        $this->fkColumn = $column;

        // Reset all other FK properties
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
     *
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
     * The table name will automatically include the WordPress prefix.
     *
     * @param string $table Table name without prefix
     * @return self
     *
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
     * Converts the action to uppercase automatically.
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return self
     *
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
     * Converts the action to uppercase automatically.
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return self
     *
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
     * This method is automatically called during build().
     * Ensures only complete foreign key definitions are stored.
     *
     * @return void
     *
     * @example
     * ```php
     * // Called internally before build
     * $this->addPendingForeignKey();
     * ```
     */
    protected function addPendingForeignKey(): void
    {
        // Only add if all required fields are set
        if (!$this->fkColumn || !$this->fkReferences || !$this->fkOn) {
            return;
        }

        // Add to pending foreign keys array
        $this->foreignKeys[] = [
            'column'     => $this->fkColumn,
            'references' => $this->fkReferences,
            'on'         => $this->fkOn,
            'onDelete'   => $this->fkOnDelete,
            'onUpdate'   => $this->fkOnUpdate,
        ];

        // Reset current FK state
        $this->fkColumn = null;
        $this->fkReferences = null;
        $this->fkOn = null;
        $this->fkOnDelete = null;
        $this->fkOnUpdate = null;
    }

    /**
     * Append all pending foreign keys to the columns array.
     *
     * This method is called automatically from Schema::build().
     * Generates SQL constraint strings for each foreign key.
     *
     * @return void
     *
     * @example
     * ```php
     * $this->appendForeignKeys(); // Appends all FK constraints to $columns
     * ```
     */
    protected function appendForeignKeys(): void
    {
        // Ensure last FK in progress is added
        $this->addPendingForeignKey();

        // Loop through all pending foreign keys
        foreach ($this->foreignKeys as $fk) {
            // Generate a unique foreign key name
            $fkName = "{$this->table}_{$fk['column']}_fk";

            // Build SQL constraint
            $sql = "CONSTRAINT `$fkName` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}` (`{$fk['references']}`)";

            // Append ON DELETE clause if set
            if ($fk['onDelete']) {
                $sql .= " ON DELETE {$fk['onDelete']}";
            }

            // Append ON UPDATE clause if set
            if ($fk['onUpdate']) {
                $sql .= " ON UPDATE {$fk['onUpdate']}";
            }

            // Add the SQL to the columns array
            $this->columns[] = $sql;
        }

        // Clear all pending foreign keys
        $this->foreignKeys = [];
    }
}
