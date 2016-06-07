<?php

/*
 * ACF Post Title Field Class Searches just the post_title for a substring.
 * @class acf_field_post_title @extends acf_field
 */
if (!class_exists('acf_field_post_title')) :



    class acf_field_post_title extends acf_field
    {


        /*
         * __construct This function will setup the field type data @type
         * function @date 5/23/2016 @since 0.0.1 @param n/a @return n/a
         */
        function __construct()
        {

            // vars
            $this->version = "0.0.2";
            $this->dir = plugin_dir_url(__FILE__);
            $this->path = plugin_dir_path(__FILE__);
            $this->name = 'post_title';
            $this->label = __("Post Title Search", 'acf');
            $this->category = 'relational';
            $this->defaults = array(
                'post_type' => array(),
                'taxonomy' => array(),
                'allow_null' => 0,
                'multiple' => 0,
                'return_format' => 'object',
                'ui' => 1
            );

            // extra
            add_action('wp_ajax_acf/fields/post_title/query', array(
                $this,
                'ajax_query'
            ));
            add_action('wp_ajax_nopriv_acf/fields/post_title/query', array(
                $this,
                'ajax_query'
            ));
            add_action('wp_enqueue_scripts', array(
                $this,
                'input_admin_enqueue_scripts'
            ));

            // filter to search posts by post_title
            add_filter('posts_clauses', array($this, 'title_like_posts_clauses'), 11, 2);
            #add_filter('posts_request', array($this, 'title_like_posts_request'), 11, 2 );

            // do not delete!
            parent::__construct();
        }


        function input_admin_enqueue_scripts()
        {
            wp_enqueue_script('acf-post-title-search-scripts', $this->dir . 'js/input.js', array(
                'acf-input'
            ), $this->version, true);
        }


        /**
         * if 'post_title_like' is set in the wp_query args, then perform a
         * like search for that value in the post_title
         */
        function title_like_posts_clauses($clauses, &$wp_query)
        {
            global $wpdb;
            if ($post_title_like = $wp_query->get('post_title_like')) {
                $clauses["fields"] .= ', MATCH(' . $wpdb->posts . '.post_title) AGAINST(\'' . esc_sql($wpdb->esc_like($post_title_like)) . '\') AS score';
                $clauses["orderby"] = "MATCH(" . $wpdb->posts . ".post_title) AGAINST('" . esc_sql($wpdb->esc_like($post_title_like)) . "') DESC";
                $clauses["where"] = " AND sz_posts.post_type IN ('post', 'ext', 'cartoon', 'press', 'reports', 'event') AND sz_posts.post_status = 'publish' AND MATCH(sz_posts.post_title) AGAINST('" . esc_sql($wpdb->esc_like($post_title_like)) . "') > 1";
            }

            return $clauses;
        }


        /**
         * if 'post_title_like' is set in the wp_query args, then perform a
         * like search for that value in the post_title
         */
        function title_like_posts_request($request, &$wp_query)
        {
            global $wpdb;
            print "request: $request<br>\n";

            return $request;
        }


        /*
         * get_choices This function will return an array of data formatted
         * for use in a select2 AJAX response @type function @date 5/23/2016
         * @since 0.0.1 @param $options (array) @return (array)
         */
        function get_choices($options = array())
        {
            // defaults
            $options = acf_parse_args($options, array(
                'post_id' => 0,
                's' => '',
                'field_key' => '',
                'paged' => 1
            ));

            // load field
            $field = acf_get_field($options['field_key']);

            // bail early if no field
            if (!$field) {return false;}

            // vars
            $r = array();
            $args = array();

            // paged
            $args['posts_per_page'] = 20;
            $args['paged'] = $options['paged'];

            // update $args
            if (!empty($field['post_type'])) {
                $args['post_type'] = acf_get_array($field['post_type']);
            } else {
                $args['post_type'] = acf_get_post_types();
            }

            // search
            if ($options['s']) {
                $args['post_status'] = 'publish';

                // enable searching in the post_title
                // rename search parameter to 'post_title_like'
                $args['post_title_like'] = $options['s'];
                unset($args['s']);

                $args['recent_posts'] = true;
            }

            // filters
            $args = apply_filters('acf/fields/post_title/query', $args, $field, $options['post_id']);
            $args = apply_filters('acf/fields/post_title/query/name=' . $field['name'], $args, $field, $options['post_id']);
            $args = apply_filters('acf/fields/post_title/query/key=' . $field['key'], $args, $field, $options['post_id']);

            // get posts grouped by post type
            $groups = acf_get_grouped_posts($args);

            if (!empty($groups)) {

                foreach (array_keys($groups) as $group_title) {

                    // vars
                    $posts = acf_extract_var($groups, $group_title);
                    $titles = array();

                    // data
                    $data = array(
                        'text' => $group_title,
                        'children' => array()
                    );

                    foreach (array_keys($posts) as $post_id) {

                        // override data
                        $posts[$post_id] = $this->get_post_title($posts[$post_id], $field, $options['post_id']);
                    }
                    ;

                    // order by search
                    if (!empty($args['s'])) {

                        $posts = acf_order_by_search($posts, $args['s']);
                    }

                    // append to $data
                    foreach (array_keys($posts) as $post_id) {

                        $data['children'][] = array(
                            'id' => $post_id,
                            'text' => $posts[$post_id]
                        );
                    }

                    // append to $r
                    $r[] = $data;
                }

                // optgroup or single
                if (count($args['post_type']) == 1) {

                    $r = $r[0]['children'];
                }
            }

            // return
            return $r;
        }


        /*
         * ajax_query description @type function @date 5/23/2016 @since 0.0.1
         * @return List of choices
         */
        function ajax_query()
        {
            // print "in ajax_query<br>\n";
            if (!acf_verify_ajax()) {
                die();
            }

            // get choices
            $choices = $this->get_choices($_POST);

            if (!$choices) {
                die();
            }

            echo json_encode($choices);
            die();
        }


        /*
         * get_post_title This function returns the HTML for a result @type
         * function @date 5/23/2016 @since 0.0.1 @param $post (object) @param
         * $field (array) @param $post_id (int) the post_id to which this
         * value is saved to @return (string)
         */
        function get_post_title($post, $field, $post_id = 0)
        {
            // get post_id
            if (!$post_id)
                $post_id = acf_get_form_data('post_id');

                // vars
            $title = acf_get_post_title($post);

            // filters
            $title = apply_filters('acf/fields/post_title/result', $title, $post, $field, $post_id);
            $title = apply_filters('acf/fields/post_title/result/name=' . $field['_name'], $title, $post, $field, $post_id);
            $title = apply_filters('acf/fields/post_title/result/key=' . $field['key'], $title, $post, $field, $post_id);

            // return
            return $title;
        }


        /*
         * render_field() Create the HTML interface for your field @param
         * $field - an array holding all the field's data @type action @since
         * 0.0.1 @date 5/23/2016
         */
        function render_field($field)
        {
            // Change Field into a select
            $field['type'] = 'select';
            $field['ui'] = 1;
            $field['ajax'] = 1;
            $field['choices'] = array();

            // populate choices if value exists
            if (!empty($field['value'])) {

                // get posts
                $posts = acf_get_posts(array(
                    'post__in' => $field['value'],
                    'post_type' => $field['post_type']
                ));

                // set choices
                if (!empty($posts)) {
                    foreach (array_keys($posts) as $i) {
                        // vars
                        $post = acf_extract_var($posts, $i);

                        // append to choices
                        $field['choices'][$post->ID] = $this->get_post_title($post, $field);
                    }
                }
            }

            acf_render_field($field);
        }


        /*
         * render_field_settings() Create extra options for your field. This
         * is rendered when editing a field. The value of $field['name'] can
         * be used (like bellow) to save extra data to the $field @type action
         * @since 0.0.1 @date 5/23/2016 @param $field - an array holding all
         * the field's data
         */
        function render_field_settings($field)
        {

            // default_value
            acf_render_field_setting($field, array(
                'label' => __('Filter by Post Type', 'acf'),
                'instructions' => '',
                'type' => 'select',
                'name' => 'post_type',
                'choices' => acf_get_pretty_post_types(),
                'multiple' => 1,
                'ui' => 1,
                'allow_null' => 1,
                'placeholder' => __("All post types", 'acf')
            ));

            // default_value
            acf_render_field_setting($field, array(
                'label' => __('Filter by Taxonomy', 'acf'),
                'instructions' => '',
                'type' => 'select',
                'name' => 'taxonomy',
                'choices' => acf_get_taxonomy_terms(),
                'multiple' => 1,
                'ui' => 1,
                'allow_null' => 1,
                'placeholder' => __("All taxonomies", 'acf')
            ));

            // allow_null
            acf_render_field_setting($field, array(
                'label' => __('Allow Null?', 'acf'),
                'instructions' => '',
                'type' => 'radio',
                'name' => 'allow_null',
                'choices' => array(
                    1 => __("Yes", 'acf'),
                    0 => __("No", 'acf')
                ),
                'layout' => 'horizontal'
            ));

            // multiple
            acf_render_field_setting($field, array(
                'label' => __('Select multiple values?', 'acf'),
                'instructions' => '',
                'type' => 'radio',
                'name' => 'multiple',
                'choices' => array(
                    1 => __("Yes", 'acf'),
                    0 => __("No", 'acf')
                ),
                'layout' => 'horizontal'
            ));

            // return_format
            acf_render_field_setting($field, array(
                'label' => __('Return Format', 'acf'),
                'instructions' => '',
                'type' => 'radio',
                'name' => 'return_format',
                'choices' => array(
                    'object' => __("Post Object", 'acf'),
                    'id' => __("Post ID", 'acf')
                ),
                'layout' => 'horizontal'
            ));
        }


        /*
         * load_value() This filter is applied to the $value after it is
         * loaded from the db @type filter @since 0.0.1 @date 5/23/2016 @param
         * $value (mixed) the value found in the database @param $post_id
         * (mixed) the $post_id from which the value was loaded @param $field
         * (array) the field array holding all the field options @return
         * $value
         */
        function load_value($value, $post_id, $field)
        {
            if ($value === 'null') {return false;}

            return $value;
        }


        /*
         * format_value() This filter is appied to the $value after it is
         * loaded from the db and before it is returned to the template @type
         * filter @since 0.0.1 @date 5/23/2016 @param $value (mixed) the value
         * which was loaded from the database @param $post_id (mixed) the
         * $post_id from which the value was loaded @param $field (array) the
         * field array holding all the field options @return $value (mixed)
         * the modified value
         */
        function format_value($value, $post_id, $field)
        {
            if (empty($value)) {return $value;}

            // force value to array
            $value = acf_get_array($value);

            // convert values to int
            $value = array_map('intval', $value);

            // load posts if needed
            if ($field['return_format'] == 'object') {
                // get posts
                $value = acf_get_posts(array(
                    'post__in' => $value,
                    'post_type' => $field['post_type']
                ));
            }

            // convert back from array if neccessary
            if (!$field['multiple']) {
                $value = array_shift($value);
            }

            return $value;
        }


        /*
         * update_value() This filter is appied to the $value before it is
         * updated in the db @type filter @since 0.0.1 @date 5/23/2016 @param
         * $value - the value which will be saved in the database @param
         * $post_id - the $post_id of which the value will be saved @param
         * $field - the field array holding all the field options @return
         * $value - the modified value
         */
        function update_value($value, $post_id, $field)
        {
            // validate
            if (empty($value)) {return $value;}

            // format
            if (is_array($value)) {
                // array
                foreach ($value as $k=>$v) {
                    // object?
                    if (is_object($v) && isset($v->ID)) {
                        $value[$k] = $v->ID;
                    }
                }

                // save value as strings, so we can clearly search for them in
                // SQL LIKE statements
                $value = array_map('strval', $value);
            } elseif (is_object($value) && isset($value->ID)) {
                // object
                $value = $value->ID;
            }

            return $value;
        }
    }

    new acf_field_post_title();


endif;

?>
