<?php

function btj_admin_init() {
  include('enqueue.php');

  add_action('admin_enqueue_scripts', 'btj_admin_enqueue');
}