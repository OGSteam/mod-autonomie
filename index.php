<?php

/*
* filename : index.php
* desc. : Fichier principal
* created : 06/11/2006 Mirtador
* @package autonomie
* @author Mirtador
* @update xaviernuma - 2015
* @link http://www.ogsteam.fr
*/

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!defined('IN_SPYOGAME')) 
{
	die("Hacking attempt");
}

require_once("views/page_header.php");
require_once("fonctions.php");


//On récupère la version actuel du mod et son nom
$query = "SELECT `title`, `version` FROM `".TABLE_MOD."` WHERE `action`='".$pub_action."' AND `active`='1' LIMIT 1";
$result = $db->sql_query($query);
if (!$db->sql_numrows($result)) die('Hacking attempt');
list($mod,$version) = $db->sql_fetch_row($result);

//Si la page a afficher n'est pas définie, on affiche la première
if (!isset($pub_page))
	$pub_page = 'autonomie';

//menu
$s_html = '';
$s_html .= '<table>';
$s_html .= '<tr>';
if ($pub_page <> "autonomie") 
{
	$s_html .= '<th class="c" width="150">';
	$s_html .= '<a href="index.php?action=autonomie&page=autonomie">Autonomie</a>';
	$s_html .= '</th>';
}
else
{
	$s_html .= '<th class="c" width="150">';
	$s_html .= 'Autonomie';
	$s_html .= '</th>';
}

if ($pub_page <> "historique") 
{
	$s_html .= '<th class="c" width="150">';
	$s_html .= '<a href="index.php?action=autonomie&page=historique">Historique des versions</a>';
	$s_html .= '</th>';
}
else 
{
	$s_html .= '<th class="c" width="150">';
	$s_html .= 'Historique des versions';
	$s_html .= '</th>';
}
$s_html .= '</tr>';
$s_html .= '</table>';

if ($pub_page == "autonomie")
{
	$s_html .= f_autonomie();
}


if ($pub_page == "historique")
{
	$s_html .= f_historique();
}

// Pied de page
$s_html_pied = '<div style="margin-top:20px;font-size:10px;width:400px;text-align:center;background-image:url(\'skin/OGSpy_skin/tableaux/th.png\');background-repeat:repeat;">'.$mod.' ('.$version.')';
$s_html_pied .= '<br>Développé par Mirtador 2006';
$s_html_pied .= '<br>Mise à jour par oXid_FoX, Shad, <a href="mailto:contact@epe-production.org?subject=autonomie">xaviernuma</a></div>';

$s_html .= $s_html_pied;

echo $s_html;

//Insertion du bas de page d'OGSpy
require_once("views/page_tail.php");

