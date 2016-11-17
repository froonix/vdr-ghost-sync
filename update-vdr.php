#! /usr/bin/php
<?php

// Dieses Script aktualisiert den virtuellen Ordner der VDR Aufnahmen.
// Kompatibel mit den VDR Version 1.6.x (Squeeze) und 1.7.x (Wheezy).
// Kompatibel mit ISO-8859-15 und UTF-8 Zeichenkodierung.
// -------------------------------------------------------------------
// v0.1.7 by killerbees19 (2012-07-28)
// -------------------------------------------------------------------
// Der virtuelle Ordner hat ein neues (lesbares) Format und wird über
// das Netzwerk über Samba, NFS, (UPnP), HTTP und FTP bereitgestellt.

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ToDo: Bei $write_image als Hintergrund vllt. einen verschwommenen
//       und entfärbten (S/W?) Filmausschnitt verwenden?
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

// PID-Datei
$pid_file			= '/tmp/update-vdr.pid';

// Quellordner
#$source_dir		= '/files/VDR';
$source_dir			= '/files/record';

// Zielordner
#$destination_dir	= '/files/records';
$destination_dir	= '/files/VDR';

// Ordner, die ignoriert werden sollen (RegExp)
$ignore_folders		= '';

// Dateien, die ignoriert werden sollen (RegExp)
$ignore_files		= '';

// Dateien, die in $destination_dir liegen dürfen und nicht gelöscht werden
$whitelist			= array(
	$destination_dir . '/cpu-day.png',
	$destination_dir . '/df-day.png',
	$destination_dir . '/hddtemp_smartctl-day.png',
	$destination_dir . '/iostat-day.png',
	$destination_dir . '/load-day.png',
	$destination_dir . '/memory-day.png',
	$destination_dir . '/vdr_femon_localhost-day.png',
	$destination_dir . '/vdr_localhost-day.png',
);

// Neue (gewünschte) Dateiendung
$new_ext			= '.mpg';

// Format von neuen Ordnern, Slashes werden automatisch erkannt.
// Platzhalter werden automatisch enkodiert und leere Platzhalter ignoriert.
$new_format			= '{TITLE}/{SEASON}/{DATE}_{TIME}';

// Ignoriere laufende Aufnahmen, da sie meistens nur fehlerhaft gestreamt werden.
$ignore_running		= true;

// Ignoriere Aufnahmen, die bereits zum Löschen markiert sind, sobald der VDR im Leerlauf ist.
$ignore_deleted		= true;

// Soll in jedem Ordner eine info.txt Datei mit allen Details erstellt werden?
// Falls ein anderer Dateiname gewünscht wird, statt dem boolschen Wert angeben!
$write_info			= true;

// Soll in jedem Ordner eine info.png mit allen Details abgespeichert werden?
// Falls ein anderer Dateiname (jpeg, gif oder png!) gewünscht wird, statt dem boolschen Wert angeben!
$write_image		= 'info.jpg';

// Soll in jedem Ordner eine all.m3u Playlist gespeichert werden?
// Falls ein anderer Dateiname gewünscht wird, statt dem boolschen Wert angeben!
$write_playlist		= false;

// Soll eine "recording" Datei gespeichert werden, wenn die Aufnahme noch läuft?
// Falls ein anderer Dateiname gewünscht wird, statt dem boolschen Wert angeben!
$write_recording	= false;

// Soll in $destination_dir eine Datei mit aktuellen Datum und Uhrzeit gespeichert werden?
$write_update		= false;

// Sollen Sonderzeichen in Dateinamen in UTF-8 erhalten bleiben?
// Falls nicht, werden alle konvertiert/entfernt - inklusive Leerzeichen!
// Dadurch gibt es 100% keine Encoding Probleme für Non-Unicode Clients.
$unicode_fs			= true;

// Zeitlimit für die Ausfürung des Scriptes in Sekunden.
$time_limit			= 900;

// Niceness für dieses PHP-Script
$nice_level			= 19;

