<!doctype html>
<html lang="fr">
<!-- 
Programme presque ok
/!\ ATTENTION : il ne faut pas que la zone 100date soit vide, sinon elle est zappée par le programme ! Mettre n'importe quoi, tant que ce n'est pas de la forme [0-9]{2}[0-9X]{2}, c'est remplacé par des blancs

Dictionnaire français/anglais :
	Label = Leader
	Répertoire = Directory
	Étiquette = Tag
	Indicateurs = Indicators
Caractères spéciaux :
	Chr(31) (1F en hexadécimal) = "US" (Unit Separator) = "$" → code de sous-zone → au début de chaque sous-zone UNM
	Chr(30) (1E en hexadécimal) = "RS" (Record Separator) → fin de zone UNM → à la fin du répertoire et de chaque zone UNM
	Chr(29) (1D en hexadécimal) = "GS" (Group Separator) → à la fin de chaque notice UNM
	Chr(28) (1C en hexadécimal) = "FS" (File Separator) → fin de fichier iso2709
(cf http://ascii-table.com/ascii.php)
 -->

	<?php
		// require_once('functions.php');
		$myIniFile = parse_ini_file ("config.ini", TRUE);
		function split_directory($directory){
			$splitted_directory = '';
			foreach(str_split($directory, 12) as $key => $value){
				$splitted_directory .= substr($value, 0, 3) . ' ' . substr($value, 3, 4) . ' ' . substr($value, 7, 5) . ' ; ';
			}
			$splitted_directory = rtrim($splitted_directory);
			$splitted_directory = rtrim($splitted_directory, ';');
			$splitted_directory = rtrim($splitted_directory);
			return $splitted_directory;
		}
		function length($str){
			// pour pouvoir changer facilement de fonction de mesure de longueur des strings
			if(isset($str)){
				$length = strlen($str); // https://www.php.net/manual/fr/function.strlen.php : strlen() retourne le nombres d'octets plutôt que le nombre de caractères dans une chaîne. 
				// $length = mb_strlen($str); // https://www.php.net/manual/fr/function.mb-strlen.php : Retourne le nombre de caractères dans la chaîne str, avec l'encodage encoding. Un caractère multi-octets est alors compté pour 1. 
				// $length = iconv_strlen($str); // https://www.php.net/manual/fr/function.iconv-strlen.php : À l'opposée de strlen(), la valeur de retour de iconv_strlen() est le nombre de caractères faisant partie de la séquence d'octets str, ce qui n'est pas toujours la même chose que la taille en octets de la chaîne de caractères. 
				return $length;
			}
		}
	?>
	<head>
		<title>CSV 2 ISO2709</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" type="text/css" href="style.css" media="screen" /> 
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fork-awesome@1.1.7/css/fork-awesome.min.css" integrity="sha256-gsmEoJAws/Kd3CjuOQzLie5Q3yshhvmo7YNtBG7aaEY=" crossorigin="anonymous">
		<script src="top.js"></script>
		<script>
			function display_hide_block(id){
				var id_bloc = id.replace('bouton', 'bloc') ;
				var div = document.getElementById(id_bloc);
				var bouton = document.getElementById(id);
				if (div.style.display == "none") {
					div.style.display = "block";
					bouton.innerHTML = "&nbsp;&ndash;&nbsp;";
				}
				else {
					div.style.display = "none";
					bouton.innerHTML = "&nbsp;+&nbsp;";
				}
			}
		</script>
		<?php // require_once('head.txt') ; ?>
	</head>
		<body>
			<a id="haut"></a>
			<h1>CSV<span style="font-size: smaller; color: red;">2</span>ISO2709</h1>
			<p>Ce programme, comme son nom l'indique, convertit un fichier csv en <a href="https://fr.wikipedia.org/wiki/ISO_2709" target="_blank">iso2709</a>.<br />&#9888; Il a été écrit pour un besoin spécifique et ne fonctionnera peut-être (probablement ?) pas dans un autre contexte, même en modifiant le fichier config.ini.</p>
			<form method="post" action="index.php" enctype="multipart/form-data">
				<input type="hidden" name="MAX_FILE_SIZE" value="2097152"> 
				<input type="file" name="nom_du_fichier"> 
				<input type="submit" name='ok' id='ok' value="Convertir en iso2709"> 
			</form><br />
			<!--<button class="bouton_bloc"><a href="directory_reader.php" target="_blank">directory_reader.php</a></button>-->
		<?php
		// print_r($_POST);
		if(isset($_POST['ok'])){
			// Initialisation des variables :
			$input=$_FILES['nom_du_fichier']['tmp_name'];
			// $output = $_FILES['nom_du_fichier']['name'] . '.mrc';
			$output = pathinfo($_FILES['nom_du_fichier']['name'], PATHINFO_FILENAME) . '.mrc';
			$isoFileData = '';
			$csvData = '';
			$leader = '';
			$directory = '';
			$recordData = '';
			$record = '';
			$position = 0;
			$currentTag = '';
			$currentIndicators = '';
			$currentSubTag = '';
			$currentData = '';
			$currentSubData = '';
			$previousTag = '';
			$previousSubTag = '';
			$previousData = '';
			$previousLength = '';
			$previousPreviousLength = '';
			$newTag_bool = FALSE;
			//
			$fp = fopen ($output, 'w');
			$wholeTextFile = explode("\n", file_get_contents($input));
			$totalLinesInFile = sizeOf($wholeTextFile) ;
			$separator = $myIniFile["CSV"]["separator"];
			$columns = $myIniFile["CSV"]["column"];
			$columnsCount = count($columns);
			
			echo '<h2><button id="bouton_parametres" onclick="display_hide_block(this.id)" class="bouton_bloc" title="Cliquer ici pour afficher ou masquer les paramètres définis dans le fichier config.ini">&nbsp;+&nbsp;</button>&nbsp;Paramètres (config.ini)</h2>';
			echo '<p>Fichier en entrée : '.$_FILES['nom_du_fichier']['name'] . ' (fichier temporaire : '. $input.')</p>';
			echo '<div id="bloc_parametres" style="display:none;">';
			echo '<p>Fichier en sortie : <a href="'.$output.'"><button style="background-color: #d9d9d9; border: 1px solid black; border-radius : 10px; box-shadow: 3px 3px 5px #666666;">'.$output.'</button></a><br />Séparateur : '.$separator.'<br />'.$columnsCount.' colonnes : <pre>';
			print_r($columns);
			echo '</pre></p>';
			echo '</div>';
			echo '<h2><button id="bouton_donnees" onclick="display_hide_block(this.id)" class="bouton_bloc" title="Cliquer ici pour afficher ou masquer les données du fichier csv">&nbsp;+&nbsp;</button>&nbsp;Données en entrée</h2>';
			echo '<div id="bloc_donnees" style="display:none;">';
			// Début de la vérification
			echo '<pre>';
			for ($i = 1; $i < $totalLinesInFile ; $i++) {
			   echo $i . " : " . $wholeTextFile[$i];
			}
			echo '</pre></div>';
			echo '<h2><button id="bouton_traitement" onclick="display_hide_block(this.id)" class="bouton_bloc" title="Cliquer ici pour afficher ou masquer les différentes notices au format iso2709">&nbsp;+&nbsp;</button>&nbsp;Traitement</h2>';
			echo '<div id="bloc_traitement" style="display:none;">';
			// Fin de la vérification
			// Analyse du csv en entrée :
			for ($i = 1; $i < $totalLinesInFile ; $i++) {
				// Traitement de la ligne i du csv :
				echo '<h3>Notice ' . $i . '</h3>';
				$leader = '';
				$directory = '';
				$recordData = '';
				$record = '';
				$position = 0;
				$currentTag = '';
				$currentIndicators = '';
				$currentSubTag = '';
				$currentData = '';
				$currentSubData = '';
				$previousTag = '';
				$previousSubTag = '';
				$previousData = '';
				$newTag_bool = FALSE;
				$line = $wholeTextFile[$i];
				if($line == ''){continue;}
				// on scinde la ligne au caractère point-virgule (ou tout autre séparateur défini dans config.ini) :
				$line_array=explode($separator, $line);
				// Analyse de la ligne i, colonne par colonne :
				for ($j = 0; $j <= $columnsCount ; $j++) {
					$newTag_bool = FALSE;
					if($j < $columnsCount){
						$csvData = $line_array[$j];
					}
					if($j < $columnsCount && (!isset($csvData) || $csvData == '')){
						continue;
					}
					// Traitement de la colonne j de la ligne i du csv :
					if($j < $columnsCount && $csvData == ''){
						continue;
					}
					// on transfère le contenu de $currentData dans $previousData :
					$previousData = $currentData;
					// puis on remet à zéro $currentData :
					$currentSubData = '';
					// on transfère le contenu de $currentTag dans $previousTag :
					$previousTag = $currentTag;
					
					if($j < $columnsCount){
						// puis on récupère la nouvelle valeur de $currentTag :
						$currentTag = explode('*', $columns[$j])[0];
						$currentSubTag = str_replace('$', chr(31), explode('*', $columns[$j])[2]);
						$currentIndicators = explode('*', $columns[$j])[1];
						// Cas particulier de la zone 200 : on modifie le 2ème indicateur en fonction des articles initiaux :
						if($currentTag == '200'){
							if(substr($csvData, 0, 2) == 'L\''){
								$currentIndicators = '12';
							}
							elseif(substr($csvData, 0, 3) == 'Le ' || substr($csvData, 0, 3) == 'La ' || substr($csvData, 0, 3) == 'Un '){
								$currentIndicators = '13';
							}
							elseif(substr($csvData, 0, 3) == 'Les ' || substr($csvData, 0, 3) == 'Une ' || substr($csvData, 0, 3) == 'The '){
								$currentIndicators = '14';
							}
							else{
								$currentIndicators = '10';
							}
						}
					}
					if($currentTag != $previousTag){$newTag_bool = TRUE;}
					if($currentTag == $previousTag && strpos($previousData, $currentSubTag) !== FALSE){$newTag_bool = TRUE;}
					if($newTag_bool === TRUE){
						// on est dans une nouvelle zone Unimarc :
						$currentData = '';
						// 1) on finalise la zone précédente ($previousData) :
						if($j < $columnsCount || ($j == $columnsCount && $i == $totalLinesInFile-1)){
							$record .= $previousData;
						}
						// 1.1) on ajoute le caractère de fin de zone
						$previousData .= chr(30);
						// 1.2) on ajoute l'étiquette au répertoire (3 caractères) :
						if($previousTag != ''){
							if($j < $columnsCount || ($j == $columnsCount && $i == $totalLinesInFile-1)){$directory .= $previousTag;}
							// 1.3) on ajoute la longueur de la zone (4 caractères) :
							$previousPreviousLength = $previousLength;
							$previousLength = length(substr($previousData,1));
							if($j < $columnsCount || ($j == $columnsCount && $i == $totalLinesInFile-1)){$directory .= str_pad($previousLength, 4, '0', STR_PAD_LEFT);}
							// 1.4) on ajoute la position du 1er caractère (5 caractères) (NB : la 1ère zone commence à 0, la 2nde  à 2 + la longueur de la 1ère...)
							if($j > 1){
								$position = $position + $previousPreviousLength ;
							}
							else{
								$position = $position ;
							}
							if($j < $columnsCount || ($j == $columnsCount && $i == $totalLinesInFile-1)){$directory .= str_pad($position, 5, '0', STR_PAD_LEFT);}
						}
						if($j == $columnsCount){
							continue;
						}
						// 2) on traite la zone actuelle :
						// 2.1) on ajoute les indicateurs:
						$currentData = chr(30).$currentIndicators;
					}
					if($j == $columnsCount){
						continue;
					}
					// 2) on traite la zone actuelle :
					// Cas particulier de la zone 100 : on n'a que la date de publication, on complète la zone :
					if($currentTag == '100'){
						$date = date("Ymd") ;
						// $csvData = str_pad($csvData, 4, ' ', STR_PAD_LEFT);
						// echo '<script>alert("Notice '.$i.' : $csvdata = '.$csvData.'");</script>';
						if (preg_match('/^[0-9]{2}[0-9X]{2}$/', $csvData) == 0) {
							$csvData = '    ' ;
						}
						$currentSubData .= $currentSubTag . $date . 'd' . str_pad($csvData, 4, ' ', STR_PAD_LEFT) . '    m  y0frey        ba' ;
					}
					else{
						$currentSubData .= $currentSubTag . $csvData;
					}
					$currentData .= $currentSubData;
				}
				// fin de traitement de la ligne
				// on a traité toutes les colonnes de la ligne csv, on finalise la notice :
				// on ajoute le caractère de fin de zone UNM et le caractère de fin de notice :
				$record .= chr(30).chr(29);
				// on calcule la longeur totale de la notice (24 car du leader + longueur du répertoire + longueur des données) :
				$directoryLength = length($directory);
				$recordLength = length($record);
				$totalLength = 24+$directoryLength+$recordLength;
				// on construit le leader en y intégrant cette longueur et l'adresse de base des données (= position du premier caractère de la première zone de données par rapport au début de la notice, soit longueur du leader + longueur du répertoire + 1 pour le code de fin de zone à la fin du répertoire) :
				$leader = str_pad($totalLength, 5, '0', STR_PAD_LEFT) . 'nam0 22' . str_pad((24+$directoryLength+1), 5, '0', STR_PAD_LEFT) .' i 450 ';
				$record = $leader . $directory . $record;
				echo 'Notice : '. $record . '<br /><br />';
				echo 'Leader : '. substr($record, 0, 24) . '<br />';
				// echo 'Répertoire : '. explode(chr(30), substr($record, 24))[0] . '<br />';
				echo 'Répertoire (splitté) : '. split_directory(explode(chr(30), substr($record, 24))[0]) . '<br />';
				echo 'Début des zones : '. (strpos($record, chr(30))+1) . '<hr />';
				// on ajoute tout ça à la string à écrire dans le fichier .mrc :
				$isoFileData .= $record;
			}
			// fin de traitement du csv
			// on a traité toutes les lignes du csv, on finalise le fichier .mrc :
			// $isoFileData .= chr(28);
			$isoFileData = str_replace(PHP_EOL,"",$isoFileData); // enlève les éventuels retours à la ligne
			$isoFileData = str_replace(chr(13),"",$isoFileData); // enlève les éventuels retours à la ligne
			$isoFileData = str_replace(chr(10),"",$isoFileData); // enlève les éventuels retours à la ligne
			fwrite($fp, $isoFileData);
			fclose($fp);
			echo '<hr /></div>';
			echo '<h2><button id="bouton_resultat" onclick="display_hide_block(this.id)" class="bouton_bloc" title="Cliquer ici pour afficher ou masquer le contenu du fichier iso2709 résultant">&nbsp;+&nbsp;</button>&nbsp;Fichier final</h2>';
			echo '<div id="bloc_resultat" style="display:none;">';
			echo $isoFileData;
			echo '<br /><br /></div>';
			echo '<script>alert("Terminé !");</script>';
			unset($_POST["ok"]);
			unset($_POST);
			echo '<form action="'.$output.'"><input style="font-size: 1.3em; font-weight: bold; border-radius: 10px; box-shadow: 10px;" type="submit" value="Récupérer le fichier iso2709"></form><br />';
		}
		?>
		<a href="#haut"><button id="top" type="button" class="right" title="Haut de page" style="font-size: 2em;"> <i class="fa fa-arrow-up" aria-hidden="true"></i> </button></a>
		<hr /><p>Copyright 2020 Stanislas Jun. Ce programme est distribué sous les termes de la <a href="COPYING.txt" target="_blank">licence publique générale GNU</a>.</p>
	</body>
</html>
