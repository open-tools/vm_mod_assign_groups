<?php
/**
 * mod_vmassign_groups - Virtuemart Buyers to Joomla! Groups bridge
 * @copyright (C) 2014 Reinhold Kainhofer, Open Tools
 * Based on code @copyright (C) 2003-2012 Nordmograph
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2
 * @link http://www.open-tools.net/ Official website
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );
$db						= &JFactory::getDBO();	
$lang 					= &JFactory::getLanguage();
$langtag 				= $lang->get('tag');
$dblangtag 				= strtolower(str_replace( '-' , '_' , $langtag ) );

$selectsize				=  $params->get('selectsize',10);
$product_ids 			= JRequest::getVar('vmassigngroups_productids', '');
$group_ids 				= JRequest::getVar('vmassigngroups_groupids', '');
$message = '';
// jimport( 'joomla.access.access' );
jimport( 'joomla.user.helper' );


function getGroupsMap() {
	$db = JFactory::getDbo();
	$db->setQuery(
		'SELECT a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level' .
		' FROM #__usergroups AS a' .
		' LEFT JOIN '.$db->quoteName('#__usergroups').' AS b ON a.lft > b.lft AND a.rgt < b.rgt' .
		' GROUP BY a.id, a.title, a.lft, a.rgt' .
		' ORDER BY a.lft ASC'
	);
	$options = $db->loadObjectList();

	// Check for a database error.
	if ($db->getErrorNum()) {
		JError::raiseNotice(500, $db->getErrorMsg());
		return null;
	}

	$res = array();
	foreach ($options as $option) {
		$res[$option->value] = $option->text;
	}
	return $res;
}


if($product_ids!='' && $group_ids!=''){
	$q = "SELECT DISTINCT(vo.`virtuemart_user_id`) AS user_id , u.`name`
		FROM `#__virtuemart_orders` vo 
		LEFT JOIN `#__virtuemart_order_items` voi ON voi.`virtuemart_order_id` =  vo.`virtuemart_order_id` 
		LEFT JOIN `#__users` AS u ON  u.`id` = ABS(vo.`virtuemart_user_id`) 
		WHERE voi.`virtuemart_product_id` IN (".join(',',$product_ids).") AND voi.`order_status` IN (\"S\", \"C\")";
	$db->setQuery($q);
	$cust_datas = $db->loadObjectList();
	$groupnames = getGroupsMap();

	foreach ($cust_datas as $cust_data){
		$currentgroups = JUserHelper::getUserGroups($cust_data->user_id);
	
		$addedgroups = array();
		foreach (array_diff($group_ids, $currentgroups) as $g) {
			$ret = JUserHelper::addUserToGroup($cust_data->user_id, $g);
			if (is_a($ret, 'Exception')) {
				JFactory::getApplication()->enqueueMessage(JText::sprintf("Unable to assign user '%s' to group '%s': %s", $cust_data->name,$groupnames[$g], $ret->getMessage() ), 'error');
			} else {
				$addedgroups[] = $groupnames[$g];
			}
		}
		if (!empty($addedgroups)) {
			echo "<p class='vmassign_group_result' style=\"border-bottom: dashed 1px #8080FF; background: #E0E0FF; margin: 2px 5px; padding: 1px 5px; \">Added user '".$cust_data->name."' (".$cust_data->user_id.") to groups: ".join(", ", $addedgroups)."</p>";
		}
	}
}




/* All Virtuemart Products */
$q = "SELECT `virtuemart_product_id` AS product_id , `product_name` FROM `#__virtuemart_products_".$dblangtag."` ORDER BY `product_name` ";
$db->setQuery($q);
$products = $db->loadObjectList();
$products_select = '<select name="vmassigngroups_productids[]" MULTIPLE size="'.$selectsize.'">';
foreach($products as $product){
	$products_select .= '<option value="'.$product->product_id.'">'.$product->product_name.'</option>';
}
$products_select .= '</select>';




/* All Joomla user groups */
function getGroups() {
	$db = JFactory::getDbo();
	$db->setQuery(
		'SELECT a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level' .
		' FROM #__usergroups AS a' .
		' LEFT JOIN '.$db->quoteName('#__usergroups').' AS b ON a.lft > b.lft AND a.rgt < b.rgt' .
		' GROUP BY a.id, a.title, a.lft, a.rgt' .
		' ORDER BY a.lft ASC'
	);
	$options = $db->loadObjectList();

	// Check for a database error.
	if ($db->getErrorNum()) {
		JError::raiseNotice(500, $db->getErrorMsg());
		return null;
	}

	foreach ($options as &$option) {
		$option->text = str_repeat('- ', $option->level).$option->text;
	}
	return $options;
}
$lists_select = '<select name="vmassigngroups_groupids[]" MULTIPLE size="'.$selectsize.'">';
foreach(getGroups() as $group){
	$lists_select .= '<option value="'.$group->value.'">'.$group->text.'</option>';
}
$lists_select .= '</select>';


?>
<div style="text-align:center;">
<form>
  <table class="category" width="100%">
    <tr>
      <td style="vertical-align:top;text-align:left;" width="50%"><div ><label>Add Customers for these products: <br />(multiple select)</label><br/>
        <?php echo $products_select; ?>
      </div></td>
 
      <td style="vertical-align:top;text-align:left;" width="50%"><div ><label>To these Joomla! groups:<br />(multiple select)</label><br />
          <?php echo $lists_select; ?>
        </div></td>
      </tr>
  </table>
  <input type="submit" name="assign" id="assign" class="button" value="Assign" /><br /><br />
  </form>

<a href="index.php?option=com_users&view=group&layout=edit">Add an Joomla user group</a> | <a href="index.php?option=com_modules&view=module&layout=edit&id=<?php echo $module->id; ?>">Edit module</a> | <a href="http://www.open-tools.net/" target="_blank">Open Tools Support</a>
  </div>
<?php

  echo '<div style="margin:10px;">'.$message.'</div>';
  