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
<script id="dsq-count-scr" src="//<?php echo $website; ?>.disqus.com/count.js" async></script>
<script>
    /**
     *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
     *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables
     */

    var disqus_config = function () {
        this.page.url = "<?php echo JRoute::_($this->app->route->item($this->getItem(), false), true, 2);?>";
        this.page.identifier = "item<?php echo $this->getItem()->id;?>";
    };

    (function() {  // DON'T EDIT BELOW THIS LINE
        var d = document, s = d.createElement('script');

        s.src = '//<?php echo $website; ?>.disqus.com/embed.js';


        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript" rel="nofollow">comments powered by Disqus.</a></noscript>