<h1><a href="$Link">$Title</a></h1>

<h2>$SubTitle</h2>

<p>$Content</p>

<ol>
	<li><a href="{$Link}build" target="_blank">Build</a></li>
	<li><a href="{$Link}populate" target="_blank">Populate</a></li>
	<li><a href="{$Link}creat">Create</a></li>
</ol>

$Form

<% if TestObjects %>
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Title</th>
				<th>Description</th>
				<th>Code</th>
				<th>Operations</th>
			</tr>
		</thead>
		<tbody>
			<% loop TestObjects %>
			<tr>
				<td>$ID</td>
				<td>$Title</td>
				<td>$Description</td>
				<td>$Code</td>
				<td>
					<a href="{$Top.Link}read?RID={$ID}">read</a> 
					<a href="{$Top.Link}update?RID={$ID}">update</a> 
					<a href="{$Top.Link}delete?RID={$ID}">delete</a>
				</td>
			</tr>
			<% end_loop %>
		</tbody>
	</table>
<% end_if %>