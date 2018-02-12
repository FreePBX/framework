<?php
/*
BMore - FreePBX module html view and db support from schema
Author: Scott Griepentrog
License: GPL v3

Usage:
	* extend this class in module class definition
	* in constructor, define schema
	* schema includes:
		- tablename
			- table (sql)
			- field definitions
				- field name
					- sqltype
					- header
					- help
				...
			- views
				- view name
					- view type
						- view defintion
							- additional fields, params etc
				...
		...
	* basic form generation handled with no additional html views or functions
	* custom view generators can be defined
	* methods can be defined to replace sql backend for custom forms
*/

namespace FreePBX\Builtin;

// context provides needed parameters for accurate view generation (see getContext)
class BMoreContext {
	public $table_name;
	public $view_name;
	public $table;		// copy of table schema
	public $view;		// copy of view definition (merged/inherited)
	public $table_fields;	// list of fields in table
	public $is_modal;	// flag to enable modal controls
}

// main BMore class
class BMore extends \FreePBX_Helpers {
	// constants
	const DISPLAY_ARG = 'display';
	const MODULE_ARG = 'module';
	const TABLE_ARG = '_t';
	const VIEW_ARG = '_v';
	const ACTION_ARG = '_a';
	const POSTVIEW_ARG = '_p';

	// context stack allows recursion detection to avoid crash
	public $context_stack = array();

	// preset defaults
	public function __construct()
	{
	}

	// UTILITY FUNCTIONS

	// generate unique id
	public function getId($prefix)
	{
		global $bmore_unique_id_count;
		return $prefix.++$bmore_unique_id_count;
	}

	// generate smartly formatted html tags with params and optional content
	public function tag($tag, $params=array(), $content = false)
	{
		if (!is_array($params)) {
			if ($params) {
				$params = array('class' => $params);
			} else {
				$params = array();
			}
		}
		if (array_key_exists('class', $params) && is_array($params['class'])) {
			$params['class'] = implode(' ', $params['class']);
		}


		$html = '<' . $tag;

		$just_tag = strstr($tag, ' ', true);
		if ($just_tag) {
			$tag = $just_tag;
		}

		foreach ($params as $name => $value) {
			if ($value === false) {
				continue;
			}
			$html .= ' ' . $name;
			if ($value === true) {
				continue;
			}
			$html .= '="' . $value . '"';
		}
		// TODO: fix this to insert close for tags supporting it if no content
		$html .= '>';

		if ($content !== false) {
			// don't waste 2 lines for every <li>
			// don't add unintended whitespace around <a> - tends to become visible
			if (strlen($content) > 40 && $tag != 'li' && $tag != 'a') {
				$content = str_replace("\n", "\n  ", "\n" . $content) . "\n";
			}
			$html .= $content;
			$html .= '</' . $tag .'>';
		}
		return $html;
	}

	// make [icon]'s come alive
	public function iconize($label)
	{
		return preg_replace_callback('|\[[^]]*\]|',
			function ($icon) {
				return '<i class="fa fa-' . substr($icon[0], 1, -1) . '"></i>';
			},
			$label);
	}

	// set the Table and View names and pointers
	private function setTableView(&$table_name, &$view_name, &$table, &$view)
	{
		if (!$table_name) {
			// if no name provided, default to first table in schema
			$table_name = reset(array_keys($this->schema));
		}

		if (!array_key_exists($table_name, $this->schema)) {
			throw new \Exception('Invalid table: '.$table_name);
		}
		$table = $this->schema[$table_name];

		if (empty($table['views'])) {
			throw new \Exception('Schema for table '.$table_name.' has no views');
		}

		if (!$view_name) {
			// if no view name provided, use the first view defined in table
			$view_name = reset(array_keys($table['views']));
		}

		if (!array_key_exists($view_name, $table['views'])) {
			throw new \Exception('Invalid view: '.$view_name);
		}
		$view = $table['views'][$view_name];
	}

	// DATABASE HANDLING

	// map sql schema type to PDO param type
	public function getParamType($field)
	{
		if (empty($field['sqltype'])) {
			throw new \Exception("Unable to get sqltype from field: ".print_r($field, true));
		}
		if (strtoupper(substr($field['sqltype'], 0, 7)) == 'VARCHAR') {
			return \PDO::PARAM_STR;
		}
		if (strtoupper(substr($field['sqltype'], 0, 3)) == 'INT') {
			return \PDO::PARAM_INT;
		}
		if (strtoupper(substr($field['sqltype'], 0, 7)) == 'TINYINT') {
			return \PDO::PARAM_INT;
		}
		throw new \Exception('Unknown parameter type from sql type: '.$field['sqltype']);
	}

