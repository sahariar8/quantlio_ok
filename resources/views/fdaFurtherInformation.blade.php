<!DOCTYPE html>
<html>
   <head>
      <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="css/careview-print.css" type="text/css">
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            th, td {
                text-align: left;
                padding: 16px;
            }
            table#example1 {
                border-collapse: collapse;
                border-spacing: 0;
                width: 100%;
                border: 1px solid #ddd;
            }
            thead tr th {
                border: 1px solid #111;
            }
            thead {
                background: #ccc;
            }
            .grid-container {
                overflow-y: auto;
                border-bottom: 1px solid #111;
            }
            .grid {
                position: relative;
                width: 100%;
                margin: 0 0 20% 0;
            }
            tr td {
                vertical-align: top;
                border-left: 1px solid #111;
            }
            img.new-star-print-logo {
                display: block;
                width: 50%;
                margin: 30px auto;
            }
        </style>
   </head>
   <body style="margin:0px; padding:0px;">
<header>
<img style="width: 250px;" src="images/newstar.png" class="new-star-print-logo">
</header>
<div class="grid">
    <div class="grid-container">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>{{ __('Test') }}</th>
                    <th>{{ __('Contraindications') }}</th>
                    <th>{{ __('Precautions') }} </th>
                    <th>{{ __('Warnings And Cautions') }} </th>
                    <th>{{ __('Drug Interactions') }} </th>
                    <th>{{ __('Warnings') }} </th>
                </tr>
            </thead>
            <tbody>
                @foreach($fdaTestDetails as $fdaTestDetail)
                    <tr>
                    <td>{{ $fdaTestDetail['test_name'] }}</td>
                    <td><div>{{ $fdaTestDetail['contraindications'] }}</div></td>
                    <td><div>{{ $fdaTestDetail['precautions'] }}</div></td>
                    <td><div>{{ $fdaTestDetail['warnings_and_cautions'] }}</div></td>
                    <td><div>{{ $fdaTestDetail['drug_interactions'] }}</div></td>
                    <td><div>{{ $fdaTestDetail['warnings'] }}</div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
    </body>
</html>