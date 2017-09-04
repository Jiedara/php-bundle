<?php

App::uses('AppModel', 'Model');

/**
* Handle synchronization between differents set of datas
* For example, languages in table 'qusto_billing_accounts' need to be the same as languages on table 'invoice_clients'
* based on a certain relationship ('invoice_client_id' on the 'qusto_billing_accounts' table for example)
*/
class Synchronizer extends AppModel
{
	/**
	 * It's a readonly model, so we deactivate cakePHP table handling
	 *@var false
	 */
	public $useTable = false;

	/**
	 *local parameters
	 *@var array
	 */
	public $local = array(
		'table' => NULL,
		'relation_id' => NULL,
		'column' => NULL,
		);

	/**
	 *$external parameters
	 *@var array
	 */
	public $external = array(
		'table' => NULL,
		'relation_id' => NULL,
		'column' => NULL,
		);

	/**
	 *Contain datas of both local and external tables
	 *@var array
	 */
	private $datas = array(
		'local' => NULL,
		'external' => NULL,
		);

	/**
	 *When $soft is true, only the diff between the two column will be returned
	 *no datas will be updated. When $soft is false, the datas will actually be updated
	 *@var boolean
	 */
	public $soft = true;

	/**
	 *When $force is false, the update of datas will only occur on 'NULL' value in the target column
	 *When $force is true, the update will override all datas of the target table, regardless of their previous value
	 *@var boolean
	 */
	public $force = false;

	/**
	 *$safemode generate a dump of the datas before they are modified by the synchronization
	 *@var boolean
	 */
	public $safemode = true;

	/**
	 *Decide if the local datas need to be pulled from the external datas
	 *Or if the local datas need to be pushed on the external datas
	 *@var string : pull|push
	 */
	public $way = 'pull';

	/**
	 *Array of differencies between the target and the master tables
	 *@var array
	 */
	private $diffs = array();

	function __construct($local = NULL, $external = NULL, $way = NULL)
	{
		if(!empty($local)){
			$this->setParameters('local', $local);
		}
		if(!empty($external)){
			$this->setParameters('external', $external);
		}
		if(!empty($way)){
			$this->way = $way;
		}
	}

	/**
	 *Set the local and external parameter, ensuring that every data is rightfully setted
	 *@param  string $type       local|external
	 *@param  Array  $parameters array of parameters need to be inserted in the variable
	 */
	private function setParameters($type, Array $parameters)
	{
		foreach ($this->local as $key => $value) {
			if(!isset($parameters[$key])){
				throw new Exception("Your $type parameter array need to have at least the keys 'table', 'relation_id' and 'column'", 1);
			}
		}
		$this->$type = $parameters;
	}

	/**
	 *Run a select query with the right parameter from $local and $external, populating the $datas array
	 *Also handle cakePHP crappy formating to generate a better array
	 */
	public function datas()
	{
		$this->datas['local'] = $this->sanitizeArray($this->query('SELECT `'. $this->local['relation_id'] .'` AS "relation_id", `'. $this->local['column'] .'` AS "column" FROM ' . $this->local['table']), $this->local['table']);
		$this->datas['external'] = $this->sanitizeArray($this->query('SELECT `'. $this->external['relation_id'] .'` AS "relation_id", `'. $this->external['column'] .'` AS "column" FROM ' . $this->external['table']), $this->external['table']);
		return $this->datas;
	}

	/**
	 *[sanitizeArray description]
	 *@param  Array  $array  [description]
	 *@param  [type] $remove [description]
	 *@return [type] [description]
	 */
	private function sanitizeArray(Array $array, $remove)
	{
		$res = array();
		foreach($array as $key => $result) {
			$res[$result[$remove]['relation_id']] = $result[$remove]['column'];
		}
		return $res;
	}

	/**
	 *[diffs description]
	 *@return [type] [description]
	 */
	public function diffs()
	{
		foreach($this->datas['external'] as $key => $value){
			if($this->datas['local'][$key] != $value){
				$this->diffs[$key] = array('local' => $this->datas['local'][$key], 'external' => $value);
			}
		}
		return $this->diffs;
	}

	/**
	 *[applyDiffs description]
	 *@return [type] [description]
	 */
	private function applyDiffs()
	{
		if($this->way == 'pull'){
			$applyOn = $this->local;
			$target = 'external';
		}
		else {
			$applyOn = $this->external;
			$target = 'local';
		}
		foreach ($this->diffs as $id => $value) {
			$value = $value[$target];
			if(!$this->force){
				$force = ' AND `'. $applyOn['column'] . '` IS NOT NULL';
			}else {
				$force = '';
			}
			$this->query('UPDATE `'. $applyOn['table'] .'` SET `'. $applyOn['column'] .'` = "'. $value . '" WHERE `'. $applyOn['relation_id'] .'` = ' . $id . $force);
		}
		return true;
	}

	/**
	 *Handle the different function of the object,
	 *@return [type] [description]
	 */
	public function sync(){
		$this->datas();
		$this->diffs();
		if($this->soft){
			return $this->diffs;
		}
		return $this->applyDiffs();
	}

}
