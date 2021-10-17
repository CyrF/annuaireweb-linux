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

if (check_acl( array( 'can_import', 'can_export' ))) {


if ( isset( $_GET['act'] )) {
	if ( $_GET['act'] == "import" and check_acl( 'can_import' ) ) { //import un user
		if(is_uploaded_file($_FILES['file']['tmp_name'])) {
			$row = 1;
			if (($handle = fopen($_FILES['file']['tmp_name'], "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$num = count($data);
					$row++;
					$name	= trim( $data[0] );
					$uid	= trim( $data[1] );
					$pwd	= trim( $data[2] );
					$grp0	= trim( $data[3] );
					$grps	= array_map( 'stripEspaces', explode( ',', $data[4] ));

					if ( $uid != 'identifiant' ) {
						// ajoute l'utilisateur
						show_msg($ldap->ajouter_utilisateur($name, $uid, $pwd), 'L\'utilisateur '. $name .' a été ajouté');
						$dn = $ldap->get_cheminldap($uid);
						// change le groupe par defaut
						if ($grp0 == 'users') {
							show_msg($ldap->modifier_utilisateur( $dn, 'gidNumber', 100 ), "Le groupe principal de $uid est users");
						}
						// cree les groupes et les attribut a l'utilisateur
						foreach ($grps as $g) {
							show_msg($ldap->ajouter_groupe($g, $ldap->get_last_groupid()-1, $uid, $g), 'Le groupe '. $g .' a été ajouté');
						}
						show_msg($ldap->modifier_groupe( $uid, $grps ), "Les groupes de l'utilisateur $uid ont été modifié");
						
						// affiche les infos pour l'impression
						echo "<div class='import-vignet'>
	<p class='vignet-name'>$name</p>
	<p class='vignet-id'><span class='vignet-label'>Identifiant:</span>$uid</p>
	<p class='vignet-pw'><span class='vignet-label'>Mot de passe:</span>$pwd</p>
	<p class='vignet-grp'>$grp0, ". implode(", ", $grps) ."</p>
</div>";
					}
				} // while
				fclose($handle);
			}
		}
	}
	if ( $_GET['act'] == "export" and check_acl( 'can_export' ) ) { //export les users
		$list = $ldap->get_users_info();
		echo '<pre>';
		echo "#$cipher_key\n";

		foreach ($list as $entry) {
			// ne pas afficher le gestionnaire, sauf si c'est lui qui est connecté.
			if ( $entry['uid'] != $cfg['gestion'] or $_SESSION['user_id'] == $cfg['gestion'] ) {
				echo '"'. $entry['cn'] .'",'. $entry['uid'] .',';
				echo dechiffrer($entry['pwd'], $cipher_key) . ',';
				echo $entry['gid'] . ',"' . implode(',', $entry['groups']) . '"' . "\n";
			}
		}
		echo '</pre>';
		exit();
	}
} else { // isset get

?>
<form enctype="multipart/form-data" action="?pg=users_import&act=import" method="POST">
  <p>Vous pouvez IMPORTER UNE LISTE issue d'un fichier au format CSV. </p>
  <p>Ordre des champs -> "nom","identifiant","motdepasse","grprincipal","grpsupp1,grpsupp12"</p>
  <input name="file" type="file" /><br />
  <input type="submit" value="Envoyez le fichier" /><br />
</form>

<br />

<p> Après l'import, la page affichera des vignettes (comme ci-dessous) remplies avec les infos reçues, pour les imprimer par exemple</p><br />
<div class="import-vignet">
<p class="vignet-name">Sir Winston Churchill</p>
<p class="vignet-id"><span class="vignet-label">Identifiant:</span>wchurchill</p>
<p class="vignet-pw"><span class="vignet-label">Mot de passe:</span>TheR0@ringL!0n</p>
<p class="vignet-grp">sudo, Group_D, sftpusers</p>
</div>
<?php
} //else isset get
} //check_acl can_import