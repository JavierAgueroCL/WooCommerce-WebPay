<?php


/*
 * @author = Cristian Tala Sánchez
 * @web = http://www.cristiantala.cl
 * 
 */

function log_me($message, $sufijo = "") {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($sufijo . "\t-> " . $message);
        }
    }
}

?>
