<?php
/**
 * Include and setup custom metaboxes and fields.
 *
 * @category YourThemeOrPlugin
 * @package  Metaboxes
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/webdevstudios/Custom-Metaboxes-and-Fields-for-WordPress
 */

add_filter( 'cmb_meta_boxes', 'opt_post_metaboxes' );
/**
 * Define the metabox and field configurations.
 *
 * @param  array $meta_boxes
 * @return array
 */
function opt_post_metaboxes ( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_opt_';

	/**
	 * Metaboxes for the 'op_tool_type' post type
	 */
	$meta_boxes['opt_post_metabox'] = array(
		'id'         => 'opt_post_metabox',
		'title'      => __( 'OP Tool Post Options', 'op_tool' ),
		'pages'      => array( 'op_tool_type', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		// 'cmb_styles' => true, // Enqueue the CMB stylesheet on the frontend
		'fields'     => array(
			array(
				'name' => __( 'Delay', 'op_tool' ),
				'desc' => __( 'Enter the number of seconds to delay before allowing user to continue.', 'op_tool' ),
				'id'   => $prefix . 'read_delay',
				'type' => 'text_small',
				'default' => '5',
				// 'repeatable' => true,
			),
		),
	);

	// Add other metaboxes as needed

	return $meta_boxes;
}

add_action( 'init', 'cmb_initialize_cmb_meta_boxes', 9999 );
/**
 * Initialize the metabox class.
 */
function cmb_initialize_cmb_meta_boxes() {

	if ( ! class_exists( 'cmb_Meta_Box' ) )
		require_once ( 'library/metabox/init.php' );

}

/**
 * CMB Theme Options
 * @version 0.1.0
 */
class op_tool_Admin {

    /**
     * Option key, and option page slug
     * @var string
     */
    private $key = 'op_tool_options';

    /**
     * Array of metaboxes/fields
     * @var array
     */
    protected $option_metabox = array();

    /**
     * Options Page title
     * @var string
     */
    protected $title = '';

    /**
     * Options Page hook
     * @var string
     */
    protected $options_page = '';

    /**
     * Constructor
     * @since 0.1.0
     */
    public function __construct() {
        // Set our title
        $this->title = __( 'OP Tool Options', 'op_tool' );
    }

    /**
     * Initiate our hooks
     * @since 0.1.0
     */
    public function hooks() {
        add_action( 'admin_init', array( $this, 'init' ) );
        add_action( 'admin_menu', array( $this, 'add_options_page' ) );
    }

    /**
     * Register our setting to WP
     * @since  0.1.0
     */
    public function init() {
        register_setting( $this->key, $this->key );
    }

    /**
     * Add menu options page
     * @since 0.1.0
     */
    public function add_options_page() {
        $this->options_page = add_menu_page( $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
    }

    /**
     * Admin page markup. Mostly handled by CMB
     * @since  0.1.0
     */
    public function admin_page_display() {
        ?>
        <div class="wrap cmb_options_page <?php echo $this->key; ?>">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
            <?php cmb_metabox_form( self::option_fields(), $this->key ); ?>
        </div>
        <?php
    }

    /**
     * Defines the theme option metabox and field configuration
     * @since  0.1.0
     * @return array
     */
    public function option_fields() {

        // Only need to initiate the array once per page-load
        if ( ! empty( $this->option_metabox ) ) {
            return $this->option_metabox;
        }

        $completion_badge_array = EMS_OP_Tool::wpbadger_award_choose_badge ();

        $this->fields = array(
			array(
				'name' => __( 'Content version number', 'op_tool' ),
				'desc' => __( 'increase this version number after any change to the content of this resource', 'op_tool' ),
				'id'   => $prefix . 'content_version',
				'type' => 'text_small',
				// 'repeatable' => true,
			),
			array(
				'name'    => __( 'Completion badge', 'op_tool' ),
				'desc'    => __( 'select the badge to be awarded for the completion of this OP Tool', 'op_tool' ),
				'id'      => $prefix . 'completion_badge_id',
				'type'    => 'select',
				'options' => $completion_badge_array,
			),
			array(
				'name' => __( 'Badge evidence text', 'op_tool' ),
				'desc' => __( 'this text will be displayed when someone follows the evidence link of a completion badge', 'op_tool' ),
				'id'   => $prefix . 'badge_evidence_text',
				'type' => 'textarea_small',
			),
			array(
				'name' => __( 'Badge admin email text', 'op_tool' ),
				'desc' => __( 'the text that will appear in the email to the Administrator the user elected to inform', 'op_tool' ),
				'id'   => $prefix . 'badge_notify_admin_text',
				'type' => 'textarea_small',
			),
			/*array(
			    'id'          => $prefix . 'ems_admin_contacts_group',
			    'type'        => 'group',
			    'description' => __( 'The EMS admin contacts for each institution (to be sent notifications when their students complete)', 'op_tool' ),
			    'options'     => array(
			        'group_title'   => __( 'Institution {#}', 'op_tool' ), // since version 1.1.4, {#} gets replaced by row number
			        'add_button'    => __( 'Add Another Institution', 'op_tool' ),
			        'remove_button' => __( 'Remove Institution', 'op_tool' ),
			        'sortable'      => true, // beta
			    ),
				'show_names'  => true,
			    // Fields array works the same, except id's only need to be unique for this group. Prefix is not needed.
			    'fields'       => array(
					array(
						'name' => __( 'Institution', 'op_tool' ),
						//'desc' => __( 'enter the intitution\'s name', 'op_tool' ),
						'id'   => $prefix . 'institution_name',
						'type' => 'text',
						// 'repeatable' => true,
					),
					array(
						'name' => __( 'EMS admin\'s email address', 'op_tool' ),
						//'desc' => __( 'enter the email address of the person who deals with EMS OPT certificates', 'op_tool' ),
						'id'   => $prefix . 'institution_admin_email',
						'type' => 'text_email',
						// 'repeatable' => true,
					),
			    ),
			),*/
        );

        $this->option_metabox = array(
            'id'         => 'option_metabox',
            'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
            'show_names' => true,
            'fields'     => $this->fields,
        );

        return $this->option_metabox;
    }

    /**
     * Public getter method for retrieving protected/private variables
     * @since  0.1.0
     * @param  string  $field Field to retrieve
     * @return mixed          Field value or exception is thrown
     */
    public function __get( $field ) {

        // Allowed fields to retrieve
        if ( in_array( $field, array( 'key', 'fields', 'title', 'options_page' ), true ) ) {
            return $this->{$field};
        }
        if ( 'option_metabox' === $field ) {
            return $this->option_fields();
        }

        throw new Exception( 'Invalid property: ' . $field );
    }

}

// Get it started
global $op_tool_Admin;

$op_tool_Admin = new op_tool_Admin();
$op_tool_Admin->hooks();

