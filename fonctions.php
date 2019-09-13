<?php

/**
* Autonomie.php
* @package autonomie
* @author Mirtador
* @update oXid_FoX
* @update xaviernuma - 2015
* @link http://www.ogsteam.fr
*/

// Test de securité
if (!defined('IN_SPYOGAME')) 
{
	die("Hacking attempt");
}

require_once("views/page_header.php");

//*Debut Fonction
// fonction calculant l'autonomie de production
function autonomie($user_building,$ress)
{
    global $user_data;
	$start = 101;
	$nb_planete = find_nb_planete_user($user_data["user_id"]);
	$result = array();//force l'interpretation de $result comme un array : retire des erreurs (silencieuses) dns le journal des PHP 5

	for ($i=$start ; $i<=$start+$nb_planete-1 ; $i++) 
		{
			// test planete existante
			if($user_building[$i][0] === TRUE)
				{
					if ($user_building[$i][$ress.'_hour'] > 0)
						$result[$i]=round(($user_building[$i]['H'.$ress.'_capacity'])/$user_building[$i][$ress.'_hour']);
					else							
						$result[$i] = '';
				}
		}
	return $result;
}

//fonction ressource lorsque que le plus petit silo est plein
function ressourcespetithangar($autonomieM,$autonomieC,$autonomieD,$user_building)
{
    global $user_data;
	$start = 101;
	$nb_planete = find_nb_planete_user($user_data["user_id"]);
	$result = array();//force l'interpretation de $result comme un array : retire des erreurs (silencieuses) dns le journal des PHP 5
	
	for ($i=$start ; $i<=$start+$nb_planete -1 ; $i++) 
		{
			// test planete existante
			if($user_building[$i][0] === TRUE)
				{
					// lorsque pas d'autonomie, il faut quand meme des valeurs pour comparer
					if (empty($autonomieM[$i])) $autonomieM[$i] = 9999999;
					if (empty($autonomieC[$i])) $autonomieC[$i] = 9999999;
					if (empty($autonomieD[$i])) $autonomieD[$i] = 9999999;

					if($autonomieM[$i]<=$autonomieC[$i] and $autonomieM[$i]<=$autonomieD[$i])
						{
							$temps= $autonomieM[$i];
						}
					elseif($autonomieC[$i]<=$autonomieM[$i] and $autonomieC[$i]<=$autonomieD[$i])
						{
							$temps= $autonomieC[$i];
						}
					elseif($autonomieD[$i]<=$autonomieM[$i] and $autonomieD[$i]<=$autonomieC[$i])
						{
							$temps= $autonomieD[$i];
						}
					$result[$i]=($user_building[$i]['M_hour']+$user_building[$i]['C_hour']+$user_building[$i]['D_hour'])*$temps;
				}
		}
	return $result;
}

//fonction ressource lorsque TOUS les silos sont pleins
//on considere que toutes les mines continuent a produire (meme si leur silo associe est deja plein)
function ressourcesgrandhangar($autonomieM,$autonomieC,$autonomieD,$user_building)
{
    global $user_data;
	$start = 101;
	$nb_planete = find_nb_planete_user($user_data["user_id"]);
	$result = array();//force l'interpretation de $result comme un array : retire des erreurs (silencieuses) dns le journal des PHP 5
	
	for ($i=$start ; $i<=$start+$nb_planete -1 ; $i++) 
		{
			// test planete existante
			if($user_building[$i][0] === TRUE)
				{
					// lorsque pas d'autonomie, il faut quand meme des valeurs pour comparer
					if (empty($autonomieM[$i])) $autonomieM[$i] = 1;
					if (empty($autonomieC[$i])) $autonomieC[$i] = 1;
					if (empty($autonomieD[$i])) $autonomieD[$i] = 1;
					
					$result[$i]=($user_building[$i]['M_hour']*$autonomieM[$i]+$user_building[$i]['C_hour']*$autonomieC[$i]+$user_building[$i]['D_hour']*$autonomieD[$i]);
				}
		}
	return $result;
}

