<!DOCTYPE html>
<html>

  <head>
    <title>Site Visits Report</title>
  </head>

  <body>

      <h1>Site Visits Report</h1>

      <table border = '1'>
        <tr>
          <th>No.</th>
          <th>Visitor</th>
          <th>Total Visits</th>
        </tr>

        <?php
            $user = "moises2";
            $password = "Moi7ad:)99";
            $database = "bbdd1";

            try {

                $db = new PDO("mysql:host=127.0.0.1;dbname=$database", $user, $password);

                $siteVisitsMap = 'siteStats';

                $i = 1;
                foreach($db->query("SELECT ip, visitas_total FROM visitas") as $row) {
                    echo "<tr>";
                      echo "<td align = 'left'>"   . $i . "."     . "</td>";
                      echo "<td align = 'left'>"   . $row['ip']     . "</td>";
                      echo "<td align = 'right'>"  . $row['visitas_total'] . "</td>";
                    echo "</tr>";

                    $i++;
                }

            } catch (Exception $e) {
                echo $e->getMessage();
            }

        ?>

      </table>
  </body>

</html>

