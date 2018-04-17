<!DOCTYPE html>
<?php
include_once("main.php");
?>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="style.css" />
        <?php
            
            $items[0]['id'] = '1';
            $items[0]['name'] = 'btc-grs';
            $items[1]['id'] = '2';
            $items[1]['name'] = 'btc-neo';
            $items[2]['id'] = '3';
            $items[2]['name'] = 'btc-xrp';
            $items[3]['id'] = '4';
            $items[3]['name'] = 'btc-xvg';
            $items[4]['id'] = '5';
            $items[4]['name'] = 'btc-omg';
        ?>
    </head>
    <body>
        <div align="center" id="ziel">
        <table id='ziel'>
            <tr>
                <th><p class='green'>id</p></th>
                <th>name</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?>
                    <td><?php echo $item['name']; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
            $coin = new \Coin();
            $coin->showCoins();
        ?>
        </div>
    </body>
</html>