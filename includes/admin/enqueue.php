<?php

function btj_admin_enqueue($hook) {
  global $typenow;

  if ( $hook != 'settings_page_jo-options' )  {
    return;
  }

  wp_register_style(
    'btj_bootstrap',
    plugins_url('/assets/style/bootstrap.css', BTJ_PLUGIN_URL)
  );

  wp_enqueue_style('btj_bootstrap');
}