<?php
    echo '<title>CR members</title>';
	
	// Creëer databases
	$db  = new SQLite3('db.sqlite');

	// Definieer de volgende variabelen:
			// Clantag (zonder #)
			$clantag = 'xxx';
			// Clanleider (met #)
			$clanleader = '#xxx';
			//Auth key
			$key = 'xxx';
	
	// Definieer headers
    $header = "Authorization: Bearer: ".$key;
	
    // Definieer API URL
    $api_url = 'https://api.clashroyale.com/v1/clans/%23'.$clantag.'/';

    // Maak curl object
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $header ]);

    // Run curl object
    $rawdata = curl_exec($ch) or die('<b>cURL Error: </b>'.curl_error($ch));
	
	// Toon data
	echo $controle;
    echo '<div style="height:400px;overflow:auto;border:1px solid;">' . $rawdata . '</div>';
	
    // Converteer
    $real_data = json_decode($rawdata);

    // Ruim curl object op
    curl_close($ch);
	
	// Maak een database aan met de leden
	$sql = 'CREATE TABLE IF NOT EXISTS "members" ("name" TEXT NOT NULL, "tag" TEXT PRIMARY KEY NOT NULL, "role" TEXT, "status" INTEGER NOT NULL, "level" NUMERIC, "trophies" NUMERIC NOT NULL, "don_given" NUMERIC, "don_receiv" NUMERIC, "time_joined" TEXT, "time_left" TEXT, "times_rejoined" NUMERIC)';
	$db->exec($sql) or die('Tabel maken mislukt');	

	$time_left	= time();			//De tijd als het script begint te lopen
	// Loop through memberlist
    foreach ($real_data->memberList as $members) {
				
        // Get some basic info
        $name       = $members->name;
        $tag        = $members->tag;
		$role		= $members->role;
		$level		= $members->expLevel;
		$trophies	= $members->trophies;
		$don_given	= $members->donations;
		$don_receiv	= $members->donationsReceived;

		
		// Bepaal de controlevariabele en stel deze in op true zolang de leider nog in de clan zit
		if ($tag == $clanleader) {
		$controle = TRUE;
		}
		var_dump($controle);

		$sql = 'SELECT * FROM "members" WHERE tag = "'.$tag.'"';
		$aantal = $db->query($sql) or die('Tagcheck mislukt');
		//var_dump($aantal);
		
		$data = $aantal->fetchArray();;
		
		if ($data) {
		// Bestaat al in de database, dus is lid (geweest)
		echo 'Status:'.$data['status'].' '.$data['name'].'';
			if ($data['status'] == 1) {
			// Lid bestaat al en is in de clan gebleven
				echo '<b>3. Naam: ' .$name.$role. ', tag: ' .$tag. ', time_left: '.$time_left.'</b><br />';	
				
				$sql = 'UPDATE "members" SET level = '.$level.', role = "'.$role.'", trophies = '.$trophies.', don_given = '.$don_given.', don_receiv = '.$don_receiv.', time_left = '.$time_left.' WHERE tag = "'.$tag.'"';
				
				echo '<span class="SQL">'.$sql.'</span><br /><br />';
				
				$db->exec($sql) or die('Error 3');
				
			} else {
			//Lid bestaat al en is teruggekeerd in clan
				$time_joined = time();
				$status = 1;
				
				$sql = 'UPDATE "members" SET level = '.$level.', role = "'.$role.'", status = '.$status.', trophies = '.$trophies.', don_given = '.$don_given.', don_receiv = '.$don_receiv.', time_joined = '.$time_joined.', time_left = '.$time_left.', times_rejoined = times_rejoined + 1 WHERE tag = "'.$tag.'"';
				
				$db->exec($sql) or die('Error 2');
				echo '<table style="background-color:tomato"><tr><td><b>DB:</b></td><td>'.$data['name'].'</td><td>'.$data['tag'].'</td><td>'.$data['time_joined'].'</td><td>'.$data['time_left'].'</td></tr></table><br />';
				echo $sql;
				echo '<b>2. Naam: ' .$name. ', tag: ' .$tag. ', time_left: '.$time_left.'</b><br /><br />';
			}
		
		} else {
		// Bestaat niet in de database, dus lid is gejoind:
		echo '<b>Nieuw!</b> ';
			$time_joined = time();
			$status = 1;
			
			$sql = 'INSERT OR REPLACE INTO "members" VALUES ("'.$name.'","'.$tag.'","'.$role.'","'.$status.'","'.$level.'","'.$trophies.'","'.$don_given.'","'.$don_receiv.'","'.$time_joined.'","'.$time_left.'","0")';
			$db->exec($sql) or die('Error 1');
			
			echo '<b>2. Naam: ' .$name. ', tag: ' .$tag. ', time_left: '.$time_left.'</b><br />';
		}
    }
	
	//Controleer of de controle geslaagd is
	
	if ($controle === TRUE) {
		// Nu alle leden die nu lid zijn zijn toegevoegd, check welke leden ontbreken en verander hun status in 0 (uit de clan)
		$sql = 'UPDATE "members" SET status = 0 WHERE time_left != "'.$time_left.'"';
		var_dump($controle);
		echo ' <b>dus controle geslaagd</b><br />'.$sql;
		$db->exec($sql) or die('Status wijzigen mislukt');
		
		} else {
		die('Controlevariabele mislukt: json incorrect ingeladen');
		}
?>
