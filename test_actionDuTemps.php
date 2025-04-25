php
<?php

function testActionDuTemps() {
    // Database connection
    $bdd = new PDO('mysql:host=localhost;dbname=virtual_trader;charset=utf8', 'root', '');

    // Test 1: Check month increment
    $req = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
    $req->execute();
    $gameStateBefore = $req->fetch();

    include 'actionDuTemps.php';

    $req = $bdd->prepare("SELECT current_month, current_year FROM game_state WHERE id = 1");
    $req->execute();
    $gameStateAfter = $req->fetch();

    $oldMonth = $gameStateBefore['current_month'];
    $newMonth = $gameStateAfter['current_month'];

    $test1Result = ($newMonth == $oldMonth + 1);
    if($oldMonth == 12){
      $test1Result = ($newMonth == 1);
    }

    echo "Test 1 (Month Increment): ";
    assertEqual($test1Result, true);

    // Test 2: Check year increment if month was 12
    $oldYear = $gameStateBefore['current_year'];
    $newYear = $gameStateAfter['current_year'];
    $test2Result = true;
    if($oldMonth == 12){
        $test2Result = ($newYear == $oldYear + 1);
    } else {
        $test2Result = ($newYear == $oldYear);
    }

    echo "Test 2 (Year Increment): ";
    assertEqual($test2Result, true);
    
    // Test 3: check if the month is set to 1 if the month was 12
    $test3Result = true;
    if($oldMonth == 12){
      $test3Result = ($newMonth == 1);
    }
    echo "Test 3 (month is 1 if it was 12): ";
    assertEqual($test3Result, true);

    // Test 4: Check if stock prices have changed
    $req = $bdd->prepare("SELECT prix FROM actions");
    $req->execute();
    $pricesBefore = $req->fetchAll(PDO::FETCH_COLUMN);
    
    include 'actionDuTemps.php';

    $req = $bdd->prepare("SELECT prix FROM actions");
    $req->execute();
    $pricesAfter = $req->fetchAll(PDO::FETCH_COLUMN);

    $test4Result = false;
    for ($i=0;$i<count($pricesBefore); $i++){
      if($pricesBefore[$i] != $pricesAfter[$i]){
        $test4Result = true;
      }
    }

    echo "Test 4 (Stock Prices Changed): ";
    assertEqual($test4Result, true);

    //reset the game state for next test
    $req = $bdd->prepare("UPDATE game_state SET current_month = ?, current_year = ? WHERE id = 1");
    $req->execute([$oldMonth, $oldYear]);
}

function assertEqual($actual, $expected) {
    if ($actual === $expected) {
        echo "passed<br>";
    } else {
        echo "failed<br>";
    }
}

testActionDuTemps();

?>