<?php
	function get_directory(){
		$dir=$_SERVER["PHP_SELF"];
		$arr=explode('/', $dir);
		array_pop($arr);
		$dir=implode('/',$arr);
		$dir=$_SERVER['HTTP_HOST'].$dir.'/';
		return $dir;
	}
	//
	function connect_to_database($base = 'prod') // connexion à la base postgresql, par défaut à la base de production
	// utilisation : connect_to_database();  connect_to_database('prod');   connect_to_database('test');   connect_to_database('backup');
		{
			global $database ;
			if($base == 'prod'){
				$database = 'DATABASE_PROD' ;
			}
			elseif($base == 'test'){
				$database = 'DATABASE_TEST' ;
			}
			elseif($base == 'backup'){
				$database = 'DATABASE_BACKUP_2018' ;
			}
			else{
				$database = 'DATABASE_PROD' ;
			}
			$myIniFile = parse_ini_file ("config.ini", TRUE);
			$host = $myIniFile[$database]["HOST"];
			$port = $myIniFile[$database]["PORT"];
			$dbname = $myIniFile[$database]["DATABASE"];
			$user = $myIniFile[$database]["USER"];
			$password = $myIniFile[$database]["PASSWORD"];
			$connection_string ='host=' . $host . ' port=' . $port .' dbname=' . $dbname . ' user=' . $user .' password=' . $password;
			$db_connection = pg_connect($connection_string) or die('Connexion impossible : ' . pg_last_error());
			// echo '<i>BDD interrogée : ' . $database . ' ('.$host.', '.$dbname.')</i><br />' ;
			if($database != 'DATABASE_PROD'){
				echo '<script>alert("Attention : vous n\'interrogez pas la base de prod mais '.$database.' ('.$host.') !");</script>' ;
			}
			return $db_connection;
		}
	//	
	function disconnect_from_database() // déconnexion de la base postgresql
	// pg_close ([ resource $connection ] )
	// Lorsque $connection n'est pas présent, la connexion par défaut est utilisée. La connexion par défaut est la dernière connexion faite par pg_connect() ou pg_pconnect(). 
		{
			pg_close();
		}

	function getDocumentTitle($barcode){
			$title = '' ;
			connect_to_database() ;
			$query = 'select br0245 from ca_dt_copies as t1 inner join ca_title_info as t2 on t1.seq_no = t2.seq_no where document = lpad(\''.$barcode.'\', 14, \'0\') ; ' ;
			$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error()) ;
			$max = pg_num_rows ($result) ;
			while ($row = pg_fetch_row($result)) {
				$title = $row[0] ;
			}
			pg_free_result($result) ;
			if ($title == ''){
				$query = 'select concat(br0245, \' (\', issue_desc, \')\')from se_copies as t1 inner join ca_title_info as t2 on t1.seq_no = t2.seq_no inner join se_issues as t3 on t1.issue_id = t3.issue_id where document = lpad(\''.$barcode.'\', 14, \'0\') ; ' ;
				$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error()) ;
				$max = pg_num_rows ($result) ;
				while ($row = pg_fetch_row($result)) {
					$title = $row[0] ;
				}
				pg_free_result($result) ;
			}
			// disconnect_from_database();
			return diacritiques($title) ;
		}
		
	function getPatronName($barcode){
			$name = '' ;
			connect_to_database() ;
			$query = "select subscriber_name from ci_dt_subscriber where subscriber_no = lpad('" . $barcode . "', 14, '0') ; ";
			$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error()) ;
			$max = pg_num_rows ($result) ;
			while ($row = pg_fetch_row($result)) {
				$name = $row[0] ;
			}
			pg_free_result($result) ;
			// disconnect_from_database();
			return diacritiques($name) ;
		}

	function getPortfolioStatus($cb) {
		$status = '' ;
		connect_to_database();
		$query = "select status from ca_dt_copies where document = lpad(".$cb."::text, 14, '0') union select status from se_copies where document = lpad(".$cb."::text, 14, '0') ; " ;
		$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error());
		if (!$result) {
		  echo "An error occurred.\n";
		  exit;
		}
		while ($row = pg_fetch_row($result)) {
			$status = $row[0] ;
		}
		return $status ;
	}	

	function getOccupationDesc($occupationCode) {
		$occupation = '' ;
		connect_to_database();
		$query = "SELECT desc_1 FROM ci_dt_tables WHERE table_ = 'TABOCC' and code = concat('TABOCC  ', '".$occupationCode."') " ;
		// echo $query . '<br />' ;
		$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error());
		if (!$result) {
		  echo "An error occurred.\n";
		  exit;
		}
		while ($row = pg_fetch_row($result)) {
			$occupation = $row[0] ;
		}
		return $occupation ;
	}	

	function getPortfolioLoanInfo($cb) {
		$loanDate = '' ;
		$loaner = '' ;
		connect_to_database();
		$query = "select loan_date, ltrim(subscriber_no, '0'), due_date, loan_hour, prolongation from ci_dt_loan where document_no = lpad(".$cb."::text, 14, '0')  ; " ;
		$result = pg_query($query) or die('Échec de la requête : ' . pg_last_error());
		if (!$result) {
		  echo "An error occurred.\n";
		  exit;
		}
		while ($row = pg_fetch_row($result)) {
			$loanDate = date('d/m/Y',strtotime($row[0] ))  ;
			$loaner = $row[1] ;
			$dueDate = date('d/m/Y',strtotime($row[2] ))  ;
			$loanHour = date('H:m:s',strtotime($row[3] ))  ;
			$prolongation = $row[4] ;
		}
		$loanInfo = array($loanDate,$loanHour, $loaner, $dueDate,$prolongation) ;
		return $loanInfo ;
	}	

		function lienPortail($cb)
			{
				return '<a href="http://www.bm-lille.fr/Default/doc/CATALOGUE/'.str_pad($cb, 10, '0', STR_PAD_LEFT).'" target="_blank">'.$cb.'</a>';
			}

	function ajoute_guillemets_pour_requete($entree) 
		{
			if(isset($entree) && !empty($entree)) {
				$entree = str_replace(', ', ',', $entree);
				$entree = str_replace(',', '\',\'', $entree);
				$entree = '\'' . $entree . '\'';
				return $entree;
			}
		}

	function effacecsv() // efface les fichiers avec l'extension "csv" créés plus de 24 heures auparavant
		{
			// source : http://www.developpez.net/forums/d666798/php/langage/fichiers/petit-script-permet-supprimer-fichiers-d-dossier/
			$rep=opendir(".");
			$i=0;
			while($file = readdir($rep)){
				// if(pathinfo($file)['extension'] == 'csv'){
				if(substr($file, strlen($file)-3, 3) == 'csv'){
					$age =  time() - filectime($file); // âge en secondes - NB : 1 jour = 60*60*24 = 86400 secondes
					// echo 'Fichier : ' . $file . ' -> age : ' . $age . ' s ; extension : ' . substr($file, strlen($file)-3, 3) ;
					// echo '<br />';
					if($age > 86400){
						unlink($file);
						$i++;
					}
				}
			}
			// affichage du nb de fichiers supprimés :
			// if($i>1){$text=$i." fichiers ont été supprimés";}
			// elseif($i==1){$text="1 fichier a été supprimé";}
			// elseif($i==0){$text="Aucun fichier n'a été supprimé";}
			// echo $text .'<br />';
		}

	function ajoute_guillemets($entree) 
		{
			if(isset($entree) && !empty($entree)) {
				$entree = "'".str_replace(', ', '\', \'', $entree)."'";
				return $entree;
			}
		}
