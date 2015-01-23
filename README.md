#Webpay para Woocommerce en Wordpress
Bienvenidos al wiki de este plugin. Espero que me ayuden a hacer que este plugin sea el mejor que se pueda encontrar como gateway de pago para WooCommerce con Webpay. 

Esta versión es totalmente nueva. Se modificó todo el core de Woocommerce, por lo que se tuvo que modificar todo el plugin partiendo desde 0... ( Uff ! ). 

Aún se están haciendo cambios debido a que los chicos de soporte de webpay no se ponen de acuerdo entre ellos y han hecho cambios en la certificación que no aparecen en el manual.

Siempre puedes encontrar la última versión estable por el buscador de plugins de wordpress. * http://wordpress.org/plugins/webpay-woocommerce-plugin/

#Información de proyecto

Este proyecto es un clon del bitbucket del autor Cristian Tala Sánche (https://bitbucket.org/ctala/) que ha sido llevado a Github para quienes preferimos esta plataforma.

Branches:

 - Master - Proyecto original siempre actualizado a la version oficinal de "ctala"
 - Dev - Branch principal del proyecto en github (tratamiento como master)

#Problemas Conocidos.
 - Ninguno por el momento ( desde versión v3.0.5 )

#Estado del Arte

 - Este plugin funciona con las últimas versiones de woocommerce. (Version 2.1.*)
 - Este plugin ya no funciona con las versiones anteriores de Woocommerce.
## Descarga ##


Si quieres editarlo recomiendo descargarlo con git:

    $ git clone https://ctala@bitbucket.org/ctala/woocommerce-webpay.git

Siempre se puede obtener y descargar la última versión de esta página o del siguiente link:

    https://bitbucket.org/ctala/woocommerce-webpay/get/master.zip

Todos los cambios y mejoras se hacen en la rama "devel" hasta ser estables.

## DEBUG MODE ##

Si tienes habilitado en tu instalación de Wordpress el modo debug, el plugin creará mensaje dependiendo de donde se encuentre en el código de manera bien detallada. Si habilitas el login en wordpress puedes ver todo lo que está ocurriendo dentro del archivo debug.log dentro de la carpeta wp-content.

**Ejemplo del debug.log**

    ubuntu@ip-10-147-227-221:/var/www/wp-content$ tail -f debug.log -n 1000 | grep WEBPAY
    [25-Jun-2013 16:24:20 UTC] [WEBPAY - FORM]      -> Entrando a la verificación de carpetas
    [25-Jun-2013 16:24:20 UTC] [WEBPAY - FORM]      -> Se utilizará /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625042420.log para guardar los datos
    [25-Jun-2013 16:24:20 UTC] [WEBPAY - FORM]      -> Preparando para escribir 1000;35 en /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625042420.log
    [25-Jun-2013 16:24:20 UTC] [WEBPAY - FORM]      -> ARCHIVO CERRADO
    [25-Jun-2013 16:28:26 UTC] [WEBPAY - FORM]      -> Entrando a la verificación de carpetas
    [25-Jun-2013 16:28:26 UTC] [WEBPAY - FORM]      -> Se utilizará /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625042826.log para guardar los datos
    [25-Jun-2013 16:28:26 UTC] [WEBPAY - FORM]      -> Preparando para escribir 1000;36 en /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625042826.log
    [25-Jun-2013 16:28:26 UTC] [WEBPAY - FORM]      -> ARCHIVO CERRADO
    [25-Jun-2013 16:35:25 UTC] [WEBPAY - PROCESS - PAYMENT] -> Iniciando el proceso de pago para 37
    [25-Jun-2013 16:35:25 UTC] [WEBPAY - FORM]      -> Entrando a la verificación de carpetas
    [25-Jun-2013 16:35:25 UTC] [WEBPAY - FORM]      -> Se utilizará /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625043525.log para guardar los datos
    [25-Jun-2013 16:35:25 UTC] [WEBPAY - FORM]      -> Preparando para escribir 1000;37 en /var/www/wp-content/plugins/woocommerce-webpay-2.0/comun/dato20130625043525.log

Puedes además revisar el código de manera directa en esta página.

## Instalación ##

Una ves descargado el plugin hay dos cosas que tienes que tener en consideración.

 1. El plugin creará una carpeta dentro del directorio de uploads llamada webpay-data que contendrá la carpeta comun. Si esta carpeta no se ha creado por favor crearla con permisos de lectura y escritura.
 2. Es my importante que se entiendan los conceptos de escritura y lectura de archivos. El script de transbank es ejecutado por el usuario de apache. Si este usuario no tiene permiso de acceso (Lectura y escritura ) a los archivos creados en la carpeta común, estos tendrán un problema de validación en las últimas etapas.
 3. Debes de tener configurado los archivos CGI de WebPay. El plugin te preguntará por esta información dentro de la configuración. ( Woocommerce -> Settings -> Paymente Gateways -> Webpay Gateway).
 4. La página usada por HTML_TR_NORMAL = http://DIRECCIONDETUPAGINA/?wc-api=WC_Gateway_Webpayplus&xt_compra
 5. Es necesario crear una página en blanco con el shortcode [webpay_thankyou] al cual será redireccionada la transacción.
 6. Es necesario modificar la configuración bajo WooCommerce -> Ajustes -> Finalizar Compra -> WebPayPlus.
 7. Para que el plugin funcione los valores de la dirección del cgi, check path y return page deben de ser correctas.
 8. Los otros valores son estéticos y pedidos por parte de Transbank
 9. Es posible que debas agregar el guion " - " a la WhiteList de transbank para que no tire error de conexión.


## Ejemplo tbk_config.dat.##

OJO,PESTAÑA y CEJA. La configuración siguiente es para un ambiente de certificación.

    IDCOMERCIO = TUIDCOMERCIO
    MEDCOM = 2
    TBK_KEY_ID = 101
    PARAMVERIFCOM = 1
    URLCGICOM = http://DIRECCIONDETUPAGINA/cgi-bin/tbk_bp_resultado.cgi
    SERVERCOM = TUIP
    PORTCOM = 80
    WHITELISTCOM = ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz 0123456789./:=&?_-
    HOST = TUIP
    WPORT = 80
    URLCGITRA = /filtroUnificado/bp_revision.cgi
    URLCGIMEDTRA = /filtroUnificado/bp_validacion.cgi
    SERVERTRA = https://certificacion.webpay.cl
    PORTTRA = 6443
    PREFIJO_CONF_TR = HTML_
    HTML_TR_NORMAL =  http://DIRECCIONDETUPAGINA/?wc-api=WC_Gateway_Webpayplus&xt_compra

CHANGELOG

 - V3.0.5 : Se arregla problema con los permalinks.
 - V3.0.4 : Se agrega la palabra débito al mensaje de error. Se agrega el nombre del cliente a la tabla de información extra de la transacción.
 - V3.0.3 : Se elimina mensaje de contacto al banco en caso de error, Se elimina la información extra en caso de failure.
 - V3.0.2 : Se agregan las políticas de devoluciones, Se elimina frase repetida en fracaso
 - V3.0.1 : Se agrega al mail de administrador y cliente el método de pago cuando es WebPayPlus.
 - V3.0.0 : Remake del plugin para compatibilidad con WooCommerce 2.1.*
 - V2.4.1 : Arreglada la redirección con algunas versiones de php, en especial para hostgator.
 - V2.4 : Versión Certificada. Ahora no se deberían requerir modificaciones para pasar las certificaciones de transbank.
 - V2.3 : Se agregan los templates para los pagos con webpay.
 - V2.2 : Se establece el short-code [webpay-thankyou] para realizar las validaciones necesarias por parte de transbank. Es necesario cambiar en la página de recepción del pedido por [woocommerce-thankyou].
 - V2.1.9 : Se usa el estandar definido por woocommerce para los códigos de estado.
 - V2.1.8 : Arreglada posible duplicidad cuando la orden pasa a on-hold.
 - V2.1.7 :
 - V2.1.6 : Modificado para que funcionara con versiones más antiguas de php.
 - V2.1.5 : Se cambian los permisos por defecto de la carpeta común. Con esto se deben asegurar que el usuario que ejecuta los CGI sea el mismo que crea los archivos.
 - V2.1.2 : Se externaliza la carpeta común al directorio de uploads. De esta manera no se borra la información al actualizar el plugin.
 - V2.1 : Se agregan las variables de la tienda en la configuración del plugin.
 - 
Y eso debería ser todo por ahora.
Have fun!

Copyright
---------

Copyright 2011-2014 Cristian Tala Sánchez Si estás leyendo esta parte existe la posibilidad de que quieras modificar incluso vender este codigo. Solo quiero aclarar que estás en todo el derecho de hacerlo, sin embargo, no incluir el autor original del codigo es una infracción a la licencia GPLv3 y se pueden realizar acciones legales para quienes recurran en este acto. Por mi parte llevo años trabajando en este codigo no para hacerme millonario, si no, para ayudar a la comunidad y un poco de reconocimiento no le hace mal a nadie. En resumen no seas cagado y copies, pegues un codigo que no te pertenece sin dar las referencias necesarias.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License or any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see http://www.gnu.org/licenses/.