// Optionen, falls $write_image aktiviert ist
$image_width		= 1280;																	// Breite des Bildes
$image_height		= 720;																	// Höhe des Bildes
$image_lifetime		= false;																// Nach dieser Zeit wird ein gespeichertes Bild neu generiert; "false" angeben, falls das nicht erwünscht ist und es nur ein mal generiert werden soll
$image_background	= hex_to_rgb('#cccccc');												// Hintergrundfarbe als Hex-Code
$image_color		= hex_to_rgb('#000000');												// Textfarbe als Hex-Code
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/freefont/FreeMono.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/freefont/FreeMonoBold.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/freefont/FreeSans.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf';
// $image_font_both	= '/usr/share/fonts/truetype/freefont/FreeSansBoldOblique.ttf';
// $image_font_italic	= '/usr/share/fonts/truetype/freefont/FreeSansOblique.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/freefont/FreeSerif.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/freefont/FreeSerifBold.ttf';
// $image_font_both	= '/usr/share/fonts/truetype/freefont/FreeSerifBoldItalice.ttf';
// $image_font_italic	= '/usr/share/fonts/truetype/freefont/FreeSerifItalic.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans-Bold.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSansCondensed.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSansCondensed-Bold.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSerif.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSerif-Bold.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSerifCondensed.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-dejavu/DejaVuSerifCondensed-Bold.ttf';
// -----------------------------------------------------------------------------
$image_font_normal	= '/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf';
$image_font_bold	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraBd.ttf';
$image_font_both	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraBI.ttf';
$image_font_italic	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraIt.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraSe.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraSeBd.ttf';
// -----------------------------------------------------------------------------
// $image_font_normal	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraMono.ttf';
// $image_font_bold	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraMoBd.ttf';
// $image_font_both	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraMoBI.ttf';
// $image_font_italic	= '/usr/share/fonts/truetype/ttf-bitstream-vera/VeraMoIt.ttf';
// -----------------------------------------------------------------------------
$image_ft			= true;																	// FreeType verwenden?
$image_size			= 20;																	// Schriftgröße in Pixel
$image_space		= 0;																	// Rand in Pixel, der um jeden Buchstaben usw. freigehalten werden soll
$image_line			= ceil($image_size / 2);												// Zeilenabstand in Pixel (zusätzlich zu $image_space!)
$image_border		= 50;																	// Rahmen (Abstand) in Pixel, rund um das Bild
$image_valign		= 't';																	// Vertikale Ausrichtung: (t)op - (m)iddle - (b)ottom

// @ToDo: Eigenes Hintergrundbild unterstützen!

################################################################################
#                            ENDE DER KONFIGURATION                            #
################################################################################

/*
// Debug
$image_lifetime = 1;
$time_limit = 0;
*/

// UTF-8 verwenden
ini_set('mbstring.internal_encoding', 'UTF-8');

// Prüfen, ob bereits eine Instanz von update-vdr.php läuft
$pid = @file_get_contents($pid_file);
if($pid && exec('ps -p ' . escapeshellarg($pid) . ' | wc -l') == 2 && preg_match('#^/usr/bin/php (.*?)' . preg_quote(basename(__file__), '#') . '#i', exec('ps -p ' . escapeshellarg($pid) . ' -o cmd --no-heading')))
{
	throw new Exception('The script is already/still running');
}

// PID beim Scriptende löschen
register_shutdown_function('clean_shutdown');

// PID speichern
$pid = getmypid();
if(!$pid || !file_put_contents($pid_file, $pid))
{
	throw new Exception('Can\'t get my PID');
}

// Fehlerüberprüfung von $source_dir und $destination_dir
// ...

// RegExp auf Fehler überprüfen
// ...

// Ext's auf Fehler überprüfen
// ...

// Fehlerüberprüfung aller anderen Variablen
// ...

// Optionale Schriftarten nicht angegeben?
$image_font_bold	= (!isset($image_font_bold))	? $image_font_normal	: $image_font_bold;
$image_font_italic	= (!isset($image_font_italic))	? $image_font_normal	: $image_font_italic;
$image_font_both	= (!isset($image_font_both))	? $image_font_normal	: $image_font_both;

// Limit setzen
set_time_limit($time_limit);

// Priorität herabsetzen
proc_nice($nice_level);

// Variablen vorbereiten
$running = $source = $destination = $links = $text = $images = $playlist = $recording = $remove = array();

