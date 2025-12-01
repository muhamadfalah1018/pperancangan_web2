<?php include('pagination.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Pagination PHP</title>

    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
</head>
<body>

<div class="container">
    <h2 class="text-center">Pagination PHP</h2>
    <hr>

    <table class="table table-bordered table-striped">
        <tr>
            <th>UserID</th>
            <th>Firstname</th>
            <th>Lastname</th>
            <th>Username</th>
        </tr>

        <?php while ($crow = mysqli_fetch_array($nquery)) { ?>
            <tr>
                <td><?= $crow['userid'] ?></td>
                <td><?= $crow['firstname'] ?></td>
                <td><?= $crow['lastname'] ?></td>
                <td><?= $crow['username'] ?></td>
            </tr>
        <?php } ?>
    </table>

    <div style="margin-top: 20px;">
        <?= $paginationCtrls ?>
    </div>

</div>

</body>
</html>
