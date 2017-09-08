<?php

require_once './wp-load.php';

$s = new Welcart_Export();
$d = new Woocommerce_Export();

echo $s->export_tags_to_string();

//$d->import_category_from_string( $cat_json );


class Welcart_Export {

	public function get_category_taxonomy() {
		return 'category';
	}

	public function get_tag_taxonomy() {
		return 'post_tag';
	}

	public function get_root_term_id() {
		return defined( 'USCES_ITEM_CAT_PARENT_ID' ) ? USCES_ITEM_CAT_PARENT_ID : 0;
	}

	public function export_category_to_string() {
		$output = array();
		$this->_export_category( $this->get_root_term_id(), $output );

		return json_encode( $output, JSON_PRETTY_PRINT );
	}

	public function _export_category( $term_id, &$output = array(), $parent_slug = '' ) {
		$term = get_term( $term_id, $this->get_category_taxonomy() );
		if ( $term && ! is_wp_error( $term ) ) {
			$item     = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_slug' => $parent_slug,
			);
			$output[] = $item;

			$metas = get_term_meta( $term->term_id );
			var_dump( $metas );

			$child_terms = get_terms( array(
				'taxonomy'   => $this->get_category_taxonomy(),
				'hide_empty' => true,
				'parent'     => $term->term_id,
			) );
			if ( $child_terms && ! is_wp_error( $child_terms ) ) {
				foreach ( $child_terms as $child_term ) {
					$this->_export_category( $child_term->term_id, $output, $term->slug );
				}
			}
		}
	}

	public function export_tags_to_string() {
		$tags = get_tags( array(
			'taxonomy' => $this->get_tag_taxonomy(),
			'hide_empty' => false,
		) );

		var_dump( $tags );

	}

}

class Woocommerce_Export {

	public function get_taxonomy() {
		return 'product_cat';
	}

	public function import_category_from_string( $content ) {
		$items = json_decode( $content, true );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				wp_insert_term( $item['name'], $this->get_taxonomy(), array(
					'slug'        => $item['slug'],
					'description' => $item['description'],
					'parent'      => $this->term_slug_to_term_id( $item['parent_slug'] ),
				) );
			}
		}
	}

	public function term_slug_to_term_id( $term_slug ) {
		$id = 0;

		$term = get_term_by( 'slug', $term_slug, $this->get_taxonomy() );
		if ( $term && ! is_wp_error( $term ) ) {
			$id = $term->term_id;
		}

		return $id;
	}

	public function clean_categories() {
		$terms = get_terms( array(
			'taxonomy'   => $this->get_taxonomy(),
			'hide_empty' => false,
		) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, WC_TAXONOMY );
			}
		}
	}

}