// Alle originalen Dateien einlesen
$walk = array($source_dir);
while(($dir = array_shift($walk)))
{
	if(($res = opendir($dir)))
	{
		// Variablen vorbereiten
		$running	= false;
		$records	= array();
		$info		= array();

		// Alle Dateien des Ordners auslesen
		while(($file = readdir($res)) !== false)
		{
			if(!in_array($file, array('.', '..')))
			{
				$filename = realpath($dir . '/' . $file);
				if($filename && file_exists($filename))
				{
					if(is_dir($filename))
					{
						if(empty($ignore_folders) || !preg_match($ignore_folders, $file))
						{
							// Bereits gelöschte Aufnahme?
							if(!$ignore_deleted || !preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}\\.[0-9]{2}\\.[0-9]{2}(\\.[0-9]{2}){0,1}\\.[0-9-]{1,3}\\.del$#', $file))
							{
								$walk[] = $filename;
								continue;
							}
						}
					}
					else if(is_file($filename))
					{
						if(empty($ignore_files) || !preg_match($ignore_files, $file))
						{
							if($file == 'index.vdr' || $file == 'index')
							{
								continue;
							}
							else if($file == 'info.vdr' || $file == 'info')
							{
								// Aufnahme Details verarbeiten
								$info = parse_vdr_info($filename);
							}
							else if(preg_match('#^[0-9]+\\.(vdr|ts)$#i', $file))
							{
								// Zur Liste hinzufügen
								$records[] = $filename;

								// Läuft die Aufnahme noch?
								if(filemtime($filename) > (time() - 60))
								{
									$running = true;
								}
							}
						}
					}
				}
			}
		}
		closedir($res);

		// Daten zusammensetzen
		if(count($records) && count($info) && (!$ignore_running || !$running))
		{
			$source[] = array(
				'info'		=> $info,
				'records'	=> $records,
				'running'	=> $running,
			);
		}
	}
}

// Alle virtuellen Dateien einlesen
$walk = array($destination_dir);
while(($dir = array_shift($walk)))
{
	if(($res = opendir($dir)))
	{
		// Alle Dateien des Ordners auslesen
		while(($file = readdir($res)) !== false)
		{
			if(!in_array($file, array('.', '..')))
			{
				$filename = realpath($dir . '/' . $file);
				if($filename && file_exists($filename))
				{
					if(is_dir($filename))
					{
						if(empty($ignore_folders) || !preg_match($ignore_folders, $file))
						{
							$walk[] = $filename;
						}
					}
					else if(is_file($filename))
					{
						if(empty($ignore_files) || !preg_match($ignore_files, $file))
						{
							$destination[] = $filename;
						}
					}
				}
			}
		}
		closedir($res);
	}
}

