<?php
echo "VERSION: " . date('Y-m-d H:i:s') . " UTC";
echo "\nFiles copied at: " . filemtime(__FILE__);
echo "\nTime now: " . time();
echo "\nDelta (seconds since file was copied): " . (time() - filemtime(__FILE__));