<?php
/**
* Plugin Name: Bridge To Jira
* Description: A plugin for inspecting JIRA issues inside WordPress.
**/

if ( !function_exists('add_action') ) {
  die("Hi there! I'm just a plugin not much I can do without WordPress loaded");
}

// Setup

define('BTJ_PLUGIN_URL', __FILE__);

// Includes

include('includes/activate.php');
include('includes/admin/init.php');
include('includes/shortcodes/jira-filter.php');

// Hooks
register_activation_hook(__FILE__, 'btj_activate_plugin');
add_action('admin_init', 'btj_admin_init');

// Shortcodes
add_shortcode('bridge-to-jira-filter', 'btj_jira_filter_shortcode');

function jo_list_of_jira_fields() {
  $endpoint = get_option('jo_endpoint');
  $user = get_option('jo_user');
  $password = get_option('jo_password');

  $wp_request_headers = array(
    'Authorization' => 'Basic ' . base64_encode( $user . ':' . $password )
  );

  $wp_request_url = $endpoint . '/rest/api/latest/field';
  $wp_response = wp_remote_request(
    $wp_request_url,
    array(
        'method'    => 'GET',
        'headers'   => $wp_request_headers
    )
  );
  if ( is_wp_error($wp_response) ) {
    echo($wp_response->get_error_message());
    return array();
  }
  $root = json_decode($wp_response['body']);
  if ( empty($root) )
    return array();

  $ret = array();
  foreach ( $root as $field )
    $ret[$field->id] = $field->name;
  return $ret;
}

function echo_log($what)
{
  echo '<pre>'.print_r( $what, true ).'</pre>';
}

function jo_add_field_box()
{
  $screens = ['post'];
  add_meta_box('jo_field_selection_id', 'JIRA field selection', 'jo_field_selection_html', 'post');
}

add_action('add_meta_boxes', 'jo_add_field_box');

function jo_field_selection_html($post)
{
  $value = get_post_meta($post->ID, '_jo_field_selection_meta_key', true);
  if ( is_string($value) ) {
    $value = array();
  }
  $jira_fields = jo_list_of_jira_fields();
  ?>
  <label for="jo_field_selection_list">Select JIRA fields to show</label>
  <select name="jo_field_selection_list[]" id="jo_field_selection_list" size="20" class="postbox" multiple>
  <?php foreach ( $jira_fields as $id => $name): ?>
    <option value="<?php echo($id); ?>" <?php if (in_array($id, $value)) echo('selected'); ?>><?php echo($name); ?></option>
  <?php endforeach; ?>
  </select>
  <?php
}

function jo_save_metadata($post_id)
{
  if ( array_key_exists('jo_field_selection_list', $_POST) ) {
    update_post_meta($post_id, '_jo_field_selection_meta_key', $_POST['jo_field_selection_list']);
  }
}

add_action('save_post', 'jo_save_metadata');

function my_added_page_content ( $content )
{
  if ( is_single() ) {
    $post_id = get_the_ID();
    $jira_selected_fields = get_post_meta($post_id, '_jo_field_selection_meta_key', true);

    $endpoint = get_option('jo_endpoint');
    $user = get_option('jo_user');
    $password = get_option('jo_password');

    $start = strpos($content, '[JIRA]');
    if ( $start === false )
      return $content;
    $end = strpos($content, '[/JIRA]', $start);
    if ( $end === false )
      return $content;
    $filter_str = substr($content, $start + 6, $end - $start - 6);

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

    if ( ! empty($root) )
    {
      if ( array_key_exists('errorMessages', $root) )
      {
        $output_error = '';
        $error_messages = $root->errorMessages;
        $output_error = $output_error . '<table>';
        $output_error = $output_error . '<tr>';
        $output_error = $output_error . '<th>Error</th>';
        $output_error = $output_error . '</tr>';
        foreach ( $error_messages as $error_message )
        {
          $output_error = $output_error . '<tr><td>' . $error_message . '</td></tr>';
        }
        $output_error = $output_error . '</table>';
        return $output_error;
      }


      $issue_table = $issue_table . '<table>';
      $issue_table = $issue_table . '<tr>';
      $issue_table = $issue_table . '<th>key</th>';
      foreach ( $jira_selected_fields as $id => $name ) {
        $issue_table = $issue_table . '<th>' . $name . '</th>';
      }
      $issue_table = $issue_table . '</tr>';
      foreach ( $root->issues as $issue )
      {
        $issue_table = $issue_table . '<tr>';
        $issue_table = $issue_table . '<td><a href="'. $issue->self . '">' . $issue->key . '</a></td>';
        foreach ( $jira_selected_fields as $jira_selected_field ) {
          if ( array_key_exists($jira_selected_field, $issue->fields) ) {
            $field = $issue->fields->{ $jira_selected_field };
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
    $ret = substr($content, 0, $start);
    $ret = $ret . $issue_table;
    $ret = $ret . substr($content, $end + 7);

    return $ret;
  }
    return $content;
}
add_filter( 'the_content', 'my_added_page_content');

add_action('admin_menu', 'plugin_admin_add_page');
add_action('admin_init', 'plugin_admin_init');

function plugin_admin_add_page() {
    add_options_page('Bridge To Jira', 'Bridge To Jira Settings', 'manage_options', 'jo-options', 'plugin_options_page');
}

function plugin_options_page() {
?>
<div>
    <h2>Bridge To Jira Plugin Options</h2>
    <form action="options.php" method="post">
    <?php settings_fields('jo-options'); ?>
    <?php do_settings_sections('jo-options'); ?>

    <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
</div>

<?php
}

function plugin_admin_init(){
  add_settings_section('jo_jira_connection_section', 'Jira Connection', 'jira_connection_section_cb', 'jo-options');

  register_setting('jo-options', 'jo_endpoint');
  register_setting('jo-options', 'jo_user');
  register_setting('jo-options', 'jo_password');

  add_settings_field('jo_endpoint', 'Endpoint', 'jo_endpoint_cb', 'jo-options', 'jo_jira_connection_section');
  add_settings_field('jo_user', 'Username', 'jo_user_cb', 'jo-options', 'jo_jira_connection_section');
  add_settings_field('jo_password', 'Password', 'jo_password_cb', 'jo-options', 'jo_jira_connection_section');
}

function jira_connection_section_cb() {
  echo '<p>These are the necessary settings to connect to Jira.</p>';
}

function jo_endpoint_cb() {
  echo "<input id='jo_endpoint' name='jo_endpoint' size='40' type='text' value='" . get_option('jo_endpoint') . "' />";
}

function jo_user_cb() {
  echo "<input id='jo_user' name='jo_user' size='40' type='text' value='" . get_option('jo_user') . "' />";
}

function jo_password_cb() {
  echo "<input id='jo_password' name='jo_password' size='40' type='password' value='" . get_option('jo_password') . "' />";
}

function plugin_options_validate($input) {
  //$newinput['jo_endpoint_option_key'] = trim($input['jo_endpoint_option_key']);
  //if(!preg_match('/^[a-z0-9]{32}$/i', $newinput['jo_endpoint_option_key'])) {
  //    $newinput['jo_endpoint_option_key'] = '';
  //}
  return $input;
}

?>
