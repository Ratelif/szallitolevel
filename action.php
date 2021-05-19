<?php
  
   if(!isset($_SESSION)) {
       session_start(); 
   }
   
   if (!isset($_SESSION['username'])) {
     header("location: index.php");
   }
   
   require ('includes/db_inc.php'); 

    //  --------------------------- kereső ------------------------------
    if (isset($_POST['query'])) {
       
        $inpText = test_input($_POST['query']);
        $query = "SELECT * FROM cikktorzs WHERE vonalkod  LIKE '$inpText%' ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        // csak egy találat van
        if ($result->num_rows == 1) 
        {   
            $row=$result->fetch_assoc(); 
              
            print_r(json_encode($row));
          
        }
        // több találat van
        else if ($result->num_rows > 1) 
        {
           
            while($row=$result->fetch_assoc()) {
                
                echo '<a href="#" class="list-group-item list-group-item-action border-01" data-afa='.$row["afa"].' data-ar='.$row["ar"].' data-cikkszam='.$row["cikkszam"].'>'.$row['cim'].'</a>' ;
                
            }
        } 
        // ha nincs találat
        else
        {
            echo "<p class='list-group-item border-1'>Nincs találat!</p>" ;
        }
    }

    //  --------------- vevő kiválasztásának adatai ------------------------------
    if (isset($_POST['user_id'])) 
    {
        $user_id = test_input($_POST['user_id']);
        $query = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt ->bind_param('i',$user_id);
        $stmt -> execute();
        $result = $stmt -> get_result();
        // $stmt->close();

        if ($result->num_rows > 0) 
        {
            $row=$result->fetch_assoc();
            exit(json_encode(array("username"=>$row['username'],"name"=>$row['name'],"address_city"=>$row['address_city'],"address_street"=>$row['address_street'])));
           
        }
        else 
        {
            echo "Database error!";
        }
    }

// -------------------------- szallitólevél tábla letárolása ------------------
if (isset($_POST["invoice_number"], $_POST["szallito_id"],$_POST["vevo_id"] )) {
    
    $query = "INSERT INTO szallito_level(pure_sorszam, szallitolevel_sorszam, szallito_ceg_id, vevo_id, total_item, brutto_osszesen, db_osszesen) VALUES(?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    $stmt -> bind_param("isiiiii",test_input($_POST["pure_sorszam"]),test_input($_POST["invoice_number"]),test_input($_POST["szallito_id"]),test_input($_POST["vevo_id"]),test_input($_POST["total_item"]),test_input($_POST["osszesen"]), test_input($_POST["db_osszesen"]));
    if(!$stmt->execute()){
        echo 'DatabeseError'.mysqli_error($conn);
    }

    // ------- az utolsó, adatbázisba történő tárolás id-jának kiválasztása -----------
        $order_id = $conn->insert_id;
        $stmt->close();
    

     // ---------------------- szallito_level_details tábla letárolása  ----------------
        for ($count = 0; $count < $_POST["total_item"]; $count++) {
           
            $query = "
                 INSERT INTO szallito_level_details (
                     szallitolevel_id, vonalkod_id, cikktorzs_id, termek_ara, termek_db, sor_osszesen)
                 VALUES (?,?,?,?,?,?)";
            $stmt= $conn->prepare($query);     
            $stmt->bind_param('iiiiii',$order_id,test_input($_POST["vkod_beiras"][$count]),test_input($_POST["cikkszam"][$count]),test_input($_POST["ar_brutto"][$count]),test_input($_POST["darab_beiras"][$count]), test_input($_POST["sor_ertek"][$count]));
            if(!$stmt->execute()){
                echo 'DatabeseError'.mysqli_error($conn);
            }
            $stmt->close();
        };  
        
        header("Location: index.php?storage=Success");
        // exit(json_encode(array('status'=>1, 'msg'=>'Az adatok tárolása sikeresen megtörtént!')));
} 


    // ------------------------- szállító tábla update --------------------
    if (isset($_POST['db_osszesen'],$_POST['update_invoice'],$_POST['total_item']) ){

        // update szallito_level
        $db_osszesen = test_input($_POST['db_osszesen']);
        $osszesen = test_input($_POST['osszesen']);
        $total_item = test_input($_POST['total_item']);
        $vevo_id = test_input($_POST['vevo_id']);
        $szallitolevel_id = test_input($_SESSION['szallitolevel_id']);

        $update_query = "UPDATE szallito_level SET db_osszesen = ?, brutto_osszesen = ?, total_item = ?, vevo_id = ? WHERE szallitolevel_id = ?";  
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('iiiii', $db_osszesen, $osszesen, $total_item, $vevo_id, $szallitolevel_id);
        $stmt->execute();

        if(mysqli_stmt_affected_rows($stmt) < 1 ){
            echo "A frissítés során hiba lépett fel! ". mysqli_error($conn);
        }
        $stmt->close();

        // delete everything from szallito_level_details
        $delete_query = "DELETE FROM szallito_level_details WHERE szallitolevel_id = ?" ;
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i",$_SESSION['szallitolevel_id']);
        $stmt->execute();
        if(mysqli_stmt_affected_rows($stmt) < 1 ){
            echo "Nem sikerült az adatok törlése! ". mysqli_error($conn);
        }
        $stmt->close();

        // INSERT INTO szallito_level_details
        for ($count = 0; $count < $_POST["total_item"]; $count++) {
            $szallitolevel_id = test_input($_SESSION['szallitolevel_id']);
            $vkod_beiras = test_input(test_input($_POST["vkod_beiras"][$count]));
            $cikkszam = test_input($_POST["cikkszam"][$count]);
            $ar_brutto = test_input($_POST["ar_brutto"][$count]);
            $darab_beiras = test_input($_POST["darab_beiras"][$count]);
            $sor_ertek = test_input(test_input($_POST["sor_ertek"][$count]));

            $query = ("INSERT INTO szallito_level_details (szallitolevel_id, vonalkod_id, cikktorzs_id, termek_ara, termek_db, sor_osszesen)VALUES (?,?,?,?,?,?)");
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiiiii", $szallitolevel_id,$vkod_beiras,$cikkszam,$ar_brutto,$darab_beiras,$sor_ertek) ;  
            $stmt->execute(); 
            
            if(mysqli_stmt_affected_rows($stmt) < 1 ){
                echo "Az adatok tárolása közben hiba lépett fel! ". mysqli_error($conn);
            }
            $stmt->close();
        };  
        
        header("Location: index.php?Update=Success");
        
    }

?>