<?php

namespace Rhymix\Framework\Parsers;

/**
 * DB table parser class for XE compatibility.
 */
class DBTableParser
{
	/**
	 * Mapping for XE-compatible types.
	 */
	protected static $_xe_types = array(
		'bignumber' => 'bigint',
		'number' => 'bigint',
		'bigtext' => 'longtext',
		'date' => 'char(14)',
	);
	
	/**
	 * List of types for which the size attribute will be ignored.
	 */
	protected static $_nosize_types = array(
		'bigint' => true,
		'int' => true,
	);
	
	/**
	 * Load a table definition XML file.
	 * 
	 * @param string $filename
	 * @return object|false
	 */
	public static function loadXML(string $filename)
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return false;
		}
		
		// Initialize table definition.
		$table = new DBTable\Table;
		$table->name = preg_replace('/\.xml$/', '', basename($filename));
		
		// Load columns.
		foreach ($xml->column as $column_info)
		{
			// Get the column name and type.
			$column = new DBTable\Column;
			$column->name = strval($column_info['name']);
			$column->type = strval($column_info['type']);
			
			// Map XE-compatible types to database native types.
			if (isset(self::$_xe_types[$column->type]))
			{
				$column->xetype = $column->type;
				$column->type = self::$_xe_types[$column->type];
			}
			else
			{
				$column->xetype = $column->type;
			}
			
			// Get the size.
			if (preg_match('/^([a-z0-9_]+)\(([0-9,\s]+)\)$/i', $column->type, $matches))
			{
				$column->type = $matches[1];
				$column->size = $matches[2];
			}
			if (isset($column_info['size']))
			{
				$column->size = strval($column_info['size']);
			}
			$column->size = implode(',', array_map('trim', explode(',', $column->size))) ?: null;
			if (isset(self::$_nosize_types[$column->type]))
			{
				$column->size = null;
			}
			
			// Get the utf8mb4 attribute.
			if (isset($column_info['utf8mb4']))
			{
				$column->utf8mb4 = toBool(strval($column_info['utf8mb4']));
			}
			
			// Get the default value.
			if (isset($column_info['default']))
			{
				$column->default_value = strval($column_info['default']);
			}
			
			// Get the NOT NULL attribute.
			if (isset($column_info['notnull']) || isset($column_info['not-null']))
			{
				$attr = strval($column_info['notnull'] ?: $column_info['not-null']);
				$column->not_null = ($attr === 'notnull' || $attr === 'not-null' || toBool($attr));
			}
			
			// Get index information.
			if (isset($column_info['index']))
			{
				$index_name = strval($column_info['index']);
				if (!isset($table->indexes[$index_name]))
				{
					$table->indexes[$index_name] = new DBTable\Index;
					$table->indexes[$index_name]->name = $index_name;
				}
				$table->indexes[$index_name]->columns[] = $column->name;
			}
			if (isset($column_info['unique']))
			{
				$index_name = strval($column_info['unique']);
				if (!isset($table->indexes[$index_name]))
				{
					$table->indexes[$index_name] = new DBTable\Index;
					$table->indexes[$index_name]->name = $index_name;
					$table->indexes[$index_name]->is_unique = true;
				}
				$table->indexes[$index_name]->columns[] = $column->name;
			}
			
			// Get primary key information.
			if (isset($column_info['primary_key']) || isset($column_info['primary-key']))
			{
				$attr = strval($column_info['primary_key'] ?: $column_info['primary-key']);
				if ($attr === 'primary_key' || $attr === 'primary-key' || toBool($attr))
				{
					$table->primary_key[] = $column->name;
					$column->is_primary_key = true;
				}
			}
			
			// Get auto-increment information.
			if (isset($column_info['auto_increment']) || isset($column_info['auto-increment']))
			{
				$attr = strval($column_info['auto_increment'] ?: $column_info['auto-increment']);
				if ($attr === 'auto_increment' || $attr === 'auto-increment' || toBool($attr))
				{
					$column->auto_increment = true;
				}
			}			
			
			// Add the column to the table definition.
			$table->columns[$column->name] = $column;
		}
		
		// Load indexes.
		foreach ($xml->index as $index_info)
		{
			$index = new DBTable\Index;
			$index->name = strval($index_info['name']);
			$index->columns = array_map('trim', explode(',', strval($index_info['columns'])));
			$index->is_unique = ($index_info['unique'] === 'unique' || toBool(strval($index_info['unique'])));
			$table->indexes[$index->name] = $index;
		}
		
		// Load other constraints (foreign keys).
		foreach ($xml->constraint as $const_info)
		{
			$const = new DBTable\Constraint;
			$const->type = strtolower($const_info['type']);
			$const->column = strval($const_info['column']);
			$const->references = strval($const_info['references']);
			$const->on_update = strtolower($const_info['on_update'] ?: $const_info['on-update']);
			$const->on_delete = strtolower($const_info['on_delete'] ?: $const_info['on-delete']);
			$table->constraints[] = $const;
		}
		
		// Return the complete table definition.
		return $table;
	}
}
