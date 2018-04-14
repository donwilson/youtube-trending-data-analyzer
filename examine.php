<?php
	require_once(__DIR__ ."/vendor/autoload.php");
	
	$available_folders = [];
	
	$raw_folders = glob("data/*");
	
	foreach($raw_folders as $raw_folder) {
		if(!in_array($raw_folder, [".", ".."]) && is_dir($raw_folder)) {
			$available_folders[] = basename($raw_folder);
		}
	}
	
	$selected_folder = ((!empty($_REQUEST['folder']) && in_array($_REQUEST['folder'], $available_folders))?$_REQUEST['folder']:false);
	
	if(false !== $selected_folder) {
		$files = [];
		
		$raw_files = glob(__DIR__ ."/data/". $selected_folder ."/*.html");
		
		foreach($raw_files as $raw_file) {
			if(!preg_match("#\-([0-9]{10})\.html$#si", $raw_file, $match)) {
				continue;
			}
			
			$files[] = [
				'folder' => $selected_folder,
				'file' => basename($raw_file),
				'time' => $match[1],
			];
		}
		
		usort($files, function($a, $b) {
			if($a['time'] > $b['time']) {
				return 1;
			} elseif($a['time'] < $b['time']) {
				return -1;
			}
			
			return 0;
		});
	}
	
	
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Examine - YouTube Trending</title>
	
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-sm-3">
				<h3>Folders</h3>
				
				<ul>
					<?php foreach($available_folders as $available_folder): ?>
						<li<?php if($available_folder == $selected_folder): ?> class="active"<?php endif; ?>>
							<?php if($available_folder == $selected_folder): ?>
								<?=htmlentities($available_folder);?>
							<?php else: ?>
								<a href="?folder=<?=urlencode($available_folder);?>"><?=htmlentities($available_folder);?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="col-sm-9">
				<?php if(false !== $selected_folder): ?>
					<h3><?=htmlentities($selected_folder);?></h3>
					
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th>Date</th>
								<th>Video IDs</th>
								<th class="text-right">Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($files as $file): ?>
								<tr>
									<td><?=date("M-j-y @ h:ia", $file['time']);?></td>
									<td>
										Unknown
									</td>
									<td class="text-right"><a href="<?=htmlentities("peek.php?folder=". urlencode($file['folder']) ."&file=". urlencode($file['file']));?>" target="_blank"><?=htmlentities($file['file']);?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<h3>Pick a Folder</h3>
					
				<?php endif; ?>
			</div>
		</div>
	</div>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	<script type="text/javascript">
		;jQuery(document).ready(function($) {
			
		});
	</script>
</body>
</html>