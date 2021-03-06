<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\view\adapter;

use \Closure;
use \Exception;
use \lithium\util\Set;
use \lithium\util\String;

/**
 * This view adapter renders content using simple string substitution, and is only useful for very
 * simple templates (no conditionals or looping) or testing.
 *
 */
class Simple extends \lithium\template\view\Renderer {

	public function __construct($config = array()) {
		$defaults = array('classes' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = array(), $options = array()) {
		$context = array();
		$this->_context = $options['context'] + $this->_context;

		foreach (array_keys($this->_context) as $key) {
			$context[$key] = $this->__get($key);
		}
		$data = array_merge($this->_toString($context), $this->_toString($data));
		return String::insert($template, $data, $options);
	}

	/**
	 * Returns a template string
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	public function template($type, $options) {
		return isset($options[$type]) ? $options[$type] : '';
	}

	protected function _toString($data) {
		foreach ($data as $key => $val) {
			switch (true) {
				case is_object($val) && !$val instanceof Closure:
					try {
						$data[$key] = (string) $val;
					} catch (Exception $e) {
						$data[$key] = '';
					}
				break;
				case is_array($val):
					$data = array_merge($data, Set::flatten($val));
				break;
			}
		}
		return $data;
	}
}

?>