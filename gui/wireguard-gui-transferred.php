<?php
exec("/usr/local/bin/wg | grep \"transfer:\" | cut -d: -f 2 | awk '{\$1=\$1};1'", $result);
if(!empty($result)) {
	echo $result[0];
} 
?>
