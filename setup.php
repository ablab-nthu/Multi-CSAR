#!/usr/bin/env php
<?php
$aux_bin = 'aux_bin/';
$multi_csar_content = file_get_contents('multi-csar.php');
$multi_csar_content = preg_replace('/\$path\s=\s(.)+;/', '$path = "'.getcwd().'/'.$aux_bin.'";', $multi_csar_content, 1);
chmod('multi-csar.php', 0200);
file_put_contents('multi-csar.php', $multi_csar_content);
array_map('chmod', glob($aux_bin.'*'), array_fill(0, 4, 0555));
chmod('multi-csar.php', 0555);
?>