// Benötigte Ordner und Links berechnen ($new_format)
foreach($source as $item)
{
	$folder = $destination_dir . '/' . strtr($new_format, array(
		'{CHANNEL}'	=> encode_string($item['info']['channel']['name']),
		'{TITLE}'	=> encode_string($item['info']['title']),
		'{SEASON}'	=> encode_string($item['info']['season']),
		'{DATE}'	=> date('Y-m-d', $item['info']['time']),
		'{TIME}'	=> date('His', $item['info']['time']),
	));

	$folder = preg_replace('#[/]{2,}#', '/', $folder);
	$folder = str_replace(array('/_/', '/-/'), '', $folder);

	if(isset($links[$folder]))
	{
		$found = false;
		for($i = 2; $i < 1000; $i++)
		{
			$tmp = $folder . '_' . sprintf('%04s', $i);
			if(!isset($links[$tmp]))
			{
				$folder = $tmp;
				$found = true;
				break;
			}
			else
			{
				continue;
			}
		}

		if(!$found)
		{
			continue;
		}
	}

	$links[$folder] = true;
	$tmp = array();

	foreach($item['records'] as $record)
	{
		$basename = preg_replace('#^([0-9]+)\\.(vdr|ts)$#i', '$1', basename($record));
		$key = $folder . '/' . $basename . $new_ext;
		$tmp[] = $basename . $new_ext;
		$links[$key] = $record;
	}

	if($write_info)
	{
		$content = date('d.m.Y, H:i:s', $item['info']['time']) . ' @ ' . $item['info']['channel']['name'] . ' (Laufzeit ca. ' . ceil($item['info']['timer']['duration'] / 60) . ' Minuten)' . "\n\n";
		$content .= $item['info']['title'] . "\n" . $item['info']['season'] . "\n\n";
		$content .= $item['info']['description'] . "\n";

		$info_filename = ((is_string($write_info)) ? basename($write_info) : 'info.txt');
		$text[$folder . '/' . $info_filename] = $content;
	}

	if($write_image && file_exists($folder))
	{
		// Dateinamen generieren
		$image_filename = $folder . '/' . ((is_string($write_image)) ? basename($write_image) : 'info.png');
		$images[$image_filename] = true;

		// Muss das Bild überhaupt erstellt werden?
		if(!file_exists($image_filename) || ($image_lifetime && filemtime($image_filename) < (time() - $image_lifetime)))
		{
			// Debug
			// var_dump($image_filename);

			// Neues Bild generieren
			$image		= imagecreatetruecolor($image_width, $image_height);

			// Farben vorbereiten
			$background	= imagecolorallocate($image, $image_background['r'], $image_background['g'], $image_background['b']);
			$color		= imagecolorallocate($image, $image_color['r'], $image_color['g'], $image_color['b']);

			// Inhalt vorbereiten (Zeilenanfang: \b steht für eine fette Zeile; \i für eine kursive Zeile; \c für mittige Ausrichtung, \r für rechte Ausrichtung; \l für eine durchgehende Linie)
			// Ein Zeilenumbruch findet automatisch statt, wenn die Zeile zu lange ist und nicht ins Bild passt.
			$content = array();
			$content[] = '';
			$content[] = '\\c\\b '	. $item['info']['title'];
			if($item['info']['season'])
			$content[] = '\\c '		. $item['info']['season'];
			$content[] = '\\l ';
			$content[] = '\\r\\i '			. date('d.m.Y, H:i:s', $item['info']['time']) . ' @ ' . $item['info']['channel']['name'] . ' (Laufzeit ca. ' . ceil($item['info']['timer']['duration'] / 60) . ' Minuten)';
			$content[] = '';
			foreach(explode("\n", $item['info']['description']) as $tmp)
			$content[] = ''			. $tmp;

			// Variablen vorbereiten
			$code		= array();

			// Jetzt gehen wir alles einmal durch und schauen, was auf mehrere Zeilen aufgeteilt werden muss
			while(($line = array_shift($content)) !== NULL)
			{
				// Variablen vorbereiten
				$bold	= false;
				$italic	= false;
				$center	= false;
				$right	= false;
				$dline	= false;
				$format	= '';

				// Zeilenanfang erkennen und merken
				while(preg_match('#^\\\\([a-z]{1})#', $line, $matches))
				{
					switch($matches[1])
					{
						case 'b':
							$bold = true;
							break;

						case 'c':
							$center = true;
							break;

						case 'i':
							$italic = true;
							break;

						case 'r':
							$right = true;
							break;

						case 'l':
							$dline = true;
							break;
					}

					$format	.= mb_substr($line, 0, 2);
					$line	 = mb_substr($line, 2);
				}

				// Linie zeichnen?
				if($dline)
				{
					$tmp	= '($i - ' . intval($image_size / 2) . ')';
					$code[]	= 'imageline($image, $image_space + $image_border, ' . $tmp . ', ($image_width - $image_space - $image_border), ' . $tmp . ', $color);';
				}
				else
				{
					// Zeile bereinigen (trim)
					$line = trim($line);

					// Zeile in Zeichen aufteilen
					#$orig_chars = $chars = str_split($line);
					$len = mb_strlen($line);
					$orig_chars = $chars = array();
					for($i = 0; $i < $len; $i++)
					{
						$tmp = mb_substr($line, $i, 1);
						$orig_chars[] = $tmp;
						$chars[] = $tmp;
					}

					// Welche Schriftart wird benötigt?
					if($bold && $italic)
					{
						$image_font = $image_font_both;
					}
					else if($bold)
					{
						$image_font = $image_font_bold;
					}
					else if($italic)
					{
						$image_font = $image_font_italic;
					}
					else
					{
						$image_font = $image_font_normal;
					}

					// Variablen berechnen
					$tmp_size	= array(($image_space + $image_border));
					$tmp_text	= array('');
					$tmp_code	= array('');

					// Die Größe für jedes Zeichen berechnen
					while(($char = array_shift($chars)) !== NULL)
					{
						if(!$image_ft)
						{
							$tmp = imagettfbbox($image_size, 0, $image_font, image_unicode($char));
						}
						else
						{
							$tmp = imageftbbox($image_size, 0, $image_font, image_unicode($char));
						}

						// Brauchen wir eine neue Zeile?
						$tmp = $tmp[4] + $image_space;
						if((array_sum($tmp_size) + $tmp + $image_border) > $image_width)
						{
							// Können wir es bei einem Wort (= Leerzeichen, das dann verloren geht) trennen?
							// Ansonsten nehmen wir die vorherige Methode!
							// ...

							$tmp_pos_start = $tmp_pos_end = 0;

							// Letztes Leerzeichen suchen
							$tmp_pos_start = mb_strrpos(implode('', $tmp_text), ' ');
							$tmp_pos_end = ($tmp_pos_start) ? ($tmp_pos_start + 1) : 0;

							// -------------------------------------------------
							// Theoretisch erweiterbar für weitere Trennzeichen/Sonderzeichen,
							// deshalb gibt es auch eine Variable für pos_start und pos_end :)
							// -------------------------------------------------

							/*
							// Debug
							var_dump(__line__);
							var_dump(implode('', $tmp_text));
							var_dump($tmp_pos_start);
							var_dump($tmp_pos_end);
							exit;
							*/

							if($tmp_pos_start && $tmp_pos_end)
							{
								$tmp_line		= '';
								$count = count($orig_chars);

								for($i = 0; $i < $count; $i++)
								{
									$tmp_char = $orig_chars[$i];

									if($i < $tmp_pos_start)
									{
										// ...
										// ...
									}
									else if($i >= $tmp_pos_end)
									{
										$tmp_line .= $tmp_char;

										unset($tmp_size[$i]);
										unset($tmp_text[$i]);
										unset($tmp_code[$i]);
									}
								}

								$tmp_line = $format . $tmp_line;
								array_unshift($content, $tmp_line);

								/*
								// Debug
								var_dump(implode('', $tmp_text));
								var_dump($tmp_line);
								exit;
								*/
							}
							else
							{
								$array		= $chars;
								array_unshift($array, $char);
								$tmp_line	= $format . implode('', $array);
								array_unshift($content, $tmp_line);
							}

							$chars	= array();
							$char	= '';
							$tmp	= 0;
						}

						if($tmp)
						{
							$tmp_size[] = $tmp;
							$tmp_text[] = $char;
							$tmp_code[] = ((!$image_ft) ? 'imagettftext' : 'imagefttext') . '($image, $image_size, 0, $tmp_start, $i, $color, ' . var_export($image_font, true) . ', ' . var_export(image_unicode($char), true) . '); $tmp_start += ' . $tmp . ';';
						}
					}

					// Zusammensetzen
					$tmp_size = array_sum($tmp_size);
					$tmp_code = implode(' ', $tmp_code);

					// Startposition berechnen
					$tmp_size += $image_border;
					if($center)
					{
						$tmp_start = intval(($image_width - $tmp_size) / 2);
					}
					else if($right)
					{
						$tmp_start = $image_width - $tmp_size;
					}
					else
					{
						$tmp_start = 0;
					}

					// Code zusammensetzen
					$code[]	= '$tmp_start = $image_space + $image_border + ' . $tmp_start . ';' . $tmp_code;
				}
			}

			// Ist der Inhalt größer als das Bild? Dann lassen wir die letzten Zeilen schrittweise weg!
			$code[] = ''; $tmp_height = $image_height + $image_border + 1;
			while($tmp_height > $image_height)
			{
				array_pop($code);
				$tmp_height = $image_space + count($code) * ($image_size + $image_space + $image_line) + $image_border;
			}

			// Debug
			// echo implode("\n", $code) . "\n";
			// exit;

			// Bild ausfüllen
			imagefill($image, 0, 0, $background);

			// Startposition für die Höhe berechnen
			$i = $image_space + $image_border;
			if($image_valign == 'm')
			{
				$i += intval(($image_height - $tmp_height) / 2);
			}
			else if($image_valign == 'b')
			{
				$i += $image_height - $tmp_height;
			}

			// Los geht's, alles in's Bild schreiben :)
			foreach($code as $eval)
			{
				eval($eval);

				$i += $image_size;
				$i += $image_space;
				$i += $image_line;
			}

			// Als was soll es gespeichert werden?
			if(preg_match('/\\.(jpeg|jpg)$/i', $image_filename))
			{
				$function = 'imagejpeg';
				$param = 100;
			}
			else if(preg_match('/\\.gif$/i', $image_filename))
			{
				$function = 'imagegif';
				$param = NULL;
			}
			else if(preg_match('/\\.png$/i', $image_filename))
			{
				$function = 'imagepng';
				$param = 0;
			}
			else
			{
				throw new Exception('Invalid image extension');
			}

			/*
			// Debug
			imagepng($image, '/files/records/test.jpg');
			exit;
			*/

			// Bild speichern
			$function($image, $image_filename, $param);

			// Speicher freigeben
			imagedestroy($image);
		}
	}

	if($write_playlist)
	{
		$playlist_filename = ((is_string($write_playlist)) ? basename($write_playlist) : 'all.m3u');
		$playlist[$folder . '/' . $playlist_filename] = implode("\n", $tmp) . "\n";
	}

	if($item['running'] && $write_recording)
	{
		$recording_filename = ((is_string($write_recording)) ? basename($write_recording) : 'recording.txt');
		$recording[$folder . '/' . $recording_filename] = 'Timer is currently running...' . "\n";
	}
}

