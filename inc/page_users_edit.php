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


if (check_acl( array( 'can_edit_user', 'can_edit_password', 'can_edit_usergroups', 'can_edit_useraccess', 'can_create_user' ))) {

if ( isset( $_GET['uid'] )) {
	// édite l'utilisateur demandé
	$entry = $ldap->get_users_info( $_GET['uid'] );
} else {
	// sinon édite un nouvel utilisateur
	$entry['cn']	= '';
	$entry['uid']	= '';
	$entry['gid']	= '';
	$entry['uidnumber']	= '';
	$entry['groups']	= array();
}
//var_dump($_POST);
// insere les donnees dans l'annuaire
if ( isset( $_POST['uid_backup'] )) {
	$uid = (empty($_POST['uid_backup'])) ? $_POST['uid'] : $_POST['uid_backup'];
	$dn = $ldap->get_cheminldap($uid);
	//echo "uid utilisé: $uid; dn trouvé: $dn <br />";
	if ($dn != false) {
		$entry = $ldap->get_users_info( $uid );
		// si l'utisateur est trouvé, c'est qui existe: on met a jour.

		if (!empty($_POST['cn']) and check_acl( 'can_edit_user' )) {
			// mise a jour du nom complet
			show_msg($ldap->modifier_utilisateur( $dn, 'cn', $_POST['cn'] ), 'Info: Le nom d\'utilisateur '. $_POST['cn'] .' a été modifié');
		}

		if (!empty($_POST['pwd']) and (check_acl( 'can_edit_user' ) or $uid == $_SESSION['user_id'])) {
			// mise a jour du mot de passe
			show_msg($ldap->modifier_utilisateur( $dn, 'userPassword', $_POST['pwd'] ), 'Le mot de passe de l\'utilisateur '. $_POST['cn'] .' a été modifié');
		}

		if (check_acl( array( 'can_edit_user', 'can_edit_usergroups' ))) {
			if ( $_POST['grprincipal'] != $entry['gid'] ) {
				// mise a jour du groupe principal
				show_msg($ldap->modifier_utilisateur( $dn, 'gidNumber', $_POST['grprincipal'] ), 'Le groupe principal de l\'utilisateur '. $_POST['cn'] .' a été modifié');
			}

			$post_grpsupp = (empty($_POST['grpsupp'])) ? array() : $_POST['grpsupp'];
			if ( !empty( array_diff( $entry['groups'], $post_grpsupp ))
				or !empty( array_diff( $post_grpsupp, $entry['groups'] )) ) {
					// mise a jour des groupes
					show_msg($ldap->modifier_groupe( $uid, $post_grpsupp ), 'Les groupes de l\'utilisateur '. $_POST['cn'] .' ont été modifiés');
			} else {/*
				echo "<br>post:";
				var_dump( $post_grpsupp );
				echo "<br>entry:";
				var_dump( $entry['groups'] );
				echo "<br>diff:";
				var_dump( array_diff($post_grpsupp, $entry['groups'] ));
				echo "<br>diffi:";
				var_dump( array_diff($entry['groups'], $post_grpsupp ));*/
			}
		} //check_acl can_edit_usergroups

		if (!empty($_POST['access']) and check_acl( 'can_edit_useraccess' )) {
			$useracl = $ldap->get_userAccessList( $uid );
			if ( !empty( array_diff( $_POST['access'], $useracl ))
				or !empty( array_diff( $useracl, $_POST['access'] )) ) {
					// mise a jour des droits d'acces";
					show_msg($ldap->attribuer_droit( $uid, $_POST['access'] ), 'Les autorisations de l\'utilisateur '. $_POST['cn'] .' ont été modifiés');
			}
		}

		$entry = $ldap->get_users_info( $uid );
	} else {
		// sinon c'est une creation
		if (!empty($_POST['uid']) and check_acl( 'can_create_user' )) {
			$result = $ldap->ajouter_utilisateur( $_POST['cn'], $_POST['uid'], $_POST['pwd'] );
			if ($result === true) {
				echo '<div class="msg-info">Info: L\'utilisateur '. $_POST['cn'] .' a été créé.</div>';
			} else {
				echo '<div class="msg-error">Erreur: '. $result .'</div>';
			}
		} // check_acl can_create_user
	}
}

echo '<div class="edit"><form id="form-edit" action="?pg=users_edit" method="post">
	<input type="hidden" id="uid_backup" name="uid_backup" value="' . $entry['uid'] . '" />'. "\n";

if (check_acl( 'can_edit_user' )) {
	echo '<label for="cn">Nom Complet : </label>
		<input type="text" id="cn" name="cn" placeholder="' . $entry['cn'] . '" onchange="guess_uid(this.value);" /><br /><br />'. "\n";
	echo '<label for="uid">Identifiant de connexion : </label>
		<input type="text" id="uid" name="uid" placeholder="' . $entry['uid'] . '" /><br />'. "\n";
} //check_acl can_edit_user

if ((check_acl( 'can_edit_password' ) and $entry['uid'] == $_SESSION['user_id']) or check_acl( 'can_edit_user' )) {
	echo '<label for="pwd">Mot de passe :</label>
		<input type="password" name="pwd" id="pwd" placeholder="Chiffré sur le serveur..." />
		<input type="checkbox" onclick="Toggle()">Afficher le mdp<br /><br />'. "\n";
} //check_acl can_edit_password

if (check_acl( 'can_edit_usergroups' )) {
	$select_users=($entry['gid'] == 100) ? "selected" : "notselect";
	$select_private=($entry['gid'] == $entry['uidnumber']) ? "selected" : "notselect";
	echo '<label for="grprincipal">Groupe principal : </label>
		<select id="grprincipal" name="grprincipal" onchange="cocher_groupe_users(this.value);" >
			<option value="100" ' . $select_users . '>users (Commun aux utilisateurs)</option>
			<option value="' . $entry['uidnumber']. '" ' . $select_private . ' >private_* (Propre à l\'utilisateur. Par défaut sur Debian.)</option>
		</select><br />'. "\n";

	$allgrp = $ldap->get_usergroups('*', true);

	echo '<div class="checkbox-list"><br /><label for="grpsupp[]">Groupes additionnels : </label> <fieldset>'. "\n";
	foreach ($allgrp as $grp) {
		if (($grp['gidnumber'] <= 1000 and check_acl( 'can_view_groups_sys' ))
				or ($grp['gidnumber'] > 1000 and $grp['gidnumber'] <= 2000 and check_acl( 'can_view_groups_user' ))
				or ($grp['gidnumber'] > 2000 and check_acl( 'can_view_groups_priv' ))) {
			$desc = (empty($grp['description'])) ? $grp['cn'] : "{$grp['description']} ({$grp['cn']})";
			if (in_array($grp['cn'], $entry['groups'])) {
				echo '<input id="grp_'. $grp['gidnumber'] .'" type="checkbox" name="grpsupp[]" value="'. $grp['cn'] .'" checked="checked" >'. $desc ."<br />\n";
			} else {
				echo '<input id="grp_'. $grp['gidnumber'] .'" type="checkbox" name="grpsupp[]" value="'. $grp['cn'] .'" >'. $desc ."<br />\n";
			}
		} else {
			if (in_array($grp['cn'], $entry['groups'])) {
				echo '<input id="grp_'. $grp['gidnumber'] .'" type="hidden" name="grpsupp[]" value="'. $grp['cn'] .'" >' ."\n";
			}
		}
	}
	echo '</fieldset><br /></div><br />'. "\n";
} // check_acl can_edit_usergroups

if (check_acl( 'can_edit_useraccess' )) {
	$allacl = $ldap->get_userAccessList( '*', true );

	echo '<div class="checkbox-list"><br /><label for="access[]">Droits d\'accès dans l\'annuaire : </label> <fieldset>'. "\n";
	foreach ($allacl as $acl) {
		$desc = (empty($acl['description'])) ? $acl['cn'] : "{$acl['description']} ({$acl['cn']})";
		if (in_array($acl['cn'], $ldap->get_userAccessList( $entry['uid'] ))) {
			echo '<input id="acl_'. $acl['cn'] .'" type="checkbox" name="access[]" value="'. $acl['cn'] .'" checked="checked" >'. $desc ."<br />\n";
		} else {
			echo '<input id="acl_'. $acl['cn'] .'" type="checkbox" name="access[]" value="'. $acl['cn'] .'" >'. $desc ."<br />\n";
		}
	}
	echo '</fieldset><br /></div><br />';
} //check_acl can_edit_useraccess

//echo '<input type="submit" value="Enregistrer" />
echo '</form>';
echo '</div>';
} //check_acl edit*
?>

<script>
document.getElementById('BtnSave').style.display = "inline-block";
</script>