<title>Clanoverzicht</title>
<head>
<link rel="stylesheet" type="text/css" href="/styles/table.css">
<script src="sort.js"></script>
</head>
<?php 
	// Creëer databases
	$db  = new SQLite3('db.sqlite');

	// Definieer de volgende variabelen:
			// Clantag (zonder #)
			$clantag = '82P2VV0';
			// Clanleider (met #)
			$clanleader = '#PRCY22L8';
			//Auth key
			$auth_key = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiIsImtpZCI6IjI4YTMxOGY3LTAwMDAtYTFlYi03ZmExLTJjNzQzM2M2Y2NhNSJ9.eyJpc3MiOiJzdXBlcmNlbGwiLCJhdWQiOiJzdXBlcmNlbGw6Z2FtZWFwaSIsImp0aSI6IjM5NGI4ZjcyLWZkZTctNGQwMS05ZTg1LTM3YWYwOTAwMmY2ZSIsImlhdCI6MTUzNjQwODA5OSwic3ViIjoiZGV2ZWxvcGVyL2FkMWUyMzhhLWM4NTQtZWE0My0wMTY2LTMyODYwYzE1ZTQ0OSIsInNjb3BlcyI6WyJyb3lhbGUiXSwibGltaXRzIjpbeyJ0aWVyIjoiZGV2ZWxvcGVyL3NpbHZlciIsInR5cGUiOiJ0aHJvdHRsaW5nIn0seyJjaWRycyI6WyIzNC4yNDEuMTY2LjM5IiwiMzQuMjUxLjEyOC41MCJdLCJ0eXBlIjoiY2xpZW50In1dfQ.7WGBB4sw-SlGtvVAoEK8uuvNjm5HtNNJ2qC6pe-xb-uZccY3Xg1tH0w5IP3y5vd-omkIcPfPGkjT9rHCZ0LTRA';
	
	// Definieer headers
    $header = "Authorization: Bearer: ".$auth_key;
	
	// Roep de actieve leden aan uit de database en sorteer op aantal trofeeën
	$sql = 'SELECT * FROM "members" WHERE status = 1 ORDER BY trophies';
	$result = $db->query($sql) or die('Ophalen ledenlijst mislukt');
		
	// Definieer het aantal laatste clanwars dat wordt meegenomen
	$bereik = 105;
	// Definieer het aantal uur dat een clanlid in de clan moet hebben gezeten om meegeteld te worden (minimaal 24, want tot 24 uur na het starten van een clanwar kunnen nieuwe leden nog meedoen). "CreatedDate" wordt door Supercell doorgegeven als de datum dat de clanwar is beëindigd. Voorbeeld: als bij $hours een parameter van 26 wordt ingevoerd, betekent dit dat nieuwe leden nog 26-24 = 2 uur hebben om mee te doen om meegeteld te worden.
	$hours = 25;
	$seconds = $hours * 3600;
	
	// Query de laatste X clanwars en bijbehorende timestamps:
	$sql = 'SELECT * FROM "wars" ORDER BY createdDate DESC LIMIT '.$bereik;
	$result_wars = $db->query($sql) or die('Ophalen 5 laatste clanwars mislukt');

?>
<body>

<div class="container">
Tekst </br> </br>
<table cellspacing="0" style="display:block;">

<thead>
<tr>
	<th colspan="4">Speler</th>
	<th colspan="3">Verzameldag</th>
	<th colspan="2">Oorlogsdag</th>
</tr>
<tr class="sort">
	<th>Naam</th>
	<th><div><span>Rol</span></div></th>
	<th><div><span>Level</span></div></th>
	<th><div><span>Trofeeën</span></div></th>
	<th><div><span>Gespeeld</span></div></th>
	<th><div><span>Verzameld</span></div></th>
	<th><div><span>%Part</span></div></th>
	<th><div><span>%Win</span></div></th>
	<th><div><span>#Gemist</span></div></th>
</tr>
<tr class="averages">
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th class="win">50%</th>
	<th></th>