// Ordner anlegen und Dateien neu verlinken.
// Zum Thema Inodes: Siehe MantisBT #1588.
foreach($links as $key => $value)
{
	if(($tmp = file_exists($key)) === false || ($value !== true && fileinode($value) != fileinode($key)))
	{
		if($value === true)
		{
			if(!mkdir($key, 0755, true))
			{
				throw new Exception('Can\'t create directory', 0);
			}
		}
		else
		{
			if($tmp)
			{
				# Debug
				#printf("unlink: %s\n", $key);

				// Inodes stimmen nicht überein
				unlink($key);
			}

			// symlinks sucks with samba!
			link($value, $key);
		}
	}
	/*
	else if(is_file($key) && fileinode($value) != fileinode($key))
	{
		# Debug
		printf("Inode mismatch: %s (%d) » %s (%d)\n", $value, fileinode($value), $key, fileinode($key));
	}
	*/
}

// Alle benötigten Dateien anlegen
foreach(array_merge($text, $playlist, $recording) as $key => $value)
{
	if(!file_exists($key) || filesize($key) != strlen($value))
	{
		file_put_contents($key, $value);
	}
}

// Alte unnötigen Links und Inhaltsdateien ermitteln
foreach($destination as $item)
{
	if(!isset($links[$item]) && !isset($text[$item]) && !isset($images[$item]) && !isset($playlist[$item]) && !isset($recording[$item]) && !in_array($item, $whitelist))
	{
		$remove[] = $item;
	}
}

