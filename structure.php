<?php

require_once 'inc.config.php';

list($_db, $_tbl) = requireParams('db', 'tbl');
require_once 'inc.database.php';
require_once 'inc.table.php';

require_once 'tpl.header.php';
require_once 'tpl.database.php';
require_once 'tpl.table.php';

if ( isset($_POST['name'], $_POST['type']) ) {
	var_dump($db->addColumn($_tbl, $_POST['name'] . ' ' . $_POST['type']));
	echo '[Error: ' . $db->error . "]\n";
	exit;
}

echo '<pre>';
echo html(trim($objTbl->sql));
echo '</pre>';

$columns = $db->structure($_tbl);
echo '<table border="1" cellpadding="4" cellspacing="2">';
foreach ( $columns AS $c => $t ) {
	$grouper = 'SELECT ' . $db->escapeAndQuoteStructure($c) . ', COUNT(1) AS num FROM ' . $db->escapeAndQuoteStructure($_tbl) . ' GROUP BY ' . $db->escapeAndQuoteStructure($c);

	echo '<tr>';
	echo '<th>' . $c . '</th>';
	echo '<td>' . $t . '</td>';
	echo '<td><a href="browse.php?db=' . html($_db) . '&tbl=' . html($_tbl) . '&sql=' . html($grouper) . '">browse</a></td>';
	echo '</tr>';
}
echo '</table>';

echo '<br />';

echo '<form method="post">';
echo '<fieldset>';
echo '<legend>Add column</legend>';
echo 'Naam: <input type="text" name="name" /><br />';
echo 'Type: <select name="type"><option>INTEGER</option><option>TEXT</option><option>FLOAT</option></select><br />';
echo '<input type="submit" value="Add!" />';
echo '</fieldset>';
echo '</form>';

echo "\nIndices:\n";
$arrIndices = $db->indices($_tbl);
echo '<pre>';
print_r($arrIndices);
echo '</pre>';

echo "\nPRIMARY KEY:\n";
$arrPK = $db->indices($_tbl, true);
echo '<pre>';
print_r($arrPK);
echo '</pre>';
