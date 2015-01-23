<?php
/*
  Plugin Name: WooCommerce WebpayPlus Chile
  Description: Sistema de pagos de tarjetas de crédito y débito para WooCommerce con WebPayPlus
  Author: Cristian Tala Sánchez
  Version: 3.0.5.3-DEVEL
  Author URI: www.cristiantala.cl
  Plugin URI: https://bitbucket.org/ctala/woocommerce-webpay/wiki/Home
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License or any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

  Copyright 2011-2014 Cristian Tala Sánchez
  Si estás leyendo esta parte existe la posibilidad de que quieras modificar
  incluso vender este código. Solo quiero aclarar que estás en todo el derecho
  de hacerlo, sin embargo, no incluir el autor original del código es una 
  infracción a la licencia GPLv3 y se pueden realizar acciones legales para 
  quienes recurran en este acto.
  Por mi parte llevo años trabajando en este código no para hacerme millonario,
  si no, para ayudar a la comunidad y un poco de reconocimiento no le hace mal a 
  nadie. En resumen no seas cagado y copiando y pegando  un código que no te pertenece
  sin dar las referencias necesarias.
  
 */

include_once 'helpers/webpay_debug.php';
include_once 'helpers/webpay_install.php';

register_activation_hook(__FILE__, 'webpayplus_install');
add_action('plugins_loaded', 'init_webpayplus_class');
add_shortcode('webpay_thankyou', 'webpayThankYou');

function webpayThankYou() {
    log_me("Entrando al ThankYouPage");

    //Variable que permite ver el contenido.
    $validoMostrar = true;
    if (isset($_GET['order']) && isset($_GET['key']) && isset($_GET['status'])) {
        $order_id = absint($_GET['order']);
        $order_key = $_GET['key'];
        $status = $_GET['status'];

        //Reviso si status es valido.
        if (!WC_Gateway_Webpayplus::webpay_status_valido($status))
            $validoMostrar = $validoMostrar && false;
        //Reviso si corresponde la orden con el key
        if (!WC_Gateway_Webpayplus::webpay_orden_valida($order_id, $order_key))
            $validoMostrar = $validoMostrar && false;
        //Muestro los datos de la orden si es valida
        if ($validoMostrar) {
            WC_Gateway_Webpayplus::order_received($order_id);
        } else {
            WC_Gateway_Webpayplus::webpay_pagina_error($order_id);
        }
    } else {
        if (isset($_GET['order'])) {
            $order_id = absint($_GET['order']);
            WC_Gateway_Webpayplus::webpay_pagina_error($order_id);
        } else {
            WC_Gateway_Webpayplus::webpay_pagina_error();
        }
    }


    log_me("Saliendo al ThankYouPage");
}

// change municipio to region ****
add_filter('gettext', 'translate_text');
add_filter('ngettext', 'translate_text');

function translate_text($translated) {
    $translated = str_ireplace('Municipio', 'Región', $translated);
    return $translated;
}

/*
 * Esta función solo agregará información de webpayplus al email si la orden corresponde a webpayplus.
 */
add_action('woocommerce_email_after_order_table', 'webpayplus_email_data', 15, 2);

function webpayplus_email_data($order) {
    $tipoPago = strtolower(str_replace(" ", "", $order->payment_method_title));
    $webpayplus = "webpayplus";
    log_me("Agregando Información extra de la Orden al Email " . $tipoPago, "WPP_MAIL");
    $strcmp = strcmp($tipoPago, $webpayplus);

    if ($strcmp == 0) {
        echo '<p><strong>Tipo de Pago:</strong> ' . $order->payment_method_title . '</p>';
    }
}

