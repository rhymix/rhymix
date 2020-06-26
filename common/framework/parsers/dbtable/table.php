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
	
	/**
	 * Generate the CREATE TABLE query for this table.
	 * 
	 * @param string $prefix
	 * @param string $charset
	 * @param string $engine
	 * @return string
	 */
	public function getCreateQuery(string $prefix = '', string $charset = 'utf8mb4', string $engine = 'innodb')
	{
		// Initialize the query.
		$result = 'CREATE TABLE `' . $prefix . $this->name . '` (';
		
		// Add columns.
		$columns = array();
		foreach ($this->columns as $column)
		{
			$columndef = '  `' . $column->name . '`' . ' ' . strtoupper($column->type);
			if ($column->size)
			{
				$columndef .= '(' . $column->size . ')';
			}
			if ($column->utf8mb4 === false && $charset === 'utf8mb4')
			{
				$columndef .= ' CHARACTER SET utf8 COLLATE utf8_unicode_ci';
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
			$idxcolumns = array_map(function($str) {
				return '`' . $str . '`';
			}, $index->columns);
			$idxtype = ($index->is_unique ? 'UNIQUE' : 'INDEX');
			$idxdef = '  ' . $idxtype . ' `' . $index->name . '` (' . implode(', ', $idxcolumns) . ')';
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
