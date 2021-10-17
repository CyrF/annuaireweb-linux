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


/**
 * Classe pour gerer un annuaire ldap
 *
 */
class AnnuaireLDAP {
	protected $ds;
	private $bound;
	private $ldap_server;
	private $ldap_admin;
	private $ldap_pass;
	private $ldap_base_dn;
	protected $ldap_uo;

	function __construct($dc, $admin, $pass, $racine) {
		$this->ldap_server	= 'localhost';
		$this->ldap_admin	= "cn=$admin,$dc";
		$this->ldap_pass	= $pass;
		$this->ldap_base_dn	= $dc;
		$this->ldap_uo	= array(
			'racine' =>	"ou=$racine,$dc",
			'acces' =>	"ou=DroitAccess,ou=$racine,$dc",
			'group' =>	"ou=Groupes,ou=$racine,$dc",
			'users' =>	"ou=Utilisateurs,ou=$racine,$dc");
		$this->ds			= false;
		$this->bound		= false;
	}


	/* ----------------------------------- UTILISATEURS --------------------------- */

	/**
	 * Ajoute un utilisateur dans l'annuaire
	 *
	 *	@param string $name		nom complet de l'utilisateur.
	 *	@param string $uid		nom de session.
	 *	@param string $pass		mot de passe, si=null sera autogeneré.
	 *	@param int $num			identifiant numerique, si=0 sera autogeneré.
	 *  @todo traiter les cas avec autogenere
	 *
	 *	@return null
	 */
	function ajouter_utilisateur($name, $uid, $pass, $num='auto') {
		global $cipher_key;
		$this->connecter(true);
		if (!$this->get_cheminldap($uid)) { // ne pas ajouter si deja existant.
			// Prépare les données
			$info["objectClass"][0] = "top";
			$info["objectClass"][1] = "account";
			$info["objectClass"][2] = "posixAccount";
			$info["objectClass"][3] = "shadowAccount";
			$info["cn"] = $name;
			if ($num == 'auto') {
				$num = $this->get_last_userid() + 1;
			}
			$info["uidNumber"] = $num;
			$info["gidNumber"][0] = $num;
			$this->ajouter_groupe("private_$num", $num, $uid, 'private');	// ajoute le groupe privé si il existe pas
			/*
			$info["gidNumber"][0] = 100; // grp users
			$info["gidNumber"][1] = 24; // grp cdrom
			$info["gidNumber"][2] = 29; // grp audio
			$info["gidNumber"][3] = 44; // grp video
			$info["gidNumber"][4] = 46; // grp plugdev
			*/
			$info["homeDirectory"] = "/home/$uid";
			$info['gecos'] = chiffrer($pass, "uid=$uid,{$this->ldap_uo['users']}", $cipher_key);
			$info["userPassword"] = "{SHA}" . base64_encode( sha1( $pass, TRUE )); // syntaxe pour php>5
			$info["loginShell"] = "/bin/bash";

			// Ajoute les données au dossier
			$r = ldap_add($this->ds, "uid=$uid,{$this->ldap_uo['users']}", $info);
			if (!$r) {
				return "ajouter_utilisateur: " . ldap_error($this->ds) . '. '. ldap_get_option($this->ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
			} else {
				return true;
			}
		}
		return "ajouter_utilisateur: $uid existe deja";
	}

	/**
	 * Modifie un attribut d'un utilisateur dans l'annuaire
	 *
	 *	@param string $dn		chemin complet de l'utilisateur.
	 *	@param string $attr		attribut a modifier.
	 *	@param mixed $value		donnée a mettre dans un tableau si multiple.
	 *
	 *	@return bool true, ou description de l'erreur.
	 */
	function modifier_utilisateur($dn, $attr, $value) {
		global $cipher_key;
		$this->connecter(true);

		if ($attr == 'userPassword') {
			$info['gecos'] = chiffrer($value, $dn, $cipher_key);		// stocke avec un chiffrage AES pour les exports
			$value = "{SHA}" . base64_encode( sha1( $value, TRUE ));	// stocke le hash SHA pour l'authentification
		}
		if ($attr == 'gidNumber') {
			if ($value == '100') {
				$this->ajouter_groupe('users', 100, 'nobody', 'Commun aux utilisateurs');	// ajoute le groupe systeme 'users' si il existe pas
			} else {
				$this->ajouter_groupe("private_$value", $value, 'nobody', 'private');	// ajoute le groupe privé si il existe pas
			}
		}
		$info[$attr] = $value;

		$r = ldap_mod_replace($this->ds, $dn, $info);
		if (!$r) {
			return "modifier_utilisateur: " . ldap_error($this->ds) . '. '. ldap_get_option($this->ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
		} else {
			return true;
		}
	}

	/**
	 * Retourne les utilisateurs
	 *
	 *	@return array
	 */
	function get_users_info($uid='*'){
		$this->connecter();
		$cherche = ldap_search($this->ds, "{$this->ldap_uo['users']}", "uid=". ldap_escape($uid, '*', LDAP_ESCAPE_FILTER));
		$info = ldap_get_entries($this->ds, $cherche);
		$resultat = array();

		for ($i=0; $i<$info["count"]; $i++) {
			$groups = $this->get_usergroups( $info[$i]["uid"][0] );
			$resultat[] = array(
				'uidnumber' => $info[$i]["uidnumber"][0],
				'cn' => $info[$i]["cn"][0],
				'uid' => $info[$i]["uid"][0],
				'gid' => $info[$i]["gidnumber"][0],
				'pwd' => $info[$i]["gecos"][0],
				'groups' => $groups);
		}
		if ($uid != '*' && !empty($resultat)) {
			return $resultat[0];
		} else {
			return $resultat;
		}
	}

	/**
	 * Retourne le dernier id numérique utilisé
	 *
	 *	@param int $lastid	(2000 par defaut pour eviter le chevauchement avec les comptes locaux)
	 *
	 *	@return int userid
	 */
	function get_last_userid($lastid = 2000){
		$this->connecter();
		$sr = ldap_list($this->ds, "{$this->ldap_uo['users']}", "uid=*", array("uidNumber"));
		$info = ldap_get_entries($this->ds, $sr);

		for ($i=0; $i < $info["count"]; $i++) {
			// les noms des champs sont en lowercase
			if (intval($info[$i]["uidnumber"][0]) > $lastid) {
				$lastid = intval( $info[$i]["uidnumber"][0]);
			}
		}
		return $lastid;
	}

	/* ----------------------------------- UNITE D'ORGANISATION --------------------------- */

	/**
	 * ajoute une unité d'organization
	 *
	 *	@param string $name		nom de l'uo
	 *
	 *	@return bool, ou description de l'erreur.
	 */
	function ajouter_uo( $name, $racine=false ) {
		$this->connecter(true);
		if (!in_array($name, $this->lister_UO())) {
			// Prépare les données
			$info["objectClass"][0] = "top";
			$info["objectClass"][1] = "organizationalUnit";
			$info["ou"] = $name;
			// Ajoute les données au dossier
			$dn = ($racine)? "ou=$name,{$this->ldap_base_dn}" : "ou=$name,{$this->ldap_uo['racine']}";
			$r = ldap_add($this->ds, $dn, $info);
			if (!$r) {
				return "ajouter_uo: " . ldap_error($this->ds);
			}
		} else {
			return "ajouter_uo: L'uo $name existe deja";
		}
		return true;
	}

	/**
	 * retourne la liste des Unités d'organization sur le serveur.
	 *
	 *	@return array
	 */
	function lister_UO(){
		$this->connecter();

		$sr = ldap_list($this->ds, $this->ldap_uo['racine'], "(objectClass=organizationalUnit)", array("ou"));
		$info = ldap_get_entries($this->ds, $sr);
		$resultat = array();

		for ($i=0; $i < $info["count"]; $i++) {
			$resultat[] = $info[$i]["ou"][0];
		}
		return $resultat;
	}

	/* ----------------------------------- DROITS D'ACCES --------------------------- */

	/**
	 * ajoute un groupe pour gerer les droits d'accès
	 *
	 *	@param string $name		nom de l'acl
	 *	@param string $desc		description
	 *
	 *	@return bool, ou description de l'erreur.
	 */
	function ajouter_droit( $name, $desc, $member ) {
		$this->connecter(true);
		if (!in_array($name, $this->lister('acces'))) {
			// Prépare les données
			$info["objectClass"][0] = "top";
			$info["objectClass"][1] = "groupOfUniqueNames";
			$info["cn"] = $name;
			$info["description"] = $desc;
			$info["uniqueMember"][0] = $this->get_cheminldap($member);
			// Ajoute les données au dossier
			$r = ldap_add($this->ds, "cn=$name,{$this->ldap_uo['acces']}", $info);
			if (!$r) {
				return "ajouter_droit: " . ldap_error($this->ds);
			}
		} else {
			return "ajouter_droit: L'acl $name existe deja";
		}
		return true;
	}

	/**
	 * attribue des droits a un utilisateur
	 *
	 *	@param string $uid		nom de session.
	 *	@param array $usracl	liste des acl
	 *
	 *	@return string description de l'erreur.
	 */
	function attribuer_droit($uid, $usracl) {
		$this->connecter(true);
		$all = $this->get_userAccessList('*');	// tous les acl dans la base
		$cur = $this->get_userAccessList($uid);	// les acl actuels de l'user
		$info['uniqueMember'] = $this->get_cheminldap( $uid );
		$errormsg = '';
		foreach ($all as $acl) {
			if (!is_array($usracl) and $usracl == $acl) {
				if (!in_array($acl, $cur)) {	// il ne l'est pas -> add
					$r = ldap_mod_add($this->ds, "cn=$acl,{$this->ldap_uo['acces']}", $info);
					if (!$r) { $errormsg .= "attribuer_droit: Ajouter $uid dans $acl: " . ldap_error($this->ds); }
				} else { // il est associé mais ne devrait pas. -> remove
					$r = ldap_mod_del($this->ds, "cn=$acl,{$this->ldap_uo['acces']}", $info);
					if (!$r) { $errormsg .= "attribuer_droit: supprimer $uid dans $acl: " . ldap_error($this->ds); }
				}
			}
			if (in_array($acl, $usracl) and is_array($usracl)) {		// il doit etre associé a ce groupe
				if (!in_array($acl, $cur)) {	// il ne l'est pas
					//add
					$r = ldap_mod_add($this->ds, "cn=$acl,{$this->ldap_uo['acces']}", $info);
					if (!$r) { $errormsg .= "attribuer_droit: Ajouter $uid dans $acl: " . ldap_error($this->ds); }
				}
			} elseif (in_array($acl, $cur) and is_array($usracl)) {// il est associé mais ne devrait pas.
				//remove
				$r = ldap_mod_del($this->ds, "cn=$acl,{$this->ldap_uo['acces']}", $info);
				if (!$r) { $errormsg .= "attribuer_droit: supprimer $uid dans $acl: " . ldap_error($this->ds); }
			}
		}
		return $errormsg;
	}

	/**
	 * Retourne les acces d'un utilisateur
	 *
	 *	@param string $uid	identifiant utilisateur
	 *
	 *	@return array		liste d'AccessList
	 */
	function get_userAccessList($uid, $description=false){
		$this->connecter();
		if ($uid == '*') {
			$filtre = "(objectClass=groupOfUniqueNames)";
		} else {
			$dn = $this->get_cheminldap( $uid );
			$filtre = "(&(objectClass=groupOfUniqueNames)(uniqueMember=" . ldap_escape($dn, '*', LDAP_ESCAPE_FILTER) . "))";
		}
		$sr = ldap_list($this->ds, $this->ldap_uo['acces'], $filtre, array("cn", "description"));
		$info = ldap_get_entries($this->ds, $sr);
		$resultat = array();

		for ($i=0; $i < $info["count"]; $i++) {
			if ($description) {
				$resultat[] = array(
					'description' => $info[$i]["description"][0],
					'cn' => $info[$i]["cn"][0]);
			} else {
				$resultat[] = $info[$i]["cn"][0];
			}
		}
		return $resultat;
	}
	/* ----------------------------------- GROUPES UTILISATEURS --------------------------- */

	/**
	 * ajoute ou supprime un utilisateur dans les groupes
	 *
	 *	@param string $uid		nom de session.
	 *	@param array $usrgrp	liste des groupes a ajouter/ ceux absents seront supprimer
	 *
	 *	@return string description de l'erreur.
	 */
	function modifier_groupe($uid, $usrgrp) {
		$this->connecter(true);
		$allgrp = $this->get_usergroups('*');	// tous les groupes dans la base
		$curgrp = $this->get_usergroups($uid);	// les groupes actuels de l'user
		$info['memberUid'] = $uid;
		$errormsg = '';
		foreach ($allgrp as $grp) {
			if (!is_array($usrgrp) and $usrgrp == $grp) {
				if (!in_array($grp, $curgrp)) {	// il ne l'est pas -> add
					$r = ldap_mod_add($this->ds, "cn=$grp,{$this->ldap_uo['group']}", $info);
					if (!$r) { $errormsg .= "modifier_groupe: Ajouter dans groupe $grp: " . ldap_error($this->ds); }
				} else { // il est associé mais ne devrait pas. -> remove
					$r = ldap_mod_del($this->ds, "cn=$grp,{$this->ldap_uo['group']}", $info);
					if (!$r) { $errormsg .= "modifier_groupe: supprimer dans groupe $grp: " . ldap_error($this->ds); }
				}
			}
			if (is_array($usrgrp) and in_array($grp, $usrgrp)) {		// il doit etre associé a ce groupe
				if (!in_array($grp, $curgrp)) {	// il ne l'est pas
					//add
					$r = ldap_mod_add($this->ds, "cn=$grp,{$this->ldap_uo['group']}", $info);
					if (!$r) { $errormsg .= "modifier_groupe: Ajouter dans groupe $grp: " . ldap_error($this->ds); }
				}
			} elseif (in_array($grp, $curgrp) and is_array($usrgrp)) {// il est associé mais ne devrait pas.
				//remove
				$r = ldap_mod_del($this->ds, "cn=$grp,{$this->ldap_uo['group']}", $info);
				if (!$r) { $errormsg .= "modifier_groupe: supprimer dans groupe $grp: " . ldap_error($this->ds); }
			}
		}
		return $errormsg;
	}

	/**
	 * Ajoute un groupe dans l'annuaire
	 *
	 *	@param string $name		nom du groupe.
	 *	@param int $num			identifiant numerique du groupe.
	 *	@param array $member	nom de session des membres.
	 *	@param string $desc		description du groupe.
	 *
	 *	@return bool
	 */
	function ajouter_groupe($name, $num, $member, $desc) {
		$this->connecter(true);
		if (!in_array($name, $this->get_usergroups())) {
			// Prépare les données
			$info["objectClass"][0] = "posixGroup";
			$info["gidNumber"] = $num;
			$info["memberUid"] = $member;
			$info["description"] = $desc;
			// Ajoute les données au dossier
			$r = ldap_add($this->ds, "cn=$name,{$this->ldap_uo['group']}", $info);
			if (!$r) {
				return "ajouter_groupe: " . ldap_error($this->ds);
			}
		} else {
			return "ajouter_groupe: Le groupe $name existe deja";
		}
		return true;
	}

	/**
	 * Retourne les groupes associés a un utilisateur
	 *
	 *	@param string $uid	nom de session (ex: bwayne)
	 *
	 *	@return array
	 */
	function get_usergroups($uid='*', $description=false, $cn='*'){
		$this->connecter();
		if ($uid == '*') {
			$filtre = "(&(objectClass=posixGroup)(cn=" . ldap_escape($cn, '*', LDAP_ESCAPE_FILTER) . "))";
		} else {
			$filtre = "(&(objectClass=posixGroup)(memberUid=" . ldap_escape($uid, '*', LDAP_ESCAPE_FILTER) . "))";
		}
		$cherche = ldap_search($this->ds, $this->ldap_uo['group'], $filtre);
		$info = ldap_get_entries($this->ds, $cherche);
		$resultat = array();

		for ($i=0; $i<$info["count"]; $i++) {
			if ($info[$i]["description"][0] != "private") {
				if ($description) {
					$resultat[] = array(
						'description' => $info[$i]["description"][0],
						'cn' => $info[$i]["cn"][0],
						'gidnumber' => $info[$i]["gidnumber"][0],
						'memberuid' => $info[$i]["memberuid"]);
				} else {
					$resultat[] = $info[$i]["cn"][0];
				}
			}
		}
/*		if ($cn != '*' && !empty($resultat)) {
			return $resultat[0];
		} else {*/
			return $resultat;
/*		}*/
	}

	/**
	 * Retourne le dernier id groupe utilisé
	 *
	 *	@param int $lastid	(2000 par defaut pour eviter le chevauchement avec les comptes locaux)
	 *
	 *	@return int userid
	 */
	function get_last_groupid($lastid = 1990){
		$this->connecter();
		$sr = ldap_list($this->ds, $this->ldap_uo['group'], "cn=*", array("gidNumber"));
		$info = ldap_get_entries($this->ds, $sr);

		for ($i=0; $i < $info["count"]; $i++) {
			$id = intval($info[$i]["gidnumber"][0]);
			if ($id > 1200 && $id < $lastid) {
				$lastid = $id;
			}
		}
		return $lastid;
	}

	/* ----------------------------------- DIVERS --------------------------- */

	/**
	 * Retourne le chemin LDAP correspondant a l'identifiant utilisateur
	 *
	 *	@param string $uid	nom de session (ex: bwayne)
	 *
	 *	@return string (ex: cn=Bruce Wayne,ou=BatCave,ou=Gotham,dc=usa), false si pas trouvé
	 */
	function get_cheminldap($uid, $key="uid"){
		if ($uid == '' ) {
			return false;
		}
		$this->connecter();
		$filtre = "($key=" . ldap_escape($uid, '*', LDAP_ESCAPE_FILTER) . ")";
		$cherche = ldap_search($this->ds, $this->ldap_uo['racine'], $filtre);
		$prem = ldap_first_entry($this->ds, $cherche);
		if ($prem) {
			return ldap_get_dn($this->ds, $prem);
		} else {
			return false;
		}
	}

	/**
	 * Se connecte au serveur LDAP
	 *
	 *	@param bool $auth	force une connexion authentifiée pour modifier l'annuaire.
	 *
	 *	@return bool true si connexion ok.
	 */
	protected function connecter($auth=false) {
		if (!$this->ds) { // initialise le socket si c'est pas deja fait
			$this->ds = ldap_connect($this->ldap_server);
			ldap_set_option($this->ds, LDAP_OPT_PROTOCOL_VERSION, 3); // php reste en version 2 par defaut.
			ldap_set_option($this->ds, LDAP_OPT_REFERRALS, 0);

		}
		if (!$this->bound or $auth) { // se connecte au serveur si c'est pas deja fait, ou force la reconnexion si non anonyme
			try {
				if ($auth) {
					$r = ldap_bind($this->ds, $this->ldap_admin, $this->ldap_pass);
				} else { // en anonyme si pas besoin d'ecrire des données
					$r = ldap_bind($this->ds);
				}
				$this->bound = $r;
			} catch (RuntimeException $e){
				if (ldap_get_option($this->ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
					echo "Error Binding to LDAP: $extended_error<br>";
				}
				echo "ldap_error: " . ldap_error($this->ds) . '<br>';
				$this->bound = false;
			}
		}
		return $this->bound;
	}

	/**
	 * supprime un utilisateur ou un groupe
	 *
	 *	@param string $dn		identifiant pour un utilisateur, nom pour un groupe
	 *	@param bool $isgrp		true pour supprimer un groupe.
	 *
	 *	@return bool, ou description de l'erreur.
	 */
	function supprimer_entree($id, $isgrp=false) {
		$this->connecter(true);
		$id = ldap_escape($id, '', LDAP_ESCAPE_DN);
		$dn = ($isgrp) ? "cn=$id,{$this->ldap_uo['group']}" : "uid=$id,{$this->ldap_uo['users']}";
		$r = ldap_delete($this->ds, $dn);
		if (!$r) {
			return "ldap_error: " . ldap_error($this->ds). " ($id)";
		}
		return true;
	}

	/**
	 * retourne une liste des objets.
	 *
	 *  @param string $type	type d'objets a lister
	 *
	 *	@return array
	 */
	function lister($type){
		$this->connecter();
		$l = array(
			'uo' =>		array(	'class' => 'organizationalUnit', 	'key' => 'ou'),
			'acces' =>	array(	'class' => 'groupOfUniqueNames', 	'key' => 'cn'),
			'group' =>	array( 	'class' => 'posixGroup', 			'key' => 'cn'),
			'users' =>	array( 	'class' => 'posixAccount', 			'key' => 'uid'));
		$k = $l[$type]['key'];

		$sr = ldap_list($this->ds, $this->ldap_uo[$type], "(objectClass={$l[$type]['class']})", array($k));
		$info = ldap_get_entries($this->ds, $sr);
		$resultat = array();

		for ($i=0; $i < $info["count"]; $i++) {
			$resultat[] = $info[$i][$k][0];
		}
		return $resultat;
	}

	/**
	 * Tente de s'authentifier avec un compte LDAP
	 *
	 *	@param string $username	nom d'ouverture de session.
	 *	@param string $password mot de passe.
	 *
	 *	@return bool true si connexion ok
	 */
	function authentifier($username, $password) {
		$this->connecter();
		$b = ldap_bind($this->ds, "uid=".ldap_escape($username, '', LDAP_ESCAPE_DN).",{$this->ldap_uo['users']}", $password);
		if ($b) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * ferme la connexion a la destruction de l'objet.
	 */
	function __destruct() {
		ldap_close($this->ds);
	}
}

?>