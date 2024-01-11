<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>Trades-Filter</title>
</head>

<body class="container border p-5 ">
    <h1 class="text-center alert alert-danger"> Create Trades Form</h1>
    <div>
        @if($errors->any())
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>


        @endif
    </div>

    <form method="post" action="{{route('trades.store')}}">
        @csrf
        @method('post')
        <div class="mb-3">
            <label for="exampleInputEmail1" class="form-label">Generic Name</label>
            <input type="text" class="form-control" name="generic" placeholder="Enter Generic Name">
        </div>
        <div class="mb-3">
            <label for="exampleInputPassword1" class="form-label">Brand</label>
            <input type="text" class="form-control" name="brand" placeholder="Enter Brand Name">
        </div>
        
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

</body>

</html>