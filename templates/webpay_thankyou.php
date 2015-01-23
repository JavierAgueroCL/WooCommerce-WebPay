<?php
//IF IT IS A WEBPAY PAYMENT
global $webpay_table_name;
global $wpdb;


$paramArr = array();
$myOrderDetails = $wpdb->get_row("SELECT * FROM $webpay_table_name WHERE idOrder = $order_id", ARRAY_A);
if ($myOrderDetails):
    $order = new WC_Order($order_id);
    ?>
    <h2 class="related_products_title order_confirmed"><?= "Información Extra de la Transacción"; ?></h2>
    <div class="clear"></div>
    <table class="shop_table order_details">

        <tfoot>

            <tr>
                <th>Tipo de Transacción</th>
                <th>Venta</th>

            </tr>
            <tr>
                <th>Nombre del Comercio</th>
                <th><?php echo WC_Gateway_Webpayplus::webpay_get_option('trade_name'); ?></th>

            </tr>
            <tr>
                <th>URL Comercio</th>
                <th><?php echo WC_Gateway_Webpayplus::webpay_get_option('url_commerce'); ?></th>

            </tr>
            <tr>
                <th>Cliente</th>
                <th><?php echo $order->billing_first_name." ".$order->billing_last_name; ?></th>

            </tr>

            <tr>
                <th>Código de Autorización</th>
                <th><?php echo $myOrderDetails['TBK_CODIGO_AUTORIZACION'] ?></th>


            </tr>

            <tr>
                <th>Final de Tarjeta</th>
                <th><?php echo $myOrderDetails['TBK_FINAL_NUMERO_TARJETA'] ?></th>


            </tr>

            <tr>
                <th>Tipo de pago</th>
                <th><?php
                    if ($myOrderDetails['TBK_TIPO_PAGO'] == "VD") {
                        echo "Redcompra </th></tr>";
                        echo "<tr><td>Tipo de Cuota</td><td>Débito</td></tr>";
                    } else {
                        echo "Crédito </th></tr>";
                        echo '<tr><td>Tipo de Cuota</td><td>';
                        switch ($myOrderDetails['TBK_TIPO_PAGO']) {
                            case 'VN':
                                echo 'Sin Cuotas';
                                break;
                            case 'VC':
                                echo 'Cuotas Normales';
                                break;
                            case 'SI':
                                echo 'Sin interés';
                                break;
                            case 'CI':
                                echo 'Cuotas Comercio';
                                break;

                            default:
                                echo $myOrderDetails['TBK_TIPO_PAGO'];
                                break;
                        }
                    }
                    ?>

                    </td>

            </tr>

            <?php
            if (!($myOrderDetails['TBK_TIPO_PAGO'] == "VD") || true):
                ?>
                <tr>
                    <th>Número de Cuotas</th>
                    <th><?php
                        if (!($myOrderDetails['TBK_NUMERO_CUOTAS'] == "0")) {
                            echo $myOrderDetails['TBK_NUMERO_CUOTAS'];
                        } else {
                            echo "00";
                        }
                        ?></th>

                </tr>
                <?php
            endif;
            ?>
        </tfoot>
    </table>
    <?php
endif;
?>