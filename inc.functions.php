<?php

function format_size($bytes) {
	$kb = $bytes / 1000;
	$size =  $kb > 999 ? number_format($kb / 1000, 1) . ' <b>MB</b>' : number_format($kb) . ' kB';
	return $size;
}

function csv_escape( $val ) {
	return str_replace('"', '""', $val);
}

function csv_row( $data ) {
	return '"' . implode('","', array_map('csv_escape', $data)) . '"' . "\r\n";
}

function csv_cols( $data ) {
	$cols = array();
	foreach ( $data as $i => $name ) {
		$cols[] = !is_int($i) && is_callable($name) ? $i : $name;
	}
	return $cols;
}

function csv_rows( $data ) {
	return implode(array_map('csv_row', $data));
}

function csv_header( $filename = '' ) {
	header('Content-Type: text/plain; charset=utf-8');

	if ( $filename ) {
		header('Content-Disposition: attachment; filename="' . $filename . '"');
	}
}

function csv_file( $data, $cols, $filename = '' ) {
	csv_header($filename);

	echo csv_row(csv_cols($cols));
	foreach ( $data AS $row ) {
		$data = array();
		foreach ( $cols as $i => $name ) {
			$data[] = !is_int($i) && is_callable($name) ? $name($row) : $row->$name;
		}
		echo csv_row($data);
	}

	if ( $filename ) {
		exit;
	}
}
