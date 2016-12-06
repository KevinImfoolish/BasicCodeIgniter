<style>


	/* 宽度在640像素以上的设备 */
	@media only screen and (min-width:641px)
	{

	}
	
	/* 宽度在960像素以上的设备 */
	@media only screen and (min-width:961px)
	{

	}

	/* 宽度在1280像素以上的设备 */
	@media only screen and (min-width:1281px)
	{

	}
</style>

<ol id=breadcrumb class=breadcrumb>
	<li><a href="<?php echo base_url() ?>">首页</a></li>
	<li><a href="<?php echo base_url($this->class_name) ?>"><?php echo $this->class_name_cn ?></a></li>
	<li class=active><?php echo $title ?></li>
</ol>

<div id=content>
	<div class=btn-group role=group>
		<a type=button class="btn btn-default" title="所有<?php echo $this->class_name_cn ?>" href="<?php echo base_url($this->class_name) ?>"><i class="fa fa-list fa-fw" aria-hidden=true></i> 所有<?php echo $this->class_name_cn ?></a>
		<?php
		// 需要特定角色和权限进行该操作
		$role_allowed = array('管理员');
		$level_allowed = 1;
		if (in_array($this->session->role, $role_allowed) & $this->session->level >= $level_allowed):
		?>
	  	<a type=button class="btn btn-default" title="创建<?php echo $this->class_name_cn ?>" href="<?php echo base_url($this->class_name.'/create') ?>"><i class="fa fa-plus fa-fw" aria-hidden=true></i> 创建<?php echo $this->class_name_cn ?></a>
	  	<a type=button class="btn btn-default" title="回收站" href="<?php echo base_url($this->class_name.'/trash') ?>"><i class="fa fa-trash fa-fw" aria-hidden=true></i> 回收站</a>
		<?php endif ?>
	</div>

	<?php
		$data_to_display = array(
			'name' => '名称',
			'name' => '名称',
			'name' => '名称',
		);
	?>
	<table class="table table-striped table-condensed table-responsive">
		<thead>
			<tr>
				<th><?php echo $this->class_name_cn ?>ID</th>
				<?php
					$thead = array_values($data_to_display);
					foreach ($thead as $th):
						echo '<th>' .$th. '</th>';
					endforeach;
				?>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($items as $item): ?>
			<tr>
				<td><?php echo $item[$this->id_name] ?></td>
				<?php
					$tr = array_keys($data_to_display);
					foreach ($tr as $td):
						echo '<td>' .$item[$td]. '</td>';
					endforeach;
				?>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>

	<div class="alert alert-warning" role=alert>
		<p>确定要<?php echo $title ?>？</p>
	</div>

	<?php
		if (isset($error)) echo '<div class="alert alert-warning" role=alert>'.$error.'</div>';
		$attributes = array('class' => 'form-'.$this->class_name.'-delete form-horizontal', 'role' => 'form');
		echo form_open($this->class_name.'/delete', $attributes);
	?>
		<fieldset>
			<input name=ids type=hidden value="<?php echo implode('|', $ids) ?>">
			<div class=form-group>
				<label for=url_cn class="col-sm-2 control-label">密码</label>
				<div class=col-sm-10>
					<input class=form-control name=password type=password size=6 pattern="\d{6}" placeholder="请输入您的登录密码" autofocus required>
					<?php echo form_error('password') ?>
				</div>
			</div>
		</fieldset>

		<div class=form-group>
		    <div class="col-sm-offset-2 col-sm-10">
				<button class="btn btn-danger" type=submit>删除</button>
		    </div>
		</div>
	</form>
</div>