	// reduce record to just fields for table (used for multi-db api transactions)
	public function filterFields($context, $record) {
		return array_intersect_key($record, $context->table_fields);
	}

	// reduce record to any field that is not a control field
	public function filterNotControlFields($record) {
		$control_fields = array(
			self::DISPLAY_ARG,
			self::MODULE_ARG,
			self::TABLE_ARG,
			self::VIEW_ARG,
			self::ACTION_ARG,
			self::POSTVIEW_ARG,
			'submit',
		);
		$not_control_fields = array_filter(array_keys($_REQUEST),
			function($field) use ($control_fields) {
				return !in_array($field, $control_fields);
			}
		);
		return array_intersect_key($record, array_flip($not_control_fields));
	}

	// get a single record
	public function getRecord($context, $record){
		$func = 'getRecord_'.$context->table_name;
		if (method_exists($this, $func)) {
			return call_user_func(array($this, $func), $context, $record);
		}
		$table = $context->table;
		if (empty($table['table'])) throw new \Exception("Table '{$context->table_name}' or $func() does not exist");
		$key_fields = array_filter($context->table_fields, function($field) { return !empty($field['key']); });
		$wheres = implode(', ', array_map(function($value) { return "$value = :$value"; }, array_keys($key_fields)));
		$sql = "SELECT * FROM {$table['table']} WHERE $wheres";
		$stmt = $this->db->prepare($sql);
		foreach ($key_fields as $name => $field) {
			if (!array_key_exists($name, $record)) {
				throw new \Exception('getRecord '.$table['table'].' requires key '.$name);
			}
			$stmt->bindParam(':'.$name, $record[$name], $this->getParamType($field));
		}
		$stmt->execute();
		$row =$stmt->fetchObject();
		return (array)$row;
	}

	// get ALL! the records
	public function getAllRecords($context) {
		$func = 'getAllRecords_'.$context->table_name;
		if (method_exists($this, $func)) {
			return call_user_func(array($this, $func), $context);
		}
		if (empty($context->table['table'])) throw new \Exception("Table '{$context->table_name}' or $func() does not exist");
		$ret = array();
		$sql = "SELECT * from {$context->table['table']}";
		foreach ($this->db->query($sql, DB_FETCHMODE_ASSOC) as $row) {
			$ret[] = (array)$row;
		}
		return $ret;
	}

	// add a new record
	public function addRecord($context, $record){
		$func = 'addRecord_'.$context->table_name;
		if (method_exists($this, $func)) {
			return call_user_func(array($this, $func), $context, $record);
		}
		$table = $context->table;
		if (empty($table['table'])) throw new \Exception("Table '{$context->table_name}' or $func() does not exist");
		if (empty($context->table_fields)) throw new \Exception('Fields not defined');
		$field_names = implode(', ', array_keys($context->table_fields));
		$colon_names = implode(', ', array_map(function($value) { return ':'.$value; }, array_keys($context->table_fields)));

		$sql = "INSERT INTO {$table['table']} ($field_names) VALUES ($colon_names)";
		$stmt = $this->db->prepare($sql);
		foreach ($context->table_fields as $name => $field) {
			if (!array_key_exists($name, $record)) {
				$record[$name] = '';
			}
			$stmt->bindParam(':'.$name, $record[$name], $this->getParamType($field));
		}
		$stmt->execute();
		return $this->db->lastInsertId();
	}

	// update record (key must exist)
	public function updateRecord($context, $record){
		$func = 'updateRecord_'.$context->table_name;
		if (method_exists($this, $func)) {
			return call_user_func(array($this, $func), $context, $record);
		}
		$table = $context->table;
		if (empty($table['table'])) throw new \Exception("Table '{$context->table_name}' or $func() does not exist");
		if (empty($context->table_fields)) throw new \Exception('Fields not defined');
		$non_key_fields = array_filter($context->table_fields, function($field) { return empty($field['key']); });
		$key_fields = array_filter($context->table_fields, function($field) { return !empty($field['key']); });
		$assignments = implode(', ', array_map(function($value) { return "$value = :$value"; }, array_keys($non_key_fields)));
		$wheres = implode(', ', array_map(function($value) { return "$value = :$value"; }, array_keys($key_fields)));

		$sql = "UPDATE {$table['table']} SET $assignments WHERE $wheres";
		$stmt = $this->db->prepare($sql);
		foreach ($context->table_fields as $name => $field) {
			if (!array_key_exists($name, $record)) {
				$record[$name] = '';
			}
			$stmt->bindParam(':' . $name, $record[$name], $this->getParamType($field));
		}
		return $stmt->execute();
	}