// calcule le nombre de transporteurs necessaire pour une quantite de ressources donnees pour toutes les planetes
function transporteur($ressources,$transporteur,$user_building)
{
    global $user_data;
	$start = 101;
	$nb_planete = find_nb_planete_user($user_data["user_id"]);
	$result = array();//force l'interpretation de $result comme un array : retire des erreurs (silencieuses) dns le journal des PHP 5
	
	for ($i=$start ; $i<=$start+$nb_planete -1 ; $i++) 
		{
			$result[$i]=1;
			// test planète existante
			if($user_building[$i][0] === TRUE)
				{
					if($transporteur=="GT")
					$result[$i]=ceil($ressources[$i]/25000);

					if($transporteur=="PT")
					$result[$i]=ceil($ressources[$i]/5000);
				}
		}
	return $result;
}

// Recupere les informations sur les mines, hangars, production...
function mine_production_empire($user_id) 
{
	global $user_data, $db;
	
	$ta_user_empire = user_get_empire($user_data["user_id"]);
	$NRJ = $ta_user_empire["technology"]["NRJ"];
	
	$start=101;
	$nb_planete = find_nb_planete_user($user_data["user_id"]);
	// Recuperation des informations sur les mines
	$planet = array(false, 'planet_name' => '', 'coordinates' => '', 'temperature' => '', 'Sat' => '',
	'M' => 0, 'C' => 0, 'D' => 0, 'CES' => 0, 'CEF' => 0 ,
	'M_percentage' => 0, 'C_percentage' => 0, 'D_percentage' => 0, 'CES_percentage' => 100, 'CEF_percentage' => 100, 'Sat_percentage' => 100,
	'HM' => 0, 'HC' => 0, 'HD' => 0);

	$quet = $db->sql_query('SELECT planet_id, planet_name, coordinates, temperature_min, temperature_max, Sat, M, C, D, CES, CEF, M_percentage, C_percentage, D_percentage, CES_percentage, CEF_percentage, Sat_percentage, HM, HC, HD FROM '.TABLE_USER_BUILDING.' WHERE user_id = '.$user_id.' ORDER BY planet_id');

	$user_building = array_fill($start, $start+$nb_planete-1, $planet);
	while ($row = $db->sql_fetch_row($quet)) 
		{
			$user_building[$row['planet_id']] = $row;
			$user_building[$row['planet_id']][0] = TRUE;
		}
	//$user_empire = user_get_empire($user_data["user_id"]);

	// calcul des productions
	unset($metal_heure);
	unset($cristal_heure);
	unset($deut_heure);

	for ($i=$start ; $i<=$start+$nb_planete -1 ; $i++) 
		{
			// si la planete existe, on calcule la prod de ressources
			if ($user_building[$i][0] === TRUE) 
				{
					$M = $user_building[$i]['M'];
					$C = $user_building[$i]['C'];
					$D = $user_building[$i]['D'];
					$CES = $user_building[$i]['CES'];
					$CEF = $user_building[$i]['CEF'];
					$SAT = $user_building[$i]['Sat'];
					$M_per = $user_building[$i]['M_percentage'];
					$C_per = $user_building[$i]['C_percentage'];
					$D_per = $user_building[$i]['D_percentage'];
					$CES_per = $user_building[$i]['CES_percentage'];
					$CEF_per = $user_building[$i]['CEF_percentage'];
					$SAT_per = $user_building[$i]['Sat_percentage'];
					$temperature_min = $user_building[$i]['temperature_min'];
					$temperature_max = $user_building[$i]['temperature_max'];
					
					// $NRJ = $user_technology[$i]['NRJ'];
					$HM = $user_building[$i]['HM'];
					$HC = $user_building[$i]['HC'];
					$HD = $user_building[$i]['HD'];
		
					$production_CES = ( $CES_per / 100 ) * ( production ( "CES", $CES, $user_data['off_ingenieur'] ));
					$production_CEF = ( $CEF_per / 100 ) * ( production ("CEF", $CEF, $user_data['off_ingenieur'] ));
					$production_SAT = ( $SAT_per / 100 ) * ( production_sat ( $temperature_max, $user_data['off_ingenieur'] ) * $SAT );
		
					$prod_energie = $production_CES + $production_CEF + $production_SAT;
					
					$consommation_M = ( $M_per / 100 ) * ( consumption ( "M", $M ));
					$consommation_C = ( $C_per / 100 ) * ( consumption ( "C", $C ));
					$consommation_D = ( $D_per / 100 ) * ( consumption ( "D", $D ));
					$consommation_CEF = consumption("CEF", $CEF);
					$cons_energie = $consommation_M + $consommation_C + $consommation_D;
		
					if ($cons_energie == 0) $cons_energie = 1;
					$ratio = floor(($prod_energie/$cons_energie)*100)/100;
					if ($ratio > 1) $ratio = 1;

					// calcul de la production horaire
					$user_building[$i]['M_hour'] = $ratio * ( production ( "M", $M, $user_data['off_geologue'] ));
					$user_building[$i]['C_hour'] = $ratio * ( production ( "C", $C, $user_data['off_geologue'] ));
					$user_building[$i]['D_hour'] = ( $ratio * ( production ( "D", $D, $user_data['off_geologue'], $temperature_max, $NRJ ))) - $consommation_CEF ;
		
					// calcul des capacites par defaut
					$user_building[$i]['HM_capacity'] = depot_capacity($HM);
					$user_building[$i]['HC_capacity'] = depot_capacity($HC);
					$user_building[$i]['HD_capacity'] = depot_capacity($HD);
				} // fin du test d'existence de la planete
		}
	return $user_building;
}

