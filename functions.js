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
 * Genere un identifiant type pnom
 *
 *	@param string cn	le nom complet
 *
 *	@return null
 */
function guess_uid(cn) {
	var uid_input = document.getElementById('uid');
	var pwd = document.getElementById("pwd");
	if ( uid_input.placeholder == "" ) {
		var pnom = cn.toLowerCase().split(" ");
		var nom = (pnom[1] == undefined)?pnom[0].substr(1):pnom[1];
		uid_input.value = pnom[0].substr(0,1) + nom;
		if ( pwd.value == "" ) {
			pwd.value = generate();
			Toggle();
		}
	}
}

/**
 * bascule le champ pasword en texte
 *
 *	@return null
 */
function Toggle() {
	var pwd = document.getElementById("pwd");
	if (pwd.type === "password") {
		pwd.type = "text";
	}
	else {
		pwd.type = "password";
	}
}

/**
 * coche le groupe users quand on le selectionne en groupe principal
 *
 *	@param string grprincipal	le vleur du select
 *
 *	@return null
 */
function cocher_groupe_users(grprincipal) {
	var grp_users = document.getElementById("grp_100");
	if (grprincipal === "100") {
		grp_users.checked = true;
	}
}

/**
 * Genere un mot de passe de 8 char.
 *
 *	@return null
 */
var generate = (
  length = 8,
  wishlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
) => Array(length)
      .fill('') // fill an empty will reduce memory usage
      .map(() => wishlist[Math.floor(crypto.getRandomValues(new Uint32Array(1))[0] / (0xffffffff + 1) * wishlist.length)])
      .join('');

function generate_pwd(input, length) {
	wishlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	if (input.value == '') {
	input.value = Array(length)
      .fill('') // fill an empty will reduce memory usage
      .map(() => wishlist[Math.floor(crypto.getRandomValues(new Uint32Array(1))[0] / (0xffffffff + 1) * wishlist.length)])
      .join('');
	}
}

/**
 * soumet le formulaire quand on clique sur l'icone disquette
 *
 *	@return null
 */
function submitForm() {
	document.getElementById('form-edit').submit();
}

/**
 * ajoute une action et soumet le formulaire
 *
 *	@param string action	l'action a faire
 *
 *	@return null
 */
function updateAction(action, group = '') {
	var bulkform = document.getElementById('bulkform');
	bulkform.action += '&act=' + action;
	if (group !== '') {
		var grpsupp = document.getElementById('grpsupp');
		grpsupp.value = group;
	}
	bulkform.submit();
}

/**
 * coche toutes les cases dans le tableau
 *
 *	@return null
 */
function checkAll() {
	var checkboxs = document.getElementsByName('bulk[]');
	for(i = 0;i < checkboxs.length; i++) {
		if (checkboxs[i].checked === true) {
			checkboxs[i].checked = false;
		} else {
			checkboxs[i].checked = true;
		}
	}
}

/**
 * cache les messages d'erreur et d'infos
 *
 *	@return null
 */
function cacherMessages() {
	var msg = document.querySelectorAll('.msg-info, .msg-error');
	if (msg.length !== 0) {
		for(i = 0;i < msg.length; i++) {
			msg[i].style.display = "none";
		}
	}
}

var intervalID = setTimeout(cacherMessages, 6000);