	// delete record
	public function deleteRecord($context, $record){
		$func = 'deleteRecord_'.$context->table_name;
		if (method_exists($this, $func)) {
			return call_user_func(array($this, $func), $context, $record);
		}
		$table = $context->table;
		if (empty($table['table'])) throw new \Exception("Table '{$context->table_name}' or $func() does not exist");
		if (empty($context->table_fields)) throw new \Exception('Fields not defined');
		$non_key_fields = array_filter($context->table_fields, function($field) { return empty($field['key']); });
		$key_fields = array_filter($context->table_fields, function($field) { return !empty($field['key']); });
		$wheres = implode(', ', array_map(function($value) { return "$value = :$value"; }, array_keys($key_fields)));

		$sql = "DELETE FROM {$table['table']} WHERE $wheres";
		$stmt = $this->db->prepare($sql);
		foreach ($key_fields as $name => $field) {
			if (!array_key_exists($name, $record)) {
				throw new \Exception('delete does not specify field ' . $name);
			}
			$stmt->bindParam(':' . $name, $record[$name], $this->getParamType($field));
		}
		return $stmt->execute();
	}

	// DATABASE UTILITY

	// returns included schema from table
	public function getIncludedFields($table_fields)
	{
		// build up field array so that included portions are in expected order
		$fields = array();
		if (empty($table_fields)) {
			$table_fields = array();
		}
		foreach ($table_fields as $name => $field) {
			if (substr($name, 0, 7) == 'include') {
				if (!is_array($field)) {
					$includes = array($field);
				} else {
					$includes = $field;
				}
				$proposed = array();
				foreach ($includes as $include) {

					if (!empty($this->schema[$include]['fields'])) {
						// include all fields from table
						foreach ($this->schema[$include]['fields'] as $inc_name => $inc_field) {
							$proposed[$inc_name] = $inc_field;
						}
						continue;
					}
					$split = explode('.', $include);
					if (count($split) == 2 && !empty($this->schema[$split[0]]['fields'][$split[1]])) {
						// include table.field
						$fields[$split[1]] = $this->schema[$split[0]]['fields'][$split[1]];
						continue;
					}
					// bail gracefully
					$fields[$name] = $field;
					continue 2;
				}
				$fields = array_merge($fields, $proposed);
				continue;
			}
			$fields[$name] = $field;
		}
		return $fields;
	}

	// returns merged set of view and schema fields to populate view
	public function getMergedFields($view_fields, $context = Null)
	{
		if (empty($context)) {
			throw new \Exception('Invalid use case - no context');
		}
		$table = $context->table;
		$table_fields = $this->getIncludedFields($table['fields']);

		if (empty($view_fields)) {
			//return $table['fields'];
			$view_fields = array('*');
		}

		$fields = array();
		foreach ($view_fields as $name => $field)
		{
			if (!is_array($field)) {
				$name = $field;
				if ($name == '*') {
					$fields = array_merge($fields, $table_fields);
					continue;
				}
				if (!array_key_exists($name, $table_fields)) {
					throw new \Exception("Field name $name does not exist in table: ".print_r($table));
				}
				$fields[$name] = $table_fields[$name];
			} else {
				if (array_key_exists($name, $table_fields)) {
					$fields[$name] = array_merge($table_fields[$name], $field);
				} else {
					$fields[$name] = $field;
				}
			}
		}
		return $fields;
	}

	// VIEW GENERATION

	// returns html 'get' url
	public function getDisplayUrl($params=array(), $context)
	{
		$default=array(
			self::DISPLAY_ARG => $this->module_name,
			self::TABLE_ARG => $context->table_name,
			self::VIEW_ARG => $context->view_name,
		);
		return 'config.php?' . http_build_query(array_merge($default, $params));
	}

	// returns ajax url for table getter
	public function getAjaxUrl($params=array(), $context)
	{
		$default = array(
			self::MODULE_ARG => $this->module_name,
			self::TABLE_ARG => $context->table_name,
			self::VIEW_ARG => $context->view_name,
		);
		return 'ajax.php?' . http_build_query(array_merge($default, $params));
	}

	// convert merged view schema to html using 'viewHandler' functions
	public function getViewAsHtml($context=Null, $contents=array())
	{
		$html = array();
		$default = array();

		if (!$context) {
			throw new \Exception('Invalid use case without context');
		}

		if (empty($context->view)) {
			// return '<div class="alert alert-warning">'."Definition not found for view '{$context->view_name}' in table '{$context->table_name}'.".'</div>';
			throw new \Exception("Definition not found for view '{$context->view_name}' in table '{$context->table_name}'");
		}

		// prevent recursion
		$context_name = "{$context->table_name}/{$context->view_name}";
		if (in_array($context_name, $this->context_stack)) {
			throw new \Exception("Recursion detected on $context_name while generating ".implode(",", $this->context_stack));
		}
		array_push($this->context_stack, $context_name);

		foreach ($context->view as $handler => $data) {
			if ($handler == 'rnav' || $handler == 'default') {
				continue;
			}
			// ignore portion past -
			$func = 'view'.ucwords($handler);
			if (method_exists($this, $func)) {
				$html[] = $this->$func($data, $context, $contents);
			} else if (function_exists($func)) {
				$html[] = $func(array_merge_recursive($default, $data), $context, $contents);
			} else {
				throw new \Exception('Unable to locate view handler for '.$handler);
			}
		}

		array_pop($this->context_stack);
		return implode("\n", $html);
	}

