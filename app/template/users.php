<table border="1">
	<tr>
		<th>Username</th>
		<th>Password</th>
	</tr>
	<?php foreach($this->users['records'] as $user){?>
	<tr>
		<td><?php echo $user['username'];?></td>
		<td><?php echo $user['password'];?></td>
	</tr>
	<?php }?>
</table>