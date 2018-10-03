<?php
    echo '<title>CR warlog</title>';

	// Creëer databases
	$db  = new SQLite3('db.sqlite');

	// Definieer headers
    $key = "Authorization: Bearer: xxx";

	// Clantag zonder #
	$clantag = 'xxx';
	
    // Definieer API URL
    $api_url = 'https://api.clashroyale.com/v1/clans/%23'.$clantag.'/warlog';

    // Maak curl object
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json' , $key ]);

    // Run curl object
    $rawdata = curl_exec($ch);
	
	// Toon data
    echo '<div style="height:400px;overflow:auto;border:1px solid;">' . $rawdata . '</div>';
	
    // Converteer
    $data = json_decode($rawdata);

    // Ruim curl object op
    curl_close($ch);
	
    // Ga items object in
    $real_data = $data->items;
	
	// Maak een database aan met de leden
	$sql = 'CREATE TABLE IF NOT EXISTS "wardata" ("tag" TEXT NOT NULL, "createdDate" TEXT NOT NULL, "cardsEarned" NUMERIC NOT NULL, "battlesPlayed" NUMERIC, "wins" NUMERIC, unique (tag, createdDate))';
	$db->exec($sql) or die('Tabel maken mislukt');
	
	// Maak een tabel aan met de oorlogen
	$sql = 'CREATE TABLE IF NOT EXISTS "wars" ("season" NUMERIC, "createdDate" TEXT UNIQUE NOT NULL)';
	$db->exec($sql) or die('Wars-tabel maken mislukt');
	
    // Loop door elke oorlog
    foreach ($real_data as $war) {

        // Haal informatie op
        $seasonId       = $war->seasonId;
        $createdDate    = $war->createdDate;
        $participants   = $war->participants;
		//$other			= json_encode($war->standings); // Tijdelijk klad, misschien script later uitbreiden
		
		// Bereken de unix timestamp
			$clean_Date = explode('.', $createdDate, -1)[0];
			$unix = date("U", strtotime($clean_Date));
		
		// Print
		echo '<b>Oorlog: ' .$createdDate. ', unix: '.$unix.', seizoen: ' .$seasonId. '</b><br />';
		
		// Schrijf weg in database
		$sql = 'INSERT OR IGNORE INTO "wars" VALUES ("'.$seasonId.'","'.$unix.'")';
		$db->exec($sql) or die('Toevoegen oorloginformatie mislukt');
		
		
        // Loop through all the participants
        foreach ($participants as $participant) {

            // Print his tag
            echo $participant->name . ' & ' . $participant->collectionDayBattlesPlayed . '<br />';
			
			// Schrijf weg naar database
			$sql = 'INSERT OR IGNORE INTO "wardata" VALUES ("'.$participant->tag.'","'.$unix.'","'.$participant->cardsEarned.'","'.$participant->collectionDayBattlesPlayed.'","'.$participant->battlesPlayed.'","'.$participant->wins.'")';
				$db->exec($sql) or die('Toevoegen data mislukt');
        }
    }
?>
