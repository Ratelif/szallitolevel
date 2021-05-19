<?php
if(!isset($_SESSION)) {
    session_start(); 
}

if (!isset($_SESSION['username'])) {
  header("location: index.php");
}

require ('./includes/db_inc.php'); 

// ------------------  Szallító sorszám beállítása: ---------------------
$user_id = "SELECT max(pure_sorszam) AS sorszam FROM szallito_level WHERE szallito_ceg_id = ? LIMIT 1";
$stmt = $conn->prepare($user_id);
$stmt->bind_param('s',$_SESSION['usr_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc(); 
$stmt->close();
$szam = "";
if($user['sorszam'] > 0) {
  $szam = $user['sorszam'] + 1;  
}
else {
  $szam = 1;  
}

$sorszam = $_SESSION['usr_id']."-".date('Y-m-d')."-".$szam;

$sorok = 1;

// header
include "./includes/header_user.php";
?>

        <div class="row">
            <div class="col-md-12" align="right"><u>Szállító sorszáma:</u> <?php echo test_input($sorszam); ?></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-md-offset-3" align="center">
              <h2 style="margin-bottom: 2rem;">Visszáru szállítólevél</h2>
            </div>
        </div>

        <div class="row justify-content-around">
          <div class="col-md-4" style="background-color: rgb(212, 211, 211);">
            <h5  align="left"><u>Átadó:</u></h5>
            <p align="left"><?php echo test_input($_SESSION['name']); ?></p>
            <p align="left"><?php echo test_input($_SESSION['address_city']); ?></p>
            <p align="left"><?php echo test_input($_SESSION['address_street']); ?></p>
            
          </div>
          <div class="col-md-4" style="background-color: rgb(212, 211, 211);">
            <h5 align="left"><u>Átvevő:</u></h5>
            <p align="left">
            <form action="new_szallito.php" method="POST">  
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
        <form method="POST" id="invoice_form" action="action.php">
          <table class="table table-hover">
              
            <thead>
              <tr>
                <th scope="col">#</th>
                <th class="vonalkod" scope="col">Vonalkód</th>
                <th class="cikkszam" scope="col">Cikkszám</th>
                <th class="cim" scope="col">Cím</th>
                <th class="db" scope="col">db</th>
                <th class="ar" scope="col">Ár</th>
                <th scope="col">ÁFA</th>
                <th class="ertek" scope="col">Érték</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody id = "uj_sor_id">
              <tr data-tr-id= "<?php echo test_input($sorok); ?>" id="row_id_<?php echo test_input($sorok); ?>">
                <td scope="row" id="sorok_szama<?php echo test_input($sorok); ?>"><?php echo test_input($sorok) ?></td>
                <td><input type="text" id="vonalkod<?php echo test_input($sorok); ?>" name="vkod_beiras[]" class="input_class w-100" onwheel="return false;" required></td>
                <td><input type="text" id="cikkszam<?php echo test_input($sorok) ?>" name="cikkszam[]" readonly class="w-100 input_szimpla" /></td>
                <td id="cim<?php echo test_input($sorok) ?>"></td>
                <td><input type="text" id="darab<?php echo test_input($sorok); ?>" class="mennyiseg w-100" data-id="<?php echo $sorok; ?>" name="darab_beiras[]" required/></td>
                <td><input type="text" id="ar<?php echo test_input($sorok) ?>" name="ar_brutto[]" readonly class="input_szimpla w-100" /></td>
                <td id="afa<?php echo test_input($sorok) ?>"></td>
                <td><input type="text" name="sor_ertek[]" readonly class="input_szimpla w-100" id="sor_ertek<?php echo test_input($sorok) ?>" /></td>
                <td><button type="button" name="remove_row" id="<?php echo test_input($sorok) ?>" class="btn btn-danger btn-xs remove_row">X</button></td>
              </tr>
            </tbody>
              <tr>
                <th scope="row"></th>
                <td></td>
                <td></td>
                <td align="left"><b>Összesen:</b></td>
                <td><input type="text" id="db_osszesen" name="db_osszesen" readonly class="input_szimpla w-100" /></td>
                <td></td>
                <td></td>
                <td ><input type="text" id="osszesen" name="osszesen" readonly class="input_szimpla w-100" /></td>
              </tr>
            
          </table>
          <div id="result"></div>
          <div align="center">
            <button type="button" name="add_row" id="add_row" class="btn btn-info">Új tétel</button>
            
            <input type="submit" name="create_invoice" id="create_invoice" class="btn btn-info" value="Lezárás" />
            <input type="hidden" name="total_item" id="total_item" value="1" />
            <input type="hidden" name="invoice_number" id="invoice_number" value="" />
            <input type="hidden" name="szallito_id" id="szallito_id" value="" />
            <input type="hidden" name="vevo_id" id="vevo_id" value="" />
            <input type="hidden" name="pure_sorszam" id="pure_sorszam" value="" />
          </div>
        </form>

    </div> <!-- end of container  -->

   <?php
      include "footer.php";
   ?>
   <script>
      // sorok számozása javascript részben:
      var sorok_szamozasa = <?php echo $sorok; ?>;
      var vevo_azonosito = "";

      // sor id tárolása, amire klikkeltünk:
      var sor_szamozas_id = "";

      $(function() {
       
          // ----------  Új sor hozzáadása a szállítóhoz ---------------
          $('#add_row').click(function(){
           
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


          // --------------    Vevő kiválasztása:  --------------
          $('#user_kivalsztas')[0].selectedIndex = -1;   
          $('select').on('change', function() {          
            $('#kivalasztas').hide();
            var ertek = this.value;
            $.ajax({
              url: 'action.php',
              type: 'POST',
              data:{ 
                user_id: ertek
              },
              dataType: 'JSON',
              success: function (response) {
                $('#cim_varos').text(response.address_city);
                $('#cim_utca').text(response.address_street);
              }
            });
              vevo_azonosito = ertek;
          });

          //  ---------  Szállítólevél lezárása ------------
          $('#create_invoice').click(function(){
            if(!vevo_azonosito)
            {
                alert('Kérem válasszon ki egy vevőt!');
                return false;    
            }  

            var sorok_szama = $('#uj_sor_id tr').length;
            var szallito_sorszam = "<?php echo $sorszam ;?>"; 
            var szallito_azonosito = "<?php echo $_SESSION['usr_id'] ;?>"; 
            var pure_sorszam = "<?php echo $szam ;?>"; 
            if(vevo_azonosito == szallito_azonosito)
            {
                alert('Kérem válasszon ki egy másik vevőt!');
                return false;    
            }  
              
            $('#total_item').val(sorok_szama);
            $('#invoice_number').val(szallito_sorszam);
            $('#szallito_id').val(szallito_azonosito);
            $('#vevo_id').val(vevo_azonosito);
            $('#pure_sorszam').val(pure_sorszam);
              

            // ------------ form küldése: ---------------------
            $('#invoice_form').submit(function(){
                $('input[type="number"]').each(function(){
                    if ($(this).val().length == 0)
                    { 
                        alert('Minden mező kitöltése kötelező!');
                        $('#invoice_form').off('submit');
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