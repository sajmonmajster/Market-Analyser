<?php

error_reporting(E_ERROR | E_WARNING);

class Coin
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = new \PDO('mysql:host=postgresql-colorful-46818;dbname=sca', 'root', '');
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
            $statement = $this->db->prepare('UPDATE template SET value="'.$data['value'].'", after_halfhr="'.$data['after_halfhr'].'", after_hr="'.$data['after_hr'].'", after_2hrs="'.$data['after_2hrs'].'", after_5hrs="'.$data['after_5hrs'].'", after_day="'.$data['after_day'].'", handluj="'.$data['handluj'].'" WHERE id='.$coin['id']);
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
        $handluj = $this->calculateHandluj($coin, $value, 288, $afterHalfHr, $after2Hrs);
        
        return [
            'value' => $value,
            'after_halfhr' => $afterHalfHr,
            'after_hr' => $afterHr,
            'after_2hrs' => $after2Hrs,
            'after_5hrs' => $after5Hrs,
            'after_day' => $afterDay,
            'handluj' => $handluj,
        ];
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
    
    private function calculateHandluj($coin, $currentValue, $skip, $afterHalfHr, $after2Hrs)
    {
        $statement = $this->db->prepare('SELECT `value` FROM `report_coins` WHERE `coin`='.$coin['id'].' ORDER BY `id` DESC LIMIT 0,'.$skip);
        $statement->execute();
        $reports = $statement->fetchAll();
        
        if (!count($reports)) {
            return 0;
        }
        $avg = 0;
        for($count=count($reports)-1; $count>0; --$count){
            $avg = $avg + $reports[$count]['value'];
        }
        $avg = $avg / (count($reports)-1);
        
        $handluj=0;
        $handluj=(2*$afterHalfHr)+$after2Hrs;
        $handluj=$handluj*(-1);
        if($handluj<0){
            $handluj=0;
        }

        echo "$handluj <br>";
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
        $statement = $this->db->prepare('SELECT * FROM `template` ORDER BY `handluj` DESC LIMIT 0,50');
        $statement->execute();
        echo "elo";
        $i=1;
        $style="";
        echo "<table border='2'>";
        echo "<tr><td>Nr</td><td>Nazwa</td><td>Wartość</td><td>Pół godziny</td><td>Godzina</td><td>2 godziny</td><td>5 godzin</td><td>Dzień</td><td>Handluj z tym na:</td></tr>";
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
            
            echo "<tr>".
                 "<td>".$i.". </td>".
                 "<td><a href='https://bittrex.com/Market/Index?MarketName=".$coin['name']."'>".$coin['name']."</a></td> ".
                 "<td>".$coin['value']."</td>".   
                 "<td>".$coin['after_halfhr']."</td>".
                 "<td>".$coin['after_hr']."</td>".
                 "<td>".$coin['after_2hrs']."</td>".
                 "<td>".$coin['after_5hrs']."</td>".
                 "<td>".$coin['after_day']."</td>".
                 "<td class='".$style."'>".$coin['handluj']."%</td>".
                 "</tr>";
            $i++;
            //".$style."
        }
        echo "</table>";
    }
}






