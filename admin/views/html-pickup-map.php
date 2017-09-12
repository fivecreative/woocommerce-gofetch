<?php
/**
* Displays our map for our pickup location
*
* @version 	1.0
* @since 	1.0
* @author 	FIVE
* @package 	WCGO/Admin/Templates
*/

	if(!defined('ABSPATH')) exit; // NO GETTING HERE
	
	$option_value = get_option($value['id'], $value['default']);

?>

<tr valign="top" class="wcgo_map">
	<!-- tr -->

	<th scope="row" class="titledesc"><?php echo esc_html($value['title']) ?></th>
	<!-- th -->
	
	<td class="forminp">
		
		<div style="width: 500px; height: 300px;" id="wcgo-map-cont" data-coords="<?php echo $option_value ?>"></div>
		<!-- wcgo-map-cont -->
		
		<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo $option_value ?>" />
		
	</td>
	<!-- td -->

</tr>
<!-- tr -->