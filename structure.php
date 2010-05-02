<?php

require_once('inc.table.php');

if ( isset($_POST['name'], $_POST['type']) ) {
	var_dump($db->addColumn($szTable, $_POST['name'].' '.$_POST['type']));
	echo '[Error: '.$db->error."]\n";
	exit;
}

echo '<pre>';
echo $objTbl->sql."\n\n";

$objTable = $db->structure($szTable);
echo '<table border="1" cellpadding="4" cellspacing="2">'."\n";
foreach ( $objTable AS $c => $t ) {
	echo '<tr><th>'.$c.'</th><td>'.$t.'</td><td><a href="browse.php'.QS.'&sql=SELECT '.$c.', COUNT(1) AS num FROM '.$szTable.' GROUP BY '.$c.'">browse</a></td></tr>';
}
echo '</table>'."\n";

echo "\n";

echo '<form method="post"><fieldset><legend>Add column</legend>Naam: <input type="text" name="name" /><br />Type: <select name="type"><option value="INTEGER">INTEGER</option><option value="TEXT">TEXT</option><option value="FLOAT">FLOAT</option></select><br /><input type="submit" value="Add!" /></fieldset></form>'."\n";

echo "\nIndices:\n";
$arrIndices = $db->indices($szTable);
print_r( $arrIndices );

echo "\nPRIMARY KEY:\n";
$arrPK = $db->indices($szTable, true);
print_r( $arrPK );


