<?php
require __DIR__.'/vendor/autoload.php';

ini_set('display_errors', 1); error_reporting(E_ALL);

use joseluisald\Ab\Ab;

$homepageColorTest = new Ab('homepage_color', array(
    'blue' => 1,
    'red' => 1,
));

?>

<body style="background-color: <?php echo $homepageColorTest->getVariation(); ?>">