</tr>
</thead>
<tbody>
<?php
	while ($row = $result->fetchArray()) {
	// Loop voor elk actief clanlid
	$tag = $row['tag'];
	
	// Bepaal de rol per clanlid
		if ($row['role'] == 'member') {
		$role = 'lid';
		} elseif ($row['role'] == 'elder') {
		$role = 'oudste';
		} elseif ($row['role'] == 'coLeader') {
		$role = 'co-leider';
		} elseif ($row['role'] == 'leader') {
		$role = 'leider';}
	
	// Bepaal het aantal keer dat een lid opnieuw gejoind is
		if ($row['times_rejoined'] > 0) {
			$times_rejoined = $row['times_rejoined'];
		}
	
		echo "<tr class='data'>\n\n<td class='name' style=' line-height: 0;'>".$row['name']."<span class='rejoined'>".$times_rejoined."</span></td><td class='role'>".$role.'</td><td class="level">'.$row['level']."</td><td class='trophies'>".$row['trophies'].'</td>';
		
		// Maak een array van de warresultaten per clanlid
		$warday_results = array();
		$warday_gamecount = array();
		$collectingday_games_results = array();
		$collectingday_cards_results = array();
		
		// Loop een kolom voor elke clanwar in de scope
		while ($row2 = $result_wars->fetchArray()) {
			$sql = 'SELECT * FROM "wardata" WHERE createdDate = "'.$row2['createdDate'].'" AND tag = "'.$tag.'"';
			
			$result_col = $db->query($sql) or die('Ophalen kolom mislukt');
			
			// Zet resultaat in een array
			$array = $result_col->fetchArray();;
			
			//---------------------------------------------------------//
			// Onderstaande code geeft de volgende outputs:
			// 0 = niet in clan
			// 1 = geen participatie
			// 2 = warday gemist
			// 3 = 0 wins
			// 4 = 1 win
			// 5 = 2 wins, enzovoorts
			//----------------------------------------------------------//
			if ($row['time_joined'] > $row2['createdDate']-$seconds) {
				$output = 0;
				$print = '_';
			} elseif ($array['cardsEarned'] == 0) {
				$output = 1;
				$print = 'DNP';
			} elseif ($array['battlesPlayed'] == 0) {
				$output = 2;
				$output_gamecount = 0;
				$output_cardsBattles = $array['cardsBattles'];
				$output_cardsCollected = $array['cardsEarned'];
				$print = '<b style="color:red">Gemist</b>';
			} else {
				$output = $array['wins']+3;
				$output_gamecount = $array['battlesPlayed'];
				$output_cardsBattles = $array['cardsBattles'];
				$output_cardsCollected = $array['cardsEarned'];
				$print = '<b>'.$array['wins'].'</b>/'.$array['battlesPlayed'].'';
			}
			// Einde output controle
			//---------------------------------------------------------//

		// Schrijf de resultaten weg in een array per speler, om ermee te kunnen rekenen
		$warday_results[] 			= $output;
		$warday_gamecount[]			= $output_gamecount;
		$collectingday_games_results[]	= $output_cardsBattles;
		$collectingday_cards_results[] 	= $output_cardsCollected;

		//$collectingday_cards_results = array();
		
		$print_while .= '<td>'.$print.'</td>';
		

		
		unset($output);
		unset($output_gamecount);
		unset($output_cardsBattles);
		unset($output_cardsCollected);
		}
		
		// Tel de verschillende warresultaten
		$counts = array_count_values($warday_results);
		
		
// Bereken gemiddeld aantal gespeelde gevechten op de verzameldag
		$collectingday = number_format(array_sum($collectingday_games_results) / ($counts[2]+$counts[3]+$counts[4]+$counts[5]+$counts[6]), 2);
			// Vervang niet-numerieke waarden door nvt
			if ($collectingday == 'nan') {
			$collectingday = '';
			}
		echo '<td class="collectingday">'.$collectingday.'</td>';

// Bereken gemiddeld aantal verzamelde kaarten op de verzameldag
		$collected = round(array_sum($collectingday_cards_results) / ($counts[2]+$counts[3]+$counts[4]+$counts[5]+$counts[6]));
			// Vervang niet-numerieke waarden door nvt
			if (is_nan($collected) == TRUE) {
			$collected = '';
			}
		echo '<td class="collected">'.$collected.'</td>';
		
		
// Bereken DNP percentage
		$DNP_perc = 100-round(($counts[1] / ($counts[1]+$counts[2]+$counts[3]+$counts[4]+$counts[5]+$counts[6])) * 100);
			// Vervang niet-numerieke waarden door nvt
			if (is_nan($DNP_perc) == TRUE) {
			$DNP_perc = '';
			} else {
			$DNP_perc = $DNP_perc.'%';
			}
		echo '<td class="participate">'.$DNP_perc.'</td>';
		
// Bereken winstpercentage (neemt gemiste wardagen NIET mee)
		$win_perc = round(($counts[4] + $counts[5]*2 + $counts[6]*3) / (array_sum($warday_gamecount)) * 100);
		
			// Vervang niet-numerieke waarden door nvt
			if (is_nan($win_perc) == TRUE) {
			$win_perc = '';
			} else {
			$win_perc = $win_perc.'%';
			}
		
		echo '<td class="win">'.$win_perc.'</td>';
		
// Bereken aantal gemiste gevechten
		$missed_count = $counts[2];
		echo '<td class="missed">'.$missed_count.'</td>';
		
// Toon alle warresultaten per speler
		echo $print_while;
		
		// Ruim alle variabelen op
		unset($print_while);
		unset($warday_gamecount);
		unset($collectingday_games_results);
		unset($collectingday_cards_results);
		echo "</tr>\n";
	}
?>
</tbody>
</table>
</div>
</body>