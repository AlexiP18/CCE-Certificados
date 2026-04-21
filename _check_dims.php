<?php
$root = 'C:/xampp/htdocs/cce-certificados/assets/templates/default_template.png';
$pub = 'C:/xampp/htdocs/cce-certificados/public/assets/templates/default_template.png';
echo "Root: ";
$i = getimagesize($root);
echo ($i ? $i[0].'x'.$i[1] : 'NOT FOUND') . PHP_EOL;
echo "Public: ";
if (file_exists($pub)) {
    $j = getimagesize($pub);
    echo ($j ? $j[0].'x'.$j[1] : 'NOT FOUND') . PHP_EOL;
} else {
    echo "NOT FOUND" . PHP_EOL;
}