	// merge view schema in priority order and return in a context class
	public function getContext($view_name=Null, $table_name = Null)
	{
		// check view for table/view format
		if (!empty($_REQUEST[self::VIEW_ARG])) {
			$split = explode('/', $_REQUEST[self::VIEW_ARG]);
			if (count($split) == 2) {
				// force the split back to _REQUEST so it doesn't accidentally get interpreted again
				list($_REQUEST[self::TABLE_ARG], $_REQUEST[self::VIEW_ARG]) = $split;
			}
		}
		// also check supplied view for table/view format
		if ($view_name) {
			$split = explode('/', $view_name);
			if (count($split) == 2) {
				list($table_name, $view_name) = $split;
			}
		}
		// if view/table haven't been specified, get them from the request
		if (!$view_name && !empty($_REQUEST[self::VIEW_ARG])) {
			$view_name = $_REQUEST[self::VIEW_ARG];
		}
		if (!$table_name && !empty($_REQUEST[self::TABLE_ARG])) {
			$table_name = $_REQUEST[self::TABLE_ARG];
		}

		// presume to use the first table if not specified
		if (empty($table_name)) {
			$table_name = reset(array_keys($this->schema));
		}
		if (!array_key_exists($table_name, $this->schema)) {
			throw new \Exception("Table '{$table_name}' does not exist");
		}
		if (empty($view_name)) {
			if (empty($this->schema[$table_name]['views'])) {
				// throw new \Exception('There are no views defined for table '.$table_name);
				// be helpful and provide a basic list table
				$this->schema[$table_name]['views']['list'] = array(
					'table' => array()
				);
			}
			$views = array_keys($this->schema[$table_name]['views']);
			$view_name = reset($views);
		}
		$context = new BMoreContext();

		$context->is_modal = False;
		$context->table_name = $table_name;
		$context->table = $this->schema[$table_name];
		$context->table_fields = $this->getIncludedFields($context->table['fields']);

		$context->view_name = $view_name;

		$view = array();
		if (!empty($context->table['views'][$view_name])) {
			$view = $context->table['views'][$view_name];
		}

		$table_default = array();
		if (!empty($context->table['views']['default'])) {
			$table_default = $context->table['views']['default'];
		}

		$default_view = array();
		if (!empty($this->schema['default']['views'][$view_name])) {
			$default_view = $this->schema['default']['views'][$view_name];
		}

		$default_default = array();
		if (!empty($this->schema['default']['views']['default'])) {
			$default_default = $this->schema['default']['views']['default'];
		}
		$context->view = array_merge_recursive($default_default, $default_view, $table_default, $view);

		return $context;
	}

	// SCHEMA-DRIVEN VIEW HANDLERS

	// generate html from multiple views concatenated in a specific order
	public function viewGroup($data, $context, $contents)
	{
		$html = array();
		foreach ($data as $view_name) {
			$html[] = $this->getViewAsHtml($this->getContext($view_name), $contents);
		}
		return implode("\n", $html);
	}

	// generate html using panel headers around each subview
	public function viewPanel($data, $context, $contents)
	{
		$h = array();
		foreach ($data as $panel_header => $view_name) {
			$h[] = $this->tag('div', array('class' => 'panel panel-default'),
				$this->tag('div', array('class' => 'panel-heading'), $panel_header).
				$this->tag('div', array('class' => 'panel-body'),
					$this->getViewAsHtml($this->getContext($view_name), $contents)
				)
			);
		}
		return implode("\n", $h);
	}

	// generate html for info panel
	public function viewInfo($data, $context, $contents) {
		$h = array();
		foreach ($data as $panel_header => $html) {
			$target = $this->getId('panel');
			$h[] = $this->tag('div', array('class' => 'panel panel-info'),
				$this->tag('div', array('class' => 'panel-heading'),
					$this->tag('div', array('class' => 'panel-title'),
						$this->tag('a', array('href' => '#', 'data-toggle' => 'collapse', 'data-target' => "#$target"),
							$this->tag('i', array('class' => 'glyphicon glyphicon-info-sign'), '')
						)."&nbsp;&nbsp;&nbsp;".$panel_header
					)
				)."\n".
				$this->tag('div', array('class' => 'panel-body collapse', 'id' => $target, 'aria-expanded' => 'true'),
					$this->viewHtml($html, $context, array())
				)
			);
		}
		return implode("\n", $h);
	}

