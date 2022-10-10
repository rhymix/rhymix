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
		$query->type = strtoupper($attribs['action'] ?? '') ?: 'SELECT';
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
				$table->alias = trim($tag['alias']) ?: null;
				$table->ifvar = trim($tag['if']) ?: null;
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
			$query->tables[$table->alias ?: $table->name] = $table;
		}
		
		// Load index hints.
		foreach ($xml->index_hint ?: [] as $index_hint_group)
		{
			$index_hint_target_db = strtolower(trim($index_hint_group['for']));
			if ($index_hint_target_db !== '' && $index_hint_target_db !== 'all')
			{
				$index_hint_target_db = explode(',', $index_hint_target_db);
				$index_hint_target_db = array_combine($index_hint_target_db, array_fill(0, count($index_hint_target_db), true));
			}
			else
			{
				$index_hint_target_db = [];
			}
			
			foreach ($index_hint_group->children() ?: [] as $tag)
			{
				$index_hint = new DBQuery\IndexHint;
				$index_hint->target_db = $index_hint_target_db;
				$index_hint->hint_type = strtoupper(trim($tag['type'])) ?: 'USE';
				$index_hint->index_name = trim($tag['name']) ?: '';
				$index_hint->table_name = trim($tag['table']) ?: '';
				$index_hint->ifvar = trim($tag['if']) ?: null;
				if (isset($tag['var']) && trim($tag['var']))
				{
					$index_hint->var = trim($tag['var']);
				}
				if (isset($tag['default']) && trim($tag['default']))
				{
					$index_hint->index_name = trim($tag['default']);
				}
				if ($index_hint->index_name || $index_hint->var)
				{
					$query->index_hints[] = $index_hint;
				}
			}
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
				$column->ifvar = trim($tag['if']) ?: null;
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
				$column->ifvar = $attribs['if'] ?? null;
				$column->default = $attribs['default'] ?? null;
				$column->not_null = ($attribs['notnull'] ?? false) ? true : false;
				$column->filter = $attribs['filter'] ?? null;
				$column->minlength = (int)($attribs['minlength'] ?? 0);
				$column->maxlength = (int)($attribs['maxlength'] ?? 0);
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
			$query->groupby->ifvar = trim($xml->groups['if']) ?: null;
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
				$cond->ifvar = $attribs['if'] ?? null;
				$cond->not_null = ($attribs['notnull'] ?? false) ? true : false;
				$cond->filter = $attribs['filter'] ?? null;
				$cond->minlength = (int)($attribs['minlength'] ?? 0);
				$cond->maxlength = (int)($attribs['maxlength'] ?? 0);
				$cond->pipe = strtoupper($attribs['pipe'] ?? null) ?: 'AND';
				$result[] = $cond;
			}
			elseif ($name === 'group')
			{
				$group = new DBQuery\ConditionGroup;
				$group->conditions = self::_parseConditions($tag);
				$group->pipe = strtoupper($attribs['pipe'] ?? null) ?: 'AND';
				$group->ifvar = $attribs['if'] ?? null;
				$result[] = $group;
			}
			elseif ($name === 'query')
			{
				$subquery = self::_parseQuery($tag);
				$subquery->ifvar = $attribs['if'] ?? null;
				$result[] = $subquery;
			}
		}
		
		return $result;
	}
}
