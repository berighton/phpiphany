<?php
/**
 * View to create an entity full view showing most (if not all) details stored in the database
 * It is assumed that ACL filtering already has been applied
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package view/entities
 * @since 1.0
 *
 */


if (isset($entity) and $entity){
	$user = get_loggedin_user();
	$title = $entity->name;
	if ($entity->type == 'group'){
		$action = "<a href='{$assets_dir}groups/update/{$entity->guid}' title='Update group details' class='btn'><i class='icon-pencil'></i> Edit</a>";
		if ($user->admin or $user->guid == $entity->owner_guid) {
			$action .= " <a href='{$assets_dir}groups/delete/{$entity->guid}?" . generate_token(true) . "' title='Delete' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a>";
		}
		$class = 'group-big';
		$entity->subtype = ucwords($entity->subtype);
		$members = '';
		if (isset($entity->members) and $entity->members){
			$members = 'Members: ';
			foreach ($entity->members as $guid){
				$member = orm::get($guid);
				$members .= "<a href='{$assets_dir}users/view/$member->guid'><strong>$member->name</strong></a>,\n			";
			}
			$members = substr($members, 0, -5) . "<br>\n			";
		}
		$owner = orm::get($entity->owner_guid);
		$details = "Subtype: $entity->subtype<br>
			{$members}Access: $entity->access<br>
			Owner: <a href='{$assets_dir}users/view/$owner->guid'><strong>$owner->name</strong></a><br>
			Created Date: $entity->created<br>
			Description: $entity->description<br>";
	} elseif ($entity->type == 'user'){
		$action = "<a href='{$assets_dir}users/update/{$entity->guid}' title='Update user details' class='btn'><i class='icon-pencil'></i> Edit</a>";
		if ($user->admin or $user->guid == $entity->owner_guid) {
			$action .= " <a href='{$assets_dir}users/delete/{$entity->guid}?" . generate_token(true) . "' title='Delete' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a>";
		}
		$class = 'user-big';
		$entity->subtype = ucwords($entity->subtype);
		$groups = '';
		if (isset($entity->membership) and $entity->membership){
			$groups = 'Groups: ';
			foreach ($entity->membership as $guid){
				$group = orm::get($guid);
				$groups .= "<a href='{$assets_dir}groups/view/$group->guid'><strong>$group->name</strong></a>,\n			";
			}
			$groups = substr($groups, 0, -5) . "<br>\n			";
		}
		$owner = orm::get($entity->owner_guid);
		$details = "Subtype: $entity->subtype<br>
			First Name: $entity->fname<br>
			Last Name: $entity->lname<br>
			Username: $entity->username<br>
			Email: <a href='mailto:$entity->email'>$entity->email</a><br>
			Language: $entity->language<br>
			{$groups}Last Login: $entity->last_login<br>
			Created Date: $entity->created<br>";
	}

	echo <<<HTML

		<div class="page-header">
			<h1>$title</h1>
		</div>
		<div class="content-wrapper $class">
			<span class="right">$action</span>
			$details
		</div>
		<br>

HTML;

}