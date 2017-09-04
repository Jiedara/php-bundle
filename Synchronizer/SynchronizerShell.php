<?php

App::uses('Shell', 'Console');

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * @package       app.Console.Command
 */
class SynchronizerShell extends Shell {

    /**
     * Shell tasks
     *
     * @var array
     */
    public $tasks = array();

    /**
     * Models used by shell
     *
     * @var array
     */
    public $uses = array();

    /**
     * Main execution function
     *
     * @return void
     */
	public function main()  {
		include('Lib/Synchronizer/Synchronizer.php');

		$local = array(
			'table' => 'plop1',
			'relation_id' => 'id',
			'column' => 'text',
		);
		$external = array(
			'table' => 'plop2',
			'relation_id' => 'id2',
			'column' => 'text2',
		);

		$Synchronizer = new Synchronizer($local, $external, 'push');
		$Synchronizer->soft = false;
		var_dump($Synchronizer->sync());
		die;
	}
}
