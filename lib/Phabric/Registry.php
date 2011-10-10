<?php
namespace Phabric;

/**
 *
 * @author Wojtek Oledzki
 * @author Glen Mailer
 *
 */
class Registry {

	protected $lookup = array();
	protected $reverseLookup = array();

	public function __construct(array $registry = array()) {
		foreach ($registry as $registryName => $collection) {
			$this->addCollection($registryName, $collection);
		}
	}

	public function add($registryName, $key, $value) {
		$this->addCollection($registryName, array($key => $value));
	}

	public function addCollection($registryName, $collection) {
		$this->lookup[$registryName] = $collection + $this->getLookup($registryName);
		$this->appendReverseLookup($registryName, $collection);
	}

	public function get($registryName, $key, $default = null) {
		$collection = $this->getLookup($registryName);

		if (empty($collection)) {
			return $default;
		}

		if (!isset($collection[$key])) {
			return $default;
		}

		return $collection[$key];
	}

	public function reverseGet($registryName, $key, $default = null) {
		$collection = $this->getReverseLookup($name);

		if (empty($collection)) {
			return $default;
		}
		if (!isset($collection[$key])) {
			return $default;
		}

		$key = serialize($key);
		return $collection[$key];
	}

	protected function createReverseLookup($name, $collection) {
		$this->reverseLookup[$name] = $this->switchArrayKeysValues($collection);
	}

	protected function appendReverseLookup($name, $collection) {
		$collection = $this->switchArrayKeysValues($collection);
		$collection = $collection + $this->getReverseLookup($name);
		$this->reverseLookup[$name] = $collection;
	}

	/**
	 * Returns lookup array for given registry name.
	 *
	 * @param string $name
	 */
	protected function getLookup($name) {
		if (!isset($this->lookup[$name])) {
			return array();
		}

		return $this->lookup[$name];
	}

	protected function getReverseLookup($name) {
		if (!isset($this->reverseLookup[$name])) {
			return array();
		}

		return $this->reverseLookup[$name];
	}

	protected function switchArrayKeysValues(array $collection) {
		$reversed = array();

		foreach ($collection as $key => $item) {
			if (is_scalar($item) || is_array($item)) {
				if (!isset($reversed[serialize($item)])) {
					$reversed[serialize($item)] = $key;
				}
			}
		}

		return $reversed;
	}
}
