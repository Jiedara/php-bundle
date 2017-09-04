<?php
App::uses('AppModel', 'Model');

/**
 *	The Parent Bridge class
 *	implemented in their children
 *	to connect two applications wide tables
 *	with a default many to many relationship
 */
class Bridge extends AppModel
{
	/**
	 * The table in this app that need to connect outside
	 *@var string
	 */
	protected $from;

	/**
	 * The table outside, connected to this local app
	 *@var string
	 */
	protected $to;

	/**
	 * id keys used in the bridge model
	 *@var int
	 */
	protected $fromKey;
	protected $toKey;

	/**
	 * The bridge table, connecting the two tables
	 *@var false
	 */
	protected $on;

	/**
	 * It's a readonly model, so we deactivate cakePHP table handling
	 *@var false
	 */
	public $useTable = false;

	/**
	 * The relationship type between the two tables
	 *@var string
	 */
	protected $relationship = 'ManyToMany';

	/**
	 * CakeModel variable
	 *@var string
	 */
	public $tablePrefix = 'bridge_';

	/**
	 * CONSTRUCTOR
	 */
	function __construct()
	{
		parent::__construct();

		//try to predict the bridge table name based on $from or $to
		if(isset($this->from) && isset($this->to) && !$this->on){
			$this->on = $this->removePrefix($this->from);
			if(!$this->tryConnection()){
				$this->on = $this->removePrefix($this->to);
				if(!$this->tryConnection()){
					throw new Exception(__('No bridge table found for ' . $this->from . ' and ' . $this->to));
				}
			}
		}
	}

	private function removePrefix($text, $delimiter = '_'){
		$name = explode($delimiter,$text);
		unset($name[0]);
		return implode($delimiter, $name);
	}

	private function tryConnection(){
		try {
			$this->query('SELECT 1 FROM ' . $this->tablePrefix . $this->on . ' LIMIT 1;');
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Set the $from variable and eventually the $fromKey variable
	 *@param  string $from
	 *@param  string $key
	 *@return  $this the current object
	 */
	public function from($from, $key = null){
		$this->from = $from;
		if(isset($key)){
			$this->fromKey = $key;
		}else {
			$this->fromKey = $this->removePrefix($from) . '_id_' . explode('_', $from)[0];
		}
		return $this;
	}

	/**
	 * Set the $to variable and eventually the $toKey variable
	 *@param  string $to
	 *@param  string $key
	 *@return  $this the current object
	 */
	public function to($to, $key = null){
		$this->to = $to;
		if(isset($key)){
			$this->toKey = $key;
		}else {
			$this->toKey = $this->removePrefix($to) . '_id_' . explode('_', $to)[0];
		}
		return $this;
	}

	/**
	 * Set the $relationship variable
	 *@param  string $relationship
	 *@return  $this the current object
	 */
	public function relationship($relationship){
		$this->relationship = $relationship;
		return $this;
	}

	/**
	 * Set the $on variable
	 *@param  string $on
	 *@return  $this the current object
	 */
	public function on($on){
		$this->on = $on;
		return $this;
	}

	/**
	 * Retrieve the datas of the distant table,
	 * Related to the local table, with the right relationship
	 *@param  null|array|int $id the local id (null to retrieve everything)
	 *@return array results
	 */
	public function cross($ids = null){

		$on =  $this->tablePrefix . $this->on;

		if(!$this->tryConnection()){
			throw new Exception(__('No bridge table found for ' . $this->from . ' and ' . $this->to));
		}

		if(isset($ids) && is_array($ids)){
			$ids = implode(',',$ids);
		}

		$query = 'SELECT * FROM ' . $on .
				' JOIN ' . $this->from . ' ON ' . $this->from . '.id = ' . $on .'.'. $this->fromKey .
				' JOIN ' . $this->to . ' ON ' . $this->to . '.id = ' . $on .'.'. $this->toKey;
		if(isset($ids)){
			$query .= ' WHERE ' . $on . '.' . $this->fromKey . ' IN ('.$ids.')';
		}
		return $this->query($query);
	}
}
