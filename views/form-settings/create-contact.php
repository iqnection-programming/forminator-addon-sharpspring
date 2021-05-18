<?php
// defaults
$vars = array(
	'error_message' => '',
	'name'          => '',
	'name_error'    => '',
	'multi_id'      => '',
	'fields'        => array(),
	'form_fields'   => array(),
	'email_fields'  => array(),
	'properties'    => array()
);
/** @var array $template_vars */

foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}

$custom_field_map = isset( $vars['custom_fields_map'] ) ? array_filter( $vars['custom_fields_map'] ) : array();
?>
<div class="integration-header">

	<h3 id="dialogTitle2" class="sui-box-title"><?php echo esc_html( __( 'Create Contact', 'forminator' ) ); ?></h3>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<span class="sui-notice sui-notice-error"><p><?php echo esc_html( $vars['error_message'] ); ?></p></span>
	<?php endif; ?>

</div>

<form style="display: block; margin-top: -10px;">

	<div tabindex="0" role="group" class="sui-form-field" style="margin-bottom: 0;">

		<label for="sharpspring-list-id" id="sharpspring-list-id-label" class="sui-label"><?php esc_html_e( 'Field Mapping', 'forminator' ); ?></label>

		<table class="sui-table" style="margin-top: 5px; margin-bottom: 0;">

			<thead>

			<tr>
				<th><?php esc_html_e( 'SharpSpring Fields', 'forminator' ); ?></th>
				<th><?php esc_html_e( 'Forminator Fields', 'forminator' ); ?></th>
			</tr>

			</thead>

			<tbody>

			<?php
			if ( ! empty( $vars['fields'] ) ) :

				foreach ( $vars['fields'] as $key => $field_title ) : ?>

					<tr>

						<td>
							<?php echo esc_html( $field_title ); ?>
							<?php if ( 'emailAddress' === $key ) : ?>
								<span class="integrations-required-field">*</span>
							<?php endif; ?>
						</td>

						<td>
							<?php
							$forminator_fields = $vars['form_fields'];
							if ( 'emailAddress' === $key ) {
								$forminator_fields = $vars['email_fields'];
							}
							$current_error    = '';
							$current_selected = '';
							if ( isset( $vars[ $key . '_error' ] ) && ! empty( $vars[ $key . '_error' ] ) ) {
								$current_error = $vars[ $key . '_error' ];
							}
							if ( isset( $vars['fields_map'][ $key ] ) && ! empty( $vars['fields_map'][ $key ] ) ) {
								$current_selected = $vars['fields_map'][ $key ];
							}
							?>
							<div class="sui-form-field <?php echo esc_attr( ! empty( $current_error ) ? 'sui-form-field-error' : '' ); ?>"<?php echo ( ! empty( $current_error ) ) ? ' style="padding-top: 5px;"' : ''; ?>>
								<select class="sui-select" name="fields_map[<?php echo esc_attr( $key ); ?>]">
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
									<?php
									if ( ! empty( $forminator_fields ) ) :
										foreach ( $forminator_fields as $forminator_field ) : ?>
											<option value="<?php echo esc_attr( $forminator_field['element_id'] ); ?>"
												<?php selected( $current_selected, $forminator_field['element_id'] ); ?>>
												<?php echo esc_html( $forminator_field['field_label'] . ' | ' . $forminator_field['element_id'] ); ?>
											</option>
										<?php endforeach;
									endif;
									?>
								</select>
								<?php if ( ! empty( $current_error ) ) : ?>
									<span class="sui-error-message"
										  style="margin-top: 5px; margin-bottom: 5px;"><?php echo esc_html( $current_error ); ?></span>
								<?php endif; ?>
							</div>
						</td>
					</tr>
				<?php endforeach;
			endif;
			if ( ! empty( $custom_field_map ) ) {
				foreach ( $custom_field_map as $custom=> $custom_field ) { ?>
					<tr class="custom-field" id="custom-field">
						<td>
							<div class="sui-form-field">
								<select class="sui-select" name=custom_property[]">
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
									<?php if ( ! empty( $vars['properties'] ) ) {
										foreach ( $vars['properties'] as $p => $prop ) { ?>
											<option value="<?php echo esc_html( $p ); ?>" <?php selected( $custom, $p ); ?>><?php echo esc_html( $prop ); ?></option>
										<?php }
									} ?>
								</select>
							</div>
						</td>
						<td>
							<div class="fui-select-with-delete">

								<div class="sui-form-field">
									<select class="sui-select" name="custom_field[]">
										<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
										<?php
										if ( ! empty( $forminator_fields ) ) :
											foreach ( $forminator_fields as $forminator_field ) : ?>
												<option value="<?php echo esc_attr( $forminator_field['element_id'] ); ?>" <?php selected( $custom_field, $forminator_field['element_id'] ); ?>>
													<?php echo esc_html( $forminator_field['field_label'] . ' | ' . $forminator_field['element_id'] ); ?>
												</option>
											<?php endforeach;
										endif;
										?>
									</select>
								</div>

								<button class="sui-button-icon sui-button-red fui-option-remove delete-sharpspring-field">
									<i class="sui-icon-trash" aria-hidden="true"></i>
								</button>

							</div>
						</td>
					</tr>
				<?php  }
			} else { ?>
				<tr class="custom-field" id="custom-field" style="display: none;">
					<td>
						<div class="sui-form-field">
							<select class="sui-select" name=custom_property[]">
								<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
								<?php if ( ! empty( $vars['properties'] ) ) {
									foreach ( $vars['properties'] as $p => $prop ) { ?>
										<option value="<?php echo esc_html( $p ); ?>"><?php echo esc_html( $prop ); ?></option>
									<?php }
								} ?>
							</select>
						</div>
					</td>
					<td>

						<div class="fui-select-with-delete">

							<div class="sui-form-field">
								<select class="sui-select" name="custom_field[]">
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
									<?php
									if ( ! empty( $forminator_fields ) ) :
										foreach ( $forminator_fields as $forminator_field ) : ?>
											<option value="<?php echo esc_attr( $forminator_field['element_id'] ); ?>">
												<?php echo esc_html( $forminator_field['field_label'] . ' | ' . $forminator_field['element_id'] ); ?>
											</option>
										<?php endforeach;
									endif;
									?>
								</select>
							</div>

							<button class="sui-button-icon sui-button-red fui-option-remove delete-sharpspring-field">
								<i class="sui-icon-trash" aria-hidden="true"></i>
							</button>

						</div>

					</td>
				</tr>
			<?php } ?>
			<tr class="add-additional-field">
				<td>
					<div class="sui-button sui-button-ghost add-sharpspring-field">
						<i class="sui-icon-plus" aria-hidden="true"></i>
						<?php esc_html_e( 'Add Additional field', 'forminator' ); ?>
					</div>
				</td>
				<td></td>
			</tr>

			</tbody>

		</table>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>" />

</form>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function (e) {
            $(".add-sharpspring-field").unbind().click(function(e) {
				e.preventDefault();
				if( $('.custom-field:visible').length < 1 ) {
					$('#custom-field').show();
				} else {
					var clone_field = $('#custom-field').clone();
					$('.add-additional-field').before( clone_field );
					clone_field.find('.select2').remove();
					clone_field.find('select.sui-select').val('').removeAttr('selected');
					clone_field.find( '.sui-select' ).SUIselect2({
						dropdownCssClass: 'sui-variables-dropdown sui-color-accessible'
					});
				}
			});
			$(document).on("click",".delete-sharpspring-field",function(e){
				e.preventDefault();
				if( $('.custom-field:visible').length < 2 ) {
					$(this).closest('.custom-field').find('select.sui-select').val('');
					$(this).closest('.custom-field').hide();
				} else {
					$(this).closest('.custom-field').remove();
				}
			});
		});
	})(jQuery);
</script>