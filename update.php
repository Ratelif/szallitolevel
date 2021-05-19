<?php
if (!isset($_SESSION)) {
  session_start();
}

if (!$_SESSION['username']) {
  header("location: index.php");
}

$szallito_ceg_nev = "";
$szallito_ceg_city = "";
$szallito_ceg_street = "";
$vevo_ceg_nev = "";
$vevo_ceg_city = "";
$vevo_ceg_street = "";
$szallito_sorszam = "";
$osszes_sor = "";
$brutto_osszesen = "";
$vevo_id = "";
$szallito_level_id = "";

include 'includes/db_inc.php';

if (isset($_GET['update'], $_GET['id'])) {
  // --------- szállító cég paraméterei -----------------
  $query = "SELECT users.name, users.address_city, users.address_street, szallito_level.szallitolevel_id FROM szallito_level JOIN users ON szallito_level.szallito_ceg_id = users.id WHERE szallitolevel_id = ? ";
  $stmt = $conn->prepare($query);
  $szallito_id = test_input($_GET['id']);
  $stmt->bind_param('s', $szallito_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_rows = $result->num_rows;
  $szallito_ceg = $result->fetch_assoc();

  $szallito_ceg_nev = $szallito_ceg['name'];
  $szallito_ceg_city = $szallito_ceg['address_city'];
  $szallito_ceg_street = $szallito_ceg['address_street'];
  $szallito_level_id = $szallito_ceg['szallitolevel_id'];
  $_SESSION['szallitolevel_id'] = $szallito_ceg['szallitolevel_id'];
  $stmt->close();


  // --------- Vevő cég paraméterei és a szállító bruttó, darab, összes_sor -------------
  $query = "SELECT szallito_level.szallitolevel_id,szallito_level.szallitolevel_sorszam, szallito_level.szallito_ceg_id, szallito_level.vevo_id,szallito_level.total_item, szallito_level.brutto_osszesen, szallito_level.db_osszesen, users.name, users.address_city, users.address_street FROM szallito_level JOIN users ON szallito_level.vevo_id = users.id WHERE szallitolevel_id = ? ";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('s', $_GET['id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_rows = $result->num_rows;

  $vevo_and_details = $result->fetch_assoc();
  $vevo_id = $vevo_and_details['vevo_id'];
  $vevo_ceg_nev = $vevo_and_details['name'];
  $vevo_ceg_city = $vevo_and_details['address_city'];
  $vevo_ceg_street = $vevo_and_details['address_street'];
  $szallito_sorszam = $vevo_and_details['szallitolevel_sorszam'];
  $osszes_sor = $vevo_and_details['total_item'];
  $brutto_osszesen = $vevo_and_details['brutto_osszesen'];
  $db_osszesen = $vevo_and_details['db_osszesen'];
  $stmt->close();

  $sorok = 1;
}
include "./includes/header_user.php";
?>

<div class="row">
  <div class="col-md-12" align="right"><u>Szállító sorszáma:</u> <?php echo $szallito_sorszam; ?></div>
</div>
<div class="row justify-content-center">
  <div class="col-md-6 col-md-offset-3" align="center">
    <h2 style="margin-bottom: 2rem;">Visszáru szállítólevél</h2>
  </div>
</div>

<div class="row justify-content-around">
  <div class="col-md-4" style="background-color: rgb(212, 211, 211);">
    <h5 align="left"><u>Átadó:</u></h5>
    <p align="left"><?php echo $szallito_ceg_nev; ?></p>
    <p align="left"><?php echo $szallito_ceg_city; ?></p>
    <p align="left"><?php echo $szallito_ceg_street; ?></p>

  </div>
  <div class="col-md-4" style="background-color: rgb(212, 211, 211);">
    <h5 align="left"><u>Átvevő:</u></h5>
    <p align="left">
    <form action="update.php" method="POST">
      <select class="select" id='user_kivalsztas' name="felhasznalok">
        <?php
        // user list 
        listOfUser();
        ?>
      </select>
      <span id="kivalasztas"> Kiválasztás</span>
    </form>
    </p>
    <p align="left" id="cim_varos"></p>
    <p align="left" id="cim_utca"></p>
  </div>
</div>
<form method="POST" id="update_form" action="action.php">
  <table class="table table-resposive table-hover">

    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Vonalkód</th>
        <th scope="col">Cikkszám</th>
        <th scope="col">Cím</th>
        <th scope="col">db</th>
        <th scope="col">Ár</th>
        <th scope="col">ÁFA</th>
        <th scope="col">Érték</th>
        <th scope="col"></th>
      </tr>
    </thead>
    <tbody id="uj_sor_id">

      <?php

      $szallito_details_select = "SELECT szallito_level_details.szallitolevel_id, szallito_level_details.vonalkod_id,szallito_level_details.termek_ara,szallito_level_details.termek_db,szallito_level_details.sor_osszesen,cikktorzs.vonalkod,cikktorzs.cikkszam,cikktorzs.cim,cikktorzs.afa FROM `szallito_level_details` JOIN cikktorzs ON szallito_level_details.cikktorzs_id = cikktorzs.cikkszam AND szallito_level_details.vonalkod_id = cikktorzs.vonalkod WHERE szallito_level_details.szallitolevel_id = ? ";
      $stmt = $conn->prepare($szallito_details_select);
      $stmt->bind_param('s', $szallito_level_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $sorokszama = $result->num_rows;
      $data = $result->fetch_all(MYSQLI_ASSOC);
      $sorok = 0;
      if ($sorokszama > 0) {
        foreach ($data as $row) {
          $sorok = $sorok + 1;
      ?>

          <tr data-tr-id="<?php echo $sorok; ?>" id="row_id_<?php echo $sorok; ?>">
            <td scope="row" id="sorok_szama<?php echo $sorok; ?>"><?php echo $sorok ?></td>
            <td><input type="text" id="vonalkod<?php echo $sorok; ?>" name="vkod_beiras[]" class="input_szimpla w-100" required readonly value=<?php echo $row['vonalkod_id'] ?>></td>
            <td><input type="text" id="cikkszam<?php echo $sorok; ?>" name="cikkszam[]" class="input_szimpla w-100" required readonly value=<?php echo $row['cikkszam'] ?>></td>
            <td id="cim<?php echo $sorok ?>"><?php echo $row['cim'] ?></td>
            <td><input type="text" id="darab<?php echo $sorok; ?>" class="mennyiseg w-100" data-id="<?php echo $sorok; ?>" value=<?php echo $row['termek_db'] ?> name="darab_beiras[]" required /></td>

            <td><input type="text" id="ar<?php echo $sorok ?>" name="ar_brutto[]" readonly value=<?php echo $row['termek_ara'] ?> class="w-100 input_szimpla" /></td>
            <td id="afa<?php echo $sorok ?>"><?php echo $row['afa'] ?></td>
            <td><input type="text" name="sor_ertek[]" readonly class="input_szimpla w-100" value=<?php echo $row['sor_osszesen'] ?> id="sor_ertek<?php echo $sorok ?>" /></td>
            <td><button type="button" name="remove_row" id="<?php echo $sorok ?>" class="btn btn-danger btn-xs remove_row">X</button></td>
          </tr>

      <?php
        }
      }

      $stmt->close();

      ?>

    </tbody>
    <tr>
      <th scope="row"></th>
      <td></td>
      <td></td>
      <td align="left"><b>Összesen:</b></td>
      <td><input type="text" id="db_osszesen" name="db_osszesen" value=<?php echo $db_osszesen; ?> readonly class="input_szimpla w-100" /></td>
      <td></td>
      <td></td>
      <td><input type="text" id="osszesen" name="osszesen" value=<?php echo $brutto_osszesen; ?> readonly class="input_szimpla w-100" /></td>
    </tr>

  </table>
  <div id="result"></div>
  <div align="center">
    <button type="button" name="add_row" id="add_row" class="btn btn-info">Új tétel</button>

    <input type="submit" name="update_invoice" id="update_invoice" class="btn btn-info" value="Lezárás" />
    <input type="hidden" name="total_item" id="total_item" value="1" />
    <input type="hidden" name="szallito_id" id="szallito_id" value="" />
    <input type="hidden" name="vevo_id" id="vevo_id" value="" />
  </div>
</form>

</div>
<!--------------- teljes konténer vége -------------------------->

<?php
include "footer.php";
?>

<script>
  // sorok számozása:
  var sorok_szamozasa = <?php echo $osszes_sor; ?>;
  var vevo_azonosito = "";

  // a sor id tárolása, amire klikkeltünk:
  var sor_szamozas_id = "";

  $(function() {

    // -------------------  Új sor hozzáadása a szállítóhoz -----------------------
    $('#add_row').click(function() {

      sorok_szamozasa = sorok_szamozasa + 1;
      var html_code = "";
      html_code += '<tr data-tr-id=' + sorok_szamozasa + ' id="row_id_' + sorok_szamozasa + '">';
      html_code += '<td scope="row" id="sorok_szama' + sorok_szamozasa + '">' + sorok_szamozasa + '</td>';
      html_code += '<td><input type="text" class="input_class w-100" required id="vonalkod' + sorok_szamozasa + '" name="vkod_beiras[]"></td>';
      html_code += '<td><input type="text" id="cikkszam' + sorok_szamozasa + '" name="cikkszam[]" readonly class="w-100 cikkszam input_szimpla" /></td>';
      html_code += '<td id="cim' + sorok_szamozasa + '"></td>';
      html_code += '<td><input type="text" class="mennyiseg w-100" id="darab' + sorok_szamozasa + '" required data-id=' + sorok_szamozasa + ' name="darab_beiras[]"></td>';
      html_code += '<td><input type="text" id="ar' + sorok_szamozasa + '" name="ar_brutto[]" readonly class="input_szimpla w-100" /></td>';
      html_code += '<td id="afa' + sorok_szamozasa + '"></td>';
      html_code += '<td><input type="text" class="input_szimpla w-100" id="sor_ertek' + sorok_szamozasa + '" required data-id=' + sorok_szamozasa + ' name="sor_ertek[]"></td>';
      html_code += '<td><button type="button" name="remove_row" id="' + sorok_szamozasa + '" class="btn btn-danger btn-xs remove_row">X</button></td>';
      html_code += '/tr>';
      $('#uj_sor_id').append(html_code);

    });

    // -----------------    Vevő kiválasztása:  --------------------------
    var vevoID = <?php echo $vevo_id; ?>;
    var vevo_city = <?php echo json_encode($vevo_ceg_city); ?>;
    var vevo_street = <?php echo json_encode($vevo_ceg_street); ?>;

    // a vevő nevének kiírása: 
    document.getElementById("user_kivalsztas").value = vevoID;

    // vevő címének kiíratása:
    $('#cim_varos').text(vevo_city);
    $('#cim_utca').text(vevo_street);

    vevo_azonosito = <?php echo $vevo_id; ?>;
    $('select').on('change', function() {
      $('#kivalasztas').hide();
      var userID = this.value;

      $.ajax({
        url: 'action.php',
        type: 'POST',
        data: {
          user_id: userID
        },
        dataType: 'JSON',
        success: function(response) {
          $('#cim_varos').text(response.address_city);
          $('#cim_utca').text(response.address_street);
        }
      });
      vevo_azonosito = userID;
    });

    //  ----------  Szállító lezárása -----------------------
    $('#update_invoice').click(function() {
      if (!vevo_azonosito) {
        alert('Kérem válasszon ki egy vevőt!');
        return false;
      }

      var sorok_szama = $('#uj_sor_id tr').length;

      var szallito_azonosito = "<?php echo $_SESSION['usr_id']; ?>";

      if (vevo_azonosito == szallito_azonosito) {
        alert('Kérem válasszon ki egy másik vevőt!');
        return false;
      }

      $('#total_item').val(sorok_szama);
      $('#szallito_id').val(szallito_azonosito);
      $('#vevo_id').val(vevo_azonosito);


      // ------------ form küldése: ----------------------------------
      $('#update_form').submit(function() {
        $('input[type="number"]').each(function() {
          if ($(this).val().length == 0) {
            alert('Minden mező kitöltése kötelező!');
            $('#update_form').off('submit');
            return false;
          }
        });

      });

    });

  });
</script>
<script defer src="./js/main.js"></script>
</body>

</html>

<?php

$conn->close();

?>