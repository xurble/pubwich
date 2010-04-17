<!doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title><?php echo PUBWICH_TITLE?></title>
		<link rel="stylesheet" media="screen" href="<?php echo Pubwich::getThemeUrl()?>/style.css" type="text/css">
<?php echo Pubwich::getHeader() ?>
	</head>
	<body>
		<div id="wrap">
			<h1><a href="/" rel="me"><?php echo PUBWICH_TITLE?></a></h1>
			<hr>
			<div class="clearfix">

<?php echo Pubwich::getLoop()?>

			</div>
			<div id="footer">
				<div class="footer-inner">
					<hr>
					<?php echo sprintf( Pubwich::_('All data is &copy; copyright %s. Proudly powered by %s.'), date('Y'), '<a class="pubwich" href="http://pubwich.org/">Pubwich</a>'  )?>
				</div>
			</div>
		</div>
<?php echo Pubwich::getFooter() ?>
	</body>
</html>
