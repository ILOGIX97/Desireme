<!DOCTYPE html>
<html>
<head>
    <style>
        .row {
            width: 50%;
            vertical-align: center;
            /*min-width: 300px;*/
        }
        .row center a:link, .row center a:visited {
            background-color: #575350;
            color: white;
            padding: 10px 25px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .row center a:hover, .row center a:active {
            background-color: #575350;
        }
    </style>
</head>
<body>
<div class="row">
    <h2 style="text-align: left;">Hello ,</h2>
    <p style="text-align: left;">This mail is regarding query from <b>{{$data['name']}}</b></p>
    <p style="text-align: left;">User email :  <b>{{$data['email']}}</b> </p>
    <p style="text-align: left;">Query : <b>{{$data['message']}}</b> </p>
</div>
</body>