	// generate html from view data structured as nested html tags with optional contents
	public function viewHtml($data, $context, $contents)
	{
		if (!is_array($data)) {
			if ($data[0] == '$') {
				$name = substr($data, 1);
				if (array_key_exists($name, $contents)) {
					return $contents[$name];
				}
//else return print_r(array($data, $name, $contents), true);
			}
			return $data;
		}
		$html = array();
		foreach ($data as $tag => $contains)
		{
			if (is_numeric($tag)) {
				// array is indexed, ignore tag name
				$html[] = $this->viewHtml($contains, $context, $contents);
			} else {
				$html[] = $this->tag($tag, array(), $this->viewHtml($contains, $context, $contents));
			}
		}
		return implode("\n", $html);
	}

	// generate html for menu bar
	public function viewMenu($data, $context, $contents)
	{
		return $this->tag('div', array('id' => $id), $this->htmlLinks($data, $context));
	}

	// generate html for data table in view (grid)
	public function viewTable($data, $context, $contents)
	{
		$scripts = array();
		if (!array_key_exists('params', $data)) {
			$params = array(
				'class' => array('table', 'table-striped'),
				'data-cache' => 'false',
				'data-maintain-selected' => 'true',
				'data-show-refresh' => 'true',
				'data-show-columns' => 'true',
				'data-show-toggle' => 'true',
				'data-toggle' => 'table',
				'data-pagination' => 'true',
				'data-search' => 'true',
			);
		} else {
			$minimal = array(
				'class' => array('table', 'table-striped'),
				'data-toggle' => 'table',
			);
			$params = array_merge($minimal, $data['params']);
		}
		$params['id'] = $this->getId('table');
		$params['data-url'] = $this->getAjaxUrl(array('command' => 'getJSON'), $context);

		$html = array();
		if (!empty($data['toolbar'])) {
			$id = $this->getId('toolbar');
			$html[] = $this->tag('div', array('id' => $id), $this->htmlLinks($data['toolbar'], $context));
			$params['data-toolbar'] = '#' . $id;
		}

		$tr = array();
		foreach ($this->getMergedFields($data['fields'], $context) as $name => $field) {
			if (empty($field['header'])) continue;
			$th_params = array(
				'class' => 'col-md-1',
				'data-field' => $name,
			);
			if (!empty($field['data-formatter'])) {
				$script_name = $context->table_name.'_'.$name.'_formatter';
				$scripts[$script_name] = str_replace('$module_name', $this->module_name, $field['data-formatter']);
				$th_params['data-formatter'] = $script_name;
			}
			$tr[] = $this->tag('th', $th_params, $field['header']);
		}
		$html[] = $this->tag('table', $params,
				$this->tag('thead', array(),
					$this->tag('tr', array(), implode("\n", $tr))
				)
			);

		if (!empty($scripts)) {
			$js = '';
			foreach ($scripts as $name => $script) {
				if ($js) {
					$js .= "\n";
				}
				$js .= 'function '.$name.'(value, row, index){'.str_replace("\n", "\n  ", "\n".$script)."\n}";
			}
			$html[] = $this->tag('script', array('type' => 'text/javascript'), $js);
		}
		if (!empty($data['auto-refresh'])) {
			$id = $params['id'];
			$name = 'auto_refresh_'.$id;
			$js = 'function '.$name.'(){' . "\n\t";
			$js .= '$(\'#' . $id . '\').bootstrapTable(\'refresh\', {silent: true});'."\n}\n";
			$js .= 'setInterval(' . $name . ', ' . 1000 * $data['auto-refresh'] . ');';
			$html[] = $this->tag('script', array('type' => 'text/javascript'), $js);
		}

		if (!empty($data['script'])) {
			$html[] = $this->tag('script', array('type' => 'text/javascript'), $data['script']);
		}
		return implode("\n", $html);
	}

