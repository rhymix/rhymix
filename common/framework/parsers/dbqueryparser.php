<?php

namespace Rhymix\Framework\Parsers;

/**
 * DB query parser class for XE compatibility.
 */
class DBQueryParser
{
	/**
	 * Load a query XML file.
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
		
		// Parse the query.
		$query_name = preg_replace('/\.xml$/', '', basename($filename));
		$query = self::_parseQuery($xml, $query_name);
		
		return $query;
	}
	
	/**
	 * Parse a query.
	 * 
	 * @param SimpleXMLElement $xml
	 * @param string $name
	 * @return object
	 */
	protected static function _parseQuery(\SimpleXMLElement $xml, string $name = ''): DBQuery\Query
	{
		// Load basic information about this query.
		$query = new DBQuery\Query;
		$query->name = $name ?: null;
		$query->alias = trim($xml['alias']) ?: null;
		if ($query->alias && !$query->name)
		{
			$query->name = $query->alias;
		}
		$query->type = trim($xml['action']);
		
		// Load tables.
		foreach ($xml->tables->table as $tag)
		{
			if (trim($tag['query']) === 'true')
			{
				$table = self::_parseQuery($tag);
				$query->tables[$table->alias] = $table;
			}
			else
			{
				$table = new DBQuery\Table;
				$table->name = trim($tag['name']);
				$table->alias = trim($tag['alias']) ?: $table->name;
				$table_type = trim($tag['type']);
				if (stripos($table_type, 'join') !== false)
				{
					$table->join_type = strtoupper($table_type);
					if ($tag->conditions)
					{
						$table->join_conditions = self::_parseConditions($tag->conditions);
					}
				}
				$query->tables[$table->alias] = $table;
			}
		}
		
		// Load columns.
		foreach ($xml->columns->column as $tag)
		{
			$column = new DBQuery\Column;
			$column->name = trim($tag['name']);
			$column->alias = trim($tag['alias']) ?: null;
			if ($column->name === '*' || preg_match('/\.\*$/', $column->name))
			{
				$column->is_wildcard = true;
			}
			if (!self::_isValidColumnName($column->name))
			{
				$column->is_expression = true;
			}
			$query->columns[] = $column;
		}
		
		// Load conditions.
		if ($xml->conditions)
		{
			$query->conditions = self::_parseConditions($xml->conditions);
		}
		
		// Load groups.
		if ($xml->groups)
		{
			$query->groupby = new DBQuery\GroupBy;
			foreach ($xml->groups->children() as $tag)
			{
				$name = $tag->getName();
				if ($name === 'group')
				{
					$query->groupby->columns[] = trim($tag['column']);
				}
				elseif ($name === 'having')
				{
					$query->groupby->having = self::_parseConditions($tag);
				}
			}
		}
		
		// Load navigation settings.
		if ($xml->navigation)
		{
			$query->navigation = new DBQuery\Navigation;
			foreach ($xml->navigation->index as $tag)
			{
				$orderby = new DBQuery\OrderBy;
				$orderby->var = trim($tag['var']) ?: null;
				$orderby->default = trim($tag['default']) ?: null;
				$orderby->order_var = trim($tag['order']) ?: null;
				$query->navigation->orderby[] = $orderby;
			}
			foreach (['list_count', 'page_count', 'page', 'offset'] as $key)
			{
				if ($tag = $xml->navigation->{$key})
				{
					$query->navigation->{$key} = new DBQuery\GenericVar;
					$query->navigation->{$key}->var = trim($tag['var']) ?: null;
					$query->navigation->{$key}->default = trim($tag['default']) ?: null;
				}
			}
		}
		
		// Return the complete query definition.
		return $query;
	}
	
	/**
	 * Parse conditions.
	 * 
	 * @param SimpleXMLElement $parent
	 * @return array
	 */
	protected static function _parseConditions(\SimpleXMLElement $parent): array
	{
		$result = array();
		foreach ($parent->children() as $tag)
		{
			$name = $tag->getName();
			if ($name === 'condition')
			{
				$cond = new DBQuery\Condition;
				$cond->operation = trim($tag['operation']);
				$cond->column = trim($tag['column']);
				$cond->var = trim($tag['var']);
				$cond->default = trim($tag['default']);
				$cond->not_null = trim($tag['notnull'] ?: $tag['not-null']) !== '' ? true : false;
				$cond->operator = strtoupper($tag['pipe']) ?: 'AND';
				$result[] = $cond;
			}
			elseif ($name === 'group')
			{
				$group = new DBQuery\ConditionGroup;
				$group->conditions = self::_parseConditions($tag);
				$group->operator = strtoupper($tag['pipe']) ?: 'AND';
				$result[] = $group;
			}
		}
		
		return $result;
	}
	
	/**
	 * Check if an expression might be a valid column name.
	 * 
	 * @param string $column_name
	 * @return bool
	 */
	protected static function _isValidColumnName(string $column_name): bool
	{
		if (preg_match('/^[a-z0-9_]+(?:\.[a-z0-9_]+)*$/', $column_name))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
