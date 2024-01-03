<?php

// Hook the custom REST route registration function to the REST API initialization
add_action('rest_api_init', 'universityRegisterSearch');

// Function to register a custom REST route for search
function universityRegisterSearch()
{
    register_rest_route('university/v1', 'search', array(
        'methods' => WP_REST_SERVER::READABLE,
        'callback' => 'universitySearchResults'
    ));
}

// Callback function for handling search results
function universitySearchResults($data)
{
    // Create a new main query to search multiple post types
    $mainQuery = new WP_Query(array(
        'post_type' => array('post', 'page', 'professor', 'program', 'campus', 'event'),
        's' => sanitize_text_field($data['term'])
    ));

    // Initialize an array to store search results categorized by post type
    $results = array(
        'generalInfo' => array(),
        'professors' => array(),
        'programs' => array(),
        'events' => array(),
        'campuses' => array()
    );

    // Loop through the main query results
    while ($mainQuery->have_posts()) {
        $mainQuery->the_post();

        // Categorize results based on post type
        if (get_post_type() == 'post' || get_post_type() == 'page') {
            array_push($results['generalInfo'], array(
                'title' => get_the_title(),
                'permalink' => get_the_permalink(),
                'postType' => get_post_type(),
                'authorName' => get_the_author()
            ));
        }

        if (get_post_type() == 'professor') {
            array_push($results['professors'], array(
                'title' => get_the_title(),
                'permalink' => get_the_permalink(),
                'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
            ));
        }

        if (get_post_type() == 'program') {
            // Get related campuses for programs
            $relatedCampuses = get_field('related_campus');

            // Loop through related campuses and add to results
            if ($relatedCampuses) {
                foreach ($relatedCampuses as $campus) {
                    array_push($results['campuses'], array(
                        'title' => get_the_title($campus),
                        'permalink' => get_the_permalink($campus)
                    ));
                }
            }

            array_push($results['programs'], array(
                'title' => get_the_title(),
                'permalink' => get_the_permalink(),
                'id' => get_the_id()
            ));
        }

        if (get_post_type() == 'campus') {
            array_push($results['campuses'], array(
                'title' => get_the_title(),
                'permalink' => get_the_permalink()
            ));
        }

        if (get_post_type() == 'event') {
            // Format event date using DateTime
            $eventDate = new DateTime(get_field('event_date'));

            // Set event description
            $description = null;

            // Use excerpt or content as description
            if (has_excerpt()) {
                $description = get_the_excerpt();
            } else {
                $description = wp_trim_words(get_the_content(), 18);
            }

            array_push($results['events'], array(
                'title' => get_the_title(),
                'permalink' => get_the_permalink(),
                'month' => $eventDate->format('M'),
                'day' => $eventDate->format('d'),
                'description' => $description
            ));
        }
    }

    // Additional processing for programs with related professors and events
    if ($results['programs']) {
        // Create a meta query for related programs
        $programsMetaQuery = array('relation' == 'OR');

        // Loop through programs and add related programs to meta query
        foreach ($results['programs'] as $item) {
            array_push($programsMetaQuery, array(
                'key' => 'related_programs',
                'compare' => 'LIKE',
                'value' => '"' . $item['id'] . '"'
            ));
        }

        // Create a new query for related professors and events
        $programRelationshipQuery = new WP_Query(array(
            'post_type' => array('professor', 'event'),
            'meta_query' => $programsMetaQuery
        ));

        // Loop through related professor and event results
        while ($programRelationshipQuery->have_posts()) {
            $programRelationshipQuery->the_post();

            // Process events
            if (get_post_type() == 'event') {
                // Format event date using DateTime
                $eventDate = new DateTime(get_field('event_date'));

                // Set event description
                $description = null;

                // Use excerpt or content as description
                if (has_excerpt()) {
                    $description = get_the_excerpt();
                } else {
                    $description = wp_trim_words(get_the_content(), 18);
                }

                array_push($results['events'], array(
                    'title' => get_the_title(),
                    'permalink' => get_the_permalink(),
                    'month' => $eventDate->format('M'),
                    'day' => $eventDate->format('d'),
                    'description' =>   $description
                ));
            }

            // Process professors
            if (get_post_type() == 'professor') {
                array_push($results['professors'], array(
                    'title' => get_the_title(),
                    'permalink' => get_the_permalink(),
                    'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
                ));
            }
        }

        // Remove duplicate entries from professors and events
        $results['professors'] = array_values(array_unique($results['professors'], SORT_REGULAR));
        $results['events'] = array_values(array_unique($results['events'], SORT_REGULAR));
    }

    // Return the final search results
    return $results;
}
