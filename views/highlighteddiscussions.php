<?php if (!defined('APPLICATION')) exit(); ?>
<div class="HighlightedDiscussionsProfile">
<br/>
<?php
echo '<h2>'.T('Highlighted Discussions').'</h2>';
if ($this->Data['HighlightedDiscussions']) {
   echo '<div class="Info Empty">'.sprintf(T('You have %s Highlighted Posts left, feel free to start a %s, checking "Highlight Post". Or you could buy some %s.'),$this->Data['HighlightedDiscussions'],sprintf(T(C('EnabledPlugins.QnA')? T('%s or %s'):'%s'),Anchor('New Discussion','/post/discussion'),Anchor('New Question','/post/question')),Anchor('more',C('Plugins.MarketPlace.StoreURI','store').'/type/PurchaseHighlightedDiscussions')).'</div>';
} else {
   echo '<div class="Info Empty">'.sprintf(T('You do not have any Highlighted Discussion Posts, you can purchase them %s.'),Anchor('here',C('Plugins.MarketPlace.StoreURI','store').'/type/PurchaseHighlightedDiscussions')).'</div>';
}
?>
</div>
<?php