//*Fin fonctions


function f_autonomie()
{

//*Debut calculs
require_once("includes/ogame.php");
require_once("includes/user.php");

global $user_data, $db;

$start = 101; 
$nb_planete = find_nb_planete_user($user_data["user_id"]);
// mines, hangars, productions, infos planetes
$user_building = mine_production_empire($user_data['user_id']);
//autonomie
$autonomieM=autonomie($user_building, 'M');
$autonomieC=autonomie($user_building, 'C');
$autonomieD=autonomie($user_building, 'D');

//ressources minimum
$ressourcesP=ressourcespetithangar($autonomieM,$autonomieC,$autonomieD,$user_building);
$ressourcesG=ressourcesgrandhangar($autonomieM,$autonomieC,$autonomieD,$user_building);

//transporteurs
$maxGT=transporteur($ressourcesG,"GT",$user_building);
$minGT=transporteur($ressourcesP,"GT",$user_building);
$maxPT=transporteur($ressourcesG,"PT",$user_building);
$minPT=transporteur($ressourcesP,"PT",$user_building);

//*Fin calculs

$s_html = "<table style=\"width:100%;\"><tr><th colspan='7'>Calculateur du temps d'autonomie de votre empire</th></tr>";
$s_html .= '<tr>';

//on initialise les variables
$planete_rouge=0;
$planete_jaune=0;
$planete_verte=0;
$somme_hangarM=0;
$somme_hangarC=0;
$somme_hangarD=0;
$maxempGT=0;
$minempGT=0;
$maxempPT=0;
$minempPT=0;
// la duree d'autonomie
$seuil_autonomie_courte=24;
$seuil_autonomie_longue=48;
for ($i=$start ; $i<=$start+$nb_planete -1 ; $i++) {
//	if ($coordinates[$i]!="&nbsp;"){
		// test planète existante
		if($user_building[$i][0] === TRUE){
		///////////////////////////////////////////////////////
		//*hangar a augmenter +autonomie planetaire
		if($autonomieD[$i]!= ''){
			if($autonomieM[$i]<=$autonomieC[$i] and $autonomieM[$i]<=$autonomieD[$i]){
				$petit_hangar= "Hangar de métal";
				$somme_hangarM= $somme_hangarM+1;
				$autoplanete=$autonomieM[$i];
			}
			elseif($autonomieC[$i]<=$autonomieM[$i] and $autonomieC[$i]<=$autonomieD[$i]){
				$petit_hangar= "Hangar de cristal";
				$somme_hangarC= $somme_hangarC+1;
				$autoplanete=$autonomieC[$i];
			}
			// on fait attention a la production de deuterium nulle (quand pas de synthetiseur)
			elseif($autonomieD[$i]<=$autonomieM[$i] and $autonomieD[$i]<=$autonomieC[$i]){
				$petit_hangar= "Réservoir de deutérium";
				$somme_hangarD= $somme_hangarD+1;
				$autoplanete=$autonomieD[$i];
			}
		}
		else{
			if($autonomieM[$i]<=$autonomieC[$i]){
				$petit_hangar= "Hangar de métal";
				$somme_hangarM= $somme_hangarM+1;
				$autoplanete=$autonomieM[$i];
			}
			elseif($autonomieC[$i]<=$autonomieM[$i]){
				$petit_hangar= "Hangar de cristal";
				$somme_hangarC= $somme_hangarC+1;
				$autoplanete=$autonomieC[$i];
			}
		}
		
		//*fin hangar a augmenter
		///////////////////////////////////////////////////////
		//*couleur hangar
		if ($autoplanete<=$seuil_autonomie_courte){
			$color="red";
			$planete_rouge=$planete_rouge+1;
		}
		elseif ($autoplanete<$seuil_autonomie_longue and $autoplanete>$seuil_autonomie_courte){
			$color="yellow";
			$planete_jaune=$planete_jaune+1;
		}
		else{
			$color="lime";
			$planete_verte=$planete_verte+1;
		}
		//*fin couleur hangar
		///////////////////////////////////////////////////////
		//*Transporteurs de l'empire
		$minempPT+=$minPT[$i];
		$maxempPT+=$maxPT[$i];
		$minempGT+=$minGT[$i];
		$maxempGT+=$maxGT[$i];
		//*fin Transporteurs de l'empire
		///////////////////////////////////////////////////////

		// Formatage des nombres.
		
		$M_hour = number_format($user_building[$i]['M_hour'], 0, ',', ' ');
		$C_hour = number_format($user_building[$i]['C_hour'], 0, ',', ' ');
		$D_hour = number_format($user_building[$i]['D_hour'], 0, ',', ' ');
		
		$HM_capacity = number_format($user_building[$i]['HM_capacity']/1000, 0, ',', ' ');
		$HC_capacity = number_format($user_building[$i]['HC_capacity']/1000, 0, ',', ' ');
		$HD_capacity = number_format($user_building[$i]['HD_capacity']/1000, 0, ',', ' ');
		
		$minimum_PT = number_format($minPT[$i], 0, ',', ' ');
		$maximum_PT = number_format($maxPT[$i], 0, ',', ' ');
		$minimum_GT = number_format($minGT[$i], 0, ',', ' ');
		$maximum_GT = number_format($maxGT[$i], 0, ',', ' ');
		
		$minimum_emp_PT = number_format($minempPT, 0, ',', ' ');
		$maximum_emp_PT = number_format($maxempPT, 0, ',', ' ');
		$minimum_emp_GT = number_format($minempGT, 0, ',', ' ');
		$maximum_emp_GT = number_format($maxempGT, 0, ',', ' ');
		
		//*Affichage des infos sur la planete
	
		$s_html .= '<tr><td rowspan="6" class="c" style="text-align: center;">' .$user_building[$i]['planet_name'].'<p style="margin-top: 0; margin-bottom: 0">'.$user_building[$i]['coordinates']. "</p></td>\n";
		$s_html .= '<td></td>';
		$s_html .= '<th>Niveau de la mine</th>';
		$s_html .= '<th>Production par heure</th>';
		$s_html .= '<th>Niveau des hangars</th>';
		$s_html .= '<th>Capacité de vos hangars</th>';
		$s_html .= '<th>Temps d\'autonomie de la planète</th>';
		$s_html .= "</tr>\n<tr>";
		$s_html .= '<th>Métal</th>';
		$s_html .= '<th>'.$user_building[$i]['M'].'</th>';
		$s_html .= '<th>'.$M_hour.'</th>';
		$s_html .= '<th>'.$user_building[$i]['HM'].'</th>';
		$s_html .= '<th>'.$HM_capacity.' K</th>';
		if ($autonomieM[$i]<72) {$s_html .= '<th>'.$autonomieM[$i].' Heures</th>';} else {$s_html .= '<th title="'.$autonomieM[$i].' Heures">'.round(($autonomieM[$i])/24,1).' Jours</th>';}
		$s_html .= "</tr>\n<tr>";
		$s_html .= '<th>Cristal</th>';
		$s_html .= '<th>'.$user_building[$i]['C'].'</th>';
		$s_html .= '<th>'.$C_hour.'</th>';
		$s_html .= '<th>'.$user_building[$i]['HC'].'</th>';
		$s_html .= '<th>'.$HC_capacity.' K</th>';
		if ($autonomieC[$i]<72) {$s_html .= '<th>'.$autonomieC[$i].' Heures</th>';} else {$s_html .= '<th title="'.$autonomieC[$i].' Heures">'.round(($autonomieC[$i])/24,1).' Jours</th>';}
		$s_html .= "</tr>\n<tr>";
		$s_html .= '<th>Deutérium</th>';
		$s_html .= '<th>'.$user_building[$i]['D'].'</th>';
		$s_html .= '<th>'.$D_hour.'</th>';
		$s_html .= '<th>'.$user_building[$i]['HD'].'</th>';
		$s_html .= '<th>'.$HD_capacity.' K</th>';
		// on fait attention à la production de deuterium nulle (quand pas de synthétiseur)
		if ($autonomieD[$i]=='') {$s_html .= '<th title="infini !">-</th>';} elseif ($autonomieD[$i]<72) {$s_html .= '<th>'.$autonomieD[$i].' Heures</th>';} else {$s_html .= '<th title="'.$autonomieD[$i].' Heures">'.round(($autonomieD[$i])/24,1).' Jours</th>';}
		$s_html .= "</tr>\n\n<tr>";
		$s_html .= '<td colspan="6"rowspan="2">';
			$s_html .= '<table>';
			$s_html .= '<tr><th colspan="6">Transport</th>';
			$s_html .= "</tr>\n<tr>";
			$s_html .= '<th colspan="1" rowspan="2" >Nb de transporteurs minimal pour vider la planète avant que le plus petit hangar soit plein (pour éviter les pertes)</th>';
			$s_html .= '<th>PT:</th>';
			$s_html .= '<th width="34">'.$minimum_PT."</th>\n";
			$s_html .= '<th colspan="1" rowspan="2">Nb de transporteurs minimal pour vider la planète avant que tous les hangars soient pleins</th>';
			$s_html .= '<th>PT:</th>';
			$s_html .= '<th width="34">'.$maximum_PT.'</th>';
			$s_html .= "</tr>\n<tr>";
			$s_html .= '<th>GT:</th>';
			$s_html .= '<th>'.$minimum_GT.'</th>';
			$s_html .= '<th>GT:</th>';
			$s_html .= '<th>'.$maximum_GT.'</th>';
			$s_html .= "</tr>\n\n<tr>";
			$s_html .= '</table>';
			$s_html .= '<table width="100%">';
			$s_html .= '<tr><td class="c" width="70%">Pour augmenter l\'autonomie de cette planète, vous devriez améliorer votre <span style="color:lime; ">'.$petit_hangar.'</span></td>';
			$s_html .= '<th width="30%"> Vous pouvez attendre <span style="color:'.$color.'">'.$autoplanete.' heures ('.round(($autoplanete)/24,1).' jours)</span> <br>avant de vider votre planète.</th></tr>';
			$s_html .= '</table>';
		$s_html .= '</td></tr>';
		$s_html .= "</tr>\n\n<tr>";//';
		//*Affichage des infos sur la planete
		///////////////////////////////////////////////////////
	}
}
$s_html .= '</table>';
///////////////////////////////////////////////////////
//*infos generales
$s_html .= '<table width="100%">';
$s_html .= '<tr><td class="c" colspan="6">Vue d\'ensemble</tr>';
$s_html .= '<tr><th colspan="6">Somme du nombre de transporteurs nécessaires à votre empire.</th></tr>';
$s_html .= '<tr>';
$s_html .= '<th rowspan="2">Pour vider toutes vos colonies avant que les plus petits hangars soit pleins</th>';
$s_html .= '<th>PT:</th><th>'.$minimum_emp_PT.'</th>';
$s_html .= '<th rowspan="2">Pour vider toutes vos colonies lorsque tous les hangars sont pleins</th>';
$s_html .= '<th>PT:</th><th>'.$maximum_emp_PT.'</th>';
$s_html .= "</tr>
<tr>";
$s_html .= '<th>GT:</th><th>'.$minimum_emp_GT.'</th>';
$s_html .= '<th>GT:</th><th>'.$maximum_emp_GT.'</th>';
$s_html .= "</tr>\n<tr>";
$s_html .= '<th colspan="6">Statistiques globales de votre empire.</th>';
$s_html .= "</tr>\n<tr>";
$s_html .= '<th colspan="2">Nombre de vos planètes ayant une autonomie nettement trop petite (- de '.$seuil_autonomie_courte.'h):</th><th>'.$planete_rouge.'</th>';
$s_html .= '<th colspan="2">Nombre de hangars de métal baissant l\'autonomie de sa planète:</th><th>'.$somme_hangarM.'</th>';
$s_html .= "</tr>\n<tr>";
$s_html .= '<th colspan="2">Nombre de vos planètes ayant une autonomie raisonnable (entre '.$seuil_autonomie_courte.' et '.$seuil_autonomie_longue.'h):</th><th>'.$planete_jaune.'</th>';
$s_html .= '<th colspan="2">Nombre de hangars de cristal baissant l\'autonomie de sa planète:</th><th>'.$somme_hangarC.'</th>';
$s_html .= "</tr>\n<tr>";
$s_html .= '<th colspan="2">Nombre de vos planètes ayant une très bonne autonomie (+ de '.$seuil_autonomie_longue.'h):</th><th>'.$planete_verte.'</th>';
$s_html .= '<th colspan="2">Nombre de réservoirs de deutérium baissant l\'autonomie de sa planète:</th><th>'.$somme_hangarD.'</th>';
$s_html .= "</tr>\n";
$s_html .= '</table>';
//*fin infos generales
///////////////////////////////////////////////////////
//*legende
$s_html .= '<table width="31%" align="right">';
$s_html .= '<tr>';
$s_html .= '<th width="100%" height="50%">';
$s_html .= '<span style="color: #00FF00; ">Vert = cette planète est autonome + de ' .$seuil_autonomie_longue. ' heures.</span><br>';
$s_html .= '<span style="color: #FFFF00; ">Jaune = attention, cette planète est autonome entre ' .$seuil_autonomie_courte.' et '.$seuil_autonomie_longue. ' heures.</span><br>';
$s_html .= '<span style="color: #FF0000; ">Rouge = cette planète risque fort de vous faire perdre des ressources, car son autonomie est inférieure à <?php $s_html .= $seuil_autonomie_courte; ?>h.</span>';
$s_html .= '</th>';
$s_html .= '</tr>';
$s_html .= '</table>';

return $s_html;

}


