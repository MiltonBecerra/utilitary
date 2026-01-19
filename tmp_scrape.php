<?php
require __DIR__.'/vendor/autoload.php';
$s=new App\Services\ScrapingService();
$ref=new ReflectionClass($s);
$m=$ref->getMethod('fetchHtml');
$m->setAccessible(true);
$html=$m->invoke($s,'https://kambista.com/');
file_put_contents('tmp_kambista.html',$html);
echo strlen($html);
?>
