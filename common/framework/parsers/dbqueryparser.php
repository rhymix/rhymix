<?php

namespace Rhymix\Framework\Parsers;

/**
 * DB query parser class for XE compatibility.
 */
class DBQueryParser extends BaseParser
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
		$attribs = self::_getAttributes($xml);
		$query = new DBQuery\Query;
		$query->name = $name ?: null;
		$query->type = strtoupper($attribs['action']) ?: 'SELECT';
		$query->alias = $attribs['alias'] ?? null;
		if ($query->alias && !$query->name)
		{
			$query->name = $query->alias;
		}
		
		// Load attributes that only apply to subqueries in the <conditions> block.
		$query->operation = $attribs['operation'] ?? null;
		$query->column = preg_replace('/[^a-z0-9_\.]/i', '', $attribs['column'] ?? null) ?: null;
		$query->pipe = strtoupper($attribs['pipe'] ?? null) ?: 'AND';
		
		// Load tables.
		foreach ($xml->tables ? $xml->tables->children() : [] as $tag)
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
			}
			
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
		
		// Load columns.
		foreach ($xml->columns ? $xml->columns->children() : [] as $tag)
		{
			if ($tag->getName() === 'query')
			{
				$subquery = self::_parseQuery($tag, trim($tag['id']));
				$query->columns[] = $subquery;
			}
			elseif ($query->type === 'SELECT')
			{
				$column = new DBQuery\ColumnRead;
				$column->name = trim($tag['name']);
				$column->alias = trim($tag['alias']) ?: null;
				if ($column->name === '*' || preg_match('/\.\*$/', $column->name))
				{
					$column->is_wildcard = true;
				}
				if (!DBQuery\Query::isValidColumnName($column->name))
				{
					$column->is_expression = true;
				}
				$query->columns[] = $column;
			}
			else
			{
				$attribs = self::_getAttributes($tag);
				$column = new DBQuery\ColumnWrite;
				$column->name = $attribs['name'];
				$column->operation = ($attribs['operation'] ?? null) ?: 'equal';
				$column->var = $attribs['var'] ?? null;
				$column->default = $attribs['default'] ?? null;
				$column->not_null = ($attribs['notnull'] ?? false) ? true : false;
				$column->filter = $attribs['filter'] ?? null;
				$column->minlength = intval($attribs['minlength'] ?? 0, 10);
				$column->maxlength = intval($attribs['maxlength'] ?? 0, 10);
				$query->columns[] = $column;
			}
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
			foreach ($xml->navigation->index ?: [] as $tag)
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
					$query->navigation->{$key} = new DBQuery\VariableBase;
					$query->navigation->{$key}->var = trim($tag['var']) ?: null;
					$query->navigation->{$key}->default = trim($tag['default']) ?: null;
				}
			}
		}
		
		// If a SELECT query has no columns, use * by default.
		if ($query->type === 'SELECT' && !count($query->columns))
		{
			$column = new DBQuery\ColumnRead;
			$column->name = '*';
			$column->is_wildcard = true;
			$column->is_expression = true;
			$query->columns[] = $column;
		}
		
		// Check the SELECT DISTINCT flag.
		if ($xml->columns && $select_distinct = trim($xml->columns['distinct']))
		{
			if ($select_distinct === 'distinct' || toBool($select_distinct))
			{
				$query->select_distinct = true;
			}
		}
		
		// Check the ON DUPLICATE KEY UPDATE (upsert) flag.
		if ($query->type === 'INSERT' && $update_duplicate = self::_getAttributes($xml)['updateduplicate'] ?? false)
		{
			if (toBool($update_duplicate))
			{
				$query->update_duplicate = true;
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
			$attribs = self::_getAttributes($tag);
			$name = $tag->getName();
			if ($name === 'condition')
			{
				$cond = new DBQuery\Condition;
				$cond->operation = $attribs['operation'];
				$cond->column = $attribs['column'];
				if (isset($attribs['var']) && !isset($attribs['default']) && preg_match('/^\w+\.\w+$/', $attribs['var']))
				{
					$cond->default = $attribs['var'];
				}
				else
				{
					$cond->var = $attribs['var'] ?? null;
					$cond->default = $attribs['default'] ?? null;
				}
				$cond->not_null = ($attribs['notnull'] ?? false) ? true : false;
				$cond->filter = $attribs['filter'] ?? null;
				$cond->minlength = intval($attribs['minlength'] ?? 0, 10);
				$cond->maxlength = intval($attribs['maxlength'] ?? 0, 10);
				$cond->pipe = strtoupper($attribs['pipe'] ?? null) ?: 'AND';
				$result[] = $cond;
			}
			elseif ($name === 'group')
			{
				$group = new DBQuery\ConditionGroup;
				$group->conditions = self::_parseConditions($tag);
				$group->pipe = strtoupper($attribs['pipe'] ?? null) ?: 'AND';
				$result[] = $group;
			}
			elseif ($name === 'query')
			{
				$subquery = self::_parseQuery($tag);
				$result[] = $subquery;
			}
		}
		
		return $result;
	}
}
