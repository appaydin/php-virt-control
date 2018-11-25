<?php
	$skip = false;
	$p_user = array_key_exists('user', $_POST) ? $_POST['user'] : false;
	$p_perms= array_key_exists('perm', $_POST) ? $_POST['perm'] : array();
	$action = array_key_exists('action', $_GET) ? $_GET['action'] : false;

	$tmp = explode("\n", base64_decode($_SESSION['logged_in']));
	$user = $tmp[0];

	$myId = false;
	$tmp = $db->get_users();
	for ($i = 0; $i < sizeof($tmp); $i++) {
		if ($tmp[$i]['name'] == $user)
			$myId = $tmp[$i]['id'];
	}

	$uid = array_key_exists('user_id', $_GET) ? $_GET['user_id'] : false;

	if ((($action == 'new') && (verify_user($db, USER_PERMISSION_USER_CREATE)))
		|| ((($action == 'edit') && (verify_user($db, USER_PERMISSION_USER_EDIT))) || ($uid == $myId))):
		$skip = true;
		$skip_add_dlg = false;
		if (array_key_exists('user', $_POST)) {
			if ($_POST['password'] != $_POST['password2']) {
				echo '<div id="msg">'.$lang->get('msg').': '.$lang->get('password_mismatch').'</div>';
			}
			else {
				$perms = 0;
				foreach($user_permissions as $key => $value) {
					if (in_array($key, $p_perms))
						eval('$perms |= '.$key.';');
				}

				reset($user_permissions);

				if (!verify_user($db, USER_PERMISSION_USER_EDIT))
					$perms = false;

				$skip_add_dlg = true;
				if ($action == 'new') {
					if ($db->user_add($_POST['user'], $_POST['password'], $perms))
						$msg = $lang->get('user_added');
					else
						$msg = $lang->get('error');
				}
				else {
					if ($db->user_edit($_GET['user_id'], $_POST['user'], $_POST['password'], $perms))
						$msg = $lang->get('user_edited');
					else
						$msg = $lang->get('error');
				}

				echo '<div id="msg">'.$lang->get('msg').': '.$msg.'</div>';
			}
		}

		if (!$skip_add_dlg):
			$ident = ($action == 'new') ? 'user_add' : 'user_edit';

			if ($action == 'edit') {
				$tmp = $db->get_users();

				for ($i = 0; $i < sizeof($tmp); $i++) {
					if ($tmp[$i]['id'] == $_GET['user_id']) {
						$p_user = $tmp[$i]['name'];
						$perms = $tmp[$i]['permissions'];

						$p_perms = array();
						while (list($key, $val) = each($user_permissions))
							eval('if ($perms & '.$key.') $p_perms[] = "'.$key.'";');

						reset($user_permissions);
					}
				}
			}
?>
<div id="content">

<div class="section"><?php echo $lang->get($ident) ?></div>

<form method="POST">

<div class="item">
        <div class="label"><?php echo $lang->get('user') ?></div>
        <div class="value"><input type="text" name="user" value="<?php echo $p_user ?>" /></div>
        <div class="nl">
</div>

<div class="item">
        <div class="label"><?php echo $lang->get('password') ?></div>
        <div class="value"><input type="password" name="password" /></div>
        <div class="nl">
</div>

<div class="item">
        <div class="label"><?php echo $lang->get('confirm_password') ?></div>
        <div class="value"><input type="password" name="password2" /></div>
        <div class="nl">
</div>

<div class="item">
        <div class="label"><?php echo $lang->get('permissions') ?></div>
        <div class="value">
<?php
	$readonly_perms = (!verify_user($db, USER_PERMISSION_USER_EDIT)) ? 'disabled="disabled"' : '';

	foreach ($user_permissions as $key => $value) {
		$val = in_array($key, $p_perms) ? 'checked="checked"' : '';
		if ($key == 'USER_PERMISSION_BASIC')
			$val = 'checked="checked" disabled="disabled"';
		echo "<input type=\"checkbox\" name=\"perm[]\" value=\"$key\" $val $readonly_perms/> ".$lang->get($value)."<br />";
	}
?>
	</div>
        <div class="nl">
</div>

<div class="item">
        <div class="label">&nbsp;</div>
	<div class="value">
		<br />
		<input type="submit" value=" <?php echo $lang->get($ident.'_btn') ?>" />
	</div>
