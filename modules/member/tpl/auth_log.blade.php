@include('header.html')
@load('css/member.css')

<table class="x_table x_table-striped x_table-hover">
	<thead>
		<tr>
			<th>{{ $lang->member }}</th>
			<th>{{ $lang->email_address }}</th>
			<th>{{ $lang->phone_number }}</th>
			<th class="title">{{ $lang->auth_log_action_type }}</th>
			<th>{{ $lang->date }}</th>
			<th>{{ $lang->ipaddress }}</th>
		</tr>
	</thead>
	@if ($auth_log && count($auth_log) > 0)
		@foreach ($auth_log as $log)
			<tbody>
				<tr>
					<td>
						@if ($log->member_srl && $log->nick_name)
							<a href="#popup_menu_area" class="member_{{ $log->member_srl }}">{{ $log->nick_name }}</a>
						@else
							{{ $log->member_srl ? $lang->auth_log_left_member : $lang->auth_log_guest }}
						@endif
					</td>
					<td>
						{{ $log->email_address ?: '—' }}
					</td>
					<td>
						{{ $log->phone_number ?: '—' }}
					</td>
					<td>
						<span @class(['auth_log_failed' => str_ends_with($log->action_type, 'failed')])>
							{{ isset($lang->auth_log_action_types[$log->action_type]) ? $lang->auth_log_action_types[$log->action_type] : $log->action_type }}
						</span>
					</td>
					<td>
						{{ zdate($log->regdate, 'Y-m-d H:i:s') }}
					</td>
					<td>
						{{ $log->ipaddress }}
					</td>
				</tr>
			</tbody>
		@endforeach
	@else
		<tbody>
			<tr>
				<td colspan="6" style="text-align: center">{{ $lang->no_data }}</td>
			</tr>
		</tbody>
	@endif
</table>

<form action="./" method="get" class="search center x_input-append" no-error-return-url="true">
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<select name="action_type" style="margin-right:4px" title="{{ $lang->auth_log_action_type }}">
		<option value="" @selected(!isset($action_type) || $action_type == '')>{{ $lang->all }}</option>
		@foreach ($lang->auth_log_action_types as $key => $val)
			<option value="{{ $key }}" @selected(isset($action_type) && $action_type == $key)>{{ $val }}</option>
		@endforeach
	</select>
	<select name="search_target" style="margin-right:4px" title="{{ $lang->search_target }}">
		<option value="ipaddress" @selected(isset($search_target) && $search_target == 'ipaddress')>{{ $lang->ipaddress }}</option>
		<option value="user_id" @selected(isset($search_target) && $search_target == 'user_id')>{{ $lang->user_id }}</option>
		<option value="email_address" @selected(isset($search_target) && $search_target == 'email_address')>{{ $lang->email_address }}</option>
		<option value="phone_number" @selected(isset($search_target) && $search_target == 'phone_number')>{{ $lang->phone_number }}</option>
		<option value="user_name" @selected(isset($search_target) && $search_target == 'user_name')>{{ $lang->user_name }}</option>
		<option value="nick_name" @selected(isset($search_target) && $search_target == 'nick_name')>{{ $lang->nick_name }}</option>
	</select>
	<input type="search" name="search_keyword" value="{{ $search_keyword ?? '' }}" style="width:140px">
	<button class="x_btn x_btn-inverse" type="submit">{{ $lang->cmd_search }}</button>
</form>

<div class="x_clearfix">
	<div class="x_pagination x_pull-left">
		<ul>
			<li class="x_disabled" @class(['x_disabled' => !$page || $page == 1])>
				<a href="{getUrl('page','','module_srl','')}" class="direction">&laquo; {{ $lang->first_page }}</a>
			</li>
			<!--@while($page_no = $page_navigation->getNextPage())-->
			{@$last_page = $page_no}
			<li class="x_active" @class(['x_active' => $page_no == $page])>
				<a href="{getUrl('page', $page_no)}">{{ $page_no }}</a>
			</li>
			<!--@end-->
			<li class="x_disabled" @class(['x_disabled' => $page == $page_navigation->last_page])>
				<a href="{getUrl('page',$page_navigation->last_page,'module_srl','')}" class="direction">{{ $lang->last_page }} &raquo;</a>
			</li>

		</ul>
	</div>
</div>
