<?php

namespace Rhymix\Framework\Helpers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\DBError;
use Rhymix\Framework\Helpers\DBHelper;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Parsers\DBTableParser;

class DBTableHelper
{
	/**
	 * The table name.
	 */
	protected string $_table_name;

	/**
	 * The prefix.
	 */
	protected string $_prefix;

	/**
	 * The DB instance.
	 */
	protected DB $_db;

	/**
	 * The DB handle.
	 */
	protected DBHelper $_handle;

	/**
	 * Pending changes.
	 */
	protected array $_pending_changes = [];

	/**
	 * Constructor.
	 */
	public function __construct(string $table_name, string $prefix, DB $db)
	{
		$this->_table_name = $table_name;
		$this->_prefix = $prefix;
		$this->_db = $db;
		$this->_handle = $db->getHandle();
	}

	/**
	 * Get the un-prefixed table name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->_table_name;
	}

	/**
	 * Get the prefixed table name.
	 *
	 * @return string
	 */
	public function getFullName(): string
	{
		return $this->_prefix . $this->_table_name;
	}

	/**
	 * Check if the table exists.
	 *
	 * @return bool
	 */
	public function exists(): bool
	{
		$stmt = $this->_handle->query(vsprintf("SHOW TABLES LIKE '%s'", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
		]));
		$result = $stmt ? $stmt->fetchAll() : [];
		return count($result) > 0;
	}

	/**
	 * Check if a column exists.
	 *
	 * @param string $column_name
	 * @return bool
	 */
	public function columnExists(string $column_name): bool
	{
		$stmt = $this->_handle->query(vsprintf("SHOW FIELDS FROM `%s` WHERE Field = '%s'", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
			$this->_db->addQuotes($column_name),
		]));
		$result = $stmt ? $stmt->fetchAll() : [];
		return count($result) > 0;
	}

	/**
	 * Get column information.
	 *
	 * @param string $column_name
	 * @return ?object
	 */
	public function getColumnInfo(string $column_name): ?object
	{
		// If column information is not found, return null.
		$stmt = $this->_handle->query(vsprintf("SHOW FULL COLUMNS FROM `%s` WHERE Field = '%s'", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
			$this->_db->addQuotes($column_name),
		]));
		$column_info = $stmt ? array_first($stmt->fetchAll(\PDO::FETCH_OBJ)) : null;
		if (!$column_info)
		{
			return null;
		}

		// Reorganize the type information.
		$dbtype = strtolower($column_info->{'Type'});
		if (preg_match('/^([a-z0-9_]+)\(([0-9,\s]+)\)$/i', $dbtype, $matches))
		{
			$dbtype = $matches[1];
			$size = $matches[2];
		}
		else
		{
			$size = '';
		}
		$xetype = DBTableParser::getXEType($dbtype, $size ?: '');

		// Detect the character set.
		if (preg_match('/^([a-zA-Z0-9]+)/', $column_info->{'Collation'} ?? '', $matches))
		{
			$charset = $matches[1] === 'utf8mb3' ? 'utf8' : $matches[1];
		}
		else
		{
			$charset = null;
		}

		// Return the result as an object.
		return (object)array(
			'name' => $column_name,
			'dbtype' => $dbtype,
			'xetype' => $xetype,
			'size' => $size,
			'default_value' => $column_info->{'Default'},
			'notnull' => strncmp($column_info->{'Null'}, 'NO', 2) == 0 ? true : false,
			'charset' => $charset,
			'collation' => $column_info->{'Collation'} ?: null,
		);
	}

	/**
	 * Add a column.
	 *
	 * @param string $column_name
	 * @param string $type
	 * @param ?string $size
	 * @param array $options
	 * @return self
	 */
	public function addColumn(string $column_name, string $type = 'number', ?string $size = null, array $options = []): self
	{
		// Normalize the type and size.
		list($type, $xetype, $size) = DBTableParser::getTypeAndSize($type, strval($size));

		// Compose the ADD COLUMN query.
		$query = sprintf("ADD COLUMN `%s` ", $this->_db->addQuotes($column_name));
		$query .= $size ? sprintf('%s(%s)', $type, $size) : $type;
		$query .= !empty($options['notnull']) ? ' NOT NULL' : '';

		// Add the default value according to the type.
		if (isset($options['default']))
		{
			if (contains('int', $type, false) && is_numeric($options['default']))
			{
				$query .= sprintf(" DEFAULT %s", $options['default']);
			}
			else
			{
				$query .= sprintf(" DEFAULT '%s'", $this->_db->addQuotes($options['default']));
			}
		}

		// Add position information.
		if (isset($options['after']))
		{
			if ($options['after'] === 'FIRST')
			{
				$query .= ' FIRST';
			}
			else
			{
				$query .= sprintf(' AFTER `%s`', $this->_db->addQuotes($options['after']));
			}
		}

		// Add the query to the pending list and return $this for method chaining.
		$this->_pending_changes[] = $query;
		return $this;
	}

	/**
	 * Modify a column.
	 *
	 * @param string $column_name
	 * @param string $type
	 * @param string $size
	 * @param array $options
	 * @return self
	 */
	public function modifyColumn(string $column_name, string $type = 'number', $size = null, array $options = []): self
	{
		// Normalize the type and size.
		list($type, $xetype, $size) = DBTableParser::getTypeAndSize($type, strval($size));

		// Compose the MODIFY COLUMN query.
		if (isset($options['new_name']) && $options['new_name'] !== $column_name)
		{
			$query = sprintf("CHANGE `%s` `%s` ", $this->_db->addQuotes($column_name), $this->_db->addQuotes($options['new_name']));
		}
		else
		{
			$query = sprintf("MODIFY `%s` ", $this->_db->addQuotes($column_name));
		}
		$query .= $size ? sprintf('%s(%s)', $type, $size) : $type;

		// Add the character set information.
		if (isset($options['new_charset']))
		{
			$charset = $options['new_charset'];
			$new_collation = preg_match('/^utf8/i', $charset) ? ($charset . '_unicode_ci') : ($charset . '_general_ci');
			$query .= ' CHARACTER SET ' . $charset . ' COLLATE ' . $new_collation;
		}

		// Add the NOT NULL constraint.
		$query .= !empty($options['notnull']) ? ' NOT NULL' : '';

		// Add the default value according to the type.
		if (isset($options['default']))
		{
			if (contains('int', $type, false) && is_numeric($options['default']))
			{
				$query .= sprintf(" DEFAULT %s", $options['default']);
			}
			else
			{
				$query .= sprintf(" DEFAULT '%s'", $this->_db->addQuotes($options['default']));
			}
		}

		// Add position information.
		if (isset($options['after']))
		{
			if ($options['after'] === 'FIRST')
			{
				$query .= ' FIRST';
			}
			else
			{
				$query .= sprintf(' AFTER `%s`', $this->_db->addQuotes($options['after']));
			}
		}

		// Add the query to the pending list and return $this for method chaining.
		$this->_pending_changes[] = $query;
		return $this;
	}

	/**
	 * Drop a column.
	 *
	 * @param string $column_name
	 * @return self
	 */
	public function dropColumn(string $column_name): self
	{
		// Add the query to the pending list and return $this for method chaining.
		$this->_pending_changes[] = sprintf("DROP `%s`", $this->_db->addQuotes($column_name));
		return $this;
	}

	/**
	 * Check if an index exists.
	 *
	 * @param string $index_name
	 * @return boolean
	 */
	public function indexExists(string $index_name): bool
	{
		$stmt = $this->_handle->query(vsprintf("SHOW INDEX FROM `%s` WHERE Key_name = '%s'", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
			$this->_db->addQuotes($index_name),
		]));
		$result = $stmt ? $stmt->fetchAll() : [];
		return count($result) > 0;
	}

	/**
	 * Get index information.
	 *
	 * @param string $index_name
	 * @return ?object
	 */
	public function getIndexInfo(string $index_name): ?object
	{
		// If the index is not found, return null.
		$stmt = $this->_handle->query(vsprintf("SHOW INDEX FROM `%s` WHERE Key_name = '%s'", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
			$this->_db->addQuotes($index_name),
		]));
		$index_info = $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
		if (!$index_info)
		{
			return null;
		}

		// Get the list of columns included in the index.
		$is_unique = false;
		$columns = [];
		foreach ($index_info as $column)
		{
			if (!$column->Non_unique)
			{
				$is_unique = true;
			}
			$columns[] = (object)[
				'name' => $column->Column_name,
				'size' => $column->Sub_part ? intval($column->Sub_part) : null,
				'cardinality' => $column->Cardinality ? intval($column->Cardinality) : null,
			];
		}

		// Return the result as an object.
		return (object)array(
			'name' => $column->Key_name,
			'table' => $column->Table,
			'type' => $column->Index_type,
			'is_unique' => $is_unique,
			'columns' => $columns,
		);
	}

	/**
	 * Add an index.
	 *
	 * @param string $index_name
	 * @param array $columns
	 * @param string|bool|int $type
	 * @param string $options
	 * @return self
	 */
	public function addIndex(string $index_name, $columns, $type = '', $options = ''): self
	{
		if (!is_array($columns))
		{
			$columns = array($columns);
		}

		if ($type === true || $type === 1)
		{
			$type = 'UNIQUE';
		}

		$query = vsprintf("ADD %s `%s` (%s) %s", [
			ltrim($type . ' INDEX'),
			$this->_db->addQuotes($index_name),
			implode(', ', array_map(function($column_name) {
				if (preg_match('/^([^()]+)\(([0-9]+)\)$/', $column_name, $matches))
				{
					return '`' . $this->_db->addQuotes($matches[1]) . '`(' . $matches[2] . ')';
				}
				else
				{
					return '`' . $this->_db->addQuotes($column_name) . '`';
				}
			}, $columns)),
			$options,
		]);

		// Add the query to the pending list and return $this for method chaining.
		$this->_pending_changes[] = $query;
		return $this;
	}

	/**
	 * Drop an index.
	 *
	 * @param string $index_name
	 * @return self
	 */
	public function dropIndex(string $index_name): self
	{
		// Add the query to the pending list and return $this for method chaining.
		$this->_pending_changes[] = sprintf("DROP INDEX `%s`", $this->_db->addQuotes($index_name));
		return $this;
	}

	/**
	 * Apply pending changes.
	 *
	 * @return DBResultHelper
	 */
	public function applyChanges(): DBResultHelper
	{
		// If there are no pending changes, return a successful result.
		if (!count($this->_pending_changes))
		{
			return new DBResultHelper;
		}

		// Concatenate all pending changes into a single ALTER TABLE query.
		$query = vsprintf("ALTER TABLE `%s` %s", [
			$this->_db->addQuotes($this->_prefix . $this->_table_name),
			implode(', ', $this->_pending_changes),
		]);

		// Execute the query and return the result.
		$result = $this->_handle->exec($query);
		return $result ? new DBResultHelper : $this->_db->getError();
	}
}
