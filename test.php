<?php
$content = file_get_contents("https://api.guildwars2.com/v2/worlds?ids=all");
$server_list = json_decode($content, true);

echo "$server = array(\n";
foreach($server_list as $single){
	echo "\t".$single['id']." => \"".$single['name']."\",\n";
}
echo ");\n";
?>