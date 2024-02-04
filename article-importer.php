<?php
/*
Plugin Name: Article Importer
Description: Custom CSV Importer for Articles
Version: 1.0
Author: Sean Aizenshtein
*/

add_action('admin_menu', 'csv_importer_menu');

function csv_importer_menu() {
    add_menu_page(
        'Article CSV Importer',
        'Article CSV Importer',
        'manage_options',
        'csv_importer_page',
        'csv_importer_page',
        'dashicons-media-spreadsheet', // Choose an appropriate icon
        30 // Adjust the position in the admin sidebar
    );
}

function csv_importer_page() {
    if (isset($_POST['import_csv'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
            // Process the uploaded CSV file
            $csv_file_path = $_FILES['csv_file']['tmp_name'];
    
            // Read the CSV file
            $csv_data = array_map('str_getcsv', file($csv_file_path));
            $csv_headers = array_shift($csv_data);
    
            // Find the index of Title and Text columns
            $title_index = array_search('Title', $csv_headers);
            $text_index = array_search('Text', $csv_headers);
            $dictionary_index = array_search('Dictionary', $csv_headers);
            $definition_index = array_search('Definition', $csv_headers);
            $names_index = array_search('Names', $csv_headers);

            if ($title_index !== false && $text_index !== false) {
                // Extract title and text from the first row
                $title = $csv_data[0][$title_index];
                $text = $csv_data[0][$text_index];
    
                // Create a new WordPress post as draft
                $post_id = wp_insert_post(array(
                    'post_title'   => $title,
                    'post_content' => $text,
                    'post_status'  => 'draft', // Set as draft
                ));
    
                // Dictionary, definition and names logic
                $dictionary_rows = array_column($csv_data, $dictionary_index);
                $definition_rows = array_column($csv_data, $definition_index);
                $names_rows = array_column($csv_data, $names_index);

                foreach ($dictionary_rows as $index => $dictionary_row) {
                    $dictionary_words = explode(',', $dictionary_row);
    
                    foreach ($dictionary_words as $word) {
                        $word = trim($word);
    
                        // Check if the word is in the text
                        if (stripos($text, $word) !== false) {
                            // Get the corresponding definition
                            $definition = $definition_rows[$index];
    
                            // Replace occurrences in the text
                            $text = str_replace($word, "<b data-tooltip='${definition}'>$word</b>", $text);
                        }
                    }
                }

                foreach ($names_rows as $index => $name_row) {
                    $names_words = explode(',', $name_row);

                    foreach ($names_words as $word) {
                        $word = trim($word);

                        if (stripos($text, $word) !== false) {
                            $text = str_replace($word, "<em>$word</em>", $text);
                        }
                    }
                }
                // Update the post content with processed text
                wp_update_post(array('ID' => $post_id, 'post_content' => $text));


                if (!is_wp_error($post_id)) {
                    echo "Post '{$title}' created successfully as draft.";
                } else {
                    echo "Error creating post: " . $post_id->get_error_message();
                }
            } else {
                echo "Title or Text columns not found in the CSV file.";
            }
        } else {
            echo "Error uploading CSV file.";
        }
    }
    ?>
    <div class="wrap">
        <h2>CSV Importer</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <input type="file" class="button button-primary" name="import_csv" value="Import CSV" accept=".csv">
        </form>
    </div>
    <?php
}