/*----------------------*/
	function enleve_double_espace($entree) 
		{
			if(isset($entree) && !empty($entree)) {
				$entree = preg_replace('!\s+!', ' ', $entree);
				return $entree;
			}
		}
/*----------------------*/
	function diacritiques($chaine) 
		{
			if(isset($chaine) && !empty($chaine) && ($chaine != "0")) {
				//diacritiques : éèêàâêîôûÉÈçÀÇ etc
				$chaine = str_replace('Ã¢', 'â', $chaine);
				$chaine = str_replace('Ã¢', 'â', $chaine);
				$chaine = str_replace('Ã¡', 'á', $chaine);
				$chaine = str_replace('Ã¤', 'ä', $chaine);
				$chaine = str_replace('Ã ', 'à', $chaine);
				$chaine = str_replace('Ã€', 'À', $chaine);
				$chaine = str_replace('Ã?', 'À', $chaine);
				$chaine = str_replace('Ã©', 'é', $chaine);
				$chaine = str_replace('Ã¨', 'è', $chaine);
				$chaine = str_replace('Ãª', 'ê', $chaine);
				$chaine = str_replace('Ã«', 'ë', $chaine);
				$chaine = str_replace('Ã‰', 'É', $chaine);
				$chaine = str_replace('Ãˆ', 'È', $chaine);
				$chaine = str_replace('Ã®', 'î', $chaine);
				$chaine = str_replace('Ã¯', 'ï', $chaine);
				$chaine = str_replace('Ã­', 'í', $chaine);
				$chaine = str_replace('Ã´', 'ô', $chaine);
				$chaine = str_replace('Ã¶', 'ö', $chaine);
				$chaine = str_replace('Ã¶', 'ö', $chaine);
				$chaine = str_replace('Ã»', 'û', $chaine);
				$chaine = str_replace('Ã¹', 'ù', $chaine);
				$chaine = str_replace('Ãº', 'ú', $chaine);
				$chaine = str_replace('Ã¼', 'ü', $chaine);
				$chaine = str_replace('Ã§', 'ç', $chaine);
				$chaine = str_replace('Ã‡', 'Ç', $chaine);
				$chaine = str_replace('Å?', 'Œ', $chaine);
				$chaine = str_replace('Ã?', 'À', $chaine);
				$chaine = str_replace('\u0152', 'Œ', $chaine);
				$chaine = str_replace('\u0153', 'œ', $chaine);
				$chaine = str_replace('Ã?', 'É', $chaine);
				$chaine = str_replace('\u00c0', 'À', $chaine); 
				$chaine = str_replace('\u00c0', 'À', $chaine); 
				$chaine = str_replace('\u00c1', 'Á', $chaine); 
				$chaine = str_replace('\u00c2', 'Â', $chaine); 
				$chaine = str_replace('\u00c3', 'Ã', $chaine); 
				$chaine = str_replace('\u00c4', 'Ä', $chaine); 
				$chaine = str_replace('\u00c5', 'Å', $chaine); 
				$chaine = str_replace('\u00c6', 'Æ', $chaine); 
				$chaine = str_replace('\u00c7', 'Ç', $chaine); 
				$chaine = str_replace('\u00c8', 'È', $chaine); 
				$chaine = str_replace('\u00c9', 'É', $chaine); 
				$chaine = str_replace('\u00ca', 'Ê', $chaine); 
				$chaine = str_replace('\u00cb', 'Ë', $chaine); 
				$chaine = str_replace('\u00cc', 'Ì', $chaine); 
				$chaine = str_replace('\u00cd', 'Í', $chaine); 
				$chaine = str_replace('\u00ce', 'Î', $chaine); 
				$chaine = str_replace('\u00cf', 'Ï', $chaine); 
				$chaine = str_replace('\u00d1', 'Ñ', $chaine); 
				$chaine = str_replace('\u00d2', 'Ò', $chaine); 
				$chaine = str_replace('\u00d3', 'Ó', $chaine); 
				$chaine = str_replace('\u00d4', 'Ô', $chaine); 
				$chaine = str_replace('\u00d5', 'Õ', $chaine); 
				$chaine = str_replace('\u00d6', 'Ö', $chaine); 
				$chaine = str_replace('\u00d8', 'Ø', $chaine); 
				$chaine = str_replace('\u00d9', 'Ù', $chaine); 
				$chaine = str_replace('\u00da', 'Ú', $chaine); 
				$chaine = str_replace('\u00db', 'Û', $chaine); 
				$chaine = str_replace('\u00dc', 'Ü', $chaine); 
				$chaine = str_replace('\u00dd', 'Ý', $chaine); 
				$chaine = str_replace('\u00df', 'ß', $chaine); 
				$chaine = str_replace('\u00e0', 'à', $chaine); 
				$chaine = str_replace('\u00e1', 'á', $chaine); 
				$chaine = str_replace('\u00e2', 'â', $chaine); 
				$chaine = str_replace('\u00e3', 'ã', $chaine); 
				$chaine = str_replace('\u00e4', 'ä', $chaine); 
				$chaine = str_replace('\u00e5', 'å', $chaine); 
				$chaine = str_replace('\u00e6', 'æ', $chaine); 
				$chaine = str_replace('\u00e7', 'ç', $chaine); 
				$chaine = str_replace('\u00e8', 'è', $chaine); 
				$chaine = str_replace('\u00e9', 'é', $chaine); 
				$chaine = str_replace('\u00ea', 'ê', $chaine); 
				$chaine = str_replace('\u00eb', 'ë', $chaine); 
				$chaine = str_replace('\u00ec', 'ì', $chaine); 
				$chaine = str_replace('\u00ed', 'í', $chaine); 
				$chaine = str_replace('\u00ee', 'î', $chaine); 
				$chaine = str_replace('\u00ef', 'ï', $chaine); 
				$chaine = str_replace('\u00f0', 'ð', $chaine); 
				$chaine = str_replace('\u00f1', 'ñ', $chaine); 
				$chaine = str_replace('\u00f2', 'ò', $chaine); 
				$chaine = str_replace('\u00f3', 'ó', $chaine); 
				$chaine = str_replace('\u00f4', 'ô', $chaine); 
				$chaine = str_replace('\u00f5', 'õ', $chaine); 
				$chaine = str_replace('\u00f6', 'ö', $chaine); 
				$chaine = str_replace('\u00f8', 'ø', $chaine); 
				$chaine = str_replace('\u00f9', 'ù', $chaine); 
				$chaine = str_replace('\u00fa', 'ú', $chaine); 
				$chaine = str_replace('\u00fb', 'û', $chaine); 
				$chaine = str_replace('\u00fc', 'ü', $chaine); 
				$chaine = str_replace('\u00fd', 'ý', $chaine); 
				$chaine = str_replace('\u00ff', 'ÿ', $chaine); 
				$chaine = str_replace('\u00e0', 'à', $chaine); 
				$chaine = str_replace('\u00e2', 'â', $chaine); 
				$chaine = str_replace('\u00e4', 'ä', $chaine); 
				$chaine = str_replace('\u00e7', 'ç', $chaine); 
				$chaine = str_replace('\u00e8', 'è', $chaine); 
				$chaine = str_replace('\u00e9', 'é', $chaine); 
				$chaine = str_replace('\u00ea', 'ê', $chaine); 
				$chaine = str_replace('\u00eb', 'ë', $chaine); 
				$chaine = str_replace('\u00ee', 'î', $chaine); 
				$chaine = str_replace('\u00ef', 'ï', $chaine); 
				$chaine = str_replace('\u00f4', 'ô', $chaine); 
				$chaine = str_replace('\u00f6', 'ö', $chaine); 
				$chaine = str_replace('\u00f9', 'ù', $chaine); 
				$chaine = str_replace('\u00fb', 'û', $chaine); 
				$chaine = str_replace('\u00fc', 'ü', $chaine); 
				$chaine = str_replace('\u0153', 'œ', $chaine); 
				return $chaine;
				//cf https://exceptionshub.com/how-to-convert-u00e9-into-a-utf8-char-in-mysql-or-php.html
			}
		}
