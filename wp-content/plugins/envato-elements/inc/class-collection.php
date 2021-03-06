<?php
/**
 * Envato Elements:
 *
 * This starts things up. Registers the SPL and starts up some classes.
 *
 * @package Envato/Envato_Elements
 * @since 0.0.2
 */

namespace Envato_Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Collection registration and management.
 *
 * @since 0.0.2
 */
class Collection extends Base {

	const PAGE_SLUG = 'envato-elements-collection';

	public $category = 'beaver-builder';

	/**
	 * Collection constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'trashed_post', [ $this, 'trashed_post' ] );
	}

	public function get_url() {
		return admin_url( 'admin.php?page=' . ENVATO_ELEMENTS_SLUG );
	}

	/**
	 * Called when the user navigates to the admin menu page.
	 *
	 * @since 0.0.2
	 * @access public
	 */
	public function admin_menu_open() {

		$this->content = $this->render_template(
			'collections/js-holder.php', [
				'all_collections' => [],
			]
		);
		$this->header  = $this->render_template( 'collections/header.php' );
		echo $this->render_template( 'wrapper.php' );

	}


	public function get_remote_collection( $collection_id ) {

		$api_data = [
			'category'      => $this->category,
			'collection_id' => $collection_id,
		];

		$data = API::get_instance()->api_call( 'v1/collection', $api_data );

		if ( $data && ! is_wp_error( $data ) && ! empty( $data['data'] ) && ! empty( $data['data']['collection_id'] ) ) {
			$data['data'] = $this->filter_collection( $data['data'] );
		}

		return $data;

	}

	public function get_remote_collections( $search = [] ) {

		$api_data    = [
			'category' => $this->category,
			// 'search'   => $search,
		];
		$page_number = empty( $search['pg'] ) || (int) $search['pg'] < 1 || (int) $search['pg'] > 100 ? 1 : (int) $search['pg'];
		$filters     = ! empty( $search['filters'] ) && is_array( $search['filters'] ) ? $search['filters'] : [];
		$per_page    = 10;

		$data = API::get_instance()->api_call( 'v1/collections', $api_data );

		if ( $data && ! is_wp_error( $data ) && ! empty( $data['data'] ) ) {
			// filter api response data?
			$filtered_data = [];
			// First we extract any filters.
			$collections_to_filter = $data['data'];
			// Clean up filters
			foreach ( $filters as $filter_key => $filter_value ) {
				if ( empty( $filter_value ) ) {
					unset( $filters[ $filter_key ] );
				}
			}
			if ( ! empty( $filters ) ) {
				foreach ( $collections_to_filter as $collection_id => $collection ) {
					$has_filter_match = false;
					foreach ( $filters as $filter_key => $filter_value ) {
						if ( ! empty( $collection['filter'] ) && ! empty( $collection['filter'][ $filter_key ] ) && isset( $collection['filter'][ $filter_key ][ $filter_value ] ) ) {
							$has_filter_match = true;
						}
					}
					if ( ! $has_filter_match ) {
						unset( $collections_to_filter[ $collection_id ] );
					}
				}
			}
			$show_coming_soon = count( $collections_to_filter ) < $per_page || count( $collections_to_filter ) <= $page_number * $per_page;
			$paged_data       = array_slice( $collections_to_filter, ( $page_number - 1 ) * $per_page, $per_page );
			if ( $paged_data ) {
				foreach ( $paged_data as $collection ) {
					if ( ! empty( $collection['templates'] ) ) {
						$filtered_collection = $this->filter_collection( $collection );
						if ( $filtered_collection ) {
							$filtered_data[] = $filtered_collection;
						}
					}
				}
			}
			$data['data'] = [
				'page_number'      => $page_number,
				'per_page'         => $per_page,
				'all_results'      => count( $data['data'] ),
				'total_results'    => count( $collections_to_filter ),
				'collections'      => $filtered_data,
				'show_coming_soon' => $show_coming_soon,
			];

		}

		return $data;

	}

	public function schedule_remote_template_install( $collection_id, $template_id, $import_type = false ) {

		$local_template_id = false;
		$api_data          = [
			'collection_id' => $collection_id,
			'template_id'   => $template_id,
			'import_type'   => $import_type,
		];
		$template_data     = API::get_instance()->api_call( 'v1/import', $api_data );

		if ( is_wp_error( $template_data ) ) {
			return $template_data;
		} elseif ( $template_data && ! empty( $template_data['data'] ) && ! empty( $template_data['data']['templates'] ) ) {

			$this->check_memory_limit();

			// First step is creating a CPT_Kit entry to group our imported templates by this category etc..
			$local_collection    = new CPT_Kits();
			$local_collection_id = $local_collection->seed_local_cache( $template_data );
			if ( $local_collection_id ) {
				foreach ( $template_data['data']['templates'] as $template ) {
					if ( $template && $template['template_id'] === $template_id && ! empty( $template['import'] ) ) {
						// We store a cached copy of this template and set it to 'draft' post type.
						// This is our flag to say this template is queued to be imported.
						$local_template_id = $local_collection->schedule_local_install( $template, $import_type );
					}
				}
			}
		}

		return $local_template_id;
	}


	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param\ WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function rest_permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * This registers all our WP REST API endpoints for the react front end
	 *
	 * @param $namespace
	 */