	// generate html for form in view
	public function viewForm($data, $context, $contents)
	{
		$url_options = array();
		if (!empty($data['postview'])) {
			$url_options[self::POSTVIEW_ARG] = $data['postview'];
		}

		$form_contents = array();
		$form_params = array(
			'class' => 'fpbx-submit',
			'action' => $this->getDisplayUrl($url_options, $context),
			'method' => 'post',
			'id' => $this->getId('form'),
			// needs a name ?
		);
		if (!empty($data['params'])) {
			// allow override of params
			$form_params = array_merge($form_params, $data['params']);
		}

		if (!empty($data['action'])) {
			$action_value = $data['action'];
		} else {
			$action_value = 'add';
		}
		$key_fields = array_filter($context->table_fields, function($field) { return !empty($field['key']); });

		$key_values = array();
		foreach ($key_fields as $name => $field) {
			$value = '';
			if (array_key_exists($name, $_REQUEST)) {
				$value = $_REQUEST[$name];
			} else {
				if (!empty($field['default'])) {
					$value = $field['default'];
				}
			}
			$key_values[$name] = $value;
		}
		$values = array();
		if (!empty($key_values)) {
			$form_params['data-fpbx-delete'] = $this->getDisplayUrl(
				array_merge($url_options, array(self::ACTION_ARG => 'delete'), $key_values),
				$context);

			$values = $this->getRecord($context, $key_values);
			if ($values) {
				if (empty($data['action'])) {
					$action_value = 'edit';
				}
			} else {
				$values = $key_values;
			}
		}

		$form_contents[] = $this->tag('input', array(
			'type' => 'hidden',
			'name' => self::ACTION_ARG,
			'value' => $action_value
		));

		$label_width = 3;
		if (!empty($data['label_width'])) {
			$label_width = $data['label_width'];
		}
		$field_width = 12 - $label_width;

		foreach ($this->getMergedFields($data['fields'], $context) as $name => $field) {
			if (is_numeric($name) && is_array($field)) {
				// non-field output
				$form_contents[] = $this->viewHtml($field, $context, array());
				continue;
			}
			if (empty($field['header'])) {
				$field['header'] = ucfirst($name);
			}
			if (array_key_exists($name, $key_fields)) {
				unset($key_fields[$name]);
			}
			if (empty($field['type'])) {
				$field['type'] = 'text';
			}
			$value = '';
			$readonly = False;
			if (!empty($field['readonly'])) {
				$readonly = $field['readonly'];
			}
			if (array_key_exists($name, $values)) {
				$value = $values[$name];
			} else {
				if (!empty($field['default'])) {
					$value = $field['default'];
				}
			}
			$outer_div_class='';
			$input_params = array(
				'type' => $field['type'],
				'class' => 'form-control',
				'id' => $name,
				'name' => $name,
				'readonly' => $readonly,
				'value' => $value,
			);
			if (!empty($field['params'])) {
				$input_params = array_merge($input_params, $field['params']);
			}
			if (!empty($field['select'])) {
				$options = array();
				foreach ($field['select'] as $value => $text) {
					$options[] = $this->tag('option', array('value' => $value), $text);
				}
				unset($input_params['type']);
				$form_field = $this->tag('select', $input_params, implode("\n", $options));
			} else if (strtoupper($field['sqltype']) == 'TINYINT(1)') {
				$outer_div_class=' radioset';
				$form_field =
					$this->tag('input', array_merge($input_params, array(
						'type' => 'radio',
						'id' => $name.'-yes',
						'value' => '1',
						'checked' => ($value != 0),
						'disabled' => ($readonly && ($value == 0)),
						'readonly' => false,
					)))."\n".
					$this->tag('label', array('for' => $name.'-yes'), 'Yes')."\n".
					$this->tag('input', array_merge($input_params, array(
						'type' => 'radio',
						'id' => $name.'-no',
						'value' => '0',
						'checked' => ($value == 0),
						'disabled' => ($readonly && ($value != 0)),
						'readonly' => false,
					)))."\n".
					$this->tag('label', array('for' => $name.'-no'), 'No');
			} else {
				$form_field = $this->tag('input', $input_params);
			}

			$form_contents[] = $this->tag('div', 'element-container',
				$this->tag('div', 'row',
					$this->tag('div', 'col-md-12',
						$this->tag('div', 'row',
							$this->tag('div', 'form-group',
								$this->tag('div', 'col-md-'.$label_width,
									$this->tag('label', array(
										'class' => 'control-label',
										'for' => $name
									), $field['header'])."\n".
									$this->tag('i', array(
										'class' => 'fa fa-question-circle fpbx-help-icon',
										'data-for' => $name
									), "")
								)."\n".
								$this->tag('div', 'col-md-'.$field_width.$outer_div_class,
									$form_field
								)
							)
						)
					)
				)."\n".
				$this->tag('div', 'row',
					$this->tag('div', 'col-md-12',
						$this->tag('span', array(
							'id' => $name.'-help',
							'class' => 'help-block fpbx-help-block'
						), $field['help'])
					)
				)
			);
		}
		// add hidden fields for any key values that were not specified in view
		foreach ($key_fields as $name => $field) {
			$value = '';
			if (array_key_exists($name, $values)) {
				$value = $values[$name];
			} else {
				if (!empty($field['default'])) {
					$value = $field['default'];
				}
			}
			$form_contents[] = $this->tag('input', array(
				'type' => 'hidden',
				'id' => $name,
				'name' => $name,
				'value' => $value,
			));
		}
		// allow (for modal) a submit button (this should be improved in some way)
		//if (!empty($data['submit'])) {
		if ($context->is_modal) {
			$form_field = $this->tag('input', array(
				'id' => 'submit',
				'type' => 'submit',
				'name' => 'submit',
				'value' => 'Submit',
			));
			$form_contents[] = $this->tag('div', 'element-container',
				$this->tag('div', 'row',
					$this->tag('div', 'col-md-12',
						$this->tag('div', 'row',
							$this->tag('div', 'form-group',
								$this->tag('div', 'col-md-3', "").
								$this->tag('div', 'col-md-9', $form_field)
							)
						)
					)
				)
			);
		}

		$js = '';
		if (!empty($data['script'])) {
			$js = "\n".$this->tag('script', array('type' => 'text/javascript'), $data['script'])."\n";
		}

		return $this->tag('form', $form_params, implode("\n", $form_contents)).$js;
	}

