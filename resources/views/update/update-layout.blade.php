<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex">
    <title>Document Management - Update</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background: #f6f5f2;
            overflow: auto;
            font-family: Roboto, 'Helvetica Neue', sans-serif;
            font-size: 16px;
            text-align: center;
        }

        .button {
            color: #fff;
            border: 1px solid transparent;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
            padding: 0 8px;
            min-width: 88px;
            line-height: 36px;
        }

        .button[disabled] {
            color: rgba(0, 0, 0, 0.26);
            cursor: default;
            box-shadow: none;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding-top: 80px;
        }

        .panel {
            background: #fff;
            box-shadow: 1px 1px 2px 0 #d0d0d0;
            padding: 20px 30px 40px;
            margin-top: 50px;
            border-radius: 4px;
        }

        p {
            margin: 15px 0 25px 0;
        }

        h3 {
            font-weight: 400;
            font-size: 18px;
        }

        ul {
            list-style: none;
        }

        li {
            padding: 15px 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.12);
            color: #f2564d;
        }

        li:last-of-type {
            border: none;
        }
    </style>
</head>

<body>
    <div class="container">
        @yield('content')
    </div>
</body>

</html>