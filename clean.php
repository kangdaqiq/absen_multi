<?php
$content = file_get_contents('database/schema/mysql-schema.sql');
$content = preg_replace('/INSERT INTO `(?!migrations)[^`]+`[^;]+;/is', '', $content);
file_put_contents('database/schema/mysql-schema.sql', $content);