</div>

</form>

<?php
		endif;
	elseif ($action == 'del'):
		$tmp = $db->get_users();
		for ($i = 0; $i < sizeof($tmp); $i++) {
			if ($tmp[$i]['id'] == $_GET['user_id'])
				$name = $tmp[$i]['name'];
		}

		if (array_key_exists('confirmed', $_GET) && $_GET['confirmed']):
			if ($db->user_del($_GET['user_id'], $name))
				$msg = $lang->get('user_deleted');
			else
				$msg = $lang->get('error');

		echo '<div id="msg">'.$lang->get('msg').': '.$msg.'</div>';
		else:
?>

<div id="content">

<div class="section"><?php echo $lang->get('user_del') ?></div>
<table id="form-table">
	<tr>
		<td colspan="3"><?php echo $lang->get('user_del_confirm').'.'. $lang->get('user').': '.$name ?></u></td>
	</tr>
	<tr align="center">
		<td><a href="<?php echo $_SERVER['REQUEST_URI'] ?>&amp;confirmed=1"><?php echo $lang->get('Yes') ?></a></td>
                  <td><a href="?page=<?php echo $page ?>"><?php echo $lang->get('No') ?></a></td>
		</td>
	</tr>
</table>
<?php
		endif;
	endif;

	function translate_permissions($perms) {
		global $lang;

		$str = array();
		if ($perms & USER_PERMISSION_SAVE_CONNECTION)
			$str[] = $lang->get('permission_save_connection');
		if ($perms & USER_PERMISSION_VM_CREATE)
			$str[] = $lang->get('permission_vm_create');
		if ($perms & USER_PERMISSION_VM_EDIT)
			$str[] = $lang->get('permission_vm_edit');
		if ($perms & USER_PERMISSION_VM_DELETE)
			$str[] = $lang->get('permission_vm_delete');
		if ($perms & USER_PERMISSION_NETWORK_CREATE)
			$str[] = $lang->get('permission_network_create');
		if ($perms & USER_PERMISSION_NETWORK_EDIT)
			$str[] = $lang->get('permission_network_edit');
		if ($perms & USER_PERMISSION_NETWORK_DELETE)
			$str[] = $lang->get('permission_network_delete');
		if ($perms & USER_PERMISSION_USER_CREATE)
			$str[] = $lang->get('permission_user_create');
		if ($perms & USER_PERMISSION_USER_EDIT)
			$str[] = $lang->get('permission_user_edit');
		if ($perms & USER_PERMISSION_USER_DELETE)
			$str[] = $lang->get('permission_user_delete');

		if (empty($str))
			return '-';

		return implode(', ', $str);
	}

	if (!$skip):
?>
<div id="content">

<div class="section"><?php echo $lang->get('users') ?></div>

<table id="domain-list">
<?php
	if (verify_user($db, USER_PERMISSION_USER_CREATE)):
?>
  <tr>
    <td>
      <a href="?page=users&amp;action=new"><?php echo $lang->get('create-new-user') ?></a>
    </td>
  </tr>
<?php
	endif;
?>
  <tr>
    <th><?php echo $lang->get('user') ?></th>
    <th><?php echo $lang->get('permissions') ?></th>
    <th><?php echo $lang->get('action') ?></th>
  </tr>
<?php
	$users = $db->get_users();
	for ($i = 0; $i < sizeof($users); $i++):

		$ok = (($users[$i]['name'] == $user) || (verify_user($db, USER_PERMISSION_USER_EDIT) || verify_user($db, USER_PERMISSION_USER_DELETE)));
		if ($ok):
?>
  <tr>
    <td><?php echo $users[$i]['name'] ?></td>
    <td><?php echo translate_permissions($users[$i]['permissions']) ?></td>
    <td>
	<a href="?page=users&amp;action=edit&amp;user_id=<?php echo $users[$i]['id'] ?>">
		<?php echo $lang->get('user_edit') ?>
	</a>
	|
	<a href="?page=users&amp;action=del&amp;user_id=<?php echo $users[$i]['id'] ?>">
		<?php echo $lang->get('user_del') ?>
	</a>
    </td>
  </tr>
<?php
		endif;
	endfor;
?>
</table>

</div>
<?php
	endif;
?>
