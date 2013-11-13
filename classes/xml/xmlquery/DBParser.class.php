<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * File containing the DBParser class
 */

/**
 * Escapes query statements: <br />
 *  - column names: member.member_srl =&gt; "member"."member_srl" <br />
 *  - expressions: SUM(member.member_srl) =&gt; SUM("member"."member_srl") <br />
 *
 * @author Corina Udrescu (corina.udrescu@arnia.ro)
 * @package classes\xml\xmlquery
 * @version 0.1
 */
class DBParser
{

	/**
	 * Character for escape target value on the left
	 *
	 * For example, in CUBRID left and right escape
	 * chars are the same, the double quote - "  <br />
	 * But for SQL Server, the escape is made with
	 * [double brackets], so the left and right char differ
	 *
	 *
	 * @var string
	 */
	var $escape_char_left;

	/**
	 * Character for escape target value on the right
	 *
	 * For example, in CUBRID left and right escape
	 * chars are the same, the double quote - "   <br />
	 * But for SQL Server, the escape is made with
	 * [double brackets], so the left and right char differ
	 *
	 * @var string
	 */
	var $escape_char_right;

	/**
	 * Table prefix string
	 *
	 * Default is "xe_"
	 *
	 * @var string
	 */
	var $table_prefix;

	/**
	 * Constructor
	 *
	 * @param string $escape_char_left
	 * @param string $escape_char_right
	 * @param string $table_prefix
	 *
	 * @return void
	 */
	function DBParser($escape_char_left, $escape_char_right = "", $table_prefix = "xe_")
	{
		$this->escape_char_left = $escape_char_left;
		if($escape_char_right !== "")
		{
			$this->escape_char_right = $escape_char_right;
		}
		else
		{
			$this->escape_char_right = $escape_char_left;
		}
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Get escape character
	 *
	 * @param string $leftOrRight left or right
	 * @return string
	 */
	function getEscapeChar($leftOrRight)
	{
		if($leftOrRight === 'left')
		{
			return $this->escape_char_left;
		}
		else
		{
			return $this->escape_char_right;
		}
	}

	/**
	 * Escape the value
	 *
	 * @param mixed $name
	 * @return string
	 */
	function escape($name)
	{
		return $this->escape_char_left . $name . $this->escape_char_right;
	}

	/**
	 * Escape the string value
	 *
	 * @param string $name
	 * @return string
	 */
	function escapeString($name)
	{
		return "'" . $this->escapeStringValue($name) . "'";
	}

	/**
	 * Escape the string value
	 *
	 * @param string $value
	 * @return string
	 */
	function escapeStringValue($value)
	{
		if($value == "*")
		{
			return $value;
		}
		if(is_string($value))
		{
			return $value = str_replace("'", "''", $value);
		}
		return $value;
	}

	/**
	 * Return table full name
	 *
	 * @param string $name table name without table prefix
	 *
	 * @return string table full name with table prefix
	 */
	function parseTableName($name)
	{
		return $this->table_prefix . $name;
	}

	/**
	 * Return column name after escape
	 *
	 * @param string $name column name before escape
	 *
	 * @return string column name after escape
	 */
	function parseColumnName($name)
	{
		return $this->escapeColumn($name);
	}

	/**
	 * Escape column name
	 *
	 * @param string $column_name
	 * @return string column name with db name
	 */
	function escapeColumn($column_name)
	{
		if($this->isUnqualifiedColumnName($column_name))
		{
			return $this->escape($column_name);
		}
		if($this->isQualifiedColumnName($column_name))
		{
			list($table, $column) = explode('.', $column_name);
			// $table can also be an alias, so the prefix should not be added
			return $this->escape($table) . '.' . $this->escape($column);
			//return $this->escape($this->parseTableName($table)).'.'.$this->escape($column);
		}
	}

	/**
	 * Checks to see if a given column name is unqualified
	 *
	 * Ex: "member_srl"           -> unqualified <br />
	 *     "member"."member_srl"  -> qualified
	 *
	 * @param string $column_name
	 * @return bool
	 */
	function isUnqualifiedColumnName($column_name)
	{
		if(strpos($column_name, '.') === FALSE && strpos($column_name, '(') === FALSE)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks to see if a given column name is qualified
	 *
	 * Ex: "member_srl"           -> unqualified <br />
	 *     "member"."member_srl"  -> qualified
	 *
	 * @param string $column_name
	 * @return bool
	 */
	function isQualifiedColumnName($column_name)
	{
		if(strpos($column_name, '.') !== FALSE && strpos($column_name, '(') === FALSE)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Escapes a query expression
	 *
	 * An expression can be: <br />
	 * <ul>
	 *  <li> a column name: "member_srl" or "xe_member"."member_srl"
	 *  <li> an expression:
	 *     <ul>
	 *        <li> LEFT(UPPER("content")) <br />
	 *        <li> readed_count + voted_count <br />
	 *        <li> CAST(regdate as DATE) </li>
	 *     </ul>
	 *  </li>
	 * </ul>
	 *
	 * @param $column_name
	 * @return string
	 */
	function parseExpression($column_name)
	{
		$functions = preg_split('/([\+\-\*\/\ ])/', $column_name, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		foreach($functions as $k => $v)
		{
			$function = &$functions[$k];
			if(strlen($function) == 1)
			{
				continue; // skip delimiters
			}
			$pos = strrpos("(", $function);
			$matches = preg_split('/([a-zA-Z0-9_*]+)/', $function, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$total_brackets = substr_count($function, "(");
			$brackets = 0;
			foreach($matches as $i => $j)
			{
				$match = &$matches[$i];
				if($match == '(')
				{
					$brackets++;
					continue;
				}
				if(strpos($match, ')') !== FALSE)
				{
					continue;
				}
				if(in_array($match, array(',', '.')))
				{
					continue;
				}
				if($brackets == $total_brackets)
				{
					if(!is_numeric($match) && !in_array(strtoupper($match), array('UNSIGNED', 'INTEGER', 'AS')))
					{
						$match = $this->escapeColumnExpression($match);
					}
				}
			}
			$function = implode('', $matches);
		}
		return implode('', $functions);
	}

	/**
	 * Checks if a given argument is an asterisk
	 *
	 * @param string $column_name
	 * @return bool
	 */
	function isStar($column_name)
	{
		if(substr($column_name, -1) == '*')
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks to see if expression is an aggregate star function
	 * like count(*)
	 *
	 * @param string $column_name
	 * @return bool
	 */
	function isStarFunction($column_name)
	{
		if(strpos($column_name, "(*)") !== FALSE)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Return column name after escape
	 * @param string $column_name
	 * @return string
	 */
	function escapeColumnExpression($column_name)
	{
		if($this->isStar($column_name))
		{
			return $column_name;
		}
		if($this->isStarFunction($column_name))
		{
			return $column_name;
		}
		if(stripos($column_name, 'distinct') !== FALSE)
		{
			return $column_name;
		}
		return $this->escapeColumn($column_name);
	}

}
/* End of file DBParser.class.php */
/* Location: ./classes/xml/xmlquery/DBParser.class.php */
