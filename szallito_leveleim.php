<?php
if(!isset($_SESSION)) {
    session_start(); 
}

if (!isset($_SESSION['username'])) {
  header("location: index.php");
}

require ('includes/db_inc.php'); 

$query = "SELECT szallito_level.szallitolevel_sorszam,szallito_level.szallitolevel_id, szallito_level.datum, szallito_level.brutto_osszesen, users.name FROM szallito_level JOIN users ON szallito_level.vevo_id = users.id WHERE szallito_ceg_id = ? AND szallito_level.status = 1";
$stmt = $conn->prepare($query);
$usr_id = test_input($_SESSION['usr_id']);
$stmt->bind_param('i',$usr_id);
$stmt->execute();
$result = $stmt->get_result();
$total_rows = $result->num_rows;

$stmt->close();
$conn ->close();

include "./includes/header_raktar.php";
?>

    <br>
    <table id="data-table" class="table table-bordered table-stpiped table-resposive table-hover">
      <thead>
          <tr>
              <th>Szállító száma</th>
              <th>Dátum</th>
              <th>Átvevő</th>
              <th>Összesen</th>
              <th>PDF</th>
              <th>Módosít</th>
          </tr>
      </thead>
      <?php
      
        if ($total_rows > 0) {
            foreach ($result as $row) {
                echo '
                    <tr>
                        <td>' . test_input($row["szallitolevel_sorszam"]) . '</td>
                        <td>' . test_input($row["datum"]) . '</td>
                        <td>' . test_input($row["name"]) . '</td>
                        <td>' . test_input($row["brutto_osszesen"]) . '</td>
                        <td><a href="table_w.php?pdf=1&id=' . test_input($row["szallitolevel_id"]) . '">PDF</a></td>
                        <td><a href="update.php?update=1&id=' . test_input($row["szallitolevel_id"]) . '"><span><img class="full" src="pic/edit.png"></span></a></td></a></td>
                    </tr>
                ';
            }
        }
      ?>
    </table>

  </div> <!----- konténer vége ----->
   
  <?php
    include "footer.php";
  ?>
  <script>
      $(function() {
       
        var table = $('#data-table').dataTable({
            "order": [[ 1, "desc" ]],
            "pageLength": 10,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Hungarian.json"
            }
        });

      });    
  </script>     
 </body>
</html>    