	// OTHER NOT SCHEMA-DRIVEN VIEW GENERATORS

	// wrap view in a modal dialog
	private function getModalDialogView($header, $id, $view_name)
	{
		$modal_params = array(
			'class' => 'modal fade',
			'id' => $id,
			'tabindex' => '-1',
			'role' => 'dialog',
			'aria-hidden' => 'true'
		);
		$close_params = array(
			'type' => 'button',
			'class' => 'close',
			'data-dismiss' => 'modal',
			'aria-label' => 'Close'
		);
		$body_context = $this->getContext($view_name);
		$body_context->is_modal = True;

		return $this->tag('div', $modal_params,
			$this->tag('div', 'modal-dialog',
				$this->tag('div', 'modal-content',
					$this->tag('div', 'modal-header',
						$this->tag('button', $close_params,
							$this->tag('span', array('aria-hidden' => 'true'),
								'&times;')
						)."\n".
						$this->tag('h4', array('class' => 'modal-title', 'id' => $id.'Label'),
							$this->iconize($header)
						)
					)."\n".
					$this->tag('div', 'modal-body',
						$this->getViewAsHtml($body_context)
					)
				)
			)
		);
	}

	// recursively follow array tree building html output
	private function htmlLinksRecursor($links, $subitem, &$tail, $context)
	{
		$sword = array();
		foreach ($links as $text => $link) {
			$class = '';
			if (strstr($text, '|')) {
				list($class, $text) = explode('|', $text, 2);
			}
			if ($link[0] == '#') {
				// convenience link to another view
				$other_view = substr($link, 1);
				$link = $this->getDisplayUrl(array(self::VIEW_ARG=>$other_view), $context);
			}
			if ($link[0] == '@') {
				// modal link to another view
				$modal_view = substr($link, 1);
				$modal_id = $this->getId('modal');
				$sword[] = $this->tag('button', array('class' => $class, 'data-toggle' => 'modal',
					'data-target' => '#'.$modal_id),
					$this->iconize($text));
				$tail[] = $this->getModalDialogView($text, $modal_id, $modal_view);
			} else if (is_array($link)) {
				$sword[] = $this->tag('div', 'btn-group',
					$this->tag('button',
						array('class' => "$class dropdown-toggle", 'type' => 'button',
							'data-toggle' => 'dropdown', 'aria-expanded' => 'false'),
						$this->iconize($text) . ' ' . $this->tag('span class="caret"')
					)."\n".
					$this->tag('ul', array('class' => 'dropdown-menu', 'role' => 'menu'),
						$this->htmlLinksRecursor($link, true, $tail, $context))
				);
			} else if ($subitem) {
				$sword[] = $this->tag('li', array(),
					$this->tag('a', array('class' => $class, 'href' => $link), $this->iconize($text))
				);
			} else {
				$sword[] = $this->tag('a', array('class' => $class, 'href' => $link), $this->iconize($text)) ;
			}
		}
		return implode("\n", $sword);
	}

	// generate html for set of links
	public function htmlLinks($links, $context)
	{
		$tail = array();
		$html = $this->htmlLinksRecursor($links, false, $tail, $context);
		return $html . implode("\n", $tail);
	}

	// View called by page.{$module_name}.php
	public function showPage()
	{
		echo "\n\n<!-- BEGIN {$this->module_name} -->\n";
		echo $this->getViewAsHtml($this->getContext('page'), array('content' =>
			$this->getViewAsHtml($this->getContext())
		));
		echo "\n<!-- END {$this->module_name} -->\n\n";
	}

	// DEFAULT BMO HANDLERS (override as needed)