// Unnötige Dateien entfernen
foreach($remove as $item)
{
	unlink($item);
}

// Leere Ordner suchen und entfernen
clean_folder($destination_dir, false);

// Aktuelle Zeit speichern
if($write_update)
{
	file_put_contents($destination_dir . '/' . date('Y-m-d_His'), time() . "\n");
}

// Fertig :)
exit(0);

################################################################################
#                               HILFS-FUNKTIONEN                               #
################################################################################

// PID-Datei bei Scriptende löschen
function clean_shutdown()
{
	global $pid_file;
	unlink($pid_file);
}

// Hilfsfunktion zum verarbeiten der info.vdr Datei
function parse_vdr_info($filename)
{
	if(($content = file_get_contents($filename)))
	{
		// UTF-8 Kodierung herstellen
		if(mb_detect_encoding($content, 'auto', true) !== 'UTF-8')
		{
			$content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-15');
		}

		$result = array(
			'channel'		=> array('uid' => '', 'name' => ''),
			'timer'			=> array('start' => 0, 'end' => 0, 'duration' => 0),
			'title'			=> 'Unbekannter Titel',
			'season'		=> '',
			'description'	=> 'Für diese Sendung gibt es leider keine Beschreibung.',
			'time'			=> 0,
		);

		foreach(explode("\n", $content) as $line)
		{
			$line = trim($line);
			$line = explode(' ', $line, 2);

			switch($line[0])
			{
				case 'C':
					$channel = explode(' ', $line[1]);
					$result['channel'] = array(
						'uid'	=> $channel[0],
						'name'	=> $channel[1],
					);
					break;

				case 'E':
					$timer = explode(' ', $line[1]);
					$result['timer'] = array(
						'start'		=> $timer[1],
						'end'		=> $timer[1] + $timer[2],
						'duration'	=> $timer[2],
					);
					break;

				case 'T':
					$result['title'] = $line[1];
					break;

				case 'S':
					$result['season'] = $line[1];
					break;

				case 'D':
					$result['description'] = str_replace('|', "\n", $line[1]);
					break;

				case 'V':
					$result['time'] = (int) $line[1];
					break;

				default:
					continue 2;
					break;
			}

			if(!$result['time'])
			{
				$result['time'] = $result['timer']['start'];
			}
		}

		if(count($result))
		{
			return $result;
		}
	}

	throw new Exception('Can\'t parse info.vdr', 0);
}

