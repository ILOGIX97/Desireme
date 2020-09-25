
<!DOCTYPE html>
<html>
<head>

</head>
<body>
	<h2 style="text-align: center;">Hello {{$data['name']}} , Thank you for registration</h2>
    <div class="row">
		<div style="width:50%;float: center;">
			<p>Please varify your registration by click on below link</p>
        </div>
        <div style="width:50%;float: center;">
            <a href="{{ $data['url'] }}">Click Me</a>
		</div>
	</div>
</body>
