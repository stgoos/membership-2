<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Displays the Import preview.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_View_Settings_Import extends MS_View {

	/**
	 * Displays the import preview form.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		ob_start();
		MS_Helper_Html::settings_box(
			array(
				$fields['object'],
				$fields['details'],
				$fields['sep'],
				$fields['clear_all'],
				$fields['back'],
				$fields['import'],
				$fields['nonce'],
				$fields['action'],
			),
			__( 'Import Overview', MS_TEXT_DOMAIN )
		);

		MS_Helper_Html::settings_box(
			array( $fields['memberships'] ),
			__( 'List of all memberships', MS_TEXT_DOMAIN ),
			'',
			'open'
		);

		MS_Helper_Html::settings_box(
			array( $fields['members'] ),
			__( 'List of all members', MS_TEXT_DOMAIN ),
			'',
			'open'
		);

		$html = ob_get_clean();

		return apply_filters(
			'ms_import_preview_object',
			$html,
			$data
		);
	}

	/**
	 * Prepare the HTML fields that can be displayed
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		$data = apply_filters(
			'ms_import_preview_data_before',
			$this->data['model']->source
		);

		// List of known Membership types; used to display the nice-name
		$ms_types = MS_Model_Membership::get_types();

		// Prepare the "Memberships" table
		$memberships = array(
			array(
				__( 'Membership name', MS_TEXT_DOMAIN ),
				__( 'Membership Type', MS_TEXT_DOMAIN ),
				__( 'Description', MS_TEXT_DOMAIN ),
				__( 'Sub-Memberships', MS_TEXT_DOMAIN ),
			),
		);

		foreach ( $data->memberships as $item ) {
			if ( ! isset( $ms_types[$item->type] ) ) {
				$item->type = MS_Model_Membership::TYPE_SIMPLE;
			}

			$memberships[] = array(
				$item->name,
				$ms_types[$item->type],
				$item->description,
				is_array( $item->children ) ? count( $item->children ) : '',
			);
		}

		// Prepare the "Members" table
		$members = array(
			array(
				__( 'Username', MS_TEXT_DOMAIN ),
				__( 'Email', MS_TEXT_DOMAIN ),
				__( 'Subscriptions', MS_TEXT_DOMAIN ),
				__( 'Invoices', MS_TEXT_DOMAIN ),
			),
		);

		foreach ( $data->members as $item ) {
			$inv_count = 0;
			foreach ( $item->registrations as $registration ) {
				$inv_count += count( $registration->invoices );
			}

			$members[] = array(
				$item->username,
				$item->email,
				count( $item->registrations ),
				$inv_count,
			);
		}

		// Prepare the return value.
		$fields = array();

		$fields['object'] = array(
			'id' => 'object',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => json_encode( $data ),
		);

		$fields['details'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => array(
				array(
					__( 'Data source', MS_TEXT_DOMAIN ),
					$data->source,
				),
				array(
					__( 'Export time', MS_TEXT_DOMAIN ),
					$data->export_time,
				),
				array(
					__( 'Memberships', MS_TEXT_DOMAIN ),
					count( $data->memberships ),
				),
				array(
					__( 'Members', MS_TEXT_DOMAIN ),
					count( $data->members ),
				),
			),
			'field_options' => array(
				'head_col' => true,
				'head_row' => false,
				'col_class' => array( 'preview-label', 'preview-data' ),
			)
		);

		$fields['clear_all'] = array(
			'id' => 'clear_all',
			'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
			'title' => __( 'Replace current content with import data (removes existing Memberships/Members before importing data)', MS_TEXT_DOMAIN ),
			'class' => 'widefat',
		);

		$fields['memberships'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => $memberships,
			'field_options' => array(
				'head_col' => false,
				'head_row' => true,
				'col_class' => array( 'preview-name', 'preview-type', 'preview-desc', 'preview-count' ),
			)
		);

		$fields['members'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'class' => 'ms-import-preview',
			'value' => $members,
			'field_options' => array(
				'head_col' => false,
				'head_row' => true,
				'col_class' => array( 'preview-name', 'preview-email', 'preview-count', 'preview-count' ),
			)
		);

		$fields['sep'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
		);

		$fields['back'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'class' => 'wpmui-field-button button',
			'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
			'url' => $_SERVER['REQUEST_URI'],
		);

		$fields['import'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Import', MS_TEXT_DOMAIN ),
		);

		$fields['action'] = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Import::ACTION_IMPORT,
		);

		$fields['nonce'] = array(
			'id' => '_wpnonce',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => wp_create_nonce( MS_Controller_Import::ACTION_IMPORT ),
		);

		return $fields;
	}

}
