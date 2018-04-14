<?php
	// https://github.com/devcem/PHP-jquery-like-selector
	require_once(__DIR__ ."/PHP-jquery-like-selector/lib/getbone.php");
	require_once(__DIR__ ."/PHP-jquery-like-selector/lib/ganon.php");
	
	function make_querier($html) {
		$parser = new html([
			'out' => $html,
			'ch' => false,
		]);
		
		return $parser->html();
	}
	
	function encoded_html_to_html($html) {
		$html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, "UTF-8");
		$html = preg_replace_callback("/(&#[0-9]+;)/", function($match) {
			return mb_convert_encoding($match[1], "UTF-8", "HTML-ENTITIES");
		}, $html);
		$html = strtr($html, array(
			"‘"				=> "'",
			"’"				=> "'",
			"&#8216;"		=> "'",
			"&#8217;"		=> "'",
			"&rsquo;"		=> "'",
			"&#132;"		=> "'",
			
			"“"				=> "\"",
			"”"				=> "\"",
			"&#8220;"		=> "\"",
			"&#8221;"		=> "\"",
		));
		
		return $html;
	}
	
	function extract_videoId($html) {
		// link
		if(preg_match("#watch\?v=([A-Za-z0-9\-_]{11})#si", $html, $match)) {
			return trim($match[1], " \t\r\n?&#");
		}
		
		return false;
	}
	
	// extract data into {VIDEO_ID: {info}[, ...]}
	function extract_youtube_trending_data($raw_html, $timestamp) {
		//if(($timestamp >= ) && ($timestamp <= )) {
		//	// date-specific extract
		//}
		
		$extraction_data = [
			'sections' => [],
			'videos' => [],
			'debug' => [],
		];
		
		$html = make_querier($raw_html);
		
		$video_position = 0;
		$section_i = 0;
		
		foreach($html->find(".section-list > li") as $section) {
			$section_i++;
			
			$html2 = make_querier($section->html());
			
			
			// extract section info
			$section_data = [];
			
			$section_header = $html2->find("h2.shelf-title-cell");
			
			if(isset($section_header[0])) {
				$section_title_raw = $section_header[0]->html();
				
				if(false !== strpos($section_title_raw, "branded-page-module-title-link")) {
					$section_title_query = make_querier($section_title_raw);
					
					// section title
					$section_title_text = $section_title_query->find(".shelf-featured-badge");
					
					if(!empty($section_title_text[0])) {
						$section_data['title'] = trim($section_title_text[0]->text());
					} else {
						trigger_error("Unable to find expected section link title at section #". $section_i, E_USER_WARNING);
					}
					
					// section object
					$section_title_link = $section_title_query->find(".branded-page-module-title-text");
					
					if(!empty($section_title_link[0])) {
						$section_title_object_html = $section_title_link[0]->html();
						
						$section_data['object'] = [
							'title' => trim(encoded_html_to_html(strip_tags($section_title_object_html))),
						];
						
						if(preg_match("#href=(\"|')(.+?)\\1#si", $section_title_raw, $match)) {
							if("" !== ($link = trim($match[2], " \t\r\n\"'"))) {
								if(!preg_match("#^https?\://#si", $link)) {
									// prepend youtube.com to partial links
									$link = "https://www.youtube.com/". ltrim($link, "/");
								}
								
								$section_data['object']['link'] = $link;
							}
						}
					} else {
						trigger_error("Unable to find expected section title object at section #". $section_i, E_USER_WARNING);
					}
				} else {
					// section title is just raw text
					$section_data['title'] = trim(encoded_html_to_html(strip_tags($section_title_raw)));
				}
			}
			
			$extraction_data['sections'][ $section_i ] = $section_data;
			
			
			// scan through section videos
			$video_i = 0;
			
			foreach($html2->find(".expanded-shelf > ul > li") as $section_item) {
				$video_position++;
				$video_i++;
				
				$video_html = $section_item->html();
				$video_querier = make_querier($video_html);
				
				if(false === ($videoId = extract_videoId($video_html))) {
					trigger_error("Unable to find Video ID at section #". $section_i ." video #". $video_i, E_USER_NOTICE);
					
					continue;
				}
				
				$video_data = [
					'id' => $videoId,
					'section' => $section_i,
				];
				
				// process video html
				
				// title
				$title_find = $video_querier->find(".yt-lockup-title a");
				
				if(isset($title_find[0])) {
					if("" !== ($video_title = trim($title_find[0]->text()))) {
						$video_data['title'] = encoded_html_to_html($video_title);
					}
				}
				
				// description
				$description_find = $video_querier->find(".yt-lockup-description");
				
				if(isset($description_find[0])) {
					$video_desc = $description_find[0]->html();
					$video_desc = strip_tags($video_desc, "<br>");
					$video_desc = preg_replace("#\s*<br\s*/?>\s*#si", "\n", $video_desc);
					//$video_desc = preg_replace("#\s*\.\.\.\s*$#si", "", $video_desc);
					$video_desc = encoded_html_to_html($video_desc);
					$video_desc = trim($video_desc);
					
					if("" !== $video_desc) {
						$video_data['description'] = $video_desc;
					}
				}
				
				// video length
				$time_find = $video_querier->find(".video-time");
				if(isset($time_find[0])) {
					$time = $time_find[0]->text();
					$time = trim($time);
					
					$seconds = 0;
					
					if(preg_match("#^([0-9]+)\:([0-9]{2})\:([0-9]{2})$#si", $time, $match)) {
						$seconds = (((int)$match[1] * 60 * 60) + ((int)$match[2] * 60) + (int)$match[3]);
					} elseif(preg_match("#^([0-9]{1,2})\:([0-9]{2})$#si", $time, $match)) {
						$seconds = (((int)$match[1] * 60) + (int)$match[2]);
					}
					
					if(!empty($seconds)) {
						$video_data['length'] = (int)$seconds;
					}
				}
				
				// author
				$author_find = $video_querier->find(".yt-lockup-byline");
				
				if(isset($author_find[0])) {
					$author_info = [];
					
					$author_html = $author_find[0]->html();
					
					$author_name_querier = make_querier($author_html);
					$author_name_find = $author_name_querier->find("a");
					
					if(isset($author_name_find[0])) {
						if("" !== ($author_username = trim(encoded_html_to_html($author_name_find[0]->text())))) {
							$author_info['username'] = $author_username;
						}
					}
					
					if(preg_match("#href=(\"|')(.+?)\\1#si", $author_html, $match)) {
						if("" !== ($link = trim($match[2], " \t\r\n\"'"))) {
							if(!preg_match("#^https?\://#si", $link)) {
								// prepend youtube.com to partial links
								$link = "https://www.youtube.com/". ltrim($link, "/");
							}
							
							$author_info['link'] = $link;
							
							if(preg_match("#youtube\.com/(channel|user)/(.+?)(?:\?|\#|$)#si", $link, $match_user)) {
								$author_info['db_value'] = trim($match_user[2], " \t\r\n/?#") ."/". trim($match_user[1], " \t\r\n/");
							}
						}
					}
					
					if(!empty($author_info)) {
						$video_data['author'] = $author_info;
					}
				}
				
				// meta
				$meta_find = $video_querier->find(".yt-lockup-meta-info li");
				
				foreach($meta_find as $meta) {
					$meta_text = trim($meta->text());
					
					if(preg_match("#^([0-9,]+)\s*views?#si", $meta_text, $match)) {
						$video_data['views'] = preg_replace("#[^0-9]+#si", "", $match[1]);
					} elseif(preg_match("#^(Streamed\s*)?([0-9]+)\s*(minute?|hour|day|week|month)s?\s*ago?#si", $meta_text, $match)) {
						if("streamed" == strtolower(trim($match[1]))) {
							$video_data['stream'] = true;
						}
						
						$age_num = (int)$match[2];
						$minutes_ago = 0;
						
						switch(strtolower(trim($match[3]))) {
							case 'minute':
								$minutes_ago = $age_num;
							break;
							case 'hour':
								$minutes_ago = ($age_num * 60);
							break;
							case 'day':
								$minutes_ago = ($age_num * 60 * 24);
							break;
							case 'week':
								$minutes_ago = ($age_num * 60 * 24 * 7);
							break;
							case 'month':
								$minutes_ago = ($age_num * 60 * 24 * 30);
							break;
							case 'year':
								$minutes_ago = ($age_num * 60 * 24 * 365);
							break;
						}
						
						if(!empty($minutes_ago)) {
							$video_data['age_minutes'] = $minutes_ago;
						}
					}
				}
				
				// verified
				$verified_find = $video_querier->find(".yt-channel-title-icon-verified");
				
				if(isset($verified_find[0])) {
					$video_data['verified'] = true;
				}
				
				// add video positions
				$video_data['position'] = $video_position;
				$video_data['section_position'] = $video_i;
				
				$extraction_data['videos'][] = $video_data;
			}
		}
		
		return $extraction_data;
	}