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

if (check_acl( 'can_view_groups')) {

if ( isset( $_GET['act'] )) {
	if ( $_GET['act'] == "delete" and check_acl( 'can_delete_group' )) { //supprime le groupe demandé
		show_msg($ldap->supprimer_entree( $_GET['cn'], true ), 'Le groupe '. $_GET['cn'] .' a été supprimé');
	}
	if (( $_GET['act'] == "bulkDelete" & !empty( $_POST['bulk'] )) and check_acl( 'can_delete_group' )) { //supprime les groupes demandés
		foreach ($_POST['bulk'] as $entry) {
			show_msg($ldap->supprimer_entree( $entry, true ), 'Le groupe '. $_GET['cn'] .' a été supprimé');
		}
	}
}

if ( isset( $_GET['type'] )) {
	$arr = array(
		'sys'	=> 'Groupes locaux système',
		'user'	=> 'Groupes d\'utilisateurs',
		'priv'	=> 'Groupes privés');
	echo '<h3>Liste des '. $arr[$_GET['type']] . '</h3>';
} else {
	echo '<h3>Tous les groupes</h3>';
}

echo '<form action="?pg=groups" method="post" id="bulkform"><table>';
echo '	<tr>';
echo '		<th><input type="checkbox" onclick="checkAll();" /></th>';
echo '		<th>Groupe</th>';
echo '		<th>Description</th>';
echo '		<th>Membres</th>';
echo '		<th></th>';
echo '	</tr>';

$list = $ldap->get_usergroups( '*', true );

foreach ($list as $entry) {
	// filtre que les groupes demandés
	$type = (isset( $_GET['type'])) ? $_GET['type'] : '*';
	if ( check_acl_group( $entry['gidnumber'], $type )) {
		$nbmembres = ( in_array( 'nobody', $entry['memberuid'] ))? $entry['memberuid']['count'] - 1 : $entry['memberuid']['count'];
		echo '	<tr>';
		echo '		<td><input type="checkbox" name="bulk[]" value="' . $entry['cn'] . '" /></td>';
		echo '		<td><a href=?pg=groups_edit&cn=' . $entry['cn'] . '>
			<img class="btn-img" src="ico/group-config.png" title="Editer le groupe" />&nbsp;' . $entry['cn'] . '</a></td>';
		echo '		<td>' . $entry['description'] . '</td>';
		echo '		<td>
				<a href=?pg=users&cn=' . $entry['cn'] . '><img class="btn-img" src="ico/user-group.png" title="Voir les membres" /></a>
				<a href=?not&pg=users&cn=' . $entry['cn'] . '><img class="btn-img" src="ico/user-group-del.png" title="Voir les utilisateurs non membres" /></a>
				&nbsp;' . $nbmembres . ' membres</td>';
		echo '		<td>
				<a href=?pg=groups&act=delete&cn=' . $entry['cn'] . '><img class="btn-img" src="ico/delete.png" title="Supprimer le groupe" /></a>
			</td>';
		echo '	</tr>';
	} // filtre
}
} // check_acl can_view_groups
?>

</table>
	<!--<input type="button" value="Supprimer les groupes selectionnés" onclick="updateAction('bulkDelete');" /></form>-->

<script>
document.getElementById('BtnDel').style.display = "inline-block";
</script>