	public function init_rest_endpoints( $namespace ) {

		$endpoints = [
			'/collections/' . $this->category . '/'   => [
				\WP_REST_Server::CREATABLE => 'rest_list',
			],
			'/collection/' . $this->category . '/'    => [
				\WP_REST_Server::READABLE => 'rest_single',
			],
			// Importing a template to library.
			'/import/' . $this->category . '/process' => [
				\WP_REST_Server::CREATABLE => 'rest_process_import',
			],
			// Inserting content onto a page.
			'/insert/' . $this->category . '/process' => [
				\WP_REST_Server::CREATABLE => 'rest_process_insert',
			],
		];

		foreach ( $endpoints as $endpoint => $details ) {
			foreach ( $details as $method => $callback ) {
				register_rest_route(
					$namespace, $endpoint, [
						[
							'methods'             => $method,
							'callback'            => [ $this, $callback ],
							'permission_callback' => [ $this, 'rest_permission_check' ],
							'args'                => [],
						],
					]
				);
			}
		}

	}

	/**
	 * Create a page from a template via the REST API
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function rest_process_insert( $request ) {

		$collection_id = $request->get_param( 'collectionId' );
		$template_id   = $request->get_param( 'templateId' );
		$insert_type   = $request->get_param( 'insertType' ); // create-page
		if ( ! $insert_type ) {
			$insert_type = 'create-page';
		}

		$options = [
			'page_name' => $request->get_param( 'pageName' ),
			'page_id'   => $request->get_param( 'pageId' ),
		];
		$result  = [];

		if ( $collection_id && $template_id ) {

			// We add this insert request to the meta data of the imported template in our CPT
			// When the template is finally imported (or if it's already imported) we fire off the actual insert request.
			// This lets us schedule an insert while the template is still importing in the background.
			$cpt_kits = new CPT_Kits();
			$this->schedule_remote_template_install( $collection_id, $template_id, $insert_type );
			$install_result    = $this->install_remote_template( $collection_id, $template_id );
			$local_template_id = $cpt_kits->schedule_insert_template( $collection_id, $template_id, $insert_type, $options );
			if ( $local_template_id ) {
				$created_page_id = $this->process_scheduled_page_inserts( $local_template_id );
			}

			$result['install_debug']   = $install_result;
			$result['source_template'] = $local_template_id;
			$result['page_id']         = $created_page_id;
			$result['page_url']        = Linker::get_instance()->get_edit_link( $created_page_id );
			$result['page_name']       = $created_page_id ? get_the_title( $created_page_id ) : '';

			return new \WP_REST_Response( $result, 200 );
		}

		return new \WP_REST_Response(
			[
				'error' => 'Unknown error',
			], 500
		);

	}

	/**
	 * Import a template via the REST api
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function rest_process_import( $request ) {

		$collection_id = $request->get_param( 'collectionId' );
		$template_id   = $request->get_param( 'templateId' );
		$import_type   = $request->get_param( 'importType' ); // = library
		if ( ! $import_type ) {
			$import_type = 'library';
		}

		$result = [
			'status'       => false,
			'category'     => $this->category,
			'collectionId' => $collection_id,
			'templateId'   => $template_id,
			'url'          => add_query_arg(
				[
					'category'      => $this->category,
					'collection_id' => $collection_id,
					'template_id'   => $template_id,
				], Collection::get_instance()->get_url()
			),
		];

		if ( $collection_id && $template_id ) {

			$this->schedule_remote_template_install( $collection_id, $template_id, $import_type );
			$install_result = $this->install_remote_template( $collection_id, $template_id );
			if ( $install_result && $install_result['post_id'] ) {
				$result['install_debug'] = $install_result;
				$result['status']        = true;
				$result['post_id']       = $install_result['post_id'];
				$result['post_url']      = Linker::get_instance()->get_edit_link( $install_result['post_id'] );
			}
		}

		return new \WP_REST_Response( $result, 200 );

	}

	/**
	 * Get a list of all templates via the rest api
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function rest_list( $request ) {

		$this->check_memory_limit();
		$search = $request->get_param( 'elementsSearch' );

		$all_collections = $this->get_remote_collections( $search );
		// todo: show the users chosen collection at the top (or sort by most recently used collections)
		if ( $all_collections && ! is_wp_error( $all_collections ) && ! empty( $all_collections['data'] ) ) {
			return new \WP_REST_Response( $all_collections, 200 );
		} else {
			$message = 'Unknown API error with REST LIST. If this continues to happen please <a href="mailto:extensions@envato.com">report the bug to us</a>.';
			if ( is_wp_error( $all_collections ) ) {
				$data = $all_collections->get_error_message();
				if ( ! empty( $data['message'] ) ) {
					$message = $data['message'];
				}
			}

			return new \WP_REST_Response(
				[
					'error' => $message,
				], 500
			);
		}

	}

	/**
	 * Get a list of all templates via the rest api.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function rest_single( $request ) {

		$collection_id = $request->get_param( 'collection_id' );

		$collection_data = $this->get_remote_collection( $collection_id );
		// todo: show the users chosen collection at the top (or sort by most recently used collections)
		if ( $collection_data && ! is_wp_error( $collection_data ) && ! empty( $collection_data['data'] ) ) {
			return new \WP_REST_Response( $collection_data, 200 );
		} else {
			$message = 'Unknown API error with REST SINGLE. If this continues to happen please <a href="mailto:extensions@envato.com">report the bug to us</a>.';
			if ( is_wp_error( $collection_data ) ) {
				$data = $collection_data->get_error_message();
				if ( ! empty( $data['message'] ) ) {
					$message = $data['message'];
				}
			}

			return new \WP_REST_Response(
				[
					'error' => $message,
				], 500
			);
		}

	}


	public function filter_template( $api_data, $category_data ) {
		$thumbx2         = $thumb = ! empty( $api_data['preview_thumbs']['w200']['url'] ) ? $api_data['preview_thumbs']['w200']['url'] : $api_data['preview_thumb'];
		$aspect          = '100%';
		$animationHeight = 0;
		if ( ! empty( $api_data['preview_thumbs']['w200']['width'] ) ) {
			$animationHeight = $api_data['preview_thumbs']['w200']['height'];
			$aspect          = ( ( intval( $api_data['preview_thumbs']['w200']['height'] ) / intval( $api_data['preview_thumbs']['w200']['width'] ) ) * 100 ) . '%';
		}
		if ( ! empty( $api_data['preview_thumbs']['w500']['width'] ) ) {
			$thumbx2         = ! empty( $api_data['preview_thumbs']['w500']['url'] ) ? $api_data['preview_thumbs']['w500']['url'] : $thumb;
			$animationHeight = $api_data['preview_thumbs']['w500']['height'];
			$aspect          = ( ( intval( $api_data['preview_thumbs']['w500']['height'] ) / intval( $api_data['preview_thumbs']['w500']['width'] ) ) * 100 ) . '%';
		}

		$large_thumb = [
			'src'    => $api_data['preview_image'],
			'width'  => 'auto',
			'height' => 'auto',
		];
		if ( ! empty( $api_data['preview_thumbs']['w1360'] ) ) {
			$large_thumb = [
				'src'    => $api_data['preview_thumbs']['w1360']['url'],
				'width'  => $api_data['preview_thumbs']['w1360']['width'],
				'height' => $api_data['preview_thumbs']['w1360']['height'],
			];
		}

		$filtered_data = [
			'templateId'             => $api_data['template_id'],
			'previewThumb'           => $thumb,
			'previewThumb2x'         => $thumbx2,
			'previewUrl'             => ! empty( $api_data['preview_url'] ) ? $api_data['preview_url'] : $thumb,
			'previewThumbAspect'     => $aspect,
			'previewThumbHeight'     => $animationHeight,
			'templateName'           => $api_data['name'],
			'templateUrl'            => add_query_arg(
				[
					'category'      => $this->category,
					'collection_id' => $category_data['collectionId'],
					'template_id'   => $api_data['template_id'],
				], Collection::get_instance()->get_url()
			),
			// detail view data:
			'templateInstalled'      => false, // todo
			// if already installed.
			'templateInstalledURL'   => '#',
			// url to edit template,
			'templateInstalledText'  => 'Edit Template',
			'templateImportText'     => 'Import Template', // Changes to 'Import Pro Template'
			// customize based on template type.
			'largeThumb'             => $large_thumb,
			// which pages the template is inserted (or pending insert) on
			'templateInserted'       => [],
			'templateError'          => false,
			'templateMissingPlugins' => [],
			'templateFeatures'       => ! empty( $api_data['template_features'] ) ? $api_data['template_features'] : [],
			'templateType'           => ! empty( $api_data['type'] ) ? $api_data['type'] : [],
		];

		if ( isset( $filtered_data['templateFeatures']['elementor-pro'] ) ) {
			$filtered_data['templateImportText']    = 'Import Pro Template';
			$filtered_data['templateInstalledText'] = 'Edit Pro Template';
		}

		$missing_plugins = Required_Plugin::get_instance()->get_missing_plugins( ! empty( $api_data['plugins'] ) ? $api_data['plugins'] : [], $this->category );
		if ( $missing_plugins ) {
			$filtered_data['templateError']          = true;
			$filtered_data['templateMissingPlugins'] = $missing_plugins;
		}

		return $filtered_data;
	}

	public function filter_collection( $api_data ) {

		$filtered_data = [
			'collectionId'        => $api_data['collection_id'],
			'categorySlug'        => $this->category,
			'collectionName'      => $api_data['name'],
			'collectionThumbnail' => $api_data['preview_thumb'],
			'collectionUrl'       => add_query_arg(
				[
					'category'      => $this->category,
					'collection_id' => $api_data['collection_id'],
				], Collection::get_instance()->get_url()
			),
			'templates'           => [],
			'features'            => [],
			'filter'              => [],
		];

		if ( ! empty( $api_data['options'] ) && ! empty( $api_data['options']['features'] ) && is_array( $api_data['options']['features'] ) ) {
			$filtered_data['features'] = $api_data['options']['features'];
		}
		if ( ! empty( $api_data['filter'] ) && is_array( $api_data['filter'] ) ) {
			$filtered_data['filter'] = $api_data['filter'];
		}

		if ( ! empty( $api_data['templates'] ) && is_array( $api_data['templates'] ) ) {
			$filtered_templates = [];
			foreach ( $api_data['templates'] as $template ) {
				$filtered_templates[] = $this->filter_template( $template, $filtered_data );
			}
			if ( $filtered_templates ) {
				$filtered_data['templates'] = $filtered_templates;
				$filtered_data              = $this->filter_installed_status( $filtered_data );
			}
		}

		return $filtered_data;
	}

	public function trashed_post( $post_id = false ) {

	}

	// Called when the rest API is returning details about this template.
	public function filter_installed_status( $collection ) {

		$imported_templates = CPT_Kits::get_instance()->get_imported_templates();
		if ( ! empty( $collection['templates'] ) ) {
			foreach ( $collection['templates'] as $id => $template ) {
				foreach ( $imported_templates as $imported_template ) {
					if ( $imported_template['categorySlug'] === $this->category && $imported_template['templateId'] === $template['templateId'] ) {

						if ( ! empty( $imported_template['imported'] ) ) {
							$collection['templates'][ $id ]['templateInstalled']    = true;
							$collection['templates'][ $id ]['templateInstalledID']  = $imported_template['ID'];
							$collection['templates'][ $id ]['templateInstalledURL'] = $this->edit_post_link( $imported_template['ID'] );
							$collection['templates'][ $id ]['templateInstalleText'] = Category::get_instance()->get_current( $this->category )->edit_button;
						}

						// We also return the "Inserted Template" details so our template can choose to display this information.
						if ( ! empty( $imported_template['inserted'] ) ) {
							$collection['templates'][ $id ]['templateInserted'] = $imported_template['inserted'];
						}
					}
				}
			}
		}

		return $collection;
	}

	public function edit_post_link( $post_id ) {
		return get_permalink( $post_id );
	}

	public function process_scheduled_page_inserts( $local_template_id ) {

		$created_page_id = 0;

		$cpt_kits       = new CPT_Kits();
		$local_template = get_post( $local_template_id );
		if ( $local_template && $local_template->ID && $local_template->post_type === $cpt_kits->cpt_slug && 'publish' === $local_template->post_status ) {

			$all_post = get_post( $local_template->ID );
			$all_meta = get_post_meta( $local_template->ID );
			unset( $all_meta['template_data'] );

			// Find out what destinations we have to inject this data into.
			$insert_history = get_post_meta( $local_template_id, 'insert_history', true );
			if ( ! is_array( $insert_history ) || ! $insert_history ) {
				$insert_history = [];
			}
			foreach ( $insert_history as $key => $val ) {
				if ( ! $val['completed'] ) {

					if ( ! empty( $val['destination_post_id'] ) ) {

						if ( 'create-page' === $val['insert_type'] ) {
							foreach ( $all_meta as $meta_key => $meta_val ) {
								if ( $meta_val && ! empty( $meta_val[0] ) ) {
									update_post_meta( $val['destination_post_id'], $meta_key, maybe_unserialize( $meta_val[0] ) );
								}
							}
							wp_update_post(
								[
									'ID'           => $val['destination_post_id'],
									'post_content' => $all_post->post_content,
								]
							);
						}
						$created_page_id                     = $val['destination_post_id'];
						$insert_history[ $key ]['completed'] = true;
					}
				}
			}
			update_post_meta( $local_template_id, 'insert_history', $insert_history );
		}

		return $created_page_id;
	}

}
