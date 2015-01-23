<?php

include_once 'webpay_debug.php';

global $wpdb;

global $webpay_db_version;
$webpay_db_version = "1.0";

global $webpay_table_name;
$webpay_table_name = $wpdb->prefix . "webpay";

global $webpay_comun_folder;
$webpay_comun_folder = WP_CONTENT_DIR.DIRECTORY_SEPARATOR."uploads".DIRECTORY_SEPARATOR."webpay-comun";

function webpayplus_install() {
    global $wpdb;
    global $webpay_db_version;
    global $webpay_table_name;
	//Cambio legacy con la versión anterior para los que tienen versiones de PHP más antiguas.
	global $webpay_comun_folder;
	//ebpay_comun_folder = wp_upload_dir()['basedir'].DIRECTORY_SEPARATOR."webpay-data".DIRECTORY_SEPARATOR."comun";



    $sql = "CREATE TABLE $webpay_table_name (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  idOrder INT NOT NULL,
  TBK_ORDEN_COMPRA INT NOT NULL,
  TBK_TIPO_TRANSACCION text NOT NULL,
  TBK_RESPUESTA  INT(2) NOT NULL,
  TBK_MONTO INT NOT NULL,
  TBK_CODIGO_AUTORIZACION INT NOT NULL,
  TBK_FINAL_NUMERO_TARJETA INT(4) NOT NULL,
  TBK_FECHA_CONTABLE INT(8) NOT NULL,
  TBK_FECHA_TRANSACCION INT(8) NOT NULL,
  TBK_HORA_TRANSACCION INT(6) NOT NULL,
  TBK_ID_TRANSACCION INT(20) NOT NULL,
  TBK_TIPO_PAGO VARCHAR(10) NOT NULL,
  TBK_NUMERO_CUOTAS INT(2) NOT NULL,
  UNIQUE KEY id (id)
    );";

    log_me($sql);
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

	log_me("Revision de la carpeta común. -> ".$webpay_comun_folder);
    add_option("webpay_db_version", $webpay_db_version);

	//Crear la carpeta en donde se guardarán los logs.
	if(!file_exists($webpay_comun_folder))
	{
		log_me("Carpeta comun no existe, se inicia la creación.");
		mkdir($webpay_comun_folder, 0760, true);	
		chmod($webpay_comun_folder, 0760);
		touch($webpay_comun_folder.DIRECTORY_SEPARATOR."index.php");
	}
	else
	{
		log_me("Carpeta común existe. Se revisarán los permisos.");
		chmod($webpay_comun_folder, 0760); 
	}

		
}

?>