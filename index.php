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

// redirige vers l'install si pas de config.
if ( ! file_exists( "inc/config.php" )) {
	header("Location: install.php");
	exit();
}

session_start();

include("inc/functions.php");
include("inc/config.php");
include("inc/ldap.class.php");

$pageid = 'dashboard';
$pageid_racine = $pageid;

$ldap = new AnnuaireLDAP( $cfg['ldap_dc'], $cfg['ldap_admin'], $cfg['ldap_pwd'], $cfg['ldap_root'] );

if ( ! empty( $_GET ) ) {
	if ( isset( $_GET['logout'] )) {	// deconnecte l'utilisateur
		session_unset();
		session_destroy();
		header("Location: index.php");
	}
	if ( isset( $_GET['pg'] )) {	// extrait la page demandée
	$pageid = htmlspecialchars($_GET['pg']);
	list($pageid_racine) = explode('_', $pageid);
	}
}

if ( ! empty( $_POST ) ) {
    if ( isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
		// quelqu'un essaie de se connecter...
		$is_auth = $ldap->authentifier($_POST['username'], $_POST['password']);
		if ($is_auth != false) {
			$userinfo = $ldap->get_users_info($_POST['username']);
			$_SESSION['user_id'] = $userinfo['uid'];
			$_SESSION['user_name'] = $userinfo['cn'];
			$_SESSION['user_groups'] = $userinfo['groups'];
			$_SESSION['user_acl'] = $ldap->get_userAccessList( $userinfo['uid'] );
    	} else {
			$_SESSION['failed_count'] += 1;
		}
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="fr">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link href="style-base.css" rel="stylesheet" type="text/css">
	<script src="functions.js"></script>
	<title>Annuaire Comptes Posix</title>
</head>
<?php
// si un utilisateur est connecté
if ( isset( $_SESSION['user_id'] ) ) {
    // affiche la page.
?>
<body>
<div>
	<div id="sidebar" class="shadow">
		<img id="sidebar-logo" src="logo.png" />
		<ul>
<?php
// affiche les menus dans la sidebar
$arr = array(
	'dashboard'	=> 'Tableau de bord',
	'computers'	=> 'Ordinateurs',
	'users'	=> 'Utilisateurs',
	'groups'	=> 'Groupes');
foreach ($arr as $key => $value) {
	if (check_acl( "can_view_$key" )) {
	if ($pageid_racine == $key) {
		echo '<li class="sidebar-item actif"><a  class="sidebar-lien" href="?pg='. $key .'">'. $value ."</a></li>\n";
		if ( file_exists("inc/sidebar_". $key .".php") ) {
			include("inc/sidebar_". $key .".php");
		}
	} else {
		echo '<li class="sidebar-item"><a  class="sidebar-lien" href="?pg='. $key .'">'. $value ."</a></li>\n";
	}
	} //check_acl can_view_$pageid_racine
}
?>
		</ul>
	</div>
	<div id="Panneau-principal">
		<div id="topnavbar">
			<span class="topnavbar-item">
				<!--<a class="topnavbar-lien" href="?pg=<?php echo "{$pageid_racine}_edit"; ?>"><img id="BtnAdd" class="btn-img mask" src="ico/add.png" title="Nouveau" /></a>-->
				<a class="topnavbar-lien" ><img id="BtnSave" class="btn-img mask" src="ico/save.png" title="Enregistrer" onclick="submitForm();" /></a>
				&nbsp;
				<a class="topnavbar-lien"><img id="BtnDis" class="btn-img mask" src="ico/user-del.png" title="Désactiver les comptes séléctionnés" onclick="updateAction('bulkDisable');" /></a>
				<a class="topnavbar-lien"><img id="BtnDel" class="btn-img mask" src="ico/delete.png" title="Supprimer la selection" onclick="updateAction('bulkDelete');" /></a>
				&nbsp;
				<!--<a class="topnavbar-lien"><img id="BtnAdd" class="btn-img" src="ico/user-add.png" title="Ajouter/supprimer la selection du groupe" onclick="updateAction('bulkAddToGroup');" /></a>-->
				<select id="BtnGrps" name="bulkgrp" class="mask"  onchange="updateAction('bulkAddToGroup', this.value);">
					<option value="nogroup">Ajouter/supprimer la selection du groupe</option>
<?php
// liste les groupes dans le menu de la topnavbar
if (check_acl( "can_edit_usergroups" )) {
	$allgrp = $ldap->get_usergroups('*', true);
	foreach ($allgrp as $grp) {
		if (check_acl_group( $grp['gidnumber'] )) {
			$desc = (empty($grp['description'])) ? $grp['cn'] : "{$grp['description']} ({$grp['cn']})";
			echo '<option value="'.$grp['cn'].'">'.$desc."</option>\n";
		}
	}
} //check_acl can_edit_usergroups
?>
				</select>
			</span>
			<span>
				<span class="topnavbar-item mask"><input type="text" id="q" placeholder="Rechercher" /></span>
				<span class="topnavbar-item"><a class="topnavbar-lien" href="?pg=users_edit&uid=<?php echo $_SESSION['user_id']; ?>"><?php echo $_SESSION['user_name']; ?></a></span>
				<span class="topnavbar-item"><a class="topnavbar-lien" href="?logout"><img class="btn-img" src="ico/logout.png" title="Déconnexion" /></a></span>
			</span>
		</div>
		<div id="contenu">
<?php
if ( file_exists("inc/page_". $pageid .".php") ) {
	include("inc/page_". $pageid .".php");
} else {
	include("inc/page_dashboard.php");
}
?>
		</div>
	</div>
</div>
</body>
</html>
<?php
} else { // is connecter
    // affiche la page de connexion
?>
<body>
<div id="FullScreenCentered">
	<div id="login-box" <?php if (isset($is_auth)) {echo 'class="error"';} ?>>
		<img id="login-logo" src="logo.png" /><br>
		<p>Veuillez vous identifier.</p><br>
		<?php
if (isset($is_auth)) {
	echo '<div class="msg-error">Erreur: authentification invalide.</div>';
}
if ( isset( $_SESSION['failed_count'] ) && $_SESSION['failed_count'] >= 3 ) {
	echo '<div class="msg-error">Erreur: Nombre de tentatives dépassées.</div>';
} else {
		?>
		<form action="" method="post">
			<input type="text" name="username" placeholder="Identifiant" /><br>
			<input type="password" name="password" placeholder="Mot de passe" /><br>
			<input type="submit" value="Se connecter"/>
		</form><?php
} // else fail count
?>
	</div>
</div>
</body>
</html>
<?php
} // else is connecter