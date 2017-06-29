<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>{{ level }}</title>
	</head>
	<body>
	<code>
		<h1>Ew.. {{ level }}</h1>
		<h3>{{ message }}</h3>
		<b>File</b>: {{ file }}:<span style="color:#f92672">{{ line }}</span><br><br>
		{{ backtrace }}
			{{ # }} {{ file }}:<span style="color:#f92672">{{ line }}</span> {{ function }}()</br>
		{{ /backtrace }}
		</code>
	</body>
</html>
