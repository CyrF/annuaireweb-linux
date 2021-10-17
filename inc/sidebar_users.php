<ul class='sidebar-submenu'>
	<?php if (check_acl( 'can_view_users')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=users">Tous les utilisateurs</a>
	</li>
	<?php } if (check_acl( 'can_create_user')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=users_edit">Nouvel utilisateur</a>
	</li>
	<?php } if (check_acl( 'can_import')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=users_import">Importer fichier CSV</a>
	</li>
	<?php } if (check_acl( 'can_export')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=users_import&act=export">Exporter comptes</a>
	</li>
	<?php } ?>
</ul>