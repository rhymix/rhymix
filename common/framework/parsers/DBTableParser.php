<?php

namespace Rhymix\Framework\Parsers;

/**
 * DB table parser class for XE compatibility.
 */
class DBTableParser extends BaseParser
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
		'integer' => true,
	);
	
	/**
	 * Load a table definition XML file.
	 * 
	 * @param string $filename
	 * @param string $content
	 * @return object|false
	 */
	public static function loadXML(string $filename = '', string $content = '')
	{
		// Load the XML content.
		if ($content)
		{
			$xml = simplexml_load_string($content);
		}
		else
		{
			$xml = simplexml_load_string(file_get_contents($filename));
		}
		
		if ($xml === false)
		{
			return false;
		}
		
		// Initialize table definition.
		$table = new DBTable\Table;
		if ($filename)
		{
			$table->name = preg_replace('/\.xml$/', '', basename($filename));
		}
		else
		{
			$table->name = strval($xml['name']);
		}
		
		$deleted = strval($xml['deleted']);
		if ($deleted !== '')
		{
			$table->deleted = toBool($deleted);
		}
		
		// Load columns.
		foreach ($xml->column as $column_info)
		{
			// Get the column name and type.
			$column = new DBTable\Column;
			$column->name = strval($column_info['name']);
			list($column->type, $column->xetype, $column->size) = self::getTypeAndSize(strval($column_info['type']), strval($column_info['size']));
			
			// Get all attributes.
			$attribs = self::_getAttributes($column_info);
			
			// Get the charset/utf8mb4 attribute.
			if (isset($attribs['charset']))
			{
				$column->charset = $attribs['charset'];
			}
			elseif (isset($attribs['utf8mb4']))
			{
				$column->charset = toBool($attribs['utf8mb4']) ? 'utf8mb4' : 'utf8';
			}
			elseif ($column->xetype === 'date' || ($column->name === 'ipaddress' && $column->size >= 60) || ($column->type === 'char' && $column->size == 1))
			{
				$column->charset = 'latin1';
			}
			
			// Get the default value.
			if (isset($attribs['default']))
			{
				$column->default_value = $attribs['default'];
			}
			
			// Get the NOT NULL attribute.
			if (isset($attribs['notnull']))
			{
				$column->not_null = true;
			}
			
			// Get index information.
			if (isset($attribs['index']))
			{
				$index_name = $attribs['index'];
				if (!isset($table->indexes[$index_name]))
				{
					$table->indexes[$index_name] = new DBTable\Index;
					$table->indexes[$index_name]->name = $index_name;
				}
				$table->indexes[$index_name]->columns[$column->name] = 0;
				$column->is_indexed = true;
			}
			if (isset($attribs['unique']))
			{
				$index_name = $attribs['unique'];
				if (!isset($table->indexes[$index_name]))
				{
					$table->indexes[$index_name] = new DBTable\Index;
					$table->indexes[$index_name]->name = $index_name;
					$table->indexes[$index_name]->type = 'UNIQUE';
				}
				$table->indexes[$index_name]->columns[$column->name] = 0;
				$column->is_indexed = true;
				$column->is_unique = true;
			}
			
			// Get primary key information.
			if (isset($attribs['primarykey']) && toBool($attribs['primarykey']))
			{
				$table->primary_key[] = $column->name;
				$column->is_indexed = true;
				$column->is_unique = true;
				$column->is_primary_key = true;
			}
			
			// Get auto-increment information.
			if (isset($attribs['autoincrement']) && toBool($attribs['autoincrement']))
			{
				$column->auto_increment = true;
			}			
			
			// Add the column to the table definition.
			$table->columns[$column->name] = $column;
		}
		
		// Load indexes.
		foreach ($xml->index as $index_info)
		{
			// Get the index name and list of columns.
			$index_info = self::_getAttributes($index_info);
			$index = new DBTable\Index;
			$index->name = $index_info['name'];
			$idxcolumns = array_map('trim', explode(',', $index_info['columns'] ?? $index_info['column']));
			foreach ($idxcolumns as $idxcolumn)
			{
				if (preg_match('/^(\S+)\s*\(([0-9]+)\)$/', $idxcolumn, $matches))
				{
					$index->columns[$matches[1]] = intval($matches[2]);
					$idxcolumn = $matches[1];
				}
				else
				{
					$index->columns[$idxcolumn] = 0;
				}
			}
			
			// Get the index type.
			if (isset($index_info['type']) && $index_info['type'])
			{
				$index->type = strtoupper($index_info['type']);
			}
			elseif (isset($index_info['unique']) && toBool($index_info['unique']))
			{
				$index->type = 'UNIQUE';
			}
			
			// Set attributes on indexed columns.
			if (isset($table->columns[$idxcolumn]) && is_object($table->columns[$idxcolumn]))
			{
				$table->columns[$idxcolumn]->is_indexed = true;
				$table->columns[$idxcolumn]->is_unique = $index->type === 'UNIQUE' ? true : $table->columns[$idxcolumn]->is_unique;
			}
			
			// If any index options are given, also store them in the index class.
			if (isset($index_info['options']) && $index_info['options'])
			{
				$index->options = $index_info['options'];
			}
			
			// Add the index to the column definition.
			$table->indexes[$index->name] = $index;
		}
		
		// Load other constraints (foreign keys).
		foreach ($xml->constraint as $const_info)
		{
			$const_info = self::_getAttributes($const_info);
			$constraint = new DBTable\Constraint;
			$constraint->type = strtoupper($const_info['type'] ?? '');
			$constraint->column = ($const_info['column'] ?? null) ?: null;
			$constraint->references = ($const_info['references'] ?? null) ?: null;
			$constraint->condition = ($const_info['condition'] ?? null) ?: null;
			$constraint->on_delete = ($const_info['ondelete'] ?? null) ?: $constraint->on_delete;
			$constraint->on_update = ($const_info['onupdate'] ?? null) ?: $constraint->on_update;
			$table->constraints[] = $constraint;
		}
		
		// Return the complete table definition.
		return $table;
	}
	
	/**
	 * Get column type and size.
	 * 
	 * @param string $type
	 * @param string $size
	 * @return array
	 */
	public static function getTypeAndSize(string $type, string $size): array
	{
		// Map XE-compatible types to database native types.
		if (isset(self::$_xe_types[$type]))
		{
			$xetype = $type;
			$type = self::$_xe_types[$type];
		}
		else
		{
			$xetype = 'none';
			$type = ltrim($type, '\\');
		}
		
		// Extract and normalize the size.
		if (preg_match('/^([a-z0-9_]+)\(([0-9,\s]+)\)$/i', $type, $matches))
		{
			$type = $matches[1];
			$size = $matches[2];
		}
		$size = implode(',', array_map('trim', explode(',', $size))) ?: null;
		if (isset(self::$_nosize_types[$type]))
		{
			$size = null;
		}
		
		// Return a complete array.
		return [$type, $xetype, $size];
	}
	
	/**
	 * Get the XE-compatible type from a real database type.
	 * 
	 * @param string $type
	 * @param string $size
	 * @return string
	 */
	public static function getXEType(string $type, string $size): string
	{
		$type = strtolower($type);
		switch ($type)
		{
			case 'bigint':
				return 'bignumber';
			case 'int':
			case 'integer':
				return 'number';
			case 'longtext':
				return 'bigtext';
			case 'char':
			case 'varchar':
				if ($size == 14)
				{
					return 'date';
				}
			default:
				return $type;
		}
	}
	
	/**
	 * Order tables according to foreign key relations.
	 * 
	 * @param array $tables [$table_name => $filename]
	 * @return array
	 */
	public static function resolveDependency(array $tables): array
	{
		// Compile the list of each table's dependency.
		$ref_list = [];
		$i = 0;
		foreach ($tables as $table_name => $filename)
		{
			$table = self::loadXML($filename);
			if ($table)
			{
				$info = (object)['name' => $table_name, 'refs' => [], 'index' => $i++];
				foreach ($table->constraints as $constraint)
				{
					if ($constraint->references)
					{
						$ref = explode('.', $constraint->references);
						$info->refs[] = $ref[0];
					}
				}
				$ref_list[$table_name] = $info;
			}
		}
		
		// Sort each table after the ones they are dependent on.
		for ($j = 0; $j < count($ref_list); $j++)
		{
			$changed = false;
			foreach ($ref_list as $table_name => $info)
			{
				if (count($info->refs))
				{
					foreach ($info->refs as $ref_name)
					{
						if (isset($ref_list[$ref_name]) && $info->index <= $ref_list[$ref_name]->index)
						{
							$info->index = $ref_list[$ref_name]->index + 1;
							$changed = true;
						}
					}
				}
				$k++;
			}
			if (!$changed)
			{
				break;
			}
		}
		
		uasort($ref_list, function($a, $b) {
			return $a->index - $b->index;
		});
		
		// Produce a result in the same format as the input.
		$result = [];
		foreach ($ref_list as $table_name => $info)
		{
			$result[$table_name] = $tables[$table_name];
		}
		return $result;
	}
}
