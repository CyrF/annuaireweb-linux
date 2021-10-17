<?php
/**
 * Copyright (C) 2020.
 * This file is a part of AnnuaireLinux
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */


if ( file_exists( "config.php" )) {
	// ne pas executer l'install si deja configuré.
	die("He's dead, Jim");
}

include("ldap.class.php");
include("functions.php");

if ( ! empty( $_POST['dc'] )) {

	$ldap = new AnnuaireLDAP($_POST['dc'], $_POST['adm'], $_POST['pwa'], $_POST['ou']);

	$cipher_key = $_POST['key'];

	/**
	 * Creation des UO.
	 */
	show_msg( $ldap->ajouter_uo($_POST['ou'], true), "Creation de l'UO racine {$_POST['ou']}" );

	$cfg_uo = array('Utilisateurs', 'Groupes', 'DroitAccess');

	foreach ($cfg_uo as $uo) {
		show_msg( $ldap->ajouter_uo($uo), "Creation de l'UO $uo" );
	}

	/**
	 * Creation de l'utilisateur gestionnaire.
	 */
	show_msg( $ldap->ajouter_utilisateur( 'Administrateur', $_POST['usr'], $_POST['pwu'] ), "ajouter le gestionnaire {$_POST['usr']}");


	/**
	 * Creation des accesslist.
	 */
	$cfg_acl = array(
		'can_acces'				=> 'acceder à cette application',
		'can_view_dashboard'	=> 'voir le tableau de bord',
		'can_view_users'		=> 'voir la liste de tous les utilisateurs',
		'can_create_user'		=> 'créer un nouvel utilisateur',
		'can_edit_user'			=> 'modifier la fiche d\'un utilisateur',
	//	'can_edit_password'		=> 'changer son mot de passe', /* toujours autoriser */
		'can_edit_usergroups'	=> 'modifier la liste des groupes attribués à un utilisateur',
		'can_edit_useraccess'	=> 'modifier les droits d\'accès dans cette application',
		'can_delete_user'		=> 'supprimer un utilisateur',
		'can_import'			=> 'importer des nouveaux utilisateurs dans un fichier CSV',
		'can_export'			=> 'exporter les comptes utilisateurs dans un fichier CSV',
		'can_view_groups'		=> 'voir la liste de tous les groupes',
		'can_view_groups_sys'	=> 'voir la liste de tous les groupes systèmes',
		'can_view_groups_user'	=> 'voir la liste de tous les groupes utilisateur',
		'can_view_groups_priv'	=> 'voir la liste de tous les groupes privés',
		'can_create_group'		=> 'créer un nouveau groupe',
		'can_edit_group'		=> 'modifier la fiche d\'un groupe',
		'can_delete_group'		=> 'supprimer un groupe',
	//	'can_edit_groupmembers'	=> 'modifier la liste des utilisateur attribués à un groupe', /* doublon */
		'can_edit_groupshares'	=> 'modifier les partages attribués à un groupe'
		);

	foreach ($cfg_acl as $cn => $desc) {
		show_msg($ldap->ajouter_droit($cn, $desc, $_POST['usr']), "Creation de l'accesslist $cn");
	}

	/**
	 * Creation des groupes systèmes.
	 */

	$cfg_sysgrp = array(
	//	array( 4,	'adm',				'Système: Les membres de ce groupe peuvent lire de nombreux fichiers journaux dans /var/log' ),
	//	array( 24,	'cdrom',			'Système: Ce groupe peut être utilisé pour permettre d'accéder à un lecteur optique' ),
		array( 27,	'sudo',				'Système: Les membres peuvent exécuter la commande sudo' ),
		array( 33,	'www-data',			'Système: Permet de modifier les fichiers du serveur Web' ),
	//	array( 37,	'operator',			'Système: Etait historiquement le seul compte qui pouvait se connecter à distance' ),
	//	array( 46,	'plugdev',			'Système: Permet aux membres de monter et de démonter les périphériques amovibles via pmount' ),
		array( 50,	'staff',			'Système: Permet de modifier /usr/local sans privilèges' ),
		array( 100,	'users',			'Système: Commun aux utilisateurs' ),
		array( 101,	'systemd-journal',	'Système: Les membres peuvent utiliser journalctl' ),
	//	array( 109,	'netdev',			'Système: Les membres de ce groupe peuvent gérer les interfaces réseau' )
	);

	foreach ($cfg_sysgrp as list($gid, $cn, $desc)) {
		show_msg($ldap->ajouter_groupe($cn, $gid, 'nobody', $desc), "Creation du groupe $cn");
	}

	/**
	 * Creation du fichier de config.
	 */
	$config = '<?php
$cfg = array (
	\'ldap_dc\'		=> \'' . $_POST['dc']  . '\',
	\'ldap_admin\'	=> \'' . $_POST['adm'] . '\',
	\'ldap_pwd\'	=> \'' . $_POST['pwa'] . '\',
	\'ldap_root\'	=> \'' . $_POST['ou']  . '\',
	\'gestion\'		=> \'' . $_POST['usr']  . '\');
$cipher_key = \'' . $_POST['key'] . '\';';

	if (file_put_contents("config.php", $config ) === false) {
		show_msg("le fichier de config n'a pas été créer", "");
		echo $config;
	} else {
		show_msg(true, "le fichier de config est créé");
	}

echo '<style>
input {
  padding: 5px 20px;
  width: 30%;
  margin: 5px;
}
label {
  width: 20%;
  display: inline-block;
  text-align: end;
}
.msg-error {	/* message erreur */
	padding:10px;
	margin:10px;
	color: #a94442;
    background-color: #f2dede;
    border: 1px solid #ebccd1;
	border-radius: 4px;
	/*display:none;*/
}
.msg-info {	/* message info */
	padding:10px;
	margin:10px;
	color: #31708f;
	background-color: #d9edf7;
	border: 1px solid #bce8f1;
	border-radius: 4px;
}
</style>';
} else { // isset post
?>

<form action="?install" method="post">
<p>La racine est dérivée du fqdn du serveur, par ex: srv-ldap.local donnera "dc=srv-ldap,dc=local". La commande "slapcat" sur le serveur peut l'afficher.</p>
	<label for="dc">Racine:</label>							<input name="dc" type="text" value="dc=local" /><br />
<p>Les comptes et groupes seront placés dans cette unité d'organisation.</p>
	<label for="ou">Unité d'organisation:</label>			<input name="ou" type="text" value="Linux" /><br />
<p>Le compte admininstrateur qui a été créer à l'installation. Reexecuter la commande "dpkg-reconfigure slapd" si ce n'est pas le cas.</p>
	<label for="adm">LDAP Administrator:</label>			<input name="adm" type="text" value="admin" /><br />
	<label for="pwa">Mot de passe administrator:</label>	<input name="pwa" type="password" value="azerty" /><br />
<p>Clef utilisée pour chiffrer les mots de passe dans la base.</p>
	<label for="key">Clef de chiffrement:</label>			<input name="key" type="text" value="0frFS80QpAoGP6ug9gbzaVdL8oDoBgzC5wmJdwh7wX2rU3fR8XyA4N6oyw" onfocus="generate_pwd(this, 16);" /><br />
<p>Compte admininstrateur pour se connecter à cette interface web.</p>
	<label for="usr">Gestionnaire de l'annuaire web:</label>	<input name="usr" type="text" value="locadm" /><br />
	<label for="pwu">Mot de passse Gestionnaire web:</label>	<input name="pwu" type="text" value="azerty" onfocus="generate_pwd(this, 8);" /><br />
	<label for="pwtest">Mot de passse Gestionnaire web:</label>	<input name="pwtest" type="text" value="" onfocus="generate_pwd(this, 8);" /><br />
	<label for="pwtest2">Mot de passse Gestionnaire web:</label>	<input name="pwtest2" type="text" value="" onfocus="generate_pwd(this, 16);" /><br />

	<br /><input type="submit" value="Installer" /><br />
</form>

<?php
} // else isset post