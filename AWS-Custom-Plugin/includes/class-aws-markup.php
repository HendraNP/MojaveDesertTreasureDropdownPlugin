<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AWS_Markup' ) ) :

    /**
     * Class for plugin search action
     */
    class AWS_Markup {

        /*
         * Generate search box markup
         */
        public function markup() {

            global $wpdb;

            $table_name = $wpdb->prefix . AWS_INDEX_TABLE_NAME;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
                echo 'Please go to <a href="' . admin_url( 'admin.php?page=aws-options' ) . '">plugins settings page</a> and click on "Reindex table" button.';
                return;
            }


            $placeholder   = AWS_Helpers::translate( 'search_field_text', AWS()->get_settings( 'search_field_text' ) );
            $min_chars     = AWS()->get_settings( 'min_chars' );
            $show_loader   = AWS()->get_settings( 'show_loader' );
            $show_more     = AWS()->get_settings( 'show_more' );
            $show_page     = AWS()->get_settings( 'show_page' );
            $show_clear    = AWS()->get_settings( 'show_clear' );
            $use_analytics = AWS()->get_settings( 'use_analytics' );
            $buttons_order = AWS()->get_settings( 'buttons_order' );

            $current_lang = AWS_Helpers::get_lang();

            $url_array = parse_url( home_url() );
            $url_query_parts = array();

            if ( isset( $url_array['query'] ) && $url_array['query'] ) {
                parse_str( $url_array['query'], $url_query_parts );
            }


            $params_string = '';

            $params = array(
                'data-url'           => admin_url('admin-ajax.php'),
                'data-siteurl'       => home_url(),
                'data-lang'          => $current_lang ? $current_lang : '',
                'data-show-loader'   => $show_loader,
                'data-show-more'     => $show_more,
                'data-show-page'     => $show_page,
                'data-show-clear'    => $show_clear,
                'data-use-analytics' => $use_analytics,
                'data-min-chars'     => $min_chars,
                'data-buttons-order' => $buttons_order,
                'data-is-mobile'     => wp_is_mobile() ? 'true' : 'false',
            );


            /**
             * Filter form data parameters before output
             * @since 1.69
             * @param array $params Data parameters array
             */
            $params = apply_filters( 'aws_front_data_parameters', $params );


            foreach( $params as $key => $value ) {
                $params_string .= $key . '="' . esc_attr( $value ) . '" ';
            }

            $markup = '';
            $markup .= '<div class="aws-container" ' . $params_string . '>';
            $markup .= '<form class="aws-search-form" action="' . home_url('/') . '" method="get" role="search" >';

            $markup .= '<div class="aws-wrapper">';

                $markup .= '<div id="divname" class="divname" name="divname">';
                $markup .= '<input  type="name" name="name" value="' . get_search_query() . '" class="aws-search-field" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off" />';
                $markup .= '</div>';
                $markup .= '<div> - </div>';
                /*$categories_array = array();
                $categories = get_categories();
                foreach( $categories as $category ){
                  $categories_array[] = $category->term_id;
                }*/
                


                
                $yearcat = array(   
                  'description'   => 'Select Year',
                  'holder'        => 'div',
                  'class'         => 'year',
                  'name'          => 'year',
                  'id'            => 'year',
                  'show_count'    => 1,
                  'taxonomy'      => 'product_cat',
                  'exclude'       => 15,
                  'show_option_none'   => 'Select Year',
                  'option_none_value'  => '-1',
                  'hide_empty'    => 0,
                  'child_of'      => 0,
                  'parent'        => 0,
                  'depth'         => 1,
                  'hierarchical'  => 1,
                );
                //wp_dropdown_categories($yearcat);
                $categories = smyles_get_taxonomy_hierarchy( 'product_cat' );
                //  $parent_category = 52; // used for testing
                //  $child_category = 82; // used for testing
                $parent_has_children = ! empty( $parent_category ) && $categories[ $parent_category ] && ! empty( $categories[ $parent_category ]->children );


                // Creative way to use wp_localize_script which creates a JS variable from array
                // You should actually change this to load your JavaScript file and move JS below to that file
                wp_register_script( 'slcustom_user_profile_fields', '' );
                wp_localize_script( 'slcustom_user_profile_fields', 'slcustom_categories', $categories );
                wp_enqueue_script( 'slcustom_user_profile_fields' );
                $parentCat = $wpdb->get_results( "SELECT term_id, name FROM wp_terms WHERE term_id IN (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat' AND parent = 0 AND term_id != 15)" );
                $markup .= '<div id="divyear" class="divyear" name="divyear">';
                $markup .= '<select>';
                $markup .= '<option value="-1">Select Year</option>';
                foreach ( $parentCat as $cat ){
                    $cat_counts = wp_list_pluck( get_categories( array('taxonomy' => 'product_cat', 'hide_empty' => 0, 'parent' => 0)), 'count', 'name' );
                    foreach ( $cat_counts as $name => $count ) {
                        if($name == $cat->name){
                            $markup .= "<option value='$cat->term_id'>$cat->name</option>";
                        }
                    }
                }
                $markup .= '</select>';
                $markup .= '</div>';
                $markup .= "<div id='divbrand' class='divbrand' name='divbrand'  style='display:none'> ";
                $markup .= '<select>';
                /*if( $parent_has_children ){
                    foreach( (array) $categories[$parent_category]->children as $c_term_id => $child ){
                        $markup .= "<option value='esc_attr( $c_term_id )'>selected( $child_category, $c_term_id )>$child->name</option>";
                    }
                }*/
                $markup .= '</select>';
                $markup .= '</div>';
                $markup .= '<div id="divmodel" class="divmodel" name="divmodel" style="display:none">';
                $markup .= '<select>';
                /*if( $parent_has_children ){
                    foreach( (array) $categories[$parent_category]->children as $c_term_id => $child ){
                        $markup .= "<option value='esc_attr( $c_term_id )'>selected( $child_category, $c_term_id )>$child->name</option>";
                    }
                }*/
                $markup .= '</select>';
                $markup .= '</div>';
                $markup .= '<div id="divproduct" class="divproduct" name="divproduct" style="display:none">';
                $markup .= '<select>';
                /*if( $parent_has_children ){
                    foreach( (array) $categories[$parent_category]->children as $c_term_id => $child ){
                        $markup .= "<option value='esc_attr( $c_term_id )'>selected( $child_category, $c_term_id )>$child->name</option>";
                    }
                }*/
                $markup .= '</select>';
                $markup .= '</div>';
                $markup .= '<input type="button" value="Search" name="submitdropdown" id="submitdropdown" class="submitdropdown" style="display:none">Submit</button>';
                $markup .= '<input type="hidden" name="post_type" value="product">';
                $markup .= '<input type="hidden" name="type_aws" value="true">';

                if ( $current_lang ) {
                    $markup .= '<input type="hidden" name="lang" value="' . esc_attr( $current_lang ) . '">';
                }

                if ( $url_query_parts ) {
                    foreach( $url_query_parts as $url_query_key => $url_query_value  ) {
                        $markup .= '<input type="hidden" name="' . esc_attr( $url_query_key ) . '" value="' . esc_attr( $url_query_value ) . '">';
                    }
                }

                $markup .= '<div class="aws-search-clear">';
                    $markup .= '<span aria-label="Clear Search">×</span>';
                $markup .= '</div>';

                $markup .= '<div class="aws-loader"></div>';

            $markup .= '</div>';

            if ( $buttons_order && $buttons_order !== '1' ) {

                $markup .= '<div class="aws-search-btn aws-form-btn">';
                $markup .= '<span class="aws-search-btn_icon">';
                $markup .= '<svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px">';
                            $markup .= '<path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>';
                        $markup .= '</svg>';
                    $markup .= '</span>';
                $markup .= '</div>';

            }

            $markup .= '</form>';
            $markup .= '</div>';

            return apply_filters( 'aws_searchbox_markup', $markup );

        
}
    }

endif;