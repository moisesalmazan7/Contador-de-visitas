<?php
    $user = "moises2";
    $password = "Moi7ad:)99";
    $database = "bbdd1";
    $table = "visitas";

    try {

        $db = new PDO("mysql:host=mysql;dbname=$database", $user, $password);

        $siteVisitsMap  = 'siteStats';
        $visitorHashKey = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

           $visitorHashKey = $_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

           $visitorHashKey = $_SERVER['HTTP_X_FORWARDED_FOR'];

        } else {

           $visitorHashKey = $_SERVER['REMOTE_ADDR'];
        }

        $totalVisits = 0;
	$query = $db->prepare("SELECT ip, visitas_total FROM visitas WHERE ip = :ip");
	$query->bindParam(":ip",$visitorHashKey);
	$query->execute();
	$fila = $query->fetch(PDO::FETCH_ASSOC);

	if ($fila){
		$totalVisits = $fila['visitas_total']+1;
		$sql = "UPDATE visitas SET visitas_total = :visitas WHERE ip = :ip";
		echo "Tu IP es ".$visitorHashKey." y el número total de visitas es de: ".$totalVisits.".";

        } else {

            $totalVisits = 1;
	    $sql = "INSERT INTO visitas (ip, visitas_total) VALUES (:ip,:visitas)",
	    echo "IP ".$visitorHashKey." añadida. Total visitas: ".$totalVisits.".";
        }

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':ip', $visitorHashKey);
        $stmt->bindParam(':visitas', $totalVisits);
        $stmt->execute();

    } catch (Exception $e) {
        echo $e->getMessage();
    }
?>
