<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

?>

<?php if ($developer) : ?>
	<script type='text/javascript'>
		var disqus_developer = 1;
	</script>
<?php endif; ?>

<div id="disqus_thread"></div>
<script type="text/javascript" src="http://disqus.com/forums/<?php echo $website; ?>/embed.js"></script>
<noscript><a href="http://<?php echo $website; ?>.disqus.com/?url=ref">View the discussion thread.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>
<script type="text/javascript">
	//<![CDATA[
	(function() {
			var links = document.getElementsByTagName('a');
			var query = '?';
			for (var i = 0; i < links.length; i++) {
				if (links[i].href.indexOf('#disqus_thread') >= 0) {
					query += 'url' + i + '=' + encodeURIComponent(links[i].href) + '&';
				}
			}
			document.write('<script charset="utf-8" type="text/javascript" src="http://disqus.com/forums/<?php echo $website; ?>/get_num_replies.js' + query + '"></'+'script>');
		})();
	//]]>
</script>