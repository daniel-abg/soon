<?php
	include 'inlcude_all.php';
	include 'loginwall.php';
	
	// Prüfung, ob die "year"-Variable in der URL mitgesendet wurde: Wenn nein, wird das aktuelle Jahr als Wert gespeichert.
	if(empty($_GET['year'])) {
		$year = date('Y');
	} else {
		$year = $_GET['year'];
	}
	// Prüfung, ob die "month"-Variable in der URL mitgesendet wurde: Wenn nein, wird der aktuelle Monat als Wert gespeichert.
	if(empty($_GET['month'])) {
		$month = date('F');
	} else {
		$month = $_GET['month'];
	}
	// Kalenderdarstellung, 1 = kompakte Ansicht
	$view = 0;
	
	// Umwandlung von den Angaben Jahr und Monat als Zeitstempel
	$timestamp_of_month = strtotime("$year $month");
	// Prüfung, ob sich um einen Zeitstempel vor dem aktuellen Monat handelt
	if($timestamp_of_month < strtotime(date('Y-m-01'))) {
		$year = date('Y');
		$month = date('F');
		header('Location: '.$path.'calendar/'.$year.'/'.$month.'');
	}
	// Speicherung von vergangenen Zeitangaben
	$previous_month_timestamp = strtotime('-1 month', $timestamp_of_month);
	$previous_month = date("F", $previous_month_timestamp);
	$previous_month_year = date("Y", $previous_month_timestamp);
	// Speicherung von zukünftigen Zeitangaben
	$next_month_timestamp = strtotime('+1 month', $timestamp_of_month);
	$next_month = date("F", $next_month_timestamp);
	$next_month_year = date("Y", $next_month_timestamp);
	
	$t_month = 't_month_'.date("n", $timestamp_of_month);
	
	$title = "".$month." ".$year."  - soon";
?>

<!DOCTYPE html>

