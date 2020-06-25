<!doctype html>
<html lang="fr">
	<?php
		require_once('functions.php');
		$myIniFile = parse_ini_file ("config.ini", TRUE);
		function split_directory($directory){
			$splat_directory = '';
			foreach(str_split($directory, 12) as $key => $value){
				$splat_directory .= substr($value, 0, 3) . ' ' . substr($value, 3, 4) . ' ' . substr($value, 7, 5) . PHP_EOL;
			}
			$splat_directory = rtrim($splat_directory);
			$splat_directory = rtrim($splat_directory, ';');
			$splat_directory = rtrim($splat_directory);
			$splat_directory = ltrim($splat_directory);
			return $splat_directory;
		}
		function length($str){
			// pour pouvoir changer facilement de fonction de mesure de longueur des strings
			if(isset($str)){
				// $length = strlen($str); // https://www.php.net/manual/fr/function.strlen.php : strlen() retourne le nombres d'octets plutôt que le nombre de caractères dans une chaîne. 
				// $length = mb_strlen($str); // https://www.php.net/manual/fr/function.mb-strlen.php : Retourne le nombre de caractères dans la chaîne str, avec l'encodage encoding. Un caractère multi-octets est alors compté pour 1. 
				$length = iconv_strlen($str); // https://www.php.net/manual/fr/function.iconv-strlen.php : À l'opposée de strlen(), la valeur de retour de iconv_strlen() est le nombre de caractères faisant partie de la séquence d'octets str, ce qui n'est pas toujours la même chose que la taille en octets de la chaîne de caractères. 
				return $length;
			}
		}
	?>
<head>
		<title>Directory Reader</title>
		<?php //require_once('head.txt') ; ?>
		<script>
			function selectAll(id){
				var p = document.getElementById(id)
				p.value;
			}
		</script>
	</head>
		<body>
			<a id="haut"></a>
			<!--<h1>CSV<span style="font-size: smaller; font-style: italic; color: red;">2</span>ISO2709</h1>-->
			<h1>Directory Reader</h1>
			<form method="post" action="directory_reader.php" enctype="multipart/form-data">
				<textarea id="directory" name="directory" rows="5" cols="100" onclick="this.select()"><?php echo $_POST['directory']; ?></textarea><br />
				<input type="submit" name='ok' id='ok' value="Envoyer"> 
			</form>
			<textarea id="directory_split" name="directory_split" rows="30" cols="100">
			<?php
				if(isset($_POST['directory'])){
					$text = $_POST['directory'];
					echo split_directory($text);
				}
			?>
			</textarea>
	</body>
</html>