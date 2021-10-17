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

//if (!defined('IN_AnnuaireLinux')) { exit; }

if (check_acl( array( 'can_edit_group', 'can_edit_usergroups', 'can_create_group' ))) {

if ( isset( $_GET['cn'] )) {
	// édite l'utilisateur demandé
	$entry = $ldap->get_usergroups('*', true, $_GET['cn']);
} else {
	// sinon édite un nouvel groupe
	$entry[0]['description']	= '';
	$entry[0]['cn']	= '';
	$entry[0]['gidnumber']	= $ldap->get_last_groupid()-1;
	$entry[0]['memberuid']	= array();
}


// insere les donnees dans l'annuaire
if ( isset( $_POST['cn'] )) {
	$cn = (empty($_POST['cn_backup'])) ? $_POST['cn'] : $_POST['cn_backup'];
	$dn = $ldap->get_cheminldap($cn, "cn");
	//echo "uid utilisé: $cn; dn trouvé: $dn <br />";
	if ($dn != false) {
		// si le groupe est trouvé, c'est qui existe: on met a jour.
		$entry = $ldap->get_usergroups('*', true, $cn);

		if (!empty($_POST['cn']) and check_acl( 'can_edit_group' )) {		// mise a jour du nom
			show_msg( $ldap->modifier_utilisateur( $dn, 'cn', $_POST['cn'] ), 'Le nom du groupe '. $_POST['cn'] .' a été modifié' );
		}
		if (!empty($_POST['description']) and check_acl( 'can_edit_group' )) {	// mise a jour de la description
			show_msg( $ldap->modifier_utilisateur( $dn, 'description', $_POST['description'] ), 'La description du groupe '. $_POST['cn'] .' a été modifié' );
		}
		if (check_acl( 'can_edit_usergroups' )) {	// mise a jour des membres
			foreach( $entry[0]['memberuid'] as $k => $member ) { // parcours les membres exstants
				if ( $k != 'count' and $member != 'nobody' and !in_array( $member, $_POST['members'] )) { // si il n'est plus dans la liste:
					show_msg($ldap->modifier_groupe( $member, $cn ), 'L\'utilisateur '. $member .' a été supprimé du groupe '. $cn);
				}
			}
		}
		$entry = $ldap->get_usergroups('*', true, $cn);
	} else {
		// sinon c'est une creation
		if (!empty($_POST['cn']) and check_acl( 'can_create_group' )) {
			show_msg( $ldap->ajouter_groupe( $_POST['cn'], $_POST['gidnumber'], 'nobody', $_POST['description'] ), 'Le groupe '. $_POST['cn'] .' a été créé' );
		}
	}
}

/**
 * Affichage du formulaire.
 */

echo '<div class="edit"><form id="form-edit" action="?pg=groups_edit" method="post">';
echo '<label for="cn">Nom du groupe : </label>
	<input type="text" id="cn" name="cn" placeholder="' . $entry[0]['cn'] . '" />
	<input type="hidden" id="cn_backup" name="cn_backup" value="' . $entry[0]['cn'] . '" /><br />';
echo '<label for="description">Description : </label>
	<input type="text" id="description" name="description" placeholder="' . $entry[0]['description'] . '" /><br />';
echo '<label for="gidnumber">Numéro de groupe GID:</label>
	<input type="text" name="gidnumber" id="gidnumber" value="' . $entry[0]['gidnumber'] . '" />0-1000 réservé au système, 1000+ comptes locaux, 2000+ comptes LDAP.<br />';

if (check_acl( 'can_edit_usergroups' )) {
echo '<div class="checkbox-list"><br /><label for="members[]">membres : </label> <fieldset>';
foreach ($entry[0]['memberuid'] as $membre) {
	$name = $ldap->get_users_info( $membre )['cn'];
	if (!$name == '') {
	echo '<input type="checkbox" name="members[]" value="'. $membre .'" checked="checked" >'. $name ."<br />\n";
	}
}
echo '</fieldset><br /></div>';
} // check_acl can_edit_usergroups

echo '</div>';
//echo '<input type="submit" value="Enregistrer" /></form>';

} //check_acl edit*
?>
<script>
document.getElementById('BtnSave').style.display = "inline-block";
</script>