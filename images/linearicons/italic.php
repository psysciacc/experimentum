<?php
	header('Content-type: image/svg+xml');
	
	$fill = array_key_exists("c", $_GET) ? $_GET['c'] : "000000";
?>
<svg version="1.1"
     baseProfile="full"
     xmlns="http://www.w3.org/2000/svg"
     width="20" height="20" viewBox="0 0 20 20">
<path fill="#<?= $fill ?>" d="M15.5 2h-4.001c-0.001 0-0.003 0-0.004 0h-3.995c-0.276 0-0.5 0.224-0.5 0.5s0.224 0.5 0.5 0.5h3.39l-2.8 14h-3.59c-0.276 0-0.5 0.224-0.5 0.5s0.224 0.5 0.5 0.5h3.999c0.001 0 0.001 0 0.002 0s0.001-0 0.002-0h3.997c0.276 0 0.5-0.224 0.5-0.5s-0.224-0.5-0.5-0.5h-3.39l2.8-14h3.59c0.276 0 0.5-0.224 0.5-0.5s-0.224-0.5-0.5-0.5z"></path>
</svg>
