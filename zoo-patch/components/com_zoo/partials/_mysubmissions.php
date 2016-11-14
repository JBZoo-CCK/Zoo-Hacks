<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$this->app->document->addStylesheet('assets:css/submission.css');
$this->app->document->addScript('assets:js/submission.js');

$mysubmissions_link = $this->app->route->mysubmissions($this->submission);

?>

<div id="mysubmissions">

	<div class="toolbar clearfix">

		<?php if($this->show_add): ?>
		<div class="submission-add">
			<a href="javascript:void(0);" class="trigger" title="<?php echo JText::_('Add Item'); ?>"><?php echo JText::_('Add Item'); ?></a>
			<div class="links">
			<?php foreach($this->types as $id => $type) : ?>
				<?php $add_link = $this->app->route->submission($this->submission, $id, null, 0, 'mysubmissions'); ?>
				<div class="add-link">
					<a href="<?php echo JRoute::_($add_link); ?>" title="<?php echo sprintf(JText::_('Add %s'), $type->name); ?>"><?php echo $type->name; ?></a>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if (isset($this->lists['select_type'])) : ?>
		<form class="submission-filter" action="<?php echo JRoute::_($this->pagination->link($mysubmissions_link, 'page='.$this->page)); ?>" method="post" name="adminForm" id="adminForm" accept-charset="utf-8">
			<?php echo $this->lists['select_type']; ?>
			<input type="text" name="search" id="zoo-search" value="<?php echo $this->lists['search'];?>" />
			<button onclick="this.form.submit();"><?php echo JText::_('Search'); ?></button>
			<button onclick="document.getElementById('zoo-search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
		</form>
		<?php endif; ?>

	</div>

	<?php if (count($this->items)) : ?>
	<ul class="submissions">

		<?php foreach ($this->items as $id => $item) : ?>
		<li>

			<div class="header">

					<a href="<?php echo $this->app->link(array('controller' => 'submission', 'submission_id' => $this->submission->id, 'task' => 'remove', 'item_id' => $id)); ?>" title="<?php echo JText::_('Delete Item'); ?>" class="item-icon delete-item"></a>

				<?php $edit_link = $this->app->route->submission($this->submission, $item->type, null, $id, 'mysubmissions'); ?>
				<a href="<?php echo JRoute::_($edit_link); ?>" title="<?php echo JText::_('Edit Item'); ?>" class="item-icon edit-item"></a>
				<h3 class="toggler"><?php echo $item->name; ?> <span>(<?php echo $item->getType()->name; ?>)</span></h3>
			 </div>

			<?php $this->params = $item->getParams('site'); ?>
			<?php $type = ($this->renderer->pathExists('item/'.$item->type)) ? $item->type : 'item'; ?>
			<div class="preview hidden <?php echo $type; ?>">
				<?php
					$layout  = 'item.'.($type != 'item' ? $item->type . '.' : '');
					echo $this->renderer->render($layout.'full', array('view' => $this, 'item' => $item));
				?>
			</div>

		</li>
		<?php endforeach; ?>

	</ul>
	<?php else : ?>

		<?php if (empty($this->lists['search'])) : ?>
		<p class="no-submissions"><?php echo sprintf(JText::_('You have not submitted any %s items yet.'), $this->filter_type); ?></p>
		<?php else : ?>
		<p class="no-submissions"><?php echo JText::_('SEARCH_NO_ITEMS').'!'; ?></p>
		<?php endif; ?>

	<?php endif; ?>

	<div class="pagination">
		<?php echo $this->pagination->render($mysubmissions_link); ?>
	</div>

</div>

<script type="text/javascript">
	jQuery(function($) {
		$('#mysubmissions').SubmissionMysubmissions({ msgDelete: '<?php echo JText::_('SUBMISSION_DELETE_CONFIRMATION'); ?>' });
	});
</script>