function f_historique()
{
	$s_html_historique = '';

	$s_html_historique .= '<table style="width:100%;">';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th colspan="2">Historique</th>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>Version</th>';
	$s_html_historique .= '<th>Modifications</th>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1</th>';
	$s_html_historique .= '<td><ul><li>Sortie du mod Autonomie.</li></ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.0a</th>';
	$s_html_historique .= '<td><ul><li>Réglage du bug lors du calcul du hangar a augmenter.</li></ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.0b</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Réglage du bug du menu, il n\'apparait plus en tant que "convertisseur".</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.1</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Ajout du code de couleur qui indique le risque de perte de ressources.</li>';
	$s_html_historique .= '<li>Ajout du calcul des transporteurs.</li>';
	$s_html_historique .= '<li>Ajout de la vue d\'ensemble de l\'empire côté hangars.</li>';
	$s_html_historique .= '<li>Amélioration de la page historique.</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.1b</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Correction de l\'orthographe.</li>';
	$s_html_historique .= '<li>Correction du numéro de version et du nom du mod (dans le bas de page).</li>';
	$s_html_historique .= '<li>Correction de la désinstallation.</li>';
	$s_html_historique .= '<li>Quelques améliorations du code.</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.2</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Correction de l\'orthographe dans le code</li>';
	$s_html_historique .= '<li>MAJ des champs PHPDoc ( @package, @author, @link )</li>';
	$s_html_historique .= '<li>Suppression des liens javascripts</li>';
	$s_html_historique .= '<li>Petite correction de la production (en rapport avec la production de base par planète)</li>';
	$s_html_historique .= '<li>Prise en compte de la température</li>';
	$s_html_historique .= '<li>Grosse correction des tableaux HTML</li>';
	$s_html_historique .= '<li>Tentative d\'aération du code html généré...</li>';
	$s_html_historique .= '<li>Changement des temps : jours à la place des heures pour les durées >= 72h</li>';
	$s_html_historique .= '<li>MAJ du numéro de version automatique (pour install.php et update.php)</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.3</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Correction d\'erreurs de type notice.</li>';
	$s_html_historique .= '<li>Détection du mod actif automatique.</li>';
	$s_html_historique .= '<li>Prise en compte de l\'absence de synthétiseur de deutérium.</li>';
	$s_html_historique .= '<li>Correction des totaux des PT/GT (changement de la méthode de calcul).</li>';
	$s_html_historique .= '<li>Ajout des "title" contenant le temps en heures (lorsque le temps est affiché en jours) pour la colonne "Temps d\'autonomie de la planète".</li>';
	$s_html_historique .= '<li>Arrondi supérieur pour le calcul des transporteurs.</li>';
	$s_html_historique .= '<li>Correction des transporteurs minimum lorsqu\'un synthétiseur n\'existe pas (niveau zéro).</li>';
	$s_html_historique .= '<li>Changement du seuil de "longue autonomie" (48h au lieu de 36h).</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<th>1.4</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Refonte totale des calculs de production (repris du mod OGSign & du mod Production) : prise en compte des pourcentages de production, des centrales à fusion qui consomment le deut, des ratios lorsqu\'il y a un manque d\'énergie...</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.5</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Mise en place d\'une fonction pour multiplier la production pour l\'univers 50.</li>';
	$s_html_historique .= '<li>note: Il faut modifier dans le code à la 18ème ligne le chiffre pour le faire. Dans la prochaine version on pourra le paramétrer dans l\'administration</li>';
	$s_html_historique .= '<li>Ajout du contenu du fichier info dans la signature (essentiellement pour nommer oXid_FoX qui a refait totalement les calculs)</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.5b</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Correction du bug qui faisait que l\'autonomie globale de la planète est recopiée depuis la planète précédente.</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.5.3</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Mise en conformité du numéro de version de mod.</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
	$s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.6.0 par Shad</th>';
	$s_html_historique .= '<td><ul>';
	$s_html_historique .= '<li>Mise en conformité des fonctions install, uninstall et update avec OGSpy 3.0.7.</li>';
	$s_html_historique .= '<li>Récupération des formules de calcul sur OGSpy</li>';
	$s_html_historique .= '<li>Prise en compte de la vitesse de OGSpy</li>';
	$s_html_historique .= '<li>Formatage des nombres</li>';
	$s_html_historique .= '<li>Ajustement de td qui couper en deux le nombre de vaisseaux nécessaire</li>';
	$s_html_historique .= '<li>Mise à jour du charset</li>';
	$s_html_historique .= '</ul></td>';
	$s_html_historique .= '</tr>';
    $s_html_historique .= '<tr>';
	$s_html_historique .= '<th>1.7.0 par xaviernuma</th>';
	$s_html_historique .= '<td>';
	$s_html_historique .= '<ul>';
	$s_html_historique .= '<li>Nettoyage du code</li>';
	$s_html_historique .= '<li>Correction de bug mineur</li>';
	$s_html_historique .= '<li>Mise à jour du charset UTF8</li>';
	$s_html_historique .= '</ul>';
	$s_html_historique .= '</td>';
	$s_html_historique .= '</tr>';
    $s_html_historique .= '<tr>';
    $s_html_historique .= '<th>1.7.2 par DarkNoon</th>';
    $s_html_historique .= '<td>';
    $s_html_historique .= '<ul>';
    $s_html_historique .= '<li>Mise à Jour du code. (Compatibilité OGSpy 3.3.2)</li>';
    $s_html_historique .= '</ul>';
    $s_html_historique .= '</td>';
    $s_html_historique .= '</tr>';
    $s_html_historique .= '<tr>';
    $s_html_historique .= '<th>1.7.4 par satepestage</th>';
    $s_html_historique .= '<td>';
    $s_html_historique .= '<ul>';
    $s_html_historique .= '<li>Mise à Jour du code. (Compatibilité OGSpy 3.3.6)</li>';
    $s_html_historique .= '</ul>';
    $s_html_historique .= '</td>';
    $s_html_historique .= '</tr>';
	$s_html_historique .= '</table>';
	
	return $s_html_historique;
}