function init_webpayplus_class() {

    class WC_Gateway_Webpayplus extends WC_Payment_Gateway {

        var $notify_url;
        var $order_received_url;
        var $webpay_thankyou_page;
        var $politicas_devoluccion;
        var $tiempos_envio;

        public function __construct() {
            $this->id = 'webpayplus';
            $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png';
            $this->has_fields = false;
            $this->method_title = 'WebPayPlus';
            $this->notify_url = WC()->api_request_url('WC_Gateway_Webpayplus');

            $this->method_description = __('Permite pagos con tarjeta de crédito y debido chilenas');
            $this->method_description .= __('<br><h4>Instrucciones</h4>');

            $this->method_description .= __('<ol><li>Completar la Información a continuación</li>'
                    . '<li>En la configuración de los CGI usar la siguiente URL <b><i>' . $this->notify_url . '&xt_compra  </b></i></li>'
                    . '<li>Si existiera un error de conexión es debido al tamaño del _POST. Se pueden modificar los archivos de configuración de transbank para que acepten más datos.</li>'
                    . '<li>Agregar al WhiteList el guión en el tbk_config.dat</li>'
                    . '</ol>'
                    . '<h3>Las instrucciones detalladas las puedes contrar en : <a href="https://bitbucket.org/ctala/woocommerce-webpay">https://bitbucket.org/ctala/woocommerce-webpay</a></h3>');




            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->liveurl = $this->settings['cgiurl'];
            $this->macpath = $this->settings['macpath'];
            $this->politicas_devoluccion = $this->settings['politicas-devoluciones'];

            $this->redirect_page_id = $this->settings['redirect_page_id'];
            $this->webpay_thankyou_page = get_site_url() . "/?page_id=" . ($this->redirect_page_id);

            /*
             * Actions
             * woocommerce_receipt_webpayplus se ejecuta luego del checkout.
             * woocommerce_thankyou_webpayplus se ejecuta al terminar la transacción.
             * woocommerce_update_options_payment_gateways_webpayplus  guarda la configuración de la pasarela de pago.
             */

            add_action('woocommerce_receipt_webpayplus', array($this, 'receipt_page'));
            add_action('woocommerce_thankyou_webpayplus', array($this, 'webpayplus_return_handler'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_webpayplus', array($this, 'webpayplus_api_handler'));
        }

        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Habilita Woocommerce Webpay Plus', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('', 'woocommerce'),
                    'default' => __('Web Pay Plus', 'woocommerce')
                ),
                'description' => array(
                    'title' => __('Customer Message', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Mensaje que recibirán los clientes al seleccionar el medio de pago'),
                    'default' => __('Sistema de pago con tarjetas de crédito y debito chilenas.'),
                ),
                'politicas-devoluciones' => array(
                    'title' => __('Políticas de Devolución', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Mensaje que recibirán los clientes sobre devoluciones al finalizar la compra.'),
                    'default' => __('No se realizan devoluciones, ni reembolsos. En caso de tener alguna duda favor de contactar a (persona XXXX) o (Departamento XXXX) al teléfono (XXXX) o al mail (XXXX@XXXX.cl)".'),
                ),
                'account_details' => array(
                    'title' => __('Detalles de WebPay', 'woocommerce'),
                    'type' => 'title',
                    'description' => __('Configuración para la configuración y acceso a los CGI de Transbank'),
                ),
                'cgiurl' => array(
                    'title' => __('CGI URL', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('url like : http://empresasctm.cl/cgi-bin/tbk_bp_pago.cgi', 'woocommerce'),
                    'default' => __('http://empresasctm.cl/cgi-bin/tbk_bp_pago.cgi', 'woocommerce')
                ),
                'macpath' => array(
                    'title' => __('Check Mac Path', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('url like : /usr/lib/cgi-bin/', 'woocommerce'),
                    'default' => __('/usr/lib/cgi-bin/', 'woocommerce')
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page'),
                    'type' => 'select',
                    'options' => $this->get_pages('Selecciona una Página'),
                    'description' => "URL of success page"
                ),
                'trade_name' => array(
                    'title' => __('Nombre del Comercio', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Trade Name like : EmpresasCTM', 'woocommerce'),
                    'default' => __('EmpresasCTM', 'woocommerce')
                ),
                'url_commerce' => array(
                    'title' => __('URL Comercio', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Url Commerce like : http://www.empresasctm.cl', 'woocommerce'),
                    'default' => __('http://www.empresasctm.cl', 'woocommerce')
                ),
            );
        }

        /**
         * Initialise Gateway Settings
         *
         * Store all settings in a single database entry
         * and make sure the $settings array is either the default
         * or the settings stored in the database.
         *
         * @since 1.0.0
         * @uses get_option(), add_option()
         * @access public
         * @return void
         */
        public function init_settings() {
            // Load form_field settings
            $this->settings = get_option($this->plugin_id . $this->id . '_settings', null);

            if (!$this->settings || !is_array($this->settings)) {

                $this->settings = array();

                // If there are no settings defined, load defaults
                if ($form_fields = $this->get_form_fields())
                    foreach ($form_fields as $k => $v)
                        $this->settings[$k] = isset($v['default']) ? $v['default'] : '';
            }

            if ($this->settings && is_array($this->settings)) {
                $this->settings = array_map(array($this, 'format_settings'), $this->settings);
                $this->enabled = isset($this->settings['enabled']) && $this->settings['enabled'] == 'yes' ? 'yes' : 'no';
            }
        }

        /**
         * get_option function.
         *
         * Gets and option from the settings API, using defaults if necessary to prevent undefined notices.
         *
         * @access public
         * @param string $key
         * @param mixed $empty_value
         * @return string The value specified for the option or a default value for the option
         */
        public function get_option($key, $empty_value = null) {
            if (empty($this->settings))
                $this->init_settings();

            // Get option default if unset
            if (!isset($this->settings[$key])) {
                $form_fields = $this->get_form_fields();
                $this->settings[$key] = isset($form_fields[$key]['default']) ? $form_fields[$key]['default'] : '';
            }

            if (!is_null($empty_value) && empty($this->settings[$key]))
                $this->settings[$key] = $empty_value;

            return $this->settings[$key];
        }

        function process_payment($order_id) {
            $sufijo = "[WEBPAY - PROCESS - PAYMENT]";
            log_me("Iniciando el proceso de pago para $order_id", $sufijo);

            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        /**
         * Output for the order received page.
         *
         * @access public
         * @return void
         */
        function receipt_page($order) {
            echo '<p>' . __('Gracias! - Tu orden ahora está pendiente de pago. Deberías ser redirigido automáticamente a la página de transbank.') . '</p>';

            echo $this->generate_webpayplus_form($order);
        }

        public function webpayplus_api_handler() {
            $sufijo = "[API]";
            log_me("ENTRANDO HANDLER", $sufijo);
            if (isset($_GET['xt_compra'])) {
                log_me("ENTRANDO XT_COMPRA", $sufijo);
                $this->xt_compra();
            } else {
                log_me("ENTRANDO RESPONSE", $sufijo);
                $this->check_webpay_response();

                //redirijo a la página de webpay thankyou que implementa la función
                //de order received. ( Debido a que a transbank no le gusta como funciona la original )
                //$order->get_checkout_order_received_url();
                $TBK_ORDEN_COMPRA = filter_input(INPUT_POST, "TBK_ORDEN_COMPRA");
                $TBK_STATUS_ORDEN = filter_input(INPUT_GET, 'status');
                $order = new WC_Order($TBK_ORDEN_COMPRA);
                $order_key = $order->order_key;

//                wp_redirect($order->get_checkout_order_received_url());
                wp_redirect($this->webpay_thankyou_page . "&status=$TBK_STATUS_ORDEN&order=$TBK_ORDEN_COMPRA&key=$order_key");
                exit;
            }
        }

        public function xt_compra() {
            global $webpay_table_name;
            global $wpdb;
            global $woocommerce;
            global $webpay_comun_folder;
            $sufijo = "[XT_COMPRA]";
            log_me("Iniciando xt_compra", $sufijo);

            //rescate de datos de POST.
            $TBK_RESPUESTA = $_POST["TBK_RESPUESTA"];
            $TBK_ORDEN_COMPRA = $_POST["TBK_ORDEN_COMPRA"];
            $TBK_MONTO = $_POST["TBK_MONTO"];
            $TBK_ID_SESION = $_POST["TBK_ID_SESION"];
            $TBK_TIPO_TRANSACCION = $_POST['TBK_TIPO_TRANSACCION'];
            $TBK_CODIGO_AUTORIZACION = $_POST['TBK_CODIGO_AUTORIZACION'];
            $TBK_FINAL_NUMERO_TARJETA = $_POST['TBK_FINAL_NUMERO_TARJETA'];
            $TBK_FECHA_CONTABLE = $_POST['TBK_FECHA_CONTABLE'];
            $TBK_FECHA_TRANSACCION = $_POST['TBK_FECHA_TRANSACCION'];
            $TBK_HORA_TRANSACCION = $_POST['TBK_HORA_TRANSACCION'];
            $TBK_ID_TRANSACCION = $_POST['TBK_ID_TRANSACCION'];
            $TBK_TIPO_PAGO = $_POST['TBK_TIPO_PAGO'];
            $TBK_NUMERO_CUOTAS = $_POST['TBK_NUMERO_CUOTAS'];


            //Validación de los datos del post.
            if (!isset($TBK_RESPUESTA) || !is_numeric($TBK_RESPUESTA))
                die('RECHAZADO');
            if (!isset($TBK_ORDEN_COMPRA))
                die('RECHAZADO');
            if (!isset($TBK_MONTO) || !is_numeric($TBK_MONTO))
                die('RECHAZADO');
            if (!isset($TBK_ID_SESION) || !is_numeric($TBK_ID_SESION))
                die('RECHAZADO');
            if (!isset($TBK_TIPO_TRANSACCION))
                die('RECHAZADO');
            if (!isset($TBK_CODIGO_AUTORIZACION) || !is_numeric($TBK_CODIGO_AUTORIZACION))
                die('RECHAZADO');
            if (!isset($TBK_FINAL_NUMERO_TARJETA) || !is_numeric($TBK_FINAL_NUMERO_TARJETA))
                die('RECHAZADO');
            if (!isset($TBK_FECHA_CONTABLE) || !is_numeric($TBK_FECHA_CONTABLE))
                die('RECHAZADO');
            if (!isset($TBK_FECHA_TRANSACCION) || !is_numeric($TBK_FECHA_TRANSACCION))
                die('RECHAZADO');
            if (!isset($TBK_HORA_TRANSACCION) || !is_numeric($TBK_HORA_TRANSACCION))
                die('RECHAZADO');
            if (!isset($TBK_ID_TRANSACCION) || !is_numeric($TBK_ID_TRANSACCION))
                die('RECHAZADO');
            if (!isset($TBK_TIPO_PAGO))
                die('RECHAZADO');
            if (!isset($TBK_NUMERO_CUOTAS) || !is_numeric($TBK_NUMERO_CUOTAS))
                die('RECHAZADO');

            $order_id = explode('_', $TBK_ORDEN_COMPRA);
            $order_id = (int) $order_id[0];

            if (!is_numeric($order_id))
                die('RECHAZADO');

            /**
             * transbank pide que se de "ACEPTADO" si la respuesta está entre -8 y -1
             */
            if ($TBK_RESPUESTA >= -8 && $TBK_RESPUESTA <= -1)
                die("ACEPTADO");

            //Validar que la orden exista         
            $order = new WC_Order($order_id);
            log_me($order->status, $sufijo);

            //Si la orden de compra no tiene status es debido a que no existe

            if ($order->status == '') {
                log_me("ORDEN NO EXISTENTE " . $order_id, $sufijo);
                die('RECHAZADO');
            } else {
                log_me("ORDEN EXISTENTE " . $order_id, $sufijo);
                //CUANDO UNA ORDEN ES PAGADA SE VA A PROCESSING.

                if ($order->status == 'completed' || $order->status == 'processing' || $order->status == 'refunded' || $order->status == 'canceled') {
                    log_me("ORDEN YA PAGADA (" . $order->status . ") EXISTENTE " . $order_id, "\t" . $sufijo);
                    die('RECHAZADO');
                } else {

                    if ($order->status == 'pending' || $order->status == 'failed') {
                        log_me("ORDEN DE COMPRA NO PAGADA (" . $order->status . "). Se procede con el pago de la orden " . $order_id, $sufijo);
                    } else {
                        log_me("ORDEN YA PAGADA (" . $order->status . ") EXISTENTE " . $order_id, "\t" . $sufijo);
                        die('RECHAZADO');
                    }
                }
            }


            /*             * **************** CONFIGURAR AQUI ****************** */
            $myPath = $webpay_comun_folder . DIRECTORY_SEPARATOR . "dato$TBK_ID_SESION.log";
            //GENERA ARCHIVO PARA MAC
            $filename_txt = $webpay_comun_folder . DIRECTORY_SEPARATOR . "MAC01Normal$TBK_ID_SESION.txt";
            // Ruta Checkmac
            $cmdline = $this->macpath . "/tbk_check_mac.cgi $filename_txt";
            /*             * **************** FIN CONFIGURACION **************** */
            $acepta = false;
            //lectura archivo que guardo pago.
            if ($fic = fopen($myPath, "r")) {
                $linea = fgets($fic);
                fclose($fic);
            }
            $detalle = explode(";", $linea);
            if (count($detalle) >= 1) {
                $monto = $detalle[0];
                $ordenCompra = $detalle[1];
            }
            log_me("INICIANDO GUARDADO EN ARCHIVO", $sufijo);
            //guarda los datos del post uno a uno en archivo para la ejecución del MAC
            $fp = fopen($filename_txt, "wt");
            while (list($key, $val) = each($_POST)) {
                fwrite($fp, "$key=$val&");
            }
            fclose($fp);
            log_me("ARCHIVO CERRADO", $sufijo);
            //Validación de respuesta de Transbank, solo si es 0 continua con la pagina de cierre
            if ($TBK_RESPUESTA == "0") {
                $acepta = true;
            } else {
                $acepta = false;
            }
            //validación de monto y Orden de compra
            //
            //
          if ($TBK_MONTO == $monto && $TBK_ORDEN_COMPRA == $ordenCompra && $acepta == true) {

                /**
                 * validamos que la orden de compra no esté repetida preguntando a la base de datos.
                 * 
                 */
                log_me("VERIFICANDO QUE LA ORDEN NO ESTÉ REPETIDA", $sufijo);
                $res = $wpdb->get_row("SELECT count(*) as total FROM " . $webpay_table_name . " WHERE idOrder = " . $TBK_ORDEN_COMPRA, ARRAY_A);


                if ($res['total'] > 0) {

                    $acepta = false;
                } else {

                    $acepta = true;
                }
            } else {
                log_me("ORDEN REPETIDA O DATOS NO VALIDADOS", $sufijo);
                $acepta = false;
            }

            //Validación MAC
            log_me("INICIANDO VALIDACION MAC", $sufijo);
            if ($acepta == true) {
                exec($cmdline, $result, $retint);
                if ($result [0] == "CORRECTO")
                    $acepta = true;
                else
                    $acepta = false;
            }
            log_me("FIN VALIDACION MAC", $sufijo);
            ?>
            <html>
                <?php
                if ($acepta == true) {
                    /*
                     * Agrego la info a la BdD.
                     */
                    ?>
                    ACEPTADO
                <?php } else { ?>
                    RECHAZADO
                <?php } exit; ?>
            </html>

            <?php
            log_me("FINALIZANDO XT_COMPRA", $sufijo);
        }

        /**
         *      Check payment response from web pay plus
         * */
        function check_webpay_response() {
            global $woocommerce;
            global $webpay_comun_folder;
            $SUFIJO = "[WEBPAY - RESPONSE]";

            log_me("Entrando al Webpay Response", $SUFIJO);
            log_me(filter_input_array(INPUT_POST));

//            log_me("TODOS LOS PARAMETROS", $SUFIJO);
//            log_me($_REQUEST);

            $TBK_ID_SESION = filter_input(INPUT_POST, "TBK_ID_SESION");
            $TBK_ORDEN_COMPRA = filter_input(INPUT_POST, "TBK_ORDEN_COMPRA");


            if (isset($TBK_ID_SESION) && isset($TBK_ORDEN_COMPRA)) {
                log_me("VARIABLES EXISTENTES", $SUFIJO);
                try {
                    $order = new WC_Order($TBK_ORDEN_COMPRA);
                    log_me("ORDEN RESCATADA", $SUFIJO);

                    $status = filter_input(INPUT_GET, 'status');
                    log_me("STATUS " . $status, $SUFIJO);
                    if ($order->status !== 'completed') {


                        /**
                         * aquí es donde se hace la validación para la inyección.
                         * 
                         */
                        //Archivo previamente generado para rescatar la información.
                        $myPath = $webpay_comun_folder . DIRECTORY_SEPARATOR . "MAC01Normal$TBK_ID_SESION.txt";
                        log_me("INICIANDO LA REVISION MAC PARA " . $myPath, $SUFIJO);
                        //Rescate de los valores informados por transbank
                        $fic = fopen($myPath, "r");
                        $linea = fgets($fic);
                        fclose($fic);
                        $detalle = explode("&", $linea);

                        $TBK = array(
                            'TBK_ORDEN_COMPRA' => explode("=", $detalle[0]),
                            'TBK_TIPO_TRANSACCION' => explode("=", $detalle[1]),
                            'TBK_RESPUESTA' => explode("=", $detalle[2]),
                            'TBK_MONTO' => explode("=", $detalle[3]),
                            'TBK_CODIGO_AUTORIZACION' => explode("=", $detalle[4]),
                            'TBK_FINAL_NUMERO_TARJETA' => explode("=", $detalle[5]),
                            'TBK_FECHA_CONTABLE' => explode("=", $detalle[6]),
                            'TBK_FECHA_TRANSACCION' => explode("=", $detalle[7]),
                            'TBK_HORA_TRANSACCION' => explode("=", $detalle[8]),
                            'TBK_ID_TRANSACCION' => explode("=", $detalle[10]),
                            'TBK_TIPO_PAGO' => explode("=", $detalle[11]),
                            'TBK_NUMERO_CUOTAS' => explode("=", $detalle[12]),
                                //'TBK_MAC' => explode("=", $detalle[13]),
                        );
                        log_me($TBK);
                        /**
                         * si es una inyección, o sea que no pasa primero por el xt_compra, no se genera archivo
                         * "MAC" entonces siempre los valores darán cero, ademas de ver si el estado es "success"
                         * preguntamos si el en el archivo rescatado existe la orden de compra si es asi pasamos a la pagina de exito
                         * 
                         */
                        if ($status == 'success' && $TBK['TBK_ORDEN_COMPRA'][1] == $TBK_ORDEN_COMPRA) {


                            // Si el pago ya fue recibido lo marcamos como procesando. 
                            $order->update_status('processing');

                            // Reducimos el stock.
                            $order->reduce_order_stock();

                            // Vaciamos el carrito
                            WC()->cart->empty_cart();


                            //Esto servirá más a futuro :). Por ahora sirve como validación.
                            log_me("INSERTANDO EN LA BDD");
                            $this->add_data_webpayplus($TBK_ORDEN_COMPRA, $TBK);
                            log_me("TERMINANDO INSERSIÓN");

                            /**
                             * en cambio si el status es "failure" o en el archivo MAC no existe la orden de compra, redirigimos a la pagina
                             * de fracaso
                             * 
                             */
                        } elseif ($status == 'failure' || $TBK['TBK_ORDEN_COMPRA'][1] != $TBK_ORDEN_COMPRA) {

                            log_me("FALLO EN EL PAGO DE LA ORDEN", $SUFIJO);
                            $order->update_status('failed');
                            $order->add_order_note('Failed');
                        }
                    } else {
                        //Si la orden ya ha sido pagada, redirijo al home para evitar exploit.
                        log_me("Esta orden ya ha sido completada", $SUFIJO);
                        wp_redirect(home_url());
                        exit;

//                            add_action('the_content', array(&$this, 'thankyouContent'));
                    }
                } catch (Exception $e) {

                    log_me("Ha ocurrido un error procesando el pago.", $SUFIJO);
                    log_me($e);
                    //Si existe un error también redirijo al inicio para evitar exploit.
                    wp_redirect(home_url());
                    exit;
//                  
                }
            } else {
                log_me("FALTAN PARAMETROS", $SUFIJO);
            }
            log_me("SALIENDO DEL RESPONSE", $SUFIJO);
        }

        function generate_webpayplus_form($order_id) {


            global $webpay_comun_folder;
            $SUFIJO = "[WEBPAY - FORM]";

            $order = new WC_Order($order_id);

            $redirect_url = $this->notify_url;
            $order_key = $order->order_key;

            if (strpos($redirect_url, "?")) {
                $failureLink = $redirect_url . "&status=failure&order=$order_id&key=$order_key";
                $successLink = $redirect_url . "&status=success&order=$order_id&key=$order_key";
            } else {
                $failureLink = $redirect_url . "?status=failure&order=$order_id&key=$order_key";
                $successLink = $redirect_url . "?status=success&order=$order_id&key=$order_key";
            }



            log_me("REDIRECT_URL " . $redirect_url, $SUFIJO);



            $TBK_MONTO = round($order->order_total);
            $TBK_ORDEN_COMPRA = $order_id;
            $TBK_ID_SESION = date("Ymdhis");

            $filename = __FILE__;

            $myPath = $webpay_comun_folder . DIRECTORY_SEPARATOR . "dato$TBK_ID_SESION.log";

            log_me("Se utilizará $myPath para guardar los datos", $SUFIJO);
            /*             * **************** FIN CONFIGURACION **************** */
            //formato Moneda
            $partesMonto = explode(",", $TBK_MONTO);
            $TBK_MONTO = $partesMonto[0] . "00";
            //Grabado de datos en archivo de transaccion
            $fic = fopen($myPath, "w+");
            $linea = "$TBK_MONTO;$TBK_ORDEN_COMPRA";

            log_me("Preparando para escribir $linea en $myPath", $SUFIJO);
            fwrite($fic, $linea);
            fclose($fic);
            log_me("ARCHIVO CERRADO", $SUFIJO);

            log_me("Argumentos", $SUFIJO);



            $webpayplus_args = array(
                'TBK_TIPO_TRANSACCION' => "TR_NORMAL",
                'TBK_MONTO' => $TBK_MONTO,
                'TBK_ORDEN_COMPRA' => $TBK_ORDEN_COMPRA,
                'TBK_ID_SESION' => $TBK_ID_SESION,
                'TBK_URL_EXITO' => $successLink, //$redirect_url . "&status=success&order=$order_id&key=$order_key",
                'TBK_URL_FRACASO' => $failureLink //$redirect_url . "&status=failure&order=$order_id&key=$order_key",
            );
            log_me($webpayplus_args);

            foreach ($webpayplus_args as $key => $value) {
                $webpayplus_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }
            /*
             * Esto hace que sea enviada automáticamente el formulario.
             */
            wc_enqueue_js('
			$.blockUI({
					message: "' . esc_js(__('Gracias por tu orden. Estamos redireccionando a Transbank')) . '",
					baseZ: 99999,
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:        "20px",
						zindex:         "9999999",
						textAlign:      "center",
						color:          "#555",
						border:         "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:         "wait",
						lineHeight:		"24px",
					}
				});
			jQuery("#submit_webpayplus_payment_form").click();
		');


            /*
             * La variable resultado tiene el formulario que es enviado a transbank. ( Todo el <FORM> )
             */
            $resultado = '<form action="' . esc_url($this->liveurl) . '" method="post" id="webpayplus_payment_form" target="_top">';
            $resultado.=implode('', $webpayplus_args_array);
            $resultado.='<!-- Button Fallback -->
				<div class="payment_buttons">
					<input type="submit" class="button alt" id="submit_webpayplus_payment_form" value="' . __('Pago via WebpayPlus') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce') . '</a>
				</div>
                                <script type="text/javascript">
					jQuery(".payment_buttons").hide();
				</script>

			</form>';
            return $resultado;
        }

        function webpayplus_return_handler() {
            log_me("INICIANDO WEBPAYPLUS HANDLER");
            include_once plugin_dir_path(__FILE__) . 'templates/webpay_thankyou.php';
            log_me("FINALIZANDO WEBPAYPLUS HANDLER");
        }

        // get all pages
        function get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title)
                $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }

        public static function order_received($order_id = 0, $order_key = "") {
            $SUFIJO = "[ORDER_RECEIVED]";
            log_me("Ingresando a la recepción de la orden con $order_id y $order_key", $SUFIJO);
            wc_print_notices();

            $order = new WC_Order($order_id);

            // Empty awaiting payment session
            unset(WC()->session->order_awaiting_payment);

            include_once plugin_dir_path(__FILE__) . 'checkout/thankyou.php';

            echo "<p style='color: red;'>";
            echo '<b>' . WC_Gateway_Webpayplus::webpay_get_option('politicas-devoluciones') . '</b>';
            echo '</p>';
        }

        function add_data_webpayplus($order_id, $TBK) {
            global $webpay_table_name;
            global $wpdb;
            $SUFIJO = "[RESBDD]";
            /*
             * Antes que todo revisamos que la orden no haya sido agregada a la BdD anteriormente, 
             * de esa manera no mezclamos la información en caso de error.
             */

            $sqlResultadosAnteriores = "SELECT count(*) FROM " . $wpdb->prefix . "webpay where idOrder = $order_id";
            log_me($sqlResultadosAnteriores, $SUFIJO);
            $resultadosAnteriores = $wpdb->get_var($sqlResultadosAnteriores);
            log_me($resultadosAnteriores, $SUFIJO);

            if ($resultadosAnteriores == 0) {

                $order = new WC_Order($order_id);
                $order->add_order_note("Pago Completado. Transacción : " . $TBK['TBK_CODIGO_AUTORIZACION'][1]);


                log_me("idOrden : ");
                log_me($order_id);
                log_me('TBK:');
                log_me($TBK);
                $rows_affected = $wpdb->insert($webpay_table_name, array(
                    'idOrder' => $order_id,
                    'TBK_ORDEN_COMPRA' => $TBK['TBK_ORDEN_COMPRA'][1],
                    'TBK_TIPO_TRANSACCION' => $TBK['TBK_TIPO_TRANSACCION'][1],
                    'TBK_RESPUESTA' => $TBK['TBK_RESPUESTA'][1],
                    'TBK_MONTO' => $TBK['TBK_MONTO'][1],
                    'TBK_CODIGO_AUTORIZACION' => $TBK['TBK_CODIGO_AUTORIZACION'][1],
                    'TBK_FINAL_NUMERO_TARJETA' => $TBK['TBK_FINAL_NUMERO_TARJETA'][1],
                    'TBK_FECHA_CONTABLE' => $TBK['TBK_FECHA_CONTABLE'][1],
                    'TBK_FECHA_TRANSACCION' => $TBK['TBK_FECHA_TRANSACCION'][1],
                    'TBK_HORA_TRANSACCION' => $TBK['TBK_HORA_TRANSACCION'][1],
                    'TBK_ID_TRANSACCION' => $TBK['TBK_ID_TRANSACCION'][1],
                    'TBK_TIPO_PAGO' => $TBK['TBK_TIPO_PAGO'][1],
                    'TBK_NUMERO_CUOTAS' => $TBK['TBK_NUMERO_CUOTAS'][1],
                        )
                );
            }
        }

        public static function webpay_status_valido($status = "") {
            $status = trim($status);
            if (strcmp($status, "success") == 0 || strcmp($status, "failure") == 0) {
                log_me("STATUS VALIDO -> " . $status);
                return TRUE;
            } else {
                log_me("STATUS NO VALIDO" . $status);
                return false;
            }
        }

        public static function webpay_orden_valida($order_id, $order_key) {
            $order = false;
            if ($order_id > 0) {
                $order = new WC_Order($order_id);
                if ($order->order_key != $order_key) {
                    unset($order);
                    return false;
                } else {
                    return true;
                }
            }
        }

        public static function webpay_pagina_error($order_id = 0) {
            include_once plugin_dir_path(__FILE__) . 'templates/webpay_error.php';
        }

        public static function webpay_get_option($option_name) {
            $options = get_option("woocommerce_webpayplus_settings");
            log_me($options);
            return $options["$option_name"];
        }

    }

    function add_webpayplus_class($methods) {
        $methods[] = 'WC_Gateway_WebpayPlus';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_webpayplus_class');
}
