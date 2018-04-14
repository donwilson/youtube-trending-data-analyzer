<?php
	require_once(__DIR__ ."/config.php");
	
	use Pheanstalk\Pheanstalk;
	
	define('PROCESS_BEANSTALK_NAME', md5("youtube_trending:process"));
	
	define('PROCESS_DATA_DIR', __DIR__ ."/data/");
	define('PROCESS_MAX_ATTEMPTS', 3);
	
	try {
		$pheanstalk = new Pheanstalk("127.0.0.1");
		
		while($job = $pheanstalk->watch(PROCESS_BEANSTALK_NAME)->ignore("default")->reserve()) {
			$ts_start = microtime(true);
			$file_data = json_decode($job->getData(), true);
			
			if(!isset($file_data['attempts'])) {
				$file_data['attempts'] = 0;
			}
			
			print "Starting on ". $file_data['file'] ."... ";
			
			if($file_data['attempts'] >= PROCESS_MAX_ATTEMPTS) {
				// record max fails
				db_query("
					INSERT INTO `log`
					SET
						`date` = NOW(),
						`type` = 'max_attempts',
						`cargo` = '". esc_sql(json_encode([
							'file' => $file_data['file'],
						])) ."'
				");
				
				print "too many attempts (". $file_data['attempts'] ." of ". PROCESS_MAX_ATTEMPTS ."), logging and removing\n";
				
				$pheanstalk->delete($job);
				
				continue;
			}
			
			$extracted_data = extract_youtube_trending_data(@file_get_contents(PROCESS_DATA_DIR . $file_data['file']), $file_data['timestamp']);
			
			if(empty($extracted_data['videos'])) {
				// fail
				$file_data['attempts'] += 1;
				
				print "fail (". (microtime(true) - $ts_start) .")\n";
				
				$pheanstalk->delete($job);
				$pheanstalk->useTube(PROCESS_BEANSTALK_NAME)->put(json_encode($file_data));
				
				continue;
			}
			
			print "done (". (microtime(true) - $ts_start) .") - importing: ";
			$ts_start = microtime(true);
			
			foreach($extracted_data['videos'] as $video) {
				$section = false;
				
				if(isset($video['section']) && isset($extracted_data['sections'][ $video['section'] ])) {
					$section = $extracted_data['sections'][ $video['section'] ];
				}
				
				db_query("
					INSERT INTO `video`
					SET
						`timestamp` = '". esc_sql($file_data['timestamp']) ."',
						`youtube_id` = '". esc_sql($video['id']) ."',
						`position` = '". esc_sql($video['position']) ."',
						`author` = ". (!empty($video['author']['db_value'])?"'". esc_sql($video['author']['db_value']) ."'":"NULL") .",
						`views` = '". esc_sql( (isset($video['views'])?$video['views']:"0") ) ."',
						`age_minutes` = '". esc_sql( (isset($video['age_minutes'])?$video['age_minutes']:"0") ) ."',
						`verified` = '". esc_sql( (!empty($video['verified'])?"1":"0") ) ."',
						`stream` = '". esc_sql( (!empty($video['stream'])?"1":"0") ) ."',
						`section_raw` = ". (!empty($section)?"'". esc_sql(json_encode($section)) ."'":"NULL") .",
						`cargo` = '". esc_sql(json_encode($video)) ."'
					ON DUPLICATE KEY UPDATE
						`position` = VALUES(`position`),
						`author` = VALUES(`author`),
						`views` = VALUES(`views`),
						`age_minutes` = VALUES(`age_minutes`),
						`verified` = VALUES(`verified`),
						`stream` = VALUES(`stream`),
						`section_raw` = VALUES(`section_raw`),
						`cargo` = VALUES(`cargo`)
				");
			}
			
			$pheanstalk->delete($job);
			
			print "done (". (microtime(true) - $ts_start) .")\n";
		}
	} catch(Exception $e) {
		die("Error: ". $e->getMessage() ."\n");
	}