<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @font-face {
            font-family: 'ipaexg';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/ipaexg.ttf') }}) format('truetype');
        }

        * {
            font-family: 'ipaexg', sans-serif;
        }

        body {
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #333;
        }

        .period {
            color: #666;
            margin-top: 5px;
        }

        .stats {
            margin: 30px 0;
        }

        .stats table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats th,
        .stats td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .stats th {
            background-color: #f2f2f2;
            font-weight: normal;
        }

        .stats tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 50px;
            text-align: right;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 style="font-family: 'ipaexg', sans-serif; font-weight: normal;">{{ $title }}</h1>
        <p class="period">期間: {{ $period }}</p>
    </div>

    <div class="stats">
        <table>
            <thead>
                <tr>
                    <th>項目</th>
                    <th>値</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>総Todo数</td>
                    <td>{{ $total }} 件</td>
                </tr>
                <tr>
                    <td>完了数</td>
                    <td>{{ $done }} 件</td>
                </tr>
                <tr>
                    <td>未完了数</td>
                    <td>{{ $active }} 件</td>
                </tr>
                <tr>
                    <td>今週完了したTodo</td>
                    <td>{{ $weekly_completed }} 件</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>生成日時: {{ now()->format('Y年m月d日 H:i:s') }}</p>
    </div>
</body>

</html>
