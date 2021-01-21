<?php

namespace Rhymix\Framework\Parsers\DBTable;

/**
 * Table class.
 */
class Table
{
	public $name;
	public $columns = array();
	public $indexes = array();
	public $primary_key = array();
	public $constraints = array();
	public $deleted = false;
	
	/**
	 * Generate the CREATE TABLE query for this table.
	 * 
	 * @param string $prefix
	 * @param string $charset
	 * @param string $engine
	 * @return string
	 */
	public function getCreateQuery(string $prefix = '', string $charset = 'utf8mb4', string $engine = 'innodb'): string
	{
		// Initialize the query.
		$result = 'CREATE TABLE `' . $prefix . $this->name . '` (';
		
		// Add columns.
		$columns = array();
		$adjusted_sizes = array();
		foreach ($this->columns as $column)
		{
			$columndef = '  `' . $column->name . '`' . ' ' . strtoupper($column->type);
			$max_size = ($column->charset === 'utf8mb4' && $charset === 'utf8mb4') ? 191 : 255;
			if (preg_match('/char/i', $column->type) && $column->size > $max_size && ($column->is_unique || $column->is_primary_key))
			{
				$adjusted_sizes[$column->name] = $max_size;
			}
			if ($column->size)
			{
				$columndef .= '(' . (isset($adjusted_sizes[$column->name]) ? $adjusted_sizes[$column->name] : $column->size) . ')';
			}
			if ($column->charset !== 'utf8mb4' && $column->charset !== $charset)
			{
				if ($column->charset === 'utf8')
				{
					$columndef .= ' CHARACTER SET ' . $column->charset . ' COLLATE ' . $column->charset . '_unicode_ci';
				}
				else
				{
					$columndef .= ' CHARACTER SET ' . $column->charset . ' COLLATE ' . $column->charset . '_general_ci';
				}
			}
			if ($column->not_null)
			{
				$columndef .= ' NOT NULL';
			}
			if ($column->default_value !== null)
			{
				if (preg_match('/(?:int|float|double|decimal|number)/i', $column->type) && is_numeric($column->default_value))
				{
					$columndef .= ' DEFAULT ' . $column->default_value;
				}
				else
				{
					$columndef .= ' DEFAULT \'' . $column->default_value . '\'';
				}
			}
			if ($column->auto_increment)
			{
				$columndef .= ' AUTO_INCREMENT';
			}
			$columns[] = $columndef;
		}
		
		// Add indexes.
		if (count($this->primary_key))
		{
			$pkcolumns = array_map(function($str) {
				return '`' . $str . '`';
			}, $this->primary_key);
			$pkdef = '  ' . 'PRIMARY KEY (' . implode(', ', $pkcolumns) . ')';
			$columns[] = $pkdef;
		}
		foreach ($this->indexes as $index)
		{
			$idxcolumns = array();
			foreach ($index->columns as $column_name => $prefix_size)
			{
				$column_info = $this->columns[$column_name];
				$current_size = isset($adjusted_sizes[$column_name]) ? $adjusted_sizes[$column_name] : $column_info->size;
				$max_size = ($column_info->charset === 'utf8mb4' && $charset === 'utf8mb4') ? 191 : 255;
				if (preg_match('/char/i', $column_info->type) && $current_size > $max_size)
				{
					$prefix_size = $max_size;
				}
				$idxcolumns[] = '`' . $column_name . '`' . ($prefix_size > 0 ? "($prefix_size)" : '');
			}
			$idxtype = $index->type ? ($index->type . ' INDEX') : 'INDEX';
			$idxdef = '  ' . $idxtype . ' `' . $index->name . '` (' . implode(', ', $idxcolumns) . ')';
			if ($index->options)
			{
				$idxdef .= ' ' . $index->options;
			}
			$columns[] = $idxdef;
		}
		
		// Add constraints.
		foreach ($this->constraints as $constraint)
		{
			$contype = strtoupper($constraint->type);
			if ($contype === 'FOREIGN KEY')
			{
				$condef = '  ' . $contype . ' (`' . $constraint->column . '`)';
				list($reftable, $refcolumn) = explode('.', $constraint->references);
				$condef .= ' REFERENCES `' . $prefix . $reftable . '` (`' . $refcolumn . '`)';
				$condef .= ' ON DELETE ' . strtoupper($constraint->on_delete);
				$condef .= ' ON UPDATE ' . strtoupper($constraint->on_update);
			}
			if ($contype === 'CHECK')
			{
				$condef = '  ' . $contype . ' (' . $constraint->condition . ')';
			}
			$columns[] = $condef;
		}
		
		// Finish the query.
		$footer = '';
		if ($engine)
		{
			$footer .= ' ENGINE = ' . (strtolower($engine) === 'innodb' ? 'InnoDB' : 'MyISAM');
		}
		if ($charset)
		{
			$footer .= ' CHARACTER SET ' . $charset . ' COLLATE ' . $charset . '_unicode_ci';
		}
		$result .= "\n" . implode(",\n", $columns);
		$result .= "\n" . ') ' . $footer . ';';
		
		return $result;
	}
}
