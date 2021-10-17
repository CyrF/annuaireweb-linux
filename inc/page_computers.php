<?php

$file = new FileAttenteFIFO($_SERVER['DOCUMENT_ROOT'] . "/fifo.db");

if ( isset($_GET['test']) ) {
	$file->AjouterDansFile( array( 'localhost', serialize($_GET)) );
}
var_dump($_POST);
var_dump($_GET);

if ( !empty($_POST) ) {
	$file->AjouterDansFile( array( $_SERVER['REMOTE_ADDR'], serialize($_POST)) );
} else {
	echo "<h3>en attente:</h3><pre>\n";
	foreach( $file->ListerFile() as $line) {
		echo $line['Id'] . "\t" . $line['header'] . "\t" . $line['Value'] . "\n";
	}
	echo '</pre>';
}

/**
 * Classe pour gerer une file d'attente
 *
 */
class FileAttenteFIFO {
	protected $FichierBase;
	protected $db_timeout;
	protected $db;

	function __construct($fichier) {
		$this->FichierBase	= $fichier;
		$this->db_timeout	= 500;

		try {
			$this->db	= new PDO("sqlite:" . $this->FichierBase, null, null, [PDO::ATTR_TIMEOUT => $this->db_timeout]);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			die('Ouverture database: '. $e->getMessage());
		}

		if (filesize($fichier) == 0) {
			$this->creerTable();
		}
	}

	/**
	 * creer la structure de la table si elle existe pas.
	 *
	 *	@return bool true
	 */
	private function creerTable() {
		try {
			// Cree la table pour stocker les messages.
			$this->db->exec('CREATE TABLE IF NOT EXISTS fifo (
				Id INTEGER PRIMARY KEY,
				Source TEXT,
				Date INTEGER,
				Value TEXT
				);');
		} catch (PDOException $e) {
			die('Database creation: '. $e->getMessage());
		}
	}

	/**
	 * Ajoute une entrée dans la file d'attente
	 *
	 *	@param array $args		array(host source, valeurs)
	 *
	 *	@return bool true
	 */
	function AjouterDansFile($args) {
		try {
			// insert le message dans la base.
			$statement = $this->db->prepare("INSERT INTO fifo (Source, Value, Date) VALUES (?, ?, strftime('%s','now'));");
			$statement->execute($args);
		} catch (PDOException $e) {
			die('Database insertion: '. $e->getMessage());
		}
		return true;
	}

	function PrendreDansFile() {
		return true;
	}

	/**
	 * liste toutes les entrées dans la file d'attente
	 *
	 *	@return array (Id, header, Value)
	 */
	function ListerFile() {
		$arr = array();
		// requete sql
		try {
			$statement = $this->db->prepare('SELECT Id, Source, Value, Date FROM fifo;');
			$statement->execute();
		} catch (PDOException $e) {
			die('Selection des lignes: '. $e->getMessage());
		}

		// rempli le tableau avec les infos
		while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
			array_push($arr, array(
				'Id' => $row['Id'],
				'header' => $row['Source'] .' - '. strftime('le %e %b à %R', (int) $row['Date']),
				'Value' => $row['Value']
			));
		}
		return $arr;
	}
}