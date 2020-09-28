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
    <h2 style="text-align: center;">Hello {{ $data->first_name. " " . $data->last_name }},</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <center><a href="{{ $url }}" >Reset Password</a></center>
    <p>{{ Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]) }}</p>
    <p>If you did not request a password reset, no further action is required.</p>
    <hr>
    <p>If you’re having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: <a href="{{ $url }}">{{ $url }}</a></p>
</div>
</body>
