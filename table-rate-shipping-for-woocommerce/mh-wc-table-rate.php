<?php
/*
Plugin Name: WooCommerce Table Rate Shipping by Mangohour
Plugin URI: https://wordpress.org/plugins/table-rate-shipping-for-woocommerce/
Description: Calculate shipping costs based on destination, weight and/or cart total. Supports unlimited country groups and rates.
Version: 1.2.1
Author: mangohour
Author URI: https://mangohour.com/
Text Domain: table-rate-shipping-for-woocommerce
License: GPL v3

WooCommerce Table Rate Shipping by Mangohour
Copyright (C) John Currie, 2014-2016, john@heymax.net

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('MHTR_DOMAIN', 'table-rate-shipping-for-woocommerce');

/**
 * Check if WooCommerce is active
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

	function mh_wc_table_rate_init() {
		if ( ! class_exists( 'MH_Table_Rate_Shipping_Method' ) ) {
			class MH_Table_Rate_Shipping_Method extends WC_Shipping_Method {


				/**
				 * Setup defaults and option locations.
				 */
				public function __construct() {
					$this->id                 			= 'mh_wc_table_rate'; // Id for your shipping method. Should be unique.
					$this->table_rate_option  			= $this->id.'_table_rates';
					$this->last_table_rate_id_option	= $this->id.'_last_table_rate_id';
					$this->zones_option 	  			= $this->id.'_zones';
					$this->last_zone_id_option 			= $this->id.'_last_zone_id';
					$this->method_title       			= __( 'Table Rate', MHTR_DOMAIN );  // Title shown in admin
					$this->method_description 			= __( 'A shipping calculator for WooCommerce', MHTR_DOMAIN ); // Description shown in admin

					// Set table arrays and last ids
					$this->get_zones();
					$this->get_last_zone_id();
					
					$this->get_table_rates();
					$this->get_last_table_rate_id();

					$this->init();
				}

				/**
				 * Initialises plugin.
				 */
				function init() {
			
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
					
					$this->enabled = $this->settings['enabled'];
					$this->title = $this->settings['title']; // Shown in drop down and admin order screen

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_zones' ) );
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_table_rates' ) );
					
				}
				
				/**
				 * Initialise form fields using WooCommerce API
				 */
				function init_form_fields() {
					$this->form_fields = array(
	                    'enabled' => array(
                            'title' 		=> __( 'Enable', MHTR_DOMAIN ),
                            'type' 			=> 'checkbox',
                            'label' 		=> __( 'Enable this shipping method', MHTR_DOMAIN ),
                            'default' 		=> 'no',
						),
	                    'title' => array(
                            'title' 		=> __( 'Title', MHTR_DOMAIN ),
                            'description' 		=> __( 'This controls the title which the user sees during checkout.', MHTR_DOMAIN ),
                            'type' 			=> 'text',
                            'default' 		=> 'Table Rate Shipping',
                            'desc_tip'     => true,
						),
						'tax_status' => array(
							'title' 		=> __( 'Tax Status', MHTR_DOMAIN ),
							'type' 			=> 'select',
							'default' 		=> 'taxable',
							'options'		=> array(
								'taxable' 	=> __( 'Taxable', MHTR_DOMAIN ),
								'none' 		=> __( 'None', MHTR_DOMAIN ),
							),
						),
	                    'handling_fee' => array(
                            'title' 		=> __( 'Handling Fee', MHTR_DOMAIN ),
                            'description' 		=> __( 'Fee, excluding tax. Leave blank to disable.', MHTR_DOMAIN ),
                            'type' 			=> 'price',
                            'default' 		=> '0.00',
                            'desc_tip'     => true,
						),
						'zones_table' => array(
							'type'				=> 'zones_table',
						),
						'table_rates_table' => array(
							'type'				=> 'table_rates_table'
						),
					);
				}
				
				/**
				 * Generates dropdown options
				 */
				function generate_options() {
				
					$option_arr = array();
				
					// ####### ZONES
					foreach($this->zones as $option):
						$option_arr['table_rate_zone'][esc_attr($option['id'])] = esc_js($option['name']);
			   		endforeach;
			   		
			   		$option_arr['table_rate_zone']['0'] = __( 'Everywhere Else', MHTR_DOMAIN);
			   		
			   		// ####### COUNTRIES
					foreach (WC()->countries->get_shipping_countries() as $id => $value) :
		   				$option_arr['country'][esc_attr($id)] = esc_js($value);
					endforeach;
					
					// ####### TABLE RATE BASIS
			   		$option_arr['rate_basis']['weight'] = sprintf(__( 'Weight (%s)', 'MHTR_DOMAIN' ), get_option('woocommerce_weight_unit'));
			   		$option_arr['rate_basis']['price'] = sprintf(__( 'Total (%s)', 'MHTR_DOMAIN' ), get_woocommerce_currency_symbol());
			   		
			   		return $option_arr;
				}
				
				/**
				 * Generates HTML for top of settings page.
				 */
				function admin_options() {
					$upgrade_url = 'https://mangohour.com/plugins/woocommerce-table-rate-shipping?utm_source=wp-plugin&utm_medium=table-rate-shipping-for-woocommerce&utm_campaign=settings-upgrade-link';
				?>
					<script type="text/javascript">
							   		
				   		var options = <?php echo json_encode($this->generate_options()); ?>;
				   		
				   		function generateSelectOptionsHtml(options, selected) {
				   			var html;
				   			var selectedHtml;
				   
							for (var key in options) {
								var value = options[key];
								
								if (selected instanceof Array) {
									if (selected.indexOf(key) != -1) {
										selectedHtml = ' selected="selected"';
									} else {
										selectedHtml = '';
									}
								} else {
									if (key == selected) {
										selectedHtml = ' selected="selected"';
									} else {
										selectedHtml = '';
									}
								}
								
								html += '<option value="' + key +'"' + selectedHtml + '>' + value + '</option>';
							}
					   		
					   		return html;
				   		}
					</script>
					<style>
						.debug-col {
							display: none;
						}
						table.shippingrows tr th {
							padding-left: 10px;
						}
						.zone td {
							vertical-align: top;
						}
						.zone textarea {
							width: 100%;
						}
						#mhtr-upgrade {
							border: 3px solid #9b5c8f;
							background: #fff;
							padding: 10px;
							margin-top: 10px;
							margin-bottom: 10px;
						}
						
					</style>
					<h2><?php _e('Table Rate Shipping by Mangohour', MHTR_DOMAIN); ?></h2>
					<div class="updated woocommerce-message">
						<p><strong><?php echo sprintf( wp_kses( __( 'Check out our <a href="%s">premium version</a> for extra features and support.', MHTR_DOMAIN ), array(  'a' => array( 'href' => array() ) ) ), esc_url( $upgrade_url ) ); ?></strong></p>
						<p><strong><?php _e('Including:', MHTR_DOMAIN); ?></strong>
							<span class="dashicons dashicons-yes"></span><?php _e('Delivery options', MHTR_DOMAIN); ?>
							<span class="dashicons dashicons-yes"></span><?php _e('States, ZIPs and postal codes', MHTR_DOMAIN); ?>
							<span class="dashicons dashicons-yes"></span><?php _e('Product shipping classes', MHTR_DOMAIN); ?>
							<span class="dashicons dashicons-yes"></span><?php _e('Quantities', MHTR_DOMAIN); ?>
							<span class="dashicons dashicons-yes"></span><?php _e('Per item/kg/lbs costs', MHTR_DOMAIN); ?>
							<span class="dashicons dashicons-yes"></span><?php _e('Tax/VAT settings', MHTR_DOMAIN); ?>
						</p>
						<p class="submit"><a href="<?php echo $upgrade_url;?>" target="_blank" class="button-primary debug-report"><?php _e('Go to Table Rate Shipping Plus website', MHTR_DOMAIN); ?></a></p>
					</div>
					<table class="form-table">
					<?php $this->generate_settings_html(); ?>
					</table>
					
				<?php
				}
				
				/**
				 * Generates HTML for zone settings table.
				 */
				function generate_zones_table_html() {
					ob_start();
					?>
					<tr valign="top">
						<th scope="row" class="titledesc"><?php _e( 'Shipping Zones', MHTR_DOMAIN ); ?></th>
						<td class="forminp" id="<?php echo $this->id; ?>_zones">
							<p style="padding-bottom: 10px;"><?php _e( 'After adding a shipping zone, hit "Save changes" so that it appears as an option in the table rate section.', MHTR_DOMAIN ); ?></p>
							<table class="shippingrows widefat" cellspacing="0">
						        <col style="width:0%">
						        <col style="width:0%">
						        <col style="width:100%;">
								<thead>
									<tr>
										<th class="check-column"><input type="checkbox"></th>
										<!--<th class="debug-col"><?php _e( 'ID', MHTR_DOMAIN ); ?></th>-->
										<th><?php _e( 'Name', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Shipping zone name, will appear in table rates table.', MHTR_DOMAIN ); ?>">[?]</a></th>
										<th><?php _e( 'Countries', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Add one or more countries that are part of this shipping zone.', MHTR_DOMAIN ); ?>">[?]</a></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th colspan="3"><a href="#" class="add button"><?php _e( 'Add Shipping Zone', MHTR_DOMAIN ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete Selected Zones', MHTR_DOMAIN ); ?></a></th>
									</tr>
								</tfoot>
								<tbody class="zones">
									<tr class="zone">
										<th></th>
										<!--<td class="debug-col">0</td>-->
										<td><div style="width: 200px;"><?php _e( 'Default Zone (everywhere else)', MHTR_DOMAIN ); ?></div></td>
										<td><em><?php _e( 'All allowed countries', MHTR_DOMAIN ); ?></em></td>
									</tr>
								</tbody>
							</table>
						   	<script type="text/javascript">
						   	
						   		var lastZoneId = <?php echo $this->last_zone_id; ?>;

						   		<?php
						   		foreach ($this->zones as $zone): 
						   			$js_array = json_encode($zone);
						   			echo "jQuery('#{$this->id}_zones table tbody tr:last').before(addZoneRowHtml(false, {$js_array}));\n";
						   		endforeach;
						   		?>
						   		
						   		function addZoneRowHtml(isNew, rowArr) {

						   			if (isNew) {	
						   				lastZoneId++;
						   				rowArr = {};
							   			rowArr['id'] = lastZoneId;
							   			rowArr['name'] = '';
							   			rowArr['country'] = '';
							   			rowArr['type'] = 'country';
							   			rowArr['include'] = '';
							   			rowArr['exclude'] = '';
							   			rowArr['enabled'] = '1';
						   			}

						   			var size = jQuery('#<?php echo $this->id; ?>_zones tbody .zone').size();
						   			var html = '\
						   					<tr class="zone">\
						   						<input type="hidden" name="<?php echo $this->id; ?>_zone_id[' + size + ']" value="' + rowArr['id'] + '" />\
						   						<input type="hidden" name="<?php echo $this->id; ?>_zone_type[' + size + ']" value="' + rowArr['type'] + '" />\
						   						<input type="hidden" name="<?php echo $this->id; ?>_zone_include[' + size + ']" value="' + rowArr['include'] + '" />\
						   						<input type="hidden" name="<?php echo $this->id; ?>_zone_exclude[' + size + ']" value="' + rowArr['exclude'] + '" />\
						   						<input type="hidden" name="<?php echo $this->id; ?>_zone_enabled[' + size + ']" value="' + rowArr['enabled'] + '" />\
												<th class="check-column"><input type="checkbox" name="select" /></th>\
												<!--<td class="debug-col">\
													' + rowArr['id'] + '\
												</td>-->\
												<td>\
													<input type="text" name="<?php echo $this->id; ?>_zone_name[' + size + ']" value="' + rowArr['name'] + '" size="30" placeholder="" />\
												</td>\
												<td style="overflow:visible;">\
													<select multiple="multiple" name="<?php echo $this->id; ?>_zone_country[' + size + '][]" class="multiselect chosen_select">\
														' + generateSelectOptionsHtml(options['country'], rowArr['country']) + '\
													</select>\
												</td>\
											</tr>';
									return html;
						   		}
						   	
								jQuery(function() {
			
									jQuery('#<?php echo $this->id; ?>_zones').on( 'click', 'a.add', function(){

										jQuery('#<?php echo $this->id; ?>_zones table tbody tr:last').before(addZoneRowHtml(true, false));
			
										if (jQuery().chosen) {
											jQuery("select.chosen_select").chosen({
												width: '350px',
												disable_search_threshold: 5
											});
										} else {
											jQuery("select.chosen_select").select2();
										}
			
										return false;
									});
			
									// Remove row
									jQuery('#<?php echo $this->id; ?>_zones').on( 'click', 'a.remove', function(){
										
										var answer = confirm("<?php _e( 'Delete the selected zones?', MHTR_DOMAIN ); ?>");
										if (answer) {
											jQuery('#<?php echo $this->id; ?>_zones table tbody tr th.check-column input:checked').each(function(i, el){
												jQuery(el).closest('tr').remove();
											});
										}
										return false;
									});
			
								});
							</script>
						</td>
					</tr>
					<?php
					return ob_get_clean();
				}
				
				/**
				 * Generates HTML for table_rate settings table.
				 */
				function generate_table_rates_table_html() {
					ob_start();
					?>
					<tr valign="top">
						<th scope="row" class="titledesc"><?php _e( 'Shipping Rates', MHTR_DOMAIN ); ?></th>
						<td class="forminp" id="<?php echo $this->id; ?>_table_rates">
							<table class="shippingrows widefat" cellspacing="0">
						        <col style="width:0%">
						        <col style="width:0%">
						        <col style="width:0%">
						        <col style="width:0%">
						        <col style="width:0%">
						        <col style="width:100%;">
								<thead>
									<tr>
										<th class="check-column"><input type="checkbox"></th>
										<th class="debug-col"><?php _e( 'ID', MHTR_DOMAIN ); ?></th>
										<th><?php _e( 'Zone', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Shipping zone, as defined in Shipping Zones table.', MHTR_DOMAIN ); ?>">[?]</a></th>
										<th><?php _e( 'Condition', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Choose which metric to base your table rate on.', MHTR_DOMAIN ); ?>">[?]</a></th>
										<th><?php _e( 'Min', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Minimum, in decimal format. Inclusive.', MHTR_DOMAIN ); ?>">[?]</a></th>
										<th><?php _e( 'Max', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Maximum, in decimal format. Inclusive. To impose no upper limit, use *".', MHTR_DOMAIN ); ?>">[?]</a></th>
										<th><?php _e( 'Cost', MHTR_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Cost, excluding tax.', MHTR_DOMAIN ); ?>">[?]</a></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<th colspan="7"><a href="#" class="add button"><?php _e( 'Add Shipping Rate', MHTR_DOMAIN ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete Selected Rates', MHTR_DOMAIN ); ?></a></th>
									</tr>
								</tfoot>
								<tbody class="table_rates">

								</tbody>
							</table>
						   	<script type="text/javascript">
						   	
						   		var lastTableRateId = <?php echo $this->last_table_rate_id; ?>;

						   		<?php
						   		foreach ($this->table_rates as $table_rate): 
						   			$js_array = json_encode($table_rate);
						   			echo "jQuery(addTableRateRowHtml(false, {$js_array})).appendTo('#{$this->id}_table_rates table tbody');\n";
						   		endforeach;
						   		?>
						   		
						   		function addTableRateRowHtml(isNew, rowArr) {
						   		
						   			if (isNew) {	
						   				lastTableRateId++;
						   				rowArr = {};
							   			rowArr['id'] = lastTableRateId;
							   			rowArr['zone'] = '<?php echo (!empty($this->zones[0]['id'])) ? $this->zones[0]['id'] : 0; ?>';
							   			rowArr['basis'] = 'weight';
							   			rowArr['min'] = '0';
							   			rowArr['max'] = '*';
							   			rowArr['cost'] = '0';
							   			rowArr['enabled'] = '1';
						   			}

						   			var size = jQuery('#<?php echo $this->id; ?>_table_rates tbody .table_rate').size();
						   			var html = '\
						   					<tr class="table_rate">\
						   						<input type="hidden" name="<?php echo $this->id; ?>_table_rate_id[' + size + ']" value="' + rowArr['id'] + '" />\
						   						<input type="hidden" name="<?php echo $this->id; ?>_table_rate_enabled[' + size + ']" value="' + rowArr['enabled'] + '" />\
												<th class="check-column"><input type="checkbox" name="select" /></th>\
												<td class="debug-col">\
													' + rowArr['id'] + '\
												</td>\
												<td>\
													<select name="<?php echo $this->id; ?>_table_rate_zone[' + size + ']">\
														' + generateSelectOptionsHtml(options['table_rate_zone'], rowArr['zone']) + '\
													</select>\
												</td>\
												<td>\
													<select name="<?php echo $this->id; ?>_table_rate_basis[' + size + ']">\
														' + generateSelectOptionsHtml(options['rate_basis'], rowArr['basis']) + '\
													</select>\
												</td>\
												<td>\
													<input type="text" name="<?php echo $this->id; ?>_table_rate_min[' + size + ']" value="' + rowArr['min'] + '" placeholder="0" size="4" />\
												</td>\
												<td>\
													<input type="text" name="<?php echo $this->id; ?>_table_rate_max[' + size + ']" value="' + rowArr['max'] + '" placeholder="*" size="4" />\
												</td>\
												<td>\
													<input type="text" name="<?php echo $this->id; ?>_table_rate_cost[' + size + ']" value="' + rowArr['cost'] + '" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" size="4" class="wc_input_price" />\
												</td>\
											</tr>';
									return html;
						   		}
						   	
								jQuery(function() {
			
									jQuery('#<?php echo $this->id; ?>_table_rates').on( 'click', 'a.add', function(){
			
										jQuery(addTableRateRowHtml(true, false)).appendTo('#<?php echo $this->id; ?>_table_rates table tbody');
			
										return false;
									});
			
									// Remove row
									jQuery('#<?php echo $this->id; ?>_table_rates').on( 'click', 'a.remove', function(){
										var answer = confirm("<?php _e( 'Delete the selected rates?', MHTR_DOMAIN ); ?>");
										if (answer) {
											jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr th.check-column input:checked').each(function(i, el){
												jQuery(el).closest('tr').remove();
											});
										}
										return false;
									});
			
								});
							</script>
						</td>
					</tr>
					<?php
					return ob_get_clean();
				}
				
				/**
				 * Process and save submitted zones.
				 */
 				function process_zones() {
					// Save the rates
					$zone_id = array();
					$zone_name = array();
					$zone_country = array();
					$zone_include = array();
					$zone_exclude = array();
					$zone_type = array();
					$zone_enabled = array();

					$zones = array();
					
					if ( isset( $_POST[ $this->id . '_zone_id'] ) ) $zone_id = array_map( 'wc_clean', $_POST[ $this->id . '_zone_id'] );
					if ( isset( $_POST[ $this->id . '_zone_name'] ) ) $zone_name = array_map( 'wc_clean', $_POST[ $this->id . '_zone_name'] );
					if ( isset( $_POST[ $this->id . '_zone_country'] ) ) $zone_country =  $_POST[ $this->id . '_zone_country'];
					if ( isset( $_POST[ $this->id . '_zone_include'] ) ) $zone_include = array_map( 'wc_clean', $_POST[ $this->id . '_zone_include'] );
					if ( isset( $_POST[ $this->id . '_zone_exclude'] ) ) $zone_exclude = array_map( 'wc_clean', $_POST[ $this->id . '_zone_exclude'] );
					if ( isset( $_POST[ $this->id . '_zone_type'] ) ) $zone_type = array_map( 'wc_clean', $_POST[ $this->id . '_zone_type'] );
					if ( isset( $_POST[ $this->id . '_zone_enabled'] ) ) $zone_enabled = array_map( 'wc_clean', $_POST[ $this->id . '_zone_enabled'] );
					
					// Get max key
					$values = $zone_id;
					ksort( $values );
					$value = end( $values );
					$key = key( $values );
					
					for ( $i = 0; $i <= $key; $i++ ) {
						if ( isset( $zone_id[ $i ] ) 
							&& ! empty( $zone_name[ $i ] )
							&& ! empty( $zone_country[ $i ] )
							&& isset( $zone_include[ $i ] )
							&& isset( $zone_exclude[ $i ] )
							&& isset( $zone_type[ $i ] )
							&& isset( $zone_enabled[ $i ] ) ){

							// Add to flat rates array
							$zones[] = array(
								'id' => $zone_id[ $i ],
								'name' => $zone_name[ $i ],
								'country' => $zone_country[ $i ],
								'include' => $zone_include[ $i ],
								'exclude' => $zone_exclude[ $i ],
								'type' => $zone_type[ $i ],
								'enabled' => $zone_enabled[ $i ],
							);
						}
					}
					
					if ( (!empty($zone_id[$key]))
						&& ($zone_id[$key] > $this->last_zone_id)
						&& (is_numeric($zone_id[$key]))) {
						$highest_zone_id = $zone_id[$key];
						update_option( $this->last_zone_id_option, $highest_zone_id );
					}
					
					update_option( $this->zones_option, $zones );
					
					$this->get_zones();
					
				}
				
				/**
				 * Retrieves zones array from database.
				 */
				function get_zones() {
					$this->zones = array_filter( (array) get_option( $this->zones_option ) );
				}
				
				/**
				 * Retrieves last zone id from database.
				 */
				function get_last_zone_id() {
					$this->last_zone_id = (int)get_option( $this->last_zone_id_option );
				}
 
 				/**
				 * Process and save submitted table_rates.
				 */
				function process_table_rates() {
					// Save the rates
					$table_rate_id = array();
					$table_rate_zone = array();
					$table_rate_basis = array();
					$table_rate_min = array();
					$table_rate_max = array();
					$table_rate_cost = array();
					$table_rate_enabled = array();
					
					$table_rates = array();
					
					if ( isset( $_POST[ $this->id . '_table_rate_id'] ) ) $table_rate_id = array_map( 'wc_clean', $_POST[ $this->id . '_table_rate_id'] );
					if ( isset( $_POST[ $this->id . '_table_rate_zone'] ) ) $table_rate_zone = array_map( 'wc_clean', $_POST[ $this->id . '_table_rate_zone'] );
					if ( isset( $_POST[ $this->id . '_table_rate_basis'] ) ) $table_rate_basis = array_map( 'wc_clean', $_POST[ $this->id . '_table_rate_basis'] );
					if ( isset( $_POST[ $this->id . '_table_rate_min'] ) )   $table_rate_min   = array_map( 'stripslashes', $_POST[ $this->id . '_table_rate_min'] );
					if ( isset( $_POST[ $this->id . '_table_rate_max'] ) )   $table_rate_max   = array_map( 'stripslashes', $_POST[ $this->id . '_table_rate_max'] );
					if ( isset( $_POST[ $this->id . '_table_rate_cost'] ) )  $table_rate_cost  = array_map( 'stripslashes', $_POST[ $this->id . '_table_rate_cost'] );
					if ( isset( $_POST[ $this->id . '_table_rate_enabled'] ) ) $table_rate_enabled = array_map( 'wc_clean', $_POST[ $this->id . '_table_rate_enabled'] );
										
					// Get max key
					$values = $table_rate_id;
					ksort( $values );
					$value = end( $values );
					$key = key( $values );
					
					for ( $i = 0; $i <= $key; $i++ ) {
						if ( isset( $table_rate_id[ $i ] ) 
							&& isset( $table_rate_zone[ $i ] )
							&& isset( $table_rate_basis[ $i ] )
							&& isset( $table_rate_min[ $i ] )
							&& isset( $table_rate_max[ $i ] )
							&& isset( $table_rate_cost[ $i ] )
							&& isset( $table_rate_enabled[ $i ] ) ) {
					
							$table_rate_cost[ $i ] = wc_format_decimal( $table_rate_cost[$i] );
					
							// Add table_rates to array
							$table_rates[] = array(
								'id' => $table_rate_id[ $i ],
								'zone' => $table_rate_zone[ $i ],
								'basis' => $table_rate_basis[ $i ],
								'min' => $table_rate_min[ $i ],
								'max' => $table_rate_max[ $i ],
								'cost' => $table_rate_cost[ $i ],
								'enabled' => $table_rate_enabled[ $i ],
							);
						}
					}
					
					if ( (!empty($table_rate_id[$key]))
						&& ($table_rate_id[$key] > $this->last_table_rate_id)
						&& (is_numeric($table_rate_id[$key]))) {
						$highest_table_rate_id = $table_rate_id[$key];
						update_option( $this->last_table_rate_id_option, $highest_table_rate_id );
					}
					
					update_option( $this->table_rate_option, $table_rates );
					
					$this->get_table_rates();
					
				}
 
				/**
				 * Retrieves table_rates array from database.
				 */
				function get_table_rates() {
					$this->table_rates = array_filter( (array) get_option( $this->table_rate_option ) );
				}
				
				/**
				 * Retrieves last table_rate id from database.
				 */
				function get_last_table_rate_id() {
					$this->last_table_rate_id = (int)get_option( $this->last_table_rate_id_option );
				}
				
				/*
					Retrieves available zone ids for supplied shipping address
				*/
				function get_available_zones($package) {
					
					$destination_country = $package['destination']['country'];
					$available_zones = array();
					
					foreach ($this->zones as $zone):
						if ( !empty($zone['country']) && in_array($destination_country, $zone['country']) ) {
							$available_zones[] = $zone['id'];
						}
					endforeach;
					
					if (empty($available_zones)) {
						$found = false;
						foreach (WC()->countries->get_shipping_countries() as $id => $value):
							if ($destination_country == $id) {
								$found = true;
							}
						endforeach;
						if ($found) {						
							$available_zones[] = '0'; // "Everywhere else" zone	
						}
					}
					
					return $available_zones;
				}
				
				/*
					Retrieves available table_rates for cart and supplied shipping addresss
				*/
				function get_available_table_rates($package) {
					
					$available_zones = $this->get_available_zones($package);
					$available_table_rates = array();
					$weight = $this->get_package_stat($package, 'weight');
					$total = $this->get_package_stat($package, 'total');
					
					foreach ($this->table_rates as $table_rate):
						
						// Is table_rate for an available zone?
						$zone_pass = (in_array($table_rate['zone'], $available_zones));
						
						// Assign minimum and maximum values to variables					
						$min_value = apply_filters('mh_table_rate_shipping_min_value', $table_rate['min'], $table_rate['basis']);
						$max_value = apply_filters('mh_table_rate_shipping_max_value', $table_rate['max'], $table_rate['basis']);
						
						// Is table_rate valid for basket weight?
						if ($table_rate['basis'] == 'weight') {
							$weight_pass = (($weight >= $min_value) && ($this->is_less_than($weight, $max_value)));
						} else {
							$weight_pass = true;
						}
						
						// Is table_rate valid for basket total?
						if ($table_rate['basis'] == 'price') {
							$total_pass = (($total >= $min_value) && ($this->is_less_than($total, $max_value)));
						} else {
							$total_pass = true;
						}
						
						// Accept table_rate if passes all tests
						if ($zone_pass && $weight_pass && $total_pass) {
							$available_table_rates[] = $table_rate;
						}
						
					endforeach;
					
					return $available_table_rates;
				}

				/*
					Retrieves a package stat
				*/
				function get_package_stat($package, $stat) {
				
					$total = 0;

					foreach ( $package['contents'] as $cart_item_key => $cart_item ) {

						$product = $cart_item['data'];
						
						if ($stat == 'weight') {
							$total += ($product->get_weight() * $cart_item['quantity']);
						} else if ($stat == 'total') {
							$total += ($product->get_price() * $cart_item['quantity']);
						}
					}
					
					return $total;
				}
				
				/*
					Return true if value less than max, incl. "*"
				*/
				function is_less_than($value, $max) {
					if ($max == '*') {
						return true;
					} else {
						return ($value <= $max);
					}
				}
				
				/*
					Retrieves an array item by searching for an id value
				*/
				function find_by_id($array, $id) {
					foreach($array as $a):
						if ($a['id'] == $id) {
							return $a;
						}
					endforeach;
					return false;
				}
				
				/*
					Retrieves cheapest rate from a list of table_rates.
				*/
				function pick_cheapest_table_rate($table_rates) {
				
					$cheapest = false;
					
					foreach ($table_rates as $table_rate):

						if ($cheapest == false) {
							$cheapest = $table_rate;
						} else {
							if ($table_rate['cost'] < $cheapest['cost']) {
								$cheapest = $table_rate;
							}
						}

					endforeach;
					
					return $cheapest;
				}
				
				/* 
					Calculate shipping cost. This is called by WooCommerce
				*/
				public function calculate_shipping( $package ) {
				
					$available_table_rates = $this->get_available_table_rates($package);
					
					$table_rate = $this->pick_cheapest_table_rate($available_table_rates);
					
					if ($this->settings['tax_status'] == 'none') {
						$tax = false;
					} else {
						$tax = '';
					}
					
					if ($table_rate != false) {
					
						$cost = $table_rate['cost'] + $this->settings['handling_fee'];
						
						$rate = array(
							'id' => $this->id,
							'label' => $this->title,
							'cost' => $cost,
							'taxes' => $tax,
							'calc_tax' => 'per_order'
						);
	
						// Register the rate
						$this->add_rate( $rate );
						
					}
				}
			}
		}
	}
	add_action( 'woocommerce_shipping_init', 'mh_wc_table_rate_init' );

	function add_mh_wc_table_rate( $methods ) {
		$methods[] = 'MH_Table_Rate_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_mh_wc_table_rate' );
	
	function mh_wc_table_rate_textdomain() {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( MHTR_DOMAIN, false, $plugin_dir . '/languages' );
	}
	add_action('plugins_loaded', 'mh_wc_table_rate_textdomain');
	
}