<html>
	<head>
		<?php include 'head.php';?>
	</head>

	<body>
		<?php include 'navbar.php';?>				
		<div class="container">
			<div class="row margin-top-20 margin-bottom-30">
				<?php
					// Prüfung, ob ein zukünftiger Monat angezeigt wird. Wenn ja: Anzeige der "einen Monat zurück"-Schaltfläche
					if($timestamp_of_month < time()) {
						echo "<div class='col-3 col-lg-4'></div>";
					} else {
						echo"<div class='col-3 col-lg-4'>
								<a class='btn btn-light' href='".$path."calendar/".$previous_month_year."/".$previous_month."'>
									<i class='fas fa-chevron-left'></i>
								</a>
							</div>";
					}
					// Anzeige des aktuellen Monats mit Schaltfläche für den PDF-Export
					echo "<div class='col-6 col-lg-4 padding-0' style='text-align: center; padding-top: 10px;'>
						".${$t_month}[$language]." ".$year."
					</div>";
					// Anzeige der "einen Monat weiter"-Schaltfläche					
					echo"<div class='col-3 col-lg-4'>
							<a class='btn btn-light' href='".$path."calendar/".$next_month_year."/".$next_month."'>
								<i class='fas fa-chevron-right'></i>
							</a>
						</div>";
				?>
				
			</div> <!-- Ende von .row -->
		</div> <!-- Ende von .container -->
		<?php
		if($view == 1) {
			echo "<div class='compact-container'>
			<div class='row compact-cols'>";
		} else {
		echo "<div class='calendar-container'>
			<div class='row calendar-cols'>";
		}
					$date = $timestamp_of_month;
					
					// Einstellung, dass beim aktuellen Monat beim aktuellen Tag begonnen wird
					if($timestamp_of_month == strtotime(date('Y-m-01'))) {
						$date = time();
					}
					
					$last_day_of_month = strtotime(date("Y-m-t 24:00", $timestamp_of_month));
										
					while ($date < $last_day_of_month) {
						// Variable, die definiert, welche Farbe der Terminname hat
						if($date == time()) {
							$appointmentcolor = "color-red";
						} else {
							$appointmentcolor = "";
						}
						
						// Definierung Datumformat
						$t_day = 't_day_'.date("N", $date);
						
						$t_date_format = array(
							${$t_day}[$language].", ".date("j.", $date), 
							${$t_day}[$language].", ".date(" j", $date)
						);
					
						if($view == 1) {
							// Ausgabe Datum
							echo "<div class='col-md-1'>
								<div>
									<a class='date btn btn-light btn-sm' href='".$path."add.php?date=".date("Y-m-d", $date)."'>
										".$t_date_format[$language]."
									</a>";
						} else {						
							echo "<div class='col-md-1'>
								<div>							
									<a class='date btn btn-light btn-sm' href='".$path."add.php?date=".date("Y-m-d", $date)."'>								
										".$t_date_format[$language]."														
									</a>";
						}
						
						// Suche nach einem Termin
						$first_timestamp_of_today = strtotime(date("Y-m-d 00:00:00", $date));
						$last_timestamp_of_today = strtotime(date("Y-m-d 23:59:59", $date));
						
						$sql_select = "SELECT * FROM entries WHERE userid = '$userid' AND timestamp >= '$first_timestamp_of_today' AND timestamp <= '$last_timestamp_of_today' ORDER BY timestamp";
						
						foreach ($connection->query($sql_select) as $row) {
							// Entschlüsselung der vom Nutzer angegebenen Informationen
							$entryname = openssl_decrypt($row['entryname'],"AES-128-ECB",$key);
							$location = openssl_decrypt($row['location'],"AES-128-ECB",$key);
							$comment = openssl_decrypt($row['comment'],"AES-128-ECB",$key);
							
							// Definierung Zeitformat
							$t_time = array(
								date('G:i', $row['timestamp'])." Uhr",
								date('g.i a', $row['timestamp'])
							);
														
							if($view == 1) {
										echo "<span class='appointment-icon'>";
								// Ausgabe Termin Popover
									echo "<a tabindex='0' data-toggle='popover' data-trigger='focus hover' data-placement='top' data-html='true' title='";								
										// Titel des Popovers
										echo "<a href=\"".$path."entry?entryid=".$row['entryid']."\">".htmlspecialchars($entryname)."</a>";
										// Inhalt des Popovers
										echo"' data-content='";										
											if($row['time_set'] == 'true') {
												echo "<div><i class=\"fas fa-clock\"></i> ".$t_time[$language]."</div>";
											}
											if(!empty($location)) {
												echo "<div><i class=\"fas fa-map-marker-alt\"></i> ".htmlspecialchars($location)."</div>";
											};
											if(!empty($comment)) {
												echo "<div><i class=\"fas fa-comment\"></i> ".htmlspecialchars($comment)."</div>";
											};								
										echo "'>";
										// Element, welches beim Anklicken das Popover öffnet
										echo "<i class='".$appointmentcolor." icon far fa-calendar'></i>";
										
									echo "</a></span>";	
							} else {
								
								// Ausgabe Terminname
								echo "<div class='appointment'><a class='".$appointmentcolor." title' href='".$path."entry?entryid=".$row['entryid']."'>";
									
									echo "<i class='far fa-calendar'></i>";
									
									echo"
										".htmlspecialchars($entryname)."
									</a>";
									
								// Prüfung, ob zum Termin eine Uhrzeit, ein Ort oder ein Kommentar vorhanden ist
								if($row['time_set'] == "false" and empty($location) and empty($comment)) {
									echo "";
								} else {
									echo "<div class='appointmentinformation'>";
								}
																
								// Wenn vorhanden: Ausgabe Terminzeit
								if($row['time_set'] == 'true') {
									echo "<div class='time'><i class='fas fa-clock'></i> ".$t_time[$language]."</div>";
								}
								
								// Wenn vorhanden: Ausgabe Terminort
								if(!empty($location)) {
									echo "<div class='location'><i class='fas fa-map-marker-alt'></i> ".htmlspecialchars($location)."</div>";
								}
								
								// Wenn vorhanden: Ausgabe Terminkommentar
								if(!empty($comment)) {
									echo "<div class='comment'><i class='fas fa-comment'></i> ".htmlspecialchars($comment)."</div>";
								}
								
								// Prüfung, ob zum Termin eine Uhrzeit, ein Ort oder ein Kommentar vorhanden ist
								if($row['time_set'] == "false" and empty($location) and empty($comment)) {
									echo "";
								} else {
									echo "</div>"; // Ende <div class='appointmentinformation'>
								}
								
								echo "</div>"; // Ende <div class='appointment'>
							}
						}
						echo "</div></div>"; // Ende von .col-md-1
						
						$date = strtotime('+1 day', $date);
					} // Ende von while ($date <= $last_day_of_month)
				?>
			</div> <?php // Ende von .row.calendar-cols ?>
			<script>
				// Zeigt "Nach oben"-Button an, wenn ein Benutzer 100 oder mehr Pixel runterscrollt
				window.onscroll = function() {scrollFunction()};

				function scrollFunction() {
					if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
						document.getElementById("myBtn").style.display = "block";
					} else {
					 	document.getElementById("myBtn").style.display = "none";
					}
				}

				// Wenn ein Benutzer auf den Button klickt, landet er zuoberst der Seite
				function topFunction() {
					document.body.scrollTop = 0; // Für Chrome, Safari und Opera 
					document.documentElement.scrollTop = 0; // Für Internet Explorer und Firefox
				} 
			</script>
			<button onclick="topFunction()" id="myBtn">
				<i class='fas fa-chevron-up'></i> 
				<?php echo $t_back_to_top[$language] ?>
			</button> 
		</div> <?php // Ende von .calendar-container ?>
	</body>
</html>

<script>
$(document).ready(function(){
    $('[data-toggle="popover"]').popover();
});
</script>