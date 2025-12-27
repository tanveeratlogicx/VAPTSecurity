<?php
// Load WordPress
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

// Reset the corrupted option
delete_option('vapt_domain_features');
update_option('vapt_domain_features', []);

echo "Option reset successfully.";
