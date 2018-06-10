<!DOCTYPE html>
<?php
include_once("main.php");
?>
<html>
    <head>
        <meta charset="utf8">
        <link rel="stylesheet" type="text/css" href="style.css" />
        
    </head>
    <body>
        <div align="center">
            <h1 class='ziel'><br>Shitcoin Analyser V1.1<br><br></h1>
        <?php
            $coin = new \Coin();
            $coin->showCoins();
        ?>
        </div>
    </body>
</html>