// Hilfsfunktion, um Titel o.ä. ohne Sonderzeichen zu repräsentieren
function encode_string($string)
{
	global $unicode_fs;

	if(!$unicode_fs)
	{
		// Deutsche Umlate und sonstige Zeichen
		$string = strtr($string, array(
			'ä'	=> 'ae',
			'Ä'	=> 'AE',
			'ö'	=> 'oe',
			'Ö'	=> 'OE',
			'ü'	=> 'ue',
			'Ü'	=> 'UE',
			'ß'	=> 'ss',
			'`'	=> '\'',
			'´'	=> '\'',
			'/'	=> '-',
			' '	=> '_',
		));

		// Akzentzeichen: http://www.php.net/manual/en/function.strtr.php#52098
		$string = strtr($string, "\xA1\xAA\xBA\xBF\xC0\xC1\xC2\xC3\xC5\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF\xD0\xD1\xD2\xD3\xD4\xD5\xD8\xD9\xDA\xDB\xDD\xE0\xE1\xE2\xE3\xE5\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF\xF0\xF1\xF2\xF3\xF4\xF5\xF8\xF9\xFA\xFB\xFD\xFF", "!ao?AAAAACEEEEIIIIDNOOOOOUUUYaaaaaceeeeiiiidnooooouuuyy");

		// !$%&()={[]}+~\'#@€^°,;.
		$string = preg_replace('#[^A-Z0-9' . preg_quote('_-', '#') . ']+#i', '', $string);
		$string = preg_replace('#[_]{2,}#', '_', $string);
		$string = preg_replace('#^[_]+#', '', $string);
		$string = preg_replace('#[_]+$#', '', $string);
	}
	else
	{
		// Sonderzeichen entfernen, die Samba nicht versteht...
		$string = preg_replace('#[^[:alnum:][:punct:][:blank:]]+#ui', ' ', $string);
		$string = preg_replace('#[' . preg_quote('\\/:*?"<>|', '#') . ']+#ui', ' - ', $string);

		// Unnötige Leerzeichen/Bindestriche entfernen
		$string = preg_replace('#[-]{2,}#', '-', $string);
		$string = preg_replace('#[ ]{2,}#', ' ', $string);
		$string = preg_replace('#^[ -]+#', '', $string);
		$string = preg_replace('#[ -]+$#', '', $string);
	}

	return $string;
}

