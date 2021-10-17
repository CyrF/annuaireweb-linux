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

if ( isset( $_GET['act'] )) {
	if ( $_GET['act'] == "delete"  and check_acl( 'can_delete_user' )) { //supprime l'utilisateur demandé
		show_msg($ldap->modifier_groupe( $_GET['uid'], array() ), 'L\'utilisateur '. $entry .' a été supprimé des groupes');
		show_msg($ldap->supprimer_entree( $_GET['uid'] ), 'L\'utilisateur '. $_GET['uid'] .' a été supprimé');
	}
	if ( $_GET['act'] == "disable"  and check_acl( 'can_edit_user' )) { //désactive l'utilisateur demandé
		show_msg($ldap->modifier_utilisateur( $ldap->get_cheminldap($_GET['uid']), 'userPassword', bin2hex(random_bytes(12)) ), 'L\'utilisateur '. $_GET['uid'] .' a été désactivé');
	}
	if (( $_GET['act'] == "bulkDelete" & !empty($_POST['bulk']))  and check_acl( 'can_delete_user' )) { //supprime les utilisateurs demandés
		foreach ($_POST['bulk'] as $entry) {
			show_msg($ldap->modifier_groupe( $entry, array() ), 'L\'utilisateur '. $entry .' a été supprimé des groupes');
			show_msg($ldap->supprimer_entree( $entry ), 'L\'utilisateur '. $entry .' a été supprimé');
		}
	}
	if (( $_GET['act'] == "bulkDisable" & !empty($_POST['bulk']))  and check_acl( 'can_edit_user' )) { //desactive les utilisateurs demandés
		foreach ($_POST['bulk'] as $entry) {
			show_msg($ldap->modifier_utilisateur( $ldap->get_cheminldap($entry), 'userPassword', bin2hex(random_bytes(12)) ), 'L\'utilisateur '. $entry .' a été désactivé');
		}
	}
	if (( $_GET['act'] == "bulkAddToGroup" & !empty($_POST['bulk']))  and check_acl( 'can_edit_usergroups' )) { //supprime les utilisateurs demandés
		foreach ($_POST['bulk'] as $entry) {
			show_msg($ldap->modifier_groupe( $entry, $_POST['grpsupp'] ), 'L\'utilisateur '. $entry .' a été ajouté/supprimé du groupe '. $_POST['grpsupp']);
		}
	}
}

if (check_acl( 'can_view_users')) {

if ( isset( $_GET['cn'] )) {
	if ( isset( $_GET['not'] )) {
		echo '<h3>Tous les utilisateurs absents du groupe '. $_GET['cn'] . '</h3>';
	} else {
		echo '<h3>Utilisateurs membres du groupe '. $_GET['cn'] . '</h3>';
	}
} else {
	echo '<h3>Tous les utilisateurs</h3>';
}

echo '<form action="?pg=users" method="post" id="bulkform"><table>';
echo '	<tr>';
echo '		<th><input type="checkbox" onclick="checkAll();" /></th>';
//echo '		<th>Id</th>';
echo '		<th>Nom Complet</th>';
echo '		<th>Groupes</th>';
echo '		<th></th>';
echo '	</tr>';

$list = $ldap->get_users_info();

foreach ($list as $entry) {
	// filtre que les membres du groupe demandés, ou inverse le filtre.
	if ( !isset( $_GET['cn'])
		or (!isset( $_GET['not']) and in_array($_GET['cn'], $entry['groups']))
		or (isset( $_GET['not']) and !in_array($_GET['cn'], $entry['groups'])) ) {
			// ne pas afficher le gestionnaire, sauf si c'est lui qui est connecté.
			if ( $entry['uid'] != $cfg['gestion'] or $_SESSION['user_id'] == $cfg['gestion'] ) {
				echo '	<tr>';
				echo '		<td><input type="checkbox" name="bulk[]" value="' . $entry['uid'] . '" /></td>';
			//	echo '		<td>' . $entry['uidnumber'] . '</td>';
				echo '		<td><a href=?pg=users_edit&uid=' . $entry['uid'] . '>
					<img class="btn-img" src="ico/user-config.png" title="Editer l\'utilisateur" />&nbsp;' . $entry['cn'] . '</a></td>';
				echo '		<td>' . implode(",", $entry['groups']) . '</td>';
				echo '		<td>
						<a href=?pg=users&act=delete&uid=' . $entry['uid'] . '><img class="btn-img" src="ico/delete.png" title="Supprimer l\'utilisateur" /></a>
						<a href=?pg=users&act=disable&uid=' . $entry['uid'] . '><img class="btn-img" src="ico/user-del.png" title="Désactiver l\'utilisateur" /></a>
					</td>';
				echo '	</tr>';
			} // cacher gestionnaire
	} // filtre groupe
}

echo '</table>';
/*
	<input type="button" value="Supprimer les utilisateurs selectionnés" onclick="updateAction(\'bulkDelete\');" /><br />
	<input type="button" value="Ajouter/supprimer les utilisateurs selectionnés dans le groupe : " onclick="updateAction(\'bulkAddToGroup\');" />
	<select id="grpsupp" name="grpsupp">';

$allgrp = $ldap->get_usergroups('*', true);
foreach ($allgrp as $grp) {
	$desc = (empty($grp['description'])) ? $grp['cn'] : "{$grp['description']} ({$grp['cn']})";
	echo '<option value="'.$grp['cn'].'">'.$desc.'</option>';
}
echo '</select>';
*/
} // check_acl can_view_userlist
?>
	<input type="hidden" value="" name="grpsupp" id="grpsupp" /><br />

	</form>
<script>
document.getElementById('BtnDis').style.display = "inline-block";
document.getElementById('BtnDel').style.display = "inline-block";
document.getElementById('BtnGrps').style.display = "inline-block";
</script>