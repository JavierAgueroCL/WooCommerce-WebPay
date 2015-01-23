<?php
/**
 * Thankyou page
 *
 * @author 		WooThemes
 * @Mofificado  Cristian Tala Sánchez <http://www.cristiantala.cl>
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
?><div class="woocommerce"><?php
global $woocommerce;

if ($order) :
    ?>

        <?php if (in_array($order->status, array('failed'))) : ?>

            <?php
            WC_Gateway_Webpayplus::webpay_pagina_error($order_id);
            ?>

            <p><?php
                if (is_user_logged_in())
                    _e('Please attempt your purchase again or go to your account page.', 'woocommerce');
                else
                    _e('Please attempt your purchase again.', 'woocommerce');
                ?></p>

            <p>
                <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button pay"><?php _e('Pay', 'woocommerce') ?></a>
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('myaccount'))); ?>" class="button pay"><?php _e('My Account', 'woocommerce'); ?></a>
                <?php endif; ?>
            </p>

        <?php else : ?>

            <p><?php _e('Thank you. Your order has been received.', 'woocommerce'); ?></p>

            <ul class="order_details">
                <li class="order">
                    <?php _e('Order:', 'woocommerce'); ?>
                    <strong><?php echo $order->get_order_number(); ?></strong>
                </li>
                <li class="date">
                    <?php _e('Date:', 'woocommerce'); ?>
                    <strong><?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?></strong>
                </li>
                <li class="total">
                    <?php _e('Total:', 'woocommerce'); ?>
                    <strong><?php echo $order->get_formatted_order_total(); ?></strong>
                </li>
                <?php if ($order->payment_method_title) : ?>
                    <li class="method">
                        <?php _e('Payment method:', 'woocommerce'); ?>
                        <strong><?php echo $order->payment_method_title; ?></strong>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="clear"></div>
            <?php include_once plugin_dir_path(__FILE__) . '/../templates/webpay_thankyou.php'; ?>
            <?php include_once plugin_dir_path(__FILE__) . '/../order/order-details.php'; ?>
        <?php endif; ?>




    <?php else : ?>

        <p><?php _e('Thank you. Your order has been received.', 'woocommerce'); ?></p>

    <?php endif; ?>

</div>        