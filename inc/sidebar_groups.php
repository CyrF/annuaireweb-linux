<ul class='sidebar-submenu'>
	<?php if (check_acl( 'can_view_groups')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=groups">Tous les groupes</a>
	</li>
	<?php } if (check_acl( 'can_view_groups_user')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=groups&type=user">Groupes d'utilisateurs</a>
	</li>
	<?php } if (check_acl( 'can_view_groups_priv')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=groups&type=priv">Groupes privés</a>
	</li>
	<?php } if (check_acl( 'can_view_groups_sys')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=groups&type=sys">Groupes système</a>
	</li>
	<?php } if (check_acl( 'can_create_group')) { ?>
	<li>
		<a class='sidebar-submenu-lien' href="?pg=groups_edit">Nouveau groupe</a>
	</li>
	<?php } ?>
</ul>