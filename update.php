<?php

/**
* update.php 
* @package autonomie
* @author Mirtador
* @link http://www.ogsteam.fr
*/

if (!defined('IN_SPYOGAME')) 
{
	die("Hacking attempt");
}

global $db;

$mod_folder = "autonomie";
$mod_name = "autonomie";
update_mod($mod_folder,$mod_name);

?>