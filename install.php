<?php

/**
* install.php 
* @package autonomie
* @author Mirtador
* @link http://www.ogsteam.fr
*/

//Ce fichier installe le module d'autonomie

if (!defined('IN_SPYOGAME')) 
{
	die("Hacking attempt");
}

global $db;

$mod_folder = "autonomie";
install_mod($mod_folder);

?>