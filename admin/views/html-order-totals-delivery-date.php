<?php
/**
* Displays the order delivery date on our admin panel
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	WCGO/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	$surcharge = !empty($wcgo_delivery['surcharge']) ? wc_price($wcgo_delivery['surcharge']) : wc_price(0);

?>

<tr>
	<td class="label"><?php echo wc_help_tip( __( 'This is the delivery date selected by the customer.', 'five' ) ); ?> <?php _e( 'Delivery Date', 'woocommerce' ); ?><br><strong><?php echo $wcgo_delivery['delivery_date_formatted'] ?></strong></td>
	<td width="1%"></td>
	<td class="total"><?php echo $surcharge ?></td>
</tr>