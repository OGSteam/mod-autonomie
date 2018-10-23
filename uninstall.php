<?php

/**
* uninstall.php 
* @package autonomie
* @author Mirtador
* @update xaviernuma - 2015
* @link http://www.ogsteam.fr
*/


if (!defined('IN_SPYOGAME')) die('Hacking attempt');

global $db;

$mod_uninstall_name = "autonomie";

uninstall_mod($mod_uninstall_name,$mod_uninstall_table);
