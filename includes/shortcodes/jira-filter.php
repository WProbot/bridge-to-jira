<?php

function btj_jira_filter_shortcode($atts = array(), $content) {
  $attributes = shortcode_atts(array(
    'fields' => ''
  ), $atts);
  $actual_fields = array();
  if ( $attributes['fields'] ) {
    $no_whitespaces = preg_replace( '/\s*,\s*/', ',', filter_var($attributes['fields'], FILTER_SANITIZE_STRING));
    $actual_fields = explode( ',', $no_whitespaces );
  }

  $endpoint = get_option('jo_endpoint');
  $user = get_option('jo_user');
  $password = get_option('jo_password');

  $filter_str = $content;

  $wp_request_headers = array(
    'Authorization' => 'Basic ' . base64_encode( $user . ':' . $password )
  );

  $wp_request_url = $endpoint . '/rest/api/latest/search?jql=' . urlencode_deep($filter_str);
  $wp_response = wp_remote_request(
    $wp_request_url,
    array(
      'method'    => 'GET',
      'headers'   => $wp_request_headers
    )
  );

  if ( is_wp_error($wp_response) ) {
    echo($wp_response->get_error_message());
    return $content;
  }

  $issue_table = '';
  $root = json_decode($wp_response['body']);
  if ( is_wp_error($root) ) {
    return $content;
  }

  if ( ! empty($root) ) {
    if ( array_key_exists('errorMessages', $root) ) {
      $output_error = '';
      $error_messages = $root->errorMessages;
      $output_error = $output_error . '<table><tr><th>Error</th></tr>';
      foreach ( $error_messages as $error_message ) {
        $output_error = $output_error . '<tr><td>' . $error_message . '</td></tr>';
      }
      $output_error = $output_error . '</table>';
      return $output_error;
    }


    $issue_table = $issue_table . '<table>';
    $issue_table = $issue_table . '<tr>';
    $issue_table = $issue_table . '<th>key</th>';
    foreach ( $actual_fields as $id ) {
      $issue_table = $issue_table . '<th>' . $id . '</th>';
    }
    $issue_table = $issue_table . '</tr>';
    foreach ( $root->issues as $issue )
    {
      $issue_table = $issue_table . '<tr>';
      $issue_table = $issue_table . '<td><a href="'. $issue->self . '">' . $issue->key . '</a></td>';
      foreach ( $actual_fields as $field_id ) {
        if ( array_key_exists($field_id, $issue->fields) ) {
          $field = $issue->fields->{ $field_id };
          $td = '<td>';
          if ( isset($field) ) {
            if ( is_string($field) ) {
              $td = $td . $field;
            } else {
              $has_name = array_key_exists('name', $field);
              if ( $has_name ) {
                $td = $td . $field->name;
              }
            }
          }
          $td = $td . '</td>';
          $issue_table = $issue_table . $td;
        }
      }
      $issue_table = $issue_table . '</tr>';
    }
    $issue_table = $issue_table . '</table>';
  }
  return $issue_table;
}