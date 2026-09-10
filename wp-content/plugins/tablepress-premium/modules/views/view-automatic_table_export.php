<?php
/**
 * Automatic Table Export View.
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Automatic Table Export View class.
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Automatic_Table_Export_View extends TablePress_Export_View {

	/**
	 * Sets up the view with data and do things that are specific for this view.
	 *
	 * @since 2.0.0
	 *
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	#[\Override]
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		parent::setup( $action, $data );

		TablePress_Modules_Helper::enqueue_script( 'automatic-table-export' );

		$this->add_meta_box( 'tables-auto-export', __( 'Automatic Table Export', 'tablepress' ), array( $this, 'postbox_auto_export' ), 'additional' );
	}

	/**
	 * Prints the content of the "Automatic Export of Tables" post meta box.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_auto_export( array $data, array $box ): void {
		$this->print_script_data_json(
			'automaticTableExport',
			array(
				'exportFormats'   => $data['export_formats'],
				'csvDelimiters'   => $data['csv_delimiters'],
				'active'          => $data['auto_export_active'],
				'path'            => $data['auto_export_path'],
				'selectedFormats' => $data['auto_export_formats'],
				'csvDelimiter'    => $data['auto_export_csv_delimiter'],
			),
		);

		echo '<div id="tablepress-automatic-table-export-screen"></div>';
	}

} // class TablePress_Automatic_Table_Export_View
