<div class="bgbox">
	<a href="/user/"><- Users</a><br />
	<h4>Add User</h4>
	<form id="addUserForm" method="post" action="/user/insertuser/" data-ajax="false">
		<input type="hidden" name="ajax" value="1" />
		
		<div data-role="fieldcontain">
			<label for="email">First Name:</label>
			<div class="timePicker">
				<input name="firstName" id="firstName" value="" />
			</div>
		</div>
		
		<div data-role="fieldcontain">
			<label for="email">Last Name:</label>
			<div class="timePicker">
				<input name="lastName" id="lastName" value="" />
			</div>
		</div>
		
		<div data-role="fieldcontain">
			<label for="email">* Email:</label>
			<div class="timePicker">
				<input name="email" id="email" value="" />
			</div>
		</div>
		
		<div data-role="fieldcontain">
			<label for="pass1">* Password:</label>
			<div class="timePicker">
				<input type="password" name="pass1" id="pass1" value="" />
			</div>
		</div>
		<div data-role="fieldcontain">
			<label for="pass2">* Verify Password:</label>
			<div class="timePicker">
				<input type="password" name="pass2" id="pass2" value="" />
			</div>
		</div>
		<div class="ui-field-contain">
			<label for="adminLevel">Admin Level:</label>
			<select name="adminLevel" id="adminLevel" data-inline="true">
				<option value="0">None</option>
				<option selected value="3">Employee</option>
				<option value="2">Manager</option>
				<!--<option value="1">Root Admin</option>-->
			</select>
		</div>
		<p>* Required Fields</p>
		<button type="button" data-inline="true" onclick="submitAddUserForm()">Add User</button>
	</form>
</div>