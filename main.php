<?php

error_reporting(E_ERROR | E_WARNING);
set_time_limit(60 * 5);
include 'db.php';

class Coin
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = new \PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_LOGIN, DB_PASSWORD);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo 'Connection failed: '.$e->getMessage();
        }
    }
    
    public function addReport()
    {
        foreach ($this->getCoins() as $coin) {
            $data = $this->prepareData($coin);
            $statement = $this->db->prepare('INSERT INTO `report_coins` (`coin`, `value`, `after_halfhr`, `after_hr`, `after_2hrs`, `after_5hrs`, `after_day`, `handluj`) '
                    . 'VALUES('.$coin['id'].', '.$data['value'].', '.$data['after_halfhr'].', '.$data['after_hr'].', '.$data['after_2hrs'].', '.$data['after_5hrs'].', '.$data['after_day'].', '.$data['handluj'].')');
            $statement->execute();
            //-----------------------------------
            //$statement = $this->db->prepare('UPDATE `template` SET value='.$data["value"]' WHERE id='.$coin["id"]);
            $statement = $this->db->prepare('UPDATE template SET value="'.$data['value'].'", after_halfhr="'.$data['after_halfhr'].'", after_hr="'.$data['after_hr'].'", after_2hrs="'.$data['after_2hrs'].'", after_5hrs="'.$data['after_5hrs'].'", after_day="'.$data['after_day'].'", avarage_week="'.$data['avarage_week'].'", deviation="'.$data['deviation'].'", handluj="'.$data['handluj'].'" WHERE id='.$coin['id']);
            $statement->execute();
            
        }
    }

    private function prepareData($coin)
    {
        $marketData = json_decode(file_get_contents('https://bittrex.com/api/v1.1/public/getmarketsummary?market='.$coin['name']), true);
        $value = number_format($marketData['result'][0]['Last'], 8);
        
        $afterHalfHr = $this->calculatePercent($coin, $value, 6);
        $afterHr = $this->calculatePercent($coin, $value, 12);
        $after2Hrs = $this->calculatePercent($coin, $value, 24);
        $after5Hrs = $this->calculatePercent($coin, $value, 60);
        $afterDay = $this->calculatePercent($coin, $value, 288);
        $avarageWeek = $this->calculateAvarage($coin, $value);
        $deviation = $this->calculateDeviation($avarageWeek, $value);
        $handluj = $this->calculateHandluj($coin, $value, 288, $afterHalfHr, $after2Hrs, $deviation);
        
        return [
            'value' => $value,
            'after_halfhr' => $afterHalfHr,
            'after_hr' => $afterHr,
            'after_2hrs' => $after2Hrs,
            'after_5hrs' => $after5Hrs,
            'after_day' => $afterDay,
            'avarage_week' => $avarageWeek,
            'deviation' => $deviation,
            'handluj' => $handluj,
        ];
    }
    
    private function calculateDeviation($avg, $value)
    {
        $dev = (1 - $avg / $value) * 100;
        $dev = round($dev, 2);
        echo "$dev <br>";
        return $dev;
    }
    
    private function calculateAvarage($coin, $value)
    {
        $statement = $this->db->prepare('SELECT `value` FROM `report_coins` WHERE `coin`='.$coin['id'].' ORDER BY `id` DESC LIMIT 0, 6'); // 2016 = 12 * 5 * 7 czyli tyle wpisów w weekend
        $statement->execute();
        $reports = $statement->fetchAll();
        $avg = 0;
        echo $coin['id'].".) ";
        $i = count($reports);
        for($count=$i; $count>=0; --$count){
            $avg = $avg + $reports[$count]['value'];
            echo $reports[$count]['value']."...+... ";
        }
        
        echo "=... $avg dzielone na: $i = ....";
        $avg = $avg / $i;
        $avg = round($avg, 8);
        $avg = number_format($avg, 8);
        //echo $coin['id'];
        echo "$avg , a odchylenie to.....";
        return $avg;
    }
    
    private function calculatePercent($coin, $currentValue, $skip)
    {
        $statement = $this->db->prepare('SELECT `value` FROM `report_coins` WHERE `coin`='.$coin['id'].' ORDER BY `id` DESC LIMIT 0,'.$skip);
        $statement->execute();
        $reports = $statement->fetchAll();
        
        if (!count($reports)) {
            return 0;
        }
        
        $oldValue = $reports[count($reports)-1]['value'];
        //round(5.94032, 2) = 5.94
        return round((1 - $oldValue / $currentValue) * 100, 2);
    }
    
    private function calculateHandluj($coin, $currentValue, $skip, $afterHalfHr, $after2Hrs, $dev)
    {
        $statement = $this->db->prepare('SELECT `value` FROM `report_coins` WHERE `coin`='.$coin['id'].' ORDER BY `id` DESC LIMIT 0,'.$skip);
        $statement->execute();
        $reports = $statement->fetchAll();
        
        if (!count($reports)) {
            return 0;
        }
        /*
        $avg = 0;
        for($count=count($reports)-1; $count>0; --$count){
            $avg = $avg + $reports[$count]['value'];
        }
        $avg = $avg / (count($reports)-1);
        */
        if ($dev>0) {
            $dev=0;
        }
        $handluj=0;
        $handluj=(2*$afterHalfHr)+$after2Hrs+$dev;
        $handluj=$handluj*(-1);
        if($handluj<0){
            $handluj=0;
        }
        if($handluj>100){
            $handluj=100;
        }

       // echo "$handluj <br>";
        return $handluj;
    }
    
    private function getCoins()
    {
        $statement = $this->db->prepare('SELECT * FROM `coins`');
        $statement->execute();
        
        return $statement->fetchAll();
    }
    //---------------------------------------------------------------------
    public function showCoins()
    {
        $statement = $this->db->prepare('SELECT * FROM `template` ORDER BY `handluj` DESC LIMIT 0,138');
        $statement->execute();
        
        $i=1;
        $style="";
        echo "<table border='2'class='tabelka'>";
        echo "<tr class='etykieta'><td>Nr</td><td>Nazwa</td><td>Wartość</td><td>Pół godziny</td><td>Godzina</td><td>2 godziny</td><td>5 godzin</td><td>Dzień</td><td>Śr. tydzień</td><td>Odchylenie od Śr.</td><td>Handluj z tym na:</td></tr>";
        foreach ($statement as $coin) {
            if($coin['handluj']>7){
                $style="handluj";
            }
            elseif($coin['handluj']<2){
                $style="normal";
            }
            else{
                $style="ziel";
            }
            $this->style($coin['after_halfhr']);
            echo "<tr>".
                 "<td>".$i.". </td>".
                 "<td><a href='https://bittrex.com/Market/Index?MarketName=".$coin['name']."'>".$coin['name']."</a></td> ".
                 "<td class='value'>".$coin['value']."</td>".   
                 "<td class='".$this->style($coin['after_halfhr'])."'>".$coin['after_halfhr']."</td>".
                 "<td class='".$this->style($coin['after_hr'])."'>".$coin['after_hr']."</td>".
                 "<td class='".$this->style($coin['after_2hrs'])."'>".$coin['after_2hrs']."</td>".
                 "<td class='".$this->style($coin['after_5hrs'])."'>".$coin['after_5hrs']."</td>".
                 "<td class='".$this->style($coin['after_day'])."'>".$coin['after_day']."</td>".
                 "<td class='value'>".$coin['avarage_week']."</td>".
                 "<td class='".$this->style($coin['deviation'])."'>".$coin['deviation']."</td>".
                 "<td class='".$style."'>".$coin['handluj']."%</td>".
                 "</tr>";
            $i++;
            //".$style."
        }
        echo "</table>";
    }
    //------------------------------------
    private function style($style)
    {
        if($style<-10){
            $style="red";
        }
        elseif($style>10){
            $style="green";
        }
        else{
            $style="";
        }
        return $style;
    }
}






