<?php
/**
 * Team importer - import teams into SportsPress.
 *
 * @author 		ThemeBoy
 * @category 	Admin
 * @package 	SportsPress/Admin/Importers
 * @version     0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WP_Importer' ) ) {
	class SP_Team_Importer extends SP_Importer {

		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->import_page = 'sportspress_team_csv';
		}

		/**
		 * import function.
		 *
		 * @access public
		 * @param mixed $file
		 * @return void
		 */
		function import( $file ) {
			global $wpdb;

			$this->imported = $this->skipped = 0;

			if ( ! is_file($file) ):
				$this->footer();
				die();
			endif;

			ini_set( 'auto_detect_line_endings', '1' );

			if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ):

				$header = fgetcsv( $handle, 0, $this->delimiter );

				if ( sizeof( $header ) == 3 ):

					$loop = 0;

					while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ):

						list( $name, $leagues, $seasons ) = $row;

						$team_object = get_page_by_title( $name, OBJECT, 'sp_team' );

						if ( ! $name || $team_object ):
							$this->skipped++;
							continue;
						endif;

						$args = array( 'post_type' => 'sp_team', 'post_status' => 'publish', 'post_title' => $name );

						$id = wp_insert_post( $args );

						// Flag as import
						update_post_meta( $id, '_sp_import', 1 );

						// Update leagues
						$leagues = explode( '|', $leagues );
						wp_set_object_terms( $id, $leagues, 'sp_league', false );

						// Update seasons
						$seasons = explode( '|', $seasons );
						wp_set_object_terms( $id, $seasons, 'sp_season', false );

						$loop ++;
						$this->imported++;
				    endwhile;

				else:

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'sportspress' ) . '</strong><br />';
					_e( 'The CSV is invalid.', 'sportspress' ) . '</p>';
					$this->footer();
					die();

				endif;

			    fclose( $handle );
			endif;

			// Show Result
			echo '<div class="updated settings-error below-h2"><p>
				'.sprintf( __( 'Import complete - imported <strong>%s</strong> teams and skipped <strong>%s</strong>.', 'sportspress' ), $this->imported, $this->skipped ).'
			</p></div>';

			$this->import_end();
		}

		/**
		 * Performs post-import cleanup of files and the cache
		 */
		function import_end() {
			echo '<p>' . __( 'All done!', 'sportspress' ) . ' <a href="' . admin_url('edit.php?post_type=sp_team') . '">' . __( 'View Teams', 'sportspress' ) . '</a>' . '</p>';

			do_action( 'import_end' );
		}

		/**
		 * header function.
		 *
		 * @access public
		 * @return void
		 */
		function header() {
			echo '<div class="wrap"><h2>' . __( 'Import Teams', 'sportspress' ) . '</h2>';
		}

		/**
		 * greet function.
		 *
		 * @access public
		 * @return void
		 */
		function greet() {
	
			echo '<div class="narrow">';
			echo '<p>' . __( 'Hi there! Choose a .csv file to upload, then click "Upload file and import".', 'sportspress' ).'</p>';

			echo '<p>' . sprintf( __( 'Teams need to be defined with columns in a specific order (3 columns). <a href="%s">Click here to download a sample</a>.', 'sportspress' ), plugin_dir_url( SP_PLUGIN_FILE ) . 'dummy-data/teams-sample.csv' ) . '</p>';

			$action = 'admin.php?import=sportspress_team_csv&step=1';

			$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
			$size = size_format( $bytes );
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) :
				?><div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:', 'sportspress'); ?></p>
				<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
			else :
				?>
				<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="upload"><?php _e( 'Choose a file from your computer:', 'sportspress' ); ?></label>
								</th>
								<td>
									<input type="file" id="upload" name="import" size="25" />
									<input type="hidden" name="action" value="save" />
									<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
									<small><?php printf( __( 'Maximum size: %s', 'sportspress' ), $size ); ?></small>
								</td>
							</tr>
							<tr>
								<th>
									<label for="file_url"><?php _e( 'OR enter path to file:', 'sportspress' ); ?></label>
								</th>
								<td>
									<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Delimiter', 'sportspress' ); ?></label><br/></th>
								<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'sportspress' ); ?>" />
					</p>
				</form>
				<?php
			endif;

			echo '</div>';
		}
	}
}
