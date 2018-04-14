<?php
	require_once(__DIR__ ."/config.php");
	
	// internal lib
	require_once(__DIR__ ."/lib/process.php");
	
	$base_data_dir = __DIR__ ."/data/";
	
	if(empty($_REQUEST['folder']) || empty($_REQUEST['file']) || ($base_data_dir !== substr(realpath($base_data_dir . $_REQUEST['folder'] ."/". $_REQUEST['file']), 0, strlen($base_data_dir)))) {
		die("Malformed request");
	}
	
	$filepath = $base_data_dir . $_REQUEST['folder'] ."/". $_REQUEST['file'];
	
	if(!preg_match("#\-([0-9]{10})\.html$#si", $filepath, $match)) {
		die("File malformed");
	}
	
	$timestamp = $match[1];
	
	if(!file_exists($filepath) || (false === ($raw_html = @file_get_contents($filepath)))) {
		die("File not found");
	}
	
	// extract
	$extracted_data = extract_youtube_trending_data($raw_html, $timestamp);
	
	// show
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=htmlentities(date("F j, Y @ h:ia", $timestamp));?> - Peek - YouTube Trending</title>
	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
</head>
<body>
	<div class="container">
		<h1><?=htmlentities("YouTube Trending - ". date("F j, Y @ h:ia", $timestamp));?></h1>
		
		<?php /*
			<pre><?=htmlentities(print_r($extracted_data['sections'], true));?></pre>
		*/ ?>
		
		<hr />
		
		<?php if(!empty($extracted_data['videos'])): ?>
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>Position</th>
						<th>Section</th>
						<th>Video ID</th>
						<th>Video Info</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($extracted_data['videos'] as $video): ?>
						<tr>
							<td><?=number_format($video['position']);?></td>
							<td>
								<?php
									$section = $extracted_data['sections'][ $video['section'] ];
									
									if(!empty($section['title'])) {
										print htmlentities($section['title']);
									}
									
									if(!empty($section['object'])) {
										if(!empty($section['object']['link'])) {
											print " - [<a href=\"https://anon.to/?". htmlentities($section['object']['link']) ."\" target=\"_blank\">";
											
											if(isset($section['object']['title'])) {
												print htmlentities($section['object']['title']);
											} else {
												print "<em>link</em>";
											}
											
											print "</a>]";
										} elseif(isset($section['object']['title'])) {
											print " - [<strong>". htmlentities($section['object']['title']) ."</strong>]";
										}
									}
								?>
							</td>
							<td><a href="https://anon.to/?https://www.youtube.com/watch?v=<?=htmlentities($video['id']);?>" target="_blank"><img src="<?=htmlentities("https://img.youtube.com/vi/". $video['id'] ."/default.jpg");?>" /></a></td>
							<td>
								<h2><?=htmlentities($video['title']);?></h2>
								
								<h5>Parsed Data</h5>
								<p><button class="btn btn-primary" type="button" data-toggle="collapse" data-target="<?=htmlentities("#debug__". $video['id']);?>" aria-expanded="false" aria-controls="collapseExample">Toggle Debug</button></p>
								
								<div class="collapse" id="<?=htmlentities("debug__". $video['id']);?>">
									<div class="well">
										<pre><?=print_r($video, true);?></pre>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else: ?>
			<div class="alert alert-danger text-center">Did not find any videos in this file</div>
		<?php endif; ?>
	</div>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			
		});
	</script>
</body>
</html>