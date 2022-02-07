# Contador-de-visitas

Lo primero que necesitamos para crear un contador de visitas en Redis y MySQL es una plataforma LAMP en Ubuntu 20.04, que es el sistema operativo que vamos a utilizar.

Para ello necesitamos:

- Linux para el sistema operativo base;
- Apache para el servidor web;
- MySQL o MariaDB para el sistema gestor de bases de datos;
- PHP, Perl o Python para el lenguaje de programación en el lado del servidor (back-end).

Primero de todo instalamos apache:
sudo apt install apache2.

Permitimos tráfico http y https:
sudo ufw allow in "Apache".

Segundo paso es instalar MySQL:
sudo apt install mysql-server.

Tercer paso, instalar PHP:
sudo apt install php libapache2-mod-php php-mysql

Cuarto paso, crear un host virtual para nuestro sitio web:
crearemos una carpeta en /var/www con el nombre de nuestro dominio, cambiaremos el propietario de la carpeta con la siguiente orden:
sudo chown -R $USER:$USER /var/www/your_domain

Y en el fichero /etc/apache2/sites-available/your_domain.conf pondremos la siguiente configuración básica:
<VirtualHost *:80>
    ServerName your_domain
    ServerAlias www.your_domain
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/your_domain
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

Habilitamos el sitio y configuramos un index.html básico:
sudo a2ensite your_domain
nano /var/www/your_domain/index.html
<h1>It works!</h1>
<p>This is the landing page of <strong>your_domain</strong>.</p>

Una vez hecho esto, entraríamos al navegador con http://server_domain_or_IP y se verá una página como la siguiente:
![imagen](https://user-images.githubusercontent.com/63236552/152823944-a3c61a11-6f16-4821-b40e-3e36d0a13ffd.png)

Para probar que tenemos php funcionando crearíamos un archivo en nano /var/www/your_domain/info.php con el siguiente código:
<?php
phpinfo();

Accediendo a nuestro navegador con http://server_domain_or_IP/info.php debería aparecer lo siguiente:
![imagen](https://user-images.githubusercontent.com/63236552/152824373-52d3ecdf-4efd-473d-8241-eadada513a93.png)

Por último, probaremos el procesamiento de PHP en nuestro servidor web:
Establecemos conexión con la consola de MySQL usando la cuenta root:
sudo mysql

Creamos una base de datos nueva:
mysql> CREATE DATABASE example_database;

Creamos un usuario con su contraseña:
mysql> CREATE USER 'example_user'@'%' IDENTIFIED WITH mysql_native_password BY 'password';

Le damos permiso al usuario de la base de datos:
mysql> GRANT ALL ON example_database.* TO 'example_user'@'%';

Creamos una tabla de prueba, le insertamos valores, y los vemos por pantalla:
mysql> CREATE TABLE example_database.todo_list (
	item_id INT AUTO_INCREMENT,
	content VARCHAR(255),
	PRIMARY KEY(item_id)
);

mysql> INSERT INTO example_database.todo_list (content) VALUES ("My first important item");

mysql> SELECT * FROM example_database.todo_list;

Con esta última orden, deberá ver lo siguiente:

![imagen](https://user-images.githubusercontent.com/63236552/152825223-d2abd2d4-7174-46c1-a955-4cc69c1012cf.png)

Por último crearemos el siguiente fichero /var/www/your_domain/todo_list.php y le pondremos el siguiente código:
<?php
$user = "example_user";
$password = "password";
$database = "example_database";
$table = "todo_list";

try {
  $db = new PDO("mysql:host=localhost;dbname=$database", $user, $password);
  echo "<h2>TODO</h2><ol>";
  foreach($db->query("SELECT content FROM $table") as $row) {
    echo "<li>" . $row['content'] . "</li>";
  }
  echo "</ol>";
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}

Si accedemos a la siguiente dirección: http://your_domain/todo_list.php, veríamos lo siguiente:
![imagen](https://user-images.githubusercontent.com/63236552/152825487-f1d0998c-18b0-4b47-acf9-9ac4b8824986.png)


El siguiente paso será configurar el contador de visitas a un sitio web con Redis y PHP en Ubuntu 20.04.

Instalamos la extensión PHP Redis:
sudo apt install -y php-redis

Una vez instalado, creamos el siguiente fichero:
sudo nano /var/www/html/test.php

Le ponemos el siguiente código:
<?php
  require_once 'hit_counter.php';
?>

<!DOCTYPE html>
<html>

  <head>
    <title>Sample Test Page</title>
  </head>

  <body>
    <h1>Sample test page</h1>
    <p>This is a sample test page.</p>
  </body>

</html>

El siguiente paso será crear un scirpt de contador de visitas de Redis:
Creamos el siguiente fichero:
sudo nano /var/www/html/hit_counter.php

Le ponemos el siguiente código sustityendo EXAMPLE_PASSWORD por nuestra contraseña del servidor de Redis:
<?php

    try {

        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('EXAMPLE_PASSWORD');

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

        if ($redis->hExists($siteVisitsMap, $visitorHashKey)) {

            $visitorData = $redis->hMget($siteVisitsMap,  array($visitorHashKey));
            $totalVisits = $visitorData[$visitorHashKey] + 1;

        } else {

            $totalVisits = 1;

        }

        $redis->hSet($siteVisitsMap, $visitorHashKey, $totalVisits);

        echo "Welcome, you've visited this page " .  $totalVisits . " times\n";

    } catch (Exception $e) {
        echo $e->getMessage();
    }


El siguiente paso será crear un script de informe de estadísitcas del sitio.
Creamos el siguiente fichero:
sudo nano /var/www/html/log_report.php

Le ponemos el siguiente código: Volvemos a sustituir EXAMPLE_PASSWORD por nuestra contraseña del servidor de Redis.
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

            try {

                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->auth('EXAMPLE_PASSWORD');

                $siteVisitsMap = 'siteStats';                          

                $siteStats = $redis->HGETALL($siteVisitsMap);

                $i = 1; 

                foreach ($siteStats as $visitor => $totalVisits) {

                    echo "<tr>";
                      echo "<td align = 'left'>"   . $i . "."     . "</td>";
                      echo "<td align = 'left'>"   . $visitor     . "</td>";
                      echo "<td align = 'right'>"  . $totalVisits . "</td>";
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

Para probar el contador de visitas de Redis accederíamos a http://your-server-IP/test.php y nos saldría lo siguiente:
![imagen](https://user-images.githubusercontent.com/63236552/152826930-4ae08160-b6fb-4f29-8d44-853f5448e1fc.png)

Visitando la siguiente url: http://your-server-IP/log_report.php, nos aparecería esto:
![imagen](https://user-images.githubusercontent.com/63236552/152827038-c6841a92-2bc9-4e7a-a1d2-e31b19dcd7c1.png)


Una vez configurado un funcionando nuestro contador de visitas en Redis, deberíamos adaptarlo a MySQL, deberíamos modificar los archivos hit_counter.php, log_report.php y test.php ya sea bien comentado el código antiguo de Redis, o duplicando esos archivos cambiandoles el nombre, y adaptar el código a MySQL  y crear una nueva base de datos, con una nueva tabla, y agregarle los campos id, ip y visitas_total como hemos hecho anteriormente.

El código final de estos tres ficheros lo tenemos subido al repositorio.

Accediendo a la misma url que con Redis,  http://your-server-IP/log_report.php nos debería aparecer el informe de visitas del contador en MySQL.






