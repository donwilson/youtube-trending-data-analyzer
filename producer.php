<?php
	require_once(__DIR__ ."/config.php");
	
	use Pheanstalk\Pheanstalk;
	
	// pull timestamp values already processed
	$timestamps = get_col("
		SELECT
			DISTINCT `timestamp`
		FROM `video`
	");
	
	
	define('PROCESS_BEANSTALK_NAME', md5("youtube_trending:process"));
	
	$files = [];
	$raw_folders = glob("data/*");
	
	foreach($raw_folders as $raw_folder) {
		if(in_array($raw_folder, [".", ".."]) || !is_dir($raw_folder)) {
			continue;
		}
		
		$folder = trim(basename($raw_folder), "/");
		
		$raw_files = glob(__DIR__ ."/data/". $folder ."/*.html");
		
		foreach($raw_files as $raw_file) {
			if(!preg_match("#\-([0-9]{10})\.html$#si", $raw_file, $match)) {
				continue;
			}
			
			if(in_array($match[1], $timestamps)) {
				// already processed
				continue;
			}
			
			$files[] = [
				'folder' => $folder,
				'file' => basename($raw_file),
				'time' => $match[1],
			];
		}
	}
	
	usort($files, function($a, $b) {
		if($a['time'] > $b['time']) {
			return 1;
		} elseif($a['time'] < $b['time']) {
			return -1;
		}
		
		return 0;
	});
	
	try {
		$pheanstalk = new Pheanstalk("127.0.0.1");
		
		$num_inserted = 0;
		
		if(!empty($files)) {
			foreach($files as $file) {
				$pheanstalk->useTube(PROCESS_BEANSTALK_NAME)->put(json_encode([
					'file' => $file['folder'] ."/". $file['file'],
					'timestamp' => $file['time'],
					'attempts' => 0,
				]));
				
				$num_inserted++;
			}
		}
		
		print "inserted ". number_format($num_inserted) ." rows\n";
	} catch(Exception $e) {
		die("Error: ". $e->getMessage() ."\n");
	}