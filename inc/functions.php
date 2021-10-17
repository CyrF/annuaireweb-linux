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

function sanitize($str) {
	return htmlspecialchars($str);
}


/**
 * Chiffre un mot de passe.
 *
 *	@param string $ciphertext	Le texte en clair.
 *	@param string $key			La clé de chiffrage.
 *
 *	@return string				Le texte chiffré, encodé en base64.
 */
function chiffrer($pwd, $uid, $key) {
	$cipher="AES-128-CBC";
	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
	$ciphertext_raw = openssl_encrypt($pwd, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	$hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
	return base64_encode( $iv.$hmac.$ciphertext_raw );
}

/**
 * dechiffre un mot de passe.
 *
 *	@param string $ciphertext	Le texte chiffré, encodé en base64.
 *	@param string $key			La clé de chiffrage.
 *
 *	@return string				Le texte en clair.
 */
function dechiffrer($ciphertext, $key) {
	$binary = base64_decode($ciphertext);
	$cipher="AES-128-CBC";
	$ivlen = openssl_cipher_iv_length($cipher);
	$iv = substr($binary, 0, $ivlen);
	$hmac = substr($binary, $ivlen, $sha2len=32);
	$ciphertext_raw = substr($binary, $ivlen+$sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	if (hash_equals($hmac, hash_hmac('sha256', $ciphertext_raw, $key, true))) {
		return $original_plaintext;
	} else {
		return "error encodage: ($ciphertext)";
	}
}

/**
 * Affiche un message d'erreur ou d'information sur la page.
 *
 *	@param bool $result		true, ou description de l'erreur.
 *	@param string $succesmsg	description de l'action reussie.
 *
 *	@return null
 */
function show_msg($result, $succesmsg='') {
	if ($result === true or $result === '') {
		echo "<div class='msg-info'>Info: $succesmsg.</div>";
	} else {
		echo "<div class='msg-error'>Erreur: $result.</div>";
	}
}

/**
 * Verifie si un utilisateur a les droits
 *
 *	@param mixed $acl	le nom de ou des acl a controler
 *
 *	@return bool true si l'utilisateur a les droits
 */
function check_acl( $acl ){
	if ($acl == 'can_edit_password') { return true; }
	if ( is_array( $acl )) {
		foreach( $acl as $a ) {
			if ( in_array( $a, $_SESSION['user_acl'] )) { return true; }
			if ($a == 'can_edit_password') { return true; }
		}
		return false;
	} else {
		return in_array( $acl, $_SESSION['user_acl'] );
	}
}

/**
 * Verifie si un utilisateur peux voir certains groupes
 *
 *	@param int $gid		le num du groupe a controler
 *
 *	@return bool true si l'utilisateur a les droits
 */
function check_acl_group( $gid, $type='*' ) {
	if (empty($gid)) {return false;}

	if (($gid <= 1000 and check_acl( 'can_view_groups_sys' ))
		and ($type == '*' or $type == 'sys')) {
			return true;
		}

	if (($gid > 1000 and $gid <= 2000 and check_acl( 'can_view_groups_user' ))
		and ($type == '*' or $type == 'user')) {
			return true;
		}

	if (($gid > 2000 and check_acl( 'can_view_groups_priv' ))
		and ($type == '*' or $type == 'priv')) {
			return true;
		}

	return false;
}

/**
 * Remplace les espaces par des tirets
 *
 *	@param string $s	la chaine a traiter
 *
 *	@return string
 */
function stripEspaces($s) {
	return str_replace(' ', '_', trim($s));
}