// Hilfsfunktion, zum Löschen von leeren Ordnern
function clean_folder($dir, $del = true)
{
	if(($res = opendir($dir)))
	{
		// Zähler resetten
		$count = 0;

		// Alle Dateien des Ordners auslesen
		while(($file = readdir($res)) !== false)
		{
			if(!in_array($file, array('.', '..')))
			{
				$filename = realpath($dir . '/' . $file);
				if($filename && file_exists($filename))
				{
					if(is_dir($filename))
					{
						if(empty($ignore_folders) || !preg_match($ignore_folders, $file))
						{
							if(($tmp = clean_folder($filename, true)))
							{
								$count += $tmp;
							}
						}
						else
						{
							$count++;
						}
					}
					else if(is_file($filename))
					{
						$count++;
					}
				}
			}
		}
		closedir($res);

		// Leer und Löschen?
		if(!$count && $del)
		{
			exec('rm -r ' . escapeshellarg($dir));
		}
	}

	return $count;
}

// Funktion von killerbees19 (?)
function convert_hex($hex, $char = false)
{
	$hex_array = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15);
	$hex = str_replace('#', '', strtoupper($hex));
	$length = strlen($hex);

	if(!in_array($length, array(3, 6, 9, 12)) || strlen(str_replace(array_keys($hex_array), '', $hex)))
	{
		$hex = '';
	}
	elseif($length == 3)
	{
		$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
	}
	elseif($length == 9)
	{
		$r = ceil(($hex_array[$hex[0]] * 16 * 16 + $hex_array[$hex[1]] * 16 + $hex_array[$hex[2]] + 1) / 16) - 1;
	    $g = ceil(($hex_array[$hex[3]] * 16 * 16 + $hex_array[$hex[4]] * 16 + $hex_array[$hex[5]] + 1) / 16) - 1;
	    $b = ceil(($hex_array[$hex[6]] * 16 * 16 + $hex_array[$hex[7]] * 16 + $hex_array[$hex[8]] + 1) / 16) - 1;
		$hex = rgb_to_hex($r, $g, $b, false);
	}
	elseif($length == 12)
	{
		$r = ceil(($hex_array[$hex[0]] * 16 * 16 + $hex_array[$hex[1]] * 16 + $hex_array[$hex[2]] + 1) / 16 / 16) - 1;
	    $g = ceil(($hex_array[$hex[3]] * 16 * 16 + $hex_array[$hex[4]] * 16 + $hex_array[$hex[5]] + 1) / 16 / 16) - 1;
	    $b = ceil(($hex_array[$hex[6]] * 16 * 16 + $hex_array[$hex[7]] * 16 + $hex_array[$hex[8]] + 1) / 16 / 16) - 1;
		$hex = rgb_to_hex($r, $g, $b, false);
	}

	return (($char) ? '#'.$hex : $hex);
}

// Funktion von Frank Burian
// http://www.phpfuncs.org/
// frank.burian@mirrorkey.com
// Direktlink zur Funktion: http://www.phpfuncs.org/?goto=46
function hex_to_rgb($hex)
{
	$hex_array = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15);
	$hex = convert_hex(str_replace('#', '', strtoupper($hex)));

	if (($length = strlen($hex)) == 3)
	{
		$hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
		$length = 6;
	}

	if ($length != 6 || strlen(str_replace(array_keys($hex_array), '', $hex)))
	{
		return NULL;
	}

    $rgb['r'] = $hex_array[$hex[0]] * 16 + $hex_array[$hex[1]];
    $rgb['g'] = $hex_array[$hex[2]] * 16 + $hex_array[$hex[3]];
    $rgb['b']= $hex_array[$hex[4]] * 16 + $hex_array[$hex[5]];
	$rgb['R'] = $rgb['r'];
	$rgb['G'] = $rgb['g'];
	$rgb['B'] = $rgb['b'];
	$rgb = array_merge(array($rgb['r'], $rgb['g'], $rgb['b']), $rgb);

	return $rgb;
}

// UTF-8 odierung für image*()
function image_unicode($content)
{
	#$content	= explode("\n", htmlentities(implode("\n", $content), ENT_DISALLOWED, 'UTF-8'));
	#$content	= explode('&#10;', mb_encode_numericentity(implode("\n", $content), array (0x0, 0xffff, 0, 0xffff), 'UTF-8'));

	return mb_encode_numericentity($content, array (0x0, 0xffff, 0, 0xffff), 'UTF-8');
}

?>
