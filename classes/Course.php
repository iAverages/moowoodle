<?php
namespace MooWoodle;
class Course {
	private $labels = array();
	private $table_heading = array();
	private $endpoint_slug = '';
	public function __construct() {
		$this->labels['course'] = array(
			'singular' => __('Course', 'moowoodle'),
			'plural' => __('Courses', 'moowoodle'),
			'menu' => __('Courses', 'moowoodle'),
		);
		$this->register_course_post_type();
		$this->register_course_cat_taxonomy();
		$this->add_my_courses_endpoint();
		add_action('woocommerce_process_product_meta', array(&$this, 'save_product_meta_data'));
		add_filter('woocommerce_product_data_tabs', array(&$this, 'moowoodle_linked_course_tab'), 99, 1);
		add_action('woocommerce_product_data_panels', array(&$this, 'moowoodle_linked_course_panals'));
		add_filter('woocommerce_product_class', array($this, 'product_type_subcription_warning'), 10, 2);
		add_filter('woocommerce_account_menu_items', array($this, 'my_courses_page_link'));
		add_action('woocommerce_account_' . $this->endpoint_slug . '_endpoint', array($this, 'woocommerce_account_my_courses_endpoint'));
		add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles'));
	}
	public function register_course_post_type() {
		$args = array(
			'labels' => array(
				'name' => sprintf(_x('%s', 'post type general name', 'moowoodle'), $this->labels['course']['plural']),
				'singular_name' => sprintf(_x('%s', 'post type singular name', 'moowoodle'), $this->labels['course']['singular']),
				'add_new' => sprintf(_x('Add New %s', 'course', 'moowoodle'), $this->labels['course']['singular']),
				'add_new_item' => sprintf(__('Add New %s', 'moowoodle'), $this->labels['course']['singular']),
				'edit_item' => sprintf(__('Edit %s', 'moowoodle'), $this->labels['course']['singular']),
				'new_item' => sprintf(__('New %s', 'moowoodle'), $this->labels['course']['singular']),
				'all_items' => sprintf(__('%s', 'moowoodle'), $this->labels['course']['plural']),
				'view_item' => sprintf(__('View %s', 'moowoodle'), $this->labels['course']['singular']),
				'search_items' => sprintf(__('Search %s', 'moowoodle'), $this->labels['course']['plural']),
				'not_found' => sprintf(__('No %s found', 'moowoodle'), strtolower($this->labels['course']['plural'])),
				'not_found_in_trash' => sprintf(__('No %s found in Trash', 'moowoodle'), strtolower($this->labels['course']['plural'])),
			),
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => true,
			'has_archive' => false,
			'hierarchical' => false,
			'show_in_menu' => false,
			'supports' => array('title', 'editor'),
			'capability_type' => 'post',
			'capabilities' => array('create_posts' => false,
				'delete_posts' => false,
			),
		);
		register_post_type('course', $args);
	}
	public function register_course_cat_taxonomy() {
		register_taxonomy('course_cat', 'course',
			array(
				'labels' => array(
					'name' => sprintf(_x('%s category', 'moowoodle'), $this->labels['course']['singular']),
					'singular_name' => sprintf(_x('%s category', 'moowoodle'), $this->labels['course']['singular']),
					'add_new_item' => sprintf(_x('Add new %s category', 'moowoodle'), $this->labels['course']['singular']),
					'new_item_name' => sprintf(_x('New %s category', 'moowoodle'), $this->labels['course']['singular']),
					'menu_name' => sprintf(_x('%s category', 'moowoodle'), $this->labels['course']['singular']), //'Categories',
					'search_items' => sprintf(_x('Search %s categories', 'moowoodle'), $this->labels['course']['singular']), //'Search Course Categories',
					'all_items' => sprintf(_x('All %s categories', 'moowoodle'), $this->labels['course']['singular']), //'All Course Categories',
					'parent_item' => sprintf(_x('Parent %s category', 'moowoodle'), $this->labels['course']['singular']), //'Parent Course Category',
					'parent_item_colon' => sprintf(_x('Parent %s category', 'moowoodle'), $this->labels['course']['singular']), //'Parent Course Category:',
					'edit_item' => sprintf(_x('Edit %s category', 'moowoodle'), $this->labels['course']['singular']), //'Edit Course Category',
					'update_item' => sprintf(_x('New %s category name', 'moowoodle'), $this->labels['course']['singular']), //'New Course Category Name'
				),
				'show_ui' => false,
				'show_tagcloud' => false,
				'hierarchical' => true,
				'query_var' => true,
			)
		);
	}
	/**
	 * Creates custom tab for product types.
	 *
	 * @access public
	 * @param array $product_data_tabs
	 * @return void
	 */
	public function moowoodle_linked_course_tab($product_data_tabs) {
		$product_data_tabs['moowoodle'] = array(
			'label' => __('Moodle Linked Course', 'moowoodle'), // translatable
			'target' => 'moowoodle_course_link_tab', // translatable
		);
		return $product_data_tabs;
	}
	/**
	 * Add meta box panal.
	 *
	 * @access public
	 * @return void
	 */
	public function moowoodle_linked_course_panals() {
		echo '<div id="moowoodle_course_link_tab" class="panel woocommerce_options_panel">';
		global $post;
		$linked_course_id = get_post_meta($post->ID, 'linked_course_id', true);
		$courses = get_posts(array('post_type' => 'course', 'numberposts' => -1, 'post_status' => 'publish'));
		?>
		<p>
			<label for="courses"><?php esc_html_e('Linked Course', 'moowoodle');?></label>
			<select id="courses-select" name="course_id">
				<option value="0"><?php esc_html_e('Select course...', 'moowoodle');?></option>
				<?php
			if (!empty($courses)) {
				foreach ($courses as $course) {
					$id = $course->ID;
					$course_short_name = get_post_meta($course->ID, '_course_short_name', true);
					$course_path = array();
					foreach(get_the_terms($id, 'course_cat') as $term){
						$course_path[] = $term->name;
					}
					$course_name = '';
					if (!empty($course_path)) {
						$course_name = implode(' / ', $course_path);
						$course_name .= ' - ';
					}
					$course_name .= $course->post_title;
					if (!empty($id)) {
						?>
								<option value="<?php echo esc_attr($id); ?>" <?php if (!empty($linked_course_id) && $id == $linked_course_id) {
							echo 'selected="selected"';
						}
						?>><?php echo $course_name;
						if (!empty($course_short_name)) {
							echo " ( " . esc_html($course_short_name) . " )";
						}
						?></option>
					<?php
				}
			}
		}
		?>
			</select>
		</p>
		<?php
		echo esc_html_e("Cannot find your course in this list?", "moowoodle");
		?>
		<a href="<?php echo esc_url(get_site_url()) ?>/wp-admin/admin.php?page=moowoodle-synchronization" target="_blank"><?php esc_html_e('Synchronize Moodle Courses from here.', 'moowoodle');?></a>
		<?php
		// Nonce field (for security)
		echo '<input type="hidden" name="product_meta_nonce" value="' . wp_create_nonce() . '">';
		echo '</div>';
	}
	public function product_type_subcription_warning($php_classname, $product_type) {
		$active_plugins = (array) get_option('active_plugins', array());
		if (is_multisite()) {
			$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
		}

		if (in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', $active_plugins) || array_key_exists('woocommerce-product/woocommerce-subscriptions.php', $active_plugins) || in_array('woocommerce-product-bundles/woocommerce-product-bundles.php', $active_plugins) || array_key_exists('woocommerce-product-bundles/woocommerce-product-bundles.php', $active_plugins)) {
			add_action('admin_notices', function(){
				if (MOOWOODLE_PRO_ADV) {
					echo '<div class="notice notice-warning is-dismissible"><p>' . __('WooComerce Subbcription and WooComerce Product Bundles is supported only with ', 'moowoodle') . '<a href="' . MOOWOODLE_PRO_SHOP_URL . '">' . __('MooWoodle Pro', 'moowoodle') . '</></p></div>';
				}
			});
		}
		return $php_classname;
	}
	/**
	 * Save product meta.
	 *
	 * @access public
	 * @param int $post_id
	 * @return void
	 */
	public function save_product_meta_data($post_id) {
		// Security check
		if (!filter_input(INPUT_POST, 'product_meta_nonce', FILTER_DEFAULT) === null && !wp_verify_nonce(filter_input(INPUT_POST, 'product_meta_nonce', FILTER_DEFAULT)) && !current_user_can('edit_product', $post_id)) {
			return $post_id;
		}
		$course_id = filter_input(INPUT_POST, 'course_id', FILTER_DEFAULT);
		if ($course_id !== null) {
			update_post_meta($post_id, 'linked_course_id', wp_kses_post($course_id));
			update_post_meta($post_id, '_sku', 'course-' . get_post_meta($course_id, '_sku'));
			update_post_meta($post_id, 'moodle_course_id', get_post_meta($course_id, 'moodle_course_id'));
		}
	}
	/**
     * Get All Caurse data.
     * @return \WP_Error| \WP_REST_Response
     */
    public function fetch_all_courses() {
        $courses = get_posts(array('post_type' => 'course', 'numberposts' => -1, 'post_status' => 'publish'));
        $formatted_courses = array();
		$pro_popup_overlay = '';
		if (MOOWOODLE_PRO_ADV) {
			$pro_popup_overlay = ' mw-pro-popup-overlay ';
		}
        foreach ($courses as $course) {
            $moodle_course_id = get_post_meta($course->ID, 'moodle_course_id', true);
            $course_short_name = get_post_meta($course->ID, '_course_short_name', true);
            $products = get_posts(array('post_type' => 'product', 'numberposts' => -1, 'post_status' => 'publish', 'meta_key' =>  'linked_course_id', 'meta_value' => $course->ID));
            $course_startdate = get_post_meta($course->ID, '_course_startdate', true);
            $course_enddate = get_post_meta($course->ID, '_course_enddate', true);
            $course_name = $course->post_title;
            $category_id = get_post_meta($course->ID, '_category_id', true);
            $term_id = Helper::moowoodle_get_term_by_moodle_id($category_id, 'course_cat', 'moowoodle_term');
            $course_category_path = get_term_meta($term_id, '_category_path', true);
            $category_ids = explode('/', $course_category_path);
            $course_path = array();
            if (!empty($category_ids)) {
                foreach ($category_ids as $cat_id) {
                    if (!empty($cat_id)) {
                        $term_id = Helper::moowoodle_get_term_by_moodle_id(intval($cat_id), 'course_cat', 'moowoodle_term');
                        $term = get_term($term_id, 'course_cat');
                        $course_path[] = $term->name;
                    }
                }
            }
            if (!empty($course_path)) {
                $course_path = implode(' / ', $course_path);
            }
            $term = get_term_by('id', $term_id, 'course_cat');
            $catagory_name = '';
            if (!empty($term)) {
                $catagory_name = '<a href="' . esc_url(admin_url('edit.php?course_cat=' . $term->slug . '&post_type=course')) . '">' . esc_html($course_path) . '</a>';
            }
            $moodle_url = '';
            if ($moodle_course_id) {
                $moodle_url = '<a href="' . esc_url(get_option('moowoodle_general_settings')["moodle_url"]) . 'course/edit.php?id=' . $moodle_course_id . '" target="_blank">' . esc_html($course_name) . '</a>';
            }
            $product_name = '';
            if ($products) {
                foreach ($products as $product) {
                    if ($product_name)  $product_name .= ' ,<br>';
                    $product_name .= '<a href="' . esc_url(admin_url() . 'post.php?post=' . $product->ID . '&action=edit') . '">' . esc_html($product->post_title) . '</a>';
                }
            }
            $enroled_user = '';
            $args = array(
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_type' => 'shop_order',
                'post_status' => 'wc-completed',
            );
            $customer_orders = wc_get_orders($args);
            $count_enrolment = 0;
            foreach ($customer_orders as $order) {
                foreach ($order->get_items() as $enrolment) {
                    $linked_course_id = get_post_meta($enrolment->get_product_id(), 'linked_course_id', true);
                    if ($linked_course_id == $course->ID) {
                        $count_enrolment++;
                    }
                }
            }
            $enroled_user = $count_enrolment;
            $date = wp_date('M j, Y', $course_startdate);
            if ($course_enddate) {
                $date .= ' - ' . wp_date('M j, Y  ', $course_enddate);
            }
            $actions = '';
            $actions .= '<div class="moowoodle-course-actions"><input type="hidden" name="course_id" value=" ' . $course->ID . '"/><button type="button" name="sync_courses" class="sync-single-course button-primary ' . $pro_popup_overlay . '" title="' . esc_attr('Sync Couse Data', 'moowoodle') . '" ' . ' ><i class="dashicons dashicons-update"></i></button>';
            if (($products)) {
                $actions .= '
                            <button type="button" name="sync_update_product" class="update-existed-single-product button-secondary ' . $pro_popup_overlay . '" title="' . esc_attr('Sync Course Data & Update Product', 'moowoodle') . '" ' . '><i class="dashicons dashicons-admin-links"></i></button>
                        </div>';
            } else {
                $actions .= '
                            <button type="button" name="sync_create_product" class="create-single-product button-secondary ' . $pro_popup_overlay . '" title="' . esc_attr('Create Product', 'moowoodle') . '" ' . '><i class="dashicons dashicons-cloud-upload"></i></button>
                        </div>';
            }
            $table_body = '';

            // Customize the data structure based on your needs
            $formatted_courses[] = array(
                'id' => $course->ID,
                'moodle_url' => $moodle_url,
                'course_short_name' => $course_short_name,
                'product_name' => $product_name == '' ? '-' : $product_name,
                'catagory_name' => $catagory_name,
                'enroled_user' => $enroled_user,
                'date' => $date,
                'actions' => $actions,
            );
        }

        return $formatted_courses;
    }
	//Adds my-courses endpoints
	function add_my_courses_endpoint() {
		$this->endpoint_slug = 'my-courses';
		$this->table_heading = array(
			__("Course Name", 'moowoodle'),
			__("Moodle User Name", 'moowoodle'),
			__("Password (First Time use Only)", 'moowoodle'),
			__("Enrolment Date", 'moowoodle'),
			__("Course Link", 'moowoodle'),
		);
		add_rewrite_endpoint($this->endpoint_slug, EP_ROOT | EP_PAGES);
		flush_rewrite_rules();
	}
	//Adds the menu item to my-account WooCommerce menu
	function my_courses_page_link($menu_links) {
		$name = __('My Courses', 'moowoodle');
		$new = array($this->endpoint_slug => $name);
		$display_settings = get_option('moowoodle_display_settings');
		if (isset($display_settings['my_courses_priority'])) {
			$priority_below = $display_settings['my_courses_priority'];
		} else {
			$priority_below = 0;
		}
		$menu_links = array_slice($menu_links, 0, $priority_below + 1, true)
		 + $new
		 + array_slice($menu_links, $priority_below + 1, NULL, true);
		return $menu_links;
	}
	function woocommerce_account_my_courses_endpoint() {
		$customer = wp_get_current_user();
		$customer_orders = array();
		$args = array(
			'numberposts' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_type' => 'shop_order',
			'post_status' => 'wc-completed',
			'customer_id' => $customer->ID,
		);
        $customer_orders = wc_get_orders($args);
		$pwd = get_user_meta($customer->ID, 'moowoodle_moodle_user_pwd', true);
		if (count($customer_orders) > 0) {
			?>
			<p>
			<div class="auto">
				<table class="table table-bordered responsive-table moodle-linked-courses widefat">
				<thead>
					<tr>
						<?php
						foreach ($this->table_heading as $key_heading => $value_heading) {
						?>
							<th>
							<?php echo $value_heading; ?>
							</th>
						<?php
						}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($customer_orders as $order) {
						$unenrolled_course = $order->get_meta( '_course_unenroled', true);
						$unenrolled_courses[] = null;
						if ($unenrolled_course != null) {
							$unenrolled_courses = str_contains($unenrolled_course, ',') ? explode(',', $unenrolled_course) : array($unenrolled_course);
						}
						foreach ($order->get_items() as $enrolment) {
							$moodle_course_id = get_post_meta($enrolment->get_product_id(), 'moodle_course_id', true);
							$course_link = Helper::get_moowoodle_course_url($moodle_course_id, 'View');
							$enrolment_date = $order->get_meta( 'moodle_user_enrolment_date', true);
							$linked_course_id = get_post_meta($enrolment->get_product_id(), 'linked_course_id', true);
							if (!$linked_course_id || in_array($moodle_course_id, $unenrolled_courses)) {
								continue;
							}
							
							?>
							<tr>
								<td>
									<?php _e(get_the_title($enrolment->get_product_id()))?>
								</td>
								<td>
									<?php _e($customer->user_login);?>
								</td>
								<td>
									<?php _e($pwd);?>
								</td>
								<td>
									<?php
									if (!empty($enrolment_date)) {
										_e(get_date_from_gmt(gmdate('M j, Y-H:i', $enrolment_date)));
									}
									?>
								</td>
								<td>
									<?php
									echo '<button type="button" class="button-tri">' . apply_filters('moodle_course_view_url', $course_link, $moodle_course_id) . '<i class="fas fa-eye"></i>' . '</button>';
									?>
								</td>
							</tr>
						<?php
						}
					}
					?>
				</tbody>
				</table>
			</div>
			</p>
			<?php
		} else {
					?>
			<h3><?php _e('You have no Course.', 'moowoodle')?></h3>
			<h3><?php _e('Kindly purchase a Course and come back here to see your course.', 'moowoodle')?></h3>
		<?php
		}
	}
	public function frontend_styles() {
		$suffix = defined('MOOWOODLE_SCRIPT_DEBUG') && MOOWOODLE_SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style('frontend_css', MOOWOODLE_PLUGIN_URL . 'assets/frontend/css/frontend' . $suffix . '.css', array(), MOOWOODLE_PLUGIN_VERSION);
	}
}