	// right side pop-out navigation bar
	public function getRightNav($request) {
		// fake a new context using the subview (this should be fixed to properly merge)
		$context = $this->getContext();
		if (empty($context->view['rnav'])) {
			return;
		}
		$context->view = $context->view['rnav'];

		// use this view's rnav sub-view
		return "\n".$this->getViewAsHtml($context)."\n";
	}

	// floating action bar
	public function getActionBar($request) {
		$buttons = array();
		switch($request[self::DISPLAY_ARG]) {
			//this is usually your module's rawname
			case $this->module_name:
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				//We hide the delete button if we are not editing an item. "id" should be whatever your unique element is.
				if (empty($request['id'])) {
					unset($buttons['delete']);
				}
				// If we are not in the form view lets 86 the buttons
				// TODO: this isn't working right
				if (empty($request[self::VIEW_ARG])){
					unset($buttons);
				}
			break;
		}
		return $buttons;
	}

	public function handlePostActionAdd() {
		$context = $this->getContext();
		$result = $this->addRecord($context, $_REQUEST);
		if (!empty($result['error'])) {
			return $result;
		}
		$_REQUEST['id'] = $result;
		return Null;
	}
	public function handlePostActionEdit() {
		$context = $this->getContext();
		return $this->updateRecord($context, $_REQUEST);
	}
	public function handlePostActionDelete() {
		$context = $this->getContext();
		return $this->deleteRecord($context, $_REQUEST);
	}
	// this is a dev tool to erase db and reset sql to match schema
	// use by adding menu item with &_a=reinstall
	public function handlePostActionReinstall() {
		echo '<pre>';
		// these functions output progress which could be useful diagnostically
		$this->uninstall();
		$this->install();
		echo '</pre>';
	}

	// handle cruddy requests
	public function doConfigPageInit($page) {
		$result = Null;
		if (!empty($_REQUEST[self::ACTION_ARG])) {
			$record = $this->filterNotControlFields($_REQUEST);
			$func = 'handlePostAction'.ucfirst($_REQUEST[self::ACTION_ARG]);
			if (!method_exists($this, $func)) {
				throw new \Exception("Method $func() is not defined");
			}
			$result = call_user_func(array($this, $func), $record);
			if (!empty($result['error'])) {
				echo $this->tag('div', array('class' => 'alert alert-danger'),
					$this->tag('p', array(), print_r($result['error'], true))
				);
			} else if (!empty($result)) {
				if ($result === true) {
					// presume this to be a successful result
					$result = Null;
				} else {
					// not sure what to do with this, but display it anyway
					echo '<pre>'.print_r($result, true).'</pre>';
				}
			}
			unset($_REQUEST[self::ACTION_ARG]);
		}
		// if there was some sort of 'failure' result, don't follow 'success' path of postview
		if (empty($result) && !empty($_REQUEST[self::POSTVIEW_ARG])) {
			$post_context = $this->getContext($_REQUEST[self::POSTVIEW_ARG]);
			$_REQUEST[self::VIEW_ARG] = $post_context->view_name;
			$_REQUEST[self::TABLE_ARG] = $post_context->table_name;
			unset($_REQUEST[self::POSTVIEW_ARG]);
		}
	}

	// standard install/uninstall
	public function install()
	{
		foreach ($this->schema as $dbname => $table) {
			if (empty($table['table'])) continue;
			out(_('Creating database table ' . $dbname));
			$table_fields = $this->getIncludedFields($table['fields']);
			$definitions = implode(', ', array_map(
				function($field_name) use ($table_fields) {
					return "`$field_name` {$table_fields[$field_name]['sqltype']}";
				},
				array_keys($table_fields)
			));
			$sql = "CREATE TABLE IF NOT EXISTS {$table['table']} ($definitions);";
			$this->db->query($sql);
		}
	}
	public function uninstall()
	{
		foreach ($this->schema as $dbname => $table) {
			if (empty($table['table'])) continue;
			out(_('Removing database table ' . $dbname));
			$sql = "DROP TABLE IF EXISTS {$table['table']};";
			$this->db->query($sql);
		}
	}

	// backup/restore not implemented
	public function backup() {}
	public function restore($backup) {}

	// check for valid ajax request
	public function ajaxRequest($req, &$setting) {
		//The ajax request
		if (method_exists($this, 'ajax_'.$req)) {
			//Tell BMO This command is valid for authenticated non-remote requests
			return true;
		} else {
			//Deny everything else
			return false;
		}
	}

	// process ajax request
	public function ajaxHandler() {
		return call_user_func(array($this, 'ajax_'.$_REQUEST['command']));
	}

	// default getJSON handler returns all records for table
	public function ajax_getJSON() {
		return $this->getAllRecords($this->getContext());
	}
}
