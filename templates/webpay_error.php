<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>

<h2>Transacción Fracasada</h2>
<p>"Su transacción no ha podido ser procesada, por favor vuelva a intentarlo." </p>


<?php
if ($order_id !== 0) :
    echo "OC Nº $order_id";
endif;
?>


<h3>Las posibles causas de este rechazo son:</h3>

<ul>
    <li>Error en el ingreso de los datos de su tarjeta de crédito o débito(fecha y/o código de seguridad).</li>
    <li>Su tarjeta de crédito o débito no cuenta con el cupo necesario para cancelar la compra.</li>
    <li>Tarjeta aún no habilitada en el sistema financiero.</li>
</ul>
