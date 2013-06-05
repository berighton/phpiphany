<?php
/**
 * Print view to output a listing of a supplied entities array
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


if (isset($entities) and $entities){
	$user = get_loggedin_user();
	$output = '';
	foreach ($entities as $entity){
		if ($entity->type == 'group'){
			$action = "<a href='{$assets_dir}groups/update/{$entity->guid}' title='Update group details' class='btn'><i class='icon-pencil'></i> Edit</a>";
			if ($user->admin or $user->guid == $entity->owner_guid) {
				$action .= " <a href='{$assets_dir}groups/delete/{$entity->guid}?" . generate_token(true) . "' title='Delete' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a>";
			}
			$entity->name = ucwords($entity->name);
			$entity->subtype = ucwords($entity->subtype);
			$entity->description = strip_tags($entity->description);
			$output .= "<div class='content-wrapper entity group-medium'><span class='right'>$action</span>Name: <a href='{$assets_dir}groups/view/{$entity->guid}'><strong>$entity->name</strong></a>" .
						"<br>Subtype: $entity->subtype<br>Description: $entity->description<br></div><br>\n			";
		} elseif ($entity->type == 'user'){
			$action = "<a href='{$assets_dir}users/update/{$entity->guid}' title='Update user details' class='btn'><i class='icon-pencil'></i> Edit</a>";
			if ($user->admin or $user->guid == $entity->owner_guid) {
				$action .= " <a href='{$assets_dir}users/delete/{$entity->guid}?" . generate_token(true) . "' title='Delete' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a>";
			}
			$entity->subtype = ucwords($entity->subtype);
			$output .= "<div class='content-wrapper entity user-medium'><span class='right'>$action</span>Name: <a href='{$assets_dir}users/view/{$entity->guid}'><strong>$entity->name</strong></a>" .
						"<br>Subtype: $entity->subtype<br>Email: $entity->email<br></div><br>\n			";
		} elseif ($entity->subtype == 'file'){
			$action = '';
			if ($user->admin or $user->guid == $entity->owner_guid) {
				$action = " <a href='{$assets_dir}download/delete/{$entity->guid}?" . generate_token(true) . "' title='Delete' class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</a>";
			}
			$entity->subtype = ucwords($entity->subtype);
			$size = readable_size($entity->size);
			$icon = $entity->get_icon();
			$tooltip = "Full path: $entity->path<br>Original name: $entity->original_name<br>MIME type: $entity->mime_type<br>Size: $size";
			$output .= "<div class='content-wrapper entity' style='background: url(\"$icon\") no-repeat 1% 50%; background-size: 64px 64px; padding-left: 90px;'>\n				<span class='right'>$action</span>" .
						"\n				Filename: <a href='{$assets_dir}download/{$entity->guid}' onmouseover=\"tooltip.show('$tooltip');\" onmouseout=\"tooltip.hide();\"><strong>$entity->name</strong></a>" .
						"<br>Description: $entity->description<br>Downloads: $entity->download_counter<br></div><br>\n			";
		}
	}
	echo $output;
}