/*----------------------*/
	function startsWith($haystack, $needle)
		{
			 $length = strlen($needle);
			 return (substr($haystack, 0, $length) === $needle);
		}
/*----------------------*/
	function endsWith($haystack, $needle)
		{
			$length = strlen($needle);
			if ($length == 0) {
				return true;
			}
			return (substr($haystack, -$length) === $needle);
		}
/*----------------------*/
	function date_francaise_longue($date){// convertit une date Y-m-d en ??
		// $date = string de la forme Y-m-d
		setlocale(LC_TIME, 'fr_FR.utf8','fra');
		$date_francaise_longue =  utf8_encode(strftime('%A %d %B %Y', strtotime($date)));
		// $date_francaise =  utf8_encode(strftime('%A %e %B %Y', strtotime($date))); // $e est censé enlever le 0 inutile dans le chiffre du jour, mais ne marche pas sous Windows
		return $date_francaise_longue ;
	}
/*----------------------*/
	function date_francaise($date){ // convertit une date Y-m-d en d/m/Y
		// $date = string de la forme Y-m-d
		$date_francaise =  strftime('%d/%m/%Y', strtotime($date));
		return $date_francaise ;
	}
/*----------------------*/
	function coche($bool){ // remplace les t/f des booléens obtenus par une requête postgresql par un caractère html visuellement plus parlant (coche verte ou croix rouge)
		if($bool==='t'){
			// return '&#x2705;';
			return '<span style="color:#00cc00;">&#x2714;</span>';
		}
		elseif($bool==='f'){
			return '&#x274C;';
		}
		else{
			return NULL;
		}
	}
//-----------------------------//	
	function string2date($string){
		// convertit un AAAAMMJJ en JJ/MM/AAAA
		if($string != ''){
			$date = substr($string, 6, 2). '/' . substr($string, 4, 2). '/' . substr($string, 0, 4) ;
		}
		else {
			$date = '';
		}
		return $date ;
	}
//-----------------------------//	
	function seq_no2title($seq_no){
		connect_to_portfolio();
		$title = '';
		$query = 'select br0245 from ca_title_info where seq_no = '.$seq_no.' ;' ;
		$result = pg_query($query ) or die('Échec de la requête : ' .pg_last_error() );
		while ($row = pg_fetch_row($result)) {	
			$title = diacritiques($row[0])  ;
		}
		pg_free_result($result);
		disconnect_from_database() ;
		return $title ;
	}
//-----------------------------//	
	function xmlViewer($xml_original){
		$xml_new = xml_original;
		$c=0;
		$xml_array = explode("{", $xml_new) ;
		
		return $xml_new